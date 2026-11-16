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
        name: 'Dashboard & Laporan',
        href: '/dashboard',
        icon: LayoutDashboard,
        roles: ['owner'],
    },
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
        name: 'Pengeluaran Toko',
        href: '/owner/expenses',
        icon: Receipt,
        roles: ['owner', 'cashier'],
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
            return 'Owner (Pemilik & Kasir)';
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
    <div class="fixed inset-0 flex overflow-hidden bg-[#FFFDF5] font-sans text-black antialiased">
        <Head :title="title ? `${title} - Andik Laundry` : 'Andik Laundry'" />

        <!-- 1. Left Desktop Sidebar (>= 1024px) - Pinned Full Viewport Height -->
        <aside
            :class="[
                'hidden lg:flex lg:flex-col bg-[#FFFDF5] border-r-[3.5px] border-black shrink-0 select-none transition-all duration-300 ease-in-out z-30',
                isDesktopSidebarOpen ? 'w-64 opacity-100' : 'w-0 border-r-0 opacity-0 overflow-hidden pointer-events-none'
            ]"
        >
            <!-- Sidebar Header: Logo + Brand + Collapse button -->
            <div class="h-14 shrink-0 flex items-center justify-between px-4 bg-[#FFEB3B] border-b-[3px] border-black">
                <Link href="/" class="flex items-center space-x-3 group min-w-0">
                    <div class="h-8 w-8 rounded-[6px] bg-black border-2 border-black text-[#FFEB3B] flex items-center justify-center font-black text-sm shadow-[2px_2px_0px_#000] shrink-0 transition-transform group-hover:scale-105">
                        AL
                    </div>
                    <div class="truncate">
                        <span class="block text-sm font-black text-black tracking-tight leading-tight truncate">Andik Laundry</span>
                        <span class="block text-[11px] font-bold text-gray-800 leading-none truncate">Sistem Manajemen</span>
                    </div>
                </Link>
                <button
                    type="button"
                    class="min-h-[36px] min-w-[36px] flex items-center justify-center rounded-[6px] border-2 border-black bg-white shadow-[2px_2px_0px_#000] text-black hover:bg-[#FF5252] hover:text-white transition-all cursor-pointer shrink-0"
                    @click="closeDesktopSidebar"
                    title="Tutup menu navigasi"
                    aria-label="Tutup menu navigasi"
                >
                    <PanelLeftClose class="h-4 w-4" />
                </button>
            </div>

            <!-- Navigation Links (Internally scrollable if needed) -->
            <div class="flex-1 overflow-y-auto px-3 py-4 space-y-1">
                <div class="px-3 pb-2 text-[10px] font-black uppercase tracking-wider text-gray-700">
                    Menu Utama
                </div>
                <nav class="space-y-1.5">
                    <Link
                        v-for="item in filteredNav"
                        :key="item.href"
                        :href="item.href"
                        :class="[
                            'flex items-center space-x-3 px-3 py-2.5 rounded-[8px] min-h-[44px] text-sm font-black transition-all',
                            isCurrentRoute(item.href)
                                ? 'bg-[#FFEB3B] text-black border-2 border-black shadow-[3px_3px_0px_#000] hover:bg-[#FDE047]'
                                : 'text-black border-2 border-transparent hover:bg-[#FEF08A] hover:border-black hover:shadow-[2px_2px_0px_#000]',
                        ]"
                    >
                        <component :is="item.icon" class="h-4 w-4 shrink-0 stroke-[2.5]" />
                        <span class="truncate">{{ item.name }}</span>
                    </Link>
                </nav>
            </div>

            <!-- Sidebar Footer: Brand info & Version -->
            <div class="p-3 border-t-[3px] border-black bg-[#FFFDF5] shrink-0">
                <div class="rounded-[8px] bg-[#FFEB3B] border-2 border-black shadow-[3px_3px_0px_#000] p-2.5 text-xs text-black">
                    <div class="flex items-center justify-between">
                        <span class="font-black text-black">Andik Laundry</span>
                        <span class="text-[10px] bg-black text-[#FFEB3B] border border-black px-1.5 py-0.5 rounded-[4px] font-mono font-black">v1.0</span>
                    </div>
                    <span class="block text-[11px] text-gray-800 font-bold mt-0.5">Sistem Kasir & Operasional</span>
                </div>
            </div>
        </aside>

        <!-- 2. Right Column: Top Header + Independently Scrollable Canvas -->
        <div class="flex-1 flex flex-col min-w-0 h-full overflow-hidden bg-[#FFFDF5]">
            <!-- Top Header Navigation -->
            <header class="h-14 shrink-0 bg-[#FFEB3B] border-b-[3.5px] border-black px-4 sm:px-6 flex items-center justify-between z-20">
                <!-- Left: Sidebar Toggle + Title/Context -->
                <div class="flex items-center space-x-3">
                    <!-- Desktop Open Button (Visible only when desktop sidebar is closed) -->
                    <button
                        v-if="!isDesktopSidebarOpen"
                        type="button"
                        class="hidden lg:inline-flex min-h-[40px] min-w-[40px] items-center justify-center rounded-[6px] border-2 border-black bg-white shadow-[2px_2px_0px_#000] p-2 text-black hover:bg-[#FFEB3B] cursor-pointer transition-all"
                        @click="handleMenuToggle"
                        title="Buka menu samping"
                        aria-label="Buka menu navigasi"
                    >
                        <PanelLeftOpen class="h-5 w-5 text-black stroke-[2.5]" />
                    </button>

                    <!-- Mobile Toggle Button (< lg) -->
                    <button
                        type="button"
                        class="lg:hidden inline-flex min-h-[40px] min-w-[40px] items-center justify-center rounded-[6px] border-2 border-black bg-white shadow-[2px_2px_0px_#000] p-2 text-black hover:bg-[#FFEB3B] cursor-pointer transition-all"
                        @click="handleMenuToggle"
                        title="Buka menu samping"
                        aria-label="Buka menu navigasi"
                    >
                        <Menu class="h-5 w-5 text-black stroke-[2.5]" />
                    </button>

                    <!-- Desktop Collapsed Brand Fallback -->
                    <div v-if="!isDesktopSidebarOpen" class="hidden lg:flex items-center space-x-2.5">
                        <div class="h-8 w-8 rounded-[6px] bg-black text-[#FFEB3B] border-2 border-black flex items-center justify-center font-black text-sm shadow-[2px_2px_0px_#000]">
                            AL
                        </div>
                        <span class="text-sm font-black text-black">Andik Laundry</span>
                    </div>

                    <!-- Mobile Brand (< lg) -->
                    <div class="flex lg:hidden items-center space-x-2">
                        <div class="h-8 w-8 rounded-[6px] bg-black text-[#FFEB3B] border-2 border-black flex items-center justify-center font-black text-sm shadow-[2px_2px_0px_#000]">
                            AL
                        </div>
                        <span class="text-sm font-black text-black sm:inline hidden">Andik Laundry</span>
                    </div>

                    <!-- Current Page Context on Desktop when sidebar is open -->
                    <div v-if="isDesktopSidebarOpen" class="hidden lg:flex items-center space-x-2">
                        <span class="text-sm font-black text-black uppercase tracking-wide">{{ title ?? 'Dashboard' }}</span>
                    </div>
                </div>

                <!-- Right: WhatsApp Mode Notice, User Profile, Logout -->
                <div class="flex items-center space-x-2 sm:space-x-3">
                    <!-- WhatsApp Gateway Mode Pill -->
                    <div class="relative hidden sm:flex items-center">
                        <button
                            type="button"
                            @click="showWaTooltip = !showWaTooltip"
                            class="inline-flex min-h-[38px] items-center space-x-1.5 px-3 py-1 rounded-full border-2 border-black bg-[#FED7AA] text-black text-xs font-black shadow-[2px_2px_0px_#000] hover:bg-[#FDBA74] transition-all cursor-pointer"
                            aria-label="Info status WhatsApp fallback"
                        >
                            <WifiOff class="h-4 w-4 text-black stroke-[2.5]" />
                            <span>WA Fallback Manual</span>
                            <HelpCircle class="h-3.5 w-3.5 text-black" />
                        </button>

                        <div
                            v-if="showWaTooltip"
                            class="absolute right-0 top-12 z-50 w-72 rounded-[8px] bg-white p-3.5 text-xs text-black shadow-[4px_4px_0px_#000] border-[2.5px] border-black"
                        >
                            <div class="flex justify-between items-start mb-1">
                                <span class="font-black text-[#D97706] uppercase tracking-wide">Mode Fallback Aktif</span>
                                <button
                                    type="button"
                                    class="text-black hover:text-[#FF5252] min-h-[36px] min-w-[36px] -m-1.5 flex items-center justify-center font-black"
                                    @click="showWaTooltip = false"
                                >
                                    <X class="h-4 w-4 stroke-[3]" />
                                </button>
                            </div>
                            <p class="text-gray-800 font-semibold leading-relaxed">
                                Gateway otomatis WhatsApp offline. Notifikasi struk dan status cucian dapat dikirim langsung oleh kasir melalui tautan resmi wa.me.
                            </p>
                        </div>
                    </div>

                    <!-- User Profile & Role Badge -->
                    <div class="flex items-center space-x-2.5 bg-white border-2 border-black shadow-[3px_3px_0px_#000] rounded-[8px] px-3 py-1.5">
                        <div class="text-right hidden sm:block">
                            <span class="block text-xs font-black text-black leading-tight">
                                {{ user?.name ?? 'Staf Laundry' }}
                            </span>
                            <span class="block text-[11px] text-gray-700 font-mono font-bold leading-none">
                                {{ user?.phone ?? '' }}
                            </span>
                        </div>
                        <Badge :variant="roleBadgeVariant as any" class="text-[11px] font-black">
                            {{ roleLabel }}
                        </Badge>
                    </div>

                    <!-- Logout Button -->
                    <Link
                        href="/logout"
                        method="post"
                        as="button"
                        class="inline-flex min-h-[38px] min-w-[38px] items-center justify-center rounded-[8px] border-2 border-black shadow-[3px_3px_0px_#000] bg-[#FF5252] text-white hover:-translate-x-0.5 hover:-translate-y-0.5 hover:shadow-[4px_4px_0px_#000] active:translate-x-0.5 active:translate-y-0.5 active:shadow-[1px_1px_0px_#000] transition-all cursor-pointer"
                        title="Keluar dari Aplikasi"
                        aria-label="Keluar dari Aplikasi"
                    >
                        <LogOut class="h-4 w-4 stroke-[2.5]" />
                    </Link>
                </div>
            </header>

            <!-- Offline WhatsApp Fallback Banner (Mobile/Tablet Alert) -->
            <div class="sm:hidden bg-[#FFEB3B] text-black px-4 py-1.5 text-xs font-black flex items-center justify-between border-b-2 border-black shrink-0">
                <div class="flex items-center space-x-1.5">
                    <WifiOff class="h-3.5 w-3.5 shrink-0 text-black stroke-[2.5]" />
                    <span>Mode Offline: Fallback WA Aktif</span>
                </div>
                <span class="text-[10px] bg-black text-[#FFEB3B] px-2 py-0.5 rounded font-mono font-black">wa.me</span>
            </div>

            <!-- Independently Scrollable Content Canvas -->
            <div class="flex-1 overflow-y-auto bg-[#FFFDF5] relative">
                <!-- Global Flash Toast Notification Banners -->
                <div v-if="flashMessages.length > 0" class="max-w-7xl mx-auto w-full px-4 sm:px-6 lg:px-8 pt-4 space-y-2">
                    <div
                        v-for="flash in flashMessages"
                        :key="flash.type"
                        :class="['flex items-center justify-between p-3.5 rounded-[8px] border-[2.5px] border-black shadow-[4px_4px_0px_#000]', flash.bg]"
                        role="alert"
                    >
                        <div class="flex items-center space-x-3">
                            <component :is="flash.icon" class="h-5 w-5 shrink-0 stroke-[2.5]" />
                            <span class="text-sm font-black leading-normal text-black">{{ flash.message }}</span>
                        </div>
                        <button
                            type="button"
                            class="inline-flex min-h-[44px] min-w-[44px] items-center justify-center rounded-md p-1.5 hover:bg-black/10 focus:outline-none"
                            @click="dismissFlash(flash.type, flash.message as string)"
                            aria-label="Tutup pemberitahuan"
                        >
                            <X class="h-4 w-4 stroke-[3]" />
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
            <SheetContent side="left" class="w-80 max-w-[85vw] bg-[#FFFDF5] text-black border-r-[3.5px] border-black p-0 flex flex-col h-full shadow-[6px_0px_0px_#000]">
                <!-- Mobile Drawer Header -->
                <SheetHeader class="p-4 bg-[#FFEB3B] border-b-[3px] border-black text-left">
                    <div class="flex items-center space-x-3 pr-8">
                        <div class="h-9 w-9 rounded-[8px] bg-black border-2 border-black text-[#FFEB3B] flex items-center justify-center font-black text-sm shadow-[2px_2px_0px_#000] shrink-0">
                            AL
                        </div>
                        <div class="truncate">
                            <SheetTitle class="text-black text-base font-black leading-tight tracking-tight">Andik Laundry</SheetTitle>
                            <SheetDescription class="text-black/70 text-xs font-bold">Sistem Manajemen Toko</SheetDescription>
                        </div>
                    </div>
                </SheetHeader>

                <!-- Navigation List -->
                <div class="p-4 space-y-2 flex-1 overflow-y-auto">
                    <div class="px-2 pb-1 text-[10px] font-black uppercase tracking-wider text-black/70">
                        Menu Navigasi
                    </div>
                    <nav class="space-y-2">
                        <Link
                            v-for="item in filteredNav"
                            :key="item.href"
                            :href="item.href"
                            @click="isMobileMenuOpen = false"
                            :class="[
                                'flex items-center space-x-3 px-3.5 py-2.5 rounded-[8px] min-h-[44px] text-sm font-black transition-all cursor-pointer',
                                isCurrentRoute(item.href)
                                    ? 'bg-[#FFEB3B] text-black border-2 border-black shadow-[3px_3px_0px_#000]'
                                    : 'bg-white text-black border-2 border-black/40 hover:bg-[#FFF9D2] hover:border-black hover:shadow-[2px_2px_0px_#000]',
                            ]"
                        >
                            <component :is="item.icon" class="h-5 w-5 shrink-0 stroke-[2.5]" />
                            <span class="truncate">{{ item.name }}</span>
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
                            <span class="block text-[10px] font-bold text-black/60 truncate">{{ roleLabel }}</span>
                        </div>
                    </div>

                    <Link
                        href="/logout"
                        method="post"
                        as="button"
                        @click="isMobileMenuOpen = false"
                        class="flex w-full items-center justify-center space-x-2 px-4 py-2.5 rounded-[8px] min-h-[44px] text-xs font-black uppercase tracking-wider text-black border-2 border-black bg-[#FF5252] shadow-[3px_3px_0px_#000] hover:bg-[#FF5252]/90 active:translate-x-0.5 active:translate-y-0.5 active:shadow-none transition-all cursor-pointer"
                    >
                        <LogOut class="h-4 w-4 shrink-0 stroke-[2.5]" />
                        <span>Keluar dari Aplikasi</span>
                    </Link>
                </div>
            </SheetContent>
        </Sheet>
    </div>
</template>
