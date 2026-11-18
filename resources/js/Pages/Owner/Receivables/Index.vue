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
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-surface p-5 rounded-lg border border-border shadow-sm">
                <div>
                    <div class="flex items-center space-x-2">
                        <h1 class="text-2xl font-heading font-bold text-text tracking-tight">Buku Piutang Pelanggan</h1>
                        <Badge variant="teal" class="text-xs uppercase font-bold">Bayar Nanti</Badge>
                    </div>
                    <p class="text-sm font-medium text-text-muted mt-1">
                        Daftar pelanggan yang masih memiliki tagihan belum lunas dan pengingat via WhatsApp.
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <Link href="/pos">
                        <Button variant="default" class="min-h-[44px] gap-2 font-bold shadow-sm">
                            <span>Buka Kasir POS</span>
                        </Button>
                    </Link>
                </div>
            </div>

            <!-- Aging & Totals KPI Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Total Piutang -->
                <Card class="border border-border rounded-lg bg-surface shadow-sm">
                    <CardHeader class="pb-2">
                        <CardTitle class="text-xs uppercase font-bold text-text-muted">Total Piutang Belum Lunas</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold font-mono text-danger tabular-nums">
                            Rp {{ formatNumber(summary.total_receivables) }}
                        </div>
                        <p class="text-xs font-medium text-text-muted mt-1">
                            {{ summary.debtors_count }} orang • {{ summary.unpaid_orders_count }} nota
                        </p>
                    </CardContent>
                </Card>

                <!-- 0-7 Hari -->
                <Card class="border border-border rounded-lg bg-surface shadow-sm">
                    <CardHeader class="pb-2">
                        <CardTitle class="text-xs uppercase font-bold text-text-muted">Lancar (0 - 7 Hari)</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold font-mono text-text tabular-nums">
                            Rp {{ formatNumber(summary.aging_breakdown.current) }}
                        </div>
                        <p class="text-xs font-medium text-text-muted mt-1">Masih dalam batas wajar</p>
                    </CardContent>
                </Card>

                <!-- 8-14 Hari -->
                <Card class="border border-warning/30 bg-warning/5 rounded-lg shadow-sm">
                    <CardHeader class="pb-2">
                        <CardTitle class="text-xs uppercase font-bold text-warning">Jatuh Tempo (8 - 14 Hari)</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold font-mono text-warning tabular-nums">
                            Rp {{ formatNumber(summary.aging_breakdown.overdue) }}
                        </div>
                        <p class="text-xs font-medium text-warning/80 mt-1">Waktunya diingatkan via WA</p>
                    </CardContent>
                </Card>

                <!-- > 14 Hari -->
                <Card class="border border-danger/30 bg-danger/5 rounded-lg shadow-sm">
                    <CardHeader class="pb-2">
                        <CardTitle class="text-xs uppercase font-bold text-danger">Menunggak (> 14 Hari)</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold font-mono text-danger tabular-nums">
                            Rp {{ formatNumber(summary.aging_breakdown.delinquent) }}
                        </div>
                        <p class="text-xs font-medium text-danger/80 mt-1">Perlu konfirmasi ke pelanggan</p>
                    </CardContent>
                </Card>
            </div>

            <!-- Search Bar -->
            <div class="bg-surface p-4 rounded-lg border border-border shadow-sm flex items-center">
                <div class="relative flex-1 max-w-md">
                    <Search class="h-4 w-4 text-text-muted absolute left-3.5 top-3.5" />
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Cari nama pelanggan, nomor nota, atau telepon..."
                        class="w-full pl-10 pr-4 py-2 border border-border rounded-md text-sm font-medium text-text bg-surface min-h-[44px] focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-600"
                    />
                </div>
            </div>

            <!-- Table of Debtors -->
            <Card class="border border-border shadow-sm rounded-lg overflow-hidden bg-surface">
                <CardHeader>
                    <CardTitle class="text-base font-heading font-bold text-text">Rincian Nota Belum Lunas</CardTitle>
                    <CardDescription class="text-xs font-medium text-text-muted">
                        Total {{ filteredDebtors.length }} nota piutang terdaftar
                    </CardDescription>
                </CardHeader>
                <CardContent class="p-0">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm text-text">
                            <thead class="bg-bg border-y border-border text-xs uppercase tracking-wider text-text-muted font-semibold">
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
                            <tbody class="divide-y divide-border">
                                <tr v-if="filteredDebtors.length === 0">
                                    <td colspan="8" class="px-5 py-8 text-center text-text-muted text-sm">
                                        Tidak ada piutang pelanggan saat ini. Semua tagihan telah lunas!
                                    </td>
                                </tr>
                                <tr v-for="debtor in filteredDebtors" :key="debtor.transaction_id" class="hover:bg-bg transition-colors">
                                    <td class="px-5 py-4 font-mono font-bold text-xs text-text">
                                        {{ debtor.invoice_number }}
                                    </td>
                                    <td class="px-5 py-4">
                                        <div class="font-bold text-text text-sm">{{ debtor.customer_name }}</div>
                                        <div class="text-xs text-text-muted font-mono flex items-center space-x-1">
                                            <Phone class="h-3 w-3 text-text-muted" />
                                            <span>{{ debtor.customer_phone || '-' }}</span>
                                        </div>
                                    </td>
                                    <td class="px-5 py-4 text-xs text-text-muted">
                                        {{ debtor.intake_date }}
                                    </td>
                                    <td class="px-5 py-4 text-center">
                                        <Badge :variant="debtor.aging_badge" class="text-xs font-semibold">
                                            {{ debtor.aging_label }}
                                        </Badge>
                                    </td>
                                    <td class="px-5 py-4 font-mono text-xs text-right text-text tabular-nums">
                                        Rp {{ formatNumber(debtor.total_amount) }}
                                    </td>
                                    <td class="px-5 py-4 font-mono text-xs text-right text-success tabular-nums">
                                        Rp {{ formatNumber(debtor.paid_amount) }}
                                    </td>
                                    <td class="px-5 py-4 font-mono font-bold text-sm text-right text-warning tabular-nums">
                                        Rp {{ formatNumber(debtor.outstanding_balance) }}
                                    </td>
                                    <td class="px-5 py-4 text-center">
                                        <div class="flex items-center justify-center gap-2">
                                            <Button
                                                type="button"
                                                variant="outline"
                                                size="sm"
                                                @click="openWhatsAppReminder(debtor)"
                                                class="min-h-[44px] gap-1.5 text-xs text-accent-700 border-accent-500/40 hover:bg-accent-100"
                                                title="Kirim Pengingat Tagihan via WhatsApp"
                                            >
                                                <MessageSquare class="h-4 w-4 text-accent-700" />
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

