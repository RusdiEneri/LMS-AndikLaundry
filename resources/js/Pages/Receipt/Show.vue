<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import {
    CheckCircle2,
    Clock,
    Scale,
    Printer,
    MessageCircle,
    Phone,
    MapPin,
    AlertCircle,
    ChevronRight,
    Sparkles,
    Shirt,
    ShieldCheck,
    Search,
    Truck,
} from 'lucide-vue-next';
import { formatRupiah, formatDateTimeIndo } from '@/Support/whatsapp';
import { router } from '@inertiajs/vue3';

interface OrderItem {
    id: number;
    item_name: string;
    pricing_unit: string;
    unit_price: number;
    actual_quantity: number;
    billed_quantity: number;
    discount_amount: number;
    subtotal: number;
    perfume_variant?: string | null;
    item_condition_notes?: string | null;
    special_instructions?: string | null;
}

interface OrderPayment {
    id: number;
    payment_number: string;
    payment_method: string;
    amount: number;
    tender_amount: number;
    change_amount: number;
    payment_status: string;
    paid_at?: string;
}

interface OrderHistory {
    id: number;
    from_status?: string | null;
    to_status: string;
    transition_notes?: string | null;
    created_at?: string;
    user_name?: string;
}

interface OrderData {
    id: number;
    invoice_number: string;
    production_status: string;
    production_status_label: string;
    payment_status: string;
    payment_status_label: string;
    intake_at: string;
    target_completion_at?: string | null;
    actual_completion_at?: string | null;
    picked_up_at?: string | null;
    subtotal_amount: number;
    discount_amount: number;
    surcharge_amount: number;
    tax_amount: number;
    total_amount: number;
    paid_amount: number;
    change_amount: number;
    outstanding_balance: number;
    staging_rack?: string | null;
    notes?: string | null;
    delivery_option?: string | null;
    customer: {
        id?: number | null;
        name: string;
        phone: string;
        phone_normalized?: string;
        address?: string;
    };
    cashier: {
        id?: number | null;
        name: string;
    };
    items: OrderItem[];
    payments: OrderPayment[];
    status_history: OrderHistory[];
}

interface StoreInfo {
    name: string;
    phone: string;
    address: string;
    paper_width?: string;
    receipt_footer?: string;
}

interface Props {
    order?: OrderData;
    store_info: StoreInfo;
}

const props = defineProps<Props>();

// 3-Step Production Workflow
const workflowSteps = [
    { key: 'diproses', label: 'Diproses', desc: 'Sedang dikerjakan' },
    { key: 'selesai', label: 'Selesai', desc: 'Siap diambil/diantar' },
    { key: 'sudah_diambil', label: 'Sudah Diambil', desc: 'Telah diterima' },
];

const currentStepIndex = computed(() => {
    if (!props.order) return -1;
    const status = props.order.production_status.toLowerCase().replace('_', '');
    const idx = workflowSteps.findIndex(s => s.key === status);
    return idx >= 0 ? idx : 0;
});

const isCancelled = computed(() => props.order?.production_status.toLowerCase() === 'cancelled');

// Payment Badge (LUNAS / BELUM LUNAS)
const paymentBadge = computed(() => {
    if (!props.order) return { text: '-', bg: 'bg-[#FAFAFA] text-[#6B6B6B] border-[#E8E8EC]' };
    const debt = Number(props.order.outstanding_balance || 0);

    if (debt <= 0) {
        return { text: 'LUNAS', bg: 'bg-[#10B981]/10 text-[#10B981] border-[#10B981]/20' };
    }
    return { text: 'BELUM LUNAS', bg: 'bg-[#EF4444]/10 text-[#EF4444] border-[#EF4444]/20' };
});

// WhatsApp Link
const whatsAppLink = computed(() => {
    const phone = (props.store_info.phone || '').replace(/\D/g, '');
    const cleanPhone = phone.startsWith('0') ? '62' + phone.slice(1) : (phone.startsWith('8') ? '62' + phone : phone);
    const msg = `Halo Andik Laundry, saya ingin konfirmasi pesanan nota ${props.order?.invoice_number || 'terbaru'}.`;
    return `https://wa.me/${cleanPhone}?text=${encodeURIComponent(msg)}`;
});

