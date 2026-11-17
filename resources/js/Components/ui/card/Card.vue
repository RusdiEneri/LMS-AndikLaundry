<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';

const props = defineProps<{
    class?: HTMLAttributes['class'];
}>();
</script>

<template>
    <div :class="cn('rounded-lg border border-border bg-surface text-text shadow-md transition-all duration-[200ms] ease-standard overflow-hidden', props.class)">
        <slot />
    </div>
</template>
