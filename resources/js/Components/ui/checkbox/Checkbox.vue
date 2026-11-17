<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';
import { Check } from 'lucide-vue-next';

interface Props {
    class?: HTMLAttributes['class'];
    disabled?: boolean;
}

const props = defineProps<Props>();

const modelValue = defineModel<boolean>();
</script>

<template>
    <label :class="cn('group inline-flex cursor-pointer items-center gap-2.5 select-none', props.class)">
        <input
            v-model="modelValue"
            type="checkbox"
            :disabled="disabled"
            class="peer sr-only"
        />
        <span
            class="flex h-5 w-5 shrink-0 items-center justify-center rounded-sm border-[1.5px] border-border bg-surface transition-all duration-[120ms] ease-standard peer-focus-visible:ring-3 peer-focus-visible:ring-primary-500 peer-focus-visible:ring-offset-2 peer-checked:border-primary-600 peer-checked:bg-primary-600 peer-checked:[&>svg]:opacity-100 peer-disabled:cursor-not-allowed peer-disabled:opacity-50"
            aria-hidden="true"
        >
            <Check class="h-3.5 w-3.5 text-white opacity-0 transition-opacity" />
        </span>
        <span v-if="$slots.default" class="text-sm text-text peer-disabled:opacity-50">
            <slot />
        </span>
    </label>
</template>
