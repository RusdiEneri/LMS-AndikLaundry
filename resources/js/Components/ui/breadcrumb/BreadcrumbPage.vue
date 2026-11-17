<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';

interface Props {
    class?: HTMLAttributes['class'];
}

const props = defineProps<Props>();
</script>

<template>
    <span aria-current="page" :class="cn('text-[13px] font-medium text-text', props.class)">
        <slot />
    </span>
</template>
