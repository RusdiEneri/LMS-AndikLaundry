<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';
import { Separator, type SeparatorProps } from 'radix-vue';

interface Props extends SeparatorProps {
    class?: HTMLAttributes['class'];
}

const props = withDefaults(defineProps<Props>(), {
    orientation: 'horizontal',
    decorative: true,
});
</script>

<template>
    <Separator
        :decorative="decorative"
        :orientation="orientation"
        :class="
            cn(
                'shrink-0 bg-border',
                orientation === 'horizontal' ? 'h-[1px] w-full' : 'h-full w-[1px]',
                props.class
            )
        "
    />
</template>
