<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import {
    X,
    CreditCard,
    Banknote,
    QrCode,
    Truck,
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
    creditCeiling: 150000,
    quickTenders: () => [10000, 20000, 50000, 100000],
    deliveryOption: 'pickup',
});

const emits = defineEmits<{
    (e: 'update:isOpen', value: boolean): void;
    (e: 'checkout-success', transaction: any): void;
}>();

// Payment Options - Simplified: Cash and QRIS only
const paymentMethod = ref<'cash' | 'qris'>('cash');
const paymentStatus = ref<'paid' | 'unpaid'>('paid');
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
        paymentStatus.value = 'paid';
        tenderInput.value = String(props.grandTotal);
        errorMessage.value = '';
        isDebtApproved.value = false;
        debtApprovalNotes.value = '';
    }
});

watch(paymentStatus, (status) => {
    if (status === 'paid') {
        tenderInput.value = String(props.grandTotal);
    } else if (status === 'unpaid') {
        tenderInput.value = '0';
    }
});

// Numeric tender parsing
const tenderNumber = computed(() => {
    if (paymentStatus.value === 'unpaid') return 0;
    const val = Number(tenderInput.value.replace(/[^0-9]/g, ''));
    return isNaN(val) ? 0 : val;
});

// Amount actually paid toward the transaction
const actualPaidAmount = computed(() => {
    if (paymentStatus.value === 'unpaid') return 0;
    if (paymentMethod.value !== 'cash') {
        return props.grandTotal;
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

    if (paymentMethod.value === 'cash' && paymentStatus.value === 'paid' && tenderNumber.value < props.grandTotal) {
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
                actual_quantity: item.calculated_area ?? item.actual_qty,
                billed_quantity: item.calculated_area ?? item.billed_qty,
                perfume_variant: item.perfume || 'Lavender',
                item_condition_notes: item.item_condition_notes || null,
                special_instructions: item.special_instructions || null,
                discount_amount: item.discount_amount || 0,
                length: item.length || null,
                width: item.width || null,
            })),
            discount_amount: props.orderDiscount || 0,
            surcharge_amount: props.tierSurcharge || 0,
            paid_amount: actualPaidAmount.value,
            payment_method: paymentMethod.value,
            payment_status: paymentStatus.value,
            notes: orderNotes.value.trim() || null,
            delivery_option: props.deliveryOption || 'pickup',
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
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-xs p-4"
        @click.self="closeModal"
    >
        <div class="bg-white rounded-[12px] max-w-xl w-full shadow-[0_8px_30px_rgba(0,0,0,0.12)] border border-[#E8E8EC] overflow-hidden flex flex-col max-h-[92vh]">
            <!-- Header -->
            <div class="px-6 py-4 bg-[#0A0A0A] text-white flex items-center justify-between">
                <div>
                    <span class="text-[10px] font-bold text-[#6366F1] uppercase tracking-wider block">Pembayaran & Checkout</span>
                    <h3 class="font-bold text-lg text-white">Kasir Andik Laundry</h3>
                </div>
                <div class="text-right">
                    <span class="text-xs text-[#9C9C9C] block">Total Tagihan</span>
                    <span class="font-mono font-bold text-xl text-white">{{ formatRupiah(grandTotal) }}</span>
                </div>
            </div>

            <!-- Content Area -->
            <div class="p-6 flex-1 overflow-y-auto space-y-4">
                <!-- Customer Snapshot Banner -->
                <div class="p-3.5 bg-[#FAFAFA] rounded-[8px] border border-[#E8E8EC] flex items-center justify-between">
                    <div class="flex items-center space-x-2.5">
                        <div class="h-9 w-9 rounded-[6px] bg-[#6366F1]/10 text-[#6366F1] border border-[#6366F1]/20 flex items-center justify-center font-bold text-xs">
                            <UserIcon class="h-4 w-4" />
                        </div>
                        <div>
                            <span class="font-bold text-xs text-[#0A0A0A] block">{{ customer?.name ?? 'Pelanggan Umum' }}</span>
                            <span class="text-[11px] font-mono text-[#6B6B6B]">{{ customer?.phone || 'Tanpa No. HP' }}</span>
                        </div>
                    </div>

                    <!-- Receivables pill if any -->
                    <div v-if="existingCustomerDebt > 0" class="text-right">
                        <span class="text-[10px] text-[#EF4444] font-semibold block">Hutang Berjalan:</span>
                        <span class="font-mono font-bold text-xs text-[#EF4444]">{{ formatRupiah(existingCustomerDebt) }}</span>
                    </div>
                </div>

                <!-- Error Alert -->
                <div v-if="errorMessage" class="p-3 rounded-[6px] bg-[#EF4444]/10 border border-[#EF4444]/20 text-[#EF4444] text-xs flex items-start space-x-2">
                    <AlertTriangle class="h-4 w-4 shrink-0 mt-0.5" />
                    <span>{{ errorMessage }}</span>
                </div>

                <!-- Payment Status Selector: Lunas / Belum Lunas -->
                <div>
                    <label class="block text-xs font-bold text-[#0A0A0A] mb-1.5">Status Pembayaran</label>
                    <div class="grid grid-cols-2 gap-2">
                        <button
                            type="button"
                            @click="paymentStatus = 'paid'"
                            :class="[
                                'min-h-[44px] py-2 px-3 rounded-[6px] border text-xs font-bold transition-all cursor-pointer focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-[#6366F1]/12',
                                paymentStatus === 'paid'
                                    ? 'bg-[#6366F1] text-white border-[#6366F1] shadow-[0_4px_12px_rgba(99,102,241,0.35)]'
                                    : 'bg-white text-[#6B6B6B] border-[#E8E8EC] hover:bg-[#FAFAFA] hover:text-[#0A0A0A]',
                            ]"
                        >
                            Lunas
                        </button>
                        <button
                            type="button"
                            @click="paymentStatus = 'unpaid'"
                            :class="[
                                'min-h-[44px] py-2 px-3 rounded-[6px] border text-xs font-bold transition-all cursor-pointer focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-[#6366F1]/12',
                                paymentStatus === 'unpaid'
                                    ? 'bg-[#0A0A0A] text-white border-[#0A0A0A] shadow-xs'
                                    : 'bg-white text-[#6B6B6B] border-[#E8E8EC] hover:bg-[#FAFAFA] hover:text-[#0A0A0A]',
                            ]"
                        >
                            Belum Lunas
                        </button>
                    </div>
                </div>

                <!-- Payment Method Selector (Cash, QRIS only) -->
                <div v-if="paymentStatus !== 'unpaid'">
                    <label class="block text-xs font-bold text-[#0A0A0A] mb-1.5">Metode Pembayaran</label>
                    <div class="grid grid-cols-2 gap-2">
                        <button
                            type="button"
                            @click="paymentMethod = 'cash'"
                            :class="[
                                'min-h-[44px] p-2.5 rounded-[6px] border text-xs font-bold flex flex-col items-center justify-center space-y-1 transition-all cursor-pointer focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-[#6366F1]/12',
                                paymentMethod === 'cash'
                                    ? 'bg-[#6366F1]/10 border-[#6366F1] text-[#6366F1]'
                                    : 'bg-white border-[#E8E8EC] text-[#6B6B6B] hover:bg-[#FAFAFA] hover:text-[#0A0A0A]',
                            ]"
                        >
                            <Banknote class="h-5 w-5" />
                            <span>Tunai (Cash)</span>
                        </button>
                        <button
                            type="button"
                            @click="paymentMethod = 'qris'"
                            :class="[
                                'min-h-[44px] p-2.5 rounded-[6px] border text-xs font-bold flex flex-col items-center justify-center space-y-1 transition-all cursor-pointer focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-[#6366F1]/12',
                                paymentMethod === 'qris'
                                    ? 'bg-[#6366F1]/10 border-[#6366F1] text-[#6366F1]'
                                    : 'bg-white border-[#E8E8EC] text-[#6B6B6B] hover:bg-[#FAFAFA] hover:text-[#0A0A0A]',
                            ]"
                        >
                            <QrCode class="h-5 w-5" />
                            <span>Non-Tunai (QRIS)</span>
                        </button>
                    </div>
                </div>

                <!-- Cash Tender Inputs & Numpad -->
                <div v-if="paymentStatus !== 'unpaid' && paymentMethod === 'cash'" class="space-y-3 p-4 bg-[#FAFAFA] rounded-[8px] border border-[#E8E8EC]">
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-bold text-[#0A0A0A]">Uang Tunai Diterima:</span>
                        <div class="text-right">
                            <span class="text-[#6B6B6B] text-[11px]">Kembalian: </span>
                            <span class="font-mono font-bold text-[#10B981] text-sm">{{ formatRupiah(changeAmount) }}</span>
                        </div>
                    </div>

                    <!-- Quick Cash Presets -->
                    <div class="grid grid-cols-4 gap-1.5">
                        <button
                            type="button"
                            @click="setExactCash"
                            class="min-h-[44px] px-2 py-1 rounded-[6px] border border-[#E8E8EC] bg-white hover:bg-[#FAFAFA] text-xs font-bold text-[#0A0A0A] cursor-pointer"
                        >
                            Uang Pas
                        </button>
                        <button
                            type="button"
                            @click="setDp50"
                            class="min-h-[44px] px-2 py-1 rounded-[6px] border border-[#F59E0B]/30 bg-[#F59E0B]/10 hover:bg-[#F59E0B]/20 text-xs font-bold text-[#F59E0B] cursor-pointer"
                        >
                            DP 50%
                        </button>
                        <button
                            v-for="preset in quickTenders"
                            :key="preset"
                            type="button"
                            @click="setPresetTender(preset)"
                            class="min-h-[44px] px-2 py-1 rounded-[6px] border border-[#E8E8EC] bg-white hover:bg-[#FAFAFA] text-xs font-mono font-bold text-[#0A0A0A] cursor-pointer"
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
                            class="min-h-[44px] w-full rounded-[6px] border border-[#E8E8EC] bg-white px-3 py-2 text-base font-mono font-bold text-[#0A0A0A] focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-[#6366F1]/12"
                        />
                    </div>

                    <!-- Interactive Numpad -->
                    <Numpad
                        v-model="tenderInput"
                        :allow-decimal="false"
                        class="pt-1"
                    />
                </div>

                <!-- QRIS Payment Info -->
                <div v-if="paymentStatus !== 'unpaid' && paymentMethod === 'qris'" class="p-4 bg-[#FAFAFA] rounded-[8px] border border-[#E8E8EC] text-center space-y-2">
                    <QrCode class="h-8 w-8 text-[#6366F1] mx-auto" />
                    <p class="text-xs text-[#6B6B6B]">
                        Tunjukkan gambar QRIS kepada pelanggan untuk pembayaran.
                    </p>
                    <p class="text-base font-bold font-mono text-[#0A0A0A]">
                        Total: {{ formatRupiah(grandTotal) }}
                    </p>
                </div>

                <!-- Credit Ceiling Alert if projected debt exceeds threshold -->
                <div
                    v-if="isExceedingCreditCeiling"
                    class="p-4 rounded-[8px] bg-[#F59E0B]/10 border border-[#F59E0B]/30 text-[#0A0A0A] space-y-2"
                >
                    <div class="flex items-center space-x-2">
                        <ShieldAlert class="h-5 w-5 text-[#F59E0B] shrink-0" />
                        <span class="font-bold text-xs text-[#F59E0B]">Peringatan Batas Plafon Piutang</span>
                    </div>
                    <p class="text-xs text-[#6B6B6B] leading-relaxed">
                        Total hutang pelanggan akan menjadi <strong>{{ formatRupiah(totalProjectedDebt) }}</strong>
                        (melebihi batas maksimal <strong>{{ formatRupiah(creditCeiling) }}</strong>).
                    </p>
                    <div class="flex items-center space-x-2 pt-1">
                        <input
                            id="debt-approve"
                            v-model="isDebtApproved"
                            type="checkbox"
                            class="h-4 w-4 rounded-[4px] border-[#E8E8EC] text-[#6366F1] focus:ring-[#6366F1]/20"
                        />
                        <label for="debt-approve" class="text-xs font-bold text-[#0A0A0A] cursor-pointer">
                            Saya menyetujui piutang melebihi plafon untuk pesanan ini
                        </label>
                    </div>
                </div>

                <!-- Notes -->
                <div>
                    <label class="block text-xs font-bold text-[#0A0A0A] mb-1">Catatan Transaksi (Opsional)</label>
                    <input
                        v-model="orderNotes"
                        type="text"
                        placeholder="Contoh: Titip hanger 3 buah, bayar lewat QRIS"
                        class="min-h-[44px] w-full rounded-[6px] border border-[#E8E8EC] bg-white px-3 py-2 text-xs text-[#0A0A0A] placeholder:text-[#9C9C9C] focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-[#6366F1]/12"
                    />
                </div>
            </div>

            <!-- Footer Action Buttons -->
            <div class="p-4 bg-[#FAFAFA] border-t border-[#E8E8EC] flex items-center space-x-3">
                <button
                    type="button"
                    @click="closeModal"
                    class="min-h-[44px] px-5 py-2 rounded-[6px] border border-[#E8E8EC] bg-white text-[#6B6B6B] text-xs font-bold hover:bg-[#FAFAFA] hover:text-[#0A0A0A] cursor-pointer"
                >
                    Batal
                </button>
                <button
                    type="button"
                    :disabled="isSubmitting"
                    @click="processOrderSubmit"
                    class="min-h-[44px] flex-1 px-5 py-2 rounded-[6px] bg-[#6366F1] hover:bg-[#4F46E5] active:scale-98 text-white text-sm font-bold shadow-[0_4px_12px_rgba(99,102,241,0.35)] disabled:opacity-50 flex items-center justify-center space-x-2 cursor-pointer transition-all"
                >
                    <Loader2 v-if="isSubmitting" class="h-5 w-5 animate-spin" />
                    <span v-else>SELESAIKAN & CETAK STRUK [F8]</span>
                </button>
            </div>
        </div>
    </div>
</template>
