<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import {
    CheckCircle2,
    Clock,
    Scale,
    Printer,
    Phone,
    MapPin,
    AlertCircle,
    ChevronRight,
    Sparkles,
    Shirt,
    ShieldCheck,
    Search,
    Truck,
    Banknote,
    QrCode,
    Coins,
    Loader2,
    X,
} from 'lucide-vue-next';
import { formatRupiah, formatDateTimeIndo } from '@/Support/whatsapp';
import WhatsAppIcon from '@/Components/WhatsAppIcon.vue';

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
    if (!props.order) return { text: '-', bg: 'bg-surface text-text border border-border' };
    const debt = Number(props.order.outstanding_balance || 0);

    if (debt <= 0) {
        return { text: 'LUNAS', bg: 'bg-success/10 text-success border border-success/20' };
    }
    return { text: 'BELUM LUNAS (BAYAR PAS AMBIL)', bg: 'bg-warning/15 text-amber-800 border border-warning/20' };
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

// Staff / Cashier Settlement State
const page = usePage();
const authUser = computed(() => (page.props as any).auth?.user);

const isSettling = ref(false);
const settleErrorMessage = ref('');
const showSettleModal = ref(false);
const settleMethod = ref<'cash' | 'qris'>('cash');
const settleTender = ref<string>('');

function openSettleModal(method: 'cash' | 'qris') {
    settleMethod.value = method;
    settleTender.value = String(props.order?.outstanding_balance || 0);
    settleErrorMessage.value = '';
    showSettleModal.value = true;
}

