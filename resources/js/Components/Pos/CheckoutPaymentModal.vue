<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import {
    X,
    CreditCard,
    Banknote,
    QrCode,
    Smartphone,
    AlertTriangle,
    CheckCircle2,
    Loader2,
    ShieldAlert,
    User as UserIcon,
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
    creditCeiling?: number;
    quickTenders?: number[];
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
    creditCeiling: 150000,
    quickTenders: () => [10000, 20000, 50000, 100000],
});

const emits = defineEmits<{
    (e: 'update:isOpen', value: boolean): void;
    (e: 'checkout-success', transaction: any): void;
}>();

// Payment Options
const paymentMethod = ref<'cash' | 'qris' | 'bank_transfer' | 'ewallet'>('cash');
const paymentMode = ref<'paid' | 'partial' | 'unpaid'>('paid');
const tenderInput = ref<string>('');
const isSubmitting = ref(false);
const errorMessage = ref('');
const orderNotes = ref('');
const isDebtApproved = ref(false);
const debtApprovalNotes = ref('');

// Sync default tender on open
watch(() => props.isOpen, (open) => {
    if (open) {
        paymentMethod.value = 'cash';
        paymentMode.value = 'paid';
        tenderInput.value = String(props.grandTotal);
        errorMessage.value = '';
        isDebtApproved.value = false;
        debtApprovalNotes.value = '';
    }
});

watch(paymentMode, (mode) => {
    if (mode === 'paid') {
        tenderInput.value = String(props.grandTotal);
    } else if (mode === 'partial') {
        tenderInput.value = String(Math.round(props.grandTotal / 2));
    } else if (mode === 'unpaid') {
        tenderInput.value = '0';
    }
});

// Numeric tender parsing
const tenderNumber = computed(() => {
    if (paymentMode.value === 'unpaid') return 0;
    const val = Number(tenderInput.value.replace(/[^0-9]/g, ''));
    return isNaN(val) ? 0 : val;
});

// Amount actually paid toward the transaction
const actualPaidAmount = computed(() => {
    if (paymentMode.value === 'unpaid') return 0;
    if (paymentMethod.value !== 'cash') {
        // Non-cash must pay exact amount
        return paymentMode.value === 'partial' ? tenderNumber.value : props.grandTotal;
    }
    return Math.min(tenderNumber.value, props.grandTotal);
});

// Change amount (Kembalian) for cash
const changeAmount = computed(() => {
    if (paymentMethod.value !== 'cash') return 0;
    return Math.max(0, tenderNumber.value - props.grandTotal);
});

// Outstanding / Debt created from this order
const currentOrderDebt = computed(() => {
    return Math.max(0, props.grandTotal - actualPaidAmount.value);
});

// Customer Debt & Credit Ceiling Guard
const existingCustomerDebt = computed(() => {
    return Number(props.customer?.outstanding_receivables ?? 0);
});

const totalProjectedDebt = computed(() => {
    return existingCustomerDebt.value + currentOrderDebt.value;
});

const isExceedingCreditCeiling = computed(() => {
    return totalProjectedDebt.value > props.creditCeiling;
});

// Quick Tender Actions
function setExactCash() {
    tenderInput.value = String(props.grandTotal);
}

function setDp50() {
    tenderInput.value = String(Math.round(props.grandTotal / 2));
}

function setPresetTender(amount: number) {
    tenderInput.value = String(amount);
}

function closeModal() {
    emits('update:isOpen', false);
}

