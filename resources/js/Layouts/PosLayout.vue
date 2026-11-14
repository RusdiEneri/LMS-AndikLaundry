<script setup lang="ts">
import { computed, ref, onMounted, onUnmounted } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import type { PageProps, User } from '@/types';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
    SheetDescription,
} from '@/Components/ui/sheet';
import {
    ArrowLeft,
    Clock,
    ShoppingBag,
    WifiOff,
    HelpCircle,
    X,
    Wallet,
    Receipt,
    LayoutDashboard,
    Shirt,
    Settings,
} from 'lucide-vue-next';

interface Props {
    title?: string;
    cartCount?: number;
    cartTotalFormatted?: string;
    shiftInfo?: {
        id?: string;
        cashier_name?: string;
        is_open?: boolean;
        opening_cash?: number;
        drawer_cash?: number;
    };
    storeName?: string;
}

const props = withDefaults(defineProps<Props>(), {
    title: 'Point of Sale (Kasir)',
    cartCount: 0,
    cartTotalFormatted: 'Rp 0',
    storeName: 'Andik Laundry',
});

const page = usePage<PageProps>();
const user = computed<User | null>(() => page.props.auth?.user ?? null);

// Mobile cart sheet state
const isMobileCartOpen = ref(false);

// WhatsApp tooltip
const showWaTooltip = ref(false);

// Real-time clock
const currentTime = ref('');
let timerInterval: any = null;

function updateClock() {
    const now = new Date();
    currentTime.value = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
}

// Global Hotkeys (F1 for search, F8 for checkout)
const emits = defineEmits<{
    (e: 'hotkey-search'): void;
    (e: 'hotkey-checkout'): void;
}>();

function handleGlobalKeydown(e: KeyboardEvent) {
    if (e.key === 'F1') {
        e.preventDefault();
        emits('hotkey-search');
    } else if (e.key === 'F8') {
        e.preventDefault();
        emits('hotkey-checkout');
    }
}

onMounted(() => {
    updateClock();
    timerInterval = setInterval(updateClock, 1000);
    window.addEventListener('keydown', handleGlobalKeydown);
});

onUnmounted(() => {
    if (timerInterval) clearInterval(timerInterval);
    window.removeEventListener('keydown', handleGlobalKeydown);
});
</script>

