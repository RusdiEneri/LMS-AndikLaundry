<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';
import { useVModel } from '@vueuse/core';

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
                'flex min-h-[44px] w-full rounded-[6px] border border-[#E8E8EC] bg-white px-3.5 py-2.5 text-sm text-[#0A0A0A] placeholder:text-[#9C9C9C] focus-visible:outline-none focus-visible:border-[#6366F1] focus-visible:ring-3 focus-visible:ring-[#6366F1]/12 disabled:cursor-not-allowed disabled:opacity-50 transition-colors',
                props.class
            )
        "
    />
</template>
