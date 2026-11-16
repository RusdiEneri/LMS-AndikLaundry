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
    <div class="min-h-screen bg-[#FFFDF5] flex flex-col font-sans text-black antialiased select-none pb-[calc(env(safe-area-inset-bottom,0px)+4.5rem)] lg:pb-0">
        <Head :title="`${title} - Andik Laundry POS`" />

        <!-- High-Speed POS Header -->
        <header class="relative z-40 bg-[#FFEB3B] border-b-[3.5px] border-black shadow-[0_4px_0px_#000]">
            <div class="px-3 sm:px-6 h-14 flex items-center justify-between">
                <!-- Left: Home / AL Brand & Store Title + Desktop Navigation -->
                <div class="flex items-center space-x-3">
                    <div class="flex items-center space-x-2.5">
                        <div class="h-9 w-9 rounded-[8px] bg-black text-[#FFEB3B] border-2 border-black flex items-center justify-center font-black text-sm shadow-[2px_2px_0px_#000] shrink-0">
                            AL
                        </div>
                        <div class="min-w-0">
                            <div class="flex items-center space-x-2">
                                <span class="font-black text-sm text-black leading-tight truncate">{{ storeName }}</span>
                                <span class="hidden sm:inline-flex items-center text-[10px] uppercase font-black py-0.5 px-2 rounded-[4px] bg-black text-[#FFEB3B] border border-black shadow-[1px_1px_0px_#000]">
                                    Owner
                                </span>
                            </div>
                            <span class="text-xs font-bold text-black/70 leading-none hidden sm:block">Usaha Rumahan Mandiri</span>
                        </div>
                    </div>

                    <!-- Desktop Nav Links (Hidden on mobile < 1024px) -->
                    <nav class="hidden lg:flex items-center space-x-1.5 pl-3 border-l-2 border-black">
                        <Link
                            href="/dashboard"
                            class="inline-flex min-h-[36px] items-center space-x-1.5 px-3 py-1.5 rounded-[8px] border-2 border-black bg-white text-black hover:bg-[#FFF9D2] hover:-translate-y-0.5 transition-all text-xs font-black shadow-[2px_2px_0px_#000] active:translate-x-0.5 active:translate-y-0.5 active:shadow-none"
                            title="Dashboard & Laporan Usaha"
                        >
                            <LayoutDashboard class="h-4 w-4 text-black" />
                            <span>Dashboard</span>
                        </Link>

                        <Link
                            href="/production/station"
                            class="inline-flex min-h-[36px] items-center space-x-1.5 px-3 py-1.5 rounded-[8px] border-2 border-black bg-white text-black hover:bg-[#FFF9D2] hover:-translate-y-0.5 transition-all text-xs font-black shadow-[2px_2px_0px_#000] active:translate-x-0.5 active:translate-y-0.5 active:shadow-none"
                            title="Antrean & Status Cucian"
                        >
                            <Shirt class="h-4 w-4 text-black" />
                            <span>Antrean Cuci</span>
                        </Link>

                        <Link
                            href="/owner/expenses"
                            class="inline-flex min-h-[36px] items-center space-x-1.5 px-3 py-1.5 rounded-[8px] border-2 border-black bg-white text-black hover:bg-[#FFF9D2] hover:-translate-y-0.5 transition-all text-xs font-black shadow-[2px_2px_0px_#000] active:translate-x-0.5 active:translate-y-0.5 active:shadow-none"
                            title="Catat Pengeluaran Toko"
                        >
                            <Receipt class="h-4 w-4 text-[#FF5252]" />
                            <span>Biaya Toko</span>
                        </Link>

                        <Link
                            href="/owner/settings"
                            class="inline-flex min-h-[36px] items-center space-x-1.5 px-3 py-1.5 rounded-[8px] border-2 border-black bg-white text-black hover:bg-[#FFF9D2] hover:-translate-y-0.5 transition-all text-xs font-black shadow-[2px_2px_0px_#000] active:translate-x-0.5 active:translate-y-0.5 active:shadow-none"
                            title="Pengaturan Toko & WhatsApp"
                        >
                            <Settings class="h-4 w-4 text-black" />
                            <span>Pengaturan</span>
                        </Link>
                    </nav>
                </div>

                <!-- Center: Operational Status & Realtime Clock (Desktop) -->
                <div class="hidden md:flex items-center space-x-3">
                    <div class="flex items-center space-x-2 px-3 py-1 rounded-[6px] bg-[#22C55E] border-2 border-black text-black text-xs font-black shadow-[2px_2px_0px_#000]">
                        <span class="h-2.5 w-2.5 rounded-full bg-black"></span>
                        <span>Toko Buka • Kasir: {{ user?.name ?? 'Pemilik' }}</span>
                    </div>

                    <div class="flex items-center space-x-1.5 text-xs text-black font-mono font-black bg-white px-3 py-1 rounded-[6px] border-2 border-black shadow-[2px_2px_0px_#000]">
                        <Clock class="h-4 w-4 text-black" />
                        <span>{{ currentTime }}</span>
                    </div>
                </div>

                <!-- Right: Mobile Menu Toggle (< 1024px) & Hotkeys/Status (Desktop) -->
                <div class="flex items-center space-x-2">
                    <div class="relative hidden sm:flex items-center">
                        <button
                            type="button"
                            @click="showWaTooltip = !showWaTooltip"
                            class="inline-flex min-h-[38px] items-center space-x-1.5 px-3 py-1.5 rounded-[8px] border-2 border-black bg-white text-black text-xs font-black shadow-[2px_2px_0px_#000] hover:bg-[#FFF9D2] active:translate-x-0.5 active:translate-y-0.5 active:shadow-none cursor-pointer"
                        >
                            <WifiOff class="h-4 w-4 text-black" />
                            <span class="hidden md:inline">WA Fallback</span>
                        </button>

                        <div
                            v-if="showWaTooltip"
                            class="absolute right-0 top-12 z-50 w-72 rounded-[10px] bg-white p-3.5 text-xs text-black shadow-[5px_5px_0px_#000] border-[2.5px] border-black"
                        >
                            <div class="flex justify-between items-start mb-1">
                                <span class="font-black text-black uppercase tracking-wider">Mode Fallback Aktif</span>
                                <button
                                    type="button"
                                    class="text-black hover:text-[#FF5252] min-h-[44px] min-w-[44px] -m-2 flex items-center justify-center font-bold"
                                    @click="showWaTooltip = false"
                                >
                                    <X class="h-4 w-4 stroke-[3]" />
                                </button>
                            </div>
                            <p class="text-black/80 font-bold leading-relaxed">
                                Gateway WuzAPI offline. Notifikasi struk dapat dikirim manual via tombol wa.me yang otomatis mengisi nomor customer & format struk.
                            </p>
                        </div>
                    </div>

                    <div class="hidden lg:flex items-center space-x-1.5 text-xs">
                        <kbd class="px-2.5 py-1 bg-white text-black font-mono rounded-[6px] border-2 border-black text-xs font-black shadow-[2px_2px_0px_#000]">F1 Cari</kbd>
                        <kbd class="px-2.5 py-1 bg-[#FFEB3B] text-black font-mono rounded-[6px] border-2 border-black text-xs font-black shadow-[2px_2px_0px_#000]">F8 Bayar</kbd>
                    </div>

                    <!-- Mobile Menu Hamburger Button (< 1024px) -->
                    <button
                        type="button"
                        class="lg:hidden inline-flex min-h-[44px] min-w-[44px] items-center justify-center rounded-[8px] border-2 border-black bg-white text-black hover:bg-[#FFF9D2] shadow-[2px_2px_0px_#000] active:translate-x-0.5 active:translate-y-0.5 active:shadow-none cursor-pointer"
                        @click="isMobileNavOpen = true"
                        aria-label="Buka menu navigasi"
                    >
                        <Menu class="h-5 w-5 text-black stroke-[2.5]" />
                    </button>
                </div>
            </div>
        </header>

        <!-- 60/40 Horizontal Split Desktop / Stacked Mobile Layout -->
        <div class="flex-1 max-w-[1920px] w-full mx-auto p-3 sm:p-4 lg:p-6 lg:h-[calc(100vh-3.5rem)] lg:overflow-hidden">
            <div class="h-full lg:grid lg:grid-cols-12 lg:gap-6">
                <!-- Left 60% Panel: Catalog & Order Builder (Columns 1-7 = ~58.3%) -->
                <div class="lg:col-span-7 flex flex-col h-full overflow-y-auto pr-0 lg:pr-2">
                    <slot name="catalog">
                        <slot />
                    </slot>
                </div>

                <!-- Right 40% Panel: Persistent Cart & Checkout Summary (Columns 8-12 = ~41.7%) -->
                <div class="hidden lg:flex lg:col-span-5 flex-col h-full bg-white rounded-[12px] border-[3.5px] border-black shadow-[6px_6px_0px_#000] overflow-hidden p-4">
                    <slot name="cart" />
                </div>
            </div>
        </div>

        <!-- Mobile & Tablet (< 1024px) Sticky Bottom Bar (56px) -->
        <div class="lg:hidden fixed bottom-0 inset-x-0 z-40 bg-[#FFEB3B] border-t-[3.5px] border-black px-4 h-16 flex items-center justify-between pb-[env(safe-area-inset-bottom,0px)] shadow-[0_-4px_0px_#000]">
            <div class="flex items-center space-x-3">
                <div class="relative flex items-center justify-center h-10 w-10 rounded-[8px] bg-black text-[#FFEB3B] border-2 border-black shadow-[2px_2px_0px_#000]">
                    <ShoppingBag class="h-5 w-5" />
                    <span
                        v-if="cartCount > 0"
                        class="absolute -top-2 -right-2 flex h-5 min-w-[20px] items-center justify-center rounded-full bg-[#FF5252] border border-black px-1 text-[11px] font-black text-black shadow-[1px_1px_0px_#000]"
                    >
                        {{ cartCount }}
                    </span>
                </div>
                <div>
                    <span class="block text-[11px] text-black uppercase tracking-wider font-black leading-none">Total Tagihan</span>
                    <span class="block text-base font-black font-mono text-black leading-tight">{{ cartTotalFormatted }}</span>
                </div>
            </div>

            <Button
                type="button"
                variant="default"
                class="min-h-[44px] px-5 font-black text-sm bg-black hover:bg-black/90 text-[#FFEB3B] active:translate-x-0.5 active:translate-y-0.5 rounded-[8px] border-2 border-black shadow-[3px_3px_0px_#000] uppercase tracking-wider"
                @click="isMobileCartOpen = true"
            >
                Lihat Keranjang
            </Button>
        </div>

        <!-- Mobile Navigation Sheet (< 1024px) -->
        <Sheet v-model:open="isMobileNavOpen">
            <SheetContent side="left" class="p-0 w-80 max-w-[85vw] flex flex-col bg-[#FFFDF5] border-r-[3.5px] border-black shadow-[6px_0px_0px_#000]">
                <SheetHeader class="p-4 border-b-[3px] border-black text-left bg-[#FFEB3B]">
                    <div class="flex items-center space-x-3 pr-8">
                        <div class="h-9 w-9 rounded-[8px] bg-black text-[#FFEB3B] border-2 border-black flex items-center justify-center font-black text-sm shadow-[2px_2px_0px_#000] shrink-0">
                            AL
                        </div>
                        <div class="truncate">
                            <SheetTitle class="text-sm font-black text-black leading-tight tracking-tight">{{ storeName }}</SheetTitle>
                            <SheetDescription class="text-xs font-bold text-black/70">Menu Manajemen Toko</SheetDescription>
                        </div>
                    </div>
                </SheetHeader>

                <div class="p-4 space-y-2 flex-1 overflow-y-auto">
                    <div class="px-2 pb-1 text-[10px] font-black uppercase tracking-wider text-black/70">
                        Menu Navigasi
                    </div>
                    <nav class="space-y-2">
                        <Link
                            href="/dashboard"
                            class="flex items-center space-x-3 px-3.5 py-2.5 rounded-[8px] border-2 border-black text-sm font-black text-black hover:bg-[#FFEB3B] shadow-[2px_2px_0px_#000] min-h-[44px] bg-white transition-all cursor-pointer"
                            @click="isMobileNavOpen = false"
                        >
                            <LayoutDashboard class="h-5 w-5 text-black stroke-[2.5]" />
                            <span>Dashboard & Laporan</span>
                        </Link>

                        <Link
                            href="/pos"
                            class="flex items-center space-x-3 px-3.5 py-2.5 rounded-[8px] border-2 border-black text-sm font-black text-black bg-[#FFEB3B] shadow-[3px_3px_0px_#000] min-h-[44px] transition-all cursor-pointer"
                            @click="isMobileNavOpen = false"
                        >
                            <ShoppingBag class="h-5 w-5 text-black stroke-[2.5]" />
                            <span>Kasir POS</span>
                        </Link>

                        <Link
                            href="/production/station"
                            class="flex items-center space-x-3 px-3.5 py-2.5 rounded-[8px] border-2 border-black text-sm font-black text-black hover:bg-[#FFEB3B] shadow-[2px_2px_0px_#000] min-h-[44px] bg-white transition-all cursor-pointer"
                            @click="isMobileNavOpen = false"
                        >
                            <Shirt class="h-5 w-5 text-black stroke-[2.5]" />
                            <span>Antrean Cuci & Progres</span>
                        </Link>

                        <Link
                            href="/owner/expenses"
                            class="flex items-center space-x-3 px-3.5 py-2.5 rounded-[8px] border-2 border-black text-sm font-black text-black hover:bg-[#FFEB3B] shadow-[2px_2px_0px_#000] min-h-[44px] bg-white transition-all cursor-pointer"
                            @click="isMobileNavOpen = false"
                        >
                            <Receipt class="h-5 w-5 text-[#FF5252] stroke-[2.5]" />
                            <span>Pengeluaran Toko</span>
                        </Link>

                        <Link
                            href="/owner/receivables"
                            class="flex items-center space-x-3 px-3.5 py-2.5 rounded-[8px] border-2 border-black text-sm font-black text-black hover:bg-[#FFEB3B] shadow-[2px_2px_0px_#000] min-h-[44px] bg-white transition-all cursor-pointer"
                            @click="isMobileNavOpen = false"
                        >
                            <Wallet class="h-5 w-5 text-[#2196F3] stroke-[2.5]" />
                            <span>Buku Piutang</span>
                        </Link>

                        <Link
                            href="/owner/settings"
                            class="flex items-center space-x-3 px-3.5 py-2.5 rounded-[8px] border-2 border-black text-sm font-black text-black hover:bg-[#FFEB3B] shadow-[2px_2px_0px_#000] min-h-[44px] bg-white transition-all cursor-pointer"
                            @click="isMobileNavOpen = false"
                        >
                            <Settings class="h-5 w-5 text-black stroke-[2.5]" />
                            <span>Pengaturan Toko & WA</span>
                        </Link>
                    </nav>
                </div>

                <!-- Drawer Footer: User Profile & Logout -->
                <div class="p-4 border-t-[3px] border-black bg-[#FFFDF5] space-y-2.5">
                    <div class="flex items-center space-x-2.5 p-2.5 rounded-[8px] border-2 border-black bg-white shadow-[2px_2px_0px_#000]">
                        <div class="h-8 w-8 rounded-[6px] border-2 border-black bg-[#FFEB3B] text-black flex items-center justify-center font-black text-xs shrink-0">
                            {{ user?.name ? user.name.slice(0, 2).toUpperCase() : 'OW' }}
                        </div>
                        <div class="truncate flex-1">
                            <span class="block text-xs font-black text-black leading-tight truncate">{{ user?.name ?? 'Andik (Owner)' }}</span>
                            <span class="block text-[10px] font-bold text-black/60 truncate">Owner (Pemilik & Kasir)</span>
                        </div>
                    </div>

                    <Link
                        href="/logout"
                        method="post"
                        as="button"
                        @click="isMobileNavOpen = false"
                        class="flex w-full items-center justify-center space-x-2 px-4 py-2.5 rounded-[8px] min-h-[44px] text-xs font-black uppercase tracking-wider text-black border-2 border-black bg-[#FF5252] shadow-[3px_3px_0px_#000] hover:bg-[#FF5252]/90 active:translate-x-0.5 active:translate-y-0.5 active:shadow-none transition-all cursor-pointer"
                    >
                        <LogOut class="h-4 w-4 shrink-0 stroke-[2.5]" />
                        <span>Keluar dari Aplikasi</span>
                    </Link>
                </div>
            </SheetContent>
        </Sheet>

        <!-- Mobile & Tablet Expandable Bottom Sheet for Cart & Checkout -->
        <Sheet v-model:open="isMobileCartOpen">
            <SheetContent side="bottom" class="p-0 max-h-[92vh] flex flex-col bg-white border-t-[3.5px] border-black">
                <SheetHeader class="p-4 border-b-2 border-black text-left bg-[#FFEB3B]">
                    <div class="flex items-center justify-between">
                        <div>
                            <SheetTitle class="text-base font-black text-black">Keranjang & Pembayaran</SheetTitle>
                            <SheetDescription class="text-xs font-bold text-black/70">
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
