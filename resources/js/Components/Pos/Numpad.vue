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
            class="btn-numpad focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-[#6366F1]/12 cursor-pointer"
            @click="onKeyClick('7')"
        >
            7
        </button>
        <button
            type="button"
            class="btn-numpad focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-[#6366F1]/12 cursor-pointer"
            @click="onKeyClick('8')"
        >
            8
        </button>
        <button
            type="button"
            class="btn-numpad focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-[#6366F1]/12 cursor-pointer"
            @click="onKeyClick('9')"
        >
            9
        </button>

        <!-- Row 2: 4, 5, 6 -->
        <button
            type="button"
            class="btn-numpad focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-[#6366F1]/12 cursor-pointer"
            @click="onKeyClick('4')"
        >
            4
        </button>
        <button
            type="button"
            class="btn-numpad focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-[#6366F1]/12 cursor-pointer"
            @click="onKeyClick('5')"
        >
            5
        </button>
        <button
            type="button"
            class="btn-numpad focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-[#6366F1]/12 cursor-pointer"
            @click="onKeyClick('6')"
        >
            6
        </button>

        <!-- Row 3: 1, 2, 3 -->
        <button
            type="button"
            class="btn-numpad focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-[#6366F1]/12 cursor-pointer"
            @click="onKeyClick('1')"
        >
            1
        </button>
        <button
            type="button"
            class="btn-numpad focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-[#6366F1]/12 cursor-pointer"
            @click="onKeyClick('2')"
        >
            2
        </button>
        <button
            type="button"
            class="btn-numpad focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-[#6366F1]/12 cursor-pointer"
            @click="onKeyClick('3')"
        >
            3
        </button>

        <!-- Row 4: 0, ., backspace -->
        <button
            type="button"
            class="btn-numpad focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-[#6366F1]/12 cursor-pointer"
            @click="onKeyClick('0')"
        >
            0
        </button>
        <button
            type="button"
            :disabled="!allowDecimal"
            class="btn-numpad focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-[#6366F1]/12 disabled:opacity-40 cursor-pointer text-2xl"
            @click="onKeyClick('.')"
            aria-label="Titik desimal"
        >
            .
        </button>
        <button
            type="button"
            class="btn-numpad focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-[#EF4444]/20 border-[#EF4444]/30 bg-[#EF4444]/10 text-[#EF4444] hover:bg-[#EF4444]/20 cursor-pointer"
            @click="onKeyClick('backspace')"
            aria-label="Hapus digit terakhir"
        >
            <Delete class="h-6 w-6" />
        </button>
    </div>
</template>
