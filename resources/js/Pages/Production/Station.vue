<script setup lang="ts">
import { ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Card, CardHeader, CardTitle, CardDescription, CardContent } from '@/Components/ui/card';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import {
    Shirt,
    Wind,
    CheckCircle2,
    RotateCcw,
    AlertTriangle,
    Clock,
    Zap,
    Play,
    Check,
    Cpu,
    ArrowRight,
} from 'lucide-vue-next';

interface QueueItem {
    order_code: string;
    customer_name: string;
    service: string;
    weight_or_qty: string;
    perfume?: string;
    tier?: string;
    machine?: string;
    remaining_minutes?: number;
    rack_target?: string;
    notes?: string | null;
    next_action: string;
}

interface MachineItem {
    id: string;
    name: string;
    status: 'idle' | 'running' | 'maintenance';
    status_label: string;
    remaining_minutes: number;
    load_kg: number;
}

interface Props {
    workstation_queues?: {
        washing_queue: QueueItem[];
        drying_queue: QueueItem[];
        ironing_packing_queue: QueueItem[];
    };
    machines?: {
        washers: MachineItem[];
        dryers: MachineItem[];
    };
    filters?: {
        active: string;
        options: string[];
    };
    perfume_variants?: string[];
}

const props = withDefaults(defineProps<Props>(), {
    workstation_queues: () => ({
        washing_queue: [
            {
                order_code: 'ORD-1042',
                customer_name: 'Budi S.',
                service: 'Cuci Komplit',
                weight_or_qty: '4.50 kg',
                perfume: 'Lavender',
                tier: 'Reguler',
                notes: null,
                next_action: 'MULAI CUCI',
            },
            {
                order_code: 'ORD-1043',
                customer_name: 'Maya K.',
                service: 'Satuan (Bedcover King)',
                weight_or_qty: '1 pcs',
                perfume: 'Sakura',
                tier: 'Kilat',
                notes: 'Noda Kerah (Spotting)',
                next_action: 'MULAI CUCI',
            },
        ],
        drying_queue: [
            {
                order_code: 'ORD-1040',
                customer_name: 'Dewi R.',
                service: 'Cuci Kering',
                weight_or_qty: '6.20 kg',
                machine: 'Mesin Dryer #02',
                remaining_minutes: 24,
                next_action: 'PINDAH SETRIKA',
            },
            {
                order_code: 'ORD-1041',
                customer_name: 'Joko W.',
                service: 'Cuci Komplit',
                weight_or_qty: '3.10 kg',
                machine: 'Mesin Dryer #01',
                remaining_minutes: 12,
                next_action: 'PINDAH SETRIKA',
            },
        ],
        ironing_packing_queue: [
            {
                order_code: 'ORD-1038',
                customer_name: 'Ahmad F.',
                service: 'Setrika Uap',
                weight_or_qty: '3.80 kg',
                perfume: 'Sakura',
                rack_target: 'Rak A-04',
                next_action: 'SIAP AMBIL / RAK',
            },
            {
                order_code: 'ORD-1039',
                customer_name: 'Rina S.',
                service: 'Jas Pria (Dry Clean)',
                weight_or_qty: '2 pcs',
                perfume: 'Snappy',
                rack_target: 'Hanger #12',
                next_action: 'SIAP AMBIL / RAK',
            },
        ],
    }),
    machines: () => ({
        washers: [
            { id: 'W-01', name: 'Mesin Cuci #01', status: 'running', status_label: 'Berjalan', remaining_minutes: 18, load_kg: 7.0 },
            { id: 'W-02', name: 'Mesin Cuci #02', status: 'idle', status_label: 'Standby / Kosong', remaining_minutes: 0, load_kg: 0.0 },
            { id: 'W-03', name: 'Mesin Cuci #03', status: 'running', status_label: 'Berjalan', remaining_minutes: 35, load_kg: 10.0 },
        ],
        dryers: [
            { id: 'D-01', name: 'Mesin Dryer #01', status: 'running', status_label: 'Berjalan', remaining_minutes: 12, load_kg: 8.0 },
            { id: 'D-02', name: 'Mesin Dryer #02', status: 'running', status_label: 'Berjalan', remaining_minutes: 24, load_kg: 8.0 },
        ],
    }),
    filters: () => ({
        active: 'semua',
        options: ['semua', 'kiloan', 'satuan', 'kilat'],
    }),
    perfume_variants: () => ['Lavender', 'Sakura', 'Snappy', 'Ocean Fresh', 'Non-Parfum'],
});

const activeFilter = ref(props.filters.active);

