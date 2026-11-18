<script setup lang="ts">
import { computed } from 'vue';
import { Plus, Scale, Shirt, MapPin } from 'lucide-vue-next';
import { formatRupiah } from '@/Support/whatsapp';

export interface ServiceItem {
    id: number;
    name: string;
    category: string;
    category_slug?: string;
    price: number;
    unit: string;
    min_weight: number | null;
    estimated_hours: number;
    code?: string;
    is_meter_service?: boolean;
    tiers?: Array<{
        id?: number;
        tier_name: string;
        tier_code: string;
        multiplier_factor: number;
        flat_surcharge: number;
        duration_hours: number;
    }>;
}

interface Props {
    service: ServiceItem;
}

const props = defineProps<Props>();

const emits = defineEmits<{
    (e: 'select', service: ServiceItem): void;
}>();

const isKiloan = computed(() => {
    return props.service.unit.toLowerCase() === 'kg' || props.service.category.toLowerCase().includes('kiloan');
});

const isMeterService = computed(() => {
    return props.service.unit.toLowerCase() === 'm²' || 
           props.service.category.toLowerCase().includes('meteran') ||
           props.service.is_meter_service === true ||
           props.service.name.toLowerCase().includes('karpet');
});

const categoryBadgeClass = computed(() => {
    const cat = props.service.category.toLowerCase();
    if (cat.includes('kiloan')) {
        return 'bg-primary-100 text-primary-700';
    }
    if (cat.includes('meteran') || cat.includes('karpet')) {
        return 'bg-warning/15 text-warning border border-warning/20';
    }
    return 'bg-accent-100 text-accent-700';
});

function handleClick() {
    emits('select', props.service);
}
</script>

<template>
    <button
        type="button"
        @click="handleClick"
        class="min-h-[44px] flex flex-col justify-between text-left p-4 rounded-lg border border-border bg-surface shadow-sm hover:shadow-md hover:border-primary-500 hover:-translate-y-0.5 active:translate-y-0 transition-all duration-[200ms] ease-standard group relative cursor-pointer focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-primary-500 focus-visible:ring-offset-2"
    >
        <div>
            <div class="flex items-center justify-between mb-2 flex-wrap gap-1">
                <span
                    :class="[
                        'overline-label px-2.5 py-0.5 rounded-pill flex items-center space-x-1',
                        categoryBadgeClass,
                    ]"
                >
                    <Scale v-if="isKiloan" class="h-3 w-3 inline" />
                    <MapPin v-else-if="isMeterService" class="h-3 w-3 inline" />
                    <Shirt v-else class="h-3 w-3 inline" />
                    <span>{{ service.category }}</span>
                </span>

                <span
                    v-if="service.min_weight && isKiloan"
                    class="text-[10px] text-primary-700 bg-primary-100 px-2 py-0.5 rounded-pill font-mono font-semibold"
                >
                    Min {{ service.min_weight }} kg
                </span>
            </div>

            <h4 class="font-heading font-bold text-sm text-text group-hover:text-primary-700 transition-colors line-clamp-2 mt-1">
                {{ service.name }}
            </h4>

            <p class="text-[11px] text-text-muted font-medium mt-1">
                Estimasi: {{ service.estimated_hours }} Jam
            </p>

            <p v-if="isMeterService" class="text-[10px] text-warning font-bold mt-1">
                Input P × L (m²)
            </p>
        </div>

        <div class="mt-4 pt-3 border-t border-border flex items-center justify-between w-full">
            <span class="font-mono font-bold text-text text-sm tabular-nums">
                {{ formatRupiah(service.price) }}
                <span class="text-xs text-text-muted font-normal">/{{ service.unit }}</span>
            </span>

            <span class="h-8 w-8 rounded-md bg-primary-100 text-primary-700 group-hover:bg-primary-600 group-hover:text-white transition-colors flex items-center justify-center font-bold">
                <Plus class="h-4 w-4" />
            </span>
        </div>
    </button>
</template>
