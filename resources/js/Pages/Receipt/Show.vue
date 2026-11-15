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
    if (!props.order) return { text: '-', bg: 'bg-slate-100 text-slate-800 border-slate-200' };
    const debt = Number(props.order.outstanding_balance || 0);

    if (debt <= 0) {
        return { text: 'LUNAS', bg: 'bg-emerald-100 text-emerald-800 border-emerald-200' };
    }
    return { text: 'BELUM LUNAS', bg: 'bg-rose-100 text-rose-800 border-rose-200' };
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

    <div class="min-h-screen bg-slate-100 text-slate-800 font-sans pb-12 antialiased">
        <!-- Top Navbar -->
        <header class="bg-white border-b border-slate-200 sticky top-0 z-30 shadow-2xs no-print">
            <div class="max-w-3xl mx-auto px-4 py-3.5 flex items-center justify-between">
                <div class="flex items-center space-x-2.5">
                    <div class="h-9 w-9 rounded-xl bg-teal-600 text-white flex items-center justify-center font-bold text-sm shadow-xs">
                        AL
                    </div>
                    <div>
                        <h1 class="font-bold text-sm text-slate-900 leading-tight">{{ store_info.name }}</h1>
                        <p class="text-[11px] text-slate-500">Nota Digital & Status Pelacakan Live</p>
                    </div>
                </div>

                <div v-if="order" class="flex items-center space-x-2">
                    <button
                        type="button"
                        @click="printReceipt"
                        class="min-h-[40px] px-3.5 py-1.5 rounded-lg border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 text-xs font-bold shadow-2xs flex items-center space-x-1.5 transition-all"
                    >
                        <Printer class="h-4 w-4" />
                        <span class="hidden sm:inline">Cetak Nota</span>
                    </button>

                    <a
                        :href="storeContactWaLink"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="min-h-[40px] px-3.5 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-xs flex items-center space-x-1.5 transition-all"
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
            <div v-if="!order" class="bg-white rounded-2xl p-8 border border-slate-200 shadow-xs text-center space-y-4">
                <div class="h-16 w-16 rounded-full bg-slate-100 flex items-center justify-center mx-auto">
                    <Search class="h-8 w-8 text-slate-400" />
                </div>
                <div>
                    <h2 class="font-bold text-lg text-slate-900">Cari Nota</h2>
                    <p class="text-sm text-slate-500 mt-1">Masukkan nomor nota atau nomor HP pelanggan</p>
                </div>
                <div class="flex gap-2 max-w-md mx-auto">
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Contoh: INV-2024-001234"
                        class="flex-1 min-h-[44px] rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-teal-500"
                        @keyup.enter="searchOrder"
                    />
                    <button
                        type="button"
                        @click="searchOrder"
                        class="min-h-[44px] px-4 rounded-lg bg-teal-600 hover:bg-teal-700 text-white font-bold text-sm transition-colors"
                    >
                        Cari
                    </button>
                </div>
                <p class="text-xs text-slate-400">
                    Atau buka langsung dari link di struk kasir Anda
                </p>
            </div>

            <!-- Order Found -->
            <template v-else>
                <!-- Order Header Card -->
                <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-xs space-y-4">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 border-b border-slate-100">
                        <div>
                            <div class="flex items-center space-x-2 flex-wrap gap-2">
                                <span class="text-xs font-bold uppercase tracking-wider text-teal-700">Nota Resmi</span>
                                <span :class="['px-2.5 py-0.5 rounded-full text-[11px] font-bold border', paymentBadge.bg]">
                                    {{ paymentBadge.text }}
                                </span>
                            </div>
                            <h2 class="font-mono font-bold text-2xl text-slate-900 mt-1 tracking-tight">
                                {{ order.invoice_number }}
                            </h2>
                        </div>

                        <div class="text-left sm:text-right">
                            <span class="text-xs text-slate-500 block">Total Tagihan</span>
                            <span class="font-mono font-bold text-2xl text-teal-800">
                                {{ formatRupiah(order.total_amount) }}
                            </span>
                        </div>
                    </div>

                    <!-- Customer & Metadata Grid -->
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
                        <div>
                            <span class="text-slate-400 block text-[11px]">Nama Pelanggan</span>
                            <span class="font-bold text-slate-900">{{ order.customer.name }}</span>
                        </div>
                        <div>
                            <span class="text-slate-400 block text-[11px]">Waktu Terima</span>
                            <span class="font-medium text-slate-700">{{ formatDateTimeIndo(order.intake_at) }}</span>
                        </div>
                        <div>
                            <span class="text-slate-400 block text-[11px]">Estimasi Selesai</span>
                            <span class="font-medium text-teal-700">{{ formatDateTimeIndo(order.target_completion_at) }}</span>
                        </div>
                        <div>
                            <span class="text-slate-400 block text-[11px]">Rak Pengambilan</span>
                            <span class="font-bold font-mono text-slate-800">{{ order.staging_rack || 'Menunggu Rak' }}</span>
                        </div>
                    </div>

                    <!-- Rack Location Display when status is SELESAI -->
                    <div v-if="order.production_status.toLowerCase() === 'selesai' || order.production_status.toLowerCase() === 'ready_for_pickup'" class="p-4 bg-teal-50 rounded-xl border border-teal-200">
                        <div class="flex items-center space-x-2">
                            <MapPin class="h-5 w-5 text-teal-600" />
                            <div>
                                <span class="text-xs font-bold text-teal-800 block">Lokasi Rak:</span>
                                <span class="font-mono font-bold text-xl text-teal-900">{{ order.staging_rack || 'Segera Ditentukan' }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Delivery Option Display -->
                    <div v-if="order.delivery_option === 'delivery'" class="p-4 bg-blue-50 rounded-xl border border-blue-200">
                        <div class="flex items-center space-x-2">
                            <Truck class="h-5 w-5 text-blue-600" />
                            <div>
                                <span class="text-xs font-bold text-blue-800 block">Pilihan Pengantaran</span>
                                <span class="text-sm text-blue-900">Antar Jemput (Gratis &lt; 3 km)</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- LIVE PRODUCTION 3-STATE TRACKER -->
                <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-xs space-y-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-2">
                            <Clock class="h-5 w-5 text-teal-600" />
                            <h3 class="font-bold text-base text-slate-900">Pelacakan Proses Produksi</h3>
                        </div>
                        <span class="text-xs font-bold px-2.5 py-1 rounded-full bg-teal-50 text-teal-800 border border-teal-200">
                            {{ order.production_status_label }}
                        </span>
                    </div>

                    <!-- Cancelled Warning if applicable -->
                    <div v-if="isCancelled" class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 flex items-center space-x-3">
                        <AlertCircle class="h-6 w-6 text-rose-600 shrink-0" />
                        <div>
                            <span class="font-bold text-sm block">Pesanan Dibatalkan</span>
                            <span class="text-xs text-rose-700">Pesanan ini telah dibatalkan oleh kasir/admin.</span>
                        </div>
                    </div>

                    <!-- 3-Step Timeline -->
                    <div v-else class="space-y-4 pt-2">
                        <div class="grid grid-cols-3 gap-3">
                            <div
                                v-for="(step, sIdx) in workflowSteps"
                                :key="step.key"
                                :class="[
                                    'p-4 rounded-xl border text-center transition-all flex flex-col items-center justify-between',
                                    sIdx < mappedStepIndex
                                        ? 'bg-emerald-50/60 border-emerald-300 text-emerald-900'
                                        : (sIdx === mappedStepIndex
                                            ? 'bg-teal-600 border-teal-600 text-white shadow-md scale-102 ring-2 ring-teal-400 ring-offset-1'
                                            : 'bg-slate-50 border-slate-200 text-slate-400'),
                                ]"
                            >
                                <div class="mb-2">
                                    <CheckCircle2 v-if="sIdx < mappedStepIndex" class="h-6 w-6 text-emerald-600 mx-auto" />
                                    <div v-else-if="sIdx === mappedStepIndex" class="h-6 w-6 rounded-full border-2 border-white flex items-center justify-center mx-auto font-bold text-xs">
                                        {{ sIdx + 1 }}
                                    </div>
                                    <div v-else class="h-6 w-6 rounded-full border border-slate-300 flex items-center justify-center mx-auto text-xs text-slate-400">
                                        {{ sIdx + 1 }}
                                    </div>
                                </div>

                                <div>
                                    <span class="font-bold text-sm block leading-tight">{{ step.label }}</span>
                                    <span class="text-[10px] opacity-80 block mt-1">{{ step.desc }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Current Step Status Banner -->
                        <div class="p-3.5 rounded-xl bg-teal-50 border border-teal-200 text-teal-950 flex items-center justify-between text-xs">
                            <div class="flex items-center space-x-2">
                                <Sparkles class="h-4 w-4 text-teal-600 shrink-0" />
                                <span>
                                    Status saat ini: <strong>{{ workflowSteps[mappedStepIndex]?.label ?? order.production_status_label }}</strong>
                                    ({{ workflowSteps[mappedStepIndex]?.desc ?? 'Sedang diproses oleh staf operasional' }})
                                </span>
                            </div>
                            <span class="font-mono text-[11px] text-teal-700 hidden sm:inline">
                                Step {{ mappedStepIndex + 1 }} dari 3
                            </span>
                        </div>
                    </div>
                </div>

                <!-- QRIS Payment Section for Unpaid Orders -->
                <div v-if="isUnpaid && order.outstanding_balance > 0" class="bg-white rounded-2xl p-6 border border-slate-200 shadow-xs text-center space-y-4">
                    <div class="flex items-center justify-center space-x-2 text-slate-700">
                        <span class="font-bold text-lg">Pembayaran QRIS</span>
                    </div>
                    <div class="p-4 bg-slate-50 rounded-xl border border-slate-200">
                        <img 
                            src="/images/qris-andiklaundry.svg" 
                            alt="QRIS Payment Andik Laundry" 
                            class="qris-image mx-auto max-w-[220px] border border-slate-200 rounded-lg shadow-2xs"
                        />
                        <p class="text-xs text-slate-500 mt-3">Scan QRIS melalui aplikasi bank atau dompet digital Anda</p>
                        <p class="text-xl font-bold font-mono text-teal-800 mt-1.5">
                            {{ formatRupiah(order.outstanding_balance) }}
                        </p>
                    </div>
                </div>

                <!-- Itemized Breakdown Table -->
                <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-xs space-y-4">
                    <h3 class="font-bold text-base text-slate-900 flex items-center space-x-2">
                        <Shirt class="h-5 w-5 text-teal-600" />
                        <span>Rincian Layanan Cucian</span>
                    </h3>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead>
                                <tr class="border-b border-slate-200 text-slate-400 font-semibold">
                                    <th class="pb-2">Layanan</th>
                                    <th class="pb-2 text-center">Jumlah / Berat</th>
                                    <th class="pb-2 text-right">Tarif Satuan</th>
                                    <th class="pb-2 text-right">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="item in order.items" :key="item.id" class="hover:bg-slate-50/50">
                                    <td class="py-3">
                                        <span class="font-bold text-slate-900 block">{{ item.item_name }}</span>
                                        <div class="flex flex-wrap items-center gap-1.5 mt-0.5 text-[11px] text-slate-500">
                                            <span v-if="item.perfume_variant" class="inline-flex items-center space-x-1 text-teal-700 bg-teal-50 px-1.5 py-0.5 rounded-md">
                                                <span>Wangi: {{ item.perfume_variant }}</span>
                                            </span>
                                            <span v-if="item.item_condition_notes" class="inline-flex items-center space-x-1 text-amber-800 bg-amber-50 border border-amber-200 px-1.5 py-0.5 rounded-md">
                                                <span>Catatan: {{ item.item_condition_notes }}</span>
                                            </span>
                                        </div>
                                    </td>
                                    <td class="py-3 text-center font-mono">
                                        <span>{{ item.actual_quantity }} {{ item.pricing_unit }}</span>
                                        <span v-if="item.billed_quantity > item.actual_quantity" class="text-[10px] text-amber-700 block font-semibold">
                                            (Cas min. {{ item.billed_quantity }} {{ item.pricing_unit }})
                                        </span>
                                    </td>
                                    <td class="py-3 text-right font-mono text-slate-700">
                                        {{ formatRupiah(item.unit_price) }}
                                    </td>
                                    <td class="py-3 text-right font-mono font-bold text-slate-900">
                                        {{ formatRupiah(item.subtotal) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Financial Ledger Summary -->
                    <div class="pt-3 border-t border-slate-200 space-y-1.5 max-w-xs ml-auto text-xs">
                        <div class="flex justify-between text-slate-600">
                            <span>Subtotal:</span>
                            <span class="font-mono">{{ formatRupiah(order.subtotal_amount) }}</span>
                        </div>
                        <div v-if="order.surcharge_amount > 0" class="flex justify-between text-teal-700">
                            <span>Biaya Kecepatan:</span>
                            <span class="font-mono">+{{ formatRupiah(order.surcharge_amount) }}</span>
                        </div>
                        <div v-if="order.discount_amount > 0" class="flex justify-between text-amber-700">
                            <span>Diskon:</span>
                            <span class="font-mono">-{{ formatRupiah(order.discount_amount) }}</span>
                        </div>
                        <div class="flex justify-between font-bold text-sm text-slate-900 pt-1 border-t border-slate-200">
                            <span>Grand Total:</span>
                            <span class="font-mono text-teal-800">{{ formatRupiah(order.total_amount) }}</span>
                        </div>
                        <div class="flex justify-between text-slate-600">
                            <span>Sudah Dibayar:</span>
                            <span class="font-mono">{{ formatRupiah(order.paid_amount) }}</span>
                        </div>
                        <div v-if="order.outstanding_balance > 0" class="flex justify-between font-bold text-rose-700 pt-1 border-t border-dotted border-slate-200">
                            <span>Sisa Pembayaran:</span>
                            <span class="font-mono">{{ formatRupiah(order.outstanding_balance) }}</span>
                        </div>
                        <div v-else class="flex justify-between font-bold text-emerald-700 pt-1 border-t border-dotted border-slate-200">
                            <span>Status:</span>
                            <span>LUNAS</span>
                        </div>
                    </div>
                </div>

                <!-- Contact/Request Delivery Button -->
                <div class="bg-white rounded-2xl p-6 border border-slate-200 text-center space-y-3 no-print">
                    <a
                        :href="whatsAppLink"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex items-center justify-center space-x-2 min-h-[48px] px-6 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-sm shadow-md transition-all"
                    >
                        <MessageCircle class="h-5 w-5" />
                        <span>Hubungi Admin / Minta Antar</span>
                    </a>
                    <p class="text-xs text-slate-500">
                        Ada pertanyaan tentang pesanan? Hubungi kami via WhatsApp
                    </p>
                </div>

                <!-- Store Information Footer & Terms -->
                <div class="bg-white rounded-2xl p-6 border border-slate-200 text-center space-y-3">
                    <div class="space-y-1">
                        <h4 class="font-bold text-sm text-slate-900">{{ store_info.name }}</h4>
                        <p class="text-xs text-slate-500">{{ store_info.address }}</p>
                        <p class="text-xs text-teal-700 font-mono">WhatsApp: {{ store_info.phone }}</p>
                    </div>

                    <p class="text-xs text-slate-400 italic max-w-md mx-auto pt-2 border-t border-slate-100">
                        {{ store_info.receipt_footer }}
                    </p>

                    <div class="pt-2 flex items-center justify-center space-x-3 text-xs no-print">
                        <a
                            :href="storeContactWaLink"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="text-emerald-700 font-bold hover:underline inline-flex items-center space-x-1"
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
