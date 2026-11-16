<script setup lang="ts">
import { computed, ref, reactive, onMounted, onUnmounted } from 'vue';
import PosLayout from '@/Layouts/PosLayout.vue';
import ServiceCard, { type ServiceItem } from '@/Components/Pos/ServiceCard.vue';
import CustomerLookupModal, { type CustomerData } from '@/Components/Pos/CustomerLookupModal.vue';
import SatuanInspectionModal from '@/Components/Pos/SatuanInspectionModal.vue';
import CartDrawer, { type CartLineItem, type TurnaroundTier } from '@/Components/Pos/CartDrawer.vue';
import CheckoutPaymentModal from '@/Components/Pos/CheckoutPaymentModal.vue';
import ThermalReceipt, { type StoreInfoProps } from '@/Components/Pos/ThermalReceipt.vue';
import { formatRupiah } from '@/Support/whatsapp';
import {
    Search,
    User as UserIcon,
    AlertCircle,
    UserCheck,
    CreditCard,
    Sparkles,
} from 'lucide-vue-next';

interface ShiftInfo {
    id?: string;
    cashier_name?: string;
    is_open?: boolean;
    opened_at?: string;
    opening_cash?: number;
    drawer_cash?: number;
}

interface StoreInfo extends StoreInfoProps {
    default_min_weight?: number;
    customer_credit_ceiling?: number;
}

interface Props {
    active_shift?: ShiftInfo;
    store_info?: StoreInfo;
    default_customer?: CustomerData;
    categories?: string[];
    services?: ServiceItem[];
    quick_tenders?: number[];
    turnaround_tiers?: TurnaroundTier[];
}

const props = withDefaults(defineProps<Props>(), {
    active_shift: () => ({
        id: 'SHF-OPEN',
        cashier_name: 'Kasir',
        is_open: true,
        opened_at: '08:00',
        opening_cash: 200000,
        drawer_cash: 200000,
    }),
    store_info: () => ({
        name: 'Andik Laundry',
        phone: '6281234567890',
        address: 'Jl. Raya Laundry No. 12, Jakarta',
        default_min_weight: 2.0,
        customer_credit_ceiling: 150000,
        paper_width: '58mm',
        receipt_footer: 'Terima kasih telah mencuci di Andik Laundry. Bersih, Wangi, Rapi!',
    }),
    default_customer: () => ({
        id: 1,
        name: 'Pelanggan Umum',
        phone: '',
        outstanding_receivables: 0,
    }),
    categories: () => ['Semua', 'Kiloan', 'Satuan', 'Karpet'],
    services: () => [
        { id: 1, name: 'Cuci Basah', category: 'Kiloan', price: 5000, unit: 'kg', min_weight: 2.0, estimated_hours: 24 },
        { id: 2, name: 'Cuci Kering', category: 'Kiloan', price: 6000, unit: 'kg', min_weight: 2.0, estimated_hours: 48 },
        { id: 3, name: 'Cuci Setrika', category: 'Kiloan', price: 8000, unit: 'kg', min_weight: 2.0, estimated_hours: 48 },
        { id: 4, name: 'Cuci Karpet', category: 'Karpet', price: 15000, unit: 'meter', min_weight: null, estimated_hours: 72, is_meter_service: true },
        { id: 5, name: 'Bedcover King (Besar)', category: 'Satuan', price: 25000, unit: 'pcs', min_weight: 1.0, estimated_hours: 48 },
        { id: 6, name: 'Bedcover Single (Kecil)', category: 'Satuan', price: 20000, unit: 'pcs', min_weight: 1.0, estimated_hours: 48 },
        { id: 7, name: 'Selimut / Sprei', category: 'Satuan', price: 15000, unit: 'pcs', min_weight: 1.0, estimated_hours: 48 },
        { id: 8, name: 'Boneka / Bantal', category: 'Satuan', price: 10000, unit: 'pcs', min_weight: 1.0, estimated_hours: 48 },
    ],
    quick_tenders: () => [10000, 20000, 50000, 100000],
    turnaround_tiers: () => [
        { code: 'reguler', label: 'Reguler (2-3 Hari)', surcharge: 0 },
        { code: 'ekspres', label: 'Ekspres (Pagi Ambil Sore)', surcharge: 2000 },
    ],
});

// Search & Catalog State
const searchQuery = ref('');
const selectedCategory = ref('Semua');
const searchInputRef = ref<HTMLInputElement | null>(null);