// Order Submission
async function processOrderSubmit() {
    if (props.cartItems.length === 0) {
        errorMessage.value = 'Keranjang cucian tidak boleh kosong.';
        return;
    }

    if (!props.customer?.id && props.customer?.name !== 'Pelanggan Umum') {
        errorMessage.value = 'Pelanggan belum teridentifikasi. Silakan pilih pelanggan.';
        return;
    }

    if (paymentMode.value === 'partial' && actualPaidAmount.value <= 0) {
        errorMessage.value = 'Nominal DP harus lebih dari Rp 0.';
        return;
    }

    if (paymentMethod.value === 'cash' && paymentMode.value === 'paid' && tenderNumber.value < props.grandTotal) {
        errorMessage.value = 'Nominal uang tunai kurang dari total tagihan.';
        return;
    }

    if (isExceedingCreditCeiling.value && !isDebtApproved.value) {
        errorMessage.value = `Total piutang pelanggan (${formatRupiah(totalProjectedDebt.value)}) melebihi batas kredit (${formatRupiah(props.creditCeiling)}). Beri persetujuan kasir untuk melanjutkan.`;
        return;
    }

    isSubmitting.value = true;
    errorMessage.value = '';

    try {
        const csrfToken = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content;

        const payload = {
            customer_id: props.customer?.id ?? null,
            items: props.cartItems.map(item => ({
                service_id: item.service.id,
                service_tier_id: props.turnaroundTier?.code === 'reguler' ? null : (item.service.tiers?.find(t => t.tier_code === props.turnaroundTier?.code)?.id ?? null),
                actual_quantity: item.actual_qty,
                perfume_variant: item.perfume || 'Lavender',
                item_condition_notes: item.item_condition_notes || null,
                special_instructions: item.special_instructions || null,
                discount_amount: item.discount_amount || 0,
            })),
            discount_amount: props.orderDiscount || 0,
            surcharge_amount: props.tierSurcharge || 0,
            paid_amount: actualPaidAmount.value,
            payment_method: paymentMethod.value,
            notes: orderNotes.value.trim() || null,
            is_debt_approved: isDebtApproved.value,
            debt_notes: isDebtApproved.value ? (debtApprovalNotes.value || 'Disetujui kasir') : null,
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
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-xs p-4"
        @click.self="closeModal"
    >
        <div class="bg-white rounded-2xl max-w-xl w-full shadow-2xl border border-slate-200 overflow-hidden flex flex-col max-h-[92vh]">
            <!-- Header -->
            <div class="px-6 py-4 bg-slate-900 text-white flex items-center justify-between">
                <div>
                    <span class="text-[10px] font-bold text-teal-400 uppercase tracking-wider block">Pembayaran & Checkout</span>
                    <h3 class="font-bold text-lg text-white">Kasir Andik Laundry</h3>
                </div>
                <div class="text-right">
                    <span class="text-xs text-slate-400 block">Total Tagihan</span>
                    <span class="font-mono font-bold text-xl text-teal-300">{{ formatRupiah(grandTotal) }}</span>
                </div>
            </div>

            <!-- Content Area -->
            <div class="p-6 flex-1 overflow-y-auto space-y-4">
                <!-- Customer Snapshot Banner -->
                <div class="p-3 bg-slate-50 rounded-xl border border-slate-200 flex items-center justify-between">
                    <div class="flex items-center space-x-2.5">
                        <div class="h-9 w-9 rounded-full bg-slate-200 text-slate-700 flex items-center justify-center font-bold text-xs">
                            <UserIcon class="h-4 w-4" />
                        </div>
                        <div>
                            <span class="font-bold text-xs text-slate-900 block">{{ customer?.name ?? 'Pelanggan Umum' }}</span>
                            <span class="text-[11px] font-mono text-slate-500">{{ customer?.phone || 'Tanpa No. HP' }}</span>
                        </div>
                    </div>

                    <!-- Receivables pill if any -->
                    <div v-if="existingCustomerDebt > 0" class="text-right">
                        <span class="text-[10px] text-rose-600 font-semibold block">Hutang Berjalan:</span>
                        <span class="font-mono font-bold text-xs text-rose-700">{{ formatRupiah(existingCustomerDebt) }}</span>
                    </div>
                </div>

                <!-- Error Alert -->
                <div v-if="errorMessage" class="p-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-xs flex items-start space-x-2">
                    <AlertTriangle class="h-4 w-4 shrink-0 mt-0.5" />
                    <span>{{ errorMessage }}</span>
                </div>

                <!-- Payment Mode Selector: Lunas, DP, Bayar Nanti -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Tipe Pembayaran</label>
                    <div class="grid grid-cols-3 gap-2">
                        <button
                            type="button"
                            @click="paymentMode = 'paid'"
                            :class="[
                                'min-h-[44px] py-2 px-3 rounded-lg border text-xs font-bold transition-all',
                                paymentMode === 'paid'
                                    ? 'bg-teal-600 text-white border-teal-600 shadow-xs'
                                    : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50',
                            ]"
                        >
                            Bayar Lunas
                        </button>
                        <button
                            type="button"
                            @click="paymentMode = 'partial'"
                            :class="[
                                'min-h-[44px] py-2 px-3 rounded-lg border text-xs font-bold transition-all',
                                paymentMode === 'partial'
                                    ? 'bg-amber-600 text-white border-amber-600 shadow-xs'
                                    : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50',
                            ]"
                        >
                            Uang Muka (DP)
                        </button>
                        <button
                            type="button"
                            @click="paymentMode = 'unpaid'"
                            :class="[
                                'min-h-[44px] py-2 px-3 rounded-lg border text-xs font-bold transition-all',
                                paymentMode === 'unpaid'
                                    ? 'bg-slate-800 text-white border-slate-800 shadow-xs'
                                    : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50',
                            ]"
                        >
                            Bayar Nanti
                        </button>
                    </div>
                </div>

                <!-- Payment Method Selector (Cash, QRIS, Transfer, E-Wallet) -->
                <div v-if="paymentMode !== 'unpaid'">
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Metode Pembayaran</label>
                    <div class="grid grid-cols-4 gap-2">
                        <button
                            type="button"
                            @click="paymentMethod = 'cash'"
                            :class="[
                                'min-h-[44px] p-2 rounded-lg border text-xs font-bold flex flex-col items-center justify-center space-y-1 transition-all',
                                paymentMethod === 'cash'
                                    ? 'bg-teal-50 border-teal-600 text-teal-800'
                                    : 'bg-white border-slate-200 text-slate-700 hover:bg-slate-50',
                            ]"
                        >
                            <Banknote class="h-4 w-4" />
                            <span>Tunai</span>
                        </button>
                        <button
                            type="button"
                            @click="paymentMethod = 'qris'"
                            :class="[
                                'min-h-[44px] p-2 rounded-lg border text-xs font-bold flex flex-col items-center justify-center space-y-1 transition-all',
                                paymentMethod === 'qris'
                                    ? 'bg-teal-50 border-teal-600 text-teal-800'
                                    : 'bg-white border-slate-200 text-slate-700 hover:bg-slate-50',
                            ]"
                        >
                            <QrCode class="h-4 w-4" />
                            <span>QRIS</span>
                        </button>
                        <button
                            type="button"
                            @click="paymentMethod = 'bank_transfer'"
                            :class="[
                                'min-h-[44px] p-2 rounded-lg border text-xs font-bold flex flex-col items-center justify-center space-y-1 transition-all',
                                paymentMethod === 'bank_transfer'
                                    ? 'bg-teal-50 border-teal-600 text-teal-800'
                                    : 'bg-white border-slate-200 text-slate-700 hover:bg-slate-50',
                            ]"
                        >
                            <CreditCard class="h-4 w-4" />
                            <span>Transfer</span>
                        </button>
                        <button
                            type="button"
                            @click="paymentMethod = 'ewallet'"
                            :class="[
                                'min-h-[44px] p-2 rounded-lg border text-xs font-bold flex flex-col items-center justify-center space-y-1 transition-all',
                                paymentMethod === 'ewallet'
                                    ? 'bg-teal-50 border-teal-600 text-teal-800'
                                    : 'bg-white border-slate-200 text-slate-700 hover:bg-slate-50',
                            ]"
                        >
                            <Smartphone class="h-4 w-4" />
                            <span>E-Wallet</span>
                        </button>
                    </div>
                </div>

                <!-- Cash Tender Inputs & Numpad -->
                <div v-if="paymentMode !== 'unpaid' && paymentMethod === 'cash'" class="space-y-3 p-4 bg-slate-50 rounded-xl border border-slate-200">
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-bold text-slate-700">Uang Tunai Diterima:</span>
                        <div class="text-right">
                            <span class="text-slate-500 text-[11px]">Kembalian: </span>
                            <span class="font-mono font-bold text-emerald-700 text-sm">{{ formatRupiah(changeAmount) }}</span>
                        </div>
                    </div>

                    <!-- Quick Cash Presets -->
                    <div class="grid grid-cols-4 gap-1.5">
                        <button
                            type="button"
                            @click="setExactCash"
                            class="min-h-[36px] px-2 py-1 rounded-md border border-slate-300 bg-white hover:bg-slate-100 text-xs font-bold text-slate-800"
                        >
                            Uang Pas
                        </button>
                        <button
                            type="button"
                            @click="setDp50"
                            class="min-h-[36px] px-2 py-1 rounded-md border border-amber-300 bg-amber-50 hover:bg-amber-100 text-xs font-bold text-amber-900"
                        >
                            DP 50%
                        </button>
                        <button
                            v-for="preset in quickTenders"
                            :key="preset"
                            type="button"
                            @click="setPresetTender(preset)"
                            class="min-h-[36px] px-2 py-1 rounded-md border border-slate-300 bg-white hover:bg-slate-100 text-xs font-mono font-bold text-slate-800"
                        >
                            {{ (preset / 1000) }}k
                        </button>
                    </div>

                    <!-- Tender Display -->
                    <div class="relative">
                        <input
                            v-model="tenderInput"
                            type="text"
                            placeholder="Nominal Pembayaran Tunai"
                            class="min-h-[44px] w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-base font-mono font-bold text-slate-900 focus:outline-none focus:ring-2 focus:ring-teal-500"
                        />
                    </div>

                    <!-- Interactive Numpad -->
                    <Numpad
                        v-model="tenderInput"
                        :allow-decimal="false"
                        class="pt-1"
                    />
                </div>

                <!-- Credit Ceiling Alert if projected debt exceeds threshold -->
                <div
                    v-if="isExceedingCreditCeiling"
                    class="p-4 rounded-xl bg-amber-50 border border-amber-300 text-amber-900 space-y-2"
                >
                    <div class="flex items-center space-x-2">
                        <ShieldAlert class="h-5 w-5 text-amber-600 shrink-0" />
                        <span class="font-bold text-xs">Peringatan Batas Plafon Piutang (Credit Ceiling)</span>
                    </div>
                    <p class="text-xs text-amber-800 leading-relaxed">
                        Total hutang pelanggan akan menjadi <strong>{{ formatRupiah(totalProjectedDebt) }}</strong>
                        (melebihi batas maksimal <strong>{{ formatRupiah(creditCeiling) }}</strong>).
                    </p>
                    <div class="flex items-center space-x-2 pt-1">
                        <input
                            id="debt-approve"
                            v-model="isDebtApproved"
                            type="checkbox"
                            class="h-4 w-4 rounded border-amber-400 text-teal-600 focus:ring-teal-500"
                        />
                        <label for="debt-approve" class="text-xs font-bold text-amber-950 cursor-pointer">
                            Saya menyetujui piutang melebihi plafon untuk pesanan ini
                        </label>
                    </div>
                </div>

                <!-- Notes -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Catatan Transaksi (Opsional)</label>
                    <input
                        v-model="orderNotes"
                        type="text"
                        placeholder="Contoh: Titip hanger baju 3 buah, bayar via transfer BCA"
                        class="min-h-[44px] w-full rounded-lg border border-slate-300 px-3 py-2 text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-teal-500"
                    />
                </div>
            </div>

            <!-- Footer Action Buttons -->
            <div class="p-4 bg-slate-50 border-t border-slate-200 flex items-center space-x-3">
                <button
                    type="button"
                    @click="closeModal"
                    class="min-h-[48px] px-5 py-2 rounded-xl border border-slate-300 bg-white text-slate-700 text-xs font-bold hover:bg-slate-100"
                >
                    Batal
                </button>
                <button
                    type="button"
                    :disabled="isSubmitting"
                    @click="processOrderSubmit"
                    class="min-h-[48px] flex-1 px-5 py-2 rounded-xl bg-teal-600 hover:bg-teal-700 active:scale-98 text-white text-sm font-bold shadow-md disabled:opacity-50 flex items-center justify-center space-x-2"
                >
                    <Loader2 v-if="isSubmitting" class="h-5 w-5 animate-spin" />
                    <span v-else>SELESAIKAN & CETAK STRUK [F8]</span>
                </button>
            </div>
        </div>
    </div>
</template>
