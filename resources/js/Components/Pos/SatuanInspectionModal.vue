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
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs p-3 sm:p-4"
        @click.self="closeModal"
    >
        <div class="bg-[#FFFDF5] rounded-[12px] max-w-md w-full border-[3.5px] border-black shadow-[8px_8px_0px_#000] overflow-hidden flex flex-col max-h-[92vh]">
            <!-- Header: Neubrutalism Yellow Banner -->
            <div class="px-5 py-3.5 bg-[#FFEB3B] border-b-[3.5px] border-black flex items-center justify-between">
                <div>
                    <div class="flex items-center space-x-2">
                        <span class="text-[10px] font-black uppercase tracking-wider bg-black text-[#FFEB3B] px-2 py-0.5 rounded-[4px] border border-black">
                            Inspeksi Kondisi Awal
                        </span>
                    </div>
                    <h3 class="font-black text-base text-black tracking-tight mt-0.5">{{ service.name }}</h3>
                </div>
                <button
                    type="button"
                    @click="closeModal"
                    class="h-8 w-8 rounded-[6px] border-2 border-black bg-white text-black hover:bg-[#FF5252] hover:text-white shadow-[2px_2px_0px_#000] active:translate-x-0.5 active:translate-y-0.5 active:shadow-none flex items-center justify-center transition-all cursor-pointer"
                    aria-label="Tutup Dialog"
                >
                    <X class="h-4 w-4 stroke-[3]" />
                </button>
            </div>

            <!-- Content -->
            <div class="p-4 sm:p-5 flex-1 overflow-y-auto space-y-4">
                <!-- Quantity & Price Stepper -->
                <div class="p-4 bg-white rounded-[8px] border-2 border-black shadow-[3px_3px_0px_#000] flex items-center justify-between">
                    <div>
                        <span class="text-xs font-black uppercase tracking-wider text-black block">Jumlah Potong (Pcs)</span>
                        <span class="font-mono font-black text-xl text-black block mt-0.5">
                            {{ formatRupiah(service.price * quantity) }}
                        </span>
                        <span class="text-[11px] font-bold text-black/70 block">@{{ formatRupiah(service.price) }} / {{ service.unit }}</span>
                    </div>

                    <div class="flex items-center space-x-2 bg-[#FFF9D2] p-1.5 rounded-[8px] border-2 border-black shadow-[2px_2px_0px_#000]">
                        <button
                            type="button"
                            @click="decrementQty"
                            :disabled="quantity <= 1"
                            class="h-9 w-9 rounded-[6px] border-2 border-black bg-white hover:bg-black/10 text-black font-black flex items-center justify-center disabled:opacity-40 cursor-pointer active:translate-x-0.5 active:translate-y-0.5"
                        >
                            <Minus class="h-4 w-4 stroke-[3]" />
                        </button>
                        <span class="font-mono font-black text-lg w-8 text-center text-black">{{ quantity }}</span>
                        <button
                            type="button"
                            @click="incrementQty"
                            class="h-9 w-9 rounded-[6px] border-2 border-black bg-[#FFEB3B] hover:bg-[#FACC15] text-black font-black flex items-center justify-center shadow-[1px_1px_0px_#000] cursor-pointer active:translate-x-0.5 active:translate-y-0.5"
                        >
                            <Plus class="h-4 w-4 stroke-[3]" />
                        </button>
                    </div>
                </div>

                <!-- Defect / Condition Notes with Quick Chips -->
                <div>
                    <label class="block text-xs font-black uppercase tracking-wider text-black mb-1 flex items-center space-x-1.5">
                        <AlertTriangle class="h-4 w-4 text-[#F59E0B] stroke-[2.5]" />
                        <span>Catatan Kerusakan Awal / Cacat Kain</span>
                    </label>
                    <p class="text-[11px] font-bold text-black/70 mb-2">
                        Wajib dicatat untuk menghindari komplain pelanggan saat pengambilan.
                    </p>

                    <!-- Quick defect tags -->
                    <div class="flex flex-wrap gap-1.5 mb-2.5">
                        <button
                            v-for="chip in commonDefects"
                            :key="chip"
                            type="button"
                            @click="appendDefect(chip)"
                            class="text-[10px] font-black px-2.5 py-1 rounded-[6px] bg-white hover:bg-[#FFF9D2] border-2 border-black shadow-[1.5px_1.5px_0px_#000] text-black transition-all cursor-pointer active:translate-x-0.5 active:translate-y-0.5"
                        >
                            + {{ chip }}
                        </button>
                    </div>

                    <textarea
                        v-model="conditionNotes"
                        rows="3"
                        placeholder="Contoh: Kancing kedua copot, ada noda luntur di kerah baju..."
                        class="w-full rounded-[8px] border-2 border-black bg-white px-3 py-2 text-xs font-bold text-black placeholder:text-black/40 shadow-[2px_2px_0px_#000] focus:outline-none focus:shadow-[3px_3px_0px_#000]"
                    ></textarea>
                </div>

                <!-- Special Instructions -->
                <div>
                    <label class="block text-xs font-black uppercase tracking-wider text-black mb-1">Instruksi Khusus (Opsional)</label>
                    <input
                        v-model="specialInstructions"
                        type="text"
                        placeholder="Misal: Jangan diperas terlalu kencang, hanger khusus"
                        class="min-h-[44px] w-full rounded-[8px] border-2 border-black bg-white px-3 py-2 text-xs font-bold text-black placeholder:text-black/40 shadow-[2px_2px_0px_#000] focus:outline-none focus:shadow-[3px_3px_0px_#000]"
                    />
                </div>
            </div>

            <!-- Footer Buttons -->
            <div class="p-4 bg-white border-t-[3px] border-black flex items-center space-x-3">
                <button
                    type="button"
                    @click="closeModal"
                    class="min-h-[44px] px-5 py-2 rounded-[8px] border-2 border-black bg-white text-black text-xs font-black uppercase tracking-wider shadow-[2px_2px_0px_#000] hover:bg-black/5 active:translate-x-0.5 active:translate-y-0.5 active:shadow-none cursor-pointer"
                >
                    Batal
                </button>
                <button
                    type="button"
                    @click="handleConfirm"
                    class="min-h-[44px] flex-1 px-5 py-2 rounded-[8px] bg-[#22C55E] hover:bg-[#16A34A] text-black hover:text-white text-xs font-black uppercase tracking-wider border-[2.5px] border-black shadow-[4px_4px_0px_#000] flex items-center justify-center space-x-2 transition-all cursor-pointer active:translate-x-0.5 active:translate-y-0.5 active:shadow-none"
                >
                    <Check class="h-4 w-4 stroke-[3]" />
                    <span>Tambahkan ke Keranjang</span>
                </button>
            </div>
        </div>
    </div>
</template>
