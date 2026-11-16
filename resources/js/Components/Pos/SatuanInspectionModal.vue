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
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-xs p-4"
        @click.self="closeModal"
    >
        <div class="bg-white rounded-[12px] max-w-md w-full shadow-[0_8px_30px_rgba(0,0,0,0.12)] border border-[#E8E8EC] overflow-hidden flex flex-col max-h-[90vh]">
            <!-- Header -->
            <div class="px-6 py-4 bg-white border-b border-[#E8E8EC] flex items-center justify-between">
                <div>
                    <span class="text-[10px] font-bold text-[#6366F1] uppercase tracking-wider block">Inspeksi Kondisi Awal</span>
                    <h3 class="font-bold text-base text-[#0A0A0A]">{{ service.name }}</h3>
                </div>
                <button
                    type="button"
                    @click="closeModal"
                    class="h-8 w-8 rounded-[6px] flex items-center justify-center text-[#6B6B6B] hover:text-[#0A0A0A] hover:bg-[#FAFAFA]"
                    aria-label="Tutup Dialog"
                >
                    <X class="h-5 w-5" />
                </button>
            </div>

            <!-- Content -->
            <div class="p-6 flex-1 overflow-y-auto space-y-4">
                <!-- Quantity & Price Stepper -->
                <div class="p-4 bg-[#6366F1]/5 rounded-[8px] border border-[#6366F1]/20 flex items-center justify-between">
                    <div>
                        <span class="text-xs text-[#6366F1] font-semibold block">Jumlah Potong (Pcs)</span>
                        <span class="font-mono font-bold text-lg text-[#0A0A0A]">
                            {{ formatRupiah(service.price * quantity) }}
                        </span>
                        <span class="text-[11px] text-[#6B6B6B] block">@{{ formatRupiah(service.price) }} / {{ service.unit }}</span>
                    </div>

                    <div class="flex items-center space-x-2 bg-white p-1 rounded-[6px] border border-[#E8E8EC] shadow-2xs">
                        <button
                            type="button"
                            @click="decrementQty"
                            :disabled="quantity <= 1"
                            class="h-9 w-9 rounded-[4px] bg-[#FAFAFA] hover:bg-[#E8E8EC] text-[#0A0A0A] flex items-center justify-center disabled:opacity-40"
                        >
                            <Minus class="h-4 w-4" />
                        </button>
                        <span class="font-mono font-bold text-base w-8 text-center text-[#0A0A0A]">{{ quantity }}</span>
                        <button
                            type="button"
                            @click="incrementQty"
                            class="h-9 w-9 rounded-[4px] bg-[#6366F1] hover:bg-[#4F46E5] text-white flex items-center justify-center shadow-xs"
                        >
                            <Plus class="h-4 w-4" />
                        </button>
                    </div>
                </div>

                <!-- Defect / Condition Notes with Quick Chips -->
                <div>
                    <label class="block text-xs font-bold text-[#0A0A0A] mb-1 flex items-center space-x-1.5">
                        <AlertTriangle class="h-3.5 w-3.5 text-[#F59E0B]" />
                        <span>Catatan Kerusakan Awal / Cacat Kain</span>
                    </label>
                    <p class="text-[11px] text-[#6B6B6B] mb-2">
                        Wajib dicatat untuk menghindari komplain pelanggan saat pengambilan.
                    </p>

                    <!-- Quick defect tags -->
                    <div class="flex flex-wrap gap-1.5 mb-2.5">
                        <button
                            v-for="chip in commonDefects"
                            :key="chip"
                            type="button"
                            @click="appendDefect(chip)"
                            class="text-[10px] font-semibold px-2 py-1 rounded-[4px] bg-[#F59E0B]/10 hover:bg-[#F59E0B]/20 border border-[#F59E0B]/20 text-[#F59E0B] transition-colors"
                        >
                            + {{ chip }}
                        </button>
                    </div>

                    <textarea
                        v-model="conditionNotes"
                        rows="3"
                        placeholder="Contoh: Kancing kedua copot, ada noda luntur di kerah baju..."
                        class="w-full rounded-[6px] border border-[#E8E8EC] px-3 py-2 text-xs text-[#0A0A0A] placeholder:text-[#9C9C9C] focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-[#6366F1]/12"
                    ></textarea>
                </div>

                <!-- Special Instructions -->
                <div>
                    <label class="block text-xs font-bold text-[#0A0A0A] mb-1">Instruksi Khusus (Opsional)</label>
                    <input
                        v-model="specialInstructions"
                        type="text"
                        placeholder="Misal: Jangan diperas terlalu kencang, hanger khusus"
                        class="min-h-[44px] w-full rounded-[6px] border border-[#E8E8EC] px-3 py-2 text-xs text-[#0A0A0A] placeholder:text-[#9C9C9C] focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-[#6366F1]/12"
                    />
                </div>
            </div>

            <!-- Footer Buttons -->
            <div class="p-4 bg-[#FAFAFA] border-t border-[#E8E8EC] flex items-center space-x-3">
                <button
                    type="button"
                    @click="closeModal"
                    class="min-h-[44px] px-4 py-2 rounded-[6px] border border-[#E8E8EC] bg-white text-[#6B6B6B] text-xs font-bold hover:bg-[#FAFAFA] hover:text-[#0A0A0A]"
                >
                    Batal
                </button>
                <button
                    type="button"
                    @click="handleConfirm"
                    class="min-h-[44px] flex-1 px-4 py-2 rounded-[6px] bg-[#6366F1] hover:bg-[#4F46E5] active:scale-98 text-white text-xs font-bold shadow-[0_4px_12px_rgba(99,102,241,0.35)] flex items-center justify-center space-x-1.5 transition-all"
                >
                    <Check class="h-4 w-4" />
                    <span>Tambahkan ke Keranjang</span>
                </button>
            </div>
        </div>
    </div>
</template>
