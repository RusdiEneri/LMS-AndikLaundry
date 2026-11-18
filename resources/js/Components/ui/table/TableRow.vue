<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';

interface Props {
    class?: HTMLAttributes['class'];
}

const props = defineProps<Props>();
</script>

<template>
    <tr
        :class="
            cn(
                'h-[52px] border-b border-border transition-colors duration-[120ms] ease-standard hover:bg-bg data-[state=selected]:bg-primary-100/50',
                props.class
            )
        "
    >
        <slot />
    </tr>
</template>
