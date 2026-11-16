<script setup lang="ts">
import { computed } from 'vue';
import { Plus, Minus, Trash2, Scale, AlertTriangle, Sparkles, Clock, Percent, Truck, MapPin } from 'lucide-vue-next';
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
    <div class="flex flex-col h-full space-y-4">
        <!-- Cart Items List Header -->
        <div class="flex items-center justify-between">
            <span class="text-xs font-bold uppercase tracking-wider text-[#6B6B6B] flex items-center space-x-1.5">
                <Scale class="h-4 w-4 text-[#6B6B6B]" />
                <span>Daftar Cucian ({{ items.length }} Item)</span>
            </span>
            <button
                v-if="items.length > 0"
                type="button"
                @click="emits('clear-cart')"
                class="text-[11px] font-semibold text-[#EF4444] hover:underline transition-colors min-h-[44px] inline-flex items-center"
            >
                Kosongkan
            </button>
        </div>

        <!-- Scrollable Items Container -->
        <div class="flex-1 overflow-y-auto space-y-2.5 pr-1 min-h-[140px] max-h-[300px] lg:max-h-[340px]">
            <!-- Empty State -->
            <div
                v-if="items.length === 0"
                class="h-36 flex flex-col items-center justify-center text-black/60 text-xs text-center border-[2.5px] border-dashed border-black rounded-[10px] p-4 bg-[#FFF9D2]/30"
            >
                <Scale class="h-8 w-8 text-black/40 mb-2 stroke-[2.5]" />
                <span class="font-black text-black">Keranjang cucian masih kosong</span>
                <span class="text-[11px] font-bold text-black/60 mt-0.5">Pilih layanan di sebelah kiri untuk mulai menimbang / mencatat</span>
            </div>

            <!-- Item Cards -->
            <div
                v-for="(item, idx) in items"
                :key="`${item.service.id}-${idx}`"
                class="p-3 bg-white rounded-[10px] border-[2px] border-black shadow-[3px_3px_0px_#000] space-y-2.5"
            >
                <div class="flex items-start justify-between">
                    <div class="space-y-1">
                        <span class="font-black text-xs text-black block">{{ item.service.name }}</span>
                        <div class="flex items-center space-x-2 text-[11px] text-black/70">
                            <span class="font-mono font-bold">{{ formatRupiah(item.service.price) }}/{{ item.service.unit }}</span>
                        </div>

                        <!-- Defect notes alert pill if any -->
                        <div
                            v-if="item.item_condition_notes"
                            class="inline-flex items-center space-x-1 px-2 py-0.5 rounded-[4px] text-[10px] bg-[#FF5252]/20 text-black border border-black font-black"
                        >
                            <AlertTriangle class="h-3 w-3 shrink-0 text-black" />
                            <span class="truncate max-w-[200px]">{{ item.item_condition_notes }}</span>
                        </div>
                    </div>

                    <!-- Remove Button -->
                    <button
                        type="button"
                        @click="emits('remove-item', idx)"
                        class="inline-flex min-h-[44px] min-w-[44px] items-center justify-center rounded-[6px] border border-black/30 text-[#FF5252] hover:bg-[#FF5252]/20 hover:border-black active:translate-x-0.5 active:translate-y-0.5 transition-all"
                        aria-label="Hapus item cucian"
                    >
                        <Trash2 class="h-4 w-4" />
                    </button>
                </div>

                <!-- Meter Input for Karpet Service -->
                <div v-if="isKarpetService(item)" class="meter-input p-3 bg-[#FFF9D2] rounded-[8px] border-[2px] border-black space-y-2 shadow-[2px_2px_0px_#000]">
                    <div class="flex items-center space-x-2">
                        <MapPin class="h-4 w-4 text-black" />
                        <span class="text-xs font-black text-black">Input Ukuran Karpet</span>
                    </div>
                    <div class="flex gap-2">
                        <div class="flex-1">
                            <label class="text-[10px] text-black font-black uppercase tracking-wider block">Panjang (m)</label>
                            <input
                                type="number"
                                step="0.1"
                                min="0"
                                :value="item.length || 0"
                                @input="(e) => { item.length = parseFloat((e.target as HTMLInputElement).value) || 0; calculateArea(item); }"
                                class="w-full min-h-[44px] rounded-[6px] border-2 border-black bg-white px-2.5 py-1 text-xs font-mono font-bold text-black shadow-[1px_1px_0px_#000] focus:outline-none"
                                placeholder="0.0"
                            />
                        </div>
                        <div class="flex-1">
                            <label class="text-[10px] text-black font-black uppercase tracking-wider block">Lebar (m)</label>
                            <input
                                type="number"
                                step="0.1"
                                min="0"
                                :value="item.width || 0"
                                @input="(e) => { item.width = parseFloat((e.target as HTMLInputElement).value) || 0; calculateArea(item); }"
                                class="w-full min-h-[44px] rounded-[6px] border-2 border-black bg-white px-2.5 py-1 text-xs font-mono font-bold text-black shadow-[1px_1px_0px_#000] focus:outline-none"
                                placeholder="0.0"
                            />
                        </div>
                    </div>
                    <div class="text-center font-bold">
                        <span class="text-xs font-black text-black">
                            Luas: {{ item.calculated_area || 0 }} m²
                        </span>
                        <span v-if="item.calculated_area > 0" class="text-[10px] text-black ml-2 font-mono font-black">
                            → {{ formatRupiah((item.calculated_area || 0) * item.service.price) }}
                        </span>
                    </div>
                </div>

                <!-- Weight / Quantity Controls & Billed calculation (for non-karpet services) -->
                <div v-else class="flex items-center justify-between pt-2 border-t-2 border-black/10">
                    <div class="flex items-center space-x-1.5">
                        <button
                            type="button"
                            @click="handleQtyStep(idx, -1)"
                            class="inline-flex min-h-[44px] min-w-[44px] items-center justify-center rounded-[6px] border-2 border-black bg-white text-black hover:bg-[#FFEB3B] active:translate-x-0.5 active:translate-y-0.5 shadow-[1px_1px_0px_#000]"
                            aria-label="Kurangi berat / jumlah"
                        >
                            <Minus class="h-4 w-4 stroke-[3]" />
                        </button>

                        <div class="flex items-center bg-white border-2 border-black rounded-[6px] px-2 py-1 min-h-[44px] shadow-[1px_1px_0px_#000]">
                            <input
                                type="number"
                                step="0.1"
                                min="0.01"
                                :value="item.actual_qty"
                                @change="handleDirectQtyChange(idx, $event)"
                                class="w-14 text-center font-mono font-black text-xs text-black bg-transparent focus:outline-none"
                            />
                            <span class="text-[11px] font-black text-black/70 ml-0.5">{{ item.service.unit }}</span>
                        </div>

                        <button
                            type="button"
                            @click="handleQtyStep(idx, 1)"
                            class="inline-flex min-h-[44px] min-w-[44px] items-center justify-center rounded-[6px] border-2 border-black bg-white text-black hover:bg-[#FFEB3B] active:translate-x-0.5 active:translate-y-0.5 shadow-[1px_1px_0px_#000]"
                            aria-label="Tambah berat / jumlah"
                        >
                            <Plus class="h-4 w-4 stroke-[3]" />
                        </button>
                    </div>

                    <div class="text-right">
                        <div class="font-mono font-black text-xs text-black">
                            {{ formatRupiah((item.calculated_area || item.billed_qty) * item.service.price) }}
                        </div>
                        <span
                            v-if="item.billed_qty > item.actual_qty"
                            class="text-[10px] text-[#FF5252] font-black block"
                        >
                            Ditagih: {{ item.billed_qty }} {{ item.service.unit }} (Min {{ item.service.min_weight }} kg)
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delivery Option Selector -->
        <div class="p-3.5 bg-white rounded-[10px] border-[2.5px] border-black shadow-[3px_3px_0px_#000] space-y-2">
            <span class="text-[11px] font-black uppercase tracking-wider text-black block flex items-center space-x-1.5">
                <Truck class="h-3.5 w-3.5 text-black" />
                <span>Pilihan Pengantaran</span>
            </span>
            <div class="grid grid-cols-2 gap-2">
                <button
                    type="button"
                    @click="emits('update:deliveryOption', 'pickup')"
                    :class="[
                        'min-h-[44px] p-2.5 text-left rounded-[8px] border-2 border-black text-xs font-black transition-all cursor-pointer active:translate-x-0.5 active:translate-y-0.5',
                        deliveryOption === 'pickup'
                            ? 'bg-[#FFEB3B] text-black shadow-[2px_2px_0px_#000]'
                            : 'bg-white text-black/70 hover:bg-[#FFF9D2]/30',
                    ]"
                >
                    <span class="block truncate font-black">Ambil Sendiri</span>
                    <span class="block text-[10px] opacity-80 font-bold">Datang ke lokasi</span>
                </button>
                <button
                    type="button"
                    @click="emits('update:deliveryOption', 'delivery')"
                    :class="[
                        'min-h-[44px] p-2.5 text-left rounded-[8px] border-2 border-black text-xs font-black transition-all cursor-pointer active:translate-x-0.5 active:translate-y-0.5',
                        deliveryOption === 'delivery'
                            ? 'bg-[#FFEB3B] text-black shadow-[2px_2px_0px_#000]'
                            : 'bg-white text-black/70 hover:bg-[#FFF9D2]/30',
                    ]"
                >
                    <span class="block truncate font-black">Antar Jemput</span>
                    <span class="block text-[10px] opacity-80 font-bold">Gratis &lt; 3 km</span>
                </button>
            </div>
        </div>

        <!-- Turnaround Tier Selector (Reguler / Ekspres) -->
        <div class="p-3.5 bg-white rounded-[10px] border-[2.5px] border-black shadow-[3px_3px_0px_#000] space-y-2">
            <span class="text-[11px] font-black uppercase tracking-wider text-black block flex items-center space-x-1.5">
                <Clock class="h-3.5 w-3.5 text-black" />
                <span>Kecepatan Pengerjaan</span>
            </span>
            <div class="grid grid-cols-2 gap-2">
                <button
                    v-for="tier in turnaroundTiers"
                    :key="tier.code"
                    type="button"
                    @click="emits('update:selectedTierCode', tier.code)"
                    :class="[
                        'min-h-[44px] p-2.5 text-left rounded-[8px] border-2 border-black text-xs font-black transition-all cursor-pointer active:translate-x-0.5 active:translate-y-0.5',
                        selectedTierCode === tier.code
                            ? 'bg-[#FFEB3B] text-black shadow-[2px_2px_0px_#000]'
                            : 'bg-white text-black/70 hover:bg-[#FFF9D2]/30',
                    ]"
                >
                    <span class="block truncate font-black">{{ tier.label }}</span>
                    <span class="block text-[10px] opacity-80 font-mono font-bold">
                        {{ tier.surcharge > 0 ? `+${formatRupiah(tier.surcharge)}` : 'Tarif Standar' }}
                    </span>
                </button>
            </div>
        </div>

        <!-- Calculation Ledger Summary -->
        <div class="p-4 bg-[#FFEB3B] text-black rounded-[10px] border-[2.5px] border-black space-y-2 select-none shadow-[4px_4px_0px_#000]">
            <div class="flex justify-between text-xs font-bold text-black/70">
                <span>Subtotal Cucian</span>
                <span class="font-mono font-black text-black">{{ formatRupiah(subtotal) }}</span>
            </div>

            <div v-if="tierSurcharge > 0" class="flex justify-between text-xs font-bold text-[#2196F3]">
                <span>Biaya Kecepatan ({{ currentTier.label.split(' ')[0] }})</span>
                <span class="font-mono font-black">+{{ formatRupiah(tierSurcharge) }}</span>
            </div>

            <div v-if="discountTotal > 0" class="flex justify-between text-xs font-bold text-[#FF5252]">
                <span>Potongan Diskon</span>
                <span class="font-mono font-black">-{{ formatRupiah(discountTotal) }}</span>
            </div>

            <div class="pt-2.5 border-t-2 border-black flex justify-between items-baseline">
                <span class="font-black text-xs uppercase tracking-wider text-black">Total Tagihan</span>
                <span class="font-mono font-black text-2xl text-black">{{ formatRupiah(grandTotal) }}</span>
            </div>
        </div>
    </div>
</template>
