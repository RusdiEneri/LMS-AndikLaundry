<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import {
    X,
    Banknote,
    QrCode,
    AlertTriangle,
    CheckCircle2,
    Loader2,
    User as UserIcon,
    ShieldCheck,
    Coins,
    Clock,
} from 'lucide-vue-next';
import Numpad from './Numpad.vue';
import { formatRupiah } from '@/Support/whatsapp';
import type { CustomerData } from './CustomerLookupModal.vue';
import type { CartLineItem, TurnaroundTier } from './CartDrawer.vue';

interface Props {
    isOpen: boolean;
    grandTotal: number;
    subtotal: number;
    tierSurcharge: number;
    orderDiscount?: number;
    customer: CustomerData | null;
    cartItems: CartLineItem[];
    turnaroundTier?: TurnaroundTier | null;
    quickTenders?: number[];
    deliveryOption?: 'pickup' | 'delivery';
}

const props = withDefaults(defineProps<Props>(), {
    isOpen: false,
    grandTotal: 0,
    subtotal: 0,
    tierSurcharge: 0,
    orderDiscount: 0,
    customer: null,
    cartItems: () => [],
    turnaroundTier: null,
    quickTenders: () => [10000, 20000, 50000, 100000],
    deliveryOption: 'pickup',
});

const emits = defineEmits<{
    (e: 'update:isOpen', value: boolean): void;
    (e: 'checkout-success', transaction: any): void;
}>();

// Payment Options: Bayar Sekarang (Lunas) vs Bayar Nanti (Pas Ambil)
const paymentTiming = ref<'paid' | 'unpaid'>('paid');
const paymentMethod = ref<'cash' | 'qris'>('cash');
const tenderInput = ref<string>('');
const isSubmitting = ref(false);
const errorMessage = ref('');
const orderNotes = ref('');
const isQrisVerified = ref(false);
const showPosterImage = ref(false);

// Sync default tender on open
watch(() => props.isOpen, (open) => {
    if (open) {
        paymentTiming.value = 'paid';
        paymentMethod.value = 'cash';
        tenderInput.value = String(props.grandTotal);
        errorMessage.value = '';
        orderNotes.value = '';
        isQrisVerified.value = false;
        showPosterImage.value = false;
    }
});

// When switching to QRIS, auto set tender to grandTotal
watch(paymentMethod, (method) => {
    if (method === 'qris') {
        tenderInput.value = String(props.grandTotal);
        isQrisVerified.value = false;
    } else {
        tenderInput.value = String(props.grandTotal);
    }
});

// Numeric tender parsing
const tenderNumber = computed(() => {
    if (paymentMethod.value === 'qris') return props.grandTotal;
    const val = Number(tenderInput.value.replace(/[^0-9]/g, ''));
    return isNaN(val) ? 0 : val;
});

// Kembalian (change amount) for cash
const changeAmount = computed(() => {
    if (paymentMethod.value !== 'cash') return 0;
    return Math.max(0, tenderNumber.value - props.grandTotal);
});

// Cash validation: cannot pay less than grand total
const isCashUnderpaid = computed(() => {
    return paymentMethod.value === 'cash' && tenderNumber.value < props.grandTotal;
});

// Quick Tender Actions
function setExactCash() {
    tenderInput.value = String(props.grandTotal);
}

function addCash(amount: number) {
    const current = tenderNumber.value;
    tenderInput.value = String(current + amount);
}

function setPresetTender(amount: number) {
    tenderInput.value = String(amount);
}

function closeModal() {
    emits('update:isOpen', false);
}

