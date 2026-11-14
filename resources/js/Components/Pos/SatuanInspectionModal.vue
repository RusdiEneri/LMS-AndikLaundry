<script setup lang="ts">
import { ref, watch } from 'vue';
import { X, Plus, Minus, AlertTriangle, Sparkles, Check } from 'lucide-vue-next';
import { formatRupiah } from '@/Support/whatsapp';
import type { ServiceItem } from './ServiceCard.vue';

interface Props {
    isOpen: boolean;
    service: ServiceItem | null;
    perfumeVariants: string[];
    defaultPerfume?: string;
}

const props = withDefaults(defineProps<Props>(), {
    isOpen: false,
    service: null,
    perfumeVariants: () => ['Lavender', 'Sakura', 'Ocean Fresh', 'Akasia', 'Non-Parfum'],
    defaultPerfume: 'Lavender',
});

const emits = defineEmits<{
    (e: 'update:isOpen', value: boolean): void;
    (e: 'confirm', data: {
        service: ServiceItem;
        actual_qty: number;
        billed_qty: number;
        perfume: string;
        item_condition_notes: string;
        special_instructions: string;
    }): void;
}>();

const quantity = ref(1);
const selectedPerfume = ref(props.defaultPerfume);
const conditionNotes = ref('');
const specialInstructions = ref('');

// Pre-defined defect chips for quick selection
const commonDefects = [
    'Noda luntur di kerah/lengan',
    'Kancing copot 1 buah',
    'Resleting seret / macet',
    'Ada sobekan halus',
    'Warna sudah pudar',
    'Bahan rapuh / berjamur',
];

watch(() => props.isOpen, (newVal) => {
    if (newVal) {
        quantity.value = 1;
        selectedPerfume.value = props.defaultPerfume || props.perfumeVariants[0] || 'Lavender';
        conditionNotes.value = '';
        specialInstructions.value = '';
    }
});

function appendDefect(defect: string) {
    if (!conditionNotes.value) {
        conditionNotes.value = defect;
    } else if (!conditionNotes.value.includes(defect)) {
        conditionNotes.value += `, ${defect}`;
    }
}

function incrementQty() {
    quantity.value += 1;
}

function decrementQty() {
    if (quantity.value > 1) {
        quantity.value -= 1;
    }
}

function handleConfirm() {
    if (!props.service) return;
    emits('confirm', {
        service: props.service,
        actual_qty: quantity.value,
        billed_qty: quantity.value,
        perfume: selectedPerfume.value,
        item_condition_notes: conditionNotes.value.trim(),
        special_instructions: specialInstructions.value.trim(),
    });
    closeModal();
}

function closeModal() {
    emits('update:isOpen', false);
}
</script>

