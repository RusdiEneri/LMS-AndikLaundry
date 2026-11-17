<script setup lang="ts">
import type { Component, HTMLAttributes } from 'vue';
import { computed } from 'vue';
import { cn } from '@/lib/utils';
import { TrendingUp, TrendingDown } from 'lucide-vue-next';

interface Props {
    label: string;
    value: string | number;
    icon?: Component;
    delta?: number;
    deltaLabel?: string;
    class?: HTMLAttributes['class'];
}

const props = defineProps<Props>();

const deltaConfig = computed(() => {
    if (props.delta === undefined) return null;
    return {
        up: props.delta >= 0,
        text: props.delta >= 0 ? 'text-success' : 'text-danger',
        Icon: props.delta >= 0 ? TrendingUp : TrendingDown,
    };
});
</script>

<template>
    <div :class="cn('rounded-lg border border-border bg-surface p-5 shadow-md', props.class)">
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
                <p class="overline-label text-text-muted">{{ label }}</p>
                <p class="mt-2 font-heading text-2xl font-bold text-text tabular-nums">{{ value }}</p>
                <p v-if="deltaConfig" class="mt-1.5 flex items-center gap-1 text-xs font-medium" :class="deltaConfig.text">
                    <component :is="deltaConfig.Icon" class="h-3.5 w-3.5" aria-hidden="true" />
                    <span>{{ (delta ?? 0) >= 0 ? '+' : '' }}{{ delta }}%{{ deltaLabel ? ` ${deltaLabel}` : '' }}</span>
                </p>
            </div>
            <div v-if="icon" class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-primary-100 text-primary-700" aria-hidden="true">
                <component :is="icon" class="h-5 w-5" />
            </div>
        </div>
    </div>
</template>