// Order Submission: Settle Now (PAID) or Pay on Pickup (UNPAID)
async function processOrderSubmit() {
    if (props.cartItems.length === 0) {
        errorMessage.value = 'Keranjang cucian tidak boleh kosong.';
        return;
    }

    if (!props.customer?.id && props.customer?.name !== 'Pelanggan Umum') {
        errorMessage.value = 'Pelanggan belum teridentifikasi. Silakan pilih pelanggan.';
        return;
    }

    const isPaid = paymentTiming.value === 'paid';

    if (isPaid) {
        if (paymentMethod.value === 'cash' && tenderNumber.value < props.grandTotal) {
            errorMessage.value = `Nominal uang tunai (${formatRupiah(tenderNumber.value)}) kurang dari total tagihan (${formatRupiah(props.grandTotal)}).`;
            return;
        }

        if (paymentMethod.value === 'qris' && !isQrisVerified.value) {
            errorMessage.value = 'Kasir wajib memverifikasi pembayaran QRIS masuk sebelum menyelesaikan pesanan.';
            return;
        }
    }

    isSubmitting.value = true;
    errorMessage.value = '';

    try {
        const csrfToken = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content;

        const payload = {
            customer_id: props.customer?.id ?? null,
            items: props.cartItems.map(item => ({
                service_id: item.service.id,
                actual_quantity: item.calculated_area ?? item.actual_qty,
                billed_quantity: item.calculated_area ?? item.billed_qty,
                perfume_variant: null,
                item_condition_notes: item.item_condition_notes || null,
                special_instructions: item.special_instructions || null,
                discount_amount: item.discount_amount || 0,
                length: item.length || null,
                width: item.width || null,
            })),
            discount_amount: props.orderDiscount || 0,
            surcharge_amount: props.tierSurcharge || 0,
            paid_amount: isPaid ? (paymentMethod.value === 'cash' ? tenderNumber.value : props.grandTotal) : 0,
            payment_method: isPaid ? paymentMethod.value : 'cash',
            payment_status: isPaid ? 'paid' : 'unpaid',
            notes: orderNotes.value.trim() || null,
            delivery_option: props.deliveryOption || 'pickup',
            is_debt_approved: !isPaid,
            debt_notes: !isPaid ? 'Bayar saat ambil cucian' : null,
        };

        const res = await fetch('/pos/orders', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken || '',
            },
            body: JSON.stringify(payload),
        });

        if (res.ok) {
            const data = await res.json();
            const transaction = data.transaction || data;
            closeModal();
            emits('checkout-success', transaction);
        } else {
            const errData = await res.json();
            errorMessage.value = errData.message || 'Gagal memproses transaksi kasir.';
        }
    } catch {
        errorMessage.value = 'Terjadi kesalahan sistem saat mengirim transaksi.';
    } finally {
        isSubmitting.value = false;
    }
}
</script>

