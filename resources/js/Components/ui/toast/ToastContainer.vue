<script setup lang="ts">
import { watch } from 'vue';
import { ToastProviderRoot, ToastRoot, ToastViewport } from './index';
import { useToast } from './useToast';

const { toasts, dismiss } = useToast();

watch(
    toasts,
    (list) => {
        list.forEach((t) => {
            if (t.id) {
                setTimeout(() => dismiss(t.id), 4000);
            }
        });
    },
    { deep: true }
);
</script>

<template>
    <ToastProviderRoot>
        <ToastRoot
            v-for="toast in toasts"
            :key="toast.id"
            :variant="toast.variant"
            @update:open="(open: boolean) => !open && dismiss(toast.id)"
        >
            <template v-if="toast.title" #title>{{ toast.title }}</template>
            <template v-if="toast.description" #default>{{ toast.description }}</template>
        </ToastRoot>
        <ToastViewport />
    </ToastProviderRoot>
</template>
