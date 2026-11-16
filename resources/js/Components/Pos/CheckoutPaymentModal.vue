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
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs p-3 sm:p-4"
        @click.self="closeModal"
    >
        <div class="bg-[#FFFDF5] rounded-[12px] max-w-xl w-full border-[3.5px] border-black shadow-[8px_8px_0px_#000] overflow-hidden flex flex-col max-h-[94vh]">
            <!-- Header: Neubrutalism Yellow Banner -->
            <div class="px-5 py-3.5 bg-[#FFEB3B] border-b-[3.5px] border-black flex items-center justify-between">
                <div>
                    <div class="flex items-center space-x-2">
                        <span
                            :class="[
                                'text-[10px] font-black uppercase tracking-wider px-2 py-0.5 rounded-[4px] border border-black',
                                paymentTiming === 'paid' ? 'bg-[#22C55E] text-black' : 'bg-black text-[#FFEB3B]'
                            ]"
                        >
                            {{ paymentTiming === 'paid' ? 'LUNAS DI AWAL' : 'BAYAR PAS AMBIL' }}
                        </span>
                    </div>
                    <h3 class="font-black text-lg text-black tracking-tight mt-1">Pembayaran Kasir</h3>
                </div>
                <div class="text-right">
                    <span class="text-[10px] font-bold text-black uppercase tracking-wider block">Total Tagihan</span>
                    <span class="font-mono font-black text-xl text-black bg-white px-2.5 py-0.5 rounded-[6px] border-2 border-black shadow-[2px_2px_0px_#000] inline-block">
                        {{ formatRupiah(grandTotal) }}
                    </span>
                </div>
            </div>

            <!-- Content Area -->
            <div class="p-4 sm:p-5 flex-1 overflow-y-auto space-y-4">
                <!-- Customer Snapshot Banner -->
                <div class="p-3 bg-white rounded-[8px] border-2 border-black shadow-[3px_3px_0px_#000] flex items-center justify-between">
                    <div class="flex items-center space-x-2.5 min-w-0">
                        <div class="h-9 w-9 rounded-[6px] bg-[#FFEB3B] text-black border-2 border-black shadow-[1.5px_1.5px_0px_#000] flex items-center justify-center font-black text-xs shrink-0">
                            <UserIcon class="h-4 w-4" />
                        </div>
                        <div class="truncate">
                            <span class="font-black text-xs text-black block truncate">{{ customer?.name ?? 'Pelanggan Umum' }}</span>
                            <span class="text-[11px] font-mono font-bold text-gray-700 block truncate">{{ customer?.phone || 'Pelanggan Walk-In' }}</span>
                        </div>
                    </div>
                    <div
                        :class="[
                            'shrink-0 flex items-center space-x-1 px-2.5 py-1 rounded-[6px] border-2 border-black text-[11px] font-black shadow-[1.5px_1.5px_0px_#000]',
                            paymentTiming === 'paid' ? 'bg-[#22C55E]/20 text-black' : 'bg-[#FFEB3B] text-black'
                        ]"
                    >
                        <ShieldCheck v-if="paymentTiming === 'paid'" class="h-3.5 w-3.5 text-[#16A34A]" />
                        <Clock v-else class="h-3.5 w-3.5 text-black" />
                        <span>{{ paymentTiming === 'paid' ? 'Langsung Lunas' : 'Bayar Nanti' }}</span>
                    </div>
                </div>

                <!-- Error Alert -->
                <div v-if="errorMessage" class="p-3 rounded-[8px] bg-[#FF5252]/15 border-2 border-black shadow-[3px_3px_0px_#000] text-black text-xs flex items-start space-x-2">
                    <AlertTriangle class="h-4 w-4 shrink-0 mt-0.5 text-[#FF5252]" />
                    <span class="font-bold">{{ errorMessage }}</span>
                </div>

                <!-- OPSI WAKTU PEMBAYARAN: BAYAR SEKARANG vs BAYAR PAS AMBIL -->
                <div>
                    <label class="block text-xs font-black uppercase tracking-wider text-black mb-1.5">
                        Pilihan Waktu Pembayaran
                    </label>
                    <div class="grid grid-cols-2 gap-2.5">
                        <button
                            type="button"
                            @click="paymentTiming = 'paid'"
                            :class="[
                                'min-h-[52px] p-2.5 rounded-[8px] border-2 border-black text-xs font-black flex items-center space-x-2.5 transition-all cursor-pointer',
                                paymentTiming === 'paid'
                                    ? 'bg-[#22C55E] text-black shadow-[3px_3px_0px_#000] scale-[1.01]'
                                    : 'bg-white text-gray-800 shadow-[1.5px_1.5px_0px_#000] hover:bg-[#EAFFEA]',
                            ]"
                        >
                            <CheckCircle2 class="h-5 w-5 shrink-0" />
                            <div class="text-left">
                                <span class="block font-black leading-tight text-xs">Bayar Sekarang</span>
                                <span class="block text-[10px] opacity-80 font-bold">Langsung Lunas</span>
                            </div>
                        </button>
                        <button
                            type="button"
                            @click="paymentTiming = 'unpaid'"
                            :class="[
                                'min-h-[52px] p-2.5 rounded-[8px] border-2 border-black text-xs font-black flex items-center space-x-2.5 transition-all cursor-pointer',
                                paymentTiming === 'unpaid'
                                    ? 'bg-[#FFEB3B] text-black shadow-[3px_3px_0px_#000] scale-[1.01]'
                                    : 'bg-white text-gray-800 shadow-[1.5px_1.5px_0px_#000] hover:bg-[#FFF9D2]',
                            ]"
                        >
                            <Clock class="h-5 w-5 shrink-0" />
                            <div class="text-left">
                                <span class="block font-black leading-tight text-xs">Bayar Pas Ambil</span>
                                <span class="block text-[10px] opacity-80 font-bold">Belum Bayar (Nanti)</span>
                            </div>
                        </button>
                    </div>
                </div>

                <!-- JIKA BAYAR SEKARANG: PILIH METODE TUNAI ATAU QRIS -->
                <div v-if="paymentTiming === 'paid'" class="space-y-4">
                    <div>
                        <label class="block text-xs font-black uppercase tracking-wider text-black mb-1.5">
                            Pilih Metode Pembayaran
                        </label>
                        <div class="grid grid-cols-2 gap-2.5">
                            <button
                                type="button"
                                @click="paymentMethod = 'cash'"
                                :class="[
                                    'min-h-[50px] p-3 rounded-[8px] border-2 border-black text-xs font-black flex items-center justify-center space-x-2 transition-all cursor-pointer',
                                    paymentMethod === 'cash'
                                        ? 'bg-[#FFEB3B] text-black shadow-[3px_3px_0px_#000] scale-[1.01]'
                                        : 'bg-white text-gray-800 shadow-[1.5px_1.5px_0px_#000] hover:bg-[#FFF9D2]',
                                ]"
                            >
                                <Banknote class="h-5 w-5 shrink-0" />
                                <span>Tunai (Cash)</span>
                            </button>
                            <button
                                type="button"
                                @click="paymentMethod = 'qris'"
                                :class="[
                                    'min-h-[50px] p-3 rounded-[8px] border-2 border-black text-xs font-black flex items-center justify-center space-x-2 transition-all cursor-pointer',
                                    paymentMethod === 'qris'
                                        ? 'bg-[#FFEB3B] text-black shadow-[3px_3px_0px_#000] scale-[1.01]'
                                        : 'bg-white text-gray-800 shadow-[1.5px_1.5px_0px_#000] hover:bg-[#FFF9D2]',
                                ]"
                            >
                                <QrCode class="h-5 w-5 shrink-0" />
                                <span>QRIS Manual</span>
                            </button>
                        </div>
                    </div>

                <!-- MODE 1: CASH TENDER & NUMPAD -->
                <div v-if="paymentMethod === 'cash'" class="space-y-3 p-4 bg-white rounded-[8px] border-2 border-black shadow-[3px_3px_0px_#000]">
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-black text-black uppercase tracking-wider">Uang Diterima:</span>
                        <div class="flex items-center space-x-1.5">
                            <span class="text-gray-700 font-bold text-[11px]">Kembalian:</span>
                            <span
                                :class="[
                                    'font-mono font-black text-sm px-2 py-0.5 rounded-[4px] border-2 border-black',
                                    changeAmount > 0
                                        ? 'bg-[#22C55E] text-black shadow-[2px_2px_0px_#000]'
                                        : 'bg-gray-100 text-gray-800',
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
                                'min-h-[48px] w-full rounded-[8px] border-2 border-black bg-white px-3.5 py-2 text-lg font-mono font-black text-black shadow-[2px_2px_0px_#000] focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-[#2196F3]',
                                isCashUnderpaid ? 'border-[#FF5252] bg-[#FF5252]/10' : '',
                            ]"
                        />
                        <div v-if="isCashUnderpaid" class="mt-1 text-[11px] font-bold text-[#FF5252] flex items-center space-x-1">
                            <AlertTriangle class="h-3.5 w-3.5" />
                            <span>Uang tunai kurang {{ formatRupiah(grandTotal - tenderNumber) }} (Tidak boleh utang)</span>
                        </div>
                    </div>

                    <!-- Quick Cash Buttons -->
                    <div class="grid grid-cols-4 gap-1.5">
                        <button
                            type="button"
                            @click="setExactCash"
                            class="min-h-[42px] px-2 py-1 rounded-[6px] border-2 border-black bg-[#FFEB3B] hover:bg-[#FDE047] text-xs font-black text-black shadow-[2px_2px_0px_#000] active:translate-x-0.5 active:translate-y-0.5 active:shadow-none cursor-pointer transition-all"
                        >
                            Uang Pas
                        </button>
                        <button
                            v-for="preset in quickTenders"
                            :key="preset"
                            type="button"
                            @click="setPresetTender(preset)"
                            class="min-h-[42px] px-2 py-1 rounded-[6px] border-2 border-black bg-white hover:bg-[#FFF9D2] text-xs font-mono font-black text-black shadow-[2px_2px_0px_#000] active:translate-x-0.5 active:translate-y-0.5 active:shadow-none cursor-pointer transition-all"
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
                <div v-else class="space-y-3.5 p-4 bg-white rounded-[8px] border-2 border-black shadow-[3px_3px_0px_#000]">
                    <!-- Amount to pay header -->
                    <div class="text-center p-3 rounded-[8px] bg-[#FFEB3B] border-2 border-black shadow-[2px_2px_0px_#000]">
                        <span class="text-[11px] font-black uppercase tracking-wider text-black block">Nominal Transfer QRIS</span>
                        <span class="font-mono font-black text-2xl text-black block">{{ formatRupiah(grandTotal) }}</span>
                        <span class="text-[11px] font-bold text-gray-800 block mt-0.5">Harap pastikan pelanggan transfer dengan nominal pas</span>
                    </div>

                    <!-- QRIS & Transfer Info Card -->
                    <div class="flex flex-col items-center justify-center p-3 bg-[#FFFDF5] rounded-[8px] border-2 border-black shadow-[2px_2px_0px_#000]">
                        <!-- Image Container with Toggle -->
                        <div class="relative flex justify-center">
                            <img
                                :src="showPosterImage ? '/images/qris-andiklaundry-poster.jpg' : '/images/qris-andiklaundry.svg'"
                                alt="QRIS Resmi Andik Laundry"
                                class="max-w-[210px] max-h-[250px] w-full object-contain rounded-[6px] border-2 border-black shadow-[2px_2px_0px_#000] bg-white p-1"
                            />
                        </div>

                        <!-- Toggle Switch: Vector vs Real Photo Poster -->
                        <div class="flex items-center space-x-2 mt-2.5">
                            <button
                                type="button"
                                @click="showPosterImage = false"
                                :class="[
                                    'px-2 py-1 text-[10px] font-black uppercase tracking-wider rounded-[6px] border-2 border-black transition-all cursor-pointer',
                                    !showPosterImage ? 'bg-[#FFEB3B] text-black shadow-[2px_2px_0px_#000]' : 'bg-white text-gray-700 hover:bg-gray-100'
                                ]"
                            >
                                QR Scan
                            </button>
                            <button
                                type="button"
                                @click="showPosterImage = true"
                                :class="[
                                    'px-2 py-1 text-[10px] font-black uppercase tracking-wider rounded-[6px] border-2 border-black transition-all cursor-pointer',
                                    showPosterImage ? 'bg-[#FFEB3B] text-black shadow-[2px_2px_0px_#000]' : 'bg-white text-gray-700 hover:bg-gray-100'
                                ]"
                            >
                                📷 Foto Banner Asli
                            </button>
                            <a
                                href="/images/qris-andiklaundry-poster.jpg"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="px-2 py-1 text-[10px] font-black uppercase tracking-wider rounded-[6px] border-2 border-black bg-white hover:bg-[#FFF9D2] text-black shadow-[1.5px_1.5px_0px_#000] transition-all inline-flex items-center"
                                title="Buka foto asli resolusi penuh"
                            >
                                Penuh ↗
                            </a>
                        </div>

                        <!-- Store & Payment Channels Info -->
                        <div class="mt-2 text-center w-full">
                            <span class="text-xs font-black text-black block">Andik Laundry, Service</span>
                            <span class="text-[11px] font-mono font-bold text-gray-900 block">NMID: ID1026636068961 (A01)</span>
                            <div class="mt-1.5 p-2 bg-white rounded-[6px] border border-black text-left text-[11px] space-y-1">
                                <div class="flex items-center justify-between">
                                    <span class="font-bold text-gray-700">E-Wallet DANA:</span>
                                    <span class="font-mono font-black text-black">Scan QRIS / Akun Toko</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="font-bold text-gray-700">Bank Mandiri:</span>
                                    <span class="font-mono font-black text-black">Tersedia Transfer Mandiri</span>
                                </div>
                            </div>
                            <span class="text-[10px] font-bold text-gray-600 block mt-1">Dukung Semua E-Wallet (DANA, GoPay, OVO, ShopeePay) & M-Banking GPN</span>
                        </div>
                    </div>

                    <!-- Kasir Verification Checkbox -->
                    <label
                        class="flex items-start space-x-2.5 p-3 rounded-[8px] border-2 border-black cursor-pointer transition-all select-none"
                        :class="isQrisVerified ? 'bg-[#22C55E]/15 border-[#16A34A]' : 'bg-[#FFFDF5] hover:bg-[#FFF9D2]'"
                    >
                        <input
                            v-model="isQrisVerified"
                            type="checkbox"
                            class="h-5 w-5 mt-0.5 rounded-[4px] border-2 border-black text-black focus:ring-black cursor-pointer"
                        />
                        <div class="text-xs leading-snug">
                            <span class="font-black text-black block">Verifikasi Pembayaran QRIS Masuk</span>
                            <span class="text-gray-700 font-bold block mt-0.5">
                                Saya telah memeriksa mutasi / melihat bukti berhasil transfer senilai {{ formatRupiah(grandTotal) }} dari HP pelanggan.
                            </span>
                        </div>
                    </label>
                </div>
            </div>

            <!-- JIKA BAYAR PAS AMBIL (BELUM BAYAR) -->
            <div v-else class="space-y-3.5 p-4 bg-white rounded-[8px] border-2 border-black shadow-[3px_3px_0px_#000]">
                    <!-- Amount to pay header -->
                    <div class="p-3.5 bg-[#FFF9D2] rounded-[8px] border-2 border-black shadow-[2px_2px_0px_#000] space-y-1.5">
                        <div class="flex items-center justify-between">
                            <span class="text-[11px] font-black uppercase tracking-wider text-black flex items-center space-x-1.5">
                                <Clock class="h-3.5 w-3.5" />
                                <span>Status: Belum Lunas (Bayar Nanti)</span>
                            </span>
                            <span class="text-[10px] font-black uppercase tracking-wider bg-black text-[#FFEB3B] px-2 py-0.5 rounded-[4px]">
                                Bayar Pas Ambil
                            </span>
                        </div>
                        <div class="flex items-baseline justify-between pt-1 border-t border-black/20">
                            <span class="text-xs font-bold text-gray-800">Total Tagihan Saat Pengambilan:</span>
                            <span class="text-2xl font-mono font-black text-black">{{ formatRupiah(grandTotal) }}</span>
                        </div>
                    </div>

                    <!-- Flow Information for Cashier & Customer -->
                    <div class="p-3.5 bg-[#FFFDF5] rounded-[8px] border border-black text-xs space-y-2">
                        <span class="font-black text-black block">Alur Pembayaran Pas Ambil:</span>
                        <ul class="space-y-1.5 text-gray-800 font-bold text-[11px] leading-relaxed">
                            <li class="flex items-start space-x-2">
                                <span class="text-black font-black">1.</span>
                                <span>Cucian langsung masuk antrean cuci dan diproses oleh tim laundry.</span>
                            </li>
                            <li class="flex items-start space-x-2">
                                <span class="text-black font-black">2.</span>
                                <span>Pelanggan menerima nota digital WhatsApp dengan status <strong>BELUM LUNAS</strong> dan QRIS resmi.</span>
                            </li>
                            <li class="flex items-start space-x-2">
                                <span class="text-black font-black">3.</span>
                                <span>Saat cucian selesai & diambil, pelanggan cukup <strong>menunjukkan nota digital</strong> ke kasir untuk pelunasan via Tunai atau QRIS.</span>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Notes (Optional) -->
                <div>
                    <label class="block text-xs font-black uppercase tracking-wider text-black mb-1">Catatan Struk (Opsional)</label>
                    <input
                        v-model="orderNotes"
                        type="text"
                        placeholder="Contoh: Titip hanger 2 buah / pakaian wangi ekstra"
                        class="min-h-[44px] w-full rounded-[8px] border-2 border-black bg-white px-3 py-2 text-xs font-bold text-black placeholder:text-gray-400 shadow-[2px_2px_0px_#000] focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-[#2196F3]"
                    />
                </div>
            </div>

            <!-- Footer Action Buttons -->
            <div class="p-3.5 sm:p-4 bg-[#FFEB3B]/20 border-t-[3.5px] border-black flex items-center space-x-3">
                <button
                    type="button"
                    @click="closeModal"
                    class="min-h-[46px] px-5 py-2 rounded-[8px] border-2 border-black bg-white text-black text-xs font-black shadow-[2px_2px_0px_#000] hover:bg-[#FF5252] hover:text-white active:translate-x-0.5 active:translate-y-0.5 active:shadow-none cursor-pointer transition-all"
                >
                    Batal
                </button>
                <button
                    type="button"
                    :disabled="isSubmitting || (paymentTiming === 'paid' && (isCashUnderpaid || (paymentMethod === 'qris' && !isQrisVerified)))"
                    @click="processOrderSubmit"
                    :class="[
                        'min-h-[46px] flex-1 px-5 py-2 rounded-[8px] border-[3px] border-black text-black text-sm font-black uppercase tracking-wider shadow-[4px_4px_0px_#000] active:translate-x-0.5 active:translate-y-0.5 active:shadow-[1px_1px_0px_#000] disabled:opacity-40 disabled:cursor-not-allowed flex items-center justify-center space-x-2 cursor-pointer transition-all',
                        paymentTiming === 'unpaid' ? 'bg-[#FFEB3B] hover:bg-[#FDE047]' : 'bg-[#22C55E] hover:bg-[#16A34A]'
                    ]"
                >
                    <Loader2 v-if="isSubmitting" class="h-5 w-5 animate-spin" />
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