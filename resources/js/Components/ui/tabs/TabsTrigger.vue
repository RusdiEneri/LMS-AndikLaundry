<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';
import { TabsTrigger as RadixTabsTrigger, type TabsTriggerProps } from 'radix-vue';

interface Props extends TabsTriggerProps {
    class?: HTMLAttributes['class'];
}

const props = defineProps<Props>();
</script>

<template>
    <RadixTabsTrigger
        :class="
            cn(
                'inline-flex items-center justify-center whitespace-nowrap border-b-2 border-transparent px-4 py-2.5 text-sm font-medium text-text-muted transition-all duration-[120ms] ease-standard hover:text-text focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-primary-500 focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 data-[state=active]:border-primary-600 data-[state=active]:text-text cursor-pointer select-none',
                props.class
            )
        "
        v-bind="props"
    >
        <slot />
    </RadixTabsTrigger>
</template>
