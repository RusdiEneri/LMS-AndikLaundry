<script setup lang="ts">
import { computed } from 'vue';
import { Plus, Minus, Trash2, Scale, AlertTriangle, Sparkles, Clock, Percent } from 'lucide-vue-next';
import { formatRupiah } from '@/Support/whatsapp';
import type { ServiceItem } from './ServiceCard.vue';

export interface CartLineItem {
    service: ServiceItem;
    actual_qty: number;
    billed_qty: number;
    perfume: string;
    item_condition_notes?: string;
    special_instructions?: string;
    discount_amount?: number;
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
    perfumeVariants: string[];
    defaultMinWeight?: number;
    orderDiscount?: number;
}

const props = withDefaults(defineProps<Props>(), {
    items: () => [],
    turnaroundTiers: () => [],
    selectedTierCode: 'reguler',
    perfumeVariants: () => ['Lavender', 'Sakura', 'Ocean Fresh', 'Non-Parfum'],
    defaultMinWeight: 3.0,
    orderDiscount: 0,
});

const emits = defineEmits<{
    (e: 'update:selectedTierCode', code: string): void;
    (e: 'update:orderDiscount', discount: number): void;
    (e: 'update-qty', index: number, newQty: number): void;
    (e: 'update-perfume', index: number, perfume: string): void;
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
        return acc + Math.max(0, (item.billed_qty * item.service.price) - itemDiscount);
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
    const step = isKg ? 0.5 : 1;
    const nextQty = Math.max(0.1, Number((item.actual_qty + (delta * step)).toFixed(2)));
    emits('update-qty', index, nextQty);
}

function handleDirectQtyChange(index: number, event: Event) {
    const val = parseFloat((event.target as HTMLInputElement).value);
    if (!isNaN(val) && val > 0) {
        emits('update-qty', index, Math.max(0.01, Number(val.toFixed(2))));
    }
}
</script>