async function confirmSettlement() {
    if (!props.order) return;
    isSettling.value = true;
    settleErrorMessage.value = '';

    try {
        const csrfToken = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content;
        const tenderVal = settleMethod.value === 'cash'
            ? (Number(settleTender.value.replace(/[^0-9]/g, '')) || props.order.outstanding_balance)
            : props.order.outstanding_balance;

        const res = await fetch(`/pos/orders/${props.order.id}/payments`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken || '',
            },
            body: JSON.stringify({
                amount: props.order.outstanding_balance,
                tender_amount: tenderVal,
                payment_method: settleMethod.value,
                notes: 'Pelunasan saat pengambilan pakaian',
            }),
        });

        if (res.ok) {
            showSettleModal.value = false;
            router.reload();
        } else {
            const data = await res.json();
            settleErrorMessage.value = data.message || 'Gagal memproses pelunasan kasir.';
        }
    } catch {
        settleErrorMessage.value = 'Terjadi kesalahan sistem saat menghubungi server.';
    } finally {
        isSettling.value = false;
    }
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

    <div class="min-h-screen bg-bg text-text font-sans pb-12 antialiased">
        <!-- Top Navbar -->
        <header class="bg-navy-950 text-white border-b border-navy-900 sticky top-0 z-30 shadow-md no-print">
            <div class="max-w-3xl mx-auto px-4 py-3.5 flex items-center justify-between">
                <div class="flex items-center space-x-2.5">
                    <div class="h-9 w-9 rounded-md bg-surface/10 text-white flex items-center justify-center font-bold text-sm shrink-0 border border-white/10">
                        AL
                    </div>
                    <div>
                        <h1 class="font-bold text-sm text-white leading-tight">{{ store_info.name }}</h1>
                        <p class="text-[11px] font-medium text-text-muted">Nota Digital & Status Pelacakan Live</p>
                    </div>
                </div>

                <div v-if="order" class="flex items-center space-x-2">
                    <button
                        type="button"
                        @click="printReceipt"
                        class="min-h-[42px] px-3.5 py-1.5 rounded-md border border-white/20 bg-surface/10 hover:bg-surface/20 text-white text-xs font-bold shadow-xs flex items-center space-x-1.5 transition-all cursor-pointer"
                    >
                        <Printer class="h-4 w-4 text-white" />
                        <span class="hidden sm:inline">Cetak Nota</span>
                    </button>

                    <a
                        :href="storeContactWaLink"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="min-h-[42px] px-3.5 py-1.5 rounded-md bg-success hover:bg-success/90 text-white text-xs font-bold shadow-xs flex items-center space-x-1.5 transition-all cursor-pointer"
                    >
                        <WhatsAppIcon class="h-4 w-4" />
                        <span>Chat Kasir</span>
                    </a>
                </div>
            </div>
        </header>

        <!-- Main Content Container -->
        <main class="max-w-3xl mx-auto px-4 pt-6 space-y-6">
            <!-- No Order Found: Search Section -->
            <div v-if="!order" class="bg-surface rounded-lg p-6 sm:p-8 border border-border shadow-sm text-center space-y-4">
                <div class="h-14 w-14 rounded-lg bg-primary-100 text-primary-600 flex items-center justify-center mx-auto shadow-xs">
                    <Search class="h-6 w-6 stroke-[2]" />
                </div>
                <div>
                    <h2 class="font-extrabold text-xl text-text">Cari Nota Digital</h2>
                    <p class="text-sm font-medium text-text-muted mt-1">Masukkan nomor nota atau nomor WhatsApp pelanggan</p>
                </div>
                <div class="flex flex-col sm:flex-row gap-2 max-w-md mx-auto">
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Contoh: AL-20261115-001"
                        class="flex-1 min-h-[44px] rounded-md border border-border bg-surface px-3.5 py-2 text-sm font-semibold text-text placeholder:text-text-muted shadow-xs focus:outline-none focus:ring-2 focus:ring-primary-100 focus:border-primary-600"
                        @keyup.enter="searchOrder"
                    />
                    <button
                        type="button"
                        @click="searchOrder"
                        class="min-h-[44px] px-5 rounded-md bg-primary-600 hover:bg-primary-700 text-white font-bold text-sm shadow-xs transition-all cursor-pointer"
                    >
                        Cari
                    </button>
                </div>
                <p class="text-xs font-medium text-text-muted">
                    Atau buka langsung tautan unik yang dikirimkan ke pesan WhatsApp Anda.
                </p>
            </div>

            <!-- Order Found -->
            <template v-else>
                <!-- Order Header Card -->
                <div class="bg-surface rounded-lg p-6 sm:p-8 border border-border shadow-sm space-y-4">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 border-b border-border">
                        <div>
                            <div class="flex items-center space-x-2 flex-wrap gap-2">
                                <span class="text-xs font-bold uppercase tracking-wider bg-primary-100 text-primary-700 px-2.5 py-0.5 rounded-pill">
                                    Nota Resmi
                                </span>
                                <span :class="['px-2.5 py-0.5 rounded-pill text-xs font-bold uppercase tracking-wider', paymentBadge.bg]">
                                    {{ paymentBadge.text }}
                                </span>
                            </div>
                            <h2 class="font-mono font-extrabold text-2xl sm:text-3xl text-text mt-2 tracking-tight">
                                {{ order.invoice_number }}
                            </h2>
                        </div>

                        <div class="text-left sm:text-right">
                            <span class="text-xs font-bold uppercase tracking-wider text-text-muted block">Total Tagihan</span>
                            <span class="font-mono font-extrabold text-2xl sm:text-3xl text-text">
                                {{ formatRupiah(order.total_amount) }}
                            </span>
                        </div>
                    </div>

                    <!-- Customer & Metadata Grid -->
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-xs">
                        <div>
                            <span class="text-text-muted block text-[11px] font-bold uppercase tracking-wider">Nama Pelanggan</span>
                            <span class="font-bold text-text text-sm mt-0.5 block">{{ order.customer.name }}</span>
                        </div>
                        <div>
                            <span class="text-text-muted block text-[11px] font-bold uppercase tracking-wider">Waktu Masuk</span>
                            <span class="font-semibold text-text mt-0.5 block">{{ formatDateTimeIndo(order.intake_at) }}</span>
                        </div>
                        <div>
                            <span class="text-text-muted block text-[11px] font-bold uppercase tracking-wider">Estimasi Selesai</span>
                            <span class="font-bold text-primary-600 mt-0.5 block">{{ formatDateTimeIndo(order.target_completion_at) }}</span>
                        </div>
                        <div>
                            <span class="text-text-muted block text-[11px] font-bold uppercase tracking-wider">Rak Pengambilan</span>
                            <span class="font-mono font-bold text-text mt-0.5 block">{{ order.staging_rack || 'Menunggu Rak' }}</span>
                        </div>
                    </div>

                    <!-- Rack Location Display when status is SELESAI -->
                    <div v-if="order.production_status.toLowerCase() === 'selesai' || order.production_status.toLowerCase() === 'ready_for_pickup'" class="p-4 bg-success/10 rounded-lg border border-success/20">
                        <div class="flex items-center space-x-2.5">
                            <MapPin class="h-6 w-6 text-success shrink-0" />
                            <div>
                                <span class="text-xs font-bold uppercase tracking-wider text-success block">Lokasi Rak Pengambilan:</span>
                                <span class="font-mono font-extrabold text-xl text-success">{{ order.staging_rack || 'Segera Ditentukan' }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Delivery Option Display -->
                    <div v-if="order.delivery_option === 'delivery'" class="p-4 bg-primary-100/50 rounded-lg border border-primary-600/30">
                        <div class="flex items-center space-x-2.5">
                            <Truck class="h-6 w-6 text-primary-600 shrink-0" />
                            <div>
                                <span class="text-xs font-bold uppercase tracking-wider text-primary-700 block">Pilihan Pengantaran</span>
                                <span class="text-sm font-bold text-text">Antar Jemput (Gratis &lt; 3 km)</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- LIVE PRODUCTION 3-STATE TRACKER -->
                <div class="bg-surface rounded-lg p-6 sm:p-8 border border-border shadow-sm space-y-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-2">
                            <Clock class="h-5 w-5 text-text" />
                            <h3 class="font-bold text-base text-text">Pelacakan Progres Cucian</h3>
                        </div>
                        <span class="text-xs font-bold px-2.5 py-0.5 rounded-pill bg-primary-100 text-primary-700">
                            {{ order.production_status_label }}
                        </span>
                    </div>

                    <!-- Cancelled Warning if applicable -->
                    <div v-if="isCancelled" class="p-4 rounded-lg bg-danger/10 border border-danger/20 text-danger flex items-center space-x-3">
                        <AlertCircle class="h-6 w-6 text-danger shrink-0" />
                        <div>
                            <span class="font-bold text-sm block">Pesanan Dibatalkan</span>
                            <span class="text-xs font-medium text-danger">Pesanan ini telah dibatalkan oleh pemilik/kasir.</span>
                        </div>
                    </div>

                    <!-- 3-Step Timeline (Mobile Responsive Stack) -->
                    <div v-else class="space-y-4 pt-1">
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div
                                v-for="(step, sIdx) in workflowSteps"
                                :key="step.key"
                                :class="[
                                    'p-4 rounded-lg transition-all flex flex-row sm:flex-col items-center sm:text-center gap-3 sm:gap-2',
                                    sIdx < mappedStepIndex
                                        ? 'bg-success/10 border border-success/20 text-success shadow-xs'
                                        : (sIdx === mappedStepIndex
                                            ? 'bg-primary-100/80 border-2 border-primary-600 text-navy-900 shadow-xs'
                                            : 'bg-bg border border-border text-text-muted'),
                                ]"
                            >
                                <div class="shrink-0">
                                    <CheckCircle2 v-if="sIdx < mappedStepIndex" class="h-6 w-6 text-success" />
                                    <div v-else-if="sIdx === mappedStepIndex" class="h-7 w-7 rounded-pill bg-primary-600 text-white flex items-center justify-center font-bold text-xs shadow-xs">
                                        {{ sIdx + 1 }}
                                    </div>
                                    <div v-else class="h-6 w-6 rounded-pill border border-border bg-surface flex items-center justify-center text-xs font-semibold text-text-muted">
                                        {{ sIdx + 1 }}
                                    </div>
                                </div>

                                <div class="min-w-0 flex-1 sm:flex-none">
                                    <span class="font-bold text-sm block leading-tight">{{ step.label }}</span>
                                    <span class="text-[11px] font-medium opacity-80 block mt-0.5">{{ step.desc }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Current Step Status Banner -->
                        <div class="p-3.5 rounded-md bg-bg border border-border text-text flex items-center justify-between text-xs">
                            <div class="flex items-center space-x-2">
                                <Sparkles class="h-4 w-4 text-primary-600 shrink-0" />
                                <span class="font-medium">
                                    Status: <strong class="font-bold text-text">{{ workflowSteps[mappedStepIndex]?.label ?? order.production_status_label }}</strong>
                                    — {{ workflowSteps[mappedStepIndex]?.desc ?? 'Sedang diproses' }}
                                </span>
                            </div>
                            <span class="font-mono text-xs text-text-muted hidden sm:inline font-bold uppercase">
                                Tahap {{ mappedStepIndex + 1 }} dari 3
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Customer & Cashier Pickup Payment Section for Unpaid Orders -->
                <div v-if="isUnpaid && order.outstanding_balance > 0" class="space-y-4">
                    <!-- Customer Pickup Instruction Card -->
                    <div class="bg-surface rounded-lg p-6 sm:p-8 border border-border shadow-sm space-y-3">
                        <div class="p-4 bg-warning/15 rounded-lg border border-warning/20 text-left">
                            <div class="flex items-center space-x-2 text-warning font-bold text-xs uppercase tracking-wider">
                                <Clock class="h-4 w-4 text-warning" />
                                <span>Bayar Pas Ambil Cucian</span>
                            </div>
                            <p class="text-xs text-warning font-medium mt-1.5 leading-relaxed">
                                Pakaian Anda sedang kami rawat & cuci. Saat pakaian bersih siap diambil, cukup <strong>tunjukkan bukti nota digital ini</strong> ke meja kasir Andik Laundry untuk pelunasan via Tunai (Cash) atau QRIS.
                            </p>
                        </div>

                        <!-- Cashier Settlement Card (Visible to Logged-in Staff) -->
                        <div v-if="authUser" class="p-4 bg-success/10 rounded-lg border border-success/20 space-y-2.5 text-left">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-2">
                                    <Coins class="h-4 w-4 text-success" />
                                    <span class="font-bold text-xs uppercase tracking-wider text-success">Aksi Kasir: Pelunasan Nota</span>
                                </div>
                                <span class="text-[11px] font-semibold text-text font-mono">Staf: {{ authUser.name }}</span>
                            </div>
                            <p class="text-[11px] text-text font-medium">
                                Pelanggan datang ke kasir untuk mengambil pakaian? Pilih metode pelunasan di bawah:
                            </p>
                            <div class="grid grid-cols-2 gap-2 pt-1">
                                <button
                                    type="button"
                                    @click="openSettleModal('cash')"
                                    class="min-h-[44px] px-3 py-2 bg-success text-white font-bold text-xs uppercase tracking-wider rounded-md hover:bg-success/90 cursor-pointer flex items-center justify-center space-x-1.5 transition-all shadow-xs"
                                >
                                    <Banknote class="h-4 w-4" />
                                    <span>Lunas Tunai</span>
                                </button>
                                <button
                                    type="button"
                                    @click="openSettleModal('qris')"
                                    class="min-h-[44px] px-3 py-2 bg-primary-600 text-white font-bold text-xs uppercase tracking-wider rounded-md hover:bg-primary-700 cursor-pointer flex items-center justify-center space-x-1.5 transition-all shadow-xs"
                                >
                                    <QrCode class="h-4 w-4" />
                                    <span>Lunas QRIS</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- QRIS Payment Card for Unpaid Orders -->
                    <div class="bg-surface rounded-lg p-6 sm:p-8 border border-border shadow-sm text-center space-y-4">
                        <div class="flex items-center justify-center space-x-2 text-text">
                            <span class="font-bold text-base uppercase tracking-wider">Pembayaran Non-Tunai (QRIS)</span>
                        </div>
                        <div class="p-5 bg-bg rounded-lg border border-border max-w-sm mx-auto shadow-xs">
                            <img 
                                src="/images/qris-andiklaundry.svg" 
                                alt="QRIS Payment Andik Laundry" 
                                class="qris-image mx-auto max-w-[200px] border border-border rounded-md shadow-xs bg-surface p-2"
                            />
                            <p class="text-xs font-medium text-text-muted mt-3">Scan QRIS melalui mobile banking atau aplikasi e-wallet Anda</p>
                            <p class="text-2xl font-extrabold font-mono text-text mt-2">
                                {{ formatRupiah(order.outstanding_balance) }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Itemized Breakdown Table -->
                <div class="bg-surface rounded-lg p-6 sm:p-8 border border-border shadow-sm space-y-4">
                    <h3 class="font-bold text-base text-text flex items-center space-x-2">
                        <Shirt class="h-5 w-5 text-text" />
                        <span>Rincian Item & Biaya</span>
                    </h3>

                    <div class="overflow-x-auto border border-border rounded-lg">
                        <table class="w-full text-left text-xs min-w-[480px] sm:min-w-0">
                            <thead>
                                <tr class="bg-bg border-b border-border text-text font-bold uppercase tracking-wider">
                                    <th class="py-3 px-4">Layanan</th>
                                    <th class="py-3 px-4 text-center">Jumlah / Berat</th>
                                    <th class="py-3 px-4 text-right">Tarif Satuan</th>
                                    <th class="py-3 px-4 text-right">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="item in order.items" :key="item.id" class="hover:bg-bg transition-colors">
                                    <td class="py-3 px-4">
                                        <span class="font-bold text-text block">{{ item.item_name }}</span>
                                        <div v-if="item.item_condition_notes" class="flex flex-wrap items-center gap-1.5 mt-1 text-[11px]">
                                            <span class="inline-flex items-center text-danger bg-danger/10 border border-danger/20 px-2 py-0.5 rounded-md font-medium">
                                                Catatan: {{ item.item_condition_notes }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="py-3 px-4 text-center font-mono font-bold text-text">
                                        <span>{{ item.actual_quantity }} {{ item.pricing_unit }}</span>
                                        <span v-if="item.billed_quantity > item.actual_quantity" class="text-[10px] text-danger block font-semibold">
                                            (Cas min. {{ item.billed_quantity }} {{ item.pricing_unit }})
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-right font-mono text-text">
                                        {{ formatRupiah(item.unit_price) }}
                                    </td>
                                    <td class="py-3 px-4 text-right font-mono font-bold text-text">
                                        {{ formatRupiah(item.subtotal) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Financial Ledger Summary -->
                    <div class="pt-3 border-t border-border space-y-1.5 max-w-xs ml-auto text-xs">
                        <div class="flex justify-between font-medium text-text-muted">
                            <span>Subtotal:</span>
                            <span class="font-mono text-text font-bold">{{ formatRupiah(order.subtotal_amount) }}</span>
                        </div>
                        <div v-if="order.surcharge_amount > 0" class="flex justify-between font-medium text-primary-600">
                            <span>Biaya Kecepatan:</span>
                            <span class="font-mono font-bold">+{{ formatRupiah(order.surcharge_amount) }}</span>
                        </div>
                        <div v-if="order.discount_amount > 0" class="flex justify-between font-medium text-danger">
                            <span>Diskon:</span>
                            <span class="font-mono font-bold">-{{ formatRupiah(order.discount_amount) }}</span>
                        </div>
                        <div class="flex justify-between font-extrabold text-sm text-text pt-1.5 border-t border-border">
                            <span>Total Tagihan:</span>
                            <span class="font-mono text-text">{{ formatRupiah(order.total_amount) }}</span>
                        </div>
                        <div class="flex justify-between font-medium text-text-muted">
                            <span>Sudah Dibayar:</span>
                            <span class="font-mono font-bold text-text">{{ formatRupiah(order.paid_amount) }}</span>
                        </div>
                        <div v-if="order.outstanding_balance > 0" class="flex justify-between font-bold text-danger pt-1.5 border-t border-border">
                            <span>Sisa Pembayaran:</span>
                            <span class="font-mono font-extrabold">{{ formatRupiah(order.outstanding_balance) }}</span>
                        </div>
                        <div v-else class="flex justify-between font-bold text-success pt-1.5 border-t border-border">
                            <span>Status:</span>
                            <span class="bg-success/10 text-success border border-success/20 px-2 py-0.5 rounded-pill text-[11px]">LUNAS</span>
                        </div>
                    </div>
                </div>

                <!-- Contact/Request Delivery Button -->
                <div class="bg-surface rounded-lg p-6 border border-border shadow-sm text-center space-y-3 no-print">
                    <a
                        :href="whatsAppLink"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex items-center justify-center space-x-2 min-h-[46px] px-6 py-2.5 rounded-md bg-success hover:bg-success/90 text-white font-bold text-sm shadow-xs transition-all cursor-pointer uppercase tracking-wider"
                    >
                        <WhatsAppIcon class="h-5 w-5" />
                        <span>Hubungi Admin / Minta Antar</span>
                    </a>
                    <p class="text-xs font-medium text-text-muted">
                        Konfirmasi bukti bayar QRIS atau permintaan pengantaran langsung via WhatsApp
                    </p>
                </div>

                <!-- Store Information Footer & Terms -->
                <div class="bg-surface rounded-lg p-6 border border-border shadow-sm text-center space-y-3">
                    <div class="space-y-1">
                        <h4 class="font-bold text-sm text-text">{{ store_info.name }}</h4>
                        <p class="text-xs font-medium text-text-muted">{{ store_info.address }}</p>
                        <p class="text-xs text-text font-mono font-semibold">No. WA Toko: {{ store_info.phone }}</p>
                    </div>

                    <p class="text-xs font-medium text-text-muted italic max-w-md mx-auto pt-2 border-t border-border">
                        {{ store_info.receipt_footer }}
                    </p>

                    <div class="pt-2 flex items-center justify-center space-x-3 text-xs no-print">
                        <a
                            :href="storeContactWaLink"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="text-primary-600 font-bold hover:underline inline-flex items-center space-x-1"
                        >
                            <WhatsAppIcon class="h-4 w-4" />
                            <span>Hubungi Kasir via WhatsApp</span>
                        </a>
                    </div>
                </div>
            </template>
        </main>

        <!-- Cashier Settlement Confirmation Modal -->
        <div
            v-if="showSettleModal && order"
            class="fixed inset-0 z-50 flex items-center justify-center bg-navy-950/60 backdrop-blur-sm p-4 no-print"
            @click.self="showSettleModal = false"
        >
            <div class="bg-surface rounded-lg max-w-md w-full border border-border shadow-2xl overflow-hidden flex flex-col">
                <div class="px-6 py-4 bg-navy-950 text-white flex items-center justify-between">
                    <div>
                        <span class="text-[10px] font-bold uppercase tracking-wider bg-surface/10 text-text-muted px-2 py-0.5 rounded-pill">
                            Kasir • Pelunasan Nota
                        </span>
                        <h3 class="font-bold text-lg text-white tracking-tight mt-1">Konfirmasi Pembayaran</h3>
                    </div>
                    <button
                        type="button"
                        @click="showSettleModal = false"
                        class="h-8 w-8 rounded-pill bg-surface/10 text-white flex items-center justify-center hover:bg-surface/20 transition-all cursor-pointer"
                    >
                        <X class="h-4 w-4" />
                    </button>
                </div>

                <div class="p-5 space-y-4">
                    <!-- Error Alert -->
                    <div v-if="settleErrorMessage" class="p-3 rounded-md bg-danger/10 border border-danger/20 text-xs text-danger font-semibold flex items-center space-x-2">
                        <AlertCircle class="h-4 w-4 text-danger shrink-0" />
                        <span>{{ settleErrorMessage }}</span>
                    </div>

                    <!-- Customer & Invoice Info -->
                    <div class="p-3.5 bg-bg rounded-md border border-border text-xs space-y-1">
                        <div class="flex justify-between">
                            <span class="font-medium text-text-muted">No. Nota:</span>
                            <span class="font-mono font-bold text-text">{{ order.invoice_number }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="font-medium text-text-muted">Pelanggan:</span>
                            <span class="font-bold text-text">{{ order.customer?.name ?? 'Pelanggan Umum' }}</span>
                        </div>
                        <div class="flex justify-between pt-1 border-t border-border">
                            <span class="font-bold text-text">Sisa Tagihan:</span>
                            <span class="font-mono font-bold text-base text-danger">{{ formatRupiah(order.outstanding_balance) }}</span>
                        </div>
                    </div>

                    <!-- Payment Method Toggle -->
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-text mb-1.5">
                            Metode Pelunasan
                        </label>
                        <div class="grid grid-cols-2 gap-2">
                            <button
                                type="button"
                                @click="settleMethod = 'cash'"
                                :class="[
                                    'p-2.5 rounded-md border text-xs font-bold flex items-center justify-center space-x-2 transition-all cursor-pointer',
                                    settleMethod === 'cash' ? 'bg-success text-white border-success shadow-xs' : 'bg-surface border-border text-text hover:bg-bg'
                                ]"
                            >
                                <Banknote class="h-4 w-4" />
                                <span>Tunai (Cash)</span>
                            </button>
                            <button
                                type="button"
                                @click="settleMethod = 'qris'"
                                :class="[
                                    'p-2.5 rounded-md border text-xs font-bold flex items-center justify-center space-x-2 transition-all cursor-pointer',
                                    settleMethod === 'qris' ? 'bg-primary-600 text-white border-primary-600 shadow-xs' : 'bg-surface border-border text-text hover:bg-bg'
                                ]"
                            >
                                <QrCode class="h-4 w-4" />
                                <span>QRIS Manual</span>
                            </button>
                        </div>
                    </div>

                    <!-- Cash Tender (if cash) -->
                    <div v-if="settleMethod === 'cash'" class="space-y-1.5">
                        <label class="block text-xs font-bold uppercase tracking-wider text-text">
                            Uang Tunai Diterima (Rp)
                        </label>
                        <input
                            v-model="settleTender"
                            type="text"
                            class="min-h-[44px] w-full rounded-md border border-border bg-surface px-3.5 py-2 text-base font-mono font-bold text-text shadow-xs focus:outline-none focus:ring-2 focus:ring-primary-100 focus:border-primary-600"
                            placeholder="Ketik nominal tunai"
                        />
                    </div>
                </div>

                <div class="p-4 bg-bg border-t border-border flex items-center space-x-2.5">
                    <button
                        type="button"
                        @click="showSettleModal = false"
                        class="min-h-[44px] px-4 rounded-md border border-border bg-surface text-text text-xs font-bold hover:bg-bg cursor-pointer shadow-xs"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        :disabled="isSettling"
                        @click="confirmSettlement"
                        class="min-h-[44px] flex-1 px-4 rounded-md bg-success hover:bg-success/90 text-white text-xs font-bold uppercase tracking-wider shadow-xs disabled:opacity-50 cursor-pointer flex items-center justify-center space-x-1.5 transition-all"
                    >
                        <Loader2 v-if="isSettling" class="h-4 w-4 animate-spin" />
                        <span v-else>Konfirmasi Lunas & Simpan</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<style>
@page {
    size: A4 portrait;
    margin: 12mm 15mm;
}

@media print {
    header,
    .no-print {
        display: none !important;
    }
    body {
        background-color: white !important;
    }
    main {
        max-width: 100% !important;
        padding: 0 !important;
        margin: 0 !important;
    }
}
</style>
