<script setup lang="ts">
import { Delete } from 'lucide-vue-next';

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
        <button
            v-for="key in ['7', '8', '9', '4', '5', '6', '1', '2', '3']"
            :key="key"
            type="button"
            class="inline-flex h-12 items-center justify-center rounded-md border border-border bg-surface font-heading text-base font-bold text-text hover:bg-primary-100 hover:border-primary-500 active:scale-[0.98] focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-primary-500 transition-all duration-[120ms] ease-standard cursor-pointer shadow-sm"
            @click="onKeyClick(key)"
        >
            {{ key }}
        </button>

        <button
            type="button"
            class="inline-flex h-12 items-center justify-center rounded-md border border-border bg-surface font-heading text-base font-bold text-text hover:bg-primary-100 hover:border-primary-500 active:scale-[0.98] focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-primary-500 transition-all duration-[120ms] ease-standard cursor-pointer shadow-sm"
            @click="onKeyClick('0')"
        >
            0
        </button>
        <button
            type="button"
            :disabled="!allowDecimal"
            class="inline-flex h-12 items-center justify-center rounded-md border border-border bg-surface text-xl text-text hover:bg-primary-100 hover:border-primary-500 active:scale-[0.98] focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-primary-500 disabled:pointer-events-none disabled:opacity-30 transition-all duration-[120ms] ease-standard cursor-pointer shadow-sm"
            @click="onKeyClick('.')"
            aria-label="Titik desimal"
        >
            .
        </button>
        <button
            type="button"
            class="inline-flex h-12 items-center justify-center rounded-md border border-danger bg-danger text-white hover:bg-danger/90 active:scale-[0.98] focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-primary-500 transition-all duration-[120ms] ease-standard cursor-pointer shadow-sm"
            @click="onKeyClick('backspace')"
            aria-label="Hapus digit terakhir"
        >
            <Delete class="h-5 w-5" />
        </button>
    </div>
</template>
