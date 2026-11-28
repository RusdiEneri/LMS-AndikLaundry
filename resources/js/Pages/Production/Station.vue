<script setup lang="ts">
import { computed, ref } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import type { PageProps } from '@/types';
import { formatRupiah } from '@/Support/whatsapp';
import {
    Search,
    CheckCircle2,
    PackageCheck,
    Clock,
    Truck,
    ExternalLink,
    AlertCircle,
    Shirt,
    Store,
} from 'lucide-vue-next';

type StageFilter = 'active' | 'diproses' | 'selesai' | 'sudah_diambil';

interface StationOrder {
    id: number;
    invoice_number: string;
    customer_name: string;
    customer_phone?: string | null;
    items_summary: string;
    stage: 'diproses' | 'selesai' | 'sudah_diambil';
    status_label: string;
    delivery_option: 'pickup' | 'delivery' | string;
    payment_status: string;
    outstanding_balance: number;
    can_release: boolean;
    intake_label: string;
    target_label?: string | null;
    notes?: string | null;
}

interface Props {
    orders: StationOrder[];
    summary: {
        processing_count: number;
        ready_count: number;
        picked_up_today_count: number;
    };
}

const props = withDefaults(defineProps<Props>(), {
    orders: () => [],
    summary: () => ({
        processing_count: 0,
        ready_count: 0,
        picked_up_today_count: 0,
    }),
});

const page = usePage<PageProps>();
const canAccessPos = computed(() => {
    const role = page.props.auth?.user?.role ?? 'operator';
    return role === 'owner' || role === 'cashier';
});

const activeFilter = ref<StageFilter>('active');
const searchQuery = ref('');
const updatingOrderId = ref<number | null>(null);
const feedback = ref<{ type: 'success' | 'error'; message: string } | null>(null);

const filterTabs: { key: StageFilter; label: string }[] = [
    { key: 'active', label: 'Semua Aktif' },
    { key: 'diproses', label: 'Diproses' },
    { key: 'selesai', label: 'Siap Ambil' },
    { key: 'sudah_diambil', label: 'Diambil Hari Ini' },
];

const filteredOrders = computed(() => {
    const q = searchQuery.value.trim().toLowerCase();

    return props.orders.filter((order) => {
        const matchesStage =
            activeFilter.value === 'active'
                ? order.stage === 'diproses' || order.stage === 'selesai'
                : order.stage === activeFilter.value;

        if (!matchesStage) {
            return false;
        }

        if (!q) {
            return true;
        }

        return (
            order.invoice_number.toLowerCase().includes(q) ||
            order.customer_name.toLowerCase().includes(q) ||
            order.items_summary.toLowerCase().includes(q)
        );
    });
});

async function transitionOrder(order: StationOrder, toStatus: 'selesai' | 'sudah_diambil') {
    if (updatingOrderId.value !== null) {
        return;
    }

    updatingOrderId.value = order.id;
    feedback.value = null;

    try {
        const csrfToken = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content ?? '';
        const res = await fetch(`/production/orders/${order.id}/status`, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify({ to_status: toStatus }),
        });

        const payload = await res.json().catch(() => ({}));

        if (!res.ok) {
            feedback.value = {
                type: 'error',
                message: payload.message ?? `Gagal memperbarui status nota ${order.invoice_number}.`,
            };
            return;
        }

        feedback.value = {
            type: 'success',
            message:
                toStatus === 'selesai'
                    ? `Nota ${order.invoice_number} ditandai selesai dan siap diambil.`
                    : `Nota ${order.invoice_number} sudah diserahkan ke pelanggan.`,
        };

        router.reload({ only: ['orders', 'summary'] });
    } catch {
        feedback.value = {
            type: 'error',
            message: 'Terjadi gangguan jaringan saat memperbarui progres cucian.',
        };
    } finally {
        updatingOrderId.value = null;
    }
}

function stageBadgeClass(stage: StationOrder['stage']): string {
    switch (stage) {
        case 'selesai':
            return 'bg-accent-100 text-navy-950 border-accent-500/35';
        case 'sudah_diambil':
            return 'bg-bg text-text-muted border-border';
        default:
            return 'bg-primary-100 text-navy-900 border-primary-500/25';
    }
}
</script>

