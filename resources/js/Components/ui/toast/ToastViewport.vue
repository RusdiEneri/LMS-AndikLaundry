<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';
import { ToastViewport as RadixToastViewport, type ToastViewportProps } from 'radix-vue';

interface Props extends ToastViewportProps {
    class?: HTMLAttributes['class'];
}

const props = defineProps<Props>();
</script>

<template>
    <RadixToastViewport
        :class="
            cn(
                'fixed top-4 right-4 z-[100] flex w-[calc(100vw-2rem)] max-w-[380px] flex-col gap-2 outline-none',
                props.class
            )
        "
        v-bind="props"
    />
</template>