// Customer State
const currentCustomer = ref<CustomerData>(props.default_customer);
const isCustomerModalOpen = ref(false);

// Inspection Modal State for Satuan items
const isInspectionModalOpen = ref(false);
const inspectingService = ref<ServiceItem | null>(null);

// Cart State
const cartItems = ref<CartLineItem[]>([]);
const selectedTierCode = ref(props.turnaround_tiers[0]?.code ?? 'reguler');
const orderDiscount = ref<number>(0);
const deliveryOption = ref<'pickup' | 'delivery'>('pickup');

// Checkout & Receipt Modal State
const isCheckoutModalOpen = ref(false);
const isReceiptModalOpen = ref(false);
const completedTransaction = ref<any>(null);

// Filtered Catalog
const filteredServices = computed(() => {
    return props.services.filter(s => {
        const matchesCategory = selectedCategory.value === 'Semua' || s.category === selectedCategory.value;
        const matchesSearch = s.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            s.category.toLowerCase().includes(searchQuery.value.toLowerCase());
        return matchesCategory && matchesSearch;
    });
});

// Financial Computations
const currentTier = computed(() => {
    return props.turnaround_tiers.find(t => t.code === selectedTierCode.value) ?? props.turnaround_tiers[0];
});

const tierSurcharge = computed(() => currentTier.value?.surcharge ?? 0);

const subtotal = computed(() => {
    return cartItems.value.reduce((acc, item) => {
        return acc + (item.billed_qty * item.service.price);
    }, 0);
});

const grandTotal = computed(() => {
    return Math.max(0, (subtotal.value - orderDiscount.value) + tierSurcharge.value);
});

// Service selection handler
function onSelectService(service: ServiceItem) {
    const isKg = service.unit.toLowerCase() === 'kg';
    const isMeterService = (service as any).is_meter_service === true || service.unit.toLowerCase() === 'm²';
    
    if (!isKg && !isMeterService) {
        // Satuan / Pieces service: Open Inspection Modal
        inspectingService.value = service;
        isInspectionModalOpen.value = true;
    } else {
        // Kiloan or Meteran service: Add or increment in cart
        const defaultQty = isMeterService ? 1 : (service.min_weight ?? props.store_info.default_min_weight ?? 2.0);
        const existing = cartItems.value.find(item => item.service.id === service.id);

        if (existing) {
            existing.actual_qty = Number((existing.actual_qty + 1.0).toFixed(2));
            if (isKg) {
                const minWeight = existing.service.min_weight ?? props.store_info.default_min_weight ?? 2.0;
                existing.billed_qty = Math.max(existing.actual_qty, minWeight);
            } else {
                existing.billed_qty = existing.actual_qty;
            }
        } else {
            cartItems.value.push({
                service,
                actual_qty: defaultQty,
                billed_qty: isKg ? defaultQty : defaultQty,
                item_condition_notes: '',
                special_instructions: '',
                length: isMeterService ? 0 : undefined,
                width: isMeterService ? 0 : undefined,
                calculated_area: isMeterService ? 0 : undefined,
            });
        }
    }
}

// Satuan Inspection Confirm Handler
function onConfirmInspection(data: {
    service: ServiceItem;
    actual_qty: number;
    billed_qty: number;
    item_condition_notes: string;
    special_instructions: string;
}) {
    cartItems.value.push({
        service: data.service,
        actual_qty: data.actual_qty,
        billed_qty: data.billed_qty,
        item_condition_notes: data.item_condition_notes,
        special_instructions: data.special_instructions,
    });
}

// Cart updates
function onUpdateQty(index: number, newQty: number) {
    const item = cartItems.value[index];
    if (!item) return;
    item.actual_qty = newQty;
    const isKg = item.service.unit.toLowerCase() === 'kg';
    const minWeight = isKg ? (item.service.min_weight ?? props.store_info.default_min_weight ?? 2.0) : 0;
    item.billed_qty = Math.max(newQty, minWeight);
}

function onRemoveItem(index: number) {
    cartItems.value.splice(index, 1);
}

function onClearCart() {
    cartItems.value = [];
}

// Customer Selection Handler
function onSelectCustomer(cust: CustomerData) {
    currentCustomer.value = cust;
}

// Checkout Trigger
function openCheckoutModal() {
    if (cartItems.value.length === 0) return;
    isCheckoutModalOpen.value = true;
}

