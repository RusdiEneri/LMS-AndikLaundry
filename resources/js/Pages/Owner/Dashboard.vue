<script setup lang="ts">
import { computed, nextTick, ref, watch } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Download, Printer, Scale, Wallet, Receipt, Info, ArrowRight } from 'lucide-vue-next';

// The controller still sends the legacy kpi props; keep them off the layout's root element.
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

interface Props {
    financialReport: FinancialReport;
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

const page = usePage();
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

// Print rules are gated on this body class so they never leak into other pages' print jobs.
async function printSummary() {
    printedAt.value = new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date());
    // window.print() blocks; let the timestamp render into the slip first.
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

const trend = computed(() => props.financialReport.details.trend);
const trendMax = computed(() => Math.max(1, ...trend.value.points.flatMap((p) => [p.revenue, p.expenses])));
const trendEmpty = computed(() => trend.value.points.every((p) => p.revenue === 0 && p.expenses === 0));
const trendUnit = computed(() => (trend.value.granularity === 'monthly' ? 'bulan' : 'hari'));

function barHeight(value: number): string {
    if (value <= 0) return '0%';
    return `${Math.max(2, (value / trendMax.value) * 100)}%`;
}
</script>

<template>
    <AppLayout title="Owner Dashboard">
        <div class="space-y-6 print:hidden" :aria-busy="loading">
            <!-- Header: title, period, actions -->
            <div class="flex flex-col gap-4 rounded-[12px] border-[3.5px] border-black bg-white p-5 lg:flex-row lg:items-end lg:justify-between shadow-[6px_6px_0px_#000]">
                <div>
                    <h1 class="font-display text-2xl sm:text-3xl font-black tracking-tight text-black">Keuangan Toko</h1>
                    <p class="mt-1 font-sans text-sm font-bold text-black/70">
                        Periode <span class="font-black text-black">{{ periodLabel }}</span>
                        <span class="text-black/50 font-normal">({{ period.days }} hari)</span>
                    </p>
                </div>

