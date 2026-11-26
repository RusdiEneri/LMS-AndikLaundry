<script setup lang="ts">
import { computed, nextTick, ref, watch } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import type { PageProps } from '@/types';
import {
    Download,
    Printer,
    Receipt,
    Shirt,
    Info,
    ChevronRight,
    Scale,
    Wallet,
} from 'lucide-vue-next';

// Legacy kpi props stay accepted cleanly without attaching to AppLayout root.
defineOptions({ inheritAttrs: false });

interface TrendPoint {
    key: string;
    label: string;
    revenue: number;
    expenses: number;
}

interface FinancialReport {
    period: { start_date: string; end_date: string; days: number };
    cash_flow: {
        cash_in: { tunai: number; transfer: number; qris: number; ewallet: number; total: number };
        cash_out: number;
        net_cash_flow: number;
    };
    accrual: {
        recognized_revenue: number;
        operating_expenses: number;
        net_operating_profit: number;
        profit_margin_percent: number;
    };
    health: { total_unpaid_receivables: number; cash_to_accrual_gap: number };
    details: {
        trend: { granularity: 'daily' | 'monthly'; points: TrendPoint[] };
        receivables: { total_outstanding: number; orders_count: number; customers_count: number };
    };
}

interface OperationsSummary {
    processing_count: number;
    ready_count: number;
}

interface RecentTransactionRow {
    id: number;
    invoice_number: string;
    customer_name: string;
    status: string;
    status_label: string;
    total_amount: number;
    outstanding_balance: number;
    date_label: string;
}

interface RecentExpenseRow {
    id: number;
    expense_number: string;
    category_label: string;
    description: string;
    amount: number;
    date_label: string;
}

interface Props {
    financialReport: FinancialReport;
    operations_summary?: OperationsSummary;
    recent_transactions?: RecentTransactionRow[];
    recent_expenses?: RecentExpenseRow[];
    store_info?: { name?: string; address?: string; phone?: string };
}

const props = defineProps<Props>();

type Preset = 'today' | 'last_7_days' | 'this_month' | 'custom';

const presets: { key: Exclude<Preset, 'custom'>; label: string }[] = [
    { key: 'today', label: 'Hari Ini' },
    { key: 'last_7_days', label: '7 Hari Terakhir' },
    { key: 'this_month', label: 'Bulan Ini' },
];

function isoDate(d: Date): string {
    const pad = (n: number) => String(n).padStart(2, '0');
    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
}

function presetRange(preset: Exclude<Preset, 'custom'>): [string, string] {
    const today = new Date();
    const end = isoDate(today);
    if (preset === 'today') return [end, end];
    if (preset === 'last_7_days') {
        const start = new Date(today);
        start.setDate(start.getDate() - 6);
        return [isoDate(start), end];
    }
    return [isoDate(new Date(today.getFullYear(), today.getMonth(), 1)), end];
}

const period = computed(() => props.financialReport.period);

function detectPreset(): Preset {
    for (const p of presets) {
        const [s, e] = presetRange(p.key);
        if (s === period.value.start_date && e === period.value.end_date) return p.key;
    }
    return 'custom';
}

const activePreset = ref<Preset>(detectPreset());
const customStart = ref(period.value.start_date);
const customEnd = ref(period.value.end_date);
const loading = ref(false);

watch(period, (p) => {
    customStart.value = p.start_date;
    customEnd.value = p.end_date;
    if (activePreset.value !== 'custom') activePreset.value = detectPreset();
});

const page = usePage<PageProps>();
const currentUser = computed(() => page.props.auth?.user ?? null);
const errors = computed(() => (page.props.errors ?? {}) as Record<string, string>);

function loadRange(startDate: string, endDate: string) {
    router.get(
        '/dashboard',
        { start_date: startDate, end_date: endDate },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            only: ['financialReport', 'errors'],
            onStart: () => (loading.value = true),
            onFinish: () => (loading.value = false),
        },
    );
}

function selectPreset(preset: Preset) {
    activePreset.value = preset;
    if (preset === 'custom') return;
    const [s, e] = presetRange(preset);
    loadRange(s, e);
}

const customInvalid = computed(() => !customStart.value || !customEnd.value || customEnd.value < customStart.value);

function applyCustom() {
    if (customInvalid.value) return;
    loadRange(customStart.value, customEnd.value);
}

const csvUrl = computed(
    () =>
        `/owner/reports/export-csv?start_date=${encodeURIComponent(period.value.start_date)}&end_date=${encodeURIComponent(period.value.end_date)}`,
);

const printedAt = ref('');

