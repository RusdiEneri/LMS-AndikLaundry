<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';

interface Props {
    class?: HTMLAttributes['class'];
    rows?: number | string;
    placeholder?: string;
    disabled?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    rows: 3,
});

const modelValue = defineModel<string>();
</script>

<template>
    <textarea
        v-model="modelValue"
        :rows="rows"
        :placeholder="placeholder"
        :disabled="disabled"
        :class="
            cn(
                'flex w-full rounded-md border border-border bg-surface px-4 py-2.5 text-sm font-medium text-text placeholder:text-text-muted focus-visible:outline-none focus-visible:border-primary-600 focus-visible:ring-3 focus-visible:ring-primary-100 disabled:cursor-not-allowed disabled:opacity-50 transition-all duration-[120ms] ease-standard',
                props.class
            )
        "
    />
</template>