const storeContactWaLink = computed(() => {
    return whatsAppLink.value;
});

// Search functionality
const searchQuery = ref('');

function searchOrder() {
    if (searchQuery.value.trim()) {
        router.get(`/receipts/${encodeURIComponent(searchQuery.value.trim())}`);
    }
}

function printReceipt() {
    window.print();
}

// Map production status to step index for 3-step workflow
function getStatusStepIndex(status: string): number {
    const s = status.toLowerCase();
    if (s === 'diproses' || s === 'waiting' || s === 'washing' || s === 'drying' || s === 'ironing' || s === 'qc' || s === 'received') {
        return 0;
    }
    if (s === 'selesai' || s === 'ready_for_pickup' || s === 'completed') {
        return 1;
    }
    if (s === 'sudah_diambil' || s === 'picked_up' || s === 'taken') {
        return 2;
    }
    return 0;
}

const mappedStepIndex = computed(() => {
    if (!props.order) return -1;
    return getStatusStepIndex(props.order.production_status);
});

// Check if order is unpaid
const isUnpaid = computed(() => props.order && props.order.outstanding_balance > 0);
</script>

<template>
    <Head :title="order ? `Nota Digital - ${order.invoice_number} | ${store_info.name}` : `Cari Nota | ${store_info.name}`" />

    <div class="min-h-screen bg-[#FAFAFA] text-[#0A0A0A] font-sans pb-12 antialiased">
        <!-- Top Navbar -->
        <header class="bg-white border-b border-[#E8E8EC] sticky top-0 z-30 shadow-2xs no-print">
            <div class="max-w-3xl mx-auto px-4 py-3 flex items-center justify-between">
                <div class="flex items-center space-x-2.5">
                    <div class="h-9 w-9 rounded-[6px] bg-[#6366F1] text-white flex items-center justify-center font-bold text-sm shadow-xs shrink-0">
                        AL
                    </div>
                    <div>
                        <h1 class="font-bold text-sm text-[#0A0A0A] leading-tight">{{ store_info.name }}</h1>
                        <p class="text-[11px] text-[#6B6B6B]">Nota Digital & Status Pelacakan Live</p>
                    </div>
                </div>

                <div v-if="order" class="flex items-center space-x-2">
                    <button
                        type="button"
                        @click="printReceipt"
                        class="min-h-[44px] px-3.5 py-1.5 rounded-[6px] border border-[#E8E8EC] bg-white hover:bg-[#FAFAFA] text-[#0A0A0A] text-xs font-semibold shadow-2xs flex items-center space-x-1.5 transition-all focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-[#6366F1]/12"
                    >
                        <Printer class="h-4 w-4 text-[#6B6B6B]" />
                        <span class="hidden sm:inline">Cetak Nota</span>
                    </button>

                    <a
                        :href="storeContactWaLink"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="min-h-[44px] px-3.5 py-1.5 rounded-[6px] bg-[#25D366] hover:bg-[#128C7E] text-white text-xs font-bold shadow-xs flex items-center space-x-1.5 transition-all focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-[#25D366]/20"
                    >
                        <MessageCircle class="h-4 w-4" />
                        <span>Chat Kasir</span>
                    </a>
                </div>
            </div>
        </header>

        <!-- Main Content Container -->
        <main class="max-w-3xl mx-auto px-4 pt-6 space-y-6">
            <!-- No Order Found: Search Section -->
            <div v-if="!order" class="bg-white rounded-[12px] p-6 sm:p-8 border border-[#E8E8EC] text-center space-y-4">
                <div class="h-14 w-14 rounded-full bg-[#FAFAFA] border border-[#E8E8EC] flex items-center justify-center mx-auto text-[#6B6B6B]">
                    <Search class="h-6 w-6" />
                </div>
                <div>
                    <h2 class="font-bold text-lg text-[#0A0A0A]">Cari Nota Digital</h2>
                    <p class="text-sm text-[#6B6B6B] mt-1">Masukkan nomor nota atau nomor WhatsApp pelanggan</p>
                </div>
                <div class="flex flex-col sm:flex-row gap-2 max-w-md mx-auto">
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Contoh: AL-20261115-001"
                        class="flex-1 min-h-[44px] rounded-[6px] border border-[#E8E8EC] bg-white px-3 py-2 text-sm text-[#0A0A0A] placeholder:text-[#9C9C9C] focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-[#6366F1]/12"
                        @keyup.enter="searchOrder"
                    />
                    <button
                        type="button"
                        @click="searchOrder"
                        class="min-h-[44px] px-5 rounded-[6px] bg-[#6366F1] hover:bg-[#4F46E5] text-white font-bold text-sm transition-colors cursor-pointer shadow-[0_4px_12px_rgba(99,102,241,0.35)]"
                    >
                        Cari
                    </button>
                </div>
                <p class="text-xs text-[#6B6B6B]">
                    Atau buka langsung tautan unik yang dikirimkan ke pesan WhatsApp Anda.
                </p>
            </div>

            <!-- Order Found -->
            <template v-else>
                <!-- Order Header Card -->
                <div class="bg-white rounded-[12px] p-5 sm:p-6 border border-[#E8E8EC] space-y-4">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 border-b border-[#E8E8EC]">
                        <div>
                            <div class="flex items-center space-x-2 flex-wrap gap-2">
                                <span class="text-xs font-bold uppercase tracking-wider text-[#6366F1]">Nota Resmi</span>
                                <span :class="['px-2.5 py-0.5 rounded-[4px] text-[11px] font-bold border', paymentBadge.bg]">
                                    {{ paymentBadge.text }}
                                </span>
                            </div>
                            <h2 class="font-mono font-bold text-xl sm:text-2xl text-[#0A0A0A] mt-1 tracking-tight">
                                {{ order.invoice_number }}
                            </h2>
                        </div>

                        <div class="text-left sm:text-right">
                            <span class="text-xs text-[#6B6B6B] block">Total Tagihan</span>
                            <span class="font-mono font-bold text-2xl text-[#0A0A0A]">
                                {{ formatRupiah(order.total_amount) }}
                            </span>
                        </div>
                    </div>

                    <!-- Customer & Metadata Grid -->
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
                        <div>
                            <span class="text-[#6B6B6B] block text-[11px]">Nama Pelanggan</span>
                            <span class="font-bold text-[#0A0A0A]">{{ order.customer.name }}</span>
                        </div>
                        <div>
                            <span class="text-[#6B6B6B] block text-[11px]">Waktu Masuk</span>
                            <span class="font-medium text-[#0A0A0A]">{{ formatDateTimeIndo(order.intake_at) }}</span>
                        </div>
                        <div>
                            <span class="text-[#6B6B6B] block text-[11px]">Estimasi Selesai</span>
                            <span class="font-semibold text-[#6366F1]">{{ formatDateTimeIndo(order.target_completion_at) }}</span>
                        </div>
                        <div>
                            <span class="text-[#6B6B6B] block text-[11px]">Rak Pengambilan</span>
                            <span class="font-bold font-mono text-[#0A0A0A]">{{ order.staging_rack || 'Menunggu Rak' }}</span>
                        </div>
                    </div>

                    <!-- Rack Location Display when status is SELESAI -->
                    <div v-if="order.production_status.toLowerCase() === 'selesai' || order.production_status.toLowerCase() === 'ready_for_pickup'" class="p-4 bg-[#6366F1]/5 rounded-[8px] border border-[#6366F1]/20">
                        <div class="flex items-center space-x-2.5">
                            <MapPin class="h-5 w-5 text-[#6366F1] shrink-0" />
                            <div>
                                <span class="text-xs font-bold text-[#6366F1] block">Lokasi Rak Pengambilan:</span>
                                <span class="font-mono font-bold text-xl text-[#0A0A0A]">{{ order.staging_rack || 'Segera Ditentukan' }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Delivery Option Display -->
                    <div v-if="order.delivery_option === 'delivery'" class="p-4 bg-[#6366F1]/5 rounded-[8px] border border-[#6366F1]/20">
                        <div class="flex items-center space-x-2.5">
                            <Truck class="h-5 w-5 text-[#6366F1] shrink-0" />
                            <div>
                                <span class="text-xs font-bold text-[#6366F1] block">Pilihan Pengantaran</span>
                                <span class="text-sm font-semibold text-[#0A0A0A]">Antar Jemput (Gratis &lt; 3 km)</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- LIVE PRODUCTION 3-STATE TRACKER -->
                <div class="bg-white rounded-[12px] p-5 sm:p-6 border border-[#E8E8EC] space-y-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-2">
                            <Clock class="h-5 w-5 text-[#6366F1]" />
                            <h3 class="font-bold text-base text-[#0A0A0A]">Pelacakan Progres Cucian</h3>
                        </div>
                        <span class="text-xs font-semibold px-2.5 py-0.5 rounded-[4px] bg-[#6366F1]/10 text-[#6366F1] border border-[#6366F1]/20">
                            {{ order.production_status_label }}
                        </span>
                    </div>

                    <!-- Cancelled Warning if applicable -->
                    <div v-if="isCancelled" class="p-4 rounded-[8px] bg-[#EF4444]/10 border border-[#EF4444]/20 text-[#EF4444] flex items-center space-x-3">
                        <AlertCircle class="h-6 w-6 text-[#EF4444] shrink-0" />
                        <div>
                            <span class="font-bold text-sm block">Pesanan Dibatalkan</span>
                            <span class="text-xs text-[#EF4444]/80">Pesanan ini telah dibatalkan oleh pemilik/kasir.</span>
                        </div>
                    </div>

                    <!-- 3-Step Timeline (Mobile Responsive Stack) -->
                    <div v-else class="space-y-4 pt-1">
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5">
                            <div
                                v-for="(step, sIdx) in workflowSteps"
                                :key="step.key"
                                :class="[
                                    'p-3.5 rounded-[8px] border transition-all flex flex-row sm:flex-col items-center sm:text-center gap-3 sm:gap-2',
                                    sIdx < mappedStepIndex
                                        ? 'bg-[#10B981]/10 border-[#10B981]/30 text-[#10B981]'
                                        : (sIdx === mappedStepIndex
                                            ? 'bg-[#6366F1] border-[#6366F1] text-white shadow-[0_4px_12px_rgba(99,102,241,0.35)]'
                                            : 'bg-[#FAFAFA] border-[#E8E8EC] text-[#9C9C9C]'),
                                ]"
                            >
                                <div class="shrink-0">
                                    <CheckCircle2 v-if="sIdx < mappedStepIndex" class="h-6 w-6 text-[#10B981]" />
                                    <div v-else-if="sIdx === mappedStepIndex" class="h-6 w-6 rounded-full border-2 border-white flex items-center justify-center font-bold text-xs">
                                        {{ sIdx + 1 }}
                                    </div>
                                    <div v-else class="h-6 w-6 rounded-full border border-[#E8E8EC] bg-white flex items-center justify-center text-xs text-[#9C9C9C]">
                                        {{ sIdx + 1 }}
                                    </div>
                                </div>

                                <div class="min-w-0 flex-1 sm:flex-none">
                                    <span class="font-bold text-sm block leading-tight">{{ step.label }}</span>
                                    <span class="text-[11px] opacity-85 block mt-0.5">{{ step.desc }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Current Step Status Banner -->
                        <div class="p-3.5 rounded-[8px] bg-[#6366F1]/5 border border-[#6366F1]/20 text-[#0A0A0A] flex items-center justify-between text-xs">
                            <div class="flex items-center space-x-2">
                                <Sparkles class="h-4 w-4 text-[#6366F1] shrink-0" />
                                <span>
                                    Status: <strong>{{ workflowSteps[mappedStepIndex]?.label ?? order.production_status_label }}</strong>
                                    — {{ workflowSteps[mappedStepIndex]?.desc ?? 'Sedang diproses' }}
                                </span>
                            </div>
                            <span class="font-mono text-[11px] text-[#6366F1] hidden sm:inline font-semibold">
                                Tahap {{ mappedStepIndex + 1 }} dari 3
                            </span>
                        </div>
                    </div>
                </div>

                <!-- QRIS Payment Section for Unpaid Orders -->
                <div v-if="isUnpaid && order.outstanding_balance > 0" class="bg-white rounded-[12px] p-5 sm:p-6 border border-[#E8E8EC] text-center space-y-4">
                    <div class="flex items-center justify-center space-x-2 text-[#0A0A0A]">
                        <span class="font-bold text-base">Pembayaran Non-Tunai (QRIS)</span>
                    </div>
                    <div class="p-5 bg-[#FAFAFA] rounded-[8px] border border-[#E8E8EC] max-w-sm mx-auto">
                        <img 
                            src="/images/qris-andiklaundry.svg" 
                            alt="QRIS Payment Andik Laundry" 
                            class="qris-image mx-auto max-w-[200px] border border-[#E8E8EC] rounded-[6px] shadow-2xs bg-white p-2"
                        />
                        <p class="text-xs text-[#6B6B6B] mt-3">Scan QRIS melalui mobile banking atau aplikasi e-wallet Anda</p>
                        <p class="text-xl font-bold font-mono text-[#0A0A0A] mt-1.5">
                            {{ formatRupiah(order.outstanding_balance) }}
                        </p>
                    </div>
                </div>

                <!-- Itemized Breakdown Table -->
                <div class="bg-white rounded-[12px] p-5 sm:p-6 border border-[#E8E8EC] space-y-4">
                    <h3 class="font-bold text-base text-[#0A0A0A] flex items-center space-x-2">
                        <Shirt class="h-5 w-5 text-[#6366F1]" />
                        <span>Rincian Item & Biaya</span>
                    </h3>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs min-w-[480px] sm:min-w-0">
                            <thead>
                                <tr class="border-b border-[#E8E8EC] text-[#6B6B6B] font-semibold">
                                    <th class="pb-2">Layanan</th>
                                    <th class="pb-2 text-center">Jumlah / Berat</th>
                                    <th class="pb-2 text-right">Tarif Satuan</th>
                                    <th class="pb-2 text-right">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[#E8E8EC]">
                                <tr v-for="item in order.items" :key="item.id" class="hover:bg-[#FAFAFA]">
                                    <td class="py-3">
                                        <span class="font-bold text-[#0A0A0A] block">{{ item.item_name }}</span>
                                        <div class="flex flex-wrap items-center gap-1.5 mt-0.5 text-[11px] text-[#6B6B6B]">
                                            <span v-if="item.perfume_variant" class="inline-flex items-center text-[#6366F1] bg-[#6366F1]/10 px-1.5 py-0.5 rounded-[4px]">
                                                Wangi: {{ item.perfume_variant }}
                                            </span>
                                            <span v-if="item.item_condition_notes" class="inline-flex items-center text-[#F59E0B] bg-[#F59E0B]/10 border border-[#F59E0B]/20 px-1.5 py-0.5 rounded-[4px]">
                                                Catatan: {{ item.item_condition_notes }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="py-3 text-center font-mono">
                                        <span>{{ item.actual_quantity }} {{ item.pricing_unit }}</span>
                                        <span v-if="item.billed_quantity > item.actual_quantity" class="text-[10px] text-[#F59E0B] block font-semibold">
                                            (Cas min. {{ item.billed_quantity }} {{ item.pricing_unit }})
                                        </span>
                                    </td>
                                    <td class="py-3 text-right font-mono text-[#6B6B6B]">
                                        {{ formatRupiah(item.unit_price) }}
                                    </td>
                                    <td class="py-3 text-right font-mono font-bold text-[#0A0A0A]">
                                        {{ formatRupiah(item.subtotal) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Financial Ledger Summary -->
                    <div class="pt-3 border-t border-[#E8E8EC] space-y-1.5 max-w-xs ml-auto text-xs">
                        <div class="flex justify-between text-[#6B6B6B]">
                            <span>Subtotal:</span>
                            <span class="font-mono text-[#0A0A0A]">{{ formatRupiah(order.subtotal_amount) }}</span>
                        </div>
                        <div v-if="order.surcharge_amount > 0" class="flex justify-between text-[#6366F1]">
                            <span>Biaya Kecepatan:</span>
                            <span class="font-mono">+{{ formatRupiah(order.surcharge_amount) }}</span>
                        </div>
                        <div v-if="order.discount_amount > 0" class="flex justify-between text-[#F59E0B]">
                            <span>Diskon:</span>
                            <span class="font-mono">-{{ formatRupiah(order.discount_amount) }}</span>
                        </div>
                        <div class="flex justify-between font-bold text-sm text-[#0A0A0A] pt-1.5 border-t border-[#E8E8EC]">
                            <span>Total Tagihan:</span>
                            <span class="font-mono text-[#0A0A0A]">{{ formatRupiah(order.total_amount) }}</span>
                        </div>
                        <div class="flex justify-between text-[#6B6B6B]">
                            <span>Sudah Dibayar:</span>
                            <span class="font-mono">{{ formatRupiah(order.paid_amount) }}</span>
                        </div>
                        <div v-if="order.outstanding_balance > 0" class="flex justify-between font-bold text-[#EF4444] pt-1 border-t border-dotted border-[#E8E8EC]">
                            <span>Sisa Pembayaran:</span>
                            <span class="font-mono">{{ formatRupiah(order.outstanding_balance) }}</span>
                        </div>
                        <div v-else class="flex justify-between font-bold text-[#10B981] pt-1 border-t border-dotted border-[#E8E8EC]">
                            <span>Status:</span>
                            <span>LUNAS</span>
                        </div>
                    </div>
                </div>

                <!-- Contact/Request Delivery Button -->
                <div class="bg-white rounded-[12px] p-5 sm:p-6 border border-[#E8E8EC] text-center space-y-3 no-print">
                    <a
                        :href="whatsAppLink"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex items-center justify-center space-x-2 min-h-[48px] px-6 py-2.5 rounded-[6px] bg-[#25D366] hover:bg-[#128C7E] text-white font-bold text-sm shadow-[0_4px_12px_rgba(37,211,102,0.35)] transition-all"
                    >
                        <MessageCircle class="h-5 w-5" />
                        <span>Hubungi Admin / Minta Antar</span>
                    </a>
                    <p class="text-xs text-[#6B6B6B]">
                        Konfirmasi bukti bayar QRIS atau permintaan pengantaran langsung via WhatsApp
                    </p>
                </div>

                <!-- Store Information Footer & Terms -->
                <div class="bg-white rounded-[12px] p-5 sm:p-6 border border-[#E8E8EC] text-center space-y-3">
                    <div class="space-y-1">
                        <h4 class="font-bold text-sm text-[#0A0A0A]">{{ store_info.name }}</h4>
                        <p class="text-xs text-[#6B6B6B]">{{ store_info.address }}</p>
                        <p class="text-xs text-[#6366F1] font-mono">No. WA Toko: {{ store_info.phone }}</p>
                    </div>

                    <p class="text-xs text-[#6B6B6B] italic max-w-md mx-auto pt-2 border-t border-[#E8E8EC]">
                        {{ store_info.receipt_footer }}
                    </p>

                    <div class="pt-2 flex items-center justify-center space-x-3 text-xs no-print">
                        <a
                            :href="storeContactWaLink"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="text-[#25D366] font-bold hover:underline inline-flex items-center space-x-1"
                        >
                            <MessageCircle class="h-4 w-4" />
                            <span>Hubungi Kasir via WhatsApp</span>
                        </a>
                    </div>
                </div>
            </template>
        </main>
    </div>
</template>

<style>
@media print {
    header,
    .no-print {
        display: none !important;
    }
    body {
        background-color: white !important;
    }
}
</style>
