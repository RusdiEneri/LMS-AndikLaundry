<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';
import { DialogTitle, type DialogTitleProps } from 'radix-vue';

interface Props extends DialogTitleProps {
    class?: HTMLAttributes['class'];
}

const props = defineProps<Props>();
</script>

<template>
    <DialogTitle :class="cn('font-display text-lg font-bold text-[#0A0A0A]', props.class)" v-bind="props">
        <slot />
    </DialogTitle>
</template>
