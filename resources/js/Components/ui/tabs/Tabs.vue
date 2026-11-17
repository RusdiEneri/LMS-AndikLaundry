<script setup lang="ts">
import { TabsRoot, type TabsRootEmits, type TabsRootProps, useForwardPropsEmits } from 'radix-vue';

interface Props extends TabsRootProps {}

const props = defineProps<Props>();
const emits = defineEmits<TabsRootEmits>();

const forwarded = useForwardPropsEmits(props, emits);
</script>

<template>
    <TabsRoot v-bind="forwarded">
        <slot />
    </TabsRoot>
</template>
