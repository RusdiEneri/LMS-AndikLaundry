<script setup lang="ts">
import { computed } from 'vue';
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';

interface Props {
    number: number | string;
    theme?: 'light' | 'dark';
    class?: HTMLAttributes['class'];
}

const props = withDefaults(defineProps<Props>(), {
    theme: 'light',
});

const displayNumber = computed(() => String(props.number).padStart(2, '0'));
</script>

<template>
    <span
        :class="
            cn(
                'inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full border-[1.5px] font-heading text-sm font-bold select-none',
                theme === 'dark' ? 'border-white text-white' : 'border-navy-900 text-navy-900',
                props.class
            )
        "
        aria-hidden="true"
    >
        {{ displayNumber }}
    </span>
</template>
