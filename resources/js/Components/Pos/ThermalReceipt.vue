<script setup lang="ts">
import { ref, computed } from 'vue';
import {
    Printer,
    Send,
    Copy,
    Check,
    X,
    ExternalLink,
    RotateCcw,
    Sparkles,
} from 'lucide-vue-next';
import {
    formatRupiah,
    formatDateTimeIndo,
    generateWhatsAppReceiptLink,
    normalizePhoneForWhatsApp,
} from '@/Support/whatsapp';

export interface StoreInfoProps {
    name?: string;
    phone?: string;
    address?: string;
    paper_width?: string;
    receipt_footer?: string;
}

interface Props {
    isOpen: boolean;
    transaction: any; // Transaction model or API response
    storeInfo?: StoreInfoProps;
}

const props = withDefaults(defineProps<Props>(), {
    isOpen: false,
    transaction: null,
    storeInfo: () => ({
        name: 'Andik Laundry',
        phone: '6281234567890',
        address: 'Jl. Raya Laundry No. 12, Jakarta',
        paper_width: '58mm',
        receipt_footer: 'Terima kasih telah mencuci di Andik Laundry. Bersih, Wangi, Rapi!',
    }),
});

const emits = defineEmits<{
    (e: 'update:isOpen', value: boolean): void;
    (e: 'new-transaction'): void;
}>();

const currentPaperWidth = ref<'58mm' | '80mm'>(
    (props.storeInfo?.paper_width === '80mm' ? '80mm' : '58mm')
);

const isCopied = ref(false);

const invoiceNumber = computed(() => props.transaction?.invoice_number ?? '-');
const customerName = computed(() => props.transaction?.customer?.name ?? 'Pelanggan Umum');
const customerPhone = computed(() => props.transaction?.customer?.phone ?? '');
const cashierName = computed(() => props.transaction?.cashier?.name ?? 'Kasir');
const intakeDate = computed(() => formatDateTimeIndo(props.transaction?.intake_at ?? props.transaction?.created_at));
const targetCompletion = computed(() => formatDateTimeIndo(props.transaction?.target_completion_at));

const items = computed(() => {
    return props.transaction?.items ?? [];
});

const subtotal = computed(() => Number(props.transaction?.subtotal_amount ?? 0));
const surcharge = computed(() => Number(props.transaction?.surcharge_amount ?? 0));
const discount = computed(() => Number(props.transaction?.discount_amount ?? 0));
const grandTotal = computed(() => Number(props.transaction?.total_amount ?? 0));
const paidAmount = computed(() => Number(props.transaction?.paid_amount ?? 0));
const changeAmount = computed(() => Number(props.transaction?.change_amount ?? 0));
const outstandingBalance = computed(() => Number(props.transaction?.outstanding_balance ?? 0));
const deliveryOption = computed(() => props.transaction?.delivery_option ?? 'pickup');

const paymentStatusLabel = computed(() => {
    if (outstandingBalance.value <= 0) return 'LUNAS';
    return 'BELUM LUNAS';
});

const digitalReceiptUrl = computed(() => {
    if (typeof window !== 'undefined') {
        return `${window.location.origin}/receipts/${invoiceNumber.value}`;
    }
    return `/receipts/${invoiceNumber.value}`;
});

// WhatsApp Link Generator
const whatsappUrl = computed(() => {
    return generateWhatsAppReceiptLink({
        phone: customerPhone.value,
        customerName: customerName.value,
        invoiceNumber: invoiceNumber.value,
        storeName: props.storeInfo.name,
        items: items.value.map((i: any) => ({
            name: i.item_name_snapshot ?? i.name ?? 'Item Cucian',
            qty: i.actual_quantity ?? i.billed_quantity ?? 1,
            unit: i.pricing_unit_snapshot ?? i.unit ?? 'kg',
            subtotal: Number(i.subtotal_snapshot ?? 0),
        })),
        grandTotal: grandTotal.value,
        paidAmount: paidAmount.value,
        outstandingBalance: outstandingBalance.value,
        receiptUrl: digitalReceiptUrl.value,
        storeFooter: props.storeInfo.receipt_footer,
    });
});

function printReceipt() {
    window.print();
}