                <div class="flex flex-wrap sm:flex-nowrap gap-2.5">
                    <a
                        :href="csvUrl"
                        class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-[8px] border-[2.5px] border-black bg-white hover:bg-slate-100 text-black font-black uppercase tracking-wider text-xs shadow-[3px_3px_0px_#000] active:translate-x-[2px] active:translate-y-[2px] active:shadow-none transition-all cursor-pointer min-h-[42px]"
                    >
                        <Download class="h-4 w-4 stroke-[2.5]" aria-hidden="true" />
                        <span>Unduh CSV</span>
                    </a>
                    <button
                        type="button"
                        class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-[8px] border-[2.5px] border-black bg-[#FFEB3B] hover:bg-[#fff066] text-black font-black uppercase tracking-wider text-xs shadow-[3px_3px_0px_#000] active:translate-x-[2px] active:translate-y-[2px] active:shadow-none transition-all cursor-pointer min-h-[42px]"
                        @click="printSummary"
                    >
                        <Printer class="h-4 w-4 stroke-[2.5]" aria-hidden="true" />
                        <span>Cetak Ringkasan</span>
                    </button>
                </div>
            </div>

            <!-- Period filter -->
            <div class="space-y-3 rounded-[12px] border-[3.5px] border-black bg-white p-4 shadow-[6px_6px_0px_#000]">
                <div role="group" aria-label="Pilih periode laporan" class="flex flex-wrap gap-2">
                    <button
                        v-for="p in presets"
                        :key="p.key"
                        type="button"
                        :aria-pressed="activePreset === p.key"
                        :disabled="loading"
                        class="min-h-[44px] rounded-[8px] border-2 px-4 text-xs font-black uppercase tracking-wider transition-all disabled:opacity-60 cursor-pointer active:translate-x-0.5 active:translate-y-0.5"
                        :class="
                            activePreset === p.key
                                ? 'border-black bg-[#FFEB3B] text-black shadow-[3px_3px_0px_#000]'
                                : 'border-black/30 bg-white text-black/70 hover:border-black hover:bg-[#FFF9D2]/30 hover:text-black shadow-[1px_1px_0px_#000]'
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
                        class="min-h-[44px] rounded-[8px] border-2 px-4 text-xs font-black uppercase tracking-wider transition-all cursor-pointer active:translate-x-0.5 active:translate-y-0.5"
                        :class="
                            activePreset === 'custom'
                                ? 'border-black bg-[#FFEB3B] text-black shadow-[3px_3px_0px_#000]'
                                : 'border-black/30 bg-white text-black/70 hover:border-black hover:bg-[#FFF9D2]/30 hover:text-black shadow-[1px_1px_0px_#000]'
                        "
                        @click="selectPreset('custom')"
                    >
                        Rentang Kustom
                    </button>
                </div>

                <form
                    v-if="activePreset === 'custom'"
                    id="custom-range"
                    class="flex flex-col gap-3 sm:flex-row sm:items-end pt-2"
                    @submit.prevent="applyCustom"
                >
                    <label class="flex flex-col gap-1 text-xs font-black uppercase tracking-wider text-black">
                        Tanggal Awal
                        <input
                            v-model="customStart"
                            type="date"
                            name="start_date"
                            required
                            :max="customEnd || undefined"
                            class="min-h-[44px] rounded-[8px] border-[2.5px] border-black bg-white px-3.5 py-2 text-sm font-bold text-black shadow-[2px_2px_0px_#000] focus:outline-none focus:shadow-[3px_3px_0px_#000]"
                        />
                    </label>
                    <label class="flex flex-col gap-1 text-xs font-black uppercase tracking-wider text-black">
                        Tanggal Akhir
                        <input
                            v-model="customEnd"
                            type="date"
                            name="end_date"
                            required
                            :min="customStart || undefined"
                            class="min-h-[44px] rounded-[8px] border-[2.5px] border-black bg-white px-3.5 py-2 text-sm font-bold text-black shadow-[2px_2px_0px_#000] focus:outline-none focus:shadow-[3px_3px_0px_#000]"
                        />
                    </label>
                    <Button type="submit" variant="default" :disabled="customInvalid || loading" class="font-black uppercase tracking-wider text-xs min-h-[44px]">Terapkan</Button>
                </form>

                <p v-if="errors.start_date || errors.end_date" role="alert" class="text-xs font-black text-[#FF5252]">
                    {{ errors.start_date || errors.end_date }}
                </p>
                <p v-if="loading" class="text-xs font-bold text-black/60" aria-live="polite">Memuat laporan periode baru…</p>
            </div>

            <!-- Accrual profit vs cash on hand, side by side -->
            <div class="grid grid-cols-1 gap-5 md:grid-cols-2" :class="{ 'opacity-60': loading }">
                <Card class="border-[3.5px] border-black bg-white rounded-[12px] shadow-[6px_6px_0px_#000]">
                    <CardHeader class="flex flex-row items-start justify-between space-y-0 pb-4">
                        <div>
                            <CardTitle class="font-display text-lg font-black text-black">Laba Bersih Usaha</CardTitle>
                            <CardDescription class="font-sans text-xs font-bold text-black/60 mt-0.5">Akrual SAK EMKM: dihitung dari nota yang sudah selesai</CardDescription>
                        </div>
                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-[8px] bg-[#FFEB3B] border-2 border-black text-black shadow-[2px_2px_0px_#000]">
                            <Scale class="h-5 w-5 stroke-[2.5]" aria-hidden="true" />
                        </div>
                    </CardHeader>
                    <CardContent>
                        <dl class="space-y-2.5 text-sm">
                            <div class="flex justify-between gap-3">
                                <dt class="text-black/70 font-bold">Omzet selesai diakui</dt>
                                <dd class="font-mono font-black text-black tabular-nums">{{ formatRupiah(accrual.recognized_revenue) }}</dd>
                            </div>
                            <div class="flex justify-between gap-3">
                                <dt class="text-black/70 font-bold">Total biaya toko</dt>
                                <dd class="font-mono font-black text-[#FF5252] tabular-nums">− {{ formatRupiah(accrual.operating_expenses) }}</dd>
                            </div>
                            <div class="flex items-baseline justify-between gap-3 border-t-2 border-black pt-3.5">
                                <dt class="font-black text-black uppercase tracking-wider text-xs">Laba bersih</dt>
                                <dd
                                    class="text-2xl sm:text-3xl font-mono font-black tracking-tight tabular-nums"
                                    :class="accrual.net_operating_profit < 0 ? 'text-[#FF5252]' : 'text-[#22C55E]'"
                                >
                                    {{ formatRupiah(accrual.net_operating_profit) }}
                                </dd>
                            </div>
                            <div class="flex justify-between gap-3 text-xs font-bold text-black/60 pt-0.5">
                                <dt>Margin laba</dt>
                                <dd class="font-black text-black tabular-nums">{{ accrual.profit_margin_percent }}%</dd>
                            </div>
                        </dl>
                    </CardContent>
                </Card>

                <Card class="border-[3.5px] border-black bg-white rounded-[12px] shadow-[6px_6px_0px_#000]">
                    <CardHeader class="flex flex-row items-start justify-between space-y-0 pb-4">
                        <div>
                            <CardTitle class="font-display text-lg font-black text-black">Uang Kas Nyata di Tangan</CardTitle>
                            <CardDescription class="font-sans text-xs font-bold text-black/60 mt-0.5">Uang yang benar-benar masuk dan keluar di periode ini</CardDescription>
                        </div>
                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-[8px] bg-[#2196F3] border-2 border-black text-black shadow-[2px_2px_0px_#000]">
                            <Wallet class="h-5 w-5 stroke-[2.5]" aria-hidden="true" />
                        </div>
                    </CardHeader>
                    <CardContent>
                        <dl class="space-y-2.5 text-sm">
                            <div class="flex justify-between gap-3">
                                <dt class="text-black/70 font-bold">Uang diterima kasir</dt>
                                <dd class="font-mono font-black text-black tabular-nums">{{ formatRupiah(cash.cash_in.total) }}</dd>
                            </div>
                            <div class="flex justify-between gap-3 pl-3 text-xs text-black/60 font-bold">
                                <dt>Tunai</dt>
                                <dd class="font-mono text-black tabular-nums">{{ formatRupiah(cash.cash_in.tunai) }}</dd>
                            </div>
                            <div class="flex justify-between gap-3 pl-3 text-xs text-black/60 font-bold">
                                <dt>Transfer / QRIS / e-wallet</dt>
                                <dd class="font-mono text-black tabular-nums">{{ formatRupiah(digitalIn) }}</dd>
                            </div>
                            <div class="flex justify-between gap-3">
                                <dt class="text-black/70 font-bold">Kas keluar</dt>
                                <dd class="font-mono font-black text-[#FF5252] tabular-nums">− {{ formatRupiah(cash.cash_out) }}</dd>
                            </div>
                            <div class="flex items-baseline justify-between gap-3 border-t-2 border-black pt-3.5">
                                <dt class="font-black text-black uppercase tracking-wider text-xs">Saldo kas bersih</dt>
                                <dd
                                    class="text-2xl sm:text-3xl font-mono font-black tracking-tight tabular-nums"
                                    :class="cash.net_cash_flow < 0 ? 'text-[#FF5252]' : 'text-[#22C55E]'"
                                >
                                    {{ formatRupiah(cash.net_cash_flow) }}
                                </dd>
                            </div>
                        </dl>
                    </CardContent>
                </Card>
            </div>

            <div
                v-if="showGapNote"
                class="flex items-start gap-3 rounded-[12px] border-[2.5px] border-black bg-[#FFF9D2] p-4 text-sm text-black shadow-[4px_4px_0px_#000]"
                role="note"
            >
                <Info class="mt-0.5 h-5 w-5 shrink-0 text-black stroke-[2.5]" aria-hidden="true" />
                <p class="font-bold">
                    Ada <strong class="tabular-nums font-black font-mono">{{ formatRupiah(gap) }}</strong> uang laba yang masih tertahan di piutang pelanggan.
                    <span class="text-black/70 font-normal"> Laba sudah tercatat karena nota selesai, tapi uangnya belum masuk kas.</span>
                </p>
            </div>

            <!-- Outstanding receivables -->
            <Card class="border-[3.5px] border-black bg-white rounded-[12px] shadow-[6px_6px_0px_#000]" :class="{ 'opacity-60': loading }">
                <CardContent class="flex flex-col gap-4 p-5 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-start gap-3.5">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-[8px] bg-[#FF5252] border-2 border-black text-black shadow-[2px_2px_0px_#000]">
                            <Receipt class="h-6 w-6 stroke-[2.5]" aria-hidden="true" />
                        </div>
                        <div>
                            <h2 class="font-display text-base font-black text-black">Piutang Pelanggan Berjalan</h2>
                            <p class="text-2xl sm:text-3xl font-mono font-black tracking-tight tabular-nums text-black">{{ formatRupiah(receivables.total_outstanding) }}</p>
                            <p class="text-xs font-bold text-black/60 mt-0.5">
                                {{ receivables.orders_count }} nota belum lunas dari {{ receivables.customers_count }} pelanggan · semua periode
                            </p>
                        </div>
                    </div>
                    <Button as-child variant="outline" class="gap-2 font-black uppercase tracking-wider text-xs min-h-[44px]">
                        <Link href="/owner/receivables">
                            <span>Lihat & tagih piutang</span>
                            <ArrowRight class="h-4 w-4 stroke-[2.5]" aria-hidden="true" />
                        </Link>
                    </Button>
                </CardContent>
            </Card>

            <!-- Trend chart -->
            <Card class="border-[3.5px] border-black bg-white rounded-[12px] shadow-[6px_6px_0px_#000]">
                <CardHeader>
                    <CardTitle class="font-display text-base font-black text-black">Omzet selesai vs biaya toko per {{ trendUnit }}</CardTitle>
                    <div class="flex flex-wrap gap-4 pt-1 text-xs font-bold text-black/70" aria-hidden="true">
                        <span class="flex items-center gap-1.5"><span class="h-3.5 w-3.5 rounded-[2px] bg-[#FFEB3B] border border-black shadow-[1px_1px_0px_#000]"></span>Omzet selesai</span>
                        <span class="flex items-center gap-1.5"><span class="h-3.5 w-3.5 rounded-[2px] bg-[#FF5252] border border-black shadow-[1px_1px_0px_#000]"></span>Biaya toko</span>
                    </div>
                </CardHeader>
                <CardContent>
                    <p v-if="trendEmpty" class="rounded-[8px] border-2 border-dashed border-black p-6 text-center text-sm font-bold text-black/60 bg-[#FFF9D2]/30">
                        Belum ada nota selesai atau biaya toko di periode ini. Grafik terisi setelah nota ditandai selesai di Antrean Cuci
                        atau pengeluaran dicatat di menu Pengeluaran.
                    </p>
                    <div v-else class="-mx-2 overflow-x-auto px-2 pb-1">
                        <ul
                            class="flex h-56 items-end gap-1.5 border-b-2 border-black"
                            :style="{ minWidth: `${trend.points.length * 2.25}rem` }"
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
                                <span class="w-full max-w-[18px] rounded-t-[3px] bg-[#FFEB3B] border-t-2 border-x-2 border-black" :style="{ height: barHeight(point.revenue) }" aria-hidden="true"></span>
                                <span class="w-full max-w-[18px] rounded-t-[3px] bg-[#FF5252] border-t-2 border-x-2 border-black" :style="{ height: barHeight(point.expenses) }" aria-hidden="true"></span>
                            </li>
                        </ul>
                        <div class="flex gap-1.5 pt-2" :style="{ minWidth: `${trend.points.length * 2.25}rem` }" aria-hidden="true">
                            <span v-for="point in trend.points" :key="point.key" class="flex-1 text-center text-[11px] font-mono font-bold leading-tight text-black">
                                {{ point.label }}
                            </span>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Full Page Executive Financial Summary (A4 format for printSummary) -->
        <div id="finance-slip" class="hidden text-black font-sans bg-white leading-normal">
            <!-- Header: Store Info & Document Title -->
            <div class="flex items-start justify-between border-b-[3px] border-black pb-4 mb-4">
                <div class="space-y-1">
                    <div class="flex items-center gap-2">
                        <div class="h-8 w-8 rounded-[6px] bg-black text-[#FFEB3B] border-2 border-black flex items-center justify-center font-black text-xs print:text-black print:bg-white print:border-black">
                            AL
                        </div>
                        <h1 class="font-display text-xl font-black tracking-tight text-black">{{ store_info?.name ?? 'Andik Laundry' }}</h1>
                    </div>
                    <p class="text-xs text-gray-700 max-w-md font-medium" v-if="store_info?.address">{{ store_info.address }}</p>
                    <p class="text-xs text-gray-700 font-mono font-medium" v-if="store_info?.phone">WhatsApp: {{ store_info.phone }}</p>
                </div>
                <div class="text-right">
                    <div class="inline-block border-2 border-black bg-white px-3 py-1 text-xs font-black uppercase tracking-wider shadow-[2px_2px_0px_#000] print:shadow-none mb-1">
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
                <!-- Column 1: Akrual SAK EMKM -->
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

                <!-- Column 2: Cash Flow (Kas Nyata) -->
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

            <!-- Reconciliation Note (if gap exists) -->
            <div v-if="showGapNote" class="border-2 border-black rounded-[8px] p-3 mb-4 bg-gray-50 text-xs">
                <div class="font-bold text-black flex items-center gap-1.5 mb-0.5">
                    <span class="font-black uppercase tracking-wider text-[10px] bg-black text-white px-1.5 py-0.5 rounded-[3px]">Catatan Rekonsiliasi</span>
                    <span>Selisih Laba vs Arus Kas Nyata</span>
                </div>
                <p class="text-gray-700 leading-relaxed text-[11px]">
                    Terdapat dana senilai <strong class="font-mono font-bold">{{ formatRupiah(gap) }}</strong> dari laba operasional yang saat ini masih berupa piutang berjalan pelanggan. Laba telah sah diakui karena pekerjaan cucian telah selesai, namun kas nyata belum diterima dari pelanggan terkait.
                </p>
            </div>

            <!-- Signatures Section -->
            <div class="grid grid-cols-2 gap-12 pt-4 pb-2 text-center text-xs">
                <div>
                    <p class="font-bold text-gray-800">Dibuat & Disiapkan Oleh:</p>
                    <div class="h-16 flex items-end justify-center">
                        <div class="border-b-2 border-black w-48 text-center pb-1">
                            <span class="font-bold text-xs">{{ user?.name ?? 'Petugas Kasir' }}</span>
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

            <!-- Document Footer -->
            <div class="border-t border-black pt-2 mt-4 text-[10px] text-gray-500 flex items-center justify-between">
                <span>LMS Andik Laundry • Sistem Manajemen Operasional & Keuangan SAK EMKM</span>
                <span>Dokumen Sah Internal • Dicetak otomatis pada {{ printedAt }}</span>
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