async function printSummary() {
    printedAt.value = new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date());
    await nextTick();
    document.body.classList.add('printing-finance-slip');
    const cleanup = () => {
        document.body.classList.remove('printing-finance-slip');
        window.removeEventListener('afterprint', cleanup);
    };
    window.addEventListener('afterprint', cleanup);
    window.print();
}

function formatRupiah(amount: number): string {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(
        amount || 0,
    );
}

function formatDate(iso: string): string {
    const [y, m, d] = iso.split('-').map(Number);
    return new Intl.DateTimeFormat('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }).format(
        new Date(y, m - 1, d),
    );
}

const periodLabel = computed(() =>
    period.value.start_date === period.value.end_date
        ? formatDate(period.value.start_date)
        : `${formatDate(period.value.start_date)} – ${formatDate(period.value.end_date)}`,
);

const accrual = computed(() => props.financialReport.accrual);
const cash = computed(() => props.financialReport.cash_flow);
const receivables = computed(() => props.financialReport.details.receivables);
const gap = computed(() => props.financialReport.health.cash_to_accrual_gap);
const showGapNote = computed(() => cash.value.net_cash_flow < accrual.value.net_operating_profit && gap.value > 0);
const digitalIn = computed(() => cash.value.cash_in.transfer + cash.value.cash_in.qris + cash.value.cash_in.ewallet);

const operations = computed<OperationsSummary>(() => ({
    processing_count: props.operations_summary?.processing_count ?? 0,
    ready_count: props.operations_summary?.ready_count ?? 0,
}));

const recentTransactions = computed(() => props.recent_transactions ?? []);
const recentExpenses = computed(() => props.recent_expenses ?? []);

const trend = computed(() => props.financialReport.details.trend);
const trendMax = computed(() => Math.max(1, ...trend.value.points.flatMap((p) => [p.revenue, p.expenses])));
const trendEmpty = computed(() => trend.value.points.every((p) => p.revenue === 0 && p.expenses === 0));
const trendUnit = computed(() => (trend.value.granularity === 'monthly' ? 'bulan' : 'hari'));

function barHeight(value: number): string {
    if (value <= 0) return '0%';
    return `${Math.max(3, (value / trendMax.value) * 100)}%`;
}
</script>