<template>
    <div class="flex flex-col h-full space-y-4">
        <!-- Cart Items List Header -->
        <div class="flex items-center justify-between">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-500 flex items-center space-x-1.5">
                <Scale class="h-4 w-4 text-slate-400" />
                <span>Daftar Cucian ({{ items.length }} Item)</span>
            </span>
            <button
                v-if="items.length > 0"
                type="button"
                @click="emits('clear-cart')"
                class="text-[11px] font-semibold text-rose-600 hover:text-rose-800 transition-colors"
            >
                Kosongkan
            </button>
        </div>

        <!-- Scrollable Items Container -->
        <div class="flex-1 overflow-y-auto space-y-2.5 pr-1 min-h-[140px] max-h-[300px] lg:max-h-[340px]">
            <!-- Empty State -->
            <div
                v-if="items.length === 0"
                class="h-36 flex flex-col items-center justify-center text-slate-400 text-xs text-center border-2 border-dashed border-slate-200 rounded-xl p-4 bg-slate-50/50"
            >
                <Scale class="h-8 w-8 text-slate-300 mb-2" />
                <span class="font-bold text-slate-600">Keranjang cucian masih kosong</span>
                <span class="text-[11px] text-slate-400 mt-0.5">Pilih layanan di sebelah kiri untuk mulai menimbang / mencatat</span>
            </div>

            <!-- Item Cards -->
            <div
                v-for="(item, idx) in items"
                :key="`${item.service.id}-${idx}`"
                class="p-3 bg-white rounded-xl border border-slate-200 shadow-2xs space-y-2.5 hover:border-slate-300 transition-colors"
            >
                <div class="flex items-start justify-between">
                    <div class="space-y-1">
                        <span class="font-bold text-xs text-slate-900 block">{{ item.service.name }}</span>
                        <div class="flex items-center space-x-2 text-[11px] text-slate-500">
                            <span class="font-mono">{{ formatRupiah(item.service.price) }}/{{ item.service.unit }}</span>
                            <span>•</span>
                            <!-- Perfume selector -->
                            <select
                                :value="item.perfume"
                                @change="emits('update-perfume', idx, ($event.target as HTMLSelectElement).value)"
                                class="text-[11px] font-semibold bg-slate-50 border border-slate-200 rounded-md px-2 py-1 text-slate-700 focus:outline-none focus:ring-1 focus:ring-teal-500"
                                aria-label="Pilih varian parfum"
                            >
                                <option v-for="p in perfumeVariants" :key="p" :value="p">{{ p }}</option>
                            </select>
                        </div>

                        <!-- Defect notes alert pill if any -->
                        <div
                            v-if="item.item_condition_notes"
                            class="inline-flex items-center space-x-1 px-2 py-0.5 rounded-md text-[10px] bg-amber-50 text-amber-900 border border-amber-200 font-medium"
                        >
                            <AlertTriangle class="h-3 w-3 shrink-0 text-amber-600" />
                            <span class="truncate max-w-[200px]">{{ item.item_condition_notes }}</span>
                        </div>
                    </div>

                    <!-- Remove Button -->
                    <button
                        type="button"
                        @click="emits('remove-item', idx)"
                        class="inline-flex min-h-[36px] min-w-[36px] items-center justify-center rounded-lg p-1.5 text-rose-500 hover:bg-rose-50 hover:text-rose-700 focus:outline-none transition-colors"
                        aria-label="Hapus item cucian"
                    >
                        <Trash2 class="h-4 w-4" />
                    </button>
                </div>

                <!-- Weight / Quantity Controls & Billed calculation -->
                <div class="flex items-center justify-between pt-2 border-t border-slate-100">
                    <div class="flex items-center space-x-1.5">
                        <button
                            type="button"
                            @click="handleQtyStep(idx, -1)"
                            class="inline-flex min-h-[36px] min-w-[36px] items-center justify-center rounded-lg border border-slate-200 bg-slate-50 text-slate-700 hover:bg-slate-100 focus:outline-none active:scale-95"
                            aria-label="Kurangi berat / jumlah"
                        >
                            <Minus class="h-3.5 w-3.5" />
                        </button>

                        <div class="flex items-center bg-slate-50 border border-slate-200 rounded-lg px-2 py-1">
                            <input
                                type="number"
                                step="0.1"
                                min="0.01"
                                :value="item.actual_qty"
                                @change="handleDirectQtyChange(idx, $event)"
                                class="w-14 text-center font-mono font-bold text-xs text-slate-900 bg-transparent focus:outline-none"
                            />
                            <span class="text-[11px] font-semibold text-slate-500 ml-0.5">{{ item.service.unit }}</span>
                        </div>

                        <button
                            type="button"
                            @click="handleQtyStep(idx, 1)"
                            class="inline-flex min-h-[36px] min-w-[36px] items-center justify-center rounded-lg border border-slate-200 bg-slate-50 text-slate-700 hover:bg-slate-100 focus:outline-none active:scale-95"
                            aria-label="Tambah berat / jumlah"
                        >
                            <Plus class="h-3.5 w-3.5" />
                        </button>
                    </div>

                    <div class="text-right">
                        <div class="font-mono font-bold text-xs text-slate-900">
                            {{ formatRupiah(item.billed_qty * item.service.price) }}
                        </div>
                        <!-- Minimum weight notice if actual < min_weight -->
                        <span
                            v-if="item.billed_qty > item.actual_qty"
                            class="text-[10px] text-amber-700 font-bold block"
                        >
                            Ditagih: {{ item.billed_qty }} {{ item.service.unit }} (Min {{ item.service.min_weight }} kg)
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Turnaround Tier Selector (Reguler / Kilat / Express / Flash) -->
        <div class="p-3 bg-slate-50 rounded-xl border border-slate-200 space-y-2">
            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-600 block flex items-center space-x-1.5">
                <Clock class="h-3.5 w-3.5 text-slate-500" />
                <span>Kecepatan Pengerjaan</span>
            </span>
            <div class="grid grid-cols-2 gap-2">
                <button
                    v-for="tier in turnaroundTiers"
                    :key="tier.code"
                    type="button"
                    @click="emits('update:selectedTierCode', tier.code)"
                    :class="[
                        'min-h-[44px] p-2 text-left rounded-lg border text-xs font-semibold transition-all focus:outline-none',
                        selectedTierCode === tier.code
                            ? 'bg-teal-600 text-white border-teal-600 shadow-xs'
                            : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-100',
                    ]"
                >
                    <span class="block truncate font-bold">{{ tier.label }}</span>
                    <span class="block text-[10px] opacity-90 font-mono">
                        {{ tier.surcharge > 0 ? `+${formatRupiah(tier.surcharge)}` : 'Tarif Standar' }}
                    </span>
                </button>
            </div>
        </div>

        <!-- Calculation Ledger Summary -->
        <div class="p-4 bg-slate-900 text-white rounded-xl space-y-2 select-none shadow-md">
            <div class="flex justify-between text-xs text-slate-300">
                <span>Subtotal Cucian</span>
                <span class="font-mono">{{ formatRupiah(subtotal) }}</span>
            </div>

            <div v-if="tierSurcharge > 0" class="flex justify-between text-xs text-teal-300">
                <span>Biaya Kecepatan ({{ currentTier.label.split(' ')[0] }})</span>
                <span class="font-mono">+{{ formatRupiah(tierSurcharge) }}</span>
            </div>

            <div v-if="discountTotal > 0" class="flex justify-between text-xs text-amber-300">
                <span>Potongan Diskon</span>
                <span class="font-mono">-{{ formatRupiah(discountTotal) }}</span>
            </div>

            <div class="pt-2 border-t border-slate-800 flex justify-between items-baseline">
                <span class="font-bold text-sm text-teal-400 uppercase tracking-wider">Total Tagihan</span>
                <span class="font-mono font-bold text-2xl text-teal-300">{{ formatRupiah(grandTotal) }}</span>
            </div>
        </div>
    </div>
</template>
