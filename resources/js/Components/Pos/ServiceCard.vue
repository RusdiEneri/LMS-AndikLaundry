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
        return 'bg-[#6366F1]/10 text-[#6366F1] border-[#6366F1]/20';
    }
    if (cat.includes('meteran')) {
        return 'bg-[#6366F1]/10 text-[#6366F1] border-[#6366F1]/20';
    }
    return 'bg-[#F59E0B]/10 text-[#F59E0B] border-[#F59E0B]/20';
});

function handleClick() {
    emits('select', props.service);
}
</script>

<template>
    <button
        type="button"
        @click="handleClick"
        class="min-h-[44px] flex flex-col justify-between text-left p-4 rounded-[12px] border border-[#E8E8EC] bg-white shadow-2xs hover:border-[#6366F1] hover:shadow-[0_8px_30px_rgba(0,0,0,0.08)] active:scale-98 transition-all group focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-[#6366F1]/12 relative cursor-pointer"
    >
        <div>
            <!-- Category & Badge Header -->
            <div class="flex items-center justify-between mb-1.5 flex-wrap gap-1">
                <span
                    :class="[
                        'text-[10px] font-bold px-2 py-0.5 rounded-[4px] border flex items-center space-x-1',
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
                    class="text-[10px] text-[#F59E0B] bg-[#F59E0B]/10 border border-[#F59E0B]/20 px-1.5 py-0.5 rounded-[4px] font-mono font-bold"
                >
                    Min {{ service.min_weight }} kg
                </span>
            </div>

            <!-- Service Name -->
            <h4 class="font-bold text-sm text-[#0A0A0A] group-hover:text-[#6366F1] transition-colors line-clamp-2 mt-1.5">
                {{ service.name }}
            </h4>

            <p class="text-[11px] text-[#6B6B6B] mt-0.5">
                Estimasi: {{ service.estimated_hours }} Jam
            </p>

            <!-- Unit type indicator -->
            <p v-if="isMeterService" class="text-[10px] text-[#6366F1] font-medium mt-0.5">
                Input P x L (m²)
            </p>
        </div>

        <!-- Price & Action Footer -->
        <div class="mt-3 pt-2.5 border-t border-[#E8E8EC] flex items-center justify-between">
            <span class="font-mono font-bold text-[#6366F1] text-sm">
                {{ formatRupiah(service.price) }}
                <span class="text-xs text-[#6B6B6B] font-normal">/{{ service.unit }}</span>
            </span>

            <span class="h-8 w-8 rounded-[6px] bg-[#6366F1]/10 text-[#6366F1] flex items-center justify-center group-hover:bg-[#6366F1] group-hover:text-white transition-colors">
                <Plus class="h-4 w-4" />
            </span>
        </div>
    </button>
</template>
