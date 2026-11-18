<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import {
    Wallet,
    PlusCircle,
    Search,
    Calendar,
    Flame,
    Droplets,
    Package,
    Zap,
    Wrench,
    Tag,
    Trash2,
    CheckCircle2,
    X,
    Filter,
    ArrowDownRight,
    ArrowUpRight,
    Receipt,
} from 'lucide-vue-next';

interface ExpenseItem {
    id: number;
    expense_number: string;
    amount: number;
    category: string;
    description: string;
    payment_method: 'cash' | 'bank_transfer';
    expense_date: string;
    receipt_image_path?: string | null;
    user?: {
        name: string;
    };
}

interface CategoryOption {
    value: string;
    label: string;
}

interface Props {
    expenses: {
        data: ExpenseItem[];
        links: any[];
        total: number;
        current_page: number;
        last_page: number;
        from: number;
        to: number;
    };
    today_summary?: {
        date: string;
        total_expenses: number;
        cash_expenses: number;
        transfer_expenses: number;
        expense_count: number;
        by_category: { category: string; count: number; total: number }[];
    };
    categories: CategoryOption[];
    filters?: {
        search?: string;
        category?: string;
        start_date?: string;
        end_date?: string;
    };
}

const props = defineProps<Props>();

// Filters state
const search = ref(props.filters?.search ?? '');
const selectedCategory = ref(props.filters?.category ?? '');
const startDate = ref(props.filters?.start_date ?? '');
const endDate = ref(props.filters?.end_date ?? '');

function applyFilter() {
    router.get(
        '/owner/expenses',
        {
            search: search.value || undefined,
            category: selectedCategory.value || undefined,
            start_date: startDate.value || undefined,
            end_date: endDate.value || undefined,
        },
        { preserveState: true, replace: true }
    );
}

function resetFilter() {
    search.value = '';
    selectedCategory.value = '';
    startDate.value = '';
    endDate.value = '';
    applyFilter();
}

// Modal State
const isAddModalOpen = ref(false);

const form = useForm({
    amount: '',
    category: 'gas_lpg',
    description: '',
    payment_method: 'cash',
    expense_date: new Date().toISOString().split('T')[0],
});

function submitExpense() {
    form.post('/owner/expenses', {
        onSuccess: () => {
            isAddModalOpen.value = false;
            form.reset('amount', 'description');
        },
    });
}

function setQuickNominal(val: number) {
    form.amount = val.toString();
}

function deleteExpense(expense: ExpenseItem) {
    if (confirm(`Hapus catatan pengeluaran ${expense.expense_number} sebesar Rp ${formatNumber(expense.amount)}?`)) {
        router.delete(`/owner/expenses/${expense.id}`);
    }
}

function formatNumber(val: number | string): string {
    const num = typeof val === 'string' ? parseFloat(val) : val;
    return new Intl.NumberFormat('id-ID').format(num || 0);
}

function getCategoryIcon(cat: string) {
    switch (cat) {
        case 'gas_lpg':
            return Flame;
        case 'detergent_chemicals':
            return Droplets;
        case 'packaging_plastic':
            return Package;
        case 'utilities':
            return Zap;
        case 'maintenance':
            return Wrench;
        default:
            return Tag;
    }
}

function getCategoryColor(cat: string) {
    switch (cat) {
        case 'gas_lpg':
            return 'bg-warning/15 text-warning border-warning/30';
        case 'detergent_chemicals':
            return 'bg-primary-100 text-primary-700 border-primary-500/30';
        case 'packaging_plastic':
            return 'bg-accent-100 text-accent-700 border-accent-500/30';
        case 'utilities':
            return 'bg-warning/15 text-warning border-warning/30';
        case 'maintenance':
            return 'bg-danger/15 text-danger border-danger/30';
        default:
            return 'bg-primary-100 text-navy-800 border-border';
    }
}
</script>

