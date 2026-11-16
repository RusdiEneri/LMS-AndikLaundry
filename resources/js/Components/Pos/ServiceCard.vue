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
        return 'bg-[#FFEB3B] text-black border-2 border-black shadow-[1px_1px_0px_#000]';
    }
    if (cat.includes('meteran')) {
        return 'bg-[#2196F3] text-black border-2 border-black shadow-[1px_1px_0px_#000]';
    }
    return 'bg-[#22C55E] text-black border-2 border-black shadow-[1px_1px_0px_#000]';
});

function handleClick() {
    emits('select', props.service);
}
</script>

<template>
    <button
        type="button"
        @click="handleClick"
        class="min-h-[44px] flex flex-col justify-between text-left p-4 rounded-[10px] border-[2.5px] border-black bg-white shadow-[4px_4px_0px_#000] hover:shadow-[6px_6px_0px_#000] hover:bg-[#FFF9D2]/30 active:translate-x-0.5 active:translate-y-0.5 active:shadow-[2px_2px_0px_#000] transition-all group relative cursor-pointer"
    >
        <div>
            <!-- Category & Badge Header -->
            <div class="flex items-center justify-between mb-1.5 flex-wrap gap-1">
                <span
                    :class="[
                        'text-[10px] font-black uppercase tracking-wider px-2 py-0.5 rounded-[4px] border flex items-center space-x-1',
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
                    class="text-[10px] text-black bg-[#FFEB3B] border border-black px-1.5 py-0.5 rounded-[4px] font-mono font-black shadow-[1px_1px_0px_#000]"
                >
                    Min {{ service.min_weight }} kg
                </span>
            </div>

            <!-- Service Name -->
            <h4 class="font-black text-sm text-black group-hover:text-black transition-colors line-clamp-2 mt-1.5">
                {{ service.name }}
            </h4>

            <p class="text-[11px] font-bold text-black/60 mt-0.5">
                Estimasi: {{ service.estimated_hours }} Jam
            </p>

            <!-- Unit type indicator -->
            <p v-if="isMeterService" class="text-[10px] text-black font-black mt-0.5">
                Input P x L (m²)
            </p>
        </div>

        <!-- Price & Action Footer -->
        <div class="mt-3 pt-2.5 border-t-2 border-black/20 flex items-center justify-between w-full">
            <span class="font-mono font-black text-black text-sm">
                {{ formatRupiah(service.price) }}
                <span class="text-xs text-black/60 font-bold">/{{ service.unit }}</span>
            </span>

            <span class="h-8 w-8 rounded-[6px] border-2 border-black bg-[#FFEB3B] group-hover:bg-[#FDE047] text-black flex items-center justify-center font-black shadow-[2px_2px_0px_#000] transition-colors">
                <Plus class="h-4 w-4 stroke-[3]" />
            </span>
        </div>
    </button>
</template>
