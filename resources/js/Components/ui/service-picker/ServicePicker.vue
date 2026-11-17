<script setup lang="ts">
import type { Component, HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';

interface ServiceOption {
    value: string;
    label: string;
    description?: string;
    price?: string;
    icon?: Component;
    disabled?: boolean;
}

interface Props {
    options: ServiceOption[];
    columns?: 1 | 2 | 3 | 4;
    class?: HTMLAttributes['class'];
}

const props = withDefaults(defineProps<Props>(), {
    columns: 2,
});

const modelValue = defineModel<string>();

const columnsClass = {
    1: 'grid-cols-1',
    2: 'grid-cols-1 sm:grid-cols-2',
    3: 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3',
    4: 'grid-cols-2 lg:grid-cols-4',
} as const;
</script>

<template>
    <div
        role="radiogroup"
        aria-label="Pilih layanan"
        :class="cn('grid gap-3', columnsClass[props.columns], props.class)"
    >
        <label
            v-for="option in options"
            :key="option.value"
            class="group relative flex cursor-pointer items-start gap-3 rounded-md border-[1.5px] bg-surface p-4 transition-all duration-[120ms] ease-standard focus-within:ring-3 focus-within:ring-primary-500 focus-within:ring-offset-2"
            :class="
                modelValue === option.value
                    ? 'border-primary-600 bg-primary-100/60 shadow-sm'
                    : 'border-border hover:border-primary-500 hover:bg-primary-100/30'
            "
        >
            <input
                v-model="modelValue"
                type="radio"
                name="service-picker"
                :value="option.value"
                :disabled="option.disabled"
                class="sr-only"
            />
            <span
                v-if="option.icon"
                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full transition-colors"
                :class="modelValue === option.value ? 'bg-primary-600 text-white' : 'bg-primary-100 text-primary-700'"
                aria-hidden="true"
            >
                <component :is="option.icon" class="h-5 w-5" />
            </span>
            <span class="min-w-0 flex-1">
                <span class="block text-sm font-semibold text-text">{{ option.label }}</span>
                <span v-if="option.description" class="mt-0.5 block text-xs text-text-muted">{{ option.description }}</span>
                <span v-if="option.price" class="mt-1 block font-heading text-sm font-bold text-primary-700 tabular-nums">{{ option.price }}</span>
            </span>
            <span
                class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full border-[1.5px] transition-all duration-[120ms] ease-standard"
                :class="modelValue === option.value ? 'border-primary-600 bg-primary-600' : 'border-border bg-surface'"
                aria-hidden="true"
            >
                <svg v-if="modelValue === option.value" class="h-3 w-3 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20 6 9 17l-5-5" />
                </svg>
            </span>
        </label>
    </div>
</template>
