<script setup lang="ts">
import { ref, watch } from 'vue';
import { X, Plus, Minus, AlertTriangle, Sparkles, Check } from 'lucide-vue-next';
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
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-3 sm:p-4 animate-fadeIn"
        @click.self="closeModal"
    >
        <div class="bg-white rounded-3xl max-w-md w-full border border-slate-200/90 shadow-2xl overflow-hidden flex flex-col max-h-[90vh]">
            <!-- Header: Modern Navy-900 Banner -->
            <div class="px-6 py-4 bg-[#10194D] text-white flex items-center justify-between">
                <div>
                    <div class="flex items-center space-x-2">
                        <span class="text-[10px] font-bold uppercase tracking-wider bg-white/10 text-slate-200 px-2.5 py-0.5 rounded-full border border-white/20">
                            Inspeksi Kondisi Awal
                        </span>
                    </div>
                    <h3 class="font-bold text-base text-white tracking-tight mt-0.5">{{ service.name }}</h3>
                </div>
                <button
                    type="button"
                    @click="closeModal"
                    class="h-8 w-8 rounded-full bg-white/10 text-white hover:bg-white/20 flex items-center justify-center transition-colors cursor-pointer"
                    aria-label="Tutup Dialog"
                >
                    <X class="h-4 w-4" />
                </button>
            </div>

            <!-- Content -->
            <div class="p-5 flex-1 overflow-y-auto space-y-4">
                <!-- Quantity & Price Stepper -->
                <div class="p-4 bg-slate-50/70 rounded-2xl border border-slate-200 flex items-center justify-between">
                    <div>
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-500 block">Jumlah Potong (Pcs)</span>
                        <span class="font-mono font-bold text-xl text-slate-900 block mt-0.5">
                            {{ formatRupiah(service.price * quantity) }}
                        </span>
                        <span class="text-[11px] font-semibold text-slate-500 block">@{{ formatRupiah(service.price) }} / {{ service.unit }}</span>
                    </div>

                    <div class="flex items-center space-x-2 bg-white p-1.5 rounded-xl border border-slate-200 shadow-xs">
                        <button
                            type="button"
                            @click="decrementQty"
                            :disabled="quantity <= 1"
                            class="h-9 w-9 rounded-lg border border-slate-200 bg-slate-50 hover:bg-slate-100 text-slate-700 font-bold flex items-center justify-center disabled:opacity-40 cursor-pointer transition-colors"
                        >
                            <Minus class="h-4 w-4" />
                        </button>
                        <span class="font-mono font-bold text-lg w-8 text-center text-slate-900">{{ quantity }}</span>
                        <button
                            type="button"
                            @click="incrementQty"
                            class="h-9 w-9 rounded-lg bg-[#4F5FE8] hover:bg-[#3D4DC6] text-white font-bold flex items-center justify-center shadow-xs cursor-pointer transition-colors"
                        >
                            <Plus class="h-4 w-4" />
                        </button>
                    </div>
                </div>

                <!-- Defect / Condition Notes with Quick Chips -->
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1 flex items-center space-x-1.5">
                        <AlertTriangle class="h-4 w-4 text-amber-500" />
                        <span>Catatan Kerusakan Awal / Cacat Kain</span>
                    </label>
                    <p class="text-[11px] font-medium text-slate-500 mb-2">
                        Wajib dicatat untuk menghindari komplain pelanggan saat pengambilan.
                    </p>

                    <!-- Quick defect tags -->
                    <div class="flex flex-wrap gap-1.5 mb-2.5">
                        <button
                            v-for="chip in commonDefects"
                            :key="chip"
                            type="button"
                            @click="appendDefect(chip)"
                            class="text-[10px] font-semibold px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-[#E6E9FD] hover:text-[#4F5FE8] text-slate-700 transition-all cursor-pointer border border-slate-200"
                        >
                            + {{ chip }}
                        </button>
                    </div>

                    <textarea
                        v-model="conditionNotes"
                        rows="3"
                        placeholder="Contoh: Kancing kedua copot, ada noda luntur di kerah baju..."
                        class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-semibold text-slate-900 placeholder:text-slate-400 shadow-xs focus:outline-none focus:ring-2 focus:ring-[#4F5FE8]"
                    ></textarea>
                </div>

                <!-- Special Instructions -->
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">Instruksi Khusus (Opsional)</label>
                    <input
                        v-model="specialInstructions"
                        type="text"
                        placeholder="Misal: Jangan diperas terlalu kencang, hanger khusus"
                        class="min-h-[44px] w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-semibold text-slate-900 placeholder:text-slate-400 shadow-xs focus:outline-none focus:ring-2 focus:ring-[#4F5FE8]"
                    />
                </div>
            </div>

            <!-- Footer Buttons -->
            <div class="p-4 bg-slate-50/70 border-t border-slate-200 flex items-center space-x-3">
                <button
                    type="button"
                    @click="closeModal"
                    class="min-h-[44px] px-5 py-2 rounded-xl border border-slate-200 bg-white text-slate-700 text-xs font-bold uppercase tracking-wider hover:bg-slate-50 transition-all cursor-pointer"
                >
                    Batal
                </button>
                <button
                    type="button"
                    @click="handleConfirm"
                    class="min-h-[44px] flex-1 px-5 py-2 rounded-xl bg-[#FF8A5B] hover:bg-[#F26B3A] text-[#10194D] text-xs font-bold uppercase tracking-wider shadow-sm flex items-center justify-center space-x-2 transition-all cursor-pointer"
                >
                    <Check class="h-4 w-4" />
                    <span>Tambahkan ke Keranjang</span>
                </button>
            </div>
        </div>
    </div>
</template>
