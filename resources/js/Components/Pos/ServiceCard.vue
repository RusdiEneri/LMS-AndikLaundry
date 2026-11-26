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
        return 'bg-primary-100 text-navy-900';
    }
    if (cat.includes('meteran') || cat.includes('karpet')) {
        return 'bg-warning/20 text-navy-950';
    }
    return 'bg-accent-100 text-navy-950';
});

function handleClick() {
    emits('select', props.service);
}
</script>

<template>
    <button
        type="button"
        @click="handleClick"
        class="min-h-[44px] flex flex-col justify-between text-left p-4 rounded-lg border border-border bg-surface shadow-xs hover:border-navy-800/40 hover:shadow-sm transition-colors duration-[150ms] ease-standard group cursor-pointer focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-primary-500 focus-visible:ring-offset-2"
    >
        <div class="w-full">
            <div class="flex items-center justify-between gap-2 mb-2.5">
                <span
                    :class="[
                        'inline-flex items-center gap-1 rounded-sm px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider',
                        categoryBadgeClass,
                    ]"
                >
                    <Scale v-if="isKiloan" class="h-3 w-3 shrink-0" aria-hidden="true" />
                    <MapPin v-else-if="isMeterService" class="h-3 w-3 shrink-0" aria-hidden="true" />
                    <Shirt v-else class="h-3 w-3 shrink-0" aria-hidden="true" />
                    <span>{{ service.category }}</span>
                </span>

                <span
                    v-if="service.min_weight && isKiloan"
                    class="text-[11px] font-mono font-medium text-text-muted tabular-nums shrink-0"
                >
                    Min {{ service.min_weight }} kg
                </span>
            </div>

            <h4 class="font-heading font-bold text-sm text-navy-950 group-hover:text-navy-800 transition-colors line-clamp-2 leading-snug">
                {{ service.name }}
            </h4>

            <p class="text-xs text-text-muted mt-1">
                Estimasi {{ service.estimated_hours }} jam
                <span v-if="isMeterService" class="font-semibold text-navy-950"> · Input P × L (m²)</span>
            </p>
        </div>

        <div class="mt-4 pt-3 border-t border-border flex items-center justify-between w-full">
            <span class="font-mono font-bold text-navy-950 text-sm tabular-nums">
                {{ formatRupiah(service.price) }}<span class="text-xs text-text-muted font-normal">/{{ service.unit }}</span>
            </span>

            <span class="h-8 w-8 rounded-md border border-border bg-bg text-navy-800 group-hover:bg-navy-800 group-hover:text-white group-hover:border-navy-800 transition-colors flex items-center justify-center shrink-0">
                <Plus class="h-4 w-4" aria-hidden="true" />
            </span>
        </div>
    </button>
</template>
