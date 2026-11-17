<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';

interface Props {
    class?: HTMLAttributes['class'];
    disabled?: boolean;
}

const props = defineProps<Props>();

const modelValue = defineModel<boolean>();
</script>

<template>
    <label :class="cn('group inline-flex cursor-pointer items-center select-none', props.class)">
        <input
            v-model="modelValue"
            type="checkbox"
            :disabled="disabled"
            class="peer sr-only"
            role="switch"
        />
        <span
            class="relative h-6 w-11 shrink-0 rounded-pill border-[1.5px] border-border bg-border transition-all duration-[200ms] ease-standard peer-focus-visible:ring-3 peer-focus-visible:ring-primary-500 peer-focus-visible:ring-offset-2 peer-checked:border-primary-600 peer-checked:bg-primary-600 peer-disabled:cursor-not-allowed peer-disabled:opacity-50"
            aria-hidden="true"
        >
            <span
                class="absolute left-0.5 top-1/2 h-4.5 w-4.5 -translate-y-1/2 rounded-full bg-surface shadow-sm transition-all duration-[200ms] ease-standard peer-checked:translate-x-5"
            />
        </span>
        <span v-if="$slots.default" class="ml-2.5 text-sm text-text peer-disabled:opacity-50">
            <slot />
        </span>
    </label>
</template>
