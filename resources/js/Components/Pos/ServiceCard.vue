<script setup lang="ts">
import { computed } from 'vue';
import { Plus, Sparkles, Scale, Shirt, MapPin } from 'lucide-vue-next';
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
        return 'bg-blue-50 text-blue-700 border-blue-200';
    }
    if (cat.includes('meteran')) {
        return 'bg-purple-50 text-purple-700 border-purple-200';
    }
    return 'bg-amber-50 text-amber-700 border-amber-200';
});

function handleClick() {
    emits('select', props.service);
}
</script>

<template>
    <button
        type="button"
        @click="handleClick"
        class="min-h-[44px] flex flex-col justify-between text-left p-3.5 rounded-xl border border-slate-200 bg-white shadow-2xs hover:border-teal-500 hover:shadow-md active:scale-98 transition-all group focus:outline-none focus:ring-2 focus:ring-teal-500 relative"
    >
        <div>
            <!-- Category & Badge Header -->
            <div class="flex items-center justify-between mb-1.5 flex-wrap gap-1">
                <span
                    :class="[
                        'text-[10px] font-bold px-2 py-0.5 rounded-md border flex items-center space-x-1',
                        categoryBadgeClass,
                    ]"
                >
                    <Scale v-if="isKiloan" class="h-3 w-3 inline" />
                    <MapPin v-else-if="isMeterService" class="h-3 w-3 inline" />
                    <Shirt v-else class="h-3 w-3 inline" />
                    <span>{{ service.category }}</span>
                </span>

                <!-- Min weight tag (for kiloan only) -->
                <span
                    v-if="service.min_weight && isKiloan"
                    class="text-[10px] text-amber-800 bg-amber-50 border border-amber-200 px-1.5 py-0.5 rounded-md font-mono font-bold"
                >
                    Min {{ service.min_weight }} kg
                </span>
            </div>

            <!-- Service Name -->
            <h4 class="font-bold text-sm text-slate-900 group-hover:text-teal-700 transition-colors line-clamp-2 mt-1">
                {{ service.name }}
            </h4>

            <p class="text-[11px] text-slate-500 mt-0.5">
                Estimasi: {{ service.estimated_hours }} Jam
            </p>

            <!-- Unit type indicator -->
            <p v-if="isMeterService" class="text-[10px] text-purple-700 font-medium mt-0.5">
                Input P x L (m²)
            </p>
        </div>

        <!-- Price & Action Footer -->
        <div class="mt-3 pt-2 border-t border-slate-100 flex items-center justify-between">
            <span class="font-mono font-bold text-teal-800 text-sm">
                {{ formatRupiah(service.price) }}
                <span class="text-xs text-slate-500 font-normal">/{{ service.unit }}</span>
            </span>

            <span class="h-8 w-8 rounded-lg bg-teal-50 text-teal-700 flex items-center justify-center group-hover:bg-teal-600 group-hover:text-white transition-colors">
                <Plus class="h-4 w-4" />
            </span>
        </div>
    </button>
</template>
