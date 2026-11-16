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
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white p-5 rounded-[12px] border-[3.5px] border-black shadow-[6px_6px_0px_#000]">
                <div>
                    <div class="flex items-center space-x-2">
                        <h1 class="text-2xl font-black text-black tracking-tight">Buku Piutang Pelanggan</h1>
                        <Badge variant="default" class="text-xs uppercase font-black">Bayar Nanti</Badge>
                    </div>
                    <p class="text-sm font-bold text-black/70 mt-1">
                        Daftar pelanggan yang masih memiliki tagihan belum lunas dan pengingat via WhatsApp.
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <Link href="/pos">
                        <Button variant="default" class="min-h-[44px] gap-2 font-black uppercase tracking-wider text-xs shadow-[3px_3px_0px_#000]">
                            <span>Buka Kasir POS</span>
                        </Button>
                    </Link>
                </div>
            </div>

            <!-- Aging & Totals KPI Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Total Piutang -->
                <Card class="border-[3px] border-black shadow-[5px_5px_0px_#000] rounded-[10px] bg-white">
                    <CardHeader class="pb-2">
                        <CardTitle class="text-xs uppercase font-black text-black/70">Total Piutang Belum Lunas</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-black font-mono text-[#FF5252]">
                            Rp {{ formatNumber(summary.total_receivables) }}
                        </div>
                        <p class="text-xs font-bold text-black/60 mt-1">
                            {{ summary.debtors_count }} orang • {{ summary.unpaid_orders_count }} nota
                        </p>
                    </CardContent>
                </Card>

                <!-- 0-7 Hari -->
                <Card class="border-[3px] border-black shadow-[5px_5px_0px_#000] bg-[#FFF9D2] rounded-[10px]">
                    <CardHeader class="pb-2">
                        <CardTitle class="text-xs uppercase font-black text-black">Lancar (0 - 7 Hari)</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-black font-mono text-black">
                            Rp {{ formatNumber(summary.aging_breakdown.current) }}
                        </div>
                        <p class="text-xs font-bold text-black/70 mt-1">Masih dalam batas wajar</p>
                    </CardContent>
                </Card>

                <!-- 8-14 Hari -->
                <Card class="border-[3px] border-black shadow-[5px_5px_0px_#000] bg-[#2196F3]/20 rounded-[10px]">
                    <CardHeader class="pb-2">
                        <CardTitle class="text-xs uppercase font-black text-black">Jatuh Tempo (8 - 14 Hari)</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-black font-mono text-black">
                            Rp {{ formatNumber(summary.aging_breakdown.overdue) }}
                        </div>
                        <p class="text-xs font-bold text-black/70 mt-1">Waktunya diingatkan via WA</p>
                    </CardContent>
                </Card>

                <!-- > 14 Hari -->
                <Card class="border-[3px] border-black shadow-[5px_5px_0px_#000] bg-[#FF5252]/20 rounded-[10px]">
                    <CardHeader class="pb-2">
                        <CardTitle class="text-xs uppercase font-black text-black">Menunggak (> 14 Hari)</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-black font-mono text-[#FF5252]">
                            Rp {{ formatNumber(summary.aging_breakdown.delinquent) }}
                        </div>
                        <p class="text-xs font-bold text-black/70 mt-1">Perlu konfirmasi ke pelanggan</p>
                    </CardContent>
                </Card>
            </div>

            <!-- Search Bar -->
            <div class="bg-white p-4 rounded-[12px] border-[3px] border-black shadow-[5px_5px_0px_#000] flex items-center">
                <div class="relative flex-1 max-w-md">
                    <Search class="h-4 w-4 text-black stroke-[2.5] absolute left-3.5 top-3.5" />
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Cari nama pelanggan, nomor nota, atau telepon..."
                        class="w-full pl-10 pr-4 py-2 border-2 border-black rounded-[8px] text-sm font-bold text-black min-h-[44px] shadow-[2px_2px_0px_#000] focus:outline-none focus:shadow-[3px_3px_0px_#000]"
                    />
                </div>
            </div>

            <!-- Table of Debtors -->
            <Card class="border-[3.5px] border-black shadow-[6px_6px_0px_#000] rounded-[12px] overflow-hidden">
                <CardHeader>
                    <CardTitle class="text-base font-black text-black">Rincian Nota Belum Lunas</CardTitle>
                    <CardDescription class="text-xs font-bold text-black/60">
                        Total {{ filteredDebtors.length }} nota piutang terdaftar
                    </CardDescription>
                </CardHeader>
                <CardContent class="p-0">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm text-black">
                            <thead class="bg-[#FFEB3B] border-y-2 border-black text-xs uppercase tracking-wider text-black font-black">
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

