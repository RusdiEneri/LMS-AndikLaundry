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
    <div class="min-h-screen bg-[#FAFAFA] flex flex-col font-sans text-[#0A0A0A] antialiased select-none pb-20 lg:pb-0">
        <Head :title="`${title} - Andik Laundry POS`" />

        <!-- High-Speed POS Header -->
        <header class="relative z-40 bg-white/90 backdrop-blur-md border-b border-[#E8E8EC]">
            <div class="px-4 sm:px-6 h-14 flex items-center justify-between">
                <!-- Left: Home / AL Brand & Store Title + Quick Navigation -->
                <div class="flex items-center space-x-3">
                    <Link
                        href="/dashboard"
                        class="inline-flex min-h-[38px] items-center space-x-1.5 px-3 py-1.5 rounded-[6px] border border-[#E8E8EC] bg-white text-[#6B6B6B] hover:bg-[#FAFAFA] hover:text-[#0A0A0A] hover:-translate-y-px transition-all focus:outline-none focus:ring-3 focus:ring-[#6366F1]/12 text-xs font-semibold"
                        title="Dashboard & Laporan Usaha"
                        aria-label="Ke Dashboard"
                    >
                        <LayoutDashboard class="h-4 w-4 text-[#6366F1]" />
                        <span class="hidden sm:inline">Dashboard</span>
                    </Link>

                    <Link
                        href="/production/station"
                        class="inline-flex min-h-[38px] items-center space-x-1.5 px-3 py-1.5 rounded-[6px] border border-[#E8E8EC] bg-white text-[#6B6B6B] hover:bg-[#FAFAFA] hover:text-[#0A0A0A] hover:-translate-y-px transition-all focus:outline-none focus:ring-3 focus:ring-[#6366F1]/12 text-xs font-semibold"
                        title="Antrean & Status Cucian"
                        aria-label="Ke Antrean Cucian"
                    >
                        <Shirt class="h-4 w-4 text-[#6366F1]" />
                        <span class="hidden sm:inline">Antrean Cuci</span>
                    </Link>

                    <Link
                        href="/owner/expenses"
                        class="inline-flex min-h-[38px] items-center space-x-1.5 px-3 py-1.5 rounded-[6px] border border-[#E8E8EC] bg-white text-[#6B6B6B] hover:bg-[#FAFAFA] hover:text-[#0A0A0A] hover:-translate-y-px transition-all focus:outline-none focus:ring-3 focus:ring-[#6366F1]/12 text-xs font-semibold"
                        title="Catat Pengeluaran Toko"
                        aria-label="Ke Pengeluaran Toko"
                    >
                        <Receipt class="h-4 w-4 text-[#EF4444]" />
                        <span class="hidden lg:inline">Biaya Toko</span>
                    </Link>

                    <Link
                        href="/owner/settings"
                        class="inline-flex min-h-[38px] items-center space-x-1.5 px-3 py-1.5 rounded-[6px] border border-[#E8E8EC] bg-white text-[#6B6B6B] hover:bg-[#FAFAFA] hover:text-[#0A0A0A] hover:-translate-y-px transition-all focus:outline-none focus:ring-3 focus:ring-[#6366F1]/12 text-xs font-semibold"
                        title="Pengaturan Toko & WhatsApp"
                        aria-label="Ke Pengaturan Toko"
                    >
                        <Settings class="h-4 w-4 text-[#6366F1]" />
                        <span class="hidden lg:inline">Pengaturan</span>
                    </Link>

                    <div class="flex items-center space-x-2.5 pl-2 border-l border-[#E8E8EC]">
                        <div class="h-8 w-8 rounded-[6px] bg-[#6366F1] flex items-center justify-center text-white font-bold text-sm shadow-xs">
                            AL
                        </div>
                        <div>
                            <div class="flex items-center space-x-2">
                                <span class="font-bold text-sm text-[#0A0A0A] leading-tight">{{ storeName }}</span>
                                <Badge variant="teal" class="text-[10px] uppercase font-bold py-0 px-2 rounded-[4px]">Owner (Pemilik & Kasir)</Badge>
                            </div>
                            <span class="text-xs text-[#6B6B6B] leading-none">Usaha Rumahan Mandiri</span>
                        </div>
                    </div>
                </div>

                <!-- Center: Operational Status & Realtime Clock (Desktop) -->
                <div class="hidden md:flex items-center space-x-4">
                    <div class="flex items-center space-x-2 px-3 py-1 rounded-full bg-[#10B981]/10 border border-[#10B981]/20 text-[#10B981] text-xs font-semibold">
                        <span class="h-2 w-2 rounded-full bg-[#10B981] animate-pulse"></span>
                        <span>Toko Buka • Kasir: {{ user?.name ?? 'Pemilik' }}</span>
                    </div>

                    <div class="flex items-center space-x-1.5 text-xs text-[#6B6B6B] font-mono bg-[#FAFAFA] px-3 py-1.5 rounded-[6px] border border-[#E8E8EC]">
                        <Clock class="h-4 w-4 text-[#6B6B6B]" />
                        <span>{{ currentTime }}</span>
                    </div>
                </div>

                <!-- Right: WhatsApp Fallback Status & Hotkeys Badge -->
                <div class="flex items-center space-x-2 sm:space-x-3">
                    <div class="relative hidden sm:flex items-center">
                        <button
                            type="button"
                            @click="showWaTooltip = !showWaTooltip"
                            class="inline-flex min-h-[44px] items-center space-x-1.5 px-3 py-1.5 rounded-full border border-amber-200/80 bg-amber-50/80 text-amber-900 text-xs font-semibold hover:bg-amber-100 focus:outline-none focus:ring-3 focus:ring-[#6366F1]/12"
                        >
                            <WifiOff class="h-4 w-4 text-amber-700" />
                            <span class="hidden md:inline">WA Fallback Manual</span>
                            <HelpCircle class="h-3.5 w-3.5 text-amber-600" />
                        </button>

                        <div
                            v-if="showWaTooltip"
                            class="absolute right-0 top-12 z-50 w-72 rounded-[8px] bg-white p-3 text-xs text-[#0A0A0A] shadow-[0_8px_30px_rgba(0,0,0,0.08)] border border-[#E8E8EC]"
                        >
                            <div class="flex justify-between items-start mb-1">
                                <span class="font-bold text-[#F59E0B]">Mode Fallback Aktif</span>
                                <button
                                    type="button"
                                    class="text-[#6B6B6B] hover:text-[#0A0A0A] min-h-[44px] min-w-[44px] -m-2 flex items-center justify-center"
                                    @click="showWaTooltip = false"
                                >
                                    <X class="h-4 w-4" />
                                </button>
                            </div>
                            <p class="text-[#6B6B6B] leading-relaxed">
                                Gateway WuzAPI offline. Notifikasi struk dapat dikirim manual via tombol wa.me yang otomatis mengisi nomor customer & format struk.
                            </p>
                        </div>
                    </div>

                    <div class="hidden lg:flex items-center space-x-1.5 text-xs text-[#6B6B6B]">
                        <kbd class="px-2 py-1 bg-[#FAFAFA] text-[#6B6B6B] font-mono rounded-[4px] border border-[#E8E8EC] text-[11px] font-bold">F1 Cari</kbd>
                        <kbd class="px-2 py-1 bg-[#FAFAFA] text-[#6B6B6B] font-mono rounded-[4px] border border-[#E8E8EC] text-[11px] font-bold">F8 Bayar</kbd>
                    </div>
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
                <div class="hidden lg:flex lg:col-span-5 flex-col h-full bg-white rounded-[12px] border border-[#E8E8EC] overflow-hidden">
                    <slot name="cart" />
                </div>
            </div>
        </div>

        <!-- Mobile & Tablet (< 1024px) Sticky Bottom Bar (56px) -->
        <div class="lg:hidden fixed bottom-0 inset-x-0 z-40 bg-white/90 backdrop-blur-md border-t border-[#E8E8EC] px-4 h-14 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <div class="relative flex items-center justify-center h-10 w-10 rounded-[6px] bg-[#6366F1]/10 text-[#6366F1] border border-[#6366F1]/20">
                    <ShoppingBag class="h-5 w-5" />
                    <span
                        v-if="cartCount > 0"
                        class="absolute -top-1.5 -right-1.5 flex h-5 min-w-[20px] items-center justify-center rounded-full bg-[#6366F1] px-1 text-[11px] font-bold text-white shadow-xs"
                    >
                        {{ cartCount }}
                    </span>
                </div>
                <div>
                    <span class="block text-[11px] text-[#6B6B6B] uppercase tracking-wider font-semibold leading-none">Total Tagihan</span>
                    <span class="block text-base font-bold font-mono text-[#0A0A0A] leading-tight">{{ cartTotalFormatted }}</span>
                </div>
            </div>

            <Button
                type="button"
                variant="default"
                class="min-h-[40px] px-5 font-bold text-sm bg-[#6366F1] hover:bg-[#4F46E5] text-white active:scale-95"
                @click="isMobileCartOpen = true"
            >
                Lihat Keranjang
            </Button>
        </div>

        <!-- Mobile & Tablet Expandable Bottom Sheet for Cart & Checkout -->
        <Sheet v-model:open="isMobileCartOpen">
            <SheetContent side="bottom" class="p-0 max-h-[92vh] flex flex-col bg-white">
                <SheetHeader class="p-4 border-b border-[#E8E8EC] text-left bg-[#FAFAFA]">
                    <div class="flex items-center justify-between">
                        <div>
                            <SheetTitle class="text-base font-bold text-[#0A0A0A]">Keranjang & Pembayaran</SheetTitle>
                            <SheetDescription class="text-xs text-[#6B6B6B]">
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