// Checkout Success Handler
function onCheckoutSuccess(transaction: any) {
    completedTransaction.value = transaction;
    isCheckoutModalOpen.value = false;
    isReceiptModalOpen.value = true;
}

// Reset POS for new transaction
function resetForNewTransaction() {
    cartItems.value = [];
    currentCustomer.value = props.default_customer;
    orderDiscount.value = 0;
    selectedTierCode.value = props.turnaround_tiers[0]?.code ?? 'reguler';
    completedTransaction.value = null;
    isReceiptModalOpen.value = false;
}

// Keyboard shortcuts
function handleKeyDown(e: KeyboardEvent) {
    if (e.key === 'F1') {
        e.preventDefault();
        searchInputRef.value?.focus();
    } else if (e.key === 'F8') {
        e.preventDefault();
        if (cartItems.value.length > 0 && !isCheckoutModalOpen.value && !isReceiptModalOpen.value) {
            openCheckoutModal();
        }
    } else if (e.key === 'Escape') {
        isCustomerModalOpen.value = false;
        isInspectionModalOpen.value = false;
        isCheckoutModalOpen.value = false;
    }
}

onMounted(() => {
    window.addEventListener('keydown', handleKeyDown);
});

onUnmounted(() => {
    window.removeEventListener('keydown', handleKeyDown);
});
</script>