<template>
    <div
        v-if="isOpen && service"
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-xs p-4"
        @click.self="closeModal"
    >
        <div class="bg-white rounded-2xl max-w-md w-full shadow-2xl border border-slate-200 overflow-hidden flex flex-col max-h-[90vh]">
            <!-- Header -->
            <div class="px-6 py-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
                <div>
                    <span class="text-[10px] font-bold text-teal-700 uppercase tracking-wider block">Inspeksi Kondisi Awal</span>
                    <h3 class="font-bold text-base text-slate-900">{{ service.name }}</h3>
                </div>
                <button
                    type="button"
                    @click="closeModal"
                    class="h-8 w-8 rounded-lg flex items-center justify-center text-slate-400 hover:text-slate-700 hover:bg-slate-200"
                    aria-label="Tutup Dialog"
                >
                    <X class="h-5 w-5" />
                </button>
            </div>

            <!-- Content -->
            <div class="p-6 flex-1 overflow-y-auto space-y-4">
                <!-- Quantity & Price Stepper -->
                <div class="p-4 bg-teal-50/50 rounded-xl border border-teal-100 flex items-center justify-between">
                    <div>
                        <span class="text-xs text-teal-800 font-semibold block">Jumlah Potong (Pcs)</span>
                        <span class="font-mono font-bold text-lg text-teal-900">
                            {{ formatRupiah(service.price * quantity) }}
                        </span>
                        <span class="text-[11px] text-teal-600 block">@{{ formatRupiah(service.price) }} / {{ service.unit }}</span>
                    </div>

                    <div class="flex items-center space-x-2 bg-white p-1 rounded-lg border border-teal-200 shadow-2xs">
                        <button
                            type="button"
                            @click="decrementQty"
                            :disabled="quantity <= 1"
                            class="h-9 w-9 rounded-md bg-slate-50 hover:bg-slate-100 text-slate-700 flex items-center justify-center disabled:opacity-40"
                        >
                            <Minus class="h-4 w-4" />
                        </button>
                        <span class="font-mono font-bold text-base w-8 text-center text-slate-900">{{ quantity }}</span>
                        <button
                            type="button"
                            @click="incrementQty"
                            class="h-9 w-9 rounded-md bg-teal-600 hover:bg-teal-700 text-white flex items-center justify-center shadow-xs"
                        >
                            <Plus class="h-4 w-4" />
                        </button>
                    </div>
                </div>

                <!-- Perfume Selection -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5 flex items-center space-x-1.5">
                        <Sparkles class="h-3.5 w-3.5 text-amber-500" />
                        <span>Varian Parfum</span>
                    </label>
                    <select
                        v-model="selectedPerfume"
                        class="min-h-[44px] w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-teal-500"
                    >
                        <option v-for="p in perfumeVariants" :key="p" :value="p">{{ p }}</option>
                    </select>
                </div>

                <!-- Defect / Condition Notes with Quick Chips -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1 flex items-center space-x-1.5">
                        <AlertTriangle class="h-3.5 w-3.5 text-amber-600" />
                        <span>Catatan Kerusakan Awal / Cacat Kain</span>
                    </label>
                    <p class="text-[11px] text-slate-500 mb-2">
                        Wajib dicatat untuk menghindari komplain pelanggan saat pengambilan.
                    </p>

                    <!-- Quick defect tags -->
                    <div class="flex flex-wrap gap-1.5 mb-2.5">
                        <button
                            v-for="chip in commonDefects"
                            :key="chip"
                            type="button"
                            @click="appendDefect(chip)"
                            class="text-[10px] font-semibold px-2 py-1 rounded-md bg-amber-50 hover:bg-amber-100 border border-amber-200 text-amber-900 transition-colors"
                        >
                            + {{ chip }}
                        </button>
                    </div>

                    <textarea
                        v-model="conditionNotes"
                        rows="3"
                        placeholder="Contoh: Kancing kedua copot, ada noda luntur di kerah baju..."
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-teal-500"
                    ></textarea>
                </div>

                <!-- Special Instructions -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Instruksi Khusus (Opsional)</label>
                    <input
                        v-model="specialInstructions"
                        type="text"
                        placeholder="Misal: Jangan diperas terlalu kencang, hanger khusus"
                        class="min-h-[44px] w-full rounded-lg border border-slate-300 px-3 py-2 text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-teal-500"
                    />
                </div>
            </div>

            <!-- Footer Buttons -->
            <div class="p-4 bg-slate-50 border-t border-slate-200 flex items-center space-x-3">
                <button
                    type="button"
                    @click="closeModal"
                    class="min-h-[44px] px-4 py-2 rounded-lg border border-slate-300 bg-white text-slate-700 text-xs font-bold hover:bg-slate-100"
                >
                    Batal
                </button>
                <button
                    type="button"
                    @click="handleConfirm"
                    class="min-h-[44px] flex-1 px-4 py-2 rounded-lg bg-teal-600 hover:bg-teal-700 active:scale-98 text-white text-xs font-bold shadow-xs flex items-center justify-center space-x-1.5"
                >
                    <Check class="h-4 w-4" />
                    <span>Tambahkan ke Keranjang</span>
                </button>
            </div>
        </div>
    </div>
</template>
