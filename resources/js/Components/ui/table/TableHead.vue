<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';

interface Props {
    class?: HTMLAttributes['class'];
}

const props = defineProps<Props>();
</script>

<template>
    <th
        :class="
            cn(
                'h-10 bg-[#EDF3F9] px-4 text-left align-middle text-xs font-semibold uppercase tracking-wide text-text-muted select-none',
                props.class
            )
        "
    >
        <slot />
    </th>
</template>
