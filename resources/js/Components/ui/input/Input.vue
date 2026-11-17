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
                'flex h-11 w-full rounded-md border border-border bg-surface px-4 py-2 text-sm font-medium text-text placeholder:text-text-muted focus-visible:outline-none focus-visible:border-primary-600 focus-visible:ring-3 focus-visible:ring-primary-100 disabled:cursor-not-allowed disabled:opacity-50 transition-all duration-[120ms] ease-standard',
                props.class
            )
        "
    />
</template>
