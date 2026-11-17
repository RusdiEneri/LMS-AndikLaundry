<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';

interface Props {
    class?: HTMLAttributes['class'];
    disabled?: boolean;
}

const props = defineProps<Props>();

const modelValue = defineModel<string>();
</script>

<template>
    <select
        v-model="modelValue"
        :disabled="disabled"
        :class="
            cn(
                'flex h-11 w-full appearance-none rounded-md border border-border bg-surface px-4 py-2 text-sm font-medium text-text focus-visible:outline-none focus-visible:border-primary-600 focus-visible:ring-3 focus-visible:ring-primary-100 disabled:cursor-not-allowed disabled:opacity-50 transition-all duration-[120ms] ease-standard',
                props.class
            )
        "
    >
        <slot />
    </select>
</template>
