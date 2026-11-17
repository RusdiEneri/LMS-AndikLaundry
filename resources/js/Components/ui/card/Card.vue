<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';

const props = defineProps<{
    class?: HTMLAttributes['class'];
}>();
</script>

<template>
    <div :class="cn('rounded-2xl border border-slate-200/80 bg-white text-slate-900 shadow-sm hover:shadow-md transition-all duration-200 overflow-hidden', props.class)">
        <slot />
    </div>
</template>
