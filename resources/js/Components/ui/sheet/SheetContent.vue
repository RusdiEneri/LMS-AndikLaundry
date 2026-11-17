<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';
import {
    DialogClose,
    DialogContent,
    type DialogContentEmits,
    type DialogContentProps,
    DialogOverlay,
    DialogPortal,
    useForwardPropsEmits,
} from 'radix-vue';
import { X } from 'lucide-vue-next';
import { sheetVariants, type SheetVariants } from './index';

interface SheetContentProps extends DialogContentProps {
    class?: HTMLAttributes['class'];
    side?: SheetVariants['side'];
}

defineOptions({
    inheritAttrs: false,
});

const props = withDefaults(defineProps<SheetContentProps>(), {
    side: 'right',
});

const emits = defineEmits<DialogContentEmits>();
const forwarded = useForwardPropsEmits(props, emits);
</script>

<template>
    <DialogPortal>
        <DialogOverlay
            class="fixed inset-0 z-50 bg-navy-950/60 backdrop-blur-[4px] data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0"
        />
        <DialogContent
            :class="cn(sheetVariants({ side }), props.class)"
            v-bind="{ ...forwarded, ...$attrs }"
        >
            <slot />

            <DialogClose
                class="absolute right-4 top-4 inline-flex h-9 w-9 items-center justify-center rounded-md bg-bg text-text-muted hover:bg-primary-100 hover:text-text focus:outline-none focus-visible:ring-3 focus-visible:ring-primary-500 transition-all duration-[120ms] ease-standard cursor-pointer z-50"
            >
                <X class="h-4 w-4" />
                <span class="sr-only">Tutup</span>
            </DialogClose>
        </DialogContent>
    </DialogPortal>
</template>
