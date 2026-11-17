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
    diterima: { bg: '#E3E8F7', text: '#1F3C88', label: 'Diterima' },
    dicuci: { bg: '#D6ECFA', text: '#14598F', label: 'Dicuci' },
    dikeringkan: { bg: '#FFF1D6', text: '#8A5A00', label: 'Dikeringkan' },
    disetrika: { bg: '#FFE8D6', text: '#A84A12', label: 'Disetrika' },
    siap: { bg: '#C9F0E6', text: '#0F6F5B', label: 'Siap diambil' },
    selesai: { bg: '#E8EAF2', text: '#4A4F73', label: 'Selesai' },
    dibatalkan: { bg: '#FDE3E4', text: '#B3262B', label: 'Dibatalkan' },
} as const;

const config = computed(() => STATUS_CONFIG[props.status] ?? STATUS_CONFIG.diterima);
</script>

<template>
    <span
        :class="cn('inline-flex items-center gap-1.5 rounded-pill px-3 py-1 text-xs font-semibold select-none', props.class)"
        :style="{ backgroundColor: config.bg, color: config.text }"
    >
        <span class="h-1.5 w-1.5 rounded-full" :style="{ backgroundColor: config.text }" aria-hidden="true" />
        {{ label ?? config.label }}
    </span>
</template>