async function copyReceiptText() {
    const rawItems = items.value.map((i: any) => {
        const name = i.item_name_snapshot ?? i.name ?? 'Item Cucian';
        const qty = i.actual_quantity ?? i.billed_quantity ?? 1;
        const unit = i.pricing_unit_snapshot ?? i.unit ?? 'kg';
        const sub = Number(i.subtotal_snapshot ?? 0);
        return `• ${name} (${qty} ${unit}): ${formatRupiah(sub)}`;
    }).join('\n');

    const text =
        `*${props.storeInfo.name}*\n` +
        `No. Nota: ${invoiceNumber.value}\n` +
        `Pelanggan: ${customerName.value} (${customerPhone.value})\n` +
        `Waktu Terima: ${intakeDate.value}\n` +
        `Target Selesai: ${targetCompletion.value}\n` +
        `------------------------\n` +
        `${rawItems}\n` +
        `------------------------\n` +
        `Total: ${formatRupiah(grandTotal.value)}\n` +
        `Bayar: ${formatRupiah(paidAmount.value)}\n` +
        `Sisa: ${formatRupiah(outstandingBalance.value)}\n` +
        `Status: ${paymentStatusLabel.value}\n\n` +
        `Cek Nota Digital:\n${digitalReceiptUrl.value}\n` +
        `${props.storeInfo.receipt_footer}`;

    try {
        await navigator.clipboard.writeText(text);
        isCopied.value = true;
        setTimeout(() => {
            isCopied.value = false;
        }, 2000);
    } catch {
        // Fallback
    }
}

function handleClose() {
    emits('update:isOpen', false);
}

function handleNewTransaction() {
    emits('update:isOpen', false);
    emits('new-transaction');
}
</script>

