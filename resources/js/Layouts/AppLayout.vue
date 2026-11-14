<script setup lang="ts">
import { computed, ref, watch } from 'vue';
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
    LayoutDashboard,
    Store,
    Shirt,
    Users,
    Settings,
    Receipt,
    Wallet,
    LogOut,
    Menu,
    CheckCircle2,
    XCircle,
    AlertTriangle,
    AlertCircle,
    Info,
    WifiOff,
    HelpCircle,
    X,
} from 'lucide-vue-next';

interface Props {
    title?: string;
}

const props = defineProps<Props>();

const page = usePage<PageProps>();
const user = computed<User | null>(() => page.props.auth?.user ?? null);
const userRole = computed(() => user.value?.role ?? 'cashier');

// Mobile drawer state
const isMobileMenuOpen = ref(false);

// WhatsApp Gateway tooltip state
const showWaTooltip = ref(false);

// Flash messages dismissal state
const dismissedFlashes = ref<Record<string, boolean>>({});

const flashMessages = computed(() => {
    const flash = page.props.flash || {};
    return [
        { type: 'success', message: flash.success, icon: CheckCircle2, bg: 'bg-emerald-50 text-emerald-900 border-emerald-300' },
        { type: 'error', message: flash.error, icon: XCircle, bg: 'bg-rose-50 text-rose-900 border-rose-300' },
        { type: 'warning', message: flash.warning, icon: AlertTriangle, bg: 'bg-amber-50 text-amber-900 border-amber-300' },
        { type: 'info', message: flash.info, icon: Info, bg: 'bg-cyan-50 text-cyan-900 border-cyan-300' },
    ].filter(item => Boolean(item.message) && !dismissedFlashes.value[item.type + item.message]);
});

function dismissFlash(type: string, msg: string) {
    dismissedFlashes.value[type + msg] = true;
}

// Reset dismissed flashes on navigation if new flash arrives
watch(() => page.props.flash, () => {
    dismissedFlashes.value = {};
}, { deep: true });

// Navigation links based on role
interface NavItem {
    name: string;
    href: string;
    icon: any;
    roles: ('owner' | 'cashier' | 'operator')[];
}

const navigationItems: NavItem[] = [
    {
        name: 'Dashboard',
        href: '/dashboard',
        icon: LayoutDashboard,
        roles: ['owner'],
    },
    {
        name: 'Point of Sale (Kasir)',
        href: '/pos',
        icon: Store,
        roles: ['owner', 'cashier'],
    },
    {
        name: 'Stasiun Produksi',
        href: '/production/station',
        icon: Shirt,
        roles: ['owner', 'operator'],
    },
    {
        name: 'Pengeluaran Toko',
        href: '/owner/expenses',
        icon: Receipt,
        roles: ['owner', 'cashier'],
    },
    {
        name: 'Rekap Kas Harian',
        href: '/owner/daily-recap',
        icon: Wallet,
        roles: ['owner', 'cashier'],
    },
    {
        name: 'Buku Piutang',
        href: '/owner/receivables',
        icon: AlertCircle,
        roles: ['owner', 'cashier'],
    },
    {
        name: 'Manajemen Pengguna',
        href: '/owner/users',
        icon: Users,
        roles: ['owner'],
    },
    {
        name: 'Pengaturan Toko',
        href: '/owner/settings',
        icon: Settings,
        roles: ['owner'],
    },
];

const filteredNav = computed(() => {
    return navigationItems.filter(item => item.roles.includes(userRole.value as any));
});

function isCurrentRoute(href: string): boolean {
    const currentUrl = page.url.split('?')[0];
    if (href === '/dashboard') {
        return currentUrl === '/dashboard';
    }
    return currentUrl.startsWith(href);
}

const roleBadgeVariant = computed(() => {
    switch (userRole.value) {
        case 'owner':
            return 'teal';
        case 'cashier':
            return 'cyan';
        case 'operator':
            return 'success';
        default:
            return 'secondary';
    }
});

const roleLabel = computed(() => {
    switch (userRole.value) {
        case 'owner':
            return 'Pemilik & Kasir (Keluarga)';
        case 'cashier':
            return 'Kasir';
        case 'operator':
            return 'Operator';
        default:
            return 'Staf';
    }
});
</script>

