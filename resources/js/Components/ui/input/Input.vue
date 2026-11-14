<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';
import { useVModel } from '@vueuse/core';

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
                'flex min-h-[44px] w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-teal-500 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50',
                props.class
            )
        "
    />
</template>
