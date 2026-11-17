<script setup lang="ts">
import { ToastProvider, type ToastProviderProps, useForwardProps } from 'radix-vue';

interface Props extends ToastProviderProps {}

const props = withDefaults(defineProps<Props>(), {
    duration: 4000,
    swipeDirection: 'right',
});

const forwarded = useForwardProps(props);
</script>

<template>
    <ToastProvider v-bind="forwarded">
        <slot />
    </ToastProvider>
</template>
