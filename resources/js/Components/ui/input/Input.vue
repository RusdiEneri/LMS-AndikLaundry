<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';

interface Props {
    defaultValue?: string | number;
    modelValue?: string | number;
    class?: HTMLAttributes['class'];
}

const props = defineProps<Props>();

const emits = defineEmits<{
    (e: 'update:modelValue', payload: string | number): void;
}>();

const modelValue = defineModel<string | number>();
</script>

<template>
    <input
        v-model="modelValue"
        :class="
            cn(
                'flex min-h-[42px] w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-sm font-medium text-slate-900 placeholder:text-slate-400 focus-visible:outline-none focus-visible:border-[#4F5FE8] focus-visible:ring-3 focus-visible:ring-[#4F5FE8]/20 disabled:cursor-not-allowed disabled:opacity-50 transition-all shadow-xs',
                props.class
            )
        "
    />
</template>
