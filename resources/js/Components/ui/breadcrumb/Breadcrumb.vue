<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';

interface Props {
    class?: HTMLAttributes['class'];
}

const props = defineProps<Props>();
</script>

<template>
    <nav aria-label="breadcrumb" :class="cn('flex', props.class)">
        <ol class="flex flex-wrap items-center gap-1.5 text-[13px] text-text-muted">
            <slot />
        </ol>
    </nav>
</template>
