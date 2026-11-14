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
            <div class="flex flex-col gap-4 rounded-xl border border-slate-200 bg-white p-5 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-slate-900">Keuangan Toko</h1>
                    <p class="mt-1 text-sm text-slate-600">
                        Periode <span class="font-semibold text-slate-900">{{ periodLabel }}</span>
                        <span class="text-slate-500">({{ period.days }} hari)</span>
                    </p>
                </div>

                <div class="flex flex-wrap gap-2">
                    <Button as="a" :href="csvUrl" variant="outline" class="gap-2 font-semibold text-slate-800">
                        <Download class="h-4 w-4" aria-hidden="true" />
                        <span>Unduh CSV</span>
                    </Button>
                    <Button
                        type="button"
                        class="gap-2 bg-slate-900 font-semibold text-white hover:bg-slate-800"
                        @click="printSummary"
                    >
                        <Printer class="h-4 w-4" aria-hidden="true" />
                        <span>Cetak Ringkasan</span>
                    </Button>
                </div>
            </div>

            <!-- Period filter -->
            <div class="space-y-3 rounded-xl border border-slate-200 bg-white p-4">
                <div role="group" aria-label="Pilih periode laporan" class="flex flex-wrap gap-2">
                    <button
                        v-for="p in presets"
                        :key="p.key"
                        type="button"
                        :aria-pressed="activePreset === p.key"
                        :disabled="loading"
                        class="min-h-[44px] rounded-lg border px-4 text-sm font-semibold transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-teal-600 focus-visible:ring-offset-2 disabled:opacity-60"
                        :class="
                            activePreset === p.key
                                ? 'border-teal-700 bg-teal-700 text-white'
                                : 'border-slate-300 bg-white text-slate-800 hover:bg-slate-50'
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
                        class="min-h-[44px] rounded-lg border px-4 text-sm font-semibold transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-teal-600 focus-visible:ring-offset-2"
                        :class="
                            activePreset === 'custom'
                                ? 'border-teal-700 bg-teal-700 text-white'
                                : 'border-slate-300 bg-white text-slate-800 hover:bg-slate-50'
                        "
                        @click="selectPreset('custom')"
                    >
                        Rentang Kustom
                    </button>
                </div>

                <form
                    v-if="activePreset === 'custom'"
                    id="custom-range"
                    class="flex flex-col gap-3 sm:flex-row sm:items-end"
                    @submit.prevent="applyCustom"
                >
                    <label class="flex flex-col gap-1 text-sm font-medium text-slate-700">
                        Tanggal Awal
                        <input
                            v-model="customStart"
                            type="date"
                            name="start_date"
                            required
                            :max="customEnd || undefined"
                            class="min-h-[44px] rounded-lg border border-slate-300 bg-white px-3 text-slate-900 focus:border-teal-600 focus:outline-none focus:ring-2 focus:ring-teal-600/30"
                        />
                    </label>
                    <label class="flex flex-col gap-1 text-sm font-medium text-slate-700">
                        Tanggal Akhir
                        <input
                            v-model="customEnd"
                            type="date"
                            name="end_date"
                            required
                            :min="customStart || undefined"
                            class="min-h-[44px] rounded-lg border border-slate-300 bg-white px-3 text-slate-900 focus:border-teal-600 focus:outline-none focus:ring-2 focus:ring-teal-600/30"
                        />
                    </label>
                    <Button type="submit" :disabled="customInvalid || loading" class="font-semibold">Terapkan</Button>
                </form>

                <p v-if="errors.start_date || errors.end_date" role="alert" class="text-sm font-medium text-rose-700">
                    {{ errors.start_date || errors.end_date }}
                </p>
                <p v-if="loading" class="text-sm text-slate-600" aria-live="polite">Memuat laporan periode baru…</p>
            </div>

            <!-- Accrual profit vs cash on hand, side by side -->
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2" :class="{ 'opacity-60': loading }">
                <Card class="border-indigo-200">
                    <CardHeader class="flex flex-row items-start justify-between space-y-0 pb-3">
                        <div>
                            <CardTitle class="text-base font-bold text-indigo-950">Laba Bersih Usaha</CardTitle>
                            <CardDescription class="text-xs text-slate-600">Akrual SAK EMKM: dihitung dari nota yang sudah selesai</CardDescription>
                        </div>
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-indigo-50 text-indigo-700">
                            <Scale class="h-5 w-5" aria-hidden="true" />
                        </div>
                    </CardHeader>
                    <CardContent>
                        <dl class="space-y-2 text-sm">
                            <div class="flex justify-between gap-3">
                                <dt class="text-slate-700">Omzet selesai diakui</dt>
                                <dd class="font-mono font-semibold text-slate-900">{{ formatRupiah(accrual.recognized_revenue) }}</dd>
                            </div>
                            <div class="flex justify-between gap-3">
                                <dt class="text-slate-700">Total biaya toko</dt>
                                <dd class="font-mono font-semibold text-slate-900">− {{ formatRupiah(accrual.operating_expenses) }}</dd>
                            </div>
                            <div class="flex items-baseline justify-between gap-3 border-t border-indigo-100 pt-3">
                                <dt class="font-bold text-indigo-950">Laba bersih</dt>
                                <dd
                                    class="font-mono text-2xl font-bold"
                                    :class="accrual.net_operating_profit < 0 ? 'text-rose-700' : 'text-indigo-800'"
                                >
                                    {{ formatRupiah(accrual.net_operating_profit) }}
                                </dd>
                            </div>
                            <div class="flex justify-between gap-3 text-xs text-slate-600">
                                <dt>Margin laba</dt>
                                <dd class="font-mono">{{ accrual.profit_margin_percent }}%</dd>
                            </div>
                        </dl>
                    </CardContent>
                </Card>

                <Card class="border-emerald-200">
                    <CardHeader class="flex flex-row items-start justify-between space-y-0 pb-3">
                        <div>
                            <CardTitle class="text-base font-bold text-emerald-950">Uang Kas Nyata di Tangan</CardTitle>
                            <CardDescription class="text-xs text-slate-600">Uang yang benar-benar masuk dan keluar di periode ini</CardDescription>
                        </div>
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-emerald-50 text-emerald-700">
                            <Wallet class="h-5 w-5" aria-hidden="true" />
                        </div>
                    </CardHeader>
                    <CardContent>
                        <dl class="space-y-2 text-sm">
                            <div class="flex justify-between gap-3">
                                <dt class="text-slate-700">Uang diterima kasir</dt>
                                <dd class="font-mono font-semibold text-slate-900">{{ formatRupiah(cash.cash_in.total) }}</dd>
                            </div>
                            <div class="flex justify-between gap-3 pl-3 text-xs text-slate-600">
                                <dt>Tunai</dt>
                                <dd class="font-mono">{{ formatRupiah(cash.cash_in.tunai) }}</dd>
                            </div>
                            <div class="flex justify-between gap-3 pl-3 text-xs text-slate-600">
                                <dt>Transfer / QRIS / e-wallet</dt>
                                <dd class="font-mono">{{ formatRupiah(digitalIn) }}</dd>
                            </div>
                            <div class="flex justify-between gap-3">
                                <dt class="text-slate-700">Kas keluar</dt>
                                <dd class="font-mono font-semibold text-slate-900">− {{ formatRupiah(cash.cash_out) }}</dd>
                            </div>
                            <div class="flex items-baseline justify-between gap-3 border-t border-emerald-100 pt-3">
                                <dt class="font-bold text-emerald-950">Saldo kas bersih</dt>
                                <dd
                                    class="font-mono text-2xl font-bold"
                                    :class="cash.net_cash_flow < 0 ? 'text-rose-700' : 'text-emerald-800'"
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
                class="flex items-start gap-3 rounded-xl border border-sky-200 bg-sky-50 p-4 text-sm text-sky-950"
                role="note"
            >
                <Info class="mt-0.5 h-5 w-5 shrink-0 text-sky-700" aria-hidden="true" />
                <p>
                    Ada <strong class="font-mono">{{ formatRupiah(gap) }}</strong> uang laba yang masih tertahan di piutang pelanggan.
                    <span class="text-sky-900">Laba sudah tercatat karena nota selesai, tapi uangnya belum masuk kas.</span>
                </p>
            </div>

            <!-- Outstanding receivables -->
            <Card class="border-amber-200" :class="{ 'opacity-60': loading }">
                <CardContent class="flex flex-col gap-4 p-5 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-start gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-amber-50 text-amber-800">
                            <Receipt class="h-5 w-5" aria-hidden="true" />
                        </div>
                        <div>
                            <h2 class="text-base font-bold text-amber-950">Piutang Pelanggan Berjalan</h2>
                            <p class="font-mono text-2xl font-bold text-amber-800">{{ formatRupiah(receivables.total_outstanding) }}</p>
                            <p class="text-xs text-slate-600">
                                {{ receivables.orders_count }} nota belum lunas dari {{ receivables.customers_count }} pelanggan · semua periode
                            </p>
                        </div>
                    </div>
                    <Button as-child variant="outline" class="gap-2 border-amber-300 font-semibold text-amber-900 hover:bg-amber-50">
                        <Link href="/owner/receivables">
                            <span>Lihat & tagih piutang</span>
                            <ArrowRight class="h-4 w-4" aria-hidden="true" />
                        </Link>
                    </Button>
                </CardContent>
            </Card>

            <!-- Trend chart -->
            <Card class="border-slate-200">
                <CardHeader>
                    <CardTitle class="text-base font-bold text-slate-900">Omzet selesai vs biaya toko per {{ trendUnit }}</CardTitle>
                    <div class="flex flex-wrap gap-4 pt-1 text-xs text-slate-700" aria-hidden="true">
                        <span class="flex items-center gap-1.5"><span class="h-3 w-3 rounded-sm bg-teal-600"></span>Omzet selesai</span>
                        <span class="flex items-center gap-1.5"><span class="h-3 w-3 rounded-sm bg-rose-500"></span>Biaya toko</span>
                    </div>
                </CardHeader>
                <CardContent>
                    <p v-if="trendEmpty" class="rounded-lg border border-dashed border-slate-300 p-6 text-center text-sm text-slate-600">
                        Belum ada nota selesai atau biaya toko di periode ini. Grafik terisi setelah nota ditandai selesai di Antrean Cuci
                        atau pengeluaran dicatat di menu Pengeluaran.
                    </p>
                    <div v-else class="-mx-2 overflow-x-auto px-2 pb-1">
                        <ul
                            class="flex h-56 items-end gap-1.5 border-b border-slate-300"
                            :style="{ minWidth: `${trend.points.length * 2.25}rem` }"
                            :aria-label="`Grafik omzet dan biaya per ${trendUnit}`"
                        >
                            <li
                                v-for="point in trend.points"
                                :key="point.key"
                                class="flex h-full flex-1 items-end justify-center gap-0.5"
                                :title="`${point.label}: omzet ${formatRupiah(point.revenue)}, biaya ${formatRupiah(point.expenses)}`"
                            >
                                <span class="sr-only">
                                    {{ point.label }}: omzet {{ formatRupiah(point.revenue) }}, biaya {{ formatRupiah(point.expenses) }}
                                </span>
                                <span class="w-full max-w-[18px] rounded-t-sm bg-teal-600" :style="{ height: barHeight(point.revenue) }" aria-hidden="true"></span>
                                <span class="w-full max-w-[18px] rounded-t-sm bg-rose-500" :style="{ height: barHeight(point.expenses) }" aria-hidden="true"></span>
                            </li>
                        </ul>
                        <div class="flex gap-1.5 pt-1.5" :style="{ minWidth: `${trend.points.length * 2.25}rem` }" aria-hidden="true">
                            <span v-for="point in trend.points" :key="point.key" class="flex-1 text-center text-[11px] leading-tight text-slate-600">
                                {{ point.label }}
                            </span>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- 80mm thermal summary; only rendered by printSummary() -->
        <div id="finance-slip" class="hidden font-mono text-xs text-black">
            <div class="border-b border-dashed border-black pb-2 text-center">
                <div class="text-sm font-bold">{{ store_info?.name ?? 'Andik Laundry' }}</div>
                <div v-if="store_info?.address">{{ store_info.address }}</div>
                <div v-if="store_info?.phone">Telp: {{ store_info.phone }}</div>
            </div>
            <div class="py-2 text-center font-bold">RINGKASAN LABA RUGI</div>
            <div class="flex justify-between border-b border-dashed border-black pb-2">
                <span>Periode</span><span>{{ periodLabel }}</span>
            </div>

            <div class="space-y-1 border-b border-dashed border-black py-2">
                <div class="font-bold">LABA USAHA (AKRUAL)</div>
                <div class="flex justify-between"><span>Omzet selesai</span><span>{{ formatRupiah(accrual.recognized_revenue) }}</span></div>
                <div class="flex justify-between"><span>Biaya toko</span><span>-{{ formatRupiah(accrual.operating_expenses) }}</span></div>
                <div class="flex justify-between border-t border-dotted border-black pt-1 font-bold">
                    <span>LABA BERSIH</span><span>{{ formatRupiah(accrual.net_operating_profit) }}</span>
                </div>
            </div>

            <div class="space-y-1 border-b border-dashed border-black py-2">
                <div class="font-bold">KAS NYATA</div>
                <div class="flex justify-between"><span>Tunai</span><span>{{ formatRupiah(cash.cash_in.tunai) }}</span></div>
                <div class="flex justify-between"><span>Transfer</span><span>{{ formatRupiah(cash.cash_in.transfer) }}</span></div>
                <div class="flex justify-between"><span>QRIS</span><span>{{ formatRupiah(cash.cash_in.qris) }}</span></div>
                <div class="flex justify-between"><span>E-wallet</span><span>{{ formatRupiah(cash.cash_in.ewallet) }}</span></div>
                <div class="flex justify-between"><span>Total masuk</span><span>{{ formatRupiah(cash.cash_in.total) }}</span></div>
                <div class="flex justify-between"><span>Kas keluar</span><span>-{{ formatRupiah(cash.cash_out) }}</span></div>
                <div class="flex justify-between border-t border-dotted border-black pt-1 font-bold">
                    <span>SALDO KAS</span><span>{{ formatRupiah(cash.net_cash_flow) }}</span>
                </div>
            </div>

            <div class="space-y-1 border-b border-dashed border-black py-2">
                <div class="flex justify-between"><span>Piutang berjalan</span><span>{{ formatRupiah(receivables.total_outstanding) }}</span></div>
                <div v-if="showGapNote" class="pt-1">Laba tertahan di piutang: {{ formatRupiah(gap) }}</div>
            </div>

            <div class="pt-2 text-center text-[10px]">Dicetak {{ printedAt }}</div>
        </div>
    </AppLayout>
</template>

<style>
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
        left: 0;
        top: 0;
        width: 80mm;
        padding: 3mm;
        background: white;
    }
}
</style>
