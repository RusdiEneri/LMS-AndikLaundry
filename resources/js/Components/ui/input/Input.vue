<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';

interface Props {
    defaultValue?: string | number;
    modelValue?: string | number;
    class?: HTMLAttributes['class'];
}

const props = defineProps<Props>();

const emits = defineEmits<{
    (e: 'update:modelValue', payload: string | number): void;
}>();

const modelValue = defineModel<string | number>();
</script>

<template>
    <input
        v-model="modelValue"
        :class="
            cn(
                'flex min-h-[44px] w-full rounded-[8px] border-[2.5px] border-black bg-white px-3.5 py-2.5 text-sm font-bold text-black shadow-[3px_3px_0px_#000] placeholder:text-gray-500 placeholder:font-normal focus-visible:outline-none focus-visible:border-black focus-visible:shadow-[4px_4px_0px_#2196F3] disabled:cursor-not-allowed disabled:opacity-50 transition-all',
                props.class
            )
        "
    />
</template>
