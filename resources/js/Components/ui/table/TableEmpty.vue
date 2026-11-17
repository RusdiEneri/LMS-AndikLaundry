<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';
import { computed } from 'vue';

interface Props {
    colspan?: number;
    class?: HTMLAttributes['class'];
}

const props = withDefaults(defineProps<Props>(), {
    colspan: 1,
});

const span = computed(() => String(props.colspan));
</script>

<template>
    <tr :class="cn('hover:bg-transparent', props.class)">
        <td :colspan="span" class="px-4 py-2">
            <slot />
        </td>
    </tr>
</template>
