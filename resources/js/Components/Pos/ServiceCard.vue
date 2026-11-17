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
        return 'bg-[#E6E9FD] text-[#26338A] border border-[#26338A]/20';
    }
    if (cat.includes('meteran') || cat.includes('karpet')) {
        return 'bg-amber-50 text-amber-800 border border-amber-200';
    }
    return 'bg-emerald-50 text-emerald-700 border border-emerald-200';
});

function handleClick() {
    emits('select', props.service);
}
</script>

<template>
    <button
        type="button"
        @click="handleClick"
        class="min-h-[44px] flex flex-col justify-between text-left p-4 rounded-2xl border border-slate-200/90 bg-white shadow-xs hover:shadow-md hover:border-[#4F5FE8]/50 hover:-translate-y-0.5 active:translate-y-0 transition-all duration-200 group relative cursor-pointer"
    >
        <div>
            <!-- Category & Badge Header -->
            <div class="flex items-center justify-between mb-2 flex-wrap gap-1">
                <span
                    :class="[
                        'text-[10px] font-bold uppercase tracking-wider px-2.5 py-0.5 rounded-full flex items-center space-x-1',
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
                    class="text-[10px] text-[#26338A] bg-[#E6E9FD] px-2 py-0.5 rounded-full font-mono font-bold"
                >
                    Min {{ service.min_weight }} kg
                </span>
            </div>

            <!-- Service Name -->
            <h4 class="font-heading font-bold text-sm text-slate-900 group-hover:text-[#4F5FE8] transition-colors line-clamp-2 mt-1">
                {{ service.name }}
            </h4>

            <p class="text-[11px] text-slate-500 font-medium mt-1">
                Estimasi: {{ service.estimated_hours }} Jam
            </p>

            <!-- Unit type indicator -->
            <p v-if="isMeterService" class="text-[10px] text-amber-800 font-bold mt-1">
                Input P × L (m²)
            </p>
        </div>

        <!-- Price & Action Footer -->
        <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between w-full">
            <span class="font-mono font-bold text-slate-900 text-sm">
                {{ formatRupiah(service.price) }}
                <span class="text-xs text-slate-500 font-normal">/{{ service.unit }}</span>
            </span>

            <span class="h-8 w-8 rounded-xl bg-[#E6E9FD] text-[#4F5FE8] group-hover:bg-[#4F5FE8] group-hover:text-white transition-colors flex items-center justify-center font-bold shadow-xs">
                <Plus class="h-4 w-4" />
            </span>
        </div>
    </button>
</template>
