<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';
import { TabsList as RadixTabsList, type TabsListProps } from 'radix-vue';

interface Props extends TabsListProps {
    class?: HTMLAttributes['class'];
}

const props = defineProps<Props>();
</script>

<template>
    <RadixTabsList
        :class="
            cn(
                'inline-flex items-center gap-1 border-b border-border',
                props.class
            )
        "
        v-bind="props"
    >
        <slot />
    </RadixTabsList>
</template>
