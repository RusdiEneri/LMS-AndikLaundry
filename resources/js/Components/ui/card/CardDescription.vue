<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';

const props = defineProps<{
    class?: HTMLAttributes['class'];
}>();
</script>

<template>
    <p :class="cn('font-sans text-sm text-[#6B6B6B]', props.class)">
        <slot />
    </p>
</template>
