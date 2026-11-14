<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';
import { DialogDescription, type DialogDescriptionProps } from 'radix-vue';

interface Props extends DialogDescriptionProps {
    class?: HTMLAttributes['class'];
}

const props = defineProps<Props>();
</script>

<template>
    <DialogDescription :class="cn('text-sm text-slate-500', props.class)" v-bind="props">
        <slot />
    </DialogDescription>
</template>
