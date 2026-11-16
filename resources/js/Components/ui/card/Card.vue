<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';

const props = defineProps<{
    class?: HTMLAttributes['class'];
}>();
</script>

<template>
    <div :class="cn('rounded-[10px] border-[3px] border-black bg-white text-black shadow-[4px_4px_0px_#000] transition-all duration-150 overflow-hidden', props.class)">
        <slot />
    </div>
</template>
