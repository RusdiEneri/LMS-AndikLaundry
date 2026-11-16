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
    if (!props.order) return { text: '-', bg: 'bg-white text-black border-2 border-black' };
    const debt = Number(props.order.outstanding_balance || 0);

    if (debt <= 0) {
        return { text: 'LUNAS', bg: 'bg-[#22C55E] text-black border-2 border-black shadow-[2px_2px_0px_#000]' };
    }
    return { text: 'BELUM LUNAS', bg: 'bg-[#FF5252] text-black border-2 border-black shadow-[2px_2px_0px_#000]' };
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

    <div class="min-h-screen bg-[#FFFDF5] text-black font-sans pb-12 antialiased">
        <!-- Top Navbar -->
        <header class="bg-[#FFEB3B] border-b-[3.5px] border-black sticky top-0 z-30 shadow-[0_4px_0px_#000] no-print">
            <div class="max-w-3xl mx-auto px-4 py-3 flex items-center justify-between">
                <div class="flex items-center space-x-2.5">
                    <div class="h-9 w-9 rounded-[8px] bg-black text-[#FFEB3B] border-2 border-black flex items-center justify-center font-black text-sm shadow-[2px_2px_0px_#000] shrink-0">
                        AL
                    </div>
                    <div>
                        <h1 class="font-black text-sm text-black leading-tight">{{ store_info.name }}</h1>
                        <p class="text-[11px] font-bold text-black/70">Nota Digital & Status Pelacakan Live</p>
                    </div>
                </div>

                <div v-if="order" class="flex items-center space-x-2">
                    <button
                        type="button"
                        @click="printReceipt"
                        class="min-h-[44px] px-3.5 py-1.5 rounded-[8px] border-[2.5px] border-black bg-white hover:bg-[#FFF9D2] text-black text-xs font-black shadow-[3px_3px_0px_#000] active:translate-x-0.5 active:translate-y-0.5 active:shadow-none flex items-center space-x-1.5 transition-all cursor-pointer"
                    >
                        <Printer class="h-4 w-4 text-black" />
                        <span class="hidden sm:inline">Cetak Nota</span>
                    </button>

                    <a
                        :href="storeContactWaLink"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="min-h-[44px] px-3.5 py-1.5 rounded-[8px] border-[2.5px] border-black bg-[#22C55E] hover:bg-[#16A34A] text-black text-xs font-black shadow-[3px_3px_0px_#000] active:translate-x-0.5 active:translate-y-0.5 active:shadow-none flex items-center space-x-1.5 transition-all cursor-pointer"
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
            <div v-if="!order" class="bg-white rounded-[12px] p-6 sm:p-8 border-[3.5px] border-black shadow-[6px_6px_0px_#000] text-center space-y-4">
                <div class="h-14 w-14 rounded-[8px] bg-[#FFF9D2] border-[2.5px] border-black flex items-center justify-center mx-auto text-black shadow-[3px_3px_0px_#000]">
                    <Search class="h-6 w-6 stroke-[2.5]" />
                </div>
                <div>
                    <h2 class="font-black text-xl text-black">Cari Nota Digital</h2>
                    <p class="text-sm font-bold text-black/70 mt-1">Masukkan nomor nota atau nomor WhatsApp pelanggan</p>
                </div>
                <div class="flex flex-col sm:flex-row gap-2 max-w-md mx-auto">
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Contoh: AL-20261115-001"
                        class="flex-1 min-h-[44px] rounded-[8px] border-[2.5px] border-black bg-white px-3.5 py-2 text-sm font-bold text-black placeholder:text-black/40 shadow-[3px_3px_0px_#000] focus:outline-none focus:shadow-[4px_4px_0px_#000]"
                        @keyup.enter="searchOrder"
                    />
                    <button
                        type="button"
                        @click="searchOrder"
                        class="min-h-[44px] px-5 rounded-[8px] border-[2.5px] border-black bg-[#FFEB3B] hover:bg-[#FDE047] text-black font-black text-sm shadow-[3px_3px_0px_#000] active:translate-x-0.5 active:translate-y-0.5 active:shadow-none transition-all cursor-pointer"
                    >
                        Cari
                    </button>
                </div>
                <p class="text-xs font-bold text-black/60">
                    Atau buka langsung tautan unik yang dikirimkan ke pesan WhatsApp Anda.
                </p>
            </div>

            <!-- Order Found -->
            <template v-else>
                <!-- Order Header Card -->
                <div class="bg-white rounded-[12px] p-5 sm:p-6 border-[3.5px] border-black shadow-[6px_6px_0px_#000] space-y-4">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 border-b-2 border-black/20">
                        <div>
                            <div class="flex items-center space-x-2 flex-wrap gap-2">
                                <span class="text-xs font-black uppercase tracking-wider bg-[#FFEB3B] text-black border-2 border-black px-2.5 py-0.5 rounded-[6px] shadow-[2px_2px_0px_#000]">
                                    Nota Resmi
                                </span>
                                <span :class="['px-2.5 py-0.5 rounded-[6px] text-xs font-black uppercase tracking-wider', paymentBadge.bg]">
                                    {{ paymentBadge.text }}
                                </span>
                            </div>
                            <h2 class="font-mono font-black text-2xl sm:text-3xl text-black mt-2 tracking-tight">
                                {{ order.invoice_number }}
                            </h2>
                        </div>

                        <div class="text-left sm:text-right">
                            <span class="text-xs font-black uppercase tracking-wider text-black/60 block">Total Tagihan</span>
                            <span class="font-mono font-black text-2xl sm:text-3xl text-black">
                                {{ formatRupiah(order.total_amount) }}
                            </span>
                        </div>
                    </div>

                    <!-- Customer & Metadata Grid -->
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
                        <div>
                            <span class="text-black/60 block text-[11px] font-black uppercase tracking-wider">Nama Pelanggan</span>
                            <span class="font-black text-black text-sm">{{ order.customer.name }}</span>
                        </div>
                        <div>
                            <span class="text-black/60 block text-[11px] font-black uppercase tracking-wider">Waktu Masuk</span>
                            <span class="font-bold text-black">{{ formatDateTimeIndo(order.intake_at) }}</span>
                        </div>
                        <div>
                            <span class="text-black/60 block text-[11px] font-black uppercase tracking-wider">Estimasi Selesai</span>
                            <span class="font-black text-[#2196F3]">{{ formatDateTimeIndo(order.target_completion_at) }}</span>
                        </div>
                        <div>
                            <span class="text-black/60 block text-[11px] font-black uppercase tracking-wider">Rak Pengambilan</span>
                            <span class="font-black font-mono text-black">{{ order.staging_rack || 'Menunggu Rak' }}</span>
                        </div>
                    </div>

                    <!-- Rack Location Display when status is SELESAI -->
                    <div v-if="order.production_status.toLowerCase() === 'selesai' || order.production_status.toLowerCase() === 'ready_for_pickup'" class="p-4 bg-[#FFF9D2] rounded-[8px] border-[2.5px] border-black shadow-[3px_3px_0px_#000]">
                        <div class="flex items-center space-x-2.5">
                            <MapPin class="h-6 w-6 text-black shrink-0" />
                            <div>
                                <span class="text-xs font-black uppercase tracking-wider text-black/70 block">Lokasi Rak Pengambilan:</span>
                                <span class="font-mono font-black text-xl text-black">{{ order.staging_rack || 'Segera Ditentukan' }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Delivery Option Display -->
                    <div v-if="order.delivery_option === 'delivery'" class="p-4 bg-[#2196F3]/15 rounded-[8px] border-[2.5px] border-black shadow-[3px_3px_0px_#000]">
                        <div class="flex items-center space-x-2.5">
                            <Truck class="h-6 w-6 text-black shrink-0" />
                            <div>
                                <span class="text-xs font-black uppercase tracking-wider text-black block">Pilihan Pengantaran</span>
                                <span class="text-sm font-black text-black">Antar Jemput (Gratis &lt; 3 km)</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- LIVE PRODUCTION 3-STATE TRACKER -->
                <div class="bg-white rounded-[12px] p-5 sm:p-6 border-[3.5px] border-black shadow-[6px_6px_0px_#000] space-y-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-2">
                            <Clock class="h-5 w-5 text-black" />
                            <h3 class="font-black text-base text-black">Pelacakan Progres Cucian</h3>
                        </div>
                        <span class="text-xs font-black px-2.5 py-0.5 rounded-[6px] bg-[#FFEB3B] text-black border-2 border-black shadow-[2px_2px_0px_#000]">
                            {{ order.production_status_label }}
                        </span>
                    </div>

                    <!-- Cancelled Warning if applicable -->
                    <div v-if="isCancelled" class="p-4 rounded-[8px] bg-[#FF5252]/20 border-[2.5px] border-black text-black flex items-center space-x-3 shadow-[3px_3px_0px_#000]">
                        <AlertCircle class="h-6 w-6 text-[#FF5252] shrink-0" />
                        <div>
                            <span class="font-black text-sm block">Pesanan Dibatalkan</span>
                            <span class="text-xs font-bold text-black/70">Pesanan ini telah dibatalkan oleh pemilik/kasir.</span>
                        </div>
                    </div>

                    <!-- 3-Step Timeline (Mobile Responsive Stack) -->
                    <div v-else class="space-y-4 pt-1">
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div
                                v-for="(step, sIdx) in workflowSteps"
                                :key="step.key"
                                :class="[
                                    'p-3.5 rounded-[8px] transition-all flex flex-row sm:flex-col items-center sm:text-center gap-3 sm:gap-2',
                                    sIdx < mappedStepIndex
                                        ? 'bg-[#22C55E]/20 border-[2.5px] border-black text-black shadow-[3px_3px_0px_#000]'
                                        : (sIdx === mappedStepIndex
                                            ? 'bg-[#FFEB3B] border-[3px] border-black text-black shadow-[4px_4px_0px_#000]'
                                            : 'bg-white border-2 border-black/30 text-black/40'),
                                ]"
                            >
                                <div class="shrink-0">
                                    <CheckCircle2 v-if="sIdx < mappedStepIndex" class="h-6 w-6 text-black" />
                                    <div v-else-if="sIdx === mappedStepIndex" class="h-7 w-7 rounded-full border-[2.5px] border-black bg-black text-[#FFEB3B] flex items-center justify-center font-black text-xs shadow-[1px_1px_0px_#000]">
                                        {{ sIdx + 1 }}
                                    </div>
                                    <div v-else class="h-6 w-6 rounded-full border-2 border-black/30 bg-white flex items-center justify-center text-xs font-bold text-black/40">
                                        {{ sIdx + 1 }}
                                    </div>
                                </div>

                                <div class="min-w-0 flex-1 sm:flex-none">
                                    <span class="font-black text-sm block leading-tight">{{ step.label }}</span>
                                    <span class="text-[11px] font-bold opacity-80 block mt-0.5">{{ step.desc }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Current Step Status Banner -->
                        <div class="p-3.5 rounded-[8px] bg-[#FFF9D2] border-[2.5px] border-black text-black flex items-center justify-between text-xs shadow-[3px_3px_0px_#000]">
                            <div class="flex items-center space-x-2">
                                <Sparkles class="h-4 w-4 text-black shrink-0" />
                                <span class="font-bold">
                                    Status: <strong>{{ workflowSteps[mappedStepIndex]?.label ?? order.production_status_label }}</strong>
                                    — {{ workflowSteps[mappedStepIndex]?.desc ?? 'Sedang diproses' }}
                                </span>
                            </div>
                            <span class="font-mono text-xs text-black hidden sm:inline font-black uppercase">
                                Tahap {{ mappedStepIndex + 1 }} dari 3
                            </span>
                        </div>
                    </div>
                </div>

                <!-- QRIS Payment Section for Unpaid Orders -->
                <div v-if="isUnpaid && order.outstanding_balance > 0" class="bg-white rounded-[12px] p-5 sm:p-6 border-[3.5px] border-black shadow-[6px_6px_0px_#000] text-center space-y-4">
                    <div class="flex items-center justify-center space-x-2 text-black">
                        <span class="font-black text-base uppercase tracking-wider">Pembayaran Non-Tunai (QRIS)</span>
                    </div>
                    <div class="p-5 bg-[#FFF9D2] rounded-[10px] border-[2.5px] border-black shadow-[4px_4px_0px_#000] max-w-sm mx-auto">
                        <img 
                            src="/images/qris-andiklaundry.svg" 
                            alt="QRIS Payment Andik Laundry" 
                            class="qris-image mx-auto max-w-[200px] border-[2.5px] border-black rounded-[8px] shadow-[3px_3px_0px_#000] bg-white p-2"
                        />
                        <p class="text-xs font-bold text-black/70 mt-3">Scan QRIS melalui mobile banking atau aplikasi e-wallet Anda</p>
                        <p class="text-2xl font-black font-mono text-black mt-2">
                            {{ formatRupiah(order.outstanding_balance) }}
                        </p>
                    </div>
                </div>

                <!-- Itemized Breakdown Table -->
                <div class="bg-white rounded-[12px] p-5 sm:p-6 border-[3.5px] border-black shadow-[6px_6px_0px_#000] space-y-4">
                    <h3 class="font-black text-base text-black flex items-center space-x-2">
                        <Shirt class="h-5 w-5 text-black" />
                        <span>Rincian Item & Biaya</span>
                    </h3>

                    <div class="overflow-x-auto border-2 border-black rounded-[8px]">
                        <table class="w-full text-left text-xs min-w-[480px] sm:min-w-0">
                            <thead>
                                <tr class="bg-[#FFEB3B] border-b-2 border-black text-black font-black uppercase tracking-wider">
                                    <th class="py-2.5 px-3">Layanan</th>
                                    <th class="py-2.5 px-3 text-center">Jumlah / Berat</th>
                                    <th class="py-2.5 px-3 text-right">Tarif Satuan</th>
                                    <th class="py-2.5 px-3 text-right">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y-2 divide-black/10">
                                <tr v-for="item in order.items" :key="item.id" class="hover:bg-[#FFF9D2]/30">
                                    <td class="py-3 px-3">
                                        <span class="font-black text-black block">{{ item.item_name }}</span>
                                        <div class="flex flex-wrap items-center gap-1.5 mt-1 text-[11px]">
                                            <span v-if="item.perfume_variant" class="inline-flex items-center text-black bg-[#FFEB3B] border border-black px-1.5 py-0.5 rounded-[4px] font-bold shadow-[1px_1px_0px_#000]">
                                                Wangi: {{ item.perfume_variant }}
                                            </span>
                                            <span v-if="item.item_condition_notes" class="inline-flex items-center text-black bg-[#FF5252]/20 border border-black px-1.5 py-0.5 rounded-[4px] font-bold">
                                                Catatan: {{ item.item_condition_notes }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="py-3 px-3 text-center font-mono font-bold text-black">
                                        <span>{{ item.actual_quantity }} {{ item.pricing_unit }}</span>
                                        <span v-if="item.billed_quantity > item.actual_quantity" class="text-[10px] text-[#FF5252] block font-black">
                                            (Cas min. {{ item.billed_quantity }} {{ item.pricing_unit }})
                                        </span>
                                    </td>
                                    <td class="py-3 px-3 text-right font-mono font-bold text-black/70">
                                        {{ formatRupiah(item.unit_price) }}
                                    </td>
                                    <td class="py-3 px-3 text-right font-mono font-black text-black">
                                        {{ formatRupiah(item.subtotal) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Financial Ledger Summary -->
                    <div class="pt-3 border-t-2 border-black space-y-1.5 max-w-xs ml-auto text-xs">
                        <div class="flex justify-between font-bold text-black/70">
                            <span>Subtotal:</span>
                            <span class="font-mono text-black font-black">{{ formatRupiah(order.subtotal_amount) }}</span>
                        </div>
                        <div v-if="order.surcharge_amount > 0" class="flex justify-between font-bold text-[#2196F3]">
                            <span>Biaya Kecepatan:</span>
                            <span class="font-mono font-black">+{{ formatRupiah(order.surcharge_amount) }}</span>
                        </div>
                        <div v-if="order.discount_amount > 0" class="flex justify-between font-bold text-[#FF5252]">
                            <span>Diskon:</span>
                            <span class="font-mono font-black">-{{ formatRupiah(order.discount_amount) }}</span>
                        </div>
                        <div class="flex justify-between font-black text-sm text-black pt-1.5 border-t-2 border-black">
                            <span>Total Tagihan:</span>
                            <span class="font-mono text-black">{{ formatRupiah(order.total_amount) }}</span>
                        </div>
                        <div class="flex justify-between font-bold text-black/70">
                            <span>Sudah Dibayar:</span>
                            <span class="font-mono font-black">{{ formatRupiah(order.paid_amount) }}</span>
                        </div>
                        <div v-if="order.outstanding_balance > 0" class="flex justify-between font-black text-[#FF5252] pt-1.5 border-t-2 border-black">
                            <span>Sisa Pembayaran:</span>
                            <span class="font-mono">{{ formatRupiah(order.outstanding_balance) }}</span>
                        </div>
                        <div v-else class="flex justify-between font-black text-[#22C55E] pt-1.5 border-t-2 border-black">
                            <span>Status:</span>
                            <span class="bg-[#22C55E] text-black border-2 border-black px-2 py-0.5 rounded-[4px] shadow-[1px_1px_0px_#000]">LUNAS</span>
                        </div>
                    </div>
                </div>

                <!-- Contact/Request Delivery Button -->
                <div class="bg-white rounded-[12px] p-5 sm:p-6 border-[3.5px] border-black shadow-[6px_6px_0px_#000] text-center space-y-3 no-print">
                    <a
                        :href="whatsAppLink"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex items-center justify-center space-x-2 min-h-[48px] px-6 py-2.5 rounded-[8px] border-[2.5px] border-black bg-[#22C55E] hover:bg-[#16A34A] text-black font-black text-sm shadow-[4px_4px_0px_#000] active:translate-x-0.5 active:translate-y-0.5 active:shadow-none transition-all cursor-pointer uppercase tracking-wider"
                    >
                        <MessageCircle class="h-5 w-5" />
                        <span>Hubungi Admin / Minta Antar</span>
                    </a>
                    <p class="text-xs font-bold text-black/70">
                        Konfirmasi bukti bayar QRIS atau permintaan pengantaran langsung via WhatsApp
                    </p>
                </div>

                <!-- Store Information Footer & Terms -->
                <div class="bg-white rounded-[12px] p-5 sm:p-6 border-[3.5px] border-black shadow-[6px_6px_0px_#000] text-center space-y-3">
                    <div class="space-y-1">
                        <h4 class="font-black text-sm text-black">{{ store_info.name }}</h4>
                        <p class="text-xs font-bold text-black/70">{{ store_info.address }}</p>
                        <p class="text-xs text-black font-mono font-bold">No. WA Toko: {{ store_info.phone }}</p>
                    </div>

                    <p class="text-xs font-bold text-black/70 italic max-w-md mx-auto pt-2 border-t-2 border-black/20">
                        {{ store_info.receipt_footer }}
                    </p>

                    <div class="pt-2 flex items-center justify-center space-x-3 text-xs no-print">
                        <a
                            :href="storeContactWaLink"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="text-black font-black hover:underline inline-flex items-center space-x-1"
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
