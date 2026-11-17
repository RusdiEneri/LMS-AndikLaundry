<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';
import { ToastDescription as RadixToastDescription, type ToastDescriptionProps } from 'radix-vue';

interface Props extends ToastDescriptionProps {
    class?: HTMLAttributes['class'];
}

const props = defineProps<Props>();
</script>

<template>
    <RadixToastDescription :class="cn('text-sm text-text-muted', props.class)" v-bind="props">
        <slot />
    </RadixToastDescription>
</template>
