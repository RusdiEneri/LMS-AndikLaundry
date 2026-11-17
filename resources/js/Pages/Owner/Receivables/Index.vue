<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import {
    AlertCircle,
    Phone,
    MessageSquare,
    Search,
    Clock,
    DollarSign,
    CheckCircle2,
    Calendar,
    ArrowUpRight,
    Users,
} from 'lucide-vue-next';

interface DebtorItem {
    transaction_id: number;
    invoice_number: string;
    customer_id: number;
    customer_name: string;
    customer_phone: string;
    intake_date: string;
    days_old: number;
    total_amount: number;
    paid_amount: number;
    outstanding_balance: number;
    aging_label: string;
    aging_badge: 'teal' | 'amber' | 'destructive';
}

interface Props {
    summary: {
        total_receivables: number;
        debtors_count: number;
        unpaid_orders_count: number;
        aging_breakdown: {
            current: number;
            overdue: number;
            delinquent: number;
        };
        debtors: DebtorItem[];
    };
}

const props = defineProps<Props>();

const search = ref('');

const filteredDebtors = computed(() => {
    if (!search.value) return props.summary.debtors;
    const q = search.value.toLowerCase();
    return props.summary.debtors.filter(
        d => d.customer_name.toLowerCase().includes(q) ||
             d.invoice_number.toLowerCase().includes(q) ||
             d.customer_phone.includes(q)
    );
});

import { computed } from 'vue';

function formatNumber(val: number): string {
    return new Intl.NumberFormat('id-ID').format(val || 0);
}

function openWhatsAppReminder(debtor: DebtorItem) {
    fetch(`/api/receivables/${debtor.transaction_id}/reminder-url`)
        .then(res => res.json())
        .then(res => {
            if (res.data?.wa_url) {
                window.open(res.data.wa_url, '_blank');
            }
        })
        .catch(err => console.error(err));
}
</script>

