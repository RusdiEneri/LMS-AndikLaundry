<script setup lang="ts">
import { computed, ref, onMounted, onUnmounted } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import type { PageProps, User } from '@/types';
import { Button } from '@/Components/ui/button';
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
    SheetDescription,
} from '@/Components/ui/sheet';
import {
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
    Menu,
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

// Mobile cart & nav sheet states
const isMobileCartOpen = ref(false);
const isMobileNavOpen = ref(false);

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
    <div class="fixed inset-0 flex flex-col overflow-hidden bg-[#F8FAFC] font-sans text-slate-900 antialiased select-none pb-[calc(env(safe-area-inset-bottom,0px)+4.5rem)] lg:pb-0">
        <Head :title="`${title} - Andik Laundry POS`" />

        <!-- High-Speed POS Header (Pinned / Sticky Top) -->
        <header class="h-16 shrink-0 bg-[#10194D] text-white border-b border-[#1E293B] shadow-sm z-40">
            <div class="px-4 sm:px-6 h-16 flex items-center justify-between">
                <!-- Left: Home / AL Brand & Store Title + Desktop Navigation -->
                <div class="flex items-center space-x-4">
                    <Link href="/" class="flex items-center space-x-2.5 group">
                        <div class="h-9 w-9 rounded-xl bg-gradient-to-br from-[#6577F0] to-[#4F5FE8] text-white flex items-center justify-center font-heading font-extrabold text-sm shadow-sm shrink-0 transition-transform group-hover:scale-105">
                            AL
                        </div>
                        <div class="min-w-0">
                            <div class="flex items-center space-x-2">
                                <span class="font-heading font-extrabold text-sm text-white leading-tight truncate">
                                    andik<span class="text-[#FF8A5B]">.</span>
                                </span>
                                <span class="hidden sm:inline-flex items-center text-[10px] uppercase font-bold py-0.5 px-2 rounded-full bg-[#4F5FE8] text-white">
                                    POS Kasir
                                </span>
                            </div>
                            <span class="text-[11px] text-slate-400 font-medium leading-none hidden sm:block">Usaha Rumahan Mandiri</span>
                        </div>
                    </Link>

                    <!-- Desktop Nav Links (Hidden on mobile < 1024px) -->
                    <nav class="hidden lg:flex items-center space-x-1.5 pl-4 border-l border-white/10">
                        <Link
                            href="/production/station"
                            class="inline-flex min-h-[36px] items-center space-x-1.5 px-3 py-1.5 rounded-full text-slate-200 hover:text-white hover:bg-white/10 transition-all text-xs font-semibold"
                            title="Antrean & Status Cucian"
                        >
                            <Shirt class="h-4 w-4" />
                            <span>Antrean Cuci</span>
                        </Link>

                        <Link
                            href="/dashboard"
                            class="inline-flex min-h-[36px] items-center space-x-1.5 px-3 py-1.5 rounded-full text-slate-200 hover:text-white hover:bg-white/10 transition-all text-xs font-semibold"
                            title="Laporan Keuangan & Laba Rugi"
                        >
                            <LayoutDashboard class="h-4 w-4" />
                            <span>Laporan Keuangan</span>
                        </Link>

                        <Link
                            href="/owner/expenses"
                            class="inline-flex min-h-[36px] items-center space-x-1.5 px-3 py-1.5 rounded-full text-slate-200 hover:text-white hover:bg-white/10 transition-all text-xs font-semibold"
                            title="Catat Pengeluaran Toko"
                        >
                            <Receipt class="h-4 w-4 text-[#FF8A5B]" />
                            <span>Biaya Toko</span>
                        </Link>

                        <Link
                            href="/owner/receivables"
                            class="inline-flex min-h-[36px] items-center space-x-1.5 px-3 py-1.5 rounded-full text-slate-200 hover:text-white hover:bg-white/10 transition-all text-xs font-semibold"
                            title="Buku Piutang & Tagihan"
                        >
                            <Wallet class="h-4 w-4 text-[#38BDF8]" />
                            <span>Buku Piutang</span>
                        </Link>

                        <Link
                            href="/owner/settings"
                            class="inline-flex min-h-[36px] items-center space-x-1.5 px-3 py-1.5 rounded-full text-slate-200 hover:text-white hover:bg-white/10 transition-all text-xs font-semibold"
                            title="Pengaturan Toko & WhatsApp"
                        >
                            <Settings class="h-4 w-4" />
                            <span>Pengaturan</span>
                        </Link>
                    </nav>
                </div>

                <!-- Center: Operational Status & Realtime Clock (Desktop) -->
                <div class="hidden md:flex items-center space-x-3">
                    <div class="flex items-center space-x-2 px-3.5 py-1.5 rounded-full bg-emerald-500/15 border border-emerald-500/30 text-emerald-300 text-xs font-semibold">
                        <span class="h-2 w-2 rounded-full bg-emerald-400 animate-pulse"></span>
                        <span>Toko Buka • Kasir: {{ user?.name ?? 'Pemilik' }}</span>
                    </div>

                    <div class="flex items-center space-x-1.5 text-xs text-slate-200 font-mono font-medium bg-[#1A2566] px-3 py-1.5 rounded-full border border-white/10">
                        <Clock class="h-3.5 w-3.5 text-[#FFE14D]" />
                        <span>{{ currentTime }}</span>
                    </div>
                </div>

                <!-- Right: Mobile Menu Toggle (< 1024px) & Hotkeys/Status (Desktop) -->
                <div class="flex items-center space-x-2.5">
                    <div class="relative hidden sm:flex items-center">
                        <button
                            type="button"
                            @click="showWaTooltip = !showWaTooltip"
                            class="inline-flex min-h-[36px] items-center space-x-1.5 px-3 py-1.5 rounded-full border border-white/15 bg-white/10 text-white text-xs font-medium hover:bg-white/20 transition-all cursor-pointer"
                        >
                            <WifiOff class="h-3.5 w-3.5 text-amber-300" />
                            <span class="hidden md:inline">WA Manual</span>
                        </button>

                        <div
                            v-if="showWaTooltip"
                            class="absolute right-0 top-12 z-50 w-72 rounded-2xl bg-white p-4 text-xs text-slate-700 shadow-2xl border border-slate-200"
                        >
                            <div class="flex justify-between items-start mb-1.5">
                                <span class="font-bold text-[#10194D] uppercase tracking-wide">Mode WhatsApp Manual</span>
                                <button
                                    type="button"
                                    class="text-slate-400 hover:text-slate-700 -m-1 p-1"
                                    @click="showWaTooltip = false"
                                >
                                    <X class="h-4 w-4" />
                                </button>
                            </div>
                            <p class="text-slate-600 leading-relaxed font-normal">
                                Notifikasi struk dan status cucian dapat dikirim manual via tombol wa.me yang otomatis mengisi nomor customer & format struk.
                            </p>
                        </div>
                    </div>

                    <div class="hidden lg:flex items-center space-x-1.5 text-xs">
                        <kbd class="px-2.5 py-1 bg-white/10 text-slate-200 font-mono rounded-lg border border-white/15 text-xs font-semibold">F1 Cari</kbd>
                        <kbd class="px-2.5 py-1 bg-[#FF8A5B] text-[#0A1033] font-mono rounded-lg text-xs font-bold shadow-xs">F8 Bayar</kbd>
                    </div>

                    <!-- Mobile Menu Hamburger Button (< 1024px) -->
                    <button
                        type="button"
                        class="lg:hidden inline-flex h-9 w-9 items-center justify-center rounded-xl bg-white/10 text-white hover:bg-white/20 cursor-pointer"
                        @click="isMobileNavOpen = true"
                        aria-label="Buka menu navigasi"
                    >
                        <Menu class="h-5 w-5" />
                    </button>
                </div>
            </div>
        </header>

        <!-- 60/40 Horizontal Split Desktop / Stacked Mobile Layout -->
        <main class="flex-1 min-h-0 max-w-[1920px] w-full mx-auto p-3 sm:p-4 lg:p-4 overflow-hidden">
            <div class="h-full lg:grid lg:grid-cols-12 lg:gap-5 min-h-0">
                <!-- Left 60% Panel: Catalog & Order Builder (Columns 1-7 = ~58.3%) -->
                <div class="lg:col-span-7 flex flex-col h-full overflow-y-auto pr-0 lg:pr-2 min-h-0">
                    <slot name="catalog">
                        <slot />
                    </slot>
                </div>

                <!-- Right 40% Panel: Persistent Cart & Checkout Summary (Columns 8-12 = ~41.7%) -->
                <div class="hidden lg:flex lg:col-span-5 flex-col h-full bg-white rounded-2xl border border-slate-200/90 shadow-sm overflow-hidden p-4 min-h-0">
                    <slot name="cart" />
                </div>
            </div>
        </main>

        <!-- Mobile & Tablet (< 1024px) Sticky Bottom Bar (56px) -->
        <div class="lg:hidden fixed bottom-0 inset-x-0 z-40 bg-[#10194D] text-white border-t border-[#1E293B] px-4 h-16 flex items-center justify-between pb-[env(safe-area-inset-bottom,0px)] shadow-2xl">
            <div class="flex items-center space-x-3">
                <div class="relative flex items-center justify-center h-10 w-10 rounded-xl bg-[#4F5FE8] text-white shadow-sm">
                    <ShoppingBag class="h-5 w-5" />
                    <span
                        v-if="cartCount > 0"
                        class="absolute -top-1.5 -right-1.5 flex h-5 min-w-[20px] items-center justify-center rounded-full bg-[#FF8A5B] px-1 text-[11px] font-bold text-[#0A1033] shadow-xs"
                    >
                        {{ cartCount }}
                    </span>
                </div>
                <div>
                    <span class="block text-[10px] text-slate-400 uppercase tracking-wider font-semibold leading-none">Total Tagihan</span>
                    <span class="block text-base font-bold font-mono text-white leading-tight">{{ cartTotalFormatted }}</span>
                </div>
            </div>

            <Button
                type="button"
                variant="default"
                class="min-h-[42px] px-5 font-bold text-sm bg-[#FF8A5B] hover:bg-[#F26B3A] text-[#0A1033] rounded-xl shadow-sm uppercase tracking-wider"
                @click="isMobileCartOpen = true"
            >
                Lihat Keranjang
            </Button>
        </div>

        <!-- Mobile Navigation Sheet (< 1024px) -->
        <Sheet v-model:open="isMobileNavOpen">
            <SheetContent side="left" class="p-0 w-80 max-w-[85vw] flex flex-col bg-[#10194D] text-white border-r border-[#1E293B] shadow-2xl">
                <SheetHeader class="p-5 border-b border-[#1E293B] text-left bg-[#0A1033]">
                    <div class="flex items-center space-x-3 pr-8">
                        <div class="h-9 w-9 rounded-xl bg-gradient-to-br from-[#6577F0] to-[#4F5FE8] text-white flex items-center justify-center font-heading font-extrabold text-sm shadow-sm shrink-0">
                            AL
                        </div>
                        <div class="truncate">
                            <SheetTitle class="text-sm font-heading font-bold text-white leading-tight tracking-tight">{{ storeName }}</SheetTitle>
                            <SheetDescription class="text-xs font-medium text-slate-400">Menu Manajemen Toko</SheetDescription>
                        </div>
                    </div>
                </SheetHeader>

                <div class="p-4 space-y-2 flex-1 overflow-y-auto">
                    <div class="px-2 pb-1 text-[10px] font-bold uppercase tracking-wider text-slate-400">
                        Menu Navigasi
                    </div>
                    <nav class="space-y-1">
                        <Link
                            href="/pos"
                            class="flex items-center space-x-3 px-3.5 py-2.5 rounded-xl text-sm font-bold text-white bg-[#4F5FE8] shadow-sm shadow-[#4F5FE8]/30 min-h-[42px] transition-all cursor-pointer"
                            @click="isMobileNavOpen = false"
                        >
                            <ShoppingBag class="h-5 w-5 shrink-0" />
                            <span>Kasir POS (Utama)</span>
                        </Link>

                        <Link
                            href="/production/station"
                            class="flex items-center space-x-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold text-slate-300 hover:text-white hover:bg-white/10 min-h-[42px] transition-all cursor-pointer"
                            @click="isMobileNavOpen = false"
                        >
                            <Shirt class="h-5 w-5 shrink-0" />
                            <span>Antrean Cuci & Progres</span>
                        </Link>

                        <Link
                            href="/dashboard"
                            class="flex items-center space-x-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold text-slate-300 hover:text-white hover:bg-white/10 min-h-[42px] transition-all cursor-pointer"
                            @click="isMobileNavOpen = false"
                        >
                            <LayoutDashboard class="h-5 w-5 shrink-0" />
                            <span>Laporan Keuangan</span>
                        </Link>

                        <Link
                            href="/owner/expenses"
                            class="flex items-center space-x-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold text-slate-300 hover:text-white hover:bg-white/10 min-h-[42px] transition-all cursor-pointer"
                            @click="isMobileNavOpen = false"
                        >
                            <Receipt class="h-5 w-5 text-[#FF8A5B] shrink-0" />
                            <span>Pengeluaran Toko</span>
                        </Link>

                        <Link
                            href="/owner/receivables"
                            class="flex items-center space-x-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold text-slate-300 hover:text-white hover:bg-white/10 min-h-[42px] transition-all cursor-pointer"
                            @click="isMobileNavOpen = false"
                        >
                            <Wallet class="h-5 w-5 text-[#38BDF8] shrink-0" />
                            <span>Buku Piutang</span>
                        </Link>

                        <Link
                            href="/owner/settings"
                            class="flex items-center space-x-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold text-slate-300 hover:text-white hover:bg-white/10 min-h-[42px] transition-all cursor-pointer"
                            @click="isMobileNavOpen = false"
                        >
                            <Settings class="h-5 w-5 shrink-0" />
                            <span>Pengaturan Toko & WA</span>
                        </Link>
                    </nav>
                </div>

                <!-- Drawer Footer: User Profile & Logout -->
                <div class="p-4 border-t border-[#1E293B] bg-[#0A1033] space-y-2.5">
                    <div class="flex items-center space-x-2.5 p-2.5 rounded-xl bg-[#1A2566] text-white">
                        <div class="h-8 w-8 rounded-lg bg-[#4F5FE8] text-white flex items-center justify-center font-bold text-xs shrink-0">
                            {{ user?.name ? user.name.slice(0, 2).toUpperCase() : 'OW' }}
                        </div>
                        <div class="truncate flex-1">
                            <span class="block text-xs font-bold text-white leading-tight truncate">{{ user?.name ?? 'Andik' }}</span>
                            <span class="block text-[10px] text-slate-400 truncate">Owner (Pemilik & Kasir)</span>
                        </div>
                    </div>

                    <Link
                        href="/logout"
                        method="post"
                        as="button"
                        @click="isMobileNavOpen = false"
                        class="flex w-full items-center justify-center space-x-2 px-4 py-2.5 rounded-xl min-h-[40px] text-xs font-bold text-red-400 hover:bg-red-500/10 hover:text-red-300 transition-all cursor-pointer"
                    >
                        <LogOut class="h-4 w-4 shrink-0" />
                        <span>Keluar dari Aplikasi</span>
                    </Link>
                </div>
            </SheetContent>
        </Sheet>

        <!-- Mobile & Tablet Expandable Bottom Sheet for Cart & Checkout -->
        <Sheet v-model:open="isMobileCartOpen">
            <SheetContent side="bottom" class="p-0 max-h-[92vh] flex flex-col bg-white border-t border-slate-200">
                <SheetHeader class="p-4 border-b border-slate-200 text-left bg-slate-50">
                    <div class="flex items-center justify-between">
                        <div>
                            <SheetTitle class="text-base font-bold text-slate-900">Keranjang & Pembayaran</SheetTitle>
                            <SheetDescription class="text-xs font-medium text-slate-500">
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
