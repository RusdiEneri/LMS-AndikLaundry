<script setup lang="ts">
import { ref, watch } from 'vue';
import { X, Plus, Minus, AlertTriangle, Check } from 'lucide-vue-next';
import { formatRupiah } from '@/Support/whatsapp';
import type { ServiceItem } from './ServiceCard.vue';

interface Props {
    isOpen: boolean;
    service: ServiceItem | null;
}

const props = withDefaults(defineProps<Props>(), {
    isOpen: false,
    service: null,
});

const emits = defineEmits<{
    (e: 'update:isOpen', value: boolean): void;
    (e: 'confirm', data: {
        service: ServiceItem;
        actual_qty: number;
        billed_qty: number;
        item_condition_notes: string;
        special_instructions: string;
    }): void;
}>();

const quantity = ref(1);
const conditionNotes = ref('');
const specialInstructions = ref('');

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
        class="fixed inset-0 z-50 flex items-center justify-center bg-navy-950/60 backdrop-blur-[4px] p-3 sm:p-4 animate-fadeIn"
        @click.self="closeModal"
    >
        <div class="bg-surface rounded-xl max-w-md w-full shadow-lg overflow-hidden flex flex-col max-h-[90vh]">
            <div class="px-6 py-4 bg-navy-900 text-white flex items-center justify-between">
                <div>
                    <div class="flex items-center space-x-2">
                        <span class="overline-label bg-white/10 text-text-on-dark-muted px-2.5 py-0.5 rounded-pill border border-white/20">
                            Inspeksi Kondisi Awal
                        </span>
                    </div>
                    <h3 class="font-heading font-bold text-base text-white tracking-tight mt-0.5">{{ service.name }}</h3>
                </div>
                <button
                    type="button"
                    @click="closeModal"
                    class="h-8 w-8 rounded-full bg-white/10 text-white hover:bg-white/20 flex items-center justify-center transition-colors duration-[120ms] ease-standard cursor-pointer focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-primary-500"
                    aria-label="Tutup Dialog"
                >
                    <X class="h-4 w-4" />
                </button>
            </div>

            <div class="p-5 flex-1 overflow-y-auto space-y-4">
                <div class="p-4 bg-bg rounded-lg border border-border flex items-center justify-between">
                    <div>
                        <span class="overline-label text-text-muted block">Jumlah Potong (Pcs)</span>
                        <span class="font-mono font-semibold text-xl text-text block mt-0.5 tabular-nums">
                            {{ formatRupiah(service.price * quantity) }}
                        </span>
                        <span class="text-[11px] font-medium text-text-muted block">@{{ formatRupiah(service.price) }} / {{ service.unit }}</span>
                    </div>

                    <div class="flex items-center space-x-2 bg-surface p-1.5 rounded-md border border-border">
                        <button
                            type="button"
                            @click="decrementQty"
                            :disabled="quantity <= 1"
                            class="h-9 w-9 rounded-md border border-border bg-bg hover:bg-primary-100 text-text font-bold flex items-center justify-center disabled:opacity-40 cursor-pointer transition-colors duration-[120ms] ease-standard focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-primary-500"
                        >
                            <Minus class="h-4 w-4" />
                        </button>
                        <span class="font-mono font-semibold text-lg w-8 text-center text-text tabular-nums">{{ quantity }}</span>
                        <button
                            type="button"
                            @click="incrementQty"
                            class="h-9 w-9 rounded-md bg-primary-600 hover:bg-primary-700 text-white font-bold flex items-center justify-center cursor-pointer transition-colors duration-[120ms] ease-standard focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-primary-500"
                        >
                            <Plus class="h-4 w-4" />
                        </button>
                    </div>
                </div>

                <div>
                    <label class="overline-label text-text mb-1 flex items-center space-x-1.5">
                        <AlertTriangle class="h-4 w-4 text-warning" />
                        <span>Catatan Kerusakan Awal / Cacat Kain</span>
                    </label>
                    <p class="text-[11px] font-medium text-text-muted mb-2">
                        Wajib dicatat untuk menghindari komplain pelanggan saat pengambilan.
                    </p>

                    <div class="flex flex-wrap gap-1.5 mb-2.5">
                        <button
                            v-for="chip in commonDefects"
                            :key="chip"
                            type="button"
                            @click="appendDefect(chip)"
                            class="text-[10px] font-semibold px-2.5 py-1 rounded-md bg-bg hover:bg-primary-100 hover:text-primary-700 text-text-muted transition-all duration-[120ms] ease-standard cursor-pointer border border-border focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-primary-500"
                        >
                            + {{ chip }}
                        </button>
                    </div>

                    <textarea
                        v-model="conditionNotes"
                        rows="3"
                        placeholder="Contoh: Kancing kedua copot, ada noda luntur di kerah baju..."
                        class="w-full rounded-md border border-border bg-surface px-3.5 py-2 text-xs font-medium text-text placeholder:text-text-muted focus:outline-none focus:border-primary-600 focus:ring-3 focus:ring-primary-100"
                    ></textarea>
                </div>

                <div>
                    <label class="overline-label text-text mb-1">Instruksi Khusus (Opsional)</label>
                    <input
                        v-model="specialInstructions"
                        type="text"
                        placeholder="Misal: Jangan diperas terlalu kencang, hanger khusus"
                        class="min-h-[44px] w-full rounded-md border border-border bg-surface px-3.5 py-2 text-xs font-medium text-text placeholder:text-text-muted focus:outline-none focus:border-primary-600 focus:ring-3 focus:ring-primary-100"
                    />
                </div>
            </div>

            <div class="p-4 bg-bg border-t border-border flex items-center space-x-3">
                <button
                    type="button"
                    @click="closeModal"
                    class="min-h-[44px] px-5 py-2 rounded-pill border-[1.5px] border-navy-900 bg-surface text-text text-xs font-semibold hover:bg-primary-100/50 transition-all duration-[120ms] ease-standard cursor-pointer focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-primary-500"
                >
                    Batal
                </button>
                <button
                    type="button"
                    @click="handleConfirm"
                    class="min-h-[44px] flex-1 px-5 py-2 rounded-pill bg-accent-500 hover:bg-accent-600 text-navy-950 text-xs font-bold uppercase tracking-wider shadow-sm flex items-center justify-center space-x-2 transition-all duration-[120ms] ease-standard cursor-pointer focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-primary-500"
                >
                    <Check class="h-4 w-4" />
                    <span>Tambahkan ke Keranjang</span>
                </button>
            </div>
        </div>
    </div>
</template>
