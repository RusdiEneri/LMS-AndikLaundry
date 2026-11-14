<script setup lang="ts">
import { computed, ref, watch, onMounted } from 'vue';
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
    ChevronLeft,
    PanelLeftClose,
    PanelLeftOpen,
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

// Desktop sidebar collapse state
const isDesktopSidebarOpen = ref(true);

onMounted(() => {
    const saved = localStorage.getItem('lms_desktop_sidebar_open');
    if (saved !== null) {
        isDesktopSidebarOpen.value = saved === 'true';
    }
});

function handleMenuToggle() {
    if (window.innerWidth < 1024) {
        isMobileMenuOpen.value = true;
    } else {
        isDesktopSidebarOpen.value = !isDesktopSidebarOpen.value;
        localStorage.setItem('lms_desktop_sidebar_open', String(isDesktopSidebarOpen.value));
    }
}

function closeDesktopSidebar() {
    isDesktopSidebarOpen.value = false;
    localStorage.setItem('lms_desktop_sidebar_open', 'false');
}

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
    <div class="h-screen w-full flex overflow-hidden bg-[#FAFAFA] font-sans text-[#0A0A0A] antialiased">
        <Head :title="title ? `${title} - Andik Laundry` : 'Andik Laundry'" />

        <!-- 1. Left Desktop Sidebar (>= 1024px) - Pinned Full Viewport Height -->
        <aside
            :class="[
                'hidden lg:flex lg:flex-col bg-white border-r border-[#E8E8EC] shrink-0 select-none transition-all duration-300 ease-in-out z-30',
                isDesktopSidebarOpen ? 'w-64 opacity-100' : 'w-0 border-r-0 opacity-0 overflow-hidden pointer-events-none'
            ]"
        >
            <!-- Sidebar Header: Logo + Brand + Collapse button -->
            <div class="h-14 shrink-0 flex items-center justify-between px-4 border-b border-[#E8E8EC]">
                <Link href="/" class="flex items-center space-x-3 group min-w-0">
                    <div class="h-8 w-8 rounded-[6px] bg-[#6366F1] flex items-center justify-center text-white font-bold text-sm shadow-xs shrink-0 transition-transform group-hover:scale-105">
                        AL
                    </div>
                    <div class="truncate">
                        <span class="block text-sm font-bold text-[#0A0A0A] tracking-tight leading-tight truncate">Andik Laundry</span>
                        <span class="block text-[11px] font-medium text-[#6B6B6B] leading-none truncate">Sistem Manajemen</span>
                    </div>
                </Link>
                <button
                    type="button"
                    class="min-h-[36px] min-w-[36px] flex items-center justify-center rounded-[6px] text-[#6B6B6B] hover:text-[#0A0A0A] hover:bg-[#FAFAFA] focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-[#6366F1]/12 transition-colors cursor-pointer shrink-0"
                    @click="closeDesktopSidebar"
                    title="Tutup menu navigasi"
                    aria-label="Tutup menu navigasi"
                >
                    <PanelLeftClose class="h-4 w-4" />
                </button>
            </div>

            <!-- Navigation Links (Internally scrollable if needed) -->
            <div class="flex-1 overflow-y-auto px-3 py-4 space-y-1">
                <div class="px-3 pb-2 text-[10px] font-bold uppercase tracking-wider text-[#9C9C9C]">
                    Menu Utama
                </div>
                <nav class="space-y-1">
                    <Link
                        v-for="item in filteredNav"
                        :key="item.href"
                        :href="item.href"
                        :class="[
                            'flex items-center space-x-3 px-3 py-2.5 rounded-[6px] min-h-[44px] text-sm font-medium transition-colors',
                            isCurrentRoute(item.href)
                                ? 'bg-[#6366F1] text-white font-medium hover:bg-[#4F46E5]'
                                : 'text-[#6B6B6B] hover:bg-[#FAFAFA] hover:text-[#0A0A0A]',
                        ]"
                    >
                        <component :is="item.icon" class="h-4 w-4 shrink-0" />
                        <span class="truncate">{{ item.name }}</span>
                    </Link>
                </nav>
            </div>

            <!-- Sidebar Footer: Brand info & Version -->
            <div class="p-3 border-t border-[#E8E8EC] shrink-0">
                <div class="rounded-[6px] bg-[#FAFAFA] border border-[#E8E8EC] p-2.5 text-xs text-[#6B6B6B]">
                    <div class="flex items-center justify-between">
                        <span class="font-semibold text-[#0A0A0A]">Andik Laundry</span>
                        <span class="text-[10px] bg-[#6366F1]/10 text-[#6366F1] border border-[#6366F1]/20 px-1.5 py-0.5 rounded-[4px] font-mono">v1.0</span>
                    </div>
                    <span class="block text-[11px] text-[#6B6B6B] mt-0.5">Sistem Kasir & Operasional</span>
                </div>
            </div>
        </aside>

        <!-- 2. Right Column: Top Header + Independently Scrollable Canvas -->
        <div class="flex-1 flex flex-col min-w-0 h-full overflow-hidden bg-[#FAFAFA]">
            <!-- Top Header Navigation -->
            <header class="h-14 shrink-0 bg-white/90 backdrop-blur-md border-b border-[#E8E8EC] px-4 sm:px-6 flex items-center justify-between z-20">
                <!-- Left: Sidebar Toggle + Title/Context -->
                <div class="flex items-center space-x-3">
                    <!-- Desktop Open Button (Visible only when desktop sidebar is closed) -->
                    <button
                        v-if="!isDesktopSidebarOpen"
                        type="button"
                        class="hidden lg:inline-flex min-h-[44px] min-w-[44px] items-center justify-center rounded-[6px] p-2 text-[#6B6B6B] hover:bg-[#FAFAFA] hover:text-[#0A0A0A] focus:outline-none focus:ring-3 focus:ring-[#6366F1]/12 cursor-pointer transition-colors"
                        @click="handleMenuToggle"
                        title="Buka menu samping"
                        aria-label="Buka menu navigasi"
                    >
                        <PanelLeftOpen class="h-5 w-5 text-[#6B6B6B]" />
                    </button>

                    <!-- Mobile Toggle Button (< lg) -->
                    <button
                        type="button"
                        class="lg:hidden inline-flex min-h-[44px] min-w-[44px] items-center justify-center rounded-[6px] p-2 text-[#6B6B6B] hover:bg-[#FAFAFA] hover:text-[#0A0A0A] focus:outline-none focus:ring-3 focus:ring-[#6366F1]/12 cursor-pointer transition-colors"
                        @click="handleMenuToggle"
                        title="Buka menu samping"
                        aria-label="Buka menu navigasi"
                    >
                        <Menu class="h-5 w-5 text-[#6B6B6B]" />
                    </button>

                    <!-- Desktop Collapsed Brand Fallback -->
                    <div v-if="!isDesktopSidebarOpen" class="hidden lg:flex items-center space-x-2.5">
                        <div class="h-8 w-8 rounded-[6px] bg-[#6366F1] flex items-center justify-center text-white font-bold text-sm shadow-xs">
                            AL
                        </div>
                        <span class="text-sm font-bold text-[#0A0A0A]">Andik Laundry</span>
                    </div>

                    <!-- Mobile Brand (< lg) -->
                    <div class="flex lg:hidden items-center space-x-2">
                        <div class="h-8 w-8 rounded-[6px] bg-[#6366F1] flex items-center justify-center text-white font-bold text-sm shadow-xs">
                            AL
                        </div>
                        <span class="text-sm font-bold text-[#0A0A0A] sm:inline hidden">Andik Laundry</span>
                    </div>

                    <!-- Current Page Context on Desktop when sidebar is open -->
                    <div v-if="isDesktopSidebarOpen" class="hidden lg:flex items-center space-x-2">
                        <span class="text-sm font-semibold text-[#0A0A0A]">{{ title ?? 'Dashboard' }}</span>
                    </div>
                </div>

                <!-- Right: WhatsApp Mode Notice, User Profile, Logout -->
                <div class="flex items-center space-x-2 sm:space-x-3">
                    <!-- WhatsApp Gateway Mode Pill -->
                    <div class="relative hidden sm:flex items-center">
                        <button
                            type="button"
                            @click="showWaTooltip = !showWaTooltip"
                            class="inline-flex min-h-[44px] items-center space-x-1.5 px-3 py-1.5 rounded-full border border-amber-200/80 bg-amber-50/80 text-amber-900 text-xs font-semibold hover:bg-amber-100 focus:outline-none focus:ring-3 focus:ring-[#6366F1]/12 transition-colors"
                            aria-label="Info status WhatsApp fallback"
                        >
                            <WifiOff class="h-4 w-4 text-amber-700" />
                            <span>WA Fallback Manual</span>
                            <HelpCircle class="h-3.5 w-3.5 text-amber-600" />
                        </button>

                        <div
                            v-if="showWaTooltip"
                            class="absolute right-0 top-12 z-50 w-72 rounded-[8px] bg-white p-3.5 text-xs text-[#0A0A0A] shadow-[0_8px_30px_rgba(0,0,0,0.08)] border border-[#E8E8EC]"
                        >
                            <div class="flex justify-between items-start mb-1">
                                <span class="font-bold text-[#F59E0B]">Mode Fallback Aktif</span>
                                <button
                                    type="button"
                                    class="text-[#6B6B6B] hover:text-[#0A0A0A] min-h-[36px] min-w-[36px] -m-1.5 flex items-center justify-center"
                                    @click="showWaTooltip = false"
                                >
                                    <X class="h-4 w-4" />
                                </button>
                            </div>
                            <p class="text-[#6B6B6B] leading-relaxed">
                                Gateway otomatis WhatsApp offline. Notifikasi struk dan status cucian dapat dikirim langsung oleh kasir melalui tautan resmi wa.me.
                            </p>
                        </div>
                    </div>

                    <!-- User Profile & Role Badge -->
                    <div class="flex items-center space-x-2.5 bg-white border border-[#E8E8EC] rounded-[8px] px-3 py-1.5">
                        <div class="text-right hidden sm:block">
                            <span class="block text-xs font-bold text-[#0A0A0A] leading-tight">
                                {{ user?.name ?? 'Staf Laundry' }}
                            </span>
                            <span class="block text-[11px] text-[#6B6B6B] tabular-nums leading-none">
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
                        class="inline-flex min-h-[44px] min-w-[44px] items-center justify-center rounded-[6px] border border-[#E8E8EC] p-2 text-[#6B6B6B] hover:bg-[#EF4444]/10 hover:text-[#EF4444] hover:border-[#EF4444]/30 focus:outline-none focus:ring-3 focus:ring-[#6366F1]/12 transition-colors"
                        title="Keluar dari Aplikasi"
                        aria-label="Keluar dari Aplikasi"
                    >
                        <LogOut class="h-4 w-4" />
                    </Link>
                </div>
            </header>

            <!-- Offline WhatsApp Fallback Banner (Mobile/Tablet Alert) -->
            <div class="sm:hidden bg-[#F59E0B] text-slate-950 px-4 py-1.5 text-xs font-semibold flex items-center justify-between border-b border-[#F59E0B]/50 shrink-0">
                <div class="flex items-center space-x-1.5">
                    <WifiOff class="h-3.5 w-3.5 shrink-0 text-slate-950" />
                    <span>Mode Offline: Fallback Manual WA Aktif</span>
                </div>
                <span class="text-[10px] bg-slate-950 text-white px-2 py-0.5 rounded-full font-mono">wa.me</span>
            </div>

            <!-- Independently Scrollable Content Canvas -->
            <div class="flex-1 overflow-y-auto">
                <!-- Global Flash Toast Notification Banners -->
                <div v-if="flashMessages.length > 0" class="max-w-7xl mx-auto w-full px-4 sm:px-6 lg:px-8 pt-4 space-y-2">
                    <div
                        v-for="flash in flashMessages"
                        :key="flash.type"
                        :class="['flex items-center justify-between p-3.5 rounded-[8px] border shadow-xs', flash.bg]"
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

                <!-- Main Content Slot -->
                <main class="p-4 sm:p-6 lg:p-8 max-w-7xl mx-auto w-full">
                    <slot />
                </main>
            </div>
        </div>

        <!-- 3. Mobile Sliding Navigation Drawer (< 1024px) -->
        <Sheet v-model:open="isMobileMenuOpen">
            <SheetContent side="left" class="w-72 bg-white text-[#0A0A0A] border-r border-[#E8E8EC] p-0 flex flex-col">
                <SheetHeader class="p-5 border-b border-[#E8E8EC] text-left">
                    <div class="flex items-center space-x-3">
                        <div class="h-8 w-8 rounded-[6px] bg-[#6366F1] flex items-center justify-center text-white font-bold text-sm shadow-xs">
                            AL
                        </div>
                        <div>
                            <SheetTitle class="text-[#0A0A0A] text-base font-bold leading-tight">Andik Laundry</SheetTitle>
                            <SheetDescription class="text-[#6B6B6B] text-xs">Menu Navigasi</SheetDescription>
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
                                'flex items-center space-x-3 px-3 py-3 rounded-[6px] min-h-[44px] text-sm font-medium transition-colors',
                                isCurrentRoute(item.href)
                                    ? 'bg-[#6366F1] text-white font-medium hover:bg-[#4F46E5]'
                                    : 'text-[#6B6B6B] hover:bg-[#FAFAFA] hover:text-[#0A0A0A]',
                            ]"
                        >
                            <component :is="item.icon" class="h-5 w-5 shrink-0" />
                            <span>{{ item.name }}</span>
                        </Link>
                    </nav>
                </div>

                <div class="p-4 border-t border-[#E8E8EC]">
                    <Link
                        href="/logout"
                        method="post"
                        as="button"
                        @click="isMobileMenuOpen = false"
                        class="flex w-full items-center space-x-3 px-3 py-3 rounded-[6px] min-h-[44px] text-sm font-medium text-[#EF4444] hover:bg-[#EF4444]/10 transition-colors"
                    >
                        <LogOut class="h-5 w-5 shrink-0" />
                        <span>Keluar Aplikasi</span>
                    </Link>
                </div>
            </SheetContent>
        </Sheet>
    </div>
</template>
