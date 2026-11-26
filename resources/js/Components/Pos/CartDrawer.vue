<script setup lang="ts">
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
        <div class="flex items-center justify-between">
            <span class="overline-label text-text-muted flex items-center space-x-1.5">
                <Scale class="h-4 w-4 text-text-muted" />
                <span>Daftar Cucian ({{ items.length }} Item)</span>
            </span>
            <button
                v-if="items.length > 0"
                type="button"
                @click="emits('clear-cart')"
                class="text-xs font-semibold text-danger hover:text-danger/80 hover:underline transition-colors min-h-[36px] inline-flex items-center cursor-pointer focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-primary-500 rounded-sm"
            >
                Kosongkan
            </button>
        </div>

        <div class="space-y-2.5">
            <div
                v-if="items.length === 0"
                class="py-5 px-4 flex flex-col items-center justify-center text-text-muted text-xs text-center border border-dashed border-border rounded-md bg-bg"
            >
                <div class="h-9 w-9 rounded-md bg-primary-100 text-navy-800 flex items-center justify-center mb-2">
                    <Scale class="h-4 w-4" aria-hidden="true" />
                </div>
                <span class="font-semibold text-navy-950 text-xs">Keranjang cucian masih kosong</span>
                <span class="text-[11px] text-text-muted mt-0.5">Pilih layanan di kiri untuk mulai mencatat</span>
            </div>

            <div
                v-for="(item, idx) in items"
                :key="`${item.service.id}-${idx}`"
                class="p-3 bg-bg rounded-md border border-border space-y-2.5"
            >
                <div class="flex items-start justify-between gap-2">
                    <div class="space-y-1">
                        <span class="font-heading font-bold text-xs sm:text-sm text-text block leading-tight">{{ item.service.name }}</span>
                        <div class="flex items-center space-x-2 text-[11px] text-text-muted">
                            <span class="font-mono font-semibold text-text tabular-nums">{{ formatRupiah(item.service.price) }}/{{ item.service.unit }}</span>
                        </div>

                        <div
                            v-if="item.item_condition_notes"
                            class="inline-flex items-center space-x-1 px-2 py-0.5 rounded-pill text-[10px] bg-danger/10 text-danger font-semibold"
                        >
                            <AlertTriangle class="h-3 w-3 shrink-0" />
                            <span class="truncate max-w-[200px]">{{ item.item_condition_notes }}</span>
                        </div>
                    </div>

                    <button
                        type="button"
                        @click="emits('remove-item', idx)"
                        class="inline-flex h-8 w-8 items-center justify-center rounded-md text-text-muted hover:text-danger hover:bg-danger/10 transition-all duration-[120ms] ease-standard cursor-pointer focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-primary-500"
                        aria-label="Hapus item cucian"
                    >
                        <Trash2 class="h-4 w-4" />
                    </button>
                </div>

                <div v-if="isKarpetService(item)" class="p-3 bg-warning/15 rounded-md border border-warning/35 space-y-2">
                    <div class="flex items-center space-x-1.5">
                        <MapPin class="h-3.5 w-3.5 text-navy-950" aria-hidden="true" />
                        <span class="text-xs font-bold text-navy-950">Ukuran Karpet</span>
                    </div>
                    <div class="flex gap-2">
                        <div class="flex-1">
                            <label class="overline-label text-navy-950 block mb-0.5">Panjang (m)</label>
                            <input
                                type="number"
                                step="0.1"
                                min="0"
                                :value="item.length || 0"
                                @input="(e) => { item.length = parseFloat((e.target as HTMLInputElement).value) || 0; calculateArea(item); }"
                                class="w-full h-9 rounded-md border border-border bg-surface px-2.5 py-1 text-xs font-mono font-semibold text-text focus:outline-none focus:border-primary-600 focus:ring-3 focus:ring-primary-100"
                                placeholder="0.0"
                            />
                        </div>
                        <div class="flex-1">
                            <label class="overline-label text-navy-950 block mb-0.5">Lebar (m)</label>
                            <input
                                type="number"
                                step="0.1"
                                min="0"
                                :value="item.width || 0"
                                @input="(e) => { item.width = parseFloat((e.target as HTMLInputElement).value) || 0; calculateArea(item); }"
                                class="w-full h-9 rounded-md border border-border bg-surface px-2.5 py-1 text-xs font-mono font-semibold text-text focus:outline-none focus:border-primary-600 focus:ring-3 focus:ring-primary-100"
                                placeholder="0.0"
                            />
                        </div>
                    </div>
                    <div class="flex justify-between items-center pt-1 text-xs font-bold text-navy-950">
                        <span>Luas: {{ item.calculated_area || 0 }} m²</span>
                        <span v-if="(item.calculated_area ?? 0) > 0" class="font-mono tabular-nums">
                            {{ formatRupiah((item.calculated_area || 0) * item.service.price) }}
                        </span>
                    </div>
                </div>

                <div v-else class="flex items-center justify-between pt-2 border-t border-border">
                    <div class="flex items-center space-x-1.5">
                        <button
                            type="button"
                            @click="handleQtyStep(idx, -1)"
                            class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-border bg-bg text-text hover:bg-primary-100 transition-all duration-[120ms] ease-standard cursor-pointer focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-primary-500"
                            aria-label="Kurangi berat / jumlah"
                        >
                            <Minus class="h-3.5 w-3.5" />
                        </button>

                        <div class="flex items-center bg-surface border border-border rounded-md px-2.5 py-1 h-8">
                            <input
                                type="number"
                                step="0.1"
                                min="0.01"
                                :value="item.actual_qty"
                                @change="handleDirectQtyChange(idx, $event)"
                                class="w-12 text-center font-mono font-semibold text-xs text-text bg-transparent focus:outline-none tabular-nums"
                            />
                            <span class="text-[11px] font-medium text-text-muted ml-0.5">{{ item.service.unit }}</span>
                        </div>

                        <button
                            type="button"
                            @click="handleQtyStep(idx, 1)"
                            class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-border bg-bg text-text hover:bg-primary-100 transition-all duration-[120ms] ease-standard cursor-pointer focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-primary-500"
                            aria-label="Tambah berat / jumlah"
                        >
                            <Plus class="h-3.5 w-3.5" />
                        </button>
                    </div>

                    <div class="text-right">
                        <div class="font-mono font-semibold text-xs sm:text-sm text-text tabular-nums">
                            {{ formatRupiah((item.calculated_area || item.billed_qty) * item.service.price) }}
                        </div>
                        <span
                            v-if="item.billed_qty > item.actual_qty"
                            class="text-[10px] text-danger font-semibold block"
                        >
                            Ditagih: {{ item.billed_qty }} {{ item.service.unit }} (Min {{ item.service.min_weight }} kg)
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="pt-3 border-t border-border space-y-2">
            <span class="overline-label text-text-muted flex items-center space-x-1.5">
                <Truck class="h-3.5 w-3.5 text-text-muted" aria-hidden="true" />
                <span>Pilihan Pengantaran</span>
            </span>
            <div class="grid grid-cols-2 gap-2">
                <button
                    type="button"
                    @click="emits('update:deliveryOption', 'pickup')"
                    :class="[
                        'min-h-[44px] p-2.5 text-left rounded-md border text-xs transition-colors duration-[120ms] ease-standard cursor-pointer focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-primary-500',
                        deliveryOption === 'pickup'
                            ? 'bg-primary-100/75 text-navy-950 border-navy-800 font-semibold'
                            : 'bg-bg text-navy-950 border-border hover:bg-primary-100/40 font-medium',
                    ]"
                >
                    <span class="block truncate text-xs">Ambil Sendiri</span>
                    <span class="block text-[11px] text-text-muted font-normal mt-0.5">Datang ke lokasi</span>
                </button>
                <button
                    type="button"
                    @click="emits('update:deliveryOption', 'delivery')"
                    :class="[
                        'min-h-[44px] p-2.5 text-left rounded-md border text-xs transition-colors duration-[120ms] ease-standard cursor-pointer focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-primary-500',
                        deliveryOption === 'delivery'
                            ? 'bg-primary-100/75 text-navy-950 border-navy-800 font-semibold'
                            : 'bg-bg text-navy-950 border-border hover:bg-primary-100/40 font-medium',
                    ]"
                >
                    <span class="block truncate text-xs">Antar Jemput</span>
                    <span class="block text-[11px] text-text-muted font-normal mt-0.5">Free Ongkir Maks 3 km</span>
                </button>
            </div>
        </div>

        <div class="pt-3 border-t border-border space-y-2">
            <span class="overline-label text-text-muted flex items-center space-x-1.5">
                <Clock class="h-3.5 w-3.5 text-text-muted" aria-hidden="true" />
                <span>Kecepatan Pengerjaan</span>
            </span>
            <div class="grid grid-cols-2 gap-2">
                <button
                    v-for="tier in turnaroundTiers"
                    :key="tier.code"
                    type="button"
                    @click="emits('update:selectedTierCode', tier.code)"
                    :class="[
                        'min-h-[44px] p-2.5 text-left rounded-md border text-xs transition-colors duration-[120ms] ease-standard cursor-pointer focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-primary-500',
                        selectedTierCode === tier.code
                            ? 'bg-primary-100/75 text-navy-950 border-navy-800 font-semibold'
                            : 'bg-bg text-navy-950 border-border hover:bg-primary-100/40 font-medium',
                    ]"
                >
                    <span class="block text-xs leading-snug">{{ tier.label }}</span>
                    <span class="block text-[11px] text-text-muted font-mono mt-0.5 tabular-nums">
                        {{ tier.surcharge > 0 ? `+${formatRupiah(tier.surcharge)}` : 'Tarif Standar' }}
                    </span>
                </button>
            </div>
        </div>
    </div>
</template>
