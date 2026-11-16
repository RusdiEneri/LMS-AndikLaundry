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
            return 'bg-amber-100 text-amber-800 border-amber-300';
        case 'detergent_chemicals':
            return 'bg-cyan-100 text-cyan-800 border-cyan-300';
        case 'packaging_plastic':
            return 'bg-indigo-100 text-indigo-800 border-indigo-300';
        case 'utilities':
            return 'bg-yellow-100 text-yellow-800 border-yellow-300';
        case 'maintenance':
            return 'bg-rose-100 text-rose-800 border-rose-300';
        default:
            return 'bg-slate-100 text-slate-800 border-slate-300';
    }
}
</script>

<template>
    <AppLayout title="Pengeluaran Operasional Toko">
        <div class="space-y-6">
            <!-- Header Title and Add Button -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white p-5 rounded-xl border border-slate-200 shadow-xs">
                <div>
                    <div class="flex items-center space-x-2">
                        <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Pengeluaran Toko</h1>
                        <Badge variant="teal" class="text-xs">Buku Kas</Badge>
                    </div>
                    <p class="text-sm text-slate-500 mt-1">
                        Pencatatan biaya beli gas LPG, sabun, deterjen, plastik, dan kebutuhan operasional harian.
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <Button
                        type="button"
                        @click="isAddModalOpen = true"
                        class="min-h-[44px] gap-2 font-bold shadow-[0_4px_12px_rgba(99,102,241,0.35)] bg-[#6366F1] hover:bg-[#4F46E5] text-white rounded-[6px]"
                    >
                        <PlusCircle class="h-4 w-4" />
                        <span>Catat Pengeluaran Baru</span>
                    </Button>
                </div>
            </div>

            <!-- Top Summary KPI Cards (Today) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <Card class="border-slate-200 shadow-xs">
                    <CardHeader class="pb-2">
                        <CardTitle class="text-xs uppercase font-bold text-slate-500">Total Pengeluaran Hari Ini</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold font-mono text-rose-600">
                            Rp {{ formatNumber(today_summary?.total_expenses ?? 0) }}
                        </div>
                        <p class="text-xs text-slate-500 mt-1">{{ today_summary?.expense_count ?? 0 }} transaksi belanja</p>
                    </CardContent>
                </Card>

                <Card class="border-slate-200 shadow-xs">
                    <CardHeader class="pb-2">
                        <CardTitle class="text-xs uppercase font-bold text-slate-500">Kas Tunai Keluar (Laci)</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold font-mono text-amber-700">
                            Rp {{ formatNumber(today_summary?.cash_expenses ?? 0) }}
                        </div>
                        <p class="text-xs text-slate-500 mt-1">Mengurangi uang tunai di laci</p>
                    </CardContent>
                </Card>

                <Card class="border-slate-200 shadow-xs">
                    <CardHeader class="pb-2">
                        <CardTitle class="text-xs uppercase font-bold text-slate-500">Non-Tunai / Transfer</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold font-mono text-indigo-700">
                            Rp {{ formatNumber(today_summary?.transfer_expenses ?? 0) }}
                        </div>
                        <p class="text-xs text-slate-500 mt-1">Dibayar via rekening bank</p>
                    </CardContent>
                </Card>

                <Card class="border-[#E8E8EC] shadow-xs bg-[#6366F1]/5">
                    <CardHeader class="pb-2">
                        <CardTitle class="text-xs uppercase font-bold text-[#6366F1]">Status Buku Kas</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-base font-bold text-[#0A0A0A] flex items-center space-x-1.5">
                            <CheckCircle2 class="h-4 w-4 text-[#10B981]" />
                            <span>Tercatat Otomatis</span>
                        </div>
                        <p class="text-xs text-[#6B6B6B] mt-1">Masuk ke Rekap Kas Harian</p>
                    </CardContent>
                </Card>
            </div>

            <!-- Filter Controls -->
            <div class="bg-white p-4 rounded-[12px] border border-[#E8E8EC] shadow-xs flex flex-wrap items-center gap-3">
                <div class="relative flex-1 min-w-[200px]">
                    <Search class="h-4 w-4 text-[#6B6B6B] absolute left-3 top-3.5" />
                    <input
                        v-model="search"
                        @keydown.enter="applyFilter"
                        type="text"
                        placeholder="Cari nomor nota atau keterangan..."
                        class="w-full pl-9 pr-4 py-2 border border-[#E8E8EC] rounded-[6px] text-sm text-[#0A0A0A] min-h-[44px] focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-[#6366F1]/12"
                    />
                </div>

                <select
                    v-model="selectedCategory"
                    @change="applyFilter"
                    class="border border-[#E8E8EC] rounded-[6px] px-3 py-2 text-sm text-[#0A0A0A] min-h-[44px] bg-white focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-[#6366F1]/12"
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
                        class="border border-[#E8E8EC] rounded-[6px] px-3 py-2 text-sm text-[#0A0A0A] min-h-[44px] bg-white focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-[#6366F1]/12"
                    />
                    <span class="text-xs text-[#6B6B6B]">s/d</span>
                    <input
                        v-model="endDate"
                        @change="applyFilter"
                        type="date"
                        class="border border-[#E8E8EC] rounded-[6px] px-3 py-2 text-sm text-[#0A0A0A] min-h-[44px] bg-white focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-[#6366F1]/12"
                    />
                </div>

                <Button type="button" variant="outline" @click="resetFilter" class="min-h-[44px] text-xs">
                    Reset
                </Button>
            </div>

            <!-- Table of Expenses -->
            <Card class="border-slate-200 shadow-xs">
                <CardHeader>
                    <CardTitle class="text-base font-bold text-slate-900">Daftar Pengeluaran Operasional</CardTitle>
                    <CardDescription class="text-xs text-slate-500">
                        Total {{ expenses.total }} catatan transaksi
                    </CardDescription>
                </CardHeader>
                <CardContent class="p-0">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm text-slate-700">
                            <thead class="bg-slate-50 border-y border-slate-200 text-[11px] uppercase tracking-wider text-slate-500 font-bold">
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
                            <tbody class="divide-y divide-slate-200">
                                <tr v-if="expenses.data.length === 0">
                                    <td colspan="7" class="px-5 py-8 text-center text-slate-400 text-sm">
                                        Belum ada catatan pengeluaran operasional.
                                    </td>
                                </tr>
                                <tr v-for="exp in expenses.data" :key="exp.id" class="hover:bg-slate-50 transition-colors">
                                    <td class="px-5 py-4 font-mono font-bold text-xs text-slate-900">
                                        {{ exp.expense_number }}
                                    </td>
                                    <td class="px-5 py-4 text-xs text-slate-600">
                                        {{ exp.expense_date }}
                                    </td>
                                    <td class="px-5 py-4">
                                        <span :class="['inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold border', getCategoryColor(exp.category)]">
                                            <component :is="getCategoryIcon(exp.category)" class="h-3 w-3" />
                                            {{ categories.find(c => c.value === exp.category)?.label ?? exp.category }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-4 text-sm text-slate-800">
                                        {{ exp.description }}
                                    </td>
                                    <td class="px-5 py-4 text-xs font-semibold">
                                        <Badge :variant="exp.payment_method === 'cash' ? 'outline' : 'secondary'">
                                            {{ exp.payment_method === 'cash' ? 'Tunai Laci' : 'Transfer' }}
                                        </Badge>
                                    </td>
                                    <td class="px-5 py-4 font-mono font-bold text-sm text-right text-rose-700">
                                        Rp {{ formatNumber(exp.amount) }}
                                    </td>
                                    <td class="px-5 py-4 text-center">
                                        <button
                                            type="button"
                                            @click="deleteExpense(exp)"
                                            class="inline-flex min-h-[44px] min-w-[44px] items-center justify-center text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-colors"
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
                class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 p-4"
            >
                <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl space-y-5 animate-in fade-in-0 zoom-in-95">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <div>
                            <h2 class="text-lg font-bold text-slate-900">Catat Pengeluaran Operasional</h2>
                            <p class="text-xs text-slate-500">Mencatat pembelian bahan / pengeluaran kas toko</p>
                        </div>
                        <button
                            type="button"
                            @click="isAddModalOpen = false"
                            class="inline-flex min-h-[44px] min-w-[44px] items-center justify-center text-slate-400 hover:text-slate-700 rounded-lg"
                        >
                            <X class="h-5 w-5" />
                        </button>
                    </div>

                    <form @submit.prevent="submitExpense" class="space-y-4">
                        <!-- Nominal -->
                        <div>
                            <label class="block text-xs font-bold text-[#0A0A0A] uppercase mb-1">
                                Nominal Pengeluaran (Rp) *
                            </label>
                            <input
                                v-model="form.amount"
                                type="number"
                                placeholder="Contoh: 44000"
                                required
                                class="w-full px-4 py-2.5 rounded-[6px] border border-[#E8E8EC] font-mono text-lg font-bold min-h-[44px] text-[#0A0A0A] focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-[#6366F1]/12"
                            />
                            <div v-if="form.errors.amount" class="text-[#EF4444] text-xs mt-1">
                                {{ form.errors.amount }}
                            </div>

                            <!-- Quick Suggestion Buttons -->
                            <div class="flex flex-wrap gap-2 mt-2">
                                <button
                                    type="button"
                                    @click="setQuickNominal(22000)"
                                    class="text-xs px-2.5 py-1 rounded-[6px] border border-[#E8E8EC] bg-[#FAFAFA] text-[#0A0A0A] hover:bg-white font-mono cursor-pointer"
                                >
                                    +22.000 (1 Gas)
                                </button>
                                <button
                                    type="button"
                                    @click="setQuickNominal(44000)"
                                    class="text-xs px-2.5 py-1 rounded-[6px] border border-[#E8E8EC] bg-[#FAFAFA] text-[#0A0A0A] hover:bg-white font-mono cursor-pointer"
                                >
                                    +44.000 (2 Gas)
                                </button>
                                <button
                                    type="button"
                                    @click="setQuickNominal(50000)"
                                    class="text-xs px-2.5 py-1 rounded-[6px] border border-[#E8E8EC] bg-[#FAFAFA] text-[#0A0A0A] hover:bg-white font-mono cursor-pointer"
                                >
                                    +50.000
                                </button>
                                <button
                                    type="button"
                                    @click="setQuickNominal(100000)"
                                    class="text-xs px-2.5 py-1 rounded-[6px] border border-[#E8E8EC] bg-[#FAFAFA] text-[#0A0A0A] hover:bg-white font-mono cursor-pointer"
                                >
                                    +100.000
                                </button>
                            </div>
                        </div>

                        <!-- Category -->
                        <div>
                            <label class="block text-xs font-bold text-[#0A0A0A] uppercase mb-1">
                                Kategori *
                            </label>
                            <select
                                v-model="form.category"
                                class="w-full px-4 py-2.5 rounded-[6px] border border-[#E8E8EC] text-sm text-[#0A0A0A] min-h-[44px] bg-white focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-[#6366F1]/12"
                            >
                                <option v-for="cat in categories" :key="cat.value" :value="cat.value">
                                    {{ cat.label }}
                                </option>
                            </select>
                        </div>

                        <!-- Description -->
                        <div>
                            <label class="block text-xs font-bold text-[#0A0A0A] uppercase mb-1">
                                Keterangan Pembelian *
                            </label>
                            <input
                                v-model="form.description"
                                type="text"
                                placeholder="Contoh: Beli 2 tabung gas LPG 3kg di Toko Bu Joko"
                                required
                                class="w-full px-4 py-2.5 rounded-[6px] border border-[#E8E8EC] text-sm text-[#0A0A0A] min-h-[44px] focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-[#6366F1]/12"
                            />
                            <div v-if="form.errors.description" class="text-[#EF4444] text-xs mt-1">
                                {{ form.errors.description }}
                            </div>
                        </div>

                        <!-- Payment Method & Date -->
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-bold text-[#0A0A0A] uppercase mb-1">
                                    Metode Pembayaran
                                </label>
                                <select
                                    v-model="form.payment_method"
                                    class="w-full px-3 py-2.5 rounded-[6px] border border-[#E8E8EC] text-sm text-[#0A0A0A] min-h-[44px] bg-white focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-[#6366F1]/12"
                                >
                                    <option value="cash">Kas Tunai (Laci)</option>
                                    <option value="bank_transfer">Transfer Bank</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-[#0A0A0A] uppercase mb-1">
                                    Tanggal
                                </label>
                                <input
                                    v-model="form.expense_date"
                                    type="date"
                                    class="w-full px-3 py-2.5 rounded-[6px] border border-[#E8E8EC] text-sm text-[#0A0A0A] min-h-[44px] bg-white focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-[#6366F1]/12"
                                />
                            </div>
                        </div>

                        <div class="pt-3 border-t border-[#E8E8EC] flex items-center justify-end gap-3">
                            <Button
                                type="button"
                                variant="outline"
                                @click="isAddModalOpen = false"
                                class="min-h-[44px] rounded-[6px]"
                            >
                                Batal
                            </Button>
                            <Button
                                type="submit"
                                :disabled="form.processing"
                                class="min-h-[44px] bg-[#6366F1] hover:bg-[#4F46E5] text-white font-bold rounded-[6px] shadow-[0_4px_12px_rgba(99,102,241,0.35)]"
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

