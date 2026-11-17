<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';

interface Props {
    class?: HTMLAttributes['class'];
}

const props = defineProps<Props>();
</script>

<template>
    <td :class="cn('px-4 align-middle text-sm text-text', props.class)">
        <slot />
    </td>
</template>