<template>
    <div
        v-if="isOpen && transaction"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs p-3 sm:p-4 overflow-y-auto"
        @click.self="handleClose"
    >
        <div class="bg-white rounded-[12px] max-w-lg w-full shadow-[8px_8px_0px_#000] border-[3.5px] border-black overflow-hidden flex flex-col my-auto max-h-[95vh]">
            <!-- Header & Paper Width Controls -->
            <div class="px-5 py-3.5 bg-[#FFEB3B] border-b-[3px] border-black flex items-center justify-between no-print">
                <div class="flex items-center space-x-2">
                    <Printer class="h-5 w-5 text-black stroke-[2.5]" />
                    <span class="font-black text-sm text-black uppercase tracking-wider">Struk Transaksi Kasir</span>
                </div>

                <div class="flex items-center space-x-2">
                    <!-- Paper Width Toggle (58mm / 80mm) -->
                    <div class="flex items-center bg-white border-2 border-black p-0.5 rounded-[6px] text-xs font-mono font-bold shadow-[2px_2px_0px_#000]">
                        <button
                            type="button"
                            @click="currentPaperWidth = '58mm'"
                            :class="[
                                'px-2 py-0.5 rounded-[4px] font-black transition-all cursor-pointer',
                                currentPaperWidth === '58mm' ? 'bg-black text-[#FFEB3B]' : 'text-black hover:bg-slate-100',
                            ]"
                        >
                            58mm
                        </button>
                        <button
                            type="button"
                            @click="currentPaperWidth = '80mm'"
                            :class="[
                                'px-2 py-0.5 rounded-[4px] font-black transition-all cursor-pointer',
                                currentPaperWidth === '80mm' ? 'bg-black text-[#FFEB3B]' : 'text-black hover:bg-slate-100',
                            ]"
                        >
                            80mm
                        </button>
                    </div>

                    <button
                        type="button"
                        @click="handleClose"
                        class="h-8 w-8 rounded-[6px] border-2 border-black bg-white text-black hover:bg-[#FF5252] hover:text-white shadow-[2px_2px_0px_#000] flex items-center justify-center transition-all cursor-pointer active:translate-x-[1px] active:translate-y-[1px] active:shadow-none"
                        aria-label="Tutup Struk"
                    >
                        <X class="h-4 w-4 stroke-[3]" />
                    </button>
                </div>
            </div>

            <!-- Receipt Preview Area -->
            <div class="p-4 sm:p-6 flex-1 overflow-y-auto bg-[#FFFDF5] flex justify-center border-b-[3px] border-black">
                <!-- PRINTABLE RECEIPT CONTAINER -->
                <div
                    id="thermal-receipt-printable"
                    :class="[
                        'bg-white text-black font-mono text-[11px] leading-relaxed p-4 shadow-[4px_4px_0px_#000] border-2 border-black transition-all select-text',
                        currentPaperWidth === '58mm' ? 'w-[230px] max-w-[230px]' : 'w-[320px] max-w-[320px]',
                    ]"
                >
                    <!-- Store Header -->
                    <div class="text-center pb-2 border-b border-dashed border-black space-y-0.5">
                        <h2 class="font-bold text-sm tracking-wider uppercase">{{ storeInfo.name }}</h2>
                        <p class="text-[10px] text-gray-700 leading-tight">{{ storeInfo.address }}</p>
                        <p class="text-[10px] text-gray-700">WA: {{ storeInfo.phone }}</p>
                    </div>

                    <!-- Invoice & Cashier Metadata -->
                    <div class="py-2 border-b border-dashed border-black text-[10px] space-y-0.5">
                        <div class="flex justify-between">
                            <span>No. Nota:</span>
                            <span class="font-bold">{{ invoiceNumber }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Waktu:</span>
                            <span>{{ intakeDate }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Kasir:</span>
                            <span>{{ cashierName }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Pelanggan:</span>
                            <span class="font-bold truncate max-w-[120px]">{{ customerName }}</span>
                        </div>
                        <div v-if="customerPhone" class="flex justify-between">
                            <span>No. HP:</span>
                            <span>{{ customerPhone }}</span>
                        </div>
                    </div>

                    <!-- Line Items Table -->
                    <div class="py-2 border-b border-dashed border-black space-y-1.5">
                        <div
                            v-for="(item, idx) in items"
                            :key="idx"
                            class="space-y-0.5"
                        >
                            <div class="font-bold flex justify-between">
                                <span class="truncate">{{ item.item_name_snapshot ?? item.name }}</span>
                            </div>

                            <div class="flex justify-between text-[10px] text-gray-700">
                                <span>
                                    {{ item.actual_quantity }} {{ item.pricing_unit_snapshot ?? item.unit }}
                                    <span v-if="item.billed_quantity && item.billed_quantity > item.actual_quantity">
                                        (Cas {{ item.billed_quantity }})
                                    </span>
                                    x {{ formatRupiah(item.unit_price_snapshot ?? item.price) }}
                                </span>
                                <span class="font-bold text-black">
                                    {{ formatRupiah(item.subtotal_snapshot ?? (item.billed_quantity * item.unit_price_snapshot)) }}
                                </span>
                            </div>

                            <div v-if="item.item_condition_notes" class="text-[9px] text-black bg-[#FFF9D2] border border-black/30 px-1 py-0.5 rounded-sm">
                                Catatan: {{ item.item_condition_notes }}
                            </div>
                        </div>
                    </div>

                    <!-- Delivery Option -->
                    <div class="py-2 border-b border-dashed border-black text-[10px]">
                        <div class="flex justify-between">
                            <span>Pengantaran:</span>
                            <span class="font-bold">
                                {{ deliveryOption === 'delivery' ? 'Antar Jemput' : 'Ambil Sendiri' }}
                            </span>
                        </div>
                    </div>

                    <!-- Ledger Summary -->
                    <div class="py-2 border-b border-dashed border-black space-y-1 text-[10px]">
                        <div class="flex justify-between">
                            <span>Subtotal:</span>
                            <span>{{ formatRupiah(subtotal) }}</span>
                        </div>
                        <div v-if="surcharge > 0" class="flex justify-between">
                            <span>Biaya Kecepatan:</span>
                            <span>+{{ formatRupiah(surcharge) }}</span>
                        </div>
                        <div v-if="discount > 0" class="flex justify-between">
                            <span>Diskon:</span>
                            <span>-{{ formatRupiah(discount) }}</span>
                        </div>
                        <div class="flex justify-between font-bold text-xs pt-1 border-t border-dotted border-black">
                            <span>TOTAL:</span>
                            <span>{{ formatRupiah(grandTotal) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Dibayar:</span>
                            <span>{{ formatRupiah(paidAmount) }}</span>
                        </div>
                        <div v-if="changeAmount > 0" class="flex justify-between">
                            <span>Kembalian:</span>
                            <span>{{ formatRupiah(changeAmount) }}</span>
                        </div>
                        <div v-if="outstandingBalance > 0" class="flex justify-between font-bold text-black">
                            <span>SISA TAGIHAN:</span>
                            <span class="font-mono">{{ formatRupiah(outstandingBalance) }}</span>
                        </div>
                    </div>

                    <!-- Payment Status Badge -->
                    <div class="py-2 text-center border-b border-dashed border-black">
                        <span
                            :class="[
                                'inline-block px-3 py-1 border-2 border-black font-black text-[10px] tracking-wider uppercase shadow-[2px_2px_0px_#000]',
                                outstandingBalance <= 0 ? 'bg-[#22C55E] text-black' : 'bg-[#FFEB3B] text-black',
                            ]"
                        >
                            STATUS: {{ paymentStatusLabel }}
                        </span>
                        <div v-if="targetCompletion" class="text-[9px] text-gray-700 font-bold mt-1.5">
                            Estimasi Selesai: {{ targetCompletion }}
                        </div>
                    </div>

                    <!-- Public Receipt Link Notice -->
                    <div class="pt-2 text-center text-[9px] text-gray-600 space-y-1">
                        <p class="font-bold text-black">Lacak Status Cucian Live:</p>
                        <p class="break-all font-mono text-[8px] text-black bg-[#FFF9D2] border border-black/40 p-1 rounded-sm">
                            {{ digitalReceiptUrl }}
                        </p>
                        <p class="pt-1 italic leading-tight text-gray-700">{{ storeInfo.receipt_footer }}</p>
                    </div>
                </div>
            </div>

            <!-- Footer Action Buttons (Excluded from print) -->
            <div class="p-4 bg-[#FFFDF5] space-y-2.5 no-print">
                <div class="grid grid-cols-2 gap-2.5">
                    <!-- Print Button -->
                    <button
                        type="button"
                        @click="printReceipt"
                        class="min-h-[44px] flex items-center justify-center space-x-2 rounded-[8px] bg-black hover:bg-neutral-800 text-white font-black text-xs uppercase tracking-wider border-2 border-black shadow-[3px_3px_0px_#000] active:translate-x-[2px] active:translate-y-[2px] active:shadow-none transition-all cursor-pointer"
                    >
                        <Printer class="h-4 w-4 stroke-[2.5]" />
                        <span>Cetak Struk [P]</span>
                    </button>

                    <!-- WhatsApp Share Button -->
                    <a
                        :href="whatsappUrl"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="min-h-[44px] flex items-center justify-center space-x-2 rounded-[8px] bg-[#22C55E] hover:bg-[#16a34a] text-black font-black text-xs uppercase tracking-wider border-2 border-black shadow-[3px_3px_0px_#000] active:translate-x-[2px] active:translate-y-[2px] active:shadow-none transition-all cursor-pointer"
                    >
                        <Send class="h-4 w-4 stroke-[2.5]" />
                        <span>Kirim WhatsApp</span>
                    </a>
                </div>

                <div class="grid grid-cols-2 gap-2.5">
                    <!-- Copy WhatsApp Message Text -->
                    <button
                        type="button"
                        @click="copyReceiptText"
                        class="min-h-[42px] flex items-center justify-center space-x-2 rounded-[8px] border-2 border-black bg-white hover:bg-slate-100 text-black font-black text-xs uppercase tracking-wider shadow-[3px_3px_0px_#000] active:translate-x-[2px] active:translate-y-[2px] active:shadow-none transition-all cursor-pointer"
                    >
                        <Check v-if="isCopied" class="h-4 w-4 text-[#22C55E] stroke-[3]" />
                        <Copy v-else class="h-4 w-4 text-black stroke-[2.5]" />
                        <span>{{ isCopied ? 'Tersalin!' : 'Salin Teks' }}</span>
                    </button>

                    <!-- Open Public Digital Receipt Page -->
                    <a
                        :href="digitalReceiptUrl"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="min-h-[42px] flex items-center justify-center space-x-2 rounded-[8px] border-2 border-black bg-white hover:bg-slate-100 text-black font-black text-xs uppercase tracking-wider shadow-[3px_3px_0px_#000] active:translate-x-[2px] active:translate-y-[2px] active:shadow-none transition-all cursor-pointer"
                    >
                        <ExternalLink class="h-4 w-4 text-black stroke-[2.5]" />
                        <span>Nota Digital</span>
                    </a>
                </div>

                <!-- Start New Transaction -->
                <button
                    type="button"
                    @click="handleNewTransaction"
                    class="min-h-[44px] w-full flex items-center justify-center space-x-2 rounded-[8px] bg-[#FFEB3B] hover:bg-[#FDE047] text-black font-black text-xs uppercase tracking-wider border-2 border-black shadow-[3px_3px_0px_#000] active:translate-x-[2px] active:translate-y-[2px] active:shadow-none transition-all cursor-pointer"
                >
                    <RotateCcw class="h-4 w-4 stroke-[2.5]" />
                    <span>Mulai Transaksi Baru</span>
                </button>
            </div>
        </div>
    </div>
</template>

<style>
@media print {
    /* Hide everything except printable receipt */
    body * {
        visibility: hidden !important;
    }
    #thermal-receipt-printable,
    #thermal-receipt-printable * {
        visibility: visible !important;
    }
    #thermal-receipt-printable {
        position: fixed !important;
        left: 0 !important;
        top: 0 !important;
        margin: 0 !important;
        padding: 4mm !important;
        box-shadow: none !important;
        border: none !important;
        width: 100% !important;
        max-width: 100% !important;
    }
    .no-print {
        display: none !important;
    }
}
</style>
