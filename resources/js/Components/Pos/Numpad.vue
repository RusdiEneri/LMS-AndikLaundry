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
    <div class="grid grid-cols-3 gap-1.5 select-none w-full">
        <!-- Row 1: 7, 8, 9 -->
        <button
            type="button"
            class="btn-numpad cursor-pointer"
            @click="onKeyClick('7')"
        >
            7
        </button>
        <button
            type="button"
            class="btn-numpad cursor-pointer"
            @click="onKeyClick('8')"
        >
            8
        </button>
        <button
            type="button"
            class="btn-numpad cursor-pointer"
            @click="onKeyClick('9')"
        >
            9
        </button>

        <!-- Row 2: 4, 5, 6 -->
        <button
            type="button"
            class="btn-numpad cursor-pointer"
            @click="onKeyClick('4')"
        >
            4
        </button>
        <button
            type="button"
            class="btn-numpad cursor-pointer"
            @click="onKeyClick('5')"
        >
            5
        </button>
        <button
            type="button"
            class="btn-numpad cursor-pointer"
            @click="onKeyClick('6')"
        >
            6
        </button>

        <!-- Row 3: 1, 2, 3 -->
        <button
            type="button"
            class="btn-numpad cursor-pointer"
            @click="onKeyClick('1')"
        >
            1
        </button>
        <button
            type="button"
            class="btn-numpad cursor-pointer"
            @click="onKeyClick('2')"
        >
            2
        </button>
        <button
            type="button"
            class="btn-numpad cursor-pointer"
            @click="onKeyClick('3')"
        >
            3
        </button>

        <!-- Row 4: 0, ., backspace -->
        <button
            type="button"
            class="btn-numpad cursor-pointer"
            @click="onKeyClick('0')"
        >
            0
        </button>
        <button
            type="button"
            :disabled="!allowDecimal"
            class="btn-numpad disabled:opacity-30 cursor-pointer text-xl"
            @click="onKeyClick('.')"
            aria-label="Titik desimal"
        >
            .
        </button>
        <button
            type="button"
            class="btn-numpad border-2 border-black bg-[#FF5252] text-white hover:bg-[#E02424] cursor-pointer shadow-[2px_2px_0px_#000]"
            @click="onKeyClick('backspace')"
            aria-label="Hapus digit terakhir"
        >
            <Delete class="h-5 w-5" />
        </button>
    </div>
</template>
