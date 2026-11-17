<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';
import { ToastClose as RadixToastClose, type ToastCloseProps } from 'radix-vue';
import { X } from 'lucide-vue-next';

interface Props extends ToastCloseProps {
    class?: HTMLAttributes['class'];
}

const props = defineProps<Props>();
</script>

<template>
    <RadixToastClose
        :class="
            cn(
                'absolute right-3 top-3 inline-flex h-7 w-7 items-center justify-center rounded-md text-text-muted hover:bg-primary-100 hover:text-text focus:outline-none focus-visible:ring-3 focus-visible:ring-primary-500 transition-all duration-[120ms] ease-standard cursor-pointer',
                props.class
            )
        "
        v-bind="props"
    >
        <X class="h-3.5 w-3.5" aria-hidden="true" />
        <span class="sr-only">Tutup</span>
    </RadixToastClose>
</template>
