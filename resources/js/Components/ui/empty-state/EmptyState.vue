<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';

interface Props {
    title: string;
    description?: string;
    class?: HTMLAttributes['class'];
}

const props = defineProps<Props>();
</script>

<template>
    <div :class="cn('flex flex-col items-center justify-center px-6 py-12 text-center', props.class)">
        <div class="mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-primary-100 text-primary-700" aria-hidden="true">
            <slot name="icon">
                <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z" />
                    <path d="m3.3 7 8.7 5 8.7-5" />
                    <path d="M12 22V12" />
                </svg>
            </slot>
        </div>
        <h3 class="font-heading text-lg font-bold text-text">{{ title }}</h3>
        <p v-if="description || $slots.default" class="mt-1.5 max-w-sm text-sm text-text-muted">
            <slot>{{ description }}</slot>
        </p>
        <div v-if="$slots.action" class="mt-5">
            <slot name="action" />
        </div>
    </div>
</template>
