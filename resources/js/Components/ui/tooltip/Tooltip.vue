<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';

interface Props {
    content: string;
    class?: HTMLAttributes['class'];
}

const props = defineProps<Props>();

const visible = ref(false);
const wrapper = ref<HTMLElement | null>(null);
let showTimer: ReturnType<typeof setTimeout> | null = null;
let hideTimer: ReturnType<typeof setTimeout> | null = null;

function show() {
    if (hideTimer) { clearTimeout(hideTimer); hideTimer = null; }
    showTimer = setTimeout(() => { visible.value = true; }, 300);
}

function hide() {
    if (showTimer) { clearTimeout(showTimer); showTimer = null; }
    hideTimer = setTimeout(() => { visible.value = false; }, 100);
}

onMounted(() => {
    wrapper.value?.addEventListener('mouseenter', show);
    wrapper.value?.addEventListener('mouseleave', hide);
    wrapper.value?.addEventListener('focusin', show);
    wrapper.value?.addEventListener('focusout', hide);
});

onUnmounted(() => {
    if (showTimer) clearTimeout(showTimer);
    if (hideTimer) clearTimeout(hideTimer);
});
</script>

<template>
    <span ref="wrapper" class="relative inline-flex">
        <slot />
        <Transition
            enter-active-class="transition-opacity duration-[200ms] ease-standard"
            enter-from-class="opacity-0"
            leave-active-class="transition-opacity duration-[120ms] ease-standard"
            leave-to-class="opacity-0"
        >
            <span
                v-if="visible"
                role="tooltip"
                :class="cn('pointer-events-none absolute bottom-full left-1/2 z-50 mb-2 -translate-x-1/2 whitespace-nowrap rounded-sm bg-navy-900 px-2.5 py-1.5 text-xs font-medium text-white shadow-md', props.class)"
            >
                {{ content }}
            </span>
        </Transition>
    </span>
</template>