<template>
    <div class="min-h-screen bg-slate-100 flex flex-col font-sans text-slate-900 antialiased">
        <Head :title="title ? `${title} - Andik Laundry` : 'Andik Laundry'" />

        <!-- Top Header Navigation -->
        <header class="sticky top-0 z-40 bg-white border-b border-slate-200 shadow-xs">
            <div class="px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
                <!-- Left: Hamburger (Mobile) + Store Logo & Title -->
                <div class="flex items-center space-x-3 sm:space-x-4">
                    <button
                        type="button"
                        class="lg:hidden inline-flex min-h-[44px] min-w-[44px] items-center justify-center rounded-lg p-2 text-slate-600 hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-teal-500"
                        @click="isMobileMenuOpen = true"
                        aria-label="Buka menu navigasi"
                    >
                        <Menu class="h-6 w-6" />
                    </button>

                    <Link href="/" class="flex items-center space-x-3 group">
                        <div class="h-10 w-10 rounded-lg bg-teal-600 flex items-center justify-center text-white font-bold text-lg shadow-sm transition-transform group-hover:scale-105">
                            AL
                        </div>
                        <div class="hidden sm:block">
                            <span class="block text-base font-bold text-slate-900 leading-tight">Andik Laundry</span>
                            <span class="block text-xs font-medium text-slate-500 leading-none">Sistem Manajemen Laundry</span>
                        </div>
                    </Link>
                </div>

                <!-- Right: Staff Pill, WhatsApp Mode Notice, Logout -->
                <div class="flex items-center space-x-2 sm:space-x-4">
                    <!-- WhatsApp Gateway Mode Pill -->
                    <div class="relative hidden md:flex items-center">
                        <button
                            type="button"
                            @click="showWaTooltip = !showWaTooltip"
                            class="inline-flex min-h-[44px] items-center space-x-1.5 px-3 py-1.5 rounded-lg border border-amber-200 bg-amber-50 text-amber-900 text-xs font-semibold hover:bg-amber-100 focus:outline-none focus:ring-2 focus:ring-amber-500"
                            aria-label="Info status WhatsApp fallback"
                        >
                            <WifiOff class="h-4 w-4 text-amber-700" />
                            <span>WA Fallback Manual</span>
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
                                Gateway otomatis WuzAPI offline. Notifikasi struk dan status cucian dapat dikirim langsung oleh kasir melalui tautan wa.me resmi.
                            </p>
                        </div>
                    </div>

                    <!-- User Profile & Role Badge -->
                    <div class="flex items-center space-x-2 bg-slate-50 border border-slate-200 rounded-lg px-3 py-1.5">
                        <div class="text-right hidden sm:block">
                            <span class="block text-xs font-bold text-slate-800 leading-tight">
                                {{ user?.name ?? 'Staf Laundry' }}
                            </span>
                            <span class="block text-[11px] text-slate-500 font-mono leading-none">
                                {{ user?.phone ?? '' }}
                            </span>
                        </div>
                        <Badge :variant="roleBadgeVariant as any" class="text-[11px] font-bold">
                            {{ roleLabel }}
                        </Badge>
                    </div>

                    <!-- Logout Button -->
                    <Link
                        href="/logout"
                        method="post"
                        as="button"
                        class="inline-flex min-h-[44px] min-w-[44px] items-center justify-center rounded-lg border border-slate-200 p-2 text-slate-600 hover:bg-rose-50 hover:text-rose-600 hover:border-rose-200 focus:outline-none focus:ring-2 focus:ring-rose-500 transition-colors"
                        title="Keluar dari Aplikasi"
                        aria-label="Keluar dari Aplikasi"
                    >
                        <LogOut class="h-5 w-5" />
                    </Link>
                </div>
            </div>
        </header>

        <!-- Offline WhatsApp Fallback Banner (Mobile/Tablet Alert) -->
        <div class="md:hidden bg-amber-500 text-slate-950 px-4 py-2 text-xs font-semibold flex items-center justify-between border-b border-amber-600">
            <div class="flex items-center space-x-2">
                <WifiOff class="h-4 w-4 shrink-0 text-slate-950" />
                <span>Mode Offline: Fallback Manual WA Aktif</span>
            </div>
            <span class="text-[11px] bg-slate-950 text-white px-2 py-0.5 rounded-full font-mono">wa.me</span>
        </div>

        <!-- Global Flash Toast Notification Banners -->
        <div v-if="flashMessages.length > 0" class="max-w-7xl mx-auto w-full px-4 sm:px-6 lg:px-8 pt-4 space-y-2">
            <div
                v-for="flash in flashMessages"
                :key="flash.type"
                :class="['flex items-center justify-between p-3.5 rounded-lg border shadow-xs', flash.bg]"
                role="alert"
            >
                <div class="flex items-center space-x-3">
                    <component :is="flash.icon" class="h-5 w-5 shrink-0" />
                    <span class="text-sm font-semibold leading-normal">{{ flash.message }}</span>
                </div>
                <button
                    type="button"
                    class="inline-flex min-h-[44px] min-w-[44px] items-center justify-center rounded-md p-1.5 opacity-80 hover:opacity-100 hover:bg-black/5 focus:outline-none"
                    @click="dismissFlash(flash.type, flash.message as string)"
                    aria-label="Tutup pemberitahuan"
                >
                    <X class="h-4 w-4" />
                </button>
            </div>
        </div>

        <div class="flex-1 flex w-full">
            <!-- Desktop Sidebar Navigation Rail (>= 1024px) -->
            <aside class="hidden lg:flex lg:flex-col w-64 bg-slate-900 border-r border-slate-800 shrink-0 select-none">
                <div class="p-4 space-y-1">
                    <p class="px-3 py-2 text-[11px] font-bold uppercase tracking-wider text-slate-400">
                        Menu Utama
                    </p>

                    <nav class="space-y-1">
                        <Link
                            v-for="item in filteredNav"
                            :key="item.href"
                            :href="item.href"
                            :class="[
                                'flex items-center space-x-3 px-3 py-2.5 rounded-lg min-h-[44px] text-sm font-medium transition-all duration-150',
                                isCurrentRoute(item.href)
                                    ? 'bg-teal-600 text-white font-semibold shadow-sm'
                                    : 'text-slate-300 hover:bg-slate-800 hover:text-white',
                            ]"
                        >
                            <component :is="item.icon" class="h-5 w-5 shrink-0" />
                            <span>{{ item.name }}</span>
                        </Link>
                    </nav>
                </div>

                <div class="mt-auto p-4 border-t border-slate-800">
                    <div class="rounded-lg bg-slate-800/80 p-3 text-xs text-slate-400">
                        <span class="block font-semibold text-slate-200">Andik Laundry v1.0</span>
                        <span class="block text-[11px] text-slate-500 mt-0.5">Single-Store Foundation</span>
                    </div>
                </div>
            </aside>

            <!-- Mobile Sliding Navigation Drawer (< 1024px) -->
            <Sheet v-model:open="isMobileMenuOpen">
                <SheetContent side="left" class="w-72 bg-slate-900 text-white border-slate-800 p-0 flex flex-col">
                    <SheetHeader class="p-5 border-b border-slate-800 text-left">
                        <div class="flex items-center space-x-3">
                            <div class="h-9 w-9 rounded-lg bg-teal-600 flex items-center justify-center text-white font-bold text-base shadow-sm">
                                AL
                            </div>
                            <div>
                                <SheetTitle class="text-white text-base font-bold leading-tight">Andik Laundry</SheetTitle>
                                <SheetDescription class="text-slate-400 text-xs">Menu Navigasi</SheetDescription>
                            </div>
                        </div>
                    </SheetHeader>

                    <div class="p-4 space-y-1 flex-1 overflow-y-auto">
                        <nav class="space-y-1">
                            <Link
                                v-for="item in filteredNav"
                                :key="item.href"
                                :href="item.href"
                                @click="isMobileMenuOpen = false"
                                :class="[
                                    'flex items-center space-x-3 px-3 py-3 rounded-lg min-h-[44px] text-sm font-medium transition-colors',
                                    isCurrentRoute(item.href)
                                        ? 'bg-teal-600 text-white font-semibold'
                                        : 'text-slate-300 hover:bg-slate-800 hover:text-white',
                                ]"
                            >
                                <component :is="item.icon" class="h-5 w-5 shrink-0" />
                                <span>{{ item.name }}</span>
                            </Link>
                        </nav>
                    </div>

                    <div class="p-4 border-t border-slate-800">
                        <Link
                            href="/logout"
                            method="post"
                            as="button"
                            @click="isMobileMenuOpen = false"
                            class="flex w-full items-center space-x-3 px-3 py-3 rounded-lg min-h-[44px] text-sm font-medium text-rose-400 hover:bg-rose-950/40 hover:text-rose-300 transition-colors"
                        >
                            <LogOut class="h-5 w-5 shrink-0" />
                            <span>Keluar Aplikasi</span>
                        </Link>
                    </div>
                </SheetContent>
            </Sheet>

            <!-- Main Content Canvas Area -->
            <div class="flex-1 flex flex-col min-w-0">
                <main class="flex-1 p-4 sm:p-6 lg:p-8 max-w-7xl mx-auto w-full">
                    <slot />
                </main>
            </div>
        </div>
    </div>
</template>
