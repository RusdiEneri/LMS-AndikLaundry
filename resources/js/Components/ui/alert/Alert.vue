<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';
import { CheckCircle2, XCircle, AlertTriangle, Info } from 'lucide-vue-next';

type AlertVariant = 'success' | 'error' | 'warning' | 'info';

interface Props {
    variant?: AlertVariant;
    title?: string;
    class?: HTMLAttributes['class'];
}

const props = withDefaults(defineProps<Props>(), {
    variant: 'info',
});

const config = {
    success: { icon: CheckCircle2, bg: 'bg-[#C9F0E6]', text: 'text-[#0F6F5B]', border: 'border-l-success' },
    error: { icon: XCircle, bg: 'bg-[#FDE3E4]', text: 'text-[#B3262B]', border: 'border-l-danger' },
    warning: { icon: AlertTriangle, bg: 'bg-[#FFF1D6]', text: 'text-[#8A5A00]', border: 'border-l-warning' },
    info: { icon: Info, bg: 'bg-primary-100', text: 'text-primary-700', border: 'border-l-info' },
} as const;
</script>

<template>
    <div
        role="alert"
        aria-live="polite"
        :class="cn('flex items-start gap-3 rounded-md border-l-4 px-4 py-3', config[props.variant].bg, config[props.variant].border, props.class)"
    >
        <component :is="config[props.variant].icon" class="h-5 w-5 shrink-0 mt-0.5" :class="config[props.variant].text" aria-hidden="true" />
        <div class="min-w-0">
            <p v-if="title" class="text-sm font-semibold" :class="config[props.variant].text">{{ title }}</p>
            <div class="text-sm text-text">
                <slot />
            </div>
        </div>
    </div>
</template>
