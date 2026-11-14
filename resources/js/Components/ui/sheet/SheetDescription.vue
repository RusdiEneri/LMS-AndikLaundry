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
    <DialogDescription :class="cn('font-sans text-sm text-[#6B6B6B]', props.class)" v-bind="props">
        <slot />
    </DialogDescription>
</template>
