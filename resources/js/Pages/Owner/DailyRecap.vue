<script setup lang="ts">
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import {
    Wallet,
    Calendar,
    Printer,
    ArrowDownRight,
    ArrowUpRight,
    QrCode,
    CreditCard,
    ShoppingBag,
    Scale,
    TrendingUp,
    CheckCircle2,
    Clock,
    DollarSign,
    Receipt,
} from 'lucide-vue-next';

interface Props {
    recap: {
        date: string;
        date_formatted: string;
        store_info: {
            name: string;
            phone: string;
            address: string;
        };
        cash_summary: {
            cash_in: number;
            cash_out: number;
            net_cash_on_hand: number;
        };
        digital_summary: {
            qris: number;
            bank_transfer: number;
            ewallet: number;
            total_digital: number;
        };
        revenue_summary: {
            total_payments_received: number;
            total_orders_invoiced: number;
            new_receivables: number;
            debt_settled_today: number;
        };
        operational_volume: {
            total_orders: number;
            total_weight_kg: number;
            total_pieces: number;
        };
        expenses_list: {
            id: number;
            expense_number: string;
            category: string;
            category_label: string;
            description: string;
            amount: number;
            payment_method: string;
        }[];
    };
    selected_date: string;
}

const props = defineProps<Props>();

const currentDate = ref(props.selected_date);

function onDateChange() {
    router.get(
        '/owner/daily-recap',
        { date: currentDate.value },
        { preserveState: true, replace: true }
    );
}

function formatNumber(val: number): string {
    return new Intl.NumberFormat('id-ID').format(val || 0);
}

function printDailySlip() {
    window.print();
}
</script>

