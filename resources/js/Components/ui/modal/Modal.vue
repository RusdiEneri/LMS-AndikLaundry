<script setup lang="ts">
import { DialogRoot, type DialogRootEmits, type DialogRootProps, useForwardPropsEmits } from 'radix-vue';

interface Props extends DialogRootProps {}

const props = defineProps<Props>();
const emits = defineEmits<DialogRootEmits>();

const forwarded = useForwardPropsEmits(props, emits);
</script>

<template>
    <DialogRoot v-bind="forwarded">
        <slot />
    </DialogRoot>
</template>