<template>
    <PosLayout
        :cart-count="cartItems.length"
        :cart-total-formatted="formatRupiah(grandTotal)"
        :shift-info="active_shift"
        :store-name="store_info.name"
        @hotkey-search="searchInputRef?.focus()"
        @hotkey-checkout="openCheckoutModal"
    >
        <!-- LEFT PANEL: Catalog & Service Selection -->
        <template #catalog>
            <div class="space-y-4">
                <!-- Search Bar & Category Filter Tabs -->
                <div class="bg-white p-4 rounded-[12px] border-[3.5px] border-black shadow-[5px_5px_0px_#000] space-y-3">
                    <div class="relative">
                        <Search class="absolute left-3.5 top-1/2 -translate-y-1/2 h-5 w-5 text-black stroke-[2.5]" />
                        <input
                            ref="searchInputRef"
                            v-model="searchQuery"
                            type="text"
                            placeholder="Cari layanan cucian atau SKU barcode... [F1]"
                            class="flex min-h-[44px] w-full rounded-[8px] border-[2.5px] border-black bg-white pl-11 pr-4 py-2 text-sm font-bold text-black placeholder:text-black/40 shadow-[3px_3px_0px_#000] focus:outline-none focus:shadow-[4px_4px_0px_#000]"
                        />
                    </div>

                    <!-- Category Pills Horizontal Scroll -->
                    <div class="flex items-center space-x-2 overflow-x-auto pb-1 scrollbar-none">
                        <button
                            v-for="cat in categories"
                            :key="cat"
                            type="button"
                            @click="selectedCategory = cat"
                            :class="[
                                'min-h-[44px] px-4 py-2 rounded-[8px] text-xs font-black uppercase tracking-wider whitespace-nowrap transition-all border-2 border-black cursor-pointer active:translate-x-0.5 active:translate-y-0.5',
                                selectedCategory === cat
                                    ? 'bg-[#FFEB3B] text-black shadow-[3px_3px_0px_#000]'
                                    : 'bg-white text-black/70 hover:bg-[#FFF9D2]/40 hover:text-black shadow-[2px_2px_0px_#000]',
                            ]"
                        >
                            {{ cat }}
                        </button>
                    </div>
                </div>

                <!-- Catalog Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-3">
                    <ServiceCard
                        v-for="service in filteredServices"
                        :key="service.id"
                        :service="service"
                        @select="onSelectService"
                    />
                </div>
            </div>
        </template>

        <!-- RIGHT PANEL / BOTTOM SHEET: Customer Bar, Cart Drawer & Checkout Action -->
        <template #cart>
            <div class="flex flex-col h-full space-y-4">
                <!-- CUSTOMER SELECTOR CARD -->
                <div class="p-3.5 bg-[#FFF9D2] rounded-[10px] border-[2.5px] border-black shadow-[3px_3px_0px_#000] flex items-center justify-between">
                    <div class="flex items-center space-x-2.5">
                        <div class="h-9 w-9 rounded-[8px] bg-black text-[#FFEB3B] border-2 border-black flex items-center justify-center font-black text-xs shadow-[1px_1px_0px_#000] shrink-0">
                            <UserIcon class="h-4 w-4" />
                        </div>
                        <div>
                            <div class="flex items-center space-x-1.5">
                                <span class="font-black text-xs text-black">{{ currentCustomer.name }}</span>
                                <span
                                    v-if="Number(currentCustomer.outstanding_receivables ?? 0) > 0"
                                    class="inline-flex items-center space-x-0.5 px-1.5 py-0.5 rounded-[4px] text-[9px] font-black bg-[#FF5252] text-black border border-black shadow-[1px_1px_0px_#000]"
                                >
                                    <AlertCircle class="h-2.5 w-2.5 stroke-[3]" />
                                    <span>Tagihan</span>
                                </span>
                            </div>
                            <span class="text-[11px] font-mono font-bold text-black/70 block">
                                {{ currentCustomer.phone || 'Pelanggan Umum (Walk-In)' }}
                            </span>
                        </div>
                    </div>

                    <button
                        type="button"
                        @click="isCustomerModalOpen = true"
                        class="min-h-[44px] px-3.5 py-1.5 rounded-[8px] border-2 border-black bg-white hover:bg-[#FFEB3B] text-black text-xs font-black shadow-[2px_2px_0px_#000] active:translate-x-0.5 active:translate-y-0.5 active:shadow-none transition-all cursor-pointer"
                    >
                        Cari / Ubah
                    </button>
                </div>

                <!-- CART DRAWER (ITEMS, STEPS, TOTALS) -->
                <CartDrawer
                    :items="cartItems"
                    :turnaround-tiers="turnaround_tiers"
                    :selected-tier-code="selectedTierCode"
                    :default-min-weight="store_info.default_min_weight"
                    :order-discount="orderDiscount"
                    :delivery-option="deliveryOption"
                    @update:selected-tier-code="selectedTierCode = $event"
                    @update:order-discount="orderDiscount = $event"
                    @update:delivery-option="deliveryOption = $event"
                    @update-qty="onUpdateQty"
                    @remove-item="onRemoveItem"
                    @clear-cart="onClearCart"
                />

                <!-- CHECKOUT BUTTON -->
                <div class="pt-2">
                    <button
                        type="button"
                        :disabled="cartItems.length === 0"
                        @click="openCheckoutModal"
                        class="w-full min-h-[50px] rounded-[8px] border-[3px] border-black bg-[#22C55E] hover:bg-[#16A34A] text-black font-black text-sm uppercase tracking-wider shadow-[4px_4px_0px_#000] active:translate-x-0.5 active:translate-y-0.5 active:shadow-[2px_2px_0px_#000] disabled:opacity-40 disabled:cursor-not-allowed flex items-center justify-center space-x-2 transition-all cursor-pointer"
                    >
                        <CreditCard class="h-5 w-5" />
                        <span>PROSES PEMBAYARAN [F8] ({{ formatRupiah(grandTotal) }})</span>
                    </button>
                </div>
            </div>
        </template>
    </PosLayout>

    <!-- CUSTOMER LOOKUP & CREATE MODAL -->
    <CustomerLookupModal
        v-model:is-open="isCustomerModalOpen"
        :current-customer="currentCustomer"
        :default-customer="props.default_customer"
        :credit-ceiling="store_info.customer_credit_ceiling"
        @select="onSelectCustomer"
    />

    <!-- SATUAN INSPECTION MODAL -->
    <SatuanInspectionModal
        v-model:is-open="isInspectionModalOpen"
        :service="inspectingService"
        @confirm="onConfirmInspection"
    />

    <!-- CHECKOUT / PAYMENT MODAL -->
    <CheckoutPaymentModal
        v-model:is-open="isCheckoutModalOpen"
        :grand-total="grandTotal"
        :subtotal="subtotal"
        :tier-surcharge="tierSurcharge"
        :order-discount="orderDiscount"
        :customer="currentCustomer"
        :cart-items="cartItems"
        :turnaround-tier="currentTier"
        :credit-ceiling="store_info.customer_credit_ceiling"
        :quick-tenders="quick_tenders"
        :delivery-option="deliveryOption"
        @checkout-success="onCheckoutSuccess"
    />

    <!-- THERMAL RECEIPT MODAL -->
    <ThermalReceipt
        v-model:is-open="isReceiptModalOpen"
        :transaction="completedTransaction"
        :store-info="store_info"
        @new-transaction="resetForNewTransaction"
    />
</template>
