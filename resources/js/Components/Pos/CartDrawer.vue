<script setup lang="ts">
import { computed } from 'vue';
import { Plus, Minus, Trash2, Scale, AlertTriangle, Clock, Truck, MapPin } from 'lucide-vue-next';
import { formatRupiah } from '@/Support/whatsapp';
import type { ServiceItem } from './ServiceCard.vue';

export interface CartLineItem {
    service: ServiceItem;
    actual_qty: number;
    billed_qty: number;
    perfume?: string;
    item_condition_notes?: string;
    special_instructions?: string;
    discount_amount?: number;
    length?: number;
    width?: number;
    calculated_area?: number;
}

export interface TurnaroundTier {
    code: string;
    label: string;
    surcharge: number;
}

interface Props {
    items: CartLineItem[];
    turnaroundTiers: TurnaroundTier[];
    selectedTierCode: string;
    defaultMinWeight?: number;
    orderDiscount?: number;
    deliveryOption?: 'pickup' | 'delivery';
}

const props = withDefaults(defineProps<Props>(), {
    items: () => [],
    turnaroundTiers: () => [],
    selectedTierCode: 'reguler',
    defaultMinWeight: 2.0,
    orderDiscount: 0,
    deliveryOption: 'pickup',
});

const emits = defineEmits<{
    (e: 'update:selectedTierCode', code: string): void;
    (e: 'update:orderDiscount', discount: number): void;
    (e: 'update:deliveryOption', option: 'pickup' | 'delivery'): void;
    (e: 'update-qty', index: number, newQty: number): void;
    (e: 'remove-item', index: number): void;
    (e: 'clear-cart'): void;
}>();

const currentTier = computed(() => {
    return props.turnaroundTiers.find(t => t.code === props.selectedTierCode) ?? props.turnaroundTiers[0];
});

const tierSurcharge = computed(() => currentTier.value?.surcharge ?? 0);

const subtotal = computed(() => {
    return props.items.reduce((acc, item) => {
        const itemDiscount = item.discount_amount ?? 0;
        const qty = item.calculated_area ? item.calculated_area : item.billed_qty;
        return acc + Math.max(0, (qty * item.service.price) - itemDiscount);
    }, 0);
});

const discountTotal = computed(() => Math.min(subtotal.value, Number(props.orderDiscount || 0)));

const grandTotal = computed(() => {
    return Math.max(0, (subtotal.value - discountTotal.value) + tierSurcharge.value);
});

function handleQtyStep(index: number, delta: number) {
    const item = props.items[index];
    if (!item) return;
    const isKg = item.service.unit.toLowerCase() === 'kg';
    const isMeterService = item.service.unit.toLowerCase() === 'm²' || (item.service as any).is_meter_service === true;
    const step = isKg ? 0.5 : (isMeterService ? 0.5 : 1);
    const nextQty = Math.max(0.1, Number((item.actual_qty + (delta * step)).toFixed(2)));
    emits('update-qty', index, nextQty);
}

function handleDirectQtyChange(index: number, event: Event) {
    const val = parseFloat((event.target as HTMLInputElement).value);
    if (!isNaN(val) && val > 0) {
        emits('update-qty', index, Math.max(0.01, Number(val.toFixed(2))));
    }
}

function calculateArea(item: CartLineItem) {
    if (item.length && item.width && item.length > 0 && item.width > 0) {
        item.calculated_area = Number((item.length * item.width).toFixed(2));
        item.billed_qty = item.calculated_area;
        item.actual_qty = item.calculated_area;
    }
}

function isKarpetService(item: CartLineItem): boolean {
    return item.service.unit.toLowerCase() === 'm²' || item.service.name.toLowerCase().includes('karpet');
}
</script>

