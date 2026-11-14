<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';

const props = defineProps<{
    class?: HTMLAttributes['class'];
}>();
</script>

<template>
    <div :class="cn('rounded-[12px] border border-[#E8E8EC] bg-white text-[#0A0A0A] transition-all duration-200 overflow-hidden', props.class)">
        <slot />
    </div>
</template>
