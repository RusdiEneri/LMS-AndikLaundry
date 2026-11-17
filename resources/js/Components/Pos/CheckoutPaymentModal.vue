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

const paymentTiming = ref<'paid' | 'unpaid'>('paid');
const paymentMethod = ref<'cash' | 'qris'>('cash');
const tenderInput = ref<string>('');
const isSubmitting = ref(false);
const errorMessage = ref('');
const orderNotes = ref('');
const isQrisVerified = ref(false);
const showPosterImage = ref(false);

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

watch(paymentMethod, (method) => {
    if (method === 'qris') {
        tenderInput.value = String(props.grandTotal);
        isQrisVerified.value = false;
    } else {
        tenderInput.value = String(props.grandTotal);
    }
});

const tenderNumber = computed(() => {
    if (paymentMethod.value === 'qris') return props.grandTotal;
    const val = Number(tenderInput.value.replace(/[^0-9]/g, ''));
    return isNaN(val) ? 0 : val;
});

const changeAmount = computed(() => {
    if (paymentMethod.value !== 'cash') return 0;
    return Math.max(0, tenderNumber.value - props.grandTotal);
});

const isCashUnderpaid = computed(() => {
    return paymentMethod.value === 'cash' && tenderNumber.value < props.grandTotal;
});

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
        class="fixed inset-0 z-50 flex items-center justify-center bg-navy-950/60 backdrop-blur-[4px] p-3 sm:p-4"
        @click.self="closeModal"
    >
        <div class="bg-surface rounded-xl max-w-xl w-full shadow-lg overflow-hidden flex flex-col max-h-[94vh]">
            <div class="px-6 py-4 bg-bg border-b border-border flex items-center justify-between">
                <div>
                    <div class="flex items-center space-x-2">
                        <span
                            :class="[
                                'overline-label px-2.5 py-0.5 rounded-pill',
                                paymentTiming === 'paid' ? 'bg-accent-100 text-accent-700' : 'bg-primary-100 text-primary-700'
                            ]"
                        >
                            {{ paymentTiming === 'paid' ? 'LUNAS DI AWAL' : 'BAYAR PAS AMBIL' }}
                        </span>
                    </div>
                    <h3 class="font-heading font-bold text-lg text-text tracking-tight mt-1">Pembayaran Kasir</h3>
                </div>
                <div class="text-right">
                    <span class="overline-label text-text-muted block">Total Tagihan</span>
                    <span class="font-mono font-bold text-xl text-navy-950 bg-primary-100 px-3 py-1 rounded-md inline-block mt-0.5 tabular-nums">
                        {{ formatRupiah(grandTotal) }}
                    </span>
                </div>
            </div>

            <div class="p-4 sm:p-6 flex-1 overflow-y-auto space-y-4">
                <div class="p-3.5 bg-bg rounded-lg border border-border flex items-center justify-between">
                    <div class="flex items-center space-x-3 min-w-0">
                        <div class="h-9 w-9 rounded-full bg-primary-100 text-primary-700 flex items-center justify-center font-bold text-xs shrink-0">
                            <UserIcon class="h-4 w-4" />
                        </div>
                        <div class="truncate">
                            <span class="font-heading font-bold text-xs text-text block truncate">{{ customer?.name ?? 'Pelanggan Umum' }}</span>
                            <span class="text-[11px] font-mono text-text-muted block truncate mt-0.5">{{ customer?.phone || 'Pelanggan Walk-In' }}</span>
                        </div>
                    </div>
                    <div
                        :class="[
                            'shrink-0 flex items-center space-x-1 px-3 py-1 rounded-pill text-xs font-semibold',
                            paymentTiming === 'paid' ? 'bg-accent-100 text-accent-700' : 'bg-[#FFF1D6] text-[#8A5A00]'
                        ]"
                    >
                        <ShieldCheck v-if="paymentTiming === 'paid'" class="h-3.5 w-3.5" />
                        <Clock v-else class="h-3.5 w-3.5" />
                        <span>{{ paymentTiming === 'paid' ? 'Langsung Lunas' : 'Bayar Nanti' }}</span>
                    </div>
                </div>

                <div v-if="errorMessage" class="p-3.5 rounded-md bg-[#FDE3E4] border-l-4 border-l-danger text-[#B3262B] text-xs flex items-start space-x-2">
                    <AlertTriangle class="h-4 w-4 shrink-0 mt-0.5" />
                    <span class="font-medium">{{ errorMessage }}</span>
                </div>

                <div>
                    <label class="overline-label text-text-muted mb-1.5 block">
                        Pilihan Waktu Pembayaran
                    </label>
                    <div class="grid grid-cols-2 gap-3">
                        <button
                            type="button"
                            @click="paymentTiming = 'paid'"
                            :class="[
                                'min-h-[52px] p-3 rounded-lg border text-xs font-bold flex items-center space-x-3 transition-all duration-[120ms] ease-standard cursor-pointer focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-primary-500',
                                paymentTiming === 'paid'
                                    ? 'bg-accent-100 border-accent-500 text-accent-700'
                                    : 'bg-surface border-border text-text hover:bg-bg',
                            ]"
                        >
                            <CheckCircle2 class="h-5 w-5 shrink-0 text-success" />
                            <div class="text-left">
                                <span class="block font-bold leading-tight text-xs">Bayar Sekarang</span>
                                <span class="block text-[10px] text-text-muted font-normal mt-0.5">Langsung Lunas</span>
                            </div>
                        </button>
                        <button
                            type="button"
                            @click="paymentTiming = 'unpaid'"
                            :class="[
                                'min-h-[52px] p-3 rounded-lg border text-xs font-bold flex items-center space-x-3 transition-all duration-[120ms] ease-standard cursor-pointer focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-primary-500',
                                paymentTiming === 'unpaid'
                                    ? 'bg-primary-100 border-primary-600 text-navy-800'
                                    : 'bg-surface border-border text-text hover:bg-bg',
                            ]"
                        >
                            <Clock class="h-5 w-5 shrink-0 text-primary-600" />
                            <div class="text-left">
                                <span class="block font-bold leading-tight text-xs">Bayar Pas Ambil</span>
                                <span class="block text-[10px] text-text-muted font-normal mt-0.5">Belum Bayar (Nanti)</span>
                            </div>
                        </button>
                    </div>
                </div>

                <div v-if="paymentTiming === 'paid'" class="space-y-4">
                    <div>
                        <label class="overline-label text-text-muted mb-1.5 block">
                            Pilih Metode Pembayaran
                        </label>
                        <div class="grid grid-cols-2 gap-3">
                            <button
                                type="button"
                                @click="paymentMethod = 'cash'"
                                :class="[
                                    'min-h-[48px] p-3 rounded-lg border text-xs font-bold flex items-center justify-center space-x-2 transition-all duration-[120ms] ease-standard cursor-pointer focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-primary-500',
                                    paymentMethod === 'cash'
                                        ? 'bg-navy-800 border-navy-800 text-white'
                                        : 'bg-surface border-border text-text hover:bg-bg',
                                ]"
                            >
                                <Banknote class="h-4 w-4 shrink-0" />
                                <span>Tunai (Cash)</span>
                            </button>
                            <button
                                type="button"
                                @click="paymentMethod = 'qris'"
                                :class="[
                                    'min-h-[48px] p-3 rounded-lg border text-xs font-bold flex items-center justify-center space-x-2 transition-all duration-[120ms] ease-standard cursor-pointer focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-primary-500',
                                    paymentMethod === 'qris'
                                        ? 'bg-navy-800 border-navy-800 text-white'
                                        : 'bg-surface border-border text-text hover:bg-bg',
                                ]"
                            >
                                <QrCode class="h-4 w-4 shrink-0" />
                                <span>QRIS Toko</span>
                            </button>
                        </div>
                    </div>

                <div v-if="paymentMethod === 'cash'" class="space-y-3.5 p-4 bg-bg rounded-lg border border-border">
                    <div class="flex items-center justify-between text-xs">
                        <span class="overline-label text-text">Uang Diterima:</span>
                        <div class="flex items-center space-x-1.5">
                            <span class="text-text-muted font-medium text-[11px]">Kembalian:</span>
                            <span
                                :class="[
                                    'font-mono font-semibold text-sm px-2.5 py-0.5 rounded-md tabular-nums',
                                    changeAmount > 0
                                        ? 'bg-accent-100 text-accent-700'
                                        : 'bg-surface text-text-muted border border-border',
                                ]"
                            >
                                {{ formatRupiah(changeAmount) }}
                            </span>
                        </div>
                    </div>

                    <div class="relative">
                        <input
                            v-model="tenderInput"
                            type="text"
                            placeholder="Ketik uang tunai diterima"
                            :class="[
                                'min-h-[46px] w-full rounded-md border border-border bg-surface px-3.5 py-2 text-lg font-mono font-bold text-text focus-visible:outline-none focus-visible:border-primary-600 focus-visible:ring-3 focus-visible:ring-primary-100 transition-all duration-[120ms] ease-standard tabular-nums',
                                isCashUnderpaid ? 'border-danger bg-[#FDE3E4]/50' : '',
                            ]"
                        />
                        <div v-if="isCashUnderpaid" class="mt-1 text-[11px] font-medium text-danger flex items-center space-x-1">
                            <AlertTriangle class="h-3.5 w-3.5" />
                            <span>Uang tunai kurang {{ formatRupiah(grandTotal - tenderNumber) }} (Tidak boleh utang)</span>
                        </div>
                    </div>

                    <div class="grid grid-cols-4 gap-2">
                        <button
                            type="button"
                            @click="setExactCash"
                            class="min-h-[40px] px-2 py-1 rounded-pill border-[1.5px] border-primary-600 bg-primary-100 hover:bg-primary-600 hover:text-white text-xs font-bold text-navy-800 active:scale-[0.98] cursor-pointer transition-all duration-[120ms] ease-standard focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-primary-500"
                        >
                            Uang Pas
                        </button>
                        <button
                            v-for="preset in quickTenders"
                            :key="preset"
                            type="button"
                            @click="setPresetTender(preset)"
                            class="min-h-[40px] px-2 py-1 rounded-pill border border-border bg-surface hover:bg-primary-100 text-xs font-mono font-bold text-text active:scale-[0.98] cursor-pointer transition-all duration-[120ms] ease-standard focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-primary-500 tabular-nums"
                        >
                            {{ preset >= 1000 ? `${preset / 1000}k` : preset }}
                        </button>
                    </div>

                    <Numpad
                        v-model="tenderInput"
                        :allow-decimal="false"
                        class="pt-1"
                    />
                </div>

                <div v-else class="space-y-3.5 p-4 bg-bg rounded-lg border border-border">
                    <div class="text-center p-3.5 rounded-lg bg-primary-100">
                        <span class="overline-label text-primary-700 block">Nominal Transfer QRIS</span>
                        <span class="font-mono font-bold text-2xl text-navy-950 block mt-0.5 tabular-nums">{{ formatRupiah(grandTotal) }}</span>
                        <span class="text-[11px] text-primary-700 block mt-0.5">Harap pastikan pelanggan transfer dengan nominal pas</span>
                    </div>

                    <div class="flex flex-col items-center justify-center p-4 bg-surface rounded-lg border border-border">
                        <div class="relative flex justify-center">
                            <img
                                :src="showPosterImage ? '/images/qris-andiklaundry-poster.jpg' : '/images/qris-andiklaundry.svg'"
                                alt="QRIS Resmi Andik Laundry"
                                class="max-w-[210px] max-h-[250px] w-full object-contain rounded-md border border-border shadow-sm bg-surface p-2"
                            />
                        </div>

                        <div class="flex items-center space-x-2 mt-3">
                            <button
                                type="button"
                                @click="showPosterImage = false"
                                :class="[
                                    'px-3 py-1 text-xs font-semibold rounded-pill border transition-all duration-[120ms] ease-standard cursor-pointer focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-primary-500',
                                    !showPosterImage ? 'bg-navy-800 text-white border-navy-800' : 'bg-bg text-text-muted border-border hover:bg-primary-100'
                                ]"
                            >
                                QR Scan
                            </button>
                            <button
                                type="button"
                                @click="showPosterImage = true"
                                :class="[
                                    'px-3 py-1 text-xs font-semibold rounded-pill border transition-all duration-[120ms] ease-standard cursor-pointer focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-primary-500',
                                    showPosterImage ? 'bg-navy-800 text-white border-navy-800' : 'bg-bg text-text-muted border-border hover:bg-primary-100'
                                ]"
                            >
                                📷 Foto Banner Asli
                            </button>
                            <a
                                href="/images/qris-andiklaundry-poster.jpg"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="px-3 py-1 text-xs font-semibold rounded-pill border border-border bg-surface hover:bg-primary-100 text-text transition-all duration-[120ms] ease-standard inline-flex items-center focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-primary-500"
                                title="Buka foto asli resolusi penuh"
                            >
                                Penuh ↗
                            </a>
                        </div>

                        <div class="mt-3 text-center w-full">
                            <span class="text-xs font-bold text-text block">Andik Laundry, Service</span>
                            <span class="text-[11px] font-mono font-medium text-text-muted block mt-0.5">NMID: ID1026636068961 (A01)</span>
                            <div class="mt-2 p-3 bg-bg rounded-md border border-border text-left text-xs space-y-1">
                                <div class="flex items-center justify-between">
                                    <span class="text-text-muted font-medium">E-Wallet DANA:</span>
                                    <span class="font-mono font-semibold text-text">Scan QRIS / Akun Toko</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-text-muted font-medium">Bank Mandiri:</span>
                                    <span class="font-mono font-semibold text-text">Tersedia Transfer Mandiri</span>
                                </div>
                            </div>
                            <span class="text-[11px] text-text-muted block mt-1.5">Dukung Semua E-Wallet (DANA, GoPay, OVO, ShopeePay) & M-Banking GPN</span>
                        </div>
                    </div>

                    <label
                        class="flex items-start space-x-3 p-3.5 rounded-lg border cursor-pointer transition-all duration-[120ms] ease-standard select-none focus-within:ring-3 focus-within:ring-primary-500"
                        :class="isQrisVerified ? 'bg-accent-100/70 border-accent-500 text-accent-700' : 'bg-surface border-border hover:bg-bg'"
                    >
                        <input
                            v-model="isQrisVerified"
                            type="checkbox"
                            class="h-4 w-4 mt-0.5 rounded border-border accent-primary-600 cursor-pointer"
                        />
                        <div class="text-xs leading-snug">
                            <span class="font-bold block">Verifikasi Pembayaran QRIS Masuk</span>
                            <span class="text-text-muted block mt-0.5 font-normal">
                                Saya telah memeriksa mutasi / melihat bukti berhasil transfer senilai {{ formatRupiah(grandTotal) }} dari HP pelanggan.
                            </span>
                        </div>
                    </label>
                </div>
                </div>

                <div v-else class="space-y-3.5 p-4 bg-bg rounded-lg border border-border">
                    <div class="p-4 bg-surface rounded-lg border border-border shadow-sm space-y-1.5">
                        <div class="flex items-center justify-between">
                            <span class="overline-label text-text flex items-center space-x-1.5">
                                <Clock class="h-3.5 w-3.5 text-primary-600" />
                                <span>Status: Belum Lunas (Bayar Nanti)</span>
                            </span>
                            <span class="overline-label bg-primary-100 text-primary-700 px-2.5 py-0.5 rounded-pill">
                                Bayar Pas Ambil
                            </span>
                        </div>
                        <div class="flex items-baseline justify-between pt-2 border-t border-border">
                            <span class="text-xs text-text-muted font-medium">Total Tagihan Saat Pengambilan:</span>
                            <span class="text-2xl font-mono font-bold text-navy-950 tabular-nums">{{ formatRupiah(grandTotal) }}</span>
                        </div>
                    </div>

                    <div class="p-4 bg-surface rounded-lg border border-border text-xs space-y-2 shadow-sm">
                        <span class="font-bold text-text block">Alur Pembayaran Pas Ambil:</span>
                        <ul class="space-y-1.5 text-text-muted text-xs leading-relaxed">
                            <li class="flex items-start space-x-2">
                                <span class="text-primary-700 font-bold">1.</span>
                                <span>Cucian langsung masuk antrean cuci dan diproses oleh tim laundry.</span>
                            </li>
                            <li class="flex items-start space-x-2">
                                <span class="text-primary-700 font-bold">2.</span>
                                <span>Pelanggan menerima nota digital WhatsApp dengan status <strong>BELUM LUNAS</strong> dan QRIS resmi.</span>
                            </li>
                            <li class="flex items-start space-x-2">
                                <span class="text-primary-700 font-bold">3.</span>
                                <span>Saat cucian selesai & diambil, pelanggan cukup <strong>menunjukkan nota digital</strong> ke kasir untuk pelunasan via Tunai atau QRIS.</span>
                            </li>
                        </ul>
                    </div>
                </div>

                <div>
                    <label class="overline-label text-text-muted mb-1 block">Catatan Struk (Opsional)</label>
                    <input
                        v-model="orderNotes"
                        type="text"
                        placeholder="Contoh: Titip hanger 2 buah / pakaian wangi ekstra"
                        class="min-h-[42px] w-full rounded-md border border-border bg-surface px-3.5 py-2 text-xs font-medium text-text placeholder:text-text-muted focus-visible:outline-none focus-visible:border-primary-600 focus-visible:ring-3 focus-visible:ring-primary-100 transition-all duration-[120ms] ease-standard"
                    />
                </div>
            </div>

            <div class="p-4 sm:p-5 bg-bg border-t border-border flex items-center space-x-3">
                <button
                    type="button"
                    @click="closeModal"
                    class="min-h-[44px] px-5 py-2 rounded-pill border-[1.5px] border-navy-900 bg-surface text-text hover:bg-primary-100/50 text-xs font-semibold active:scale-[0.98] cursor-pointer transition-all duration-[120ms] ease-standard focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-primary-500"
                >
                    Batal
                </button>
                <button
                    type="button"
                    :disabled="isSubmitting || (paymentTiming === 'paid' && (isCashUnderpaid || (paymentMethod === 'qris' && !isQrisVerified)))"
                    @click="processOrderSubmit"
                    :class="[
                        'min-h-[44px] flex-1 px-5 py-2 rounded-pill text-sm font-bold uppercase tracking-wider shadow-sm active:scale-[0.98] disabled:opacity-40 disabled:pointer-events-none flex items-center justify-center space-x-2 cursor-pointer transition-all duration-[120ms] ease-standard focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-primary-500',
                        paymentTiming === 'unpaid'
                            ? 'bg-accent-500 hover:bg-accent-600 text-navy-950'
                            : 'bg-success hover:bg-success/90 text-white'
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
