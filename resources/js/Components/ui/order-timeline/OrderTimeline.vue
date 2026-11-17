<script setup lang="ts">
import { computed } from 'vue';
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';
import { Check } from 'lucide-vue-next';

export type TimelineStepStatus = 'done' | 'active' | 'pending';

interface Props {
    steps: { label: string; status: TimelineStepStatus }[];
    orientation?: 'vertical' | 'horizontal';
    class?: HTMLAttributes['class'];
}

const props = withDefaults(defineProps<Props>(), {
    orientation: 'horizontal',
});

const isVertical = computed(() => props.orientation === 'vertical');
</script>

<template>
    <ol
        :class="
            cn(
                isVertical ? 'flex flex-col' : 'flex flex-row items-start',
                props.class
            )
        "
    >
        <li
            v-for="(step, index) in steps"
            :key="step.label"
            :class="cn('relative flex', isVertical ? 'gap-3 pb-6 last:pb-0' : 'flex-1 flex-col items-center')"
        >
            <div v-if="isVertical && index < steps.length - 1" aria-hidden="true" class="absolute left-[11px] top-6 h-[calc(100%-24px)] w-[1.5px]" :class="step.status === 'done' ? 'bg-primary-600' : 'bg-border'" />
            <div
                :class="
                    cn(
                        'z-10 flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-[10px] font-bold transition-all duration-[200ms] ease-standard',
                        isVertical ? '' : 'mb-2',
                        step.status === 'done' && 'bg-primary-600 text-white',
                        step.status === 'active' && 'ring-2 ring-accent-500 ring-offset-2 bg-primary-100 text-primary-700',
                        step.status === 'pending' && 'bg-border text-text-muted'
                    )
                "
                aria-current="step"
            >
                <Check v-if="step.status === 'done'" class="h-3.5 w-3.5" aria-hidden="true" />
                <span v-else>{{ index + 1 }}</span>
            </div>
            <div :class="cn(isVertical ? '' : 'text-center')">
                <p
                    class="text-xs font-semibold"
                    :class="
                        step.status === 'done' ? 'text-primary-700' : step.status === 'active' ? 'text-text' : 'text-text-muted'
                    "
                >
                    {{ step.label }}
                </p>
                <slot name="meta" :step="step" :index="index" />
            </div>
            <div v-if="!isVertical && index < steps.length - 1" aria-hidden="true" class="absolute top-3 left-1/2 h-[1.5px] w-full" :class="step.status === 'done' ? 'bg-primary-600' : 'bg-border'" />
        </li>
    </ol>
</template>