<template>
    <div class="space-y-3 pb-1">
        <!-- Cart Items List Header -->
        <div class="flex items-center justify-between">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-500 flex items-center space-x-1.5">
                <Scale class="h-4 w-4 text-slate-500" />
                <span>Daftar Cucian ({{ items.length }} Item)</span>
            </span>
            <button
                v-if="items.length > 0"
                type="button"
                @click="emits('clear-cart')"
                class="text-xs font-semibold text-red-500 hover:text-red-700 hover:underline transition-colors min-h-[36px] inline-flex items-center cursor-pointer"
            >
                Kosongkan
            </button>
        </div>

        <!-- Items Container -->
        <div class="space-y-2.5">
            <!-- Empty State -->
            <div
                v-if="items.length === 0"
                class="py-6 px-4 flex flex-col items-center justify-center text-slate-500 text-xs text-center border-2 border-dashed border-slate-200 rounded-2xl bg-slate-50/50"
            >
                <div class="h-10 w-10 rounded-full bg-[#E6E9FD] text-[#4F5FE8] flex items-center justify-center mb-2">
                    <Scale class="h-5 w-5" />
                </div>
                <span class="font-bold text-slate-800 text-xs">Keranjang cucian masih kosong</span>
                <span class="text-[11px] text-slate-500 mt-0.5">Pilih layanan di sebelah kiri untuk mulai menimbang / mencatat</span>
            </div>

            <!-- Item Cards -->
            <div
                v-for="(item, idx) in items"
                :key="`${item.service.id}-${idx}`"
                class="p-3.5 bg-white rounded-2xl border border-slate-200/90 shadow-xs space-y-2.5"
            >
                <div class="flex items-start justify-between gap-2">
                    <div class="space-y-1">
                        <span class="font-heading font-bold text-xs sm:text-sm text-slate-900 block leading-tight">{{ item.service.name }}</span>
                        <div class="flex items-center space-x-2 text-[11px] text-slate-500">
                            <span class="font-mono font-bold text-slate-700">{{ formatRupiah(item.service.price) }}/{{ item.service.unit }}</span>
                        </div>

                        <!-- Defect notes alert pill if any -->
                        <div
                            v-if="item.item_condition_notes"
                            class="inline-flex items-center space-x-1 px-2 py-0.5 rounded-full text-[10px] bg-red-50 text-red-700 border border-red-200 font-semibold"
                        >
                            <AlertTriangle class="h-3 w-3 shrink-0" />
                            <span class="truncate max-w-[200px]">{{ item.item_condition_notes }}</span>
                        </div>
                    </div>

                    <!-- Remove Button -->
                    <button
                        type="button"
                        @click="emits('remove-item', idx)"
                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 hover:text-red-600 hover:bg-red-50 transition-all cursor-pointer"
                        aria-label="Hapus item cucian"
                    >
                        <Trash2 class="h-4 w-4" />
                    </button>
                </div>

                <!-- Meter Input for Karpet Service -->
                <div v-if="isKarpetService(item)" class="p-3 bg-amber-50/70 rounded-xl border border-amber-200/80 space-y-2">
                    <div class="flex items-center space-x-1.5">
                        <MapPin class="h-3.5 w-3.5 text-amber-800" />
                        <span class="text-xs font-bold text-amber-900">Ukuran Karpet</span>
                    </div>
                    <div class="flex gap-2">
                        <div class="flex-1">
                            <label class="text-[10px] text-amber-800 font-bold uppercase tracking-wider block mb-0.5">Panjang (m)</label>
                            <input
                                type="number"
                                step="0.1"
                                min="0"
                                :value="item.length || 0"
                                @input="(e) => { item.length = parseFloat((e.target as HTMLInputElement).value) || 0; calculateArea(item); }"
                                class="w-full h-9 rounded-lg border border-amber-200 bg-white px-2.5 py-1 text-xs font-mono font-bold text-slate-900 focus:outline-none focus:ring-2 focus:ring-amber-400"
                                placeholder="0.0"
                            />
                        </div>
                        <div class="flex-1">
                            <label class="text-[10px] text-amber-800 font-bold uppercase tracking-wider block mb-0.5">Lebar (m)</label>
                            <input
                                type="number"
                                step="0.1"
                                min="0"
                                :value="item.width || 0"
                                @input="(e) => { item.width = parseFloat((e.target as HTMLInputElement).value) || 0; calculateArea(item); }"
                                class="w-full h-9 rounded-lg border border-amber-200 bg-white px-2.5 py-1 text-xs font-mono font-bold text-slate-900 focus:outline-none focus:ring-2 focus:ring-amber-400"
                                placeholder="0.0"
                            />
                        </div>
                    </div>
                    <div class="flex justify-between items-center pt-1 text-xs font-bold text-amber-900">
                        <span>Luas: {{ item.calculated_area || 0 }} m²</span>
                        <span v-if="item.calculated_area > 0" class="font-mono text-[#0F172A]">
                            {{ formatRupiah((item.calculated_area || 0) * item.service.price) }}
                        </span>
                    </div>
                </div>

                <!-- Weight / Quantity Controls & Billed calculation (for non-karpet services) -->
                <div v-else class="flex items-center justify-between pt-2 border-t border-slate-100">
                    <div class="flex items-center space-x-1.5">
                        <button
                            type="button"
                            @click="handleQtyStep(idx, -1)"
                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-slate-50 text-slate-700 hover:bg-slate-100 transition-all cursor-pointer shadow-xs"
                            aria-label="Kurangi berat / jumlah"
                        >
                            <Minus class="h-3.5 w-3.5" />
                        </button>

                        <div class="flex items-center bg-white border border-slate-200 rounded-lg px-2.5 py-1 h-8 shadow-xs">
                            <input
                                type="number"
                                step="0.1"
                                min="0.01"
                                :value="item.actual_qty"
                                @change="handleDirectQtyChange(idx, $event)"
                                class="w-12 text-center font-mono font-bold text-xs text-slate-900 bg-transparent focus:outline-none"
                            />
                            <span class="text-[11px] font-medium text-slate-500 ml-0.5">{{ item.service.unit }}</span>
                        </div>

                        <button
                            type="button"
                            @click="handleQtyStep(idx, 1)"
                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-slate-50 text-slate-700 hover:bg-slate-100 transition-all cursor-pointer shadow-xs"
                            aria-label="Tambah berat / jumlah"
                        >
                            <Plus class="h-3.5 w-3.5" />
                        </button>
                    </div>

                    <div class="text-right">
                        <div class="font-mono font-bold text-xs sm:text-sm text-slate-900">
                            {{ formatRupiah((item.calculated_area || item.billed_qty) * item.service.price) }}
                        </div>
                        <span
                            v-if="item.billed_qty > item.actual_qty"
                            class="text-[10px] text-red-600 font-semibold block"
                        >
                            Ditagih: {{ item.billed_qty }} {{ item.service.unit }} (Min {{ item.service.min_weight }} kg)
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delivery Option Selector -->
        <div class="p-3.5 bg-white rounded-2xl border border-slate-200/90 shadow-xs space-y-2">
            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500 flex items-center space-x-1.5">
                <Truck class="h-3.5 w-3.5 text-slate-600" />
                <span>Pilihan Pengantaran</span>
            </span>
            <div class="grid grid-cols-2 gap-2">
                <button
                    type="button"
                    @click="emits('update:deliveryOption', 'pickup')"
                    :class="[
                        'min-h-[42px] p-2.5 text-left rounded-xl border text-xs transition-all cursor-pointer',
                        deliveryOption === 'pickup'
                            ? 'bg-[#E6E9FD] text-[#26338A] border-[#4F5FE8] font-bold shadow-xs'
                            : 'bg-slate-50 text-slate-600 border-slate-200 hover:bg-slate-100 font-medium',
                    ]"
                >
                    <span class="block truncate text-xs">Ambil Sendiri</span>
                    <span class="block text-[10px] opacity-75 font-normal mt-0.5">Datang ke lokasi</span>
                </button>
                <button
                    type="button"
                    @click="emits('update:deliveryOption', 'delivery')"
                    :class="[
                        'min-h-[42px] p-2.5 text-left rounded-xl border text-xs transition-all cursor-pointer',
                        deliveryOption === 'delivery'
                            ? 'bg-[#E6E9FD] text-[#26338A] border-[#4F5FE8] font-bold shadow-xs'
                            : 'bg-slate-50 text-slate-600 border-slate-200 hover:bg-slate-100 font-medium',
                    ]"
                >
                    <span class="block truncate text-xs">Antar Jemput</span>
                    <span class="block text-[10px] opacity-75 font-normal mt-0.5">Free Ongkir Maks 3 kg</span>
                </button>
            </div>
        </div>

        <!-- Turnaround Tier Selector (Reguler / Ekspres) -->
        <div class="p-3.5 bg-white rounded-2xl border border-slate-200/90 shadow-xs space-y-2">
            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500 flex items-center space-x-1.5">
                <Clock class="h-3.5 w-3.5 text-slate-600" />
                <span>Kecepatan Pengerjaan</span>
            </span>
            <div class="grid grid-cols-2 gap-2">
                <button
                    v-for="tier in turnaroundTiers"
                    :key="tier.code"
                    type="button"
                    @click="emits('update:selectedTierCode', tier.code)"
                    :class="[
                        'min-h-[42px] p-2.5 text-left rounded-xl border text-xs transition-all cursor-pointer',
                        selectedTierCode === tier.code
                            ? 'bg-[#E6E9FD] text-[#26338A] border-[#4F5FE8] font-bold shadow-xs'
                            : 'bg-slate-50 text-slate-600 border-slate-200 hover:bg-slate-100 font-medium',
                    ]"
                >
                    <span class="block truncate text-xs">{{ tier.label }}</span>
                    <span class="block text-[10px] opacity-75 font-mono mt-0.5">
                        {{ tier.surcharge > 0 ? `+${formatRupiah(tier.surcharge)}` : 'Tarif Standar' }}
                    </span>
                </button>
            </div>
        </div>

        <!-- Calculation Ledger Summary -->
        <div class="p-4 bg-gradient-to-br from-[#10194D] to-[#1A2566] text-white rounded-2xl shadow-sm space-y-2 select-none">
            <div class="flex justify-between text-xs text-slate-300">
                <span>Subtotal Cucian</span>
                <span class="font-mono font-bold text-white">{{ formatRupiah(subtotal) }}</span>
            </div>

            <div v-if="tierSurcharge > 0" class="flex justify-between text-xs text-[#38BDF8]">
                <span>Biaya Kecepatan ({{ currentTier.label.split(' ')[0] }})</span>
                <span class="font-mono font-bold">+{{ formatRupiah(tierSurcharge) }}</span>
            </div>

            <div v-if="discountTotal > 0" class="flex justify-between text-xs text-rose-300">
                <span>Potongan Diskon</span>
                <span class="font-mono font-bold">-{{ formatRupiah(discountTotal) }}</span>
            </div>

            <div class="pt-2.5 border-t border-white/15 flex justify-between items-baseline">
                <span class="font-bold text-xs uppercase tracking-wider text-slate-300">Total Tagihan</span>
                <span class="font-heading font-extrabold text-2xl text-[#FFE14D]">{{ formatRupiah(grandTotal) }}</span>
            </div>
        </div>
    </div>
</template>