<template>
    <div class="min-h-screen bg-slate-100 flex flex-col font-sans text-slate-900 antialiased select-none pb-20 lg:pb-0">
        <Head :title="`${title} - Andik Laundry POS`" />

        <!-- High-Speed POS Header -->
        <header class="sticky top-0 z-40 bg-white border-b border-slate-200 shadow-xs">
            <div class="px-4 sm:px-6 h-16 flex items-center justify-between">
                <!-- Left: Home / AL Brand & Store Title + Quick Navigation -->
                <div class="flex items-center space-x-3">
                    <Link
                        href="/dashboard"
                        class="inline-flex min-h-[44px] items-center space-x-1.5 px-3 py-1.5 rounded-lg border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 hover:text-slate-900 focus:outline-none focus:ring-2 focus:ring-teal-500 shadow-xs text-xs font-semibold"
                        title="Dashboard & Laporan Usaha"
                        aria-label="Ke Dashboard"
                    >
                        <LayoutDashboard class="h-4 w-4 text-teal-600" />
                        <span class="hidden sm:inline">Dashboard</span>
                    </Link>

                    <Link
                        href="/production/station"
                        class="inline-flex min-h-[44px] items-center space-x-1.5 px-3 py-1.5 rounded-lg border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 hover:text-slate-900 focus:outline-none focus:ring-2 focus:ring-teal-500 shadow-xs text-xs font-semibold"
                        title="Antrean & Status Cucian"
                        aria-label="Ke Antrean Cucian"
                    >
                        <Shirt class="h-4 w-4 text-indigo-600" />
                        <span class="hidden sm:inline">Antrean Cuci</span>
                    </Link>

                    <Link
                        href="/owner/expenses"
                        class="inline-flex min-h-[44px] items-center space-x-1.5 px-3 py-1.5 rounded-lg border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 hover:text-slate-900 focus:outline-none focus:ring-2 focus:ring-teal-500 shadow-xs text-xs font-semibold"
                        title="Catat Pengeluaran Toko"
                        aria-label="Ke Pengeluaran Toko"
                    >
                        <Receipt class="h-4 w-4 text-rose-600" />
                        <span class="hidden lg:inline">Biaya Toko</span>
                    </Link>

                    <Link
                        href="/owner/daily-recap"
                        class="inline-flex min-h-[44px] items-center space-x-1.5 px-3 py-1.5 rounded-lg border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 hover:text-slate-900 focus:outline-none focus:ring-2 focus:ring-teal-500 shadow-xs text-xs font-semibold"
                        title="Rekap Kas Harian"
                        aria-label="Ke Rekap Kas Harian"
                    >
                        <Wallet class="h-4 w-4 text-emerald-600" />
                        <span class="hidden lg:inline">Rekap Kas</span>
                    </Link>

                    <div class="flex items-center space-x-2.5 pl-2 border-l border-slate-200">
                        <div class="h-10 w-10 rounded-lg bg-teal-600 flex items-center justify-center text-white font-bold text-lg shadow-sm">
                            AL
                        </div>
                        <div>
                            <div class="flex items-center space-x-2">
                                <span class="font-bold text-base text-slate-900 leading-tight">{{ storeName }}</span>
                                <Badge variant="teal" class="text-[10px] uppercase font-bold py-0 px-2">Kasir Toko</Badge>
                            </div>
                            <span class="text-xs text-slate-500 leading-none">Usaha Rumahan Mandiri</span>
                        </div>
                    </div>
                </div>

                <!-- Center: Operational Status & Realtime Clock (Desktop) -->
                <div class="hidden md:flex items-center space-x-4">
                    <div class="flex items-center space-x-2 px-3 py-1.5 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-semibold">
                        <span class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        <span>Toko Buka • Kasir: {{ user?.name ?? 'Pemilik' }}</span>
                    </div>

                    <div class="flex items-center space-x-1.5 text-xs text-slate-600 font-mono bg-slate-100 px-3 py-1.5 rounded-lg border border-slate-200">
                        <Clock class="h-4 w-4 text-slate-500" />
                        <span>{{ currentTime }}</span>
                    </div>
                </div>

                <!-- Right: WhatsApp Fallback Status & Hotkeys Badge -->
                <div class="flex items-center space-x-2 sm:space-x-3">
                    <div class="relative hidden sm:flex items-center">
                        <button
                            type="button"
                            @click="showWaTooltip = !showWaTooltip"
                            class="inline-flex min-h-[44px] items-center space-x-1.5 px-3 py-1.5 rounded-lg border border-amber-200 bg-amber-50 text-amber-900 text-xs font-semibold hover:bg-amber-100 focus:outline-none focus:ring-2 focus:ring-amber-500"
                        >
                            <WifiOff class="h-4 w-4 text-amber-700" />
                            <span class="hidden md:inline">WA Fallback Manual</span>
                            <HelpCircle class="h-3.5 w-3.5 text-amber-600" />
                        </button>

                        <div
                            v-if="showWaTooltip"
                            class="absolute right-0 top-12 z-50 w-72 rounded-lg bg-slate-900 p-3 text-xs text-white shadow-xl border border-slate-700"
                        >
                            <div class="flex justify-between items-start mb-1">
                                <span class="font-bold text-amber-400">Mode Fallback Aktif</span>
                                <button
                                    type="button"
                                    class="text-slate-400 hover:text-white min-h-[44px] min-w-[44px] -m-2 flex items-center justify-center"
                                    @click="showWaTooltip = false"
                                >
                                    <X class="h-4 w-4" />
                                </button>
                            </div>
                            <p class="text-slate-300 leading-relaxed">
                                Gateway WuzAPI offline. Notifikasi struk dapat dikirim manual via tombol wa.me yang otomatis mengisi nomor customer & format struk.
                            </p>
                        </div>
                    </div>

                    <div class="hidden lg:flex items-center space-x-1.5 text-xs text-slate-500">
                        <kbd class="px-2 py-1 bg-slate-200 text-slate-700 font-mono rounded-sm border border-slate-300 text-[11px] font-bold">F1 Cari</kbd>
                        <kbd class="px-2 py-1 bg-slate-200 text-slate-700 font-mono rounded-sm border border-slate-300 text-[11px] font-bold">F8 Bayar</kbd>
                    </div>
                </div>
            </div>
        </header>

        <!-- 60/40 Horizontal Split Desktop / Stacked Mobile Layout -->
        <div class="flex-1 max-w-[1920px] w-full mx-auto p-3 sm:p-4 lg:p-6 lg:h-[calc(100vh-4rem)] lg:overflow-hidden">
            <div class="h-full lg:grid lg:grid-cols-12 lg:gap-6">
                <!-- Left 60% Panel: Catalog & Order Builder (Columns 1-7 = ~58.3%) -->
                <div class="lg:col-span-7 flex flex-col h-full overflow-y-auto pr-0 lg:pr-2">
                    <slot name="catalog">
                        <slot />
                    </slot>
                </div>

                <!-- Right 40% Panel: Persistent Cart & Checkout Summary (Columns 8-12 = ~41.7%) -->
                <div class="hidden lg:flex lg:col-span-5 flex-col h-full bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
                    <slot name="cart" />
                </div>
            </div>
        </div>

        <!-- Mobile & Tablet (< 1024px) Sticky Bottom Bar (64px) -->
        <div class="lg:hidden fixed bottom-0 inset-x-0 z-40 bg-white border-t border-slate-200 shadow-lg px-4 h-16 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <div class="relative flex items-center justify-center h-10 w-10 rounded-lg bg-teal-50 text-teal-700 border border-teal-200">
                    <ShoppingBag class="h-5 w-5" />
                    <span
                        v-if="cartCount > 0"
                        class="absolute -top-1.5 -right-1.5 flex h-5 min-w-[20px] items-center justify-center rounded-full bg-teal-600 px-1 text-[11px] font-bold text-white shadow-xs"
                    >
                        {{ cartCount }}
                    </span>
                </div>
                <div>
                    <span class="block text-[11px] text-slate-500 uppercase tracking-wider font-semibold leading-none">Total Tagihan</span>
                    <span class="block text-base font-bold font-mono text-slate-900 leading-tight">{{ cartTotalFormatted }}</span>
                </div>
            </div>

            <Button
                type="button"
                variant="default"
                class="min-h-[44px] px-5 font-bold text-sm bg-teal-600 hover:bg-teal-700 text-white shadow-md active:scale-95"
                @click="isMobileCartOpen = true"
            >
                Lihat Keranjang
            </Button>
        </div>

        <!-- Mobile & Tablet Expandable Bottom Sheet for Cart & Checkout -->
        <Sheet v-model:open="isMobileCartOpen">
            <SheetContent side="bottom" class="p-0 max-h-[92vh] flex flex-col bg-white">
                <SheetHeader class="p-4 border-b border-slate-200 text-left bg-slate-50">
                    <div class="flex items-center justify-between">
                        <div>
                            <SheetTitle class="text-base font-bold text-slate-900">Keranjang & Pembayaran</SheetTitle>
                            <SheetDescription class="text-xs text-slate-500">
                                {{ cartCount }} item cucian | Total: {{ cartTotalFormatted }}
                            </SheetDescription>
                        </div>
                    </div>
                </SheetHeader>

                <div class="flex-1 overflow-y-auto p-4">
                    <slot name="cart" />
                </div>
            </SheetContent>
        </Sheet>
    </div>
</template>