<template>
    <AppLayout title="Buku Piutang Pelanggan">
        <div class="space-y-6">
            <!-- Header Title -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white p-6 rounded-2xl border border-slate-200/90 shadow-xs">
                <div>
                    <div class="flex items-center space-x-2">
                        <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Buku Piutang Pelanggan</h1>
                        <Badge variant="default" class="text-xs uppercase font-bold">Bayar Nanti</Badge>
                    </div>
                    <p class="text-sm font-medium text-slate-500 mt-1">
                        Daftar pelanggan yang masih memiliki tagihan belum lunas dan pengingat via WhatsApp.
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <Link href="/pos">
                        <Button variant="default" class="min-h-[44px] gap-2 font-bold uppercase tracking-wider text-xs rounded-xl shadow-xs bg-[#4F5FE8] text-white hover:bg-[#3D4DC6]">
                            <span>Buka Kasir POS</span>
                        </Button>
                    </Link>
                </div>
            </div>

            <!-- Aging & Totals KPI Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Total Piutang -->
                <Card class="border border-slate-200 rounded-2xl bg-white shadow-xs">
                    <CardHeader class="pb-2">
                        <CardTitle class="text-xs uppercase font-bold text-slate-500">Total Piutang Belum Lunas</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-extrabold font-mono text-rose-600">
                            Rp {{ formatNumber(summary.total_receivables) }}
                        </div>
                        <p class="text-xs font-medium text-slate-500 mt-1">
                            {{ summary.debtors_count }} orang • {{ summary.unpaid_orders_count }} nota
                        </p>
                    </CardContent>
                </Card>

                <!-- 0-7 Hari -->
                <Card class="border border-slate-200 rounded-2xl bg-white shadow-xs">
                    <CardHeader class="pb-2">
                        <CardTitle class="text-xs uppercase font-bold text-slate-500">Lancar (0 - 7 Hari)</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-extrabold font-mono text-slate-800">
                            Rp {{ formatNumber(summary.aging_breakdown.current) }}
                        </div>
                        <p class="text-xs font-medium text-slate-500 mt-1">Masih dalam batas wajar</p>
                    </CardContent>
                </Card>

                <!-- 8-14 Hari -->
                <Card class="border border-amber-200 bg-amber-50/50 rounded-2xl shadow-xs">
                    <CardHeader class="pb-2">
                        <CardTitle class="text-xs uppercase font-bold text-amber-700">Jatuh Tempo (8 - 14 Hari)</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-extrabold font-mono text-amber-900">
                            Rp {{ formatNumber(summary.aging_breakdown.overdue) }}
                        </div>
                        <p class="text-xs font-medium text-amber-700/80 mt-1">Waktunya diingatkan via WA</p>
                    </CardContent>
                </Card>

                <!-- > 14 Hari -->
                <Card class="border border-rose-200 bg-rose-50/50 rounded-2xl shadow-xs">
                    <CardHeader class="pb-2">
                        <CardTitle class="text-xs uppercase font-bold text-rose-700">Menunggak (> 14 Hari)</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-extrabold font-mono text-rose-600">
                            Rp {{ formatNumber(summary.aging_breakdown.delinquent) }}
                        </div>
                        <p class="text-xs font-medium text-rose-700/80 mt-1">Perlu konfirmasi ke pelanggan</p>
                    </CardContent>
                </Card>
            </div>

            <!-- Search Bar -->
            <div class="bg-white p-4 rounded-2xl border border-slate-200/90 shadow-xs flex items-center">
                <div class="relative flex-1 max-w-md">
                    <Search class="h-4 w-4 text-slate-400 absolute left-3.5 top-3.5" />
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Cari nama pelanggan, nomor nota, atau telepon..."
                        class="w-full pl-10 pr-4 py-2 border border-slate-200 rounded-xl text-sm font-semibold text-slate-900 min-h-[44px] shadow-xs focus:outline-none focus:ring-2 focus:ring-[#4F5FE8]"
                    />
                </div>
            </div>

            <!-- Table of Debtors -->
            <Card class="border border-slate-200/90 shadow-xs rounded-2xl overflow-hidden">
                <CardHeader>
                    <CardTitle class="text-base font-bold text-slate-900">Rincian Nota Belum Lunas</CardTitle>
                    <CardDescription class="text-xs font-medium text-slate-500">
                        Total {{ filteredDebtors.length }} nota piutang terdaftar
                    </CardDescription>
                </CardHeader>
                <CardContent class="p-0">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm text-slate-900">
                            <thead class="bg-slate-50 border-y border-slate-200 text-xs uppercase tracking-wider text-slate-600 font-bold">
                                <tr>
                                    <th class="px-5 py-3">No. Nota</th>
                                    <th class="px-5 py-3">Pelanggan</th>
                                    <th class="px-5 py-3">Tanggal Nota</th>
                                    <th class="px-5 py-3 text-center">Umur Piutang</th>
                                    <th class="px-5 py-3 text-right">Total Nota</th>
                                    <th class="px-5 py-3 text-right">Sudah Bayar</th>
                                    <th class="px-5 py-3 text-right">Sisa Piutang</th>
                                    <th class="px-5 py-3 text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200">
                                <tr v-if="filteredDebtors.length === 0">
                                    <td colspan="8" class="px-5 py-8 text-center text-slate-400 text-sm">
                                        Tidak ada piutang pelanggan saat ini. Semua tagihan telah lunas!
                                    </td>
                                </tr>
                                <tr v-for="debtor in filteredDebtors" :key="debtor.transaction_id" class="hover:bg-slate-50 transition-colors">
                                    <td class="px-5 py-4 font-mono font-bold text-xs text-slate-900">
                                        {{ debtor.invoice_number }}
                                    </td>
                                    <td class="px-5 py-4">
                                        <div class="font-bold text-slate-800 text-sm">{{ debtor.customer_name }}</div>
                                        <div class="text-xs text-slate-500 font-mono flex items-center space-x-1">
                                            <Phone class="h-3 w-3 text-slate-400" />
                                            <span>{{ debtor.customer_phone || '-' }}</span>
                                        </div>
                                    </td>
                                    <td class="px-5 py-4 text-xs text-slate-600">
                                        {{ debtor.intake_date }}
                                    </td>
                                    <td class="px-5 py-4 text-center">
                                        <Badge :variant="debtor.aging_badge" class="text-xs font-semibold">
                                            {{ debtor.aging_label }}
                                        </Badge>
                                    </td>
                                    <td class="px-5 py-4 font-mono text-xs text-right text-slate-700">
                                        Rp {{ formatNumber(debtor.total_amount) }}
                                    </td>
                                    <td class="px-5 py-4 font-mono text-xs text-right text-emerald-700">
                                        Rp {{ formatNumber(debtor.paid_amount) }}
                                    </td>
                                    <td class="px-5 py-4 font-mono font-bold text-sm text-right text-amber-700">
                                        Rp {{ formatNumber(debtor.outstanding_balance) }}
                                    </td>
                                    <td class="px-5 py-4 text-center">
                                        <div class="flex items-center justify-center gap-2">
                                            <Button
                                                type="button"
                                                variant="outline"
                                                size="sm"
                                                @click="openWhatsAppReminder(debtor)"
                                                class="min-h-[44px] gap-1.5 text-xs text-emerald-700 border-emerald-300 hover:bg-emerald-50"
                                                title="Kirim Pengingat Tagihan via WhatsApp"
                                            >
                                                <MessageSquare class="h-4 w-4 text-emerald-600" />
                                                <span>Ingatkan WA</span>
                                            </Button>

                                            <Link :href="`/receipts/${debtor.invoice_number}`">
                                                <Button
                                                    variant="secondary"
                                                    size="sm"
                                                    class="min-h-[44px] text-xs"
                                                    title="Buka Nota Digital"
                                                >
                                                    Nota
                                                </Button>
                                            </Link>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>

