<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { computed } from 'vue';
import { cn } from '@/lib/utils';

export type OrderStatus =
    | 'diterima'
    | 'dicuci'
    | 'dikeringkan'
    | 'disetrika'
    | 'siap'
    | 'selesai'
    | 'dibatalkan';

interface Props {
    status: OrderStatus;
    label?: string;
    class?: HTMLAttributes['class'];
}

const props = defineProps<Props>();

const STATUS_CONFIG = {
    diterima: { bgClass: 'bg-primary-100', textClass: 'text-primary-700', dotClass: 'bg-primary-700', label: 'Diterima' },
    dicuci: { bgClass: 'bg-primary-100', textClass: 'text-primary-700', dotClass: 'bg-primary-700', label: 'Dicuci' },
    dikeringkan: { bgClass: 'bg-warning/15', textClass: 'text-warning', dotClass: 'bg-warning', label: 'Dikeringkan' },
    disetrika: { bgClass: 'bg-warning/15', textClass: 'text-warning', dotClass: 'bg-warning', label: 'Disetrika' },
    siap: { bgClass: 'bg-accent-100', textClass: 'text-accent-700', dotClass: 'bg-accent-700', label: 'Siap diambil' },
    selesai: { bgClass: 'bg-success/15', textClass: 'text-success', dotClass: 'bg-success', label: 'Selesai' },
    dibatalkan: { bgClass: 'bg-danger/10', textClass: 'text-danger', dotClass: 'bg-danger', label: 'Dibatalkan' },
} as const;

const config = computed(() => STATUS_CONFIG[props.status] ?? STATUS_CONFIG.diterima);
</script>

<template>
    <span
        :class="cn(
            'inline-flex items-center gap-1.5 rounded-pill px-3 py-1 text-xs font-semibold select-none border border-current/15',
            config.bgClass,
            config.textClass,
            props.class
        )"
    >
        <span class="h-1.5 w-1.5 rounded-pill" :class="config.dotClass" aria-hidden="true" />
        {{ label ?? config.label }}
    </span>
</template>
