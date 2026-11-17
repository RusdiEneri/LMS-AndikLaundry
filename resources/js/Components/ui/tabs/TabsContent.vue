<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';
import { TabsContent as RadixTabsContent, type TabsContentProps } from 'radix-vue';

interface Props extends TabsContentProps {
    class?: HTMLAttributes['class'];
}

const props = defineProps<Props>();
</script>

<template>
    <RadixTabsContent
        :class="cn('mt-4 focus-visible:outline-none', props.class)"
        v-bind="props"
    >
        <slot />
    </RadixTabsContent>
</template>