// Interactive Action Toast State
const activeNotice = ref<string | null>(null);

function triggerProgress(orderCode: string, action: string) {
    activeNotice.value = `Order ${orderCode} berhasil dipindahkan: [${action}]`;
    setTimeout(() => {
        if (activeNotice.value?.includes(orderCode)) {
            activeNotice.value = null;
        }
    }, 4000);
}

function triggerRewash(orderCode: string) {
    activeNotice.value = `Order ${orderCode} dijadwalkan untuk CUCI ULANG (Free Rewash Defect Protection)`;
    setTimeout(() => {
        if (activeNotice.value?.includes(orderCode)) {
            activeNotice.value = null;
        }
    }, 4000);
}

function getPerfumeBadgeVariant(perfume?: string): 'teal' | 'rose' | 'amber' | 'cyan' | 'secondary' {
    switch (perfume) {
        case 'Lavender':
            return 'teal';
        case 'Sakura':
            return 'rose';
        case 'Snappy':
            return 'amber';
        case 'Ocean Fresh':
            return 'cyan';
        default:
            return 'secondary';
    }
}
</script>

<template>
    <AppLayout title="Stasiun Produksi">
        <div class="space-y-6">
            <!-- Header & Telemetry Status -->
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 bg-surface p-5 rounded-lg border border-border shadow-sm">
                <div>
                    <div class="flex items-center space-x-2">
                        <h1 class="text-2xl font-heading font-bold text-text tracking-tight">Stasiun Produksi & Antrean Cuci</h1>
                        <Badge variant="teal" class="text-xs uppercase font-bold">Produksi</Badge>
                    </div>
                    <p class="text-sm font-medium text-text-muted mt-1">
                        Pelacakan proses cucian fisik, waktu putar mesin cuci & dryer, serta penempatan rak.
                    </p>
                </div>

                <!-- Machine Telemetry Status Summary -->
                <div class="flex flex-wrap items-center gap-3">
                    <div class="flex items-center space-x-2 bg-bg border border-border rounded-pill px-3.5 py-2 text-xs shadow-sm">
                        <Cpu class="h-4 w-4 text-text-muted" />
                        <span class="font-bold text-text">Mesin Cuci:</span>
                        <span class="font-mono text-primary-700 font-bold">2/3 Berjalan</span>
                    </div>

                    <div class="flex items-center space-x-2 bg-bg border border-border rounded-pill px-3.5 py-2 text-xs shadow-sm">
                        <Wind class="h-4 w-4 text-text-muted" />
                        <span class="font-bold text-text">Dryer:</span>
                        <span class="font-mono text-accent-700 font-bold">2/2 Berjalan</span>
                    </div>
                </div>
            </div>

            <!-- Operator Action Notice Banner -->
            <div
                v-if="activeNotice"
                class="p-4 rounded-lg bg-accent-100 border border-accent-500/30 text-accent-700 flex items-center justify-between shadow-sm animate-in fade-in-0"
            >
                <div class="flex items-center space-x-2.5">
                    <CheckCircle2 class="h-5 w-5 text-success shrink-0" />
                    <span class="text-sm font-bold">{{ activeNotice }}</span>
                </div>
                <button
                    type="button"
                    class="text-accent-700 hover:underline min-h-[44px] min-w-[44px] inline-flex items-center justify-center font-bold text-xs cursor-pointer"
                    @click="activeNotice = null"
                >
                    Tutup
                </button>
            </div>

            <!-- Machine Telemetry Status Bar -->
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">
                <div
                    v-for="washer in machines.washers"
                    :key="washer.id"
                    class="p-3.5 rounded-lg border border-border bg-surface shadow-sm space-y-1.5"
                    :class="washer.status === 'running' ? 'bg-primary-100/40 border-primary-500/40' : 'bg-surface'"
                >
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-text">{{ washer.name }}</span>
                        <span
                            class="h-2.5 w-2.5 rounded-full"
                            :class="washer.status === 'running' ? 'bg-success ring-2 ring-success/30' : 'bg-border'"
                        ></span>
                    </div>
                    <div class="flex items-center justify-between text-xs">
                        <Badge :variant="washer.status === 'running' ? 'default' : 'outline'" class="text-[10px]">
                            {{ washer.status_label }}
                        </Badge>
                        <span v-if="washer.status === 'running'" class="font-mono text-xs font-bold text-primary-700">
                            {{ washer.remaining_minutes }}m
                        </span>
                    </div>
                </div>

                <div
                    v-for="dryer in machines.dryers"
                    :key="dryer.id"
                    class="p-3.5 rounded-lg border border-border bg-surface shadow-sm space-y-1.5"
                    :class="dryer.status === 'running' ? 'bg-accent-100/40 border-accent-500/40' : 'bg-surface'"
                >
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-text">{{ dryer.name }}</span>
                        <span
                            class="h-2.5 w-2.5 rounded-full"
                            :class="dryer.status === 'running' ? 'bg-accent-600 ring-2 ring-accent-500/30' : 'bg-border'"
                        ></span>
                    </div>
                    <div class="flex items-center justify-between text-xs">
                        <Badge :variant="dryer.status === 'running' ? 'teal' : 'outline'" class="text-[10px]">
                            {{ dryer.status_label }}
                        </Badge>
                        <span v-if="dryer.status === 'running'" class="font-mono text-xs font-bold text-accent-700">
                            {{ dryer.remaining_minutes }}m
                        </span>
                    </div>
                </div>
            </div>

            <!-- 3-Column Workstation Kanban Queues -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                <!-- Column 1: Antrian Cuci (Washing Queue) -->
                <div class="flex flex-col space-y-3 bg-bg p-4 rounded-lg border border-border shadow-sm">
                    <div class="flex items-center justify-between pb-2.5 border-b border-border">
                        <div class="flex items-center space-x-2">
                            <div class="h-8 w-8 rounded-md bg-warning/20 text-warning flex items-center justify-center font-bold">
                                <Shirt class="h-4 w-4" />
                            </div>
                            <h3 class="font-heading font-bold text-sm text-text">1. Antrian Cuci</h3>
                        </div>
                        <Badge variant="default" class="text-xs font-mono font-bold">
                            {{ workstation_queues.washing_queue.length }} Nota
                        </Badge>
                    </div>

                    <div class="space-y-3">
                        <div
                            v-for="order in workstation_queues.washing_queue"
                            :key="order.order_code"
                            class="bg-surface p-4 rounded-lg border border-border shadow-sm space-y-3 hover:shadow-md transition-shadow"
                        >
                            <div class="flex items-start justify-between">
                                <div>
                                    <span class="font-mono font-bold text-xs text-primary-700">{{ order.order_code }}</span>
                                    <h4 class="font-heading font-bold text-sm text-text">{{ order.customer_name }}</h4>
                                    <span class="text-xs font-medium text-text-muted">{{ order.service }}</span>
                                </div>
                                <Badge variant="outline" class="font-mono font-bold text-xs">
                                    {{ order.weight_or_qty }}
                                </Badge>
                            </div>

                            <!-- Tier Badge (if any) -->
                            <div v-if="order.tier === 'Kilat'" class="flex flex-wrap items-center gap-1.5 pt-1">
                                <Badge variant="danger" class="text-[11px] font-bold">
                                    Kilat (24 Jam)
                                </Badge>
                            </div>

                            <!-- Special Notes / Defect Warning -->
                            <div v-if="order.notes" class="p-2.5 rounded-md bg-warning/15 border border-warning/30 text-warning text-xs font-medium flex items-center space-x-1.5">
                                <AlertTriangle class="h-4 w-4 text-warning shrink-0" />
                                <span>{{ order.notes }}</span>
                            </div>

                            <!-- Action Buttons -->
                            <div class="pt-2 border-t border-border flex items-center gap-2">
                                <Button
                                    type="button"
                                    variant="secondary"
                                    class="flex-1 min-h-[42px] font-bold text-xs shadow-sm uppercase tracking-wider"
                                    @click="triggerProgress(order.order_code, order.next_action)"
                                >
                                    <Play class="h-4 w-4 mr-1.5" />
                                    <span>{{ order.next_action }}</span>
                                </Button>

                                <button
                                    type="button"
                                    title="Jadwalkan Cuci Ulang"
                                    class="inline-flex min-h-[42px] min-w-[42px] items-center justify-center rounded-md border border-border bg-surface p-2 text-text-muted hover:bg-danger/10 hover:text-danger shadow-sm transition-colors cursor-pointer"
                                    @click="triggerRewash(order.order_code)"
                                    aria-label="Cuci ulang"
                                >
                                    <RotateCcw class="h-4 w-4" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Column 2: Proses Pengeringan (Drying Queue) -->
                <div class="flex flex-col space-y-3 bg-bg p-4 rounded-lg border border-border shadow-sm">
                    <div class="flex items-center justify-between pb-2.5 border-b border-border">
                        <div class="flex items-center space-x-2">
                            <div class="h-8 w-8 rounded-md bg-primary-100 text-primary-700 flex items-center justify-center font-bold">
                                <Wind class="h-4 w-4" />
                            </div>
                            <h3 class="font-heading font-bold text-sm text-text">2. Proses Pengeringan</h3>
                        </div>
                        <Badge variant="outline" class="text-xs font-mono font-bold">
                            {{ workstation_queues.drying_queue.length }} Mesin
                        </Badge>
                    </div>

                    <div class="space-y-3">
                        <div
                            v-for="order in workstation_queues.drying_queue"
                            :key="order.order_code"
                            class="bg-surface p-4 rounded-lg border border-border shadow-sm space-y-3 hover:shadow-md transition-shadow"
                        >
                            <div class="flex items-start justify-between">
                                <div>
                                    <span class="font-mono font-bold text-xs text-primary-700">{{ order.order_code }}</span>
                                    <h4 class="font-heading font-bold text-sm text-text">{{ order.customer_name }}</h4>
                                    <span class="text-xs font-medium text-text-muted">{{ order.service }}</span>
                                </div>
                                <Badge variant="outline" class="font-mono font-bold text-xs">
                                    {{ order.weight_or_qty }}
                                </Badge>
                            </div>

                            <!-- Machine Assignment & Timer -->
                            <div class="p-2.5 rounded-md bg-primary-100/60 border border-primary-500/30 flex items-center justify-between text-xs text-primary-700 font-semibold">
                                <span class="flex items-center space-x-1.5">
                                    <Wind class="h-4 w-4 text-primary-600" />
                                    <span>{{ order.machine }}</span>
                                </span>
                                <span class="font-mono font-bold text-primary-700 flex items-center space-x-1">
                                    <Clock class="h-3.5 w-3.5" />
                                    <span>Sisa {{ order.remaining_minutes }} Menit</span>
                                </span>
                            </div>

                            <!-- Action Button -->
                            <div class="pt-2 border-t border-border">
                                <Button
                                    type="button"
                                    variant="secondary"
                                    class="w-full min-h-[42px] font-bold text-xs shadow-sm uppercase tracking-wider"
                                    @click="triggerProgress(order.order_code, order.next_action)"
                                >
                                    <ArrowRight class="h-4 w-4 mr-1.5" />
                                    <span>{{ order.next_action }}</span>
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Column 3: Setrika & Packing / Rak (Ironing & Packing Queue) -->
                <div class="flex flex-col space-y-3 bg-bg p-4 rounded-lg border border-border shadow-sm">
                    <div class="flex items-center justify-between pb-2.5 border-b border-border">
                        <div class="flex items-center space-x-2">
                            <div class="h-8 w-8 rounded-md bg-accent-100 text-accent-700 flex items-center justify-center font-bold">
                                <CheckCircle2 class="h-4 w-4" />
                            </div>
                            <h3 class="font-heading font-bold text-sm text-text">3. Setrika & Packing</h3>
                        </div>
                        <Badge variant="default" class="text-xs font-mono font-bold">
                            {{ workstation_queues.ironing_packing_queue.length }} Nota
                        </Badge>
                    </div>

                    <div class="space-y-3">
                        <div
                            v-for="order in workstation_queues.ironing_packing_queue"
                            :key="order.order_code"
                            class="bg-surface p-4 rounded-lg border border-border shadow-sm space-y-3 hover:shadow-md transition-shadow"
                        >
                            <div class="flex items-start justify-between">
                                <div>
                                    <span class="font-mono font-bold text-xs text-primary-700">{{ order.order_code }}</span>
                                    <h4 class="font-heading font-bold text-sm text-text">{{ order.customer_name }}</h4>
                                    <span class="text-xs font-medium text-text-muted">{{ order.service }}</span>
                                </div>
                                <Badge variant="outline" class="font-mono font-bold text-xs">
                                    {{ order.weight_or_qty }}
                                </Badge>
                            </div>

                            <!-- Target Rack / Hanger Badge -->
                            <div class="flex items-center justify-end text-xs pt-1">
                                <span class="font-mono font-bold text-accent-700 bg-accent-100 border border-accent-500/30 px-2.5 py-0.5 rounded-pill text-xs">
                                    Target: {{ order.rack_target }}
                                </span>
                            </div>

                            <!-- Action Button -->
                            <div class="pt-2 border-t border-border">
                                <Button
                                    type="button"
                                    variant="default"
                                    class="w-full min-h-[42px] font-bold text-xs shadow-sm uppercase tracking-wider"
                                    @click="triggerProgress(order.order_code, order.next_action)"
                                >
                                    <Check class="h-4 w-4 mr-1.5" />
                                    <span>{{ order.next_action }}</span>
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