<template>
    <AppLayout title="Pengeluaran Operasional Toko">
        <div class="space-y-6">
            <!-- Header Title and Add Button -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-surface p-5 rounded-lg border border-border shadow-sm">
                <div>
                    <div class="flex items-center space-x-2">
                        <h1 class="text-2xl font-heading font-bold text-text tracking-tight">Pengeluaran Toko</h1>
                        <Badge variant="teal" class="text-xs">Buku Kas</Badge>
                    </div>
                    <p class="text-sm text-text-muted mt-1">
                        Pencatatan biaya beli gas LPG, sabun, deterjen, plastik, dan kebutuhan operasional harian.
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <Button
                        type="button"
                        variant="default"
                        @click="isAddModalOpen = true"
                        class="min-h-[44px] gap-2 font-bold shadow-sm"
                    >
                        <PlusCircle class="h-4 w-4" />
                        <span>Catat Pengeluaran Baru</span>
                    </Button>
                </div>
            </div>

            <!-- Top Summary KPI Cards (Today) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <Card class="border-border shadow-sm rounded-lg bg-surface">
                    <CardHeader class="pb-2">
                        <CardTitle class="text-xs uppercase font-bold text-text-muted">Total Pengeluaran Hari Ini</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold font-mono text-danger tabular-nums">
                            Rp {{ formatNumber(today_summary?.total_expenses ?? 0) }}
                        </div>
                        <p class="text-xs text-text-muted mt-1">{{ today_summary?.expense_count ?? 0 }} transaksi belanja</p>
                    </CardContent>
                </Card>

                <Card class="border-border shadow-sm rounded-lg bg-surface">
                    <CardHeader class="pb-2">
                        <CardTitle class="text-xs uppercase font-bold text-text-muted">Kas Tunai Keluar (Laci)</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold font-mono text-warning tabular-nums">
                            Rp {{ formatNumber(today_summary?.cash_expenses ?? 0) }}
                        </div>
                        <p class="text-xs text-text-muted mt-1">Mengurangi uang tunai di laci</p>
                    </CardContent>
                </Card>

                <Card class="border-border shadow-sm rounded-lg bg-surface">
                    <CardHeader class="pb-2">
                        <CardTitle class="text-xs uppercase font-bold text-text-muted">Non-Tunai / Transfer</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold font-mono text-primary-700 tabular-nums">
                            Rp {{ formatNumber(today_summary?.transfer_expenses ?? 0) }}
                        </div>
                        <p class="text-xs text-text-muted mt-1">Dibayar via rekening bank</p>
                    </CardContent>
                </Card>

                <Card class="border border-border shadow-sm rounded-lg bg-surface">
                    <CardHeader class="pb-2">
                        <CardTitle class="text-xs uppercase font-bold text-text-muted">Status Buku Kas</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-base font-bold text-text flex items-center space-x-1.5">
                            <CheckCircle2 class="h-4 w-4 text-success" />
                            <span>Tercatat Otomatis</span>
                        </div>
                        <p class="text-xs font-medium text-text-muted mt-1">Masuk ke Rekap Kas Harian</p>
                    </CardContent>
                </Card>
            </div>

            <!-- Filter Controls -->
            <div class="bg-surface p-4 rounded-lg border border-border shadow-sm flex flex-wrap items-center gap-3">
                <div class="relative flex-1 min-w-[200px]">
                    <Search class="h-4 w-4 text-text-muted absolute left-3 top-3.5" />
                    <input
                        v-model="search"
                        @keydown.enter="applyFilter"
                        type="text"
                        placeholder="Cari nomor nota atau keterangan..."
                        class="w-full pl-9 pr-4 py-2 border border-border rounded-md text-sm font-medium text-text bg-surface min-h-[44px] focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-600"
                    />
                </div>

                <select
                    v-model="selectedCategory"
                    @change="applyFilter"
                    class="border border-border rounded-md px-3 py-2 text-sm font-medium text-text min-h-[44px] bg-surface focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-600"
                >
                    <option value="">Semua Kategori</option>
                    <option v-for="cat in categories" :key="cat.value" :value="cat.value">
                        {{ cat.label }}
                    </option>
                </select>

                <div class="flex items-center gap-2">
                    <input
                        v-model="startDate"
                        @change="applyFilter"
                        type="date"
                        class="border border-border rounded-md px-3 py-2 text-sm font-medium text-text min-h-[44px] bg-surface focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-600"
                    />
                    <span class="text-xs font-semibold text-text-muted">s/d</span>
                    <input
                        v-model="endDate"
                        @change="applyFilter"
                        type="date"
                        class="border border-border rounded-md px-3 py-2 text-sm font-medium text-text min-h-[44px] bg-surface focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-600"
                    />
                </div>

                <Button type="button" variant="outline" @click="resetFilter" class="min-h-[44px] text-xs font-bold uppercase tracking-wider">
                    Reset
                </Button>
            </div>

            <!-- Table of Expenses -->
            <Card class="border border-border shadow-sm rounded-lg overflow-hidden bg-surface">
                <CardHeader>
                    <CardTitle class="text-base font-heading font-bold text-text">Daftar Pengeluaran Operasional</CardTitle>
                    <CardDescription class="text-xs font-medium text-text-muted">
                        Total {{ expenses.total }} catatan transaksi
                    </CardDescription>
                </CardHeader>
                <CardContent class="p-0">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm text-text">
                            <thead class="bg-bg border-y border-border text-xs uppercase tracking-wider text-text-muted font-semibold">
                                <tr>
                                    <th class="px-5 py-3">No. Pengeluaran</th>
                                    <th class="px-5 py-3">Tanggal</th>
                                    <th class="px-5 py-3">Kategori</th>
                                    <th class="px-5 py-3">Keterangan</th>
                                    <th class="px-5 py-3">Metode Bayar</th>
                                    <th class="px-5 py-3 text-right">Nominal</th>
                                    <th class="px-5 py-3 text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border">
                                <tr v-if="expenses.data.length === 0">
                                    <td colspan="7" class="px-5 py-8 text-center text-text-muted text-sm">
                                        Belum ada catatan pengeluaran operasional.
                                    </td>
                                </tr>
                                <tr v-for="exp in expenses.data" :key="exp.id" class="hover:bg-bg transition-colors">
                                    <td class="px-5 py-4 font-mono font-bold text-xs text-text">
                                        {{ exp.expense_number }}
                                    </td>
                                    <td class="px-5 py-4 text-xs text-text-muted">
                                        {{ exp.expense_date }}
                                    </td>
                                    <td class="px-5 py-4">
                                        <span :class="['inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-pill text-xs font-semibold border', getCategoryColor(exp.category)]">
                                            <component :is="getCategoryIcon(exp.category)" class="h-3 w-3" />
                                            {{ categories.find(c => c.value === exp.category)?.label ?? exp.category }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-4 text-sm text-text">
                                        {{ exp.description }}
                                    </td>
                                    <td class="px-5 py-4 text-xs font-semibold">
                                        <Badge :variant="exp.payment_method === 'cash' ? 'outline' : 'secondary'">
                                            {{ exp.payment_method === 'cash' ? 'Tunai Laci' : 'Transfer' }}
                                        </Badge>
                                    </td>
                                    <td class="px-5 py-4 font-mono font-bold text-sm text-right text-danger tabular-nums">
                                        Rp {{ formatNumber(exp.amount) }}
                                    </td>
                                    <td class="px-5 py-4 text-center">
                                        <button
                                            type="button"
                                            @click="deleteExpense(exp)"
                                            class="inline-flex min-h-[44px] min-w-[44px] items-center justify-center text-text-muted hover:text-danger hover:bg-danger/10 rounded-md transition-colors cursor-pointer"
                                            title="Hapus Pengeluaran"
                                        >
                                            <Trash2 class="h-4 w-4" />
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </CardContent>
            </Card>

            <!-- Add Expense Modal Dialog -->
            <div
                v-if="isAddModalOpen"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-navy-950/60 backdrop-blur-xs"
                @click.self="isAddModalOpen = false"
            >
                <div class="bg-surface rounded-xl max-w-lg w-full p-6 shadow-lg border border-border space-y-5 animate-in fade-in-0 zoom-in-95">
                    <div class="flex items-center justify-between pb-3 border-b border-border">
                        <div>
                            <h2 class="text-lg font-heading font-bold text-text">Catat Pengeluaran Operasional</h2>
                            <p class="text-xs text-text-muted">Mencatat pembelian bahan / pengeluaran kas toko</p>
                        </div>
                        <button
                            type="button"
                            @click="isAddModalOpen = false"
                            class="inline-flex min-h-[44px] min-w-[44px] items-center justify-center text-text-muted hover:text-text rounded-md cursor-pointer"
                        >
                            <X class="h-5 w-5" />
                        </button>
                    </div>

                    <form @submit.prevent="submitExpense" class="space-y-4">
                        <!-- Nominal -->
                        <div>
                            <label class="block text-xs font-bold text-text uppercase mb-1">
                                Nominal Pengeluaran (Rp) *
                            </label>
                            <input
                                v-model="form.amount"
                                type="number"
                                placeholder="Contoh: 44000"
                                required
                                class="w-full px-4 py-2.5 rounded-md border border-border font-mono text-lg font-bold min-h-[44px] text-text bg-surface focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-600"
                            />
                            <div v-if="form.errors.amount" class="text-danger text-xs mt-1">
                                {{ form.errors.amount }}
                            </div>

                            <!-- Quick Suggestion Buttons -->
                            <div class="flex flex-wrap gap-2 mt-2">
                                <button
                                    type="button"
                                    @click="setQuickNominal(22000)"
                                    class="text-xs px-2.5 py-1 rounded-pill border border-border bg-bg text-text hover:bg-primary-100 font-mono cursor-pointer"
                                >
                                    +22.000 (1 Gas)
                                </button>
                                <button
                                    type="button"
                                    @click="setQuickNominal(44000)"
                                    class="text-xs px-2.5 py-1 rounded-pill border border-border bg-bg text-text hover:bg-primary-100 font-mono cursor-pointer"
                                >
                                    +44.000 (2 Gas)
                                </button>
                                <button
                                    type="button"
                                    @click="setQuickNominal(50000)"
                                    class="text-xs px-2.5 py-1 rounded-pill border border-border bg-bg text-text hover:bg-primary-100 font-mono cursor-pointer"
                                >
                                    +50.000
                                </button>
                                <button
                                    type="button"
                                    @click="setQuickNominal(100000)"
                                    class="text-xs px-2.5 py-1 rounded-pill border border-border bg-bg text-text hover:bg-primary-100 font-mono cursor-pointer"
                                >
                                    +100.000
                                </button>
                            </div>
                        </div>

                        <!-- Category -->
                        <div>
                            <label class="block text-xs font-bold text-text uppercase mb-1">
                                Kategori *
                            </label>
                            <select
                                v-model="form.category"
                                class="w-full px-4 py-2.5 rounded-md border border-border text-sm text-text min-h-[44px] bg-surface focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-600"
                            >
                                <option v-for="cat in categories" :key="cat.value" :value="cat.value">
                                    {{ cat.label }}
                                </option>
                            </select>
                        </div>

                        <!-- Description -->
                        <div>
                            <label class="block text-xs font-bold text-text uppercase mb-1">
                                Keterangan Pembelian *
                            </label>
                            <input
                                v-model="form.description"
                                type="text"
                                placeholder="Contoh: Beli 2 tabung gas LPG 3kg di Toko Bu Joko"
                                required
                                class="w-full px-4 py-2.5 rounded-md border border-border text-sm text-text min-h-[44px] bg-surface focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-600"
                            />
                            <div v-if="form.errors.description" class="text-danger text-xs mt-1">
                                {{ form.errors.description }}
                            </div>
                        </div>

                        <!-- Payment Method & Date -->
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-bold text-text uppercase mb-1">
                                    Metode Pembayaran
                                </label>
                                <select
                                    v-model="form.payment_method"
                                    class="w-full px-3 py-2.5 rounded-md border border-border text-sm text-text min-h-[44px] bg-surface focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-600"
                                >
                                    <option value="cash">Kas Tunai (Laci)</option>
                                    <option value="bank_transfer">Transfer Bank</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-text uppercase mb-1">
                                    Tanggal
                                </label>
                                <input
                                    v-model="form.expense_date"
                                    type="date"
                                    class="w-full px-3 py-2.5 rounded-md border border-border text-sm text-text min-h-[44px] bg-surface focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-600"
                                />
                            </div>
                        </div>

                        <div class="pt-3 border-t border-border flex items-center justify-end gap-3">
                            <Button
                                type="button"
                                variant="outline"
                                @click="isAddModalOpen = false"
                                class="min-h-[44px]"
                            >
                                Batal
                            </Button>
                            <Button
                                type="submit"
                                variant="default"
                                :disabled="form.processing"
                                class="min-h-[44px] font-bold"
                            >
                                {{ form.processing ? 'Menyimpan...' : 'Simpan Pengeluaran' }}
                            </Button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