<template>
    <AppLayout title="Dashboard">
        <div class="space-y-5 print:hidden" :aria-busy="loading">
            <!-- Band 1: Header + Actions -->
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-primary-700">
                        Keuangan & Operasional
                    </p>
                    <h1 class="mt-1 font-heading text-2xl sm:text-3xl font-extrabold tracking-tight text-navy-950">
                        {{ store_info?.name ?? 'Andik Laundry' }}
                    </h1>
                    <p class="mt-1 text-sm text-text-muted">
                        Ringkasan akrual SAK EMKM dan arus kas nyata · Periode
                        <span class="font-semibold text-navy-950">{{ periodLabel }}</span>
                        <span>({{ period.days }} hari)</span>
                    </p>
                </div>

                <div class="flex flex-wrap sm:flex-nowrap items-center gap-2.5">
                    <a
                        :href="csvUrl"
                        class="inline-flex min-h-[42px] items-center justify-center gap-2 rounded-md border border-slate-400/70 bg-surface px-4 text-xs font-semibold text-navy-950 shadow-xs transition-colors hover:border-primary-600 hover:bg-primary-100/50 focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-primary-500"
                    >
                        <Download class="h-4 w-4 text-primary-700" aria-hidden="true" />
                        <span>Unduh CSV</span>
                    </a>
                    <button
                        type="button"
                        class="inline-flex min-h-[42px] cursor-pointer items-center justify-center gap-2 rounded-md bg-navy-800 px-4 text-xs font-bold text-white shadow-xs transition-colors hover:bg-navy-700 focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-primary-500"
                        @click="printSummary"
                    >
                        <Printer class="h-4 w-4" aria-hidden="true" />
                        <span>Cetak Ringkasan</span>
                    </button>
                </div>
            </div>

            <!-- Period Filter Strip -->
            <div class="rounded-lg border border-border bg-surface px-4 py-3 shadow-xs">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div role="group" aria-label="Pilih periode laporan" class="flex flex-wrap gap-2">
                        <button
                            v-for="p in presets"
                            :key="p.key"
                            type="button"
                            :aria-pressed="activePreset === p.key"
                            :disabled="loading"
                            class="min-h-[38px] rounded-md px-3.5 text-xs font-semibold transition-colors disabled:opacity-60 cursor-pointer focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-primary-500"
                            :class="
                                activePreset === p.key
                                    ? 'bg-navy-800 text-white shadow-xs'
                                    : 'border border-border bg-bg text-text hover:bg-primary-100 hover:text-navy-950'
                            "
                            @click="selectPreset(p.key)"
                        >
                            {{ p.label }}
                        </button>
                        <button
                            type="button"
                            :aria-pressed="activePreset === 'custom'"
                            :aria-expanded="activePreset === 'custom'"
                            aria-controls="custom-range"
                            class="min-h-[38px] rounded-md px-3.5 text-xs font-semibold transition-colors cursor-pointer focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-primary-500"
                            :class="
                                activePreset === 'custom'
                                    ? 'bg-navy-800 text-white shadow-xs'
                                    : 'border border-border bg-bg text-text hover:bg-primary-100 hover:text-navy-950'
                            "
                            @click="selectPreset('custom')"
                        >
                            Rentang Kustom
                        </button>
                    </div>

                    <p v-if="loading" class="text-xs font-medium text-primary-700" aria-live="polite">
                        Memuat periode...
                    </p>
                </div>

                <form
                    v-if="activePreset === 'custom'"
                    id="custom-range"
                    class="mt-3 flex flex-col gap-3 border-t border-border pt-3 sm:flex-row sm:items-end"
                    @submit.prevent="applyCustom"
                >
                    <label class="flex flex-col gap-1 text-xs font-semibold text-navy-950">
                        Tanggal Awal
                        <input
                            v-model="customStart"
                            type="date"
                            name="start_date"
                            required
                            :max="customEnd || undefined"
                            class="min-h-[42px] rounded-md border border-slate-400/80 bg-surface px-3 text-sm font-medium text-text focus:border-primary-600 focus:outline-none focus:ring-3 focus:ring-primary-100"
                        />
                    </label>
                    <label class="flex flex-col gap-1 text-xs font-semibold text-navy-950">
                        Tanggal Akhir
                        <input
                            v-model="customEnd"
                            type="date"
                            name="end_date"
                            required
                            :min="customStart || undefined"
                            class="min-h-[42px] rounded-md border border-slate-400/80 bg-surface px-3 text-sm font-medium text-text focus:border-primary-600 focus:outline-none focus:ring-3 focus:ring-primary-100"
                        />
                    </label>
                    <button
                        type="submit"
                        :disabled="customInvalid || loading"
                        class="min-h-[42px] cursor-pointer rounded-md bg-navy-800 px-5 text-xs font-bold text-white transition-colors hover:bg-navy-700 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        Terapkan
                    </button>
                </form>

                <p v-if="errors.start_date || errors.end_date" role="alert" class="mt-2 text-xs font-semibold text-danger">
                    {{ errors.start_date || errors.end_date }}
                </p>
            </div>

            <!-- Band 2: Top Asymmetric 2-Column Overview (Laba + Embedded Chart | Kas Nyata + Split Channels) -->
            <div class="grid grid-cols-1 gap-5 lg:grid-cols-12" :class="{ 'opacity-60': loading }">
                <!-- Left Primary Card -->
                <section class="lg:col-span-6 flex flex-col rounded-lg border border-border bg-surface p-5 shadow-xs">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-[11px] font-bold uppercase tracking-[0.12em] text-text-muted">
                                Laba Bersih Usaha (Akrual)
                            </p>
                            <div
                                class="mt-1.5 font-heading text-3xl sm:text-[2.1rem] font-extrabold font-mono tracking-tight tabular-nums"
                                :class="accrual.net_operating_profit < 0 ? 'text-danger' : 'text-navy-950'"
                            >
                                {{ formatRupiah(accrual.net_operating_profit) }}
                            </div>
                            <p class="mt-1 text-xs text-text-muted tabular-nums">
                                Omzet selesai <span class="font-semibold text-text">{{ formatRupiah(accrual.recognized_revenue) }}</span>
                                · Biaya <span class="font-semibold text-danger">{{ formatRupiah(accrual.operating_expenses) }}</span>
                                · Margin <span class="font-semibold text-navy-950">{{ accrual.profit_margin_percent }}%</span>
                            </p>
                        </div>
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-md bg-primary-100 text-primary-700">
                            <Scale class="h-5 w-5" aria-hidden="true" />
                        </div>
                    </div>

                    <!-- Embedded Trend Chart Box -->
                    <div class="mt-5 flex-1 rounded-md border border-dashed border-border bg-bg p-4 flex flex-col justify-between">
                        <div class="flex items-center justify-between gap-2 pb-2">
                            <span class="text-[11px] font-semibold text-text-muted">
                                Tren omzet vs biaya per {{ trendUnit }}
                            </span>
                            <div class="flex items-center gap-3 text-[11px] font-semibold text-text-muted" aria-hidden="true">
                                <span class="inline-flex items-center gap-1.5">
                                    <span class="h-2.5 w-2.5 rounded-xs bg-primary-600"></span>Omzet
                                </span>
                                <span class="inline-flex items-center gap-1.5">
                                    <span class="h-2.5 w-2.5 rounded-xs bg-accent-500"></span>Biaya
                                </span>
                            </div>
                        </div>

                        <div v-if="trendEmpty" class="flex min-h-[112px] items-center justify-center text-center text-xs font-medium text-text-muted px-4">
                            Belum ada omzet selesai atau biaya pada periode ini.
                        </div>
                        <div v-else class="-mx-1 overflow-x-auto px-1 pt-2">
                            <ul
                                class="flex h-28 items-end gap-1.5 border-b border-border"
                                :style="{ minWidth: `${trend.points.length * 2}rem` }"
                                :aria-label="`Grafik omzet dan biaya per ${trendUnit}`"
                            >
                                <li
                                    v-for="point in trend.points"
                                    :key="point.key"
                                    class="relative flex h-full flex-1 items-end justify-center gap-0.5"
                                    :title="`${point.label}: omzet ${formatRupiah(point.revenue)}, biaya ${formatRupiah(point.expenses)}`"
                                >
                                    <span class="sr-only">
                                        {{ point.label }}: omzet {{ formatRupiah(point.revenue) }}, biaya {{ formatRupiah(point.expenses) }}
                                    </span>
                                    <span class="w-full max-w-[14px] rounded-t-xs bg-primary-600" :style="{ height: barHeight(point.revenue) }" aria-hidden="true"></span>
                                    <span class="w-full max-w-[14px] rounded-t-xs bg-accent-500" :style="{ height: barHeight(point.expenses) }" aria-hidden="true"></span>
                                </li>
                            </ul>
                            <div class="flex gap-1.5 pt-1.5" :style="{ minWidth: `${trend.points.length * 2}rem` }" aria-hidden="true">
                                <span v-for="point in trend.points" :key="point.key" class="flex-1 text-center text-[10px] font-mono text-text-muted">
                                    {{ point.label }}
                                </span>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Right Cash Flow Card -->
                <section class="lg:col-span-6 flex flex-col justify-between rounded-lg border border-border bg-surface p-5 shadow-xs">
                    <div>
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-[11px] font-bold uppercase tracking-[0.12em] text-text-muted">
                                    Saldo Kas Nyata (Cash Flow)
                                </p>
                                <div
                                    class="mt-1.5 font-heading text-3xl sm:text-[2.1rem] font-extrabold font-mono tracking-tight tabular-nums"
                                    :class="cash.net_cash_flow < 0 ? 'text-danger' : 'text-navy-950'"
                                >
                                    {{ formatRupiah(cash.net_cash_flow) }}
                                </div>
                                <p class="mt-1 text-xs text-text-muted tabular-nums">
                                    Diterima <span class="font-semibold text-text">{{ formatRupiah(cash.cash_in.total) }}</span>
                                    · Keluar <span class="font-semibold text-danger">{{ formatRupiah(cash.cash_out) }}</span>
                                </p>
                            </div>
                            <Link
                                href="/owner/daily-recap"
                                class="inline-flex items-center gap-1 rounded-md px-2 py-1 text-xs font-semibold text-primary-700 hover:bg-primary-100/70 transition-colors focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-primary-500"
                            >
                                <span>Rekap Harian</span>
                                <ChevronRight class="h-3.5 w-3.5" aria-hidden="true" />
                            </Link>
                        </div>

                        <div
                            v-if="showGapNote"
                            class="mt-4 flex items-start gap-2.5 rounded-md border border-warning/35 bg-warning/10 p-3 text-xs text-text"
                            role="note"
                        >
                            <Info class="mt-0.5 h-4 w-4 shrink-0 text-warning" aria-hidden="true" />
                            <span>
                                Selisih <strong class="font-mono tabular-nums text-navy-950">{{ formatRupiah(gap) }}</strong> masih tertahan di piutang nota selesai yang belum lunas.
                            </span>
                        </div>
                    </div>

                    <!-- 2-Column Bottom Split -->
                    <div class="mt-6 grid grid-cols-2 divide-x divide-border border-t border-border pt-4">
                        <div class="pr-4">
                            <p class="text-xs font-medium text-text-muted flex items-center gap-1.5">
                                <Wallet class="h-3.5 w-3.5 text-primary-700" aria-hidden="true" />
                                <span>Kas Tunai Masuk</span>
                            </p>
                            <p class="mt-1 font-mono text-lg font-bold text-navy-950 tabular-nums">
                                {{ formatRupiah(cash.cash_in.tunai) }}
                            </p>
                        </div>
                        <div class="pl-4">
                            <p class="text-xs font-medium text-text-muted">
                                QRIS / Transfer / Digital
                            </p>
                            <p class="mt-1 font-mono text-lg font-bold text-navy-950 tabular-nums">
                                {{ formatRupiah(digitalIn) }}
                            </p>
                        </div>
                    </div>
                </section>
            </div>

            <!-- Band 3: 3-Column Horizontal Divided Strip (Piutang | Cucian Diproses | Siap Diambil) -->
            <div
                class="grid grid-cols-1 divide-y divide-border rounded-lg border border-border bg-surface shadow-xs md:grid-cols-3 md:divide-x md:divide-y-0"
                :class="{ 'opacity-60': loading }"
            >
                <div class="flex items-center justify-between gap-3 p-4 sm:px-5">
                    <div class="min-w-0">
                        <p class="text-[11px] font-bold uppercase tracking-[0.12em] text-text-muted">
                            Piutang Pelanggan
                        </p>
                        <p class="mt-1 font-mono text-base font-bold text-navy-950 tabular-nums">
                            {{ formatRupiah(receivables.total_outstanding) }}
                        </p>
                        <p class="mt-0.5 text-xs text-text-muted">
                            {{ receivables.orders_count }} nota · {{ receivables.customers_count }} pelanggan
                        </p>
                    </div>
                    <Link
                        href="/owner/receivables"
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-md border border-border bg-bg text-primary-700 hover:bg-primary-100 transition-colors focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-primary-500"
                        aria-label="Lihat daftar piutang"
                        title="Lihat daftar piutang"
                    >
                        <Receipt class="h-4 w-4" aria-hidden="true" />
                    </Link>
                </div>

                <div class="flex items-center justify-between gap-3 p-4 sm:px-5">
                    <div class="min-w-0">
                        <p class="text-[11px] font-bold uppercase tracking-[0.12em] text-text-muted">
                            Cucian Diproses
                        </p>
                        <p class="mt-1 font-mono text-base font-bold text-navy-950 tabular-nums">
                            {{ operations.processing_count }} Nota
                        </p>
                        <p class="mt-0.5 text-xs text-text-muted">
                            Sedang dicuci, dikeringkan, atau disetrika
                        </p>
                    </div>
                    <Link
                        href="/production/station"
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-md border border-border bg-bg text-primary-700 hover:bg-primary-100 transition-colors focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-primary-500"
                        aria-label="Buka stasiun produksi"
                        title="Buka stasiun produksi"
                    >
                        <Shirt class="h-4 w-4" aria-hidden="true" />
                    </Link>
                </div>

                <div class="flex items-center justify-between gap-3 p-4 sm:px-5">
                    <div class="min-w-0">
                        <p class="text-[11px] font-bold uppercase tracking-[0.12em] text-text-muted">
                            Siap Diambil
                        </p>
                        <p class="mt-1 font-mono text-base font-bold text-navy-950 tabular-nums">
                            {{ operations.ready_count }} Nota
                        </p>
                        <p class="mt-0.5 text-xs text-text-muted">
                            Selesai di rak, menunggu pelanggan
                        </p>
                    </div>
                    <span
                        class="h-2.5 w-2.5 shrink-0 rounded-full"
                        :class="operations.ready_count > 0 ? 'bg-accent-500' : 'bg-slate-400'"
                        aria-hidden="true"
                    ></span>
                </div>
            </div>

            <!-- Band 4: Bottom 2-Column Detail Cards (Transaksi Terbaru | Pengeluaran Terbaru) -->
            <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
                <!-- Left: Recent Transactions -->
                <section class="flex flex-col rounded-lg border border-border bg-surface shadow-xs">
                    <div class="flex items-center justify-between border-b border-border px-5 py-3.5">
                        <h2 class="font-heading text-sm font-bold text-navy-950">Transaksi Terbaru</h2>
                        <Link
                            href="/pos"
                            class="text-xs font-semibold text-primary-700 hover:underline focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-primary-500 rounded-sm"
                        >
                            Buka Kasir
                        </Link>
                    </div>

                    <div v-if="recentTransactions.length === 0" class="flex flex-1 flex-col items-center justify-center gap-3 p-8 text-center">
                        <p class="text-sm text-text-muted">Belum ada transaksi tercatat.</p>
                        <Link
                            href="/pos"
                            class="inline-flex min-h-[40px] items-center justify-center rounded-md bg-navy-800 px-4 text-xs font-bold text-white shadow-xs transition-colors hover:bg-navy-700 focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-primary-500"
                        >
                            Buat Transaksi
                        </Link>
                    </div>

                    <ul v-else class="divide-y divide-border">
                        <li
                            v-for="txn in recentTransactions"
                            :key="txn.id"
                            class="flex items-center justify-between gap-3 px-5 py-3 hover:bg-bg/60 transition-colors"
                        >
                            <div class="min-w-0">
                                <div class="flex items-center gap-2">
                                    <Link
                                        :href="`/nota/${txn.invoice_number}`"
                                        class="font-mono text-xs font-bold text-primary-700 hover:underline"
                                    >
                                        {{ txn.invoice_number }}
                                    </Link>
                                    <span class="rounded-pill bg-primary-100 px-2 py-0.5 text-[10px] font-semibold text-navy-800">
                                        {{ txn.status_label }}
                                    </span>
                                </div>
                                <p class="mt-0.5 truncate text-xs text-text-muted">
                                    {{ txn.customer_name }} · {{ txn.date_label }}
                                </p>
                            </div>
                            <div class="shrink-0 text-right">
                                <span class="font-mono text-sm font-bold text-navy-950 tabular-nums">
                                    {{ formatRupiah(txn.total_amount) }}
                                </span>
                            </div>
                        </li>
                    </ul>
                </section>

                <!-- Right: Recent Expenses -->
                <section class="flex flex-col rounded-lg border border-border bg-surface shadow-xs">
                    <div class="flex items-center justify-between border-b border-border px-5 py-3.5">
                        <h2 class="font-heading text-sm font-bold text-navy-950">Pengeluaran Terbaru</h2>
                        <Link
                            href="/owner/expenses"
                            class="text-xs font-semibold text-primary-700 hover:underline focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-primary-500 rounded-sm"
                        >
                            Lihat semua
                        </Link>
                    </div>

                    <div v-if="recentExpenses.length === 0" class="flex flex-1 flex-col items-center justify-center gap-3 p-8 text-center">
                        <p class="text-sm text-text-muted">Belum ada pengeluaran toko tercatat.</p>
                        <Link
                            href="/owner/expenses"
                            class="inline-flex min-h-[40px] items-center justify-center rounded-md border border-slate-400/70 bg-surface px-4 text-xs font-semibold text-navy-950 transition-colors hover:bg-primary-100/50 focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-primary-500"
                        >
                            Catat Pengeluaran
                        </Link>
                    </div>

                    <ul v-else class="divide-y divide-border">
                        <li
                            v-for="expense in recentExpenses"
                            :key="expense.id"
                            class="flex items-center justify-between gap-3 px-5 py-3 hover:bg-bg/60 transition-colors"
                        >
                            <div class="min-w-0">
                                <p class="truncate text-xs font-semibold text-navy-950">
                                    {{ expense.description }}
                                </p>
                                <p class="mt-0.5 truncate text-xs text-text-muted">
                                    {{ expense.category_label }} · {{ expense.date_label }}
                                </p>
                            </div>
                            <div class="shrink-0 text-right">
                                <span class="font-mono text-sm font-bold text-danger tabular-nums">
                                    − {{ formatRupiah(expense.amount) }}
                                </span>
                            </div>
                        </li>
                    </ul>
                </section>
            </div>
        </div>

        <!-- Full Page Executive Financial Summary (A4 format for printSummary) -->
        <div id="finance-slip" class="hidden text-black font-sans bg-white leading-normal">
            <!-- Header: Store Info & Document Title -->
            <div class="flex items-start justify-between border-b-[3px] border-black pb-4 mb-4">
                <div class="space-y-1">
                    <div class="flex items-center gap-2">
                        <img
                            src="/images/logo-a.webp"
                            alt=""
                            class="h-8 w-auto object-contain"
                            width="33"
                            height="32"
                            aria-hidden="true"
                        />
                        <h1 class="font-heading text-xl font-black tracking-tight text-black">{{ store_info?.name ?? 'Andik Laundry' }}</h1>
                    </div>
                    <p class="text-xs text-gray-700 max-w-md font-medium" v-if="store_info?.address">{{ store_info.address }}</p>
                    <p class="text-xs text-gray-700 font-mono font-medium" v-if="store_info?.phone">WhatsApp: {{ store_info.phone }}</p>
                </div>
                <div class="text-right">
                    <div class="inline-block border-2 border-black bg-white px-3 py-1 text-xs font-black uppercase tracking-wider mb-1">
                        Ringkasan Keuangan SAK EMKM
                    </div>
                    <div class="text-xs font-bold text-gray-900">
                        Periode: <span class="font-black">{{ periodLabel }}</span>
                    </div>
                    <div class="text-[11px] text-gray-600">
                        Durasi: {{ period.days }} Hari · Dicetak: {{ printedAt }}
                    </div>
                </div>
            </div>

            <!-- Top Summary Cards (3 Columns) -->
            <div class="grid grid-cols-3 gap-3 mb-4">
                <div class="border-2 border-black rounded-[8px] p-3 bg-white">
                    <div class="text-[10px] font-black uppercase tracking-wider text-gray-600 mb-0.5">Laba Bersih (Akrual)</div>
                    <div class="text-xl font-mono font-black tabular-nums text-black">{{ formatRupiah(accrual.net_operating_profit) }}</div>
                    <div class="text-[10px] text-gray-600 mt-1 font-mono">
                        Omzet: {{ formatRupiah(accrual.recognized_revenue) }} · Biaya: {{ formatRupiah(accrual.operating_expenses) }}
                    </div>
                </div>
                <div class="border-2 border-black rounded-[8px] p-3 bg-white">
                    <div class="text-[10px] font-black uppercase tracking-wider text-gray-600 mb-0.5">Saldo Kas Bersih (Nyata)</div>
                    <div class="text-xl font-mono font-black tabular-nums text-black">{{ formatRupiah(cash.net_cash_flow) }}</div>
                    <div class="text-[10px] text-gray-600 mt-1 font-mono">
                        Masuk: {{ formatRupiah(cash.cash_in.total) }} · Keluar: {{ formatRupiah(cash.cash_out) }}
                    </div>
                </div>
                <div class="border-2 border-black rounded-[8px] p-3 bg-white">
                    <div class="text-[10px] font-black uppercase tracking-wider text-gray-600 mb-0.5">Piutang Pelanggan</div>
                    <div class="text-xl font-mono font-black tabular-nums text-black">{{ formatRupiah(receivables.total_outstanding) }}</div>
                    <div class="text-[10px] text-gray-600 mt-1">
                        {{ receivables.orders_count }} Nota Belum Lunas ({{ receivables.customers_count }} Pelanggan)
                    </div>
                </div>
            </div>

            <!-- 2-Column Side-by-Side Detailed Breakdown -->
            <div class="grid grid-cols-2 gap-4 mb-4">
                <div class="border-2 border-black rounded-[8px] p-3.5 bg-white flex flex-col justify-between">
                    <div>
                        <div class="border-b-2 border-black pb-1.5 mb-2.5">
                            <h2 class="text-xs font-black uppercase tracking-wider text-black">1. Laporan Laba Rugi Operasional (Akrual)</h2>
                            <p class="text-[10px] text-gray-600">Standar Pengakuan Selesai Cuci (SAK EMKM)</p>
                        </div>
                        <div class="space-y-1.5 text-xs">
                            <div class="flex justify-between py-1 border-b border-dashed border-gray-300">
                                <span class="font-medium text-gray-800">Pendapatan / Omzet Cucian Selesai</span>
                                <span class="font-mono font-bold">{{ formatRupiah(accrual.recognized_revenue) }}</span>
                            </div>
                            <div class="flex justify-between py-1 border-b border-dashed border-gray-300">
                                <span class="font-medium text-gray-800">Beban Operasional & Pengeluaran Toko</span>
                                <span class="font-mono font-bold text-black">− {{ formatRupiah(accrual.operating_expenses) }}</span>
                            </div>
                            <div class="flex justify-between py-2 border-t-2 border-black font-black text-sm">
                                <span>LABA / (RUGI) BERSIH OPERASIONAL</span>
                                <span class="font-mono">{{ formatRupiah(accrual.net_operating_profit) }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="mt-3 pt-2 border-t border-gray-200 text-[9.5px] text-gray-500 italic">
                        * Omzet diakui secara akrual saat cucian selesai diproduksi dan siap diserahkan kepada pelanggan.
                    </div>
                </div>

                <div class="border-2 border-black rounded-[8px] p-3.5 bg-white flex flex-col justify-between">
                    <div>
                        <div class="border-b-2 border-black pb-1.5 mb-2.5">
                            <h2 class="text-xs font-black uppercase tracking-wider text-black">2. Laporan Arus Kas Nyata (Cash Flow)</h2>
                            <p class="text-[10px] text-gray-600">Rincian Penerimaan & Pengeluaran Kas Fisik/Bank</p>
                        </div>
                        <div class="space-y-1 text-xs">
                            <div class="flex justify-between py-0.5">
                                <span class="text-gray-700">Penerimaan Kas - Tunai (Cash)</span>
                                <span class="font-mono">{{ formatRupiah(cash.cash_in.tunai) }}</span>
                            </div>
                            <div class="flex justify-between py-0.5">
                                <span class="text-gray-700">Penerimaan Kas - Transfer Bank</span>
                                <span class="font-mono">{{ formatRupiah(cash.cash_in.transfer) }}</span>
                            </div>
                            <div class="flex justify-between py-0.5">
                                <span class="text-gray-700">Penerimaan Kas - QRIS Manual</span>
                                <span class="font-mono">{{ formatRupiah(cash.cash_in.qris) }}</span>
                            </div>
                            <div class="flex justify-between py-0.5">
                                <span class="text-gray-700">Penerimaan Kas - E-Wallet</span>
                                <span class="font-mono">{{ formatRupiah(cash.cash_in.ewallet) }}</span>
                            </div>
                            <div class="flex justify-between py-1 border-t border-dashed border-gray-400 font-bold">
                                <span>Total Kas Nyata Diterima</span>
                                <span class="font-mono">{{ formatRupiah(cash.cash_in.total) }}</span>
                            </div>
                            <div class="flex justify-between py-1 border-b border-dashed border-gray-400 font-bold">
                                <span>Total Kas Keluar (Pengeluaran)</span>
                                <span class="font-mono text-black">− {{ formatRupiah(cash.cash_out) }}</span>
                            </div>
                            <div class="flex justify-between py-2 border-t-2 border-black font-black text-sm">
                                <span>SALDO ARUS KAS BERSIH</span>
                                <span class="font-mono">{{ formatRupiah(cash.net_cash_flow) }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="mt-3 pt-2 border-t border-gray-200 text-[9.5px] text-gray-500 italic">
                        * Kas mencerminkan dana riil yang masuk laci atau rekening operasional toko selama periode ini.
                    </div>
                </div>
            </div>

            <div v-if="showGapNote" class="border-2 border-black rounded-[8px] p-3 mb-4 bg-gray-50 text-xs">
                <div class="font-bold text-black flex items-center gap-1.5 mb-0.5">
                    <span class="font-black uppercase tracking-wider text-[10px] bg-black text-white px-1.5 py-0.5 rounded-[3px]">Catatan Rekonsiliasi</span>
                    <span>Selisih Laba vs Arus Kas Nyata</span>
                </div>
                <p class="text-gray-700 leading-relaxed text-[11px]">
                    Terdapat dana senilai <strong class="font-mono font-bold">{{ formatRupiah(gap) }}</strong> dari laba operasional yang saat ini masih berupa piutang berjalan pelanggan. Laba telah sah diakui karena pekerjaan cucian telah selesai, namun kas nyata belum diterima dari pelanggan terkait.
                </p>
            </div>

            <div class="grid grid-cols-2 gap-12 pt-4 pb-2 text-center text-xs">
                <div>
                    <p class="font-bold text-gray-800">Dibuat & Disiapkan Oleh:</p>
                    <div class="h-16 flex items-end justify-center">
                        <div class="border-b-2 border-black w-48 text-center pb-1">
                            <span class="font-bold text-xs">{{ currentUser?.name ?? 'Petugas Kasir' }}</span>
                        </div>
                    </div>
                    <p class="text-[10px] text-gray-600 mt-1">Petugas Kasir / Operasional</p>
                </div>
                <div>
                    <p class="font-bold text-gray-800">Disetujui & Diperiksa Oleh:</p>
                    <div class="h-16 flex items-end justify-center">
                        <div class="border-b-2 border-black w-48 text-center pb-1">
                            <span class="font-bold text-xs">Andik (Owner)</span>
                        </div>
                    </div>
                    <p class="text-[10px] text-gray-600 mt-1">Pemilik Toko (Owner)</p>
                </div>
            </div>

            <div class="border-t border-black pt-2 mt-4 text-[10px] text-gray-500 flex items-center justify-between">
                <span>LMS Andik Laundry · Sistem Manajemen Operasional & Keuangan SAK EMKM</span>
                <span>Dokumen Sah Internal · Dicetak otomatis pada {{ printedAt }}</span>
            </div>
        </div>
    </AppLayout>
</template>

<style>
@page {
    size: A4 portrait;
    margin: 12mm 15mm;
}

@media print {
    body.printing-finance-slip * {
        visibility: hidden !important;
    }
    body.printing-finance-slip #finance-slip,
    body.printing-finance-slip #finance-slip * {
        visibility: visible !important;
    }
    body.printing-finance-slip #finance-slip {
        display: block !important;
        position: absolute !important;
        left: 0 !important;
        top: 0 !important;
        width: 100% !important;
        max-width: 100% !important;
        box-sizing: border-box !important;
        margin: 0 !important;
        padding: 0 !important;
        background: white !important;
        color: black !important;
        font-family: 'DM Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }
}
</style>
