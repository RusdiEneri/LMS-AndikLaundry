<script setup lang="ts">
import { computed } from 'vue';
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';
import { ChevronLeft, ChevronRight } from 'lucide-vue-next';

interface Props {
    currentPage: number;
    totalPages: number;
    class?: HTMLAttributes['class'];
}

const props = defineProps<Props>();

const emits = defineEmits<{
    (e: 'page-change', page: number): void;
}>();

const visiblePages = computed(() => {
    const total = props.totalPages;
    const current = props.currentPage;
    const pages: (number | 'ellipsis')[] = [];

    if (total <= 7) {
        for (let i = 1; i <= total; i++) pages.push(i);
        return pages;
    }

    pages.push(1);
    if (current > 3) pages.push('ellipsis');
    for (let i = Math.max(2, current - 1); i <= Math.min(total - 1, current + 1); i++) {
        pages.push(i);
    }
    if (current < total - 2) pages.push('ellipsis');
    pages.push(total);

    return pages;
});

function goTo(page: number) {
    if (page >= 1 && page <= props.totalPages && page !== props.currentPage) {
        emits('page-change', page);
    }
}
</script>

<template>
    <nav v-if="totalPages > 1" aria-label="Pagination" :class="cn('flex items-center justify-center gap-1.5', props.class)">
        <button
            type="button"
            :disabled="currentPage <= 1"
            class="inline-flex h-8 w-8 items-center justify-center rounded-pill border border-border bg-surface text-text-muted hover:bg-primary-100 hover:text-text focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-primary-500 disabled:pointer-events-none disabled:opacity-50 transition-all duration-[120ms] ease-standard cursor-pointer"
            aria-label="Halaman sebelumnya"
            @click="goTo(currentPage - 1)"
        >
            <ChevronLeft class="h-4 w-4" aria-hidden="true" />
        </button>

        <template v-for="(page, index) in visiblePages" :key="index">
            <span v-if="page === 'ellipsis'" class="inline-flex h-8 w-8 items-center justify-center text-sm text-text-muted select-none">…</span>
            <button
                v-else
                type="button"
                :aria-current="page === currentPage ? 'page' : undefined"
                class="inline-flex h-8 min-w-8 items-center justify-center rounded-pill px-2.5 text-sm font-medium transition-all duration-[120ms] ease-standard focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-primary-500 cursor-pointer"
                :class="page === currentPage
                    ? 'bg-primary-700 text-white'
                    : 'border border-border bg-surface text-text-muted hover:bg-primary-100 hover:text-text'"
                @click="goTo(page)"
            >
                {{ page }}
            </button>
        </template>

        <button
            type="button"
            :disabled="currentPage >= totalPages"
            class="inline-flex h-8 w-8 items-center justify-center rounded-pill border border-border bg-surface text-text-muted hover:bg-primary-100 hover:text-text focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-primary-500 disabled:pointer-events-none disabled:opacity-50 transition-all duration-[120ms] ease-standard cursor-pointer"
            aria-label="Halaman berikutnya"
            @click="goTo(currentPage + 1)"
        >
            <ChevronRight class="h-4 w-4" aria-hidden="true" />
        </button>
    </nav>
</template>
