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
    LogOut,
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
    <div class="fixed inset-0 flex flex-col overflow-hidden bg-bg font-body text-text antialiased select-none pb-[calc(env(safe-area-inset-bottom,0px)+4.5rem)] lg:pb-0">
        <Head :title="`${title} - Andik Laundry POS`" />

        <!-- High-Speed POS Header (Pinned / Sticky Top) -->
        <header class="h-16 shrink-0 bg-navy-900 text-white border-b border-navy-950 shadow-sm z-40">
            <div class="px-4 sm:px-6 h-16 flex items-center justify-between">
                <!-- Left: Home / AL Brand & Store Title + Desktop Navigation -->
                <div class="flex items-center space-x-4">
                    <Link href="/" class="flex items-center space-x-2.5 group">
                        <div class="h-9 w-9 rounded-md bg-primary-600 text-white flex items-center justify-center font-heading font-extrabold text-sm shadow-sm shrink-0 transition-transform duration-[200ms] ease-standard group-hover:scale-105">
                            AL
                        </div>
                        <div class="min-w-0">
                            <div class="flex items-center space-x-2">
                                <span class="font-heading font-extrabold text-sm text-white leading-tight truncate">
                                    andik<span class="text-accent-500">.</span>
                                </span>
                                <span class="hidden sm:inline-flex items-center text-[10px] uppercase font-bold py-0.5 px-2 rounded-pill bg-accent-500 text-navy-950">
                                    POS Kasir
                                </span>
                            </div>
                            <span class="text-[11px] text-text-on-dark-muted font-medium leading-none hidden sm:block">Usaha Rumahan Mandiri</span>
                        </div>
                    </Link>

                    <!-- Desktop Nav Links (Hidden on mobile < 1024px) -->
                    <nav class="hidden lg:flex items-center space-x-1.5 pl-4 border-l border-white/10" aria-label="Navigasi POS">
                        <Link
                            href="/production/station"
                            class="inline-flex min-h-[36px] items-center space-x-1.5 px-3 py-1.5 rounded-pill text-text-on-dark-muted hover:text-white hover:bg-white/10 transition-all duration-[120ms] ease-standard text-xs font-semibold focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-primary-500"
                            title="Antrean & Status Cucian"
                        >
                            <Shirt class="h-4 w-4" aria-hidden="true" />
                            <span>Antrean Cuci</span>
                        </Link>

                        <Link
                            href="/dashboard"
                            class="inline-flex min-h-[36px] items-center space-x-1.5 px-3 py-1.5 rounded-pill text-text-on-dark-muted hover:text-white hover:bg-white/10 transition-all duration-[120ms] ease-standard text-xs font-semibold focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-primary-500"
                            title="Laporan Keuangan & Laba Rugi"
                        >
                            <LayoutDashboard class="h-4 w-4" aria-hidden="true" />
                            <span>Laporan Keuangan</span>
                        </Link>

                        <Link
                            href="/owner/expenses"
                            class="inline-flex min-h-[36px] items-center space-x-1.5 px-3 py-1.5 rounded-pill text-text-on-dark-muted hover:text-white hover:bg-white/10 transition-all duration-[120ms] ease-standard text-xs font-semibold focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-primary-500"
                            title="Catat Pengeluaran Toko"
                        >
                            <Receipt class="h-4 w-4 text-accent-500" aria-hidden="true" />
                            <span>Biaya Toko</span>
                        </Link>

                        <Link
                            href="/owner/receivables"
                            class="inline-flex min-h-[36px] items-center space-x-1.5 px-3 py-1.5 rounded-pill text-text-on-dark-muted hover:text-white hover:bg-white/10 transition-all duration-[120ms] ease-standard text-xs font-semibold focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-primary-500"
                            title="Buku Piutang & Tagihan"
                        >
                            <Wallet class="h-4 w-4 text-primary-500" aria-hidden="true" />
                            <span>Buku Piutang</span>
                        </Link>

                        <Link
                            href="/owner/settings"
                            class="inline-flex min-h-[36px] items-center space-x-1.5 px-3 py-1.5 rounded-pill text-text-on-dark-muted hover:text-white hover:bg-white/10 transition-all duration-[120ms] ease-standard text-xs font-semibold focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-primary-500"
                            title="Pengaturan Toko & WhatsApp"
                        >
                            <Settings class="h-4 w-4" aria-hidden="true" />
                            <span>Pengaturan</span>
                        </Link>
                    </nav>
                </div>

                <!-- Center: Operational Status & Realtime Clock (Desktop) -->
                <div class="hidden md:flex items-center space-x-3">
                    <div class="flex items-center space-x-2 px-3.5 py-1.5 rounded-pill bg-accent-500/15 border border-accent-500/30 text-accent-100 text-xs font-semibold">
                        <span class="h-2 w-2 rounded-full bg-accent-500 animate-pulse" aria-hidden="true"></span>
                        <span>Toko Buka • Kasir: {{ user?.name ?? 'Pemilik' }}</span>
                    </div>

                    <div class="flex items-center space-x-1.5 text-xs text-white font-mono font-medium bg-navy-800 px-3 py-1.5 rounded-pill border border-white/10">
                        <Clock class="h-3.5 w-3.5 text-accent-100" aria-hidden="true" />
                        <span class="tabular-nums">{{ currentTime }}</span>
                    </div>
                </div>

                <!-- Right: Mobile Menu Toggle (< 1024px) & Hotkeys/Status (Desktop) -->
                <div class="flex items-center space-x-2.5">
                    <div class="relative hidden sm:flex items-center">
                        <button
                            type="button"
                            @click="showWaTooltip = !showWaTooltip"
                            class="inline-flex min-h-[36px] items-center space-x-1.5 px-3 py-1.5 rounded-pill border border-white/15 bg-white/10 text-white text-xs font-medium hover:bg-white/20 transition-all duration-[120ms] ease-standard cursor-pointer focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-primary-500"
                        >
                            <WifiOff class="h-3.5 w-3.5 text-accent-100" aria-hidden="true" />
                            <span class="hidden md:inline">WA Manual</span>
                        </button>

                        <div
                            v-if="showWaTooltip"
                            class="absolute right-0 top-12 z-50 w-72 rounded-md bg-surface p-4 text-xs text-text shadow-lg border border-border"
                        >
                            <div class="flex justify-between items-start mb-1.5">
                                <span class="overline-label text-text">Mode WhatsApp Manual</span>
                                <button
                                    type="button"
                                    class="text-text-muted hover:text-text -m-1 p-1 rounded-sm focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-primary-500"
                                    @click="showWaTooltip = false"
                                    aria-label="Tutup info"
                                >
                                    <X class="h-4 w-4" />
                                </button>
                            </div>
                            <p class="text-text-muted leading-relaxed font-normal">
                                Notifikasi struk dan status cucian dapat dikirim manual via tombol wa.me yang otomatis mengisi nomor customer & format struk.
                            </p>
                        </div>
                    </div>

                    <div class="hidden lg:flex items-center space-x-1.5 text-xs">
                        <kbd class="px-2.5 py-1 bg-white/10 text-white font-mono rounded-md border border-white/15 text-xs font-semibold">F1 Cari</kbd>
                        <kbd class="px-2.5 py-1 bg-accent-500 text-navy-950 font-mono rounded-md text-xs font-bold">F8 Bayar</kbd>
                    </div>

                    <!-- Mobile Menu Hamburger Button (< 1024px) -->
                    <button
                        type="button"
                        class="lg:hidden inline-flex h-9 w-9 items-center justify-center rounded-md bg-white/10 text-white hover:bg-white/20 cursor-pointer focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-primary-500 transition-all duration-[120ms] ease-standard"
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
                <div class="hidden lg:flex lg:col-span-5 flex-col h-full bg-surface rounded-lg border border-border shadow-md overflow-hidden p-4 min-h-0">
                    <slot name="cart" />
                </div>
            </div>
        </main>

        <!-- Mobile & Tablet (< 1024px) Sticky Bottom Bar (56px) -->
        <div class="lg:hidden fixed bottom-0 inset-x-0 z-40 bg-navy-900 text-white border-t border-navy-950 px-4 h-16 flex items-center justify-between pb-[env(safe-area-inset-bottom,0px)] shadow-lg">
            <div class="flex items-center space-x-3">
                <div class="relative flex items-center justify-center h-10 w-10 rounded-md bg-primary-700 text-white shadow-sm">
                    <ShoppingBag class="h-5 w-5" aria-hidden="true" />
                    <span
                        v-if="cartCount > 0"
                        class="absolute -top-1.5 -right-1.5 flex h-5 min-w-[20px] items-center justify-center rounded-pill bg-accent-500 px-1 text-[11px] font-bold text-navy-950 shadow-sm tabular-nums"
                    >
                        {{ cartCount }}
                    </span>
                </div>
                <div>
                    <span class="overline-label block text-text-on-dark-muted leading-none">Total Tagihan</span>
                    <span class="block text-base font-bold font-mono text-white leading-tight tabular-nums">{{ cartTotalFormatted }}</span>
                </div>
            </div>

            <Button
                type="button"
                variant="default"
                class="min-h-[44px] px-5 font-bold text-sm uppercase tracking-wider"
                @click="isMobileCartOpen = true"
            >
                Lihat Keranjang
            </Button>
        </div>

        <!-- Mobile Navigation Sheet (< 1024px) -->
        <Sheet v-model:open="isMobileNavOpen">
            <SheetContent side="left" class="p-0 w-80 max-w-[85vw] flex flex-col bg-navy-900 text-white border-r border-navy-950 shadow-lg">
                <SheetHeader class="p-5 border-b border-white/10 text-left bg-navy-950">
                    <div class="flex items-center space-x-3 pr-8">
                        <div class="h-9 w-9 rounded-md bg-primary-600 text-white flex items-center justify-center font-heading font-extrabold text-sm shadow-sm shrink-0">
                            AL
                        </div>
                        <div class="truncate">
                            <SheetTitle class="text-sm font-heading font-bold text-white leading-tight tracking-tight">{{ storeName }}</SheetTitle>
                            <SheetDescription class="text-xs font-medium text-text-on-dark-muted">Menu Manajemen Toko</SheetDescription>
                        </div>
                    </div>
                </SheetHeader>

                <div class="p-4 space-y-4 flex-1 overflow-y-auto">
                    <div>
                        <div class="overline-label px-2 pb-2 text-text-on-dark-muted">Operasional</div>
                        <nav class="space-y-1" aria-label="Operasional">
                            <Link
                                href="/pos"
                                class="flex items-center space-x-3 px-3.5 py-2.5 rounded-pill text-sm font-bold text-white bg-primary-700 shadow-sm min-h-[42px] transition-all duration-[120ms] ease-standard cursor-pointer focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-primary-500"
                                @click="isMobileNavOpen = false"
                            >
                                <ShoppingBag class="h-5 w-5 shrink-0" aria-hidden="true" />
                                <span>Kasir POS (Utama)</span>
                            </Link>

                            <Link
                                href="/production/station"
                                class="flex items-center space-x-3 px-3.5 py-2.5 rounded-pill text-sm font-semibold text-text-on-dark-muted hover:text-white hover:bg-white/10 min-h-[42px] transition-all duration-[120ms] ease-standard cursor-pointer focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-primary-500"
                                @click="isMobileNavOpen = false"
                            >
                                <Shirt class="h-5 w-5 shrink-0" aria-hidden="true" />
                                <span>Antrean Cuci & Progres</span>
                            </Link>
                        </nav>
                    </div>

                    <div>
                        <div class="overline-label px-2 pb-2 text-text-on-dark-muted">Keuangan</div>
                        <nav class="space-y-1" aria-label="Keuangan">
                            <Link
                                href="/dashboard"
                                class="flex items-center space-x-3 px-3.5 py-2.5 rounded-pill text-sm font-semibold text-text-on-dark-muted hover:text-white hover:bg-white/10 min-h-[42px] transition-all duration-[120ms] ease-standard cursor-pointer focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-primary-500"
                                @click="isMobileNavOpen = false"
                            >
                                <LayoutDashboard class="h-5 w-5 shrink-0" aria-hidden="true" />
                                <span>Laporan Keuangan</span>
                            </Link>

                            <Link
                                href="/owner/expenses"
                                class="flex items-center space-x-3 px-3.5 py-2.5 rounded-pill text-sm font-semibold text-text-on-dark-muted hover:text-white hover:bg-white/10 min-h-[42px] transition-all duration-[120ms] ease-standard cursor-pointer focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-primary-500"
                                @click="isMobileNavOpen = false"
                            >
                                <Receipt class="h-5 w-5 text-accent-500 shrink-0" aria-hidden="true" />
                                <span>Pengeluaran Toko</span>
                            </Link>

                            <Link
                                href="/owner/receivables"
                                class="flex items-center space-x-3 px-3.5 py-2.5 rounded-pill text-sm font-semibold text-text-on-dark-muted hover:text-white hover:bg-white/10 min-h-[42px] transition-all duration-[120ms] ease-standard cursor-pointer focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-primary-500"
                                @click="isMobileNavOpen = false"
                            >
                                <Wallet class="h-5 w-5 text-primary-500 shrink-0" aria-hidden="true" />
                                <span>Buku Piutang</span>
                            </Link>
                        </nav>
                    </div>

                    <div>
                        <div class="overline-label px-2 pb-2 text-text-on-dark-muted">Pengaturan</div>
                        <nav class="space-y-1" aria-label="Pengaturan">
                            <Link
                                href="/owner/settings"
                                class="flex items-center space-x-3 px-3.5 py-2.5 rounded-pill text-sm font-semibold text-text-on-dark-muted hover:text-white hover:bg-white/10 min-h-[42px] transition-all duration-[120ms] ease-standard cursor-pointer focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-primary-500"
                                @click="isMobileNavOpen = false"
                            >
                                <Settings class="h-5 w-5 shrink-0" aria-hidden="true" />
                                <span>Pengaturan Toko & WA</span>
                            </Link>
                        </nav>
                    </div>
                </div>

                <!-- Drawer Footer: User Profile & Logout -->
                <div class="p-4 border-t border-white/10 bg-navy-950 space-y-2.5">
                    <div class="flex items-center space-x-2.5 p-2.5 rounded-md bg-navy-800 text-white">
                        <div class="h-8 w-8 rounded-md bg-primary-600 text-white flex items-center justify-center font-bold text-xs shrink-0">
                            {{ user?.name ? user.name.slice(0, 2).toUpperCase() : 'OW' }}
                        </div>
                        <div class="truncate flex-1">
                            <span class="block text-xs font-bold text-white leading-tight truncate">{{ user?.name ?? 'Andik' }}</span>
                            <span class="block text-[10px] text-text-on-dark-muted truncate">Owner (Pemilik & Kasir)</span>
                        </div>
                    </div>

                    <Link
                        href="/logout"
                        method="post"
                        as="button"
                        @click="isMobileNavOpen = false"
                        class="flex w-full items-center justify-center space-x-2 px-4 py-2.5 rounded-pill min-h-[40px] text-xs font-bold text-danger/80 hover:bg-danger/15 hover:text-white transition-all duration-[120ms] ease-standard cursor-pointer focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-primary-500"
                    >
                        <LogOut class="h-4 w-4 shrink-0" />
                        <span>Keluar dari Aplikasi</span>
                    </Link>
                </div>
            </SheetContent>
        </Sheet>

        <!-- Mobile & Tablet Expandable Bottom Sheet for Cart & Checkout -->
        <Sheet v-model:open="isMobileCartOpen">
            <SheetContent side="bottom" class="p-0 max-h-[92vh] flex flex-col bg-surface border-t border-border">
                <SheetHeader class="p-4 border-b border-border text-left bg-bg">
                    <div class="flex items-center justify-between">
                        <div>
                            <SheetTitle class="text-base font-bold text-text">Keranjang & Pembayaran</SheetTitle>
                            <SheetDescription class="text-xs font-medium text-text-muted">
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
