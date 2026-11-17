<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';

interface Props {
    class?: HTMLAttributes['class'];
}

const props = defineProps<Props>();
</script>

<template>
    <span role="presentation" aria-hidden="true" :class="cn('text-text-muted/60 select-none', props.class)">
        /
    </span>
</template>