<template>
    <AppLayout title="Rekap Kas Harian">
        <div class="space-y-6">
            <!-- Header with Date Picker and Print Button -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-surface p-5 rounded-lg border border-border shadow-sm print:hidden">
                <div>
                    <div class="flex items-center space-x-2">
                        <h1 class="text-2xl font-heading font-bold text-text tracking-tight">Rekap Kas Harian</h1>
                        <Badge variant="teal" class="text-xs">Usaha Rumahan</Badge>
                    </div>
                    <p class="text-sm text-text-muted mt-1">
                        Ringkasan transaksi, pembayaran tunai/digital, pengeluaran toko, dan sisa uang bersih di laci.
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <div class="flex items-center space-x-2 bg-bg px-3 py-1.5 rounded-pill border border-border">
                        <Calendar class="h-4 w-4 text-text-muted" />
                        <input
                            v-model="currentDate"
                            @change="onDateChange"
                            type="date"
                            class="bg-transparent border-none text-sm font-semibold text-text focus:outline-none min-h-[44px]"
                        />
                    </div>

                    <Button
                        type="button"
                        variant="default"
                        @click="printDailySlip"
                        class="min-h-[44px] gap-2 font-bold shadow-sm"
                    >
                        <Printer class="h-4 w-4" />
                        <span>Cetak Struk Rekap</span>
                    </Button>
                </div>
            </div>

            <!-- 4 Primary Metric Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 print:hidden">
                <!-- 1. Total Pembayaran Diterima -->
                <Card class="border-border shadow-sm rounded-lg bg-surface">
                    <CardHeader class="pb-2">
                        <CardTitle class="text-xs uppercase font-bold text-text-muted">Total Uang Masuk</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold font-mono text-success tabular-nums">
                            Rp {{ formatNumber(recap.revenue_summary.total_payments_received) }}
                        </div>
                        <div class="flex items-center space-x-2 text-xs text-text-muted mt-1">
                            <span>Tunai + QRIS + Transfer</span>
                        </div>
                    </CardContent>
                </Card>

                <!-- 2. Kas Tunai Masuk -->
                <Card class="border-border shadow-sm rounded-lg bg-surface">
                    <CardHeader class="pb-2">
                        <CardTitle class="text-xs uppercase font-bold text-text-muted">Kas Masuk Tunai</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold font-mono text-text tabular-nums">
                            Rp {{ formatNumber(recap.cash_summary.cash_in) }}
                        </div>
                        <p class="text-xs text-text-muted mt-1">Uang fisik masuk laci</p>
                    </CardContent>
                </Card>

                <!-- 3. Pengeluaran Toko Tunai -->
                <Card class="border-border shadow-sm rounded-lg bg-surface">
                    <CardHeader class="pb-2">
                        <CardTitle class="text-xs uppercase font-bold text-text-muted">Pengeluaran Kas Tunai</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold font-mono text-danger tabular-nums">
                            Rp {{ formatNumber(recap.cash_summary.cash_out) }}
                        </div>
                        <p class="text-xs text-text-muted mt-1">Beli gas, sabun, plastik, dll</p>
                    </CardContent>
                </Card>

                <!-- 4. Hero: Sisa Bersih Kas Tunai di Laci -->
                <Card class="border border-success/30 bg-success/10 shadow-sm rounded-lg">
                    <CardHeader class="pb-2">
                        <CardTitle class="text-xs uppercase font-bold text-success">Sisa Kas Bersih di Laci</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold font-mono text-text tabular-nums">
                            Rp {{ formatNumber(recap.cash_summary.net_cash_on_hand) }}
                        </div>
                        <div class="flex items-center space-x-1.5 text-xs text-success mt-1 font-semibold">
                            <CheckCircle2 class="h-3.5 w-3.5 text-success" />
                            <span>Uang Fisik Kas Toko</span>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Detailed Breakdowns: Digital Payments & Operational Volume -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 print:hidden">
                <!-- Digital Non-Cash (Col 6) -->
                <Card class="lg:col-span-6 border-border shadow-sm rounded-lg bg-surface">
                    <CardHeader>
                        <CardTitle class="text-base font-heading font-bold text-text">Rincian Pembayaran Non-Tunai</CardTitle>
                        <CardDescription class="text-xs text-text-muted">
                            Masuk langsung ke rekening / e-wallet
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="flex items-center justify-between p-3 rounded-md bg-bg border border-border">
                            <div class="flex items-center space-x-3">
                                <QrCode class="h-5 w-5 text-primary-700" />
                                <div>
                                    <div class="text-sm font-bold text-text">QRIS</div>
                                    <div class="text-xs text-text-muted">Statis / Merchant QR</div>
                                </div>
                            </div>
                            <div class="font-mono font-bold text-text tabular-nums">
                                Rp {{ formatNumber(recap.digital_summary.qris) }}
                            </div>
                        </div>

                        <div class="flex items-center justify-between p-3 rounded-md bg-bg border border-border">
                            <div class="flex items-center space-x-3">
                                <CreditCard class="h-5 w-5 text-primary-600" />
                                <div>
                                    <div class="text-sm font-bold text-text">Transfer Bank</div>
                                    <div class="text-xs text-text-muted">BCA / Mandiri / BRI</div>
                                </div>
                            </div>
                            <div class="font-mono font-bold text-text tabular-nums">
                                Rp {{ formatNumber(recap.digital_summary.bank_transfer) }}
                            </div>
                        </div>

                        <div class="pt-2 border-t border-border flex justify-between items-center text-sm font-bold text-text">
                            <span>Total Non-Tunai</span>
                            <span class="font-mono text-primary-700 tabular-nums">Rp {{ formatNumber(recap.digital_summary.total_digital) }}</span>
                        </div>
                    </CardContent>
                </Card>

                <!-- Operational Volume & Receivables (Col 6) -->
                <Card class="lg:col-span-6 border-border shadow-sm rounded-lg bg-surface">
                    <CardHeader>
                        <CardTitle class="text-base font-heading font-bold text-text">Volume Cucian & Piutang</CardTitle>
                        <CardDescription class="text-xs text-text-muted">
                            Beban cucian fisik dan status piutang hari ini
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-3">
                        <div class="grid grid-cols-3 gap-2 text-center">
                            <div class="p-3 bg-bg rounded-md border border-border">
                                <div class="text-xs text-text-muted">Total Nota</div>
                                <div class="text-xl font-bold font-mono text-text mt-1">
                                    {{ recap.operational_volume.total_orders }}
                                </div>
                            </div>
                            <div class="p-3 bg-bg rounded-md border border-border">
                                <div class="text-xs text-text-muted">Berat Kiloan</div>
                                <div class="text-xl font-bold font-mono text-primary-700 mt-1">
                                    {{ recap.operational_volume.total_weight_kg }} kg
                                </div>
                            </div>
                            <div class="p-3 bg-bg rounded-md border border-border">
                                <div class="text-xs text-text-muted">Item Satuan</div>
                                <div class="text-xl font-bold font-mono text-warning mt-1">
                                    {{ recap.operational_volume.total_pieces }} pcs
                                </div>
                            </div>
                        </div>

                        <div class="p-3 bg-bg rounded-md border border-border text-xs space-y-1.5">
                            <div class="flex justify-between">
                                <span class="text-text-muted">Total Nilai Nota Diterima:</span>
                                <span class="font-mono font-bold text-text tabular-nums">Rp {{ formatNumber(recap.revenue_summary.total_orders_invoiced) }}</span>
                            </div>
                            <div class="flex justify-between text-warning">
                                <span>Piutang Baru (Bayar Nanti):</span>
                                <span class="font-mono font-bold tabular-nums">Rp {{ formatNumber(recap.revenue_summary.new_receivables) }}</span>
                            </div>
                            <div class="flex justify-between text-success">
                                <span>Pelunasan Piutang Lama:</span>
                                <span class="font-mono font-bold tabular-nums">Rp {{ formatNumber(recap.revenue_summary.debt_settled_today) }}</span>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- List of Expenses Today -->
            <Card class="border-border shadow-sm rounded-lg bg-surface print:hidden">
                <CardHeader>
                    <div class="flex items-center justify-between">
                        <div>
                            <CardTitle class="text-base font-heading font-bold text-text">Rincian Pengeluaran Toko Hari Ini</CardTitle>
                            <CardDescription class="text-xs text-text-muted">
                                Catatan belanja gas, deterjen, dan bahan operasional
                            </CardDescription>
                        </div>
                        <Badge variant="outline">{{ recap.expenses_list.length }} Catatan</Badge>
                    </div>
                </CardHeader>
                <CardContent class="p-0">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm text-text">
                            <thead class="bg-bg border-y border-border text-[11px] uppercase tracking-wider text-text-muted font-semibold">
                                <tr>
                                    <th class="px-5 py-3">No. Nota</th>
                                    <th class="px-5 py-3">Kategori</th>
                                    <th class="px-5 py-3">Keterangan</th>
                                    <th class="px-5 py-3">Metode Bayar</th>
                                    <th class="px-5 py-3 text-right">Nominal</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border">
                                <tr v-if="recap.expenses_list.length === 0">
                                    <td colspan="5" class="px-5 py-6 text-center text-text-muted text-xs">
                                        Tidak ada pengeluaran operasional yang dicatat pada tanggal ini.
                                    </td>
                                </tr>
                                <tr v-for="exp in recap.expenses_list" :key="exp.id" class="hover:bg-bg transition-colors">
                                    <td class="px-5 py-3 font-mono font-bold text-xs text-text">{{ exp.expense_number }}</td>
                                    <td class="px-5 py-3 text-xs text-text-muted">{{ exp.category }}</td>
                                    <td class="px-5 py-3 text-sm text-text">{{ exp.description }}</td>
                                    <td class="px-5 py-3 text-xs">
                                        <Badge :variant="exp.payment_method === 'cash' ? 'outline' : 'secondary'">
                                            {{ exp.payment_method === 'cash' ? 'Tunai Laci' : 'Transfer' }}
                                        </Badge>
                                    </td>
                                    <td class="px-5 py-3 font-mono font-bold text-sm text-right text-danger tabular-nums">
                                        Rp {{ formatNumber(exp.amount) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </CardContent>
            </Card>

            <!-- Printable Thermal Receipt (Hidden on Screen, Visible on Print) -->
            <div class="hidden print:block font-mono text-black text-xs p-2 max-w-[80mm] mx-auto">
                <div class="text-center pb-2 border-b border-dashed border-black">
                    <div class="text-sm font-bold">{{ recap.store_info.name }}</div>
                    <div>{{ recap.store_info.address }}</div>
                    <div>Telp: {{ recap.store_info.phone }}</div>
                </div>

                <div class="py-2 text-center font-bold">
                    RINGKASAN KAS HARIAN
                </div>

                <div class="space-y-1 pb-2 border-b border-dashed border-black text-[11px]">
                    <div class="flex justify-between">
                        <span>Tanggal:</span>
                        <span>{{ recap.date }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Hari:</span>
                        <span>{{ recap.date_formatted }}</span>
                    </div>
                </div>

                <div class="py-2 space-y-1 text-[11px] border-b border-dashed border-black">
                    <div class="font-bold">RINGKASAN ORDER</div>
                    <div class="flex justify-between">
                        <span>Total Nota:</span>
                        <span>{{ recap.operational_volume.total_orders }} Nota</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Total Berat:</span>
                        <span>{{ recap.operational_volume.total_weight_kg }} kg</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Total Satuan:</span>
                        <span>{{ recap.operational_volume.total_pieces }} pcs</span>
                    </div>
                </div>

                <div class="py-2 space-y-1 text-[11px] border-b border-dashed border-black">
                    <div class="font-bold">PEMBAYARAN DITERIMA</div>
                    <div class="flex justify-between">
                        <span>1. Tunai (Cash):</span>
                        <span>Rp {{ formatNumber(recap.cash_summary.cash_in) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span>2. QRIS:</span>
                        <span>Rp {{ formatNumber(recap.digital_summary.qris) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span>3. Transfer Bank:</span>
                        <span>Rp {{ formatNumber(recap.digital_summary.bank_transfer) }}</span>
                    </div>
                    <div class="flex justify-between font-bold pt-1 border-t border-dotted border-black">
                        <span>TOTAL MASUK:</span>
                        <span>Rp {{ formatNumber(recap.revenue_summary.total_payments_received) }}</span>
                    </div>
                </div>

                <div class="py-2 space-y-1 text-[11px] border-b border-dashed border-black">
                    <div class="font-bold">PENGELUARAN TOKO</div>
                    <div v-for="exp in recap.expenses_list" :key="exp.id" class="flex justify-between">
                        <span>{{ exp.description.substring(0, 18) }}:</span>
                        <span>Rp {{ formatNumber(exp.amount) }}</span>
                    </div>
                    <div class="flex justify-between font-bold pt-1 border-t border-dotted border-black">
                        <span>TOTAL PENGELUARAN TUNAI:</span>
                        <span>Rp {{ formatNumber(recap.cash_summary.cash_out) }}</span>
                    </div>
                </div>

                <div class="py-3 text-center border-b border-dashed border-black">
                    <div class="text-[10px] uppercase font-bold">SISA KAS BERSIH DI LACI</div>
                    <div class="text-base font-bold mt-1">
                        Rp {{ formatNumber(recap.cash_summary.net_cash_on_hand) }}
                    </div>
                </div>

                <div class="pt-3 text-center text-[10px] space-y-1">
                    <div>LMS Andik Laundry</div>
                    <div>Usaha Rumahan Dikelola Mandiri</div>
                    <div>Semangat & Berkah!</div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

