<script setup lang="ts">
import { Delete, RotateCcw } from 'lucide-vue-next';

interface Props {
    modelValue?: string | number;
    allowDecimal?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    modelValue: '',
    allowDecimal: true,
});

const emits = defineEmits<{
    (e: 'update:modelValue', value: string): void;
    (e: 'digit', digit: string): void;
    (e: 'decimal'): void;
    (e: 'backspace'): void;
    (e: 'clear'): void;
}>();

function onKeyClick(key: string) {
    if (key === 'backspace') {
        emits('backspace');
        const str = String(props.modelValue || '');
        if (str.length > 0) {
            emits('update:modelValue', str.slice(0, -1));
        }
    } else if (key === 'clear') {
        emits('clear');
        emits('update:modelValue', '');
    } else if (key === '.') {
        emits('decimal');
        const str = String(props.modelValue || '');
        if (!str.includes('.')) {
            emits('update:modelValue', str ? `${str}.` : '0.');
        }
    } else {
        emits('digit', key);
        const str = String(props.modelValue || '');
        emits('update:modelValue', `${str}${key}`);
    }
}
</script>

<template>
    <div class="grid grid-cols-3 gap-2 select-none w-full">
        <!-- Row 1: 7, 8, 9 -->
        <button
            type="button"
            class="flex min-h-[52px] min-w-[52px] items-center justify-center rounded-lg border border-slate-200 bg-white text-xl font-bold text-slate-800 shadow-xs hover:bg-slate-50 active:scale-95 active:bg-slate-200 transition-all focus:outline-none focus:ring-2 focus:ring-teal-500"
            @click="onKeyClick('7')"
        >
            7
        </button>
        <button
            type="button"
            class="flex min-h-[52px] min-w-[52px] items-center justify-center rounded-lg border border-slate-200 bg-white text-xl font-bold text-slate-800 shadow-xs hover:bg-slate-50 active:scale-95 active:bg-slate-200 transition-all focus:outline-none focus:ring-2 focus:ring-teal-500"
            @click="onKeyClick('8')"
        >
            8
        </button>
        <button
            type="button"
            class="flex min-h-[52px] min-w-[52px] items-center justify-center rounded-lg border border-slate-200 bg-white text-xl font-bold text-slate-800 shadow-xs hover:bg-slate-50 active:scale-95 active:bg-slate-200 transition-all focus:outline-none focus:ring-2 focus:ring-teal-500"
            @click="onKeyClick('9')"
        >
            9
        </button>

        <!-- Row 2: 4, 5, 6 -->
        <button
            type="button"
            class="flex min-h-[52px] min-w-[52px] items-center justify-center rounded-lg border border-slate-200 bg-white text-xl font-bold text-slate-800 shadow-xs hover:bg-slate-50 active:scale-95 active:bg-slate-200 transition-all focus:outline-none focus:ring-2 focus:ring-teal-500"
            @click="onKeyClick('4')"
        >
            4
        </button>
        <button
            type="button"
            class="flex min-h-[52px] min-w-[52px] items-center justify-center rounded-lg border border-slate-200 bg-white text-xl font-bold text-slate-800 shadow-xs hover:bg-slate-50 active:scale-95 active:bg-slate-200 transition-all focus:outline-none focus:ring-2 focus:ring-teal-500"
            @click="onKeyClick('5')"
        >
            5
        </button>
        <button
            type="button"
            class="flex min-h-[52px] min-w-[52px] items-center justify-center rounded-lg border border-slate-200 bg-white text-xl font-bold text-slate-800 shadow-xs hover:bg-slate-50 active:scale-95 active:bg-slate-200 transition-all focus:outline-none focus:ring-2 focus:ring-teal-500"
            @click="onKeyClick('6')"
        >
            6
        </button>

        <!-- Row 3: 1, 2, 3 -->
        <button
            type="button"
            class="flex min-h-[52px] min-w-[52px] items-center justify-center rounded-lg border border-slate-200 bg-white text-xl font-bold text-slate-800 shadow-xs hover:bg-slate-50 active:scale-95 active:bg-slate-200 transition-all focus:outline-none focus:ring-2 focus:ring-teal-500"
            @click="onKeyClick('1')"
        >
            1
        </button>
        <button
            type="button"
            class="flex min-h-[52px] min-w-[52px] items-center justify-center rounded-lg border border-slate-200 bg-white text-xl font-bold text-slate-800 shadow-xs hover:bg-slate-50 active:scale-95 active:bg-slate-200 transition-all focus:outline-none focus:ring-2 focus:ring-teal-500"
            @click="onKeyClick('2')"
        >
            2
        </button>
        <button
            type="button"
            class="flex min-h-[52px] min-w-[52px] items-center justify-center rounded-lg border border-slate-200 bg-white text-xl font-bold text-slate-800 shadow-xs hover:bg-slate-50 active:scale-95 active:bg-slate-200 transition-all focus:outline-none focus:ring-2 focus:ring-teal-500"
            @click="onKeyClick('3')"
        >
            3
        </button>

        <!-- Row 4: 0, ., backspace -->
        <button
            type="button"
            class="flex min-h-[52px] min-w-[52px] items-center justify-center rounded-lg border border-slate-200 bg-white text-xl font-bold text-slate-800 shadow-xs hover:bg-slate-50 active:scale-95 active:bg-slate-200 transition-all focus:outline-none focus:ring-2 focus:ring-teal-500"
            @click="onKeyClick('0')"
        >
            0
        </button>
        <button
            type="button"
            :disabled="!allowDecimal"
            class="flex min-h-[52px] min-w-[52px] items-center justify-center rounded-lg border border-slate-200 bg-white text-2xl font-bold text-slate-800 shadow-xs hover:bg-slate-50 active:scale-95 active:bg-slate-200 transition-all disabled:opacity-40 focus:outline-none focus:ring-2 focus:ring-teal-500"
            @click="onKeyClick('.')"
            aria-label="Titik desimal"
        >
            .
        </button>
        <button
            type="button"
            class="flex min-h-[52px] min-w-[52px] items-center justify-center rounded-lg border border-rose-200 bg-rose-50 text-rose-700 shadow-xs hover:bg-rose-100 active:scale-95 active:bg-rose-200 transition-all focus:outline-none focus:ring-2 focus:ring-rose-500"
            @click="onKeyClick('backspace')"
            aria-label="Hapus digit terakhir"
        >
            <Delete class="h-6 w-6" />
        </button>
    </div>
</template>
