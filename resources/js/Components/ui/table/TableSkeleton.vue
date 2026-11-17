<script setup lang="ts">
import { computed } from 'vue';
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';
import { Skeleton } from '@/Components/ui/skeleton';

interface Props {
    rows?: number;
    cols?: number;
    class?: HTMLAttributes['class'];
}

const props = withDefaults(defineProps<Props>(), {
    rows: 5,
    cols: 4,
});

const rowIndices = computed(() => Array.from({ length: props.rows }, (_, i) => i));
const colIndices = computed(() => Array.from({ length: props.cols }, (_, i) => i));
</script>

<template>
    <tr v-for="row in rowIndices" :key="row" :class="cn('h-[52px]', props.class)">
        <td v-for="col in colIndices" :key="col" class="px-4 align-middle">
            <Skeleton class="h-4 w-full max-w-[160px]" />
        </td>
    </tr>
</template>