<template>
    <div
        v-if="isOpen"
        class="fixed inset-0 z-50 flex items-center justify-center bg-[#0A1033]/60 backdrop-blur-xs p-3 sm:p-4"
        @click.self="closeModal"
    >
        <div class="bg-white rounded-3xl max-w-xl w-full border border-slate-200 shadow-2xl overflow-hidden flex flex-col max-h-[94vh]">
            <!-- Header -->
            <div class="px-6 py-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
                <div>
                    <div class="flex items-center space-x-2">
                        <span
                            :class="[
                                'text-[10px] font-bold uppercase tracking-wider px-2.5 py-0.5 rounded-full',
                                paymentTiming === 'paid' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-[#E6E9FD] text-[#26338A] border border-[#26338A]/20'
                            ]"
                        >
                            {{ paymentTiming === 'paid' ? 'LUNAS DI AWAL' : 'BAYAR PAS AMBIL' }}
                        </span>
                    </div>
                    <h3 class="font-heading font-extrabold text-lg text-slate-900 tracking-tight mt-1">Pembayaran Kasir</h3>
                </div>
                <div class="text-right">
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Total Tagihan</span>
                    <span class="font-mono font-extrabold text-xl text-[#0A1033] bg-[#E6E9FD] text-[#26338A] px-3 py-1 rounded-xl inline-block mt-0.5">
                        {{ formatRupiah(grandTotal) }}
                    </span>
                </div>
            </div>

            <!-- Content Area -->
            <div class="p-4 sm:p-6 flex-1 overflow-y-auto space-y-4">
                <!-- Customer Snapshot Banner -->
                <div class="p-3.5 bg-slate-50 rounded-2xl border border-slate-200/80 flex items-center justify-between">
                    <div class="flex items-center space-x-3 min-w-0">
                        <div class="h-9 w-9 rounded-xl bg-[#E6E9FD] text-[#4F5FE8] flex items-center justify-center font-bold text-xs shrink-0">
                            <UserIcon class="h-4 w-4" />
                        </div>
                        <div class="truncate">
                            <span class="font-heading font-bold text-xs text-slate-900 block truncate">{{ customer?.name ?? 'Pelanggan Umum' }}</span>
                            <span class="text-[11px] font-mono text-slate-500 block truncate mt-0.5">{{ customer?.phone || 'Pelanggan Walk-In' }}</span>
                        </div>
                    </div>
                    <div
                        :class="[
                            'shrink-0 flex items-center space-x-1 px-3 py-1 rounded-full text-xs font-semibold',
                            paymentTiming === 'paid' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-amber-50 text-amber-800 border border-amber-200'
                        ]"
                    >
                        <ShieldCheck v-if="paymentTiming === 'paid'" class="h-3.5 w-3.5" />
                        <Clock v-else class="h-3.5 w-3.5" />
                        <span>{{ paymentTiming === 'paid' ? 'Langsung Lunas' : 'Bayar Nanti' }}</span>
                    </div>
                </div>

                <!-- Error Alert -->
                <div v-if="errorMessage" class="p-3.5 rounded-2xl bg-rose-50 border border-rose-200 text-rose-900 text-xs flex items-start space-x-2">
                    <AlertTriangle class="h-4 w-4 shrink-0 mt-0.5 text-rose-600" />
                    <span class="font-medium">{{ errorMessage }}</span>
                </div>

                <!-- OPSI WAKTU PEMBAYARAN: BAYAR SEKARANG vs BAYAR PAS AMBIL -->
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1.5">
                        Pilihan Waktu Pembayaran
                    </label>
                    <div class="grid grid-cols-2 gap-3">
                        <button
                            type="button"
                            @click="paymentTiming = 'paid'"
                            :class="[
                                'min-h-[52px] p-3 rounded-2xl border text-xs font-bold flex items-center space-x-3 transition-all cursor-pointer',
                                paymentTiming === 'paid'
                                    ? 'bg-emerald-50 border-emerald-500 text-emerald-900 shadow-xs'
                                    : 'bg-white border-slate-200 text-slate-700 hover:bg-slate-50',
                            ]"
                        >
                            <CheckCircle2 class="h-5 w-5 shrink-0 text-emerald-600" />
                            <div class="text-left">
                                <span class="block font-bold leading-tight text-xs">Bayar Sekarang</span>
                                <span class="block text-[10px] text-slate-500 font-normal mt-0.5">Langsung Lunas</span>
                            </div>
                        </button>
                        <button
                            type="button"
                            @click="paymentTiming = 'unpaid'"
                            :class="[
                                'min-h-[52px] p-3 rounded-2xl border text-xs font-bold flex items-center space-x-3 transition-all cursor-pointer',
                                paymentTiming === 'unpaid'
                                    ? 'bg-[#E6E9FD] border-[#4F5FE8] text-[#26338A] shadow-xs'
                                    : 'bg-white border-slate-200 text-slate-700 hover:bg-slate-50',
                            ]"
                        >
                            <Clock class="h-5 w-5 shrink-0 text-[#4F5FE8]" />
                            <div class="text-left">
                                <span class="block font-bold leading-tight text-xs">Bayar Pas Ambil</span>
                                <span class="block text-[10px] text-slate-500 font-normal mt-0.5">Belum Bayar (Nanti)</span>
                            </div>
                        </button>
                    </div>
                </div>

                <!-- JIKA BAYAR SEKARANG: PILIH METODE TUNAI ATAU QRIS -->
                <div v-if="paymentTiming === 'paid'" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1.5">
                            Pilih Metode Pembayaran
                        </label>
                        <div class="grid grid-cols-2 gap-3">
                            <button
                                type="button"
                                @click="paymentMethod = 'cash'"
                                :class="[
                                    'min-h-[48px] p-3 rounded-2xl border text-xs font-bold flex items-center justify-center space-x-2 transition-all cursor-pointer',
                                    paymentMethod === 'cash'
                                        ? 'bg-[#4F5FE8] border-[#4F5FE8] text-white shadow-xs'
                                        : 'bg-white border-slate-200 text-slate-700 hover:bg-slate-50',
                                ]"
                            >
                                <Banknote class="h-4 w-4 shrink-0" />
                                <span>Tunai (Cash)</span>
                            </button>
                            <button
                                type="button"
                                @click="paymentMethod = 'qris'"
                                :class="[
                                    'min-h-[48px] p-3 rounded-2xl border text-xs font-bold flex items-center justify-center space-x-2 transition-all cursor-pointer',
                                    paymentMethod === 'qris'
                                        ? 'bg-[#4F5FE8] border-[#4F5FE8] text-white shadow-xs'
                                        : 'bg-white border-slate-200 text-slate-700 hover:bg-slate-50',
                                ]"
                            >
                                <QrCode class="h-4 w-4 shrink-0" />
                                <span>QRIS Toko</span>
                            </button>
                        </div>
                    </div>

                <!-- MODE 1: CASH TENDER & NUMPAD -->
                <div v-if="paymentMethod === 'cash'" class="space-y-3.5 p-4 bg-slate-50 rounded-2xl border border-slate-200">
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-bold text-slate-700 uppercase tracking-wider">Uang Diterima:</span>
                        <div class="flex items-center space-x-1.5">
                            <span class="text-slate-500 font-medium text-[11px]">Kembalian:</span>
                            <span
                                :class="[
                                    'font-mono font-bold text-sm px-2.5 py-0.5 rounded-lg border',
                                    changeAmount > 0
                                        ? 'bg-emerald-50 text-emerald-700 border-emerald-200'
                                        : 'bg-white text-slate-600 border-slate-200',
                                ]"
                            >
                                {{ formatRupiah(changeAmount) }}
                            </span>
                        </div>
                    </div>

                    <!-- Tender Display Box -->
                    <div class="relative">
                        <input
                            v-model="tenderInput"
                            type="text"
                            placeholder="Ketik uang tunai diterima"
                            :class="[
                                'min-h-[46px] w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-lg font-mono font-bold text-slate-900 focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-[#4F5FE8]/20 focus-visible:border-[#4F5FE8] transition-all shadow-xs',
                                isCashUnderpaid ? 'border-rose-400 bg-rose-50/50' : '',
                            ]"
                        />
                        <div v-if="isCashUnderpaid" class="mt-1 text-[11px] font-medium text-rose-600 flex items-center space-x-1">
                            <AlertTriangle class="h-3.5 w-3.5" />
                            <span>Uang tunai kurang {{ formatRupiah(grandTotal - tenderNumber) }} (Tidak boleh utang)</span>
                        </div>
                    </div>

                    <!-- Quick Cash Buttons -->
                    <div class="grid grid-cols-4 gap-2">
                        <button
                            type="button"
                            @click="setExactCash"
                            class="min-h-[40px] px-2 py-1 rounded-xl border border-[#4F5FE8] bg-[#E6E9FD] hover:bg-[#4F5FE8] hover:text-white text-xs font-bold text-[#26338A] active:scale-[0.98] cursor-pointer transition-all shadow-xs"
                        >
                            Uang Pas
                        </button>
                        <button
                            v-for="preset in quickTenders"
                            :key="preset"
                            type="button"
                            @click="setPresetTender(preset)"
                            class="min-h-[40px] px-2 py-1 rounded-xl border border-slate-200 bg-white hover:bg-slate-100 text-xs font-mono font-bold text-slate-800 active:scale-[0.98] cursor-pointer transition-all shadow-xs"
                        >
                            {{ preset >= 1000 ? `${preset / 1000}k` : preset }}
                        </button>
                    </div>

                    <!-- Interactive Numpad -->
                    <Numpad
                        v-model="tenderInput"
                        :allow-decimal="false"
                        class="pt-1"
                    />
                </div>

                <!-- MODE 2: QRIS MANUAL SCAN & VERIFY -->
                <div v-else class="space-y-3.5 p-4 bg-slate-50 rounded-2xl border border-slate-200">
                    <!-- Amount to pay header -->
                    <div class="text-center p-3.5 rounded-2xl bg-[#E6E9FD] border border-[#26338A]/20">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-[#26338A] block">Nominal Transfer QRIS</span>
                        <span class="font-mono font-extrabold text-2xl text-[#10194D] block mt-0.5">{{ formatRupiah(grandTotal) }}</span>
                        <span class="text-[11px] text-[#26338A]/80 block mt-0.5">Harap pastikan pelanggan transfer dengan nominal pas</span>
                    </div>

                    <!-- QRIS & Transfer Info Card -->
                    <div class="flex flex-col items-center justify-center p-4 bg-white rounded-2xl border border-slate-200 shadow-xs">
                        <!-- Image Container with Toggle -->
                        <div class="relative flex justify-center">
                            <img
                                :src="showPosterImage ? '/images/qris-andiklaundry-poster.jpg' : '/images/qris-andiklaundry.svg'"
                                alt="QRIS Resmi Andik Laundry"
                                class="max-w-[210px] max-h-[250px] w-full object-contain rounded-xl border border-slate-200 shadow-sm bg-white p-2"
                            />
                        </div>

                        <!-- Toggle Switch: Vector vs Real Photo Poster -->
                        <div class="flex items-center space-x-2 mt-3">
                            <button
                                type="button"
                                @click="showPosterImage = false"
                                :class="[
                                    'px-3 py-1 text-xs font-bold uppercase tracking-wider rounded-full border transition-all cursor-pointer',
                                    !showPosterImage ? 'bg-[#4F5FE8] text-white border-[#4F5FE8]' : 'bg-slate-100 text-slate-700 border-slate-200 hover:bg-slate-200'
                                ]"
                            >
                                QR Scan
                            </button>
                            <button
                                type="button"
                                @click="showPosterImage = true"
                                :class="[
                                    'px-3 py-1 text-xs font-bold uppercase tracking-wider rounded-full border transition-all cursor-pointer',
                                    showPosterImage ? 'bg-[#4F5FE8] text-white border-[#4F5FE8]' : 'bg-slate-100 text-slate-700 border-slate-200 hover:bg-slate-200'
                                ]"
                            >
                                📷 Foto Banner Asli
                            </button>
                            <a
                                href="/images/qris-andiklaundry-poster.jpg"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="px-3 py-1 text-xs font-bold uppercase tracking-wider rounded-full border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 shadow-xs transition-all inline-flex items-center"
                                title="Buka foto asli resolusi penuh"
                            >
                                Penuh ↗
                            </a>
                        </div>

                        <!-- Store & Payment Channels Info -->
                        <div class="mt-3 text-center w-full">
                            <span class="text-xs font-bold text-slate-900 block">Andik Laundry, Service</span>
                            <span class="text-[11px] font-mono font-medium text-slate-600 block mt-0.5">NMID: ID1026636068961 (A01)</span>
                            <div class="mt-2 p-3 bg-slate-50 rounded-xl border border-slate-200 text-left text-xs space-y-1">
                                <div class="flex items-center justify-between">
                                    <span class="text-slate-600 font-medium">E-Wallet DANA:</span>
                                    <span class="font-mono font-bold text-slate-900">Scan QRIS / Akun Toko</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-slate-600 font-medium">Bank Mandiri:</span>
                                    <span class="font-mono font-bold text-slate-900">Tersedia Transfer Mandiri</span>
                                </div>
                            </div>
                            <span class="text-[11px] text-slate-500 block mt-1.5">Dukung Semua E-Wallet (DANA, GoPay, OVO, ShopeePay) & M-Banking GPN</span>
                        </div>
                    </div>

                    <!-- Kasir Verification Checkbox -->
                    <label
                        class="flex items-start space-x-3 p-3.5 rounded-2xl border cursor-pointer transition-all select-none"
                        :class="isQrisVerified ? 'bg-emerald-50/70 border-emerald-300 text-emerald-900' : 'bg-white border-slate-200 hover:bg-slate-50'"
                    >
                        <input
                            v-model="isQrisVerified"
                            type="checkbox"
                            class="h-4 w-4 mt-0.5 rounded border-slate-300 text-[#4F5FE8] focus:ring-[#4F5FE8] cursor-pointer"
                        />
                        <div class="text-xs leading-snug">
                            <span class="font-bold block">Verifikasi Pembayaran QRIS Masuk</span>
                            <span class="text-slate-600 block mt-0.5 font-normal">
                                Saya telah memeriksa mutasi / melihat bukti berhasil transfer senilai {{ formatRupiah(grandTotal) }} dari HP pelanggan.
                            </span>
                        </div>
                    </label>
                </div>
            </div>

            <!-- JIKA BAYAR PAS AMBIL (BELUM BAYAR) -->
            <div v-else class="space-y-3.5 p-4 bg-slate-50 rounded-2xl border border-slate-200">
                    <!-- Amount to pay header -->
                    <div class="p-4 bg-white rounded-2xl border border-slate-200 shadow-xs space-y-1.5">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold uppercase tracking-wider text-slate-700 flex items-center space-x-1.5">
                                <Clock class="h-3.5 w-3.5 text-[#4F5FE8]" />
                                <span>Status: Belum Lunas (Bayar Nanti)</span>
                            </span>
                            <span class="text-[10px] font-bold uppercase tracking-wider bg-[#E6E9FD] text-[#26338A] px-2.5 py-0.5 rounded-full">
                                Bayar Pas Ambil
                            </span>
                        </div>
                        <div class="flex items-baseline justify-between pt-2 border-t border-slate-100">
                            <span class="text-xs text-slate-500 font-medium">Total Tagihan Saat Pengambilan:</span>
                            <span class="text-2xl font-mono font-extrabold text-[#0A1033]">{{ formatRupiah(grandTotal) }}</span>
                        </div>
                    </div>

                    <!-- Flow Information for Cashier & Customer -->
                    <div class="p-4 bg-white rounded-2xl border border-slate-200 text-xs space-y-2 shadow-xs">
                        <span class="font-bold text-slate-900 block">Alur Pembayaran Pas Ambil:</span>
                        <ul class="space-y-1.5 text-slate-600 text-xs leading-relaxed">
                            <li class="flex items-start space-x-2">
                                <span class="text-[#4F5FE8] font-bold">1.</span>
                                <span>Cucian langsung masuk antrean cuci dan diproses oleh tim laundry.</span>
                            </li>
                            <li class="flex items-start space-x-2">
                                <span class="text-[#4F5FE8] font-bold">2.</span>
                                <span>Pelanggan menerima nota digital WhatsApp dengan status <strong>BELUM LUNAS</strong> dan QRIS resmi.</span>
                            </li>
                            <li class="flex items-start space-x-2">
                                <span class="text-[#4F5FE8] font-bold">3.</span>
                                <span>Saat cucian selesai & diambil, pelanggan cukup <strong>menunjukkan nota digital</strong> ke kasir untuk pelunasan via Tunai atau QRIS.</span>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Notes (Optional) -->
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Catatan Struk (Opsional)</label>
                    <input
                        v-model="orderNotes"
                        type="text"
                        placeholder="Contoh: Titip hanger 2 buah / pakaian wangi ekstra"
                        class="min-h-[42px] w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-medium text-slate-900 placeholder:text-slate-400 focus-visible:outline-none focus-visible:border-[#4F5FE8] focus-visible:ring-3 focus-visible:ring-[#4F5FE8]/20 transition-all shadow-xs"
                    />
                </div>
            </div>

            <!-- Footer Action Buttons -->
            <div class="p-4 sm:p-5 bg-slate-50 border-t border-slate-200 flex items-center space-x-3">
                <button
                    type="button"
                    @click="closeModal"
                    class="min-h-[44px] px-5 py-2 rounded-xl border border-slate-200 bg-white text-slate-700 hover:bg-slate-100 text-xs font-bold shadow-xs active:scale-[0.98] cursor-pointer transition-all"
                >
                    Batal
                </button>
                <button
                    type="button"
                    :disabled="isSubmitting || (paymentTiming === 'paid' && (isCashUnderpaid || (paymentMethod === 'qris' && !isQrisVerified)))"
                    @click="processOrderSubmit"
                    :class="[
                        'min-h-[44px] flex-1 px-5 py-2 rounded-xl text-sm font-bold uppercase tracking-wider shadow-sm active:scale-[0.98] disabled:opacity-40 disabled:cursor-not-allowed flex items-center justify-center space-x-2 cursor-pointer transition-all',
                        paymentTiming === 'unpaid'
                            ? 'bg-[#FF8A5B] hover:bg-[#F26B3A] text-[#0A1033] shadow-[0_4px_14px_rgba(255,138,91,0.35)]'
                            : 'bg-emerald-600 hover:bg-emerald-700 text-white shadow-emerald-600/25'
                    ]"
                >
                    <Loader2 v-if="isSubmitting" class="h-4 w-4 animate-spin" />
                    <span v-else>
                        {{
                            paymentTiming === 'unpaid'
                                ? 'SIMPAN ORDER (BAYAR PAS AMBIL) & CETAK STRUK [F8]'
                                : (paymentMethod === 'qris' ? 'KONFIRMASI QRIS & CETAK STRUK [F8]' : 'LUNAS TUNAI & CETAK STRUK [F8]')
                        }}
                    </span>
                </button>
            </div>
        </div>
    </div>
</template>