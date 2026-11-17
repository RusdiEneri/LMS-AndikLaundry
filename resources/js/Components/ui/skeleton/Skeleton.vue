<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';

interface Props {
    class?: HTMLAttributes['class'];
}

const props = defineProps<Props>();
</script>

<template>
    <div
        :class="cn('rounded-lg bg-border', props.class)"
        aria-hidden="true"
    >
        <div class="skeleton-shimmer h-full w-full rounded-lg" />
    </div>
</template>
