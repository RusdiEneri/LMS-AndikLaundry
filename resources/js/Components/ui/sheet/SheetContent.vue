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
            class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0"
        />
        <DialogContent
            :class="cn(sheetVariants({ side }), props.class)"
            v-bind="{ ...forwarded, ...$attrs }"
        >
            <slot />

            <DialogClose
                class="absolute right-4 top-4 inline-flex min-h-[44px] min-w-[44px] items-center justify-center rounded-lg p-2 text-slate-500 hover:bg-slate-100 hover:text-slate-900 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2 disabled:pointer-events-none"
            >
                <X class="h-5 w-5" />
                <span class="sr-only">Tutup</span>
            </DialogClose>
        </DialogContent>
    </DialogPortal>
</template>
