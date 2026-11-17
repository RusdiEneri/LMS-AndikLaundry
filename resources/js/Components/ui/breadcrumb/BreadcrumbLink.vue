<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';

interface Props {
    href: string;
    class?: HTMLAttributes['class'];
}

const props = defineProps<Props>();
</script>

<template>
    <a
        :href="href"
        :class="cn('text-[13px] text-text-muted transition-colors hover:text-primary-700 focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-primary-500 rounded-sm', props.class)"
    >
        <slot />
    </a>
</template>