<template>
    <AppLayout title="Progres Cucian">
        <div class="space-y-5">
            <!-- Header Band (Consistent with Dashboard Band 1) -->
            <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-primary-700">
                        Operasional
                    </p>
                    <h1 class="mt-1 font-heading text-2xl sm:text-3xl font-extrabold tracking-tight text-navy-950">
                        Progres Cucian
                    </h1>
                    <p class="mt-1 text-sm text-text-muted">
                        Pantau cucian masuk, tandai selesai, dan konfirmasi pengambilan pelanggan.
                    </p>
                </div>

                <Link
                    v-if="canAccessPos"
                    href="/pos"
                    class="inline-flex min-h-[42px] items-center justify-center gap-2 rounded-md bg-navy-800 px-4 text-xs font-bold text-white shadow-xs transition-colors hover:bg-navy-700 focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-primary-500 self-start sm:self-auto"
                >
                    <Store class="h-4 w-4" aria-hidden="true" />
                    <span>Buka Kasir POS</span>
                </Link>
            </div>

            <!-- 3-Column Summary Strip (Consistent with Dashboard Band 3) -->
            <div class="grid grid-cols-1 divide-y divide-border rounded-lg border border-border bg-surface shadow-xs md:grid-cols-3 md:divide-x md:divide-y-0">
                <div class="flex items-center justify-between gap-3 p-4 sm:px-5">
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-[0.12em] text-text-muted">
                            Sedang Diproses
                        </p>
                        <p class="mt-1 font-mono text-xl font-bold text-navy-950 tabular-nums">
                            {{ summary.processing_count }} <span class="text-xs font-medium text-text-muted">nota</span>
                        </p>
                    </div>
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-md bg-primary-100 text-navy-800">
                        <Shirt class="h-4 w-4" aria-hidden="true" />
                    </div>
                </div>

                <div class="flex items-center justify-between gap-3 p-4 sm:px-5">
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-[0.12em] text-text-muted">
                            Selesai · Siap Ambil
                        </p>
                        <p class="mt-1 font-mono text-xl font-bold text-navy-950 tabular-nums">
                            {{ summary.ready_count }} <span class="text-xs font-medium text-text-muted">nota</span>
                        </p>
                    </div>
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-md bg-accent-100 text-accent-700">
                        <CheckCircle2 class="h-4 w-4" aria-hidden="true" />
                    </div>
                </div>

                <div class="flex items-center justify-between gap-3 p-4 sm:px-5">
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-[0.12em] text-text-muted">
                            Diambil Hari Ini
                        </p>
                        <p class="mt-1 font-mono text-xl font-bold text-navy-950 tabular-nums">
                            {{ summary.picked_up_today_count }} <span class="text-xs font-medium text-text-muted">nota</span>
                        </p>
                    </div>
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-md border border-border bg-bg text-text-muted">
                        <PackageCheck class="h-4 w-4" aria-hidden="true" />
                    </div>
                </div>
            </div>

            <!-- Feedback Banner -->
            <div
                v-if="feedback"
                role="status"
                class="flex items-center justify-between gap-3 rounded-lg border p-3.5 text-xs font-semibold"
                :class="
                    feedback.type === 'success'
                        ? 'border-accent-500/35 bg-accent-100 text-navy-950'
                        : 'border-danger/30 bg-danger/10 text-danger'
                "
            >
                <div class="flex items-center gap-2">
                    <CheckCircle2 v-if="feedback.type === 'success'" class="h-4 w-4 shrink-0 text-accent-700" aria-hidden="true" />
                    <AlertCircle v-else class="h-4 w-4 shrink-0" aria-hidden="true" />
                    <span>{{ feedback.message }}</span>
                </div>
                <button
                    type="button"
                    class="min-h-[36px] px-2.5 text-xs font-bold underline cursor-pointer"
                    @click="feedback = null"
                >
                    Tutup
                </button>
            </div>

            <!-- Search & Status Filter Bar -->
            <div class="rounded-lg border border-border bg-surface p-4 shadow-xs space-y-3">
                <div class="relative">
                    <Search class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-500" aria-hidden="true" />
                    <label for="station-order-search" class="sr-only">Cari nota atau pelanggan</label>
                    <input
                        id="station-order-search"
                        name="order_search"
                        v-model="searchQuery"
                        type="search"
                        placeholder="Cari nomor nota, nama pelanggan, atau layanan..."
                        class="flex min-h-[42px] w-full rounded-md border border-border bg-bg pl-10 pr-4 py-2 text-sm font-medium text-text placeholder:text-slate-500 focus:bg-surface focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-600 transition-colors"
                    />
                </div>

                <div class="flex flex-wrap items-center gap-1.5" role="tablist" aria-label="Filter status cucian">
                    <button
                        v-for="tab in filterTabs"
                        :key="tab.key"
                        type="button"
                        role="tab"
                        :aria-selected="activeFilter === tab.key"
                        @click="activeFilter = tab.key"
                        :class="[
                            'min-h-[38px] rounded-md px-3.5 py-1.5 text-xs font-semibold transition-colors cursor-pointer focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-primary-500',
                            activeFilter === tab.key
                                ? 'bg-navy-800 text-white shadow-xs'
                                : 'border border-border bg-bg text-text hover:bg-primary-100 hover:text-navy-950',
                        ]"
                    >
                        {{ tab.label }}
                    </button>
                </div>
            </div>

            <!-- Empty State -->
            <div
                v-if="filteredOrders.length === 0"
                class="rounded-lg border border-dashed border-border bg-surface p-10 text-center"
            >
                <div class="mx-auto mb-3 flex h-11 w-11 items-center justify-center rounded-md bg-primary-100 text-navy-800">
                    <Shirt class="h-5 w-5" aria-hidden="true" />
                </div>
                <p class="font-heading text-sm font-bold text-navy-950">
                    {{ searchQuery.trim() ? 'Tidak ada nota yang cocok dengan pencarian' : 'Belum ada cucian pada tahap ini' }}
                </p>
                <p class="mt-1 text-xs text-text-muted">
                    {{ searchQuery.trim() ? 'Coba kata kunci atau pilih filter lain.' : 'Pesanan baru dari Kasir POS otomatis muncul di sini.' }}
                </p>
            </div>

            <!-- Simplified Order Cards -->
            <div v-else class="grid grid-cols-1 gap-3.5 md:grid-cols-2">
                <article
                    v-for="order in filteredOrders"
                    :key="order.id"
                    class="flex flex-col justify-between rounded-lg border border-border bg-surface p-4 shadow-xs space-y-3.5"
                >
                    <div class="space-y-2.5">
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0">
                                <Link
                                    :href="`/nota/${order.invoice_number}`"
                                    class="inline-flex items-center gap-1 font-mono text-xs font-bold text-primary-700 hover:underline focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-primary-500 rounded-xs"
                                >
                                    <span>{{ order.invoice_number }}</span>
                                    <ExternalLink class="h-3 w-3" aria-hidden="true" />
                                </Link>
                                <h2 class="mt-0.5 font-heading text-base font-bold text-navy-950 truncate">
                                    {{ order.customer_name }}
                                </h2>
                            </div>

                            <span
                                class="shrink-0 rounded-sm border px-2 py-0.5 text-[11px] font-bold"
                                :class="stageBadgeClass(order.stage)"
                            >
                                {{ order.status_label }}
                            </span>
                        </div>

                        <p class="text-sm font-medium text-text">
                            {{ order.items_summary }}
                        </p>

                        <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-text-muted">
                            <span class="inline-flex items-center gap-1">
                                <Clock class="h-3.5 w-3.5 shrink-0" aria-hidden="true" />
                                Masuk {{ order.intake_label }}
                            </span>

                            <span v-if="order.delivery_option === 'delivery'" class="inline-flex items-center gap-1 font-semibold text-navy-950">
                                <Truck class="h-3.5 w-3.5 shrink-0 text-primary-700" aria-hidden="true" />
                                Antar Jemput
                            </span>

                            <span
                                class="font-semibold"
                                :class="order.outstanding_balance > 0 ? 'text-danger' : 'text-accent-700'"
                            >
                                {{
                                    order.outstanding_balance > 0
                                        ? `Kurang ${formatRupiah(order.outstanding_balance)}`
                                        : 'Lunas'
                                }}
                            </span>
                        </div>

                        <p
                            v-if="order.notes"
                            class="rounded-md border border-warning/35 bg-warning/15 px-3 py-2 text-xs font-medium text-navy-950"
                        >
                            Catatan: {{ order.notes }}
                        </p>
                    </div>

                    <div class="pt-3 border-t border-border flex items-center justify-between gap-2.5">
                        <Link
                            :href="`/nota/${order.invoice_number}`"
                            class="inline-flex min-h-[42px] items-center justify-center rounded-md border border-border bg-bg px-3.5 text-xs font-semibold text-navy-950 transition-colors hover:bg-primary-100/70 focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-primary-500"
                        >
                            Detail Nota
                        </Link>

                        <button
                            v-if="order.stage === 'diproses'"
                            type="button"
                            :disabled="updatingOrderId === order.id"
                            @click="transitionOrder(order, 'selesai')"
                            class="flex-1 inline-flex min-h-[42px] items-center justify-center gap-1.5 rounded-md bg-primary-700 hover:bg-navy-800 px-4 text-xs font-bold text-white shadow-xs transition-colors cursor-pointer disabled:opacity-60 focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-primary-500"
                        >
                            <CheckCircle2 class="h-4 w-4 shrink-0" aria-hidden="true" />
                            <span>{{ updatingOrderId === order.id ? 'Menyimpan...' : 'Tandai Selesai' }}</span>
                        </button>

                        <button
                            v-else-if="order.stage === 'selesai' && order.can_release"
                            type="button"
                            :disabled="updatingOrderId === order.id"
                            @click="transitionOrder(order, 'sudah_diambil')"
                            class="flex-1 inline-flex min-h-[42px] items-center justify-center gap-1.5 rounded-md bg-navy-800 hover:bg-navy-700 px-4 text-xs font-bold text-white shadow-xs transition-colors cursor-pointer disabled:opacity-60 focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-primary-500"
                        >
                            <PackageCheck class="h-4 w-4 shrink-0" aria-hidden="true" />
                            <span>{{ updatingOrderId === order.id ? 'Menyimpan...' : 'Sudah Diambil' }}</span>
                        </button>

                        <Link
                            v-else-if="order.stage === 'selesai'"
                            :href="`/nota/${order.invoice_number}`"
                            class="flex-1 inline-flex min-h-[42px] items-center justify-center rounded-md border border-danger/40 bg-danger/10 px-4 text-xs font-bold text-danger transition-colors hover:bg-danger/15 focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-primary-500"
                        >
                            Bayar Dulu Sebelum Ambil
                        </Link>

                        <span
                            v-else
                            class="text-xs font-semibold text-text-muted"
                        >
                            Selesai diserahkan
                        </span>
                    </div>
                </article>
            </div>
        </div>
    </AppLayout>
</template>
