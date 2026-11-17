<script setup lang="ts">
import { computed, ref, watch, onMounted } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import type { PageProps, User } from '@/types';
import { Badge } from '@/Components/ui/badge';
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
    Settings,
    Receipt,
    Wallet,
    LogOut,
    Menu,
    CheckCircle2,
    XCircle,
    AlertTriangle,
    Info,
    WifiOff,
    HelpCircle,
    X,
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
        { type: 'success', message: flash.success, icon: CheckCircle2, bg: 'bg-emerald-50 text-emerald-900 border border-emerald-200' },
        { type: 'error', message: flash.error, icon: XCircle, bg: 'bg-rose-50 text-rose-900 border border-rose-200' },
        { type: 'warning', message: flash.warning, icon: AlertTriangle, bg: 'bg-amber-50 text-amber-900 border border-amber-200' },
        { type: 'info', message: flash.info, icon: Info, bg: 'bg-sky-50 text-sky-900 border border-sky-200' },
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
        name: 'Kasir POS',
        href: '/pos',
        icon: Store,
        roles: ['owner', 'cashier'],
    },
    {
        name: 'Progres Cucian',
        href: '/production/station',
        icon: Shirt,
        roles: ['owner', 'operator'],
    },
    {
        name: 'Laporan Keuangan',
        href: '/dashboard',
        icon: LayoutDashboard,
        roles: ['owner'],
    },
    {
        name: 'Pengeluaran Toko',
        href: '/owner/expenses',
        icon: Receipt,
        roles: ['owner', 'cashier'],
    },
    {
        name: 'Buku Piutang',
        href: '/owner/receivables',
        icon: Wallet,
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
            return 'default';
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
            return 'Owner & Kasir';
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
    <div class="fixed inset-0 flex overflow-hidden bg-[#F8FAFC] font-sans text-slate-900 antialiased selection:bg-[#FF8A5B] selection:text-[#0A1033]">
        <Head :title="title ? `${title} - Andik Laundry` : 'Andik Laundry'" />

        <!-- 1. Left Desktop Sidebar (>= 1024px) - Pinned Full Viewport Height -->
        <aside
            :class="[
                'hidden lg:flex lg:flex-col bg-[#10194D] text-white border-r border-[#1E293B] shrink-0 select-none transition-all duration-300 ease-in-out z-30',
                isDesktopSidebarOpen ? 'w-64 opacity-100' : 'w-0 border-r-0 opacity-0 overflow-hidden pointer-events-none'
            ]"
        >
            <!-- Sidebar Header: Logo + Brand + Collapse button -->
            <div class="h-16 shrink-0 flex items-center justify-between px-5 bg-[#0A1033] border-b border-[#1E293B]">
                <Link href="/" class="flex items-center space-x-3 group min-w-0">
                    <div class="h-9 w-9 rounded-xl bg-gradient-to-br from-[#6577F0] to-[#4F5FE8] text-white flex items-center justify-center font-heading font-extrabold text-sm shadow-sm shrink-0 transition-transform group-hover:scale-105">
                        AL
                    </div>
                    <div class="truncate">
                        <span class="block font-heading font-extrabold text-sm text-white tracking-tight leading-tight truncate">
                            andik<span class="text-[#FF8A5B]">.</span>
                        </span>
                        <span class="block text-[10px] font-bold uppercase tracking-wider text-[#E6E9FD] leading-none truncate">
                            Laundry Management
                        </span>
                    </div>
                </Link>
                <button
                    type="button"
                    class="h-8 w-8 flex items-center justify-center rounded-lg text-slate-400 hover:text-white hover:bg-white/10 transition-all cursor-pointer shrink-0"
                    @click="closeDesktopSidebar"
                    title="Tutup menu samping"
                    aria-label="Tutup menu samping"
                >
                    <PanelLeftClose class="h-4 w-4" />
                </button>
            </div>

            <!-- Navigation Links (Internally scrollable if needed) -->
            <div class="flex-1 overflow-y-auto px-3.5 py-4 space-y-1">
                <div class="px-3 pb-2 text-[10px] font-bold uppercase tracking-wider text-slate-400">
                    Menu Utama
                </div>
                <nav class="space-y-1">
                    <Link
                        v-for="item in filteredNav"
                        :key="item.href"
                        :href="item.href"
                        :class="[
                            'flex items-center space-x-3 px-3.5 py-2.5 rounded-xl min-h-[42px] text-sm font-semibold transition-all duration-150',
                            isCurrentRoute(item.href)
                                ? 'bg-[#4F5FE8] text-white shadow-sm shadow-[#4F5FE8]/30 font-bold'
                                : 'text-slate-300 hover:bg-white/10 hover:text-white',
                        ]"
                    >
                        <component :is="item.icon" class="h-4 w-4 shrink-0" />
                        <span class="truncate">{{ item.name }}</span>
                    </Link>
                </nav>
            </div>

            <!-- Sidebar Footer: Brand info & Version -->
            <div class="p-3.5 border-t border-[#1E293B] bg-[#0A1033] shrink-0">
                <div class="rounded-xl bg-[#1A2566] p-3 text-xs text-white">
                    <div class="flex items-center justify-between">
                        <span class="font-heading font-bold text-white">Andik Laundry</span>
                        <span class="text-[10px] bg-[#4F5FE8] text-white px-1.5 py-0.5 rounded-md font-mono font-bold">v1.2</span>
                    </div>
                    <span class="block text-[11px] text-slate-300 font-medium mt-1">Sistem Kasir & Operasional</span>
                </div>
            </div>
        </aside>

        <!-- 2. Right Column: Top Header + Independently Scrollable Canvas -->
        <div class="flex-1 flex flex-col min-w-0 h-full overflow-hidden bg-[#F8FAFC]">
            <!-- Top Header Navigation -->
            <header class="h-16 shrink-0 bg-white border-b border-slate-200/80 px-4 sm:px-6 flex items-center justify-between shadow-xs z-20">
                <!-- Left: Sidebar Toggle + Title/Context -->
                <div class="flex items-center space-x-3">
                    <!-- Desktop Open Button (Visible only when desktop sidebar is closed) -->
                    <button
                        v-if="!isDesktopSidebarOpen"
                        type="button"
                        class="hidden lg:inline-flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 cursor-pointer transition-all"
                        @click="handleMenuToggle"
                        title="Buka menu samping"
                        aria-label="Buka menu samping"
                    >
                        <PanelLeftOpen class="h-4 w-4" />
                    </button>

                    <!-- Mobile Toggle Button (< lg) -->
                    <button
                        type="button"
                        class="lg:hidden inline-flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 cursor-pointer transition-all"
                        @click="handleMenuToggle"
                        title="Buka menu samping"
                        aria-label="Buka menu samping"
                    >
                        <Menu class="h-5 w-5" />
                    </button>

                    <!-- Desktop Collapsed Brand Fallback -->
                    <div v-if="!isDesktopSidebarOpen" class="hidden lg:flex items-center space-x-2.5">
                        <div class="h-8 w-8 rounded-lg bg-[#4F5FE8] text-white flex items-center justify-center font-heading font-bold text-xs shadow-xs">
                            AL
                        </div>
                        <span class="font-heading font-bold text-sm text-slate-900">Andik Laundry</span>
                    </div>

                    <!-- Mobile Brand (< lg) -->
                    <div class="flex lg:hidden items-center space-x-2">
                        <div class="h-8 w-8 rounded-lg bg-[#4F5FE8] text-white flex items-center justify-center font-heading font-bold text-xs shadow-xs">
                            AL
                        </div>
                        <span class="font-heading font-bold text-sm text-slate-900 sm:inline hidden">Andik Laundry</span>
                    </div>

                    <!-- Current Page Context on Desktop when sidebar is open -->
                    <div v-if="isDesktopSidebarOpen" class="hidden lg:flex items-center space-x-2">
                        <span class="font-heading font-bold text-sm text-slate-900 tracking-tight">{{ title ?? 'Dashboard' }}</span>
                    </div>
                </div>

                <!-- Right: WhatsApp Mode Notice, User Profile, Logout -->
                <div class="flex items-center space-x-2 sm:space-x-3">
                    <!-- WhatsApp Gateway Mode Pill -->
                    <div class="relative hidden sm:flex items-center">
                        <button
                            type="button"
                            @click="showWaTooltip = !showWaTooltip"
                            class="inline-flex items-center space-x-1.5 px-3 py-1.5 rounded-full border border-slate-200 bg-slate-50 text-slate-700 hover:bg-slate-100 text-xs font-medium transition-all cursor-pointer"
                            aria-label="Info status WhatsApp fallback"
                        >
                            <WifiOff class="h-3.5 w-3.5 text-slate-600" />
                            <span>WA Manual</span>
                            <HelpCircle class="h-3.5 w-3.5 text-slate-400" />
                        </button>

                        <div
                            v-if="showWaTooltip"
                            class="absolute right-0 top-12 z-50 w-72 rounded-2xl bg-white p-4 text-xs text-slate-700 shadow-xl border border-slate-200"
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
                                Notifikasi struk dan status cucian dapat dikirim langsung oleh kasir melalui tombol WhatsApp otomatis berformat wa.me.
                            </p>
                        </div>
                    </div>

                    <!-- User Profile & Role Badge -->
                    <div class="flex items-center space-x-2.5 bg-slate-50 border border-slate-200/80 rounded-xl px-3 py-1.5">
                        <div class="text-right hidden sm:block">
                            <span class="block text-xs font-bold text-slate-900 leading-tight">
                                {{ user?.name ?? 'Staf Toko' }}
                            </span>
                            <span class="block text-[11px] text-slate-500 font-mono leading-none mt-0.5">
                                {{ user?.phone ?? '' }}
                            </span>
                        </div>
                        <Badge :variant="roleBadgeVariant as any" class="text-[11px] font-semibold">
                            {{ roleLabel }}
                        </Badge>
                    </div>

                    <!-- Logout Button -->
                    <Link
                        href="/logout"
                        method="post"
                        as="button"
                        class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 hover:text-red-600 hover:bg-red-50 hover:border-red-200 transition-all cursor-pointer shadow-xs"
                        title="Keluar dari Aplikasi"
                        aria-label="Keluar dari Aplikasi"
                    >
                        <LogOut class="h-4 w-4" />
                    </Link>
                </div>
            </header>

            <!-- Offline WhatsApp Fallback Banner (Mobile/Tablet Alert) -->
            <div class="sm:hidden bg-[#E6E9FD] text-[#26338A] px-4 py-1.5 text-xs font-semibold flex items-center justify-between border-b border-[#26338A]/20 shrink-0">
                <div class="flex items-center space-x-1.5">
                    <WifiOff class="h-3.5 w-3.5 shrink-0" />
                    <span>Mode WhatsApp Manual Aktif</span>
                </div>
                <span class="text-[10px] bg-[#26338A] text-white px-2 py-0.5 rounded-full font-mono">wa.me</span>
            </div>

            <!-- Independently Scrollable Content Canvas -->
            <div class="flex-1 overflow-y-auto bg-[#F8FAFC] relative">
                <!-- Global Flash Toast Notification Banners -->
                <div v-if="flashMessages.length > 0" class="max-w-7xl mx-auto w-full px-4 sm:px-6 lg:px-8 pt-4 space-y-2">
                    <div
                        v-for="flash in flashMessages"
                        :key="flash.type"
                        :class="['flex items-center justify-between p-3.5 rounded-2xl shadow-sm', flash.bg]"
                        role="alert"
                    >
                        <div class="flex items-center space-x-3">
                            <component :is="flash.icon" class="h-5 w-5 shrink-0" />
                            <span class="text-sm font-semibold leading-normal">{{ flash.message }}</span>
                        </div>
                        <button
                            type="button"
                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg hover:bg-black/5 focus:outline-none"
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
            <SheetContent side="left" class="w-80 max-w-[85vw] bg-[#10194D] text-white border-r border-[#1E293B] p-0 flex flex-col h-full shadow-2xl">
                <!-- Mobile Drawer Header -->
                <SheetHeader class="p-5 bg-[#0A1033] border-b border-[#1E293B] text-left">
                    <div class="flex items-center space-x-3 pr-8">
                        <div class="h-9 w-9 rounded-xl bg-gradient-to-br from-[#6577F0] to-[#4F5FE8] text-white flex items-center justify-center font-heading font-extrabold text-sm shadow-sm shrink-0">
                            AL
                        </div>
                        <div class="truncate">
                            <SheetTitle class="text-white text-base font-heading font-bold leading-tight tracking-tight">
                                andik<span class="text-[#FF8A5B]">.</span>
                            </SheetTitle>
                            <SheetDescription class="text-slate-400 text-xs font-medium">Sistem Manajemen Toko</SheetDescription>
                        </div>
                    </div>
                </SheetHeader>

                <!-- Navigation List -->
                <div class="p-4 space-y-2 flex-1 overflow-y-auto">
                    <div class="px-2 pb-1 text-[10px] font-bold uppercase tracking-wider text-slate-400">
                        Menu Navigasi
                    </div>
                    <nav class="space-y-1">
                        <Link
                            v-for="item in filteredNav"
                            :key="item.href"
                            :href="item.href"
                            @click="isMobileMenuOpen = false"
                            :class="[
                                'flex items-center space-x-3 px-3.5 py-2.5 rounded-xl min-h-[42px] text-sm font-semibold transition-all cursor-pointer',
                                isCurrentRoute(item.href)
                                    ? 'bg-[#4F5FE8] text-white font-bold shadow-sm shadow-[#4F5FE8]/30'
                                    : 'text-slate-300 hover:bg-white/10 hover:text-white',
                            ]"
                        >
                            <component :is="item.icon" class="h-5 w-5 shrink-0" />
                            <span class="truncate">{{ item.name }}</span>
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
                            <span class="block text-[10px] text-slate-400 truncate">{{ roleLabel }}</span>
                        </div>
                    </div>

                    <Link
                        href="/logout"
                        method="post"
                        as="button"
                        @click="isMobileMenuOpen = false"
                        class="flex w-full items-center justify-center space-x-2 px-4 py-2.5 rounded-xl min-h-[40px] text-xs font-bold text-red-400 hover:bg-red-500/10 hover:text-red-300 transition-all cursor-pointer"
                    >
                        <LogOut class="h-4 w-4 shrink-0" />
                        <span>Keluar dari Aplikasi</span>
                    </Link>
                </div>
            </SheetContent>
        </Sheet>
    </div>
</template>
