<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';
import { ToastRoot as RadixToastRoot, type ToastRootEmits, type ToastRootProps, useForwardPropsEmits } from 'radix-vue';
import { CheckCircle2, XCircle, AlertTriangle, Info } from 'lucide-vue-next';

type ToastVariant = 'success' | 'error' | 'warning' | 'info';

interface Props extends ToastRootProps {
    variant?: ToastVariant;
    class?: HTMLAttributes['class'];
}

const props = withDefaults(defineProps<Props>(), {
    variant: 'info',
});

const emits = defineEmits<ToastRootEmits>();
const forwarded = useForwardPropsEmits(props, emits);

const iconMap = { success: CheckCircle2, error: XCircle, warning: AlertTriangle, info: Info } as const;
const iconColorMap = {
    success: 'text-success',
    error: 'text-danger',
    warning: 'text-warning',
    info: 'text-info',
} as const;
</script>

<template>
    <RadixToastRoot
        v-bind="forwarded"
        :class="
            cn(
                'flex items-start gap-3 rounded-md border border-border bg-surface px-4 py-3 shadow-lg data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0 data-[state=closed]:zoom-out-95 data-[state=open]:zoom-in-95 data-[swipe=end]:animate-out data-[swipe=end]:slide-out-to-right-full data-[swipe=move]:translate-x-[var(--radix-toast-swipe-move-x)]',
                props.class
            )
        "
    >
        <component :is="iconMap[props.variant]" class="h-5 w-5 shrink-0 mt-0.5" :class="iconColorMap[props.variant]" aria-hidden="true" />
        <div class="min-w-0 flex-1">
            <ToastTitle v-if="$slots.title">
                <slot name="title" />
            </ToastTitle>
            <ToastDescription v-if="$slots.default">
                <slot />
            </ToastDescription>
        </div>
        <ToastClose />
    </RadixToastRoot>
</template>
