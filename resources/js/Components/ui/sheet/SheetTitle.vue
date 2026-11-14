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
    <DialogTitle :class="cn('text-lg font-semibold text-slate-900', props.class)" v-bind="props">
        <slot />
    </DialogTitle>
</template>
