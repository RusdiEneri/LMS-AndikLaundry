<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';
import { ToastTitle as RadixToastTitle, type ToastTitleProps } from 'radix-vue';

interface Props extends ToastTitleProps {
    class?: HTMLAttributes['class'];
}

const props = defineProps<Props>();
</script>

<template>
    <RadixToastTitle :class="cn('text-sm font-semibold text-text', props.class)" v-bind="props">
        <slot />
    </RadixToastTitle>
</template>
