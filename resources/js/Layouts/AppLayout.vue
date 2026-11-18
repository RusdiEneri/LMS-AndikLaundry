<script setup lang="ts">
import { computed, ref, watch, onMounted } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import type { PageProps, User } from '@/types';
import { Badge } from '@/Components/ui/badge';
import { Alert } from '@/Components/ui/alert';
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
        { type: 'success', message: flash.success },
        { type: 'error', message: flash.error },
        { type: 'warning', message: flash.warning },
        { type: 'info', message: flash.info },
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

// DESIGN.md 6.7 — sidebar groups with overline labels
const navGroups = computed(() => {
    const operational = ['/pos', '/production/station'];
    const finance = ['/dashboard', '/owner/expenses', '/owner/receivables'];
    const settings = ['/owner/settings'];

    return [
        { label: 'Operasional', items: filteredNav.value.filter(i => operational.includes(i.href)) },
        { label: 'Keuangan', items: filteredNav.value.filter(i => finance.includes(i.href)) },
        { label: 'Pengaturan', items: filteredNav.value.filter(i => settings.includes(i.href)) },
    ].filter(group => group.items.length > 0);
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
    <div class="fixed inset-0 flex overflow-hidden bg-bg font-body text-text antialiased">
        <Head :title="title ? `${title} - Andik Laundry` : 'Andik Laundry'" />

        <!-- 1. Left Desktop Sidebar (>= 1024px) — navy-900, 240px open / 72px collapsed (DESIGN.md 5.2) -->
        <aside
            :class="[
                'hidden lg:flex lg:flex-col bg-navy-900 text-white shrink-0 select-none transition-[width] duration-300 ease-standard z-30 border-r border-navy-950',
                isDesktopSidebarOpen ? 'w-60' : 'w-[72px]',
            ]"
        >
            <!-- Sidebar Header: Logo + Brand + Collapse button -->
            <div
                class="h-16 shrink-0 flex items-center border-b border-white/10"
                :class="isDesktopSidebarOpen ? 'justify-between px-5' : 'justify-center px-2'"
            >
                <Link href="/" class="flex items-center space-x-3 group min-w-0">
                    <div class="h-9 w-9 rounded-md bg-primary-600 text-white flex items-center justify-center font-heading font-extrabold text-sm shadow-sm shrink-0 transition-transform duration-[200ms] ease-standard group-hover:scale-105">
                        AL
                    </div>
                    <div v-if="isDesktopSidebarOpen" class="truncate">
                        <span class="block font-heading font-extrabold text-sm text-white tracking-tight leading-tight truncate">
                            andik<span class="text-accent-500">.</span>
                        </span>
                        <span class="overline-label block text-text-on-dark-muted leading-none truncate">
                            Laundry Management
                        </span>
                    </div>
                </Link>
                <button
                    v-if="isDesktopSidebarOpen"
                    type="button"
                    class="h-8 w-8 flex items-center justify-center rounded-md text-text-on-dark-muted hover:text-white hover:bg-white/10 transition-all duration-[120ms] ease-standard cursor-pointer shrink-0 focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-primary-500"
                    @click="closeDesktopSidebar"
                    title="Ciutkan menu samping"
                    aria-label="Ciutkan menu samping"
                >
                    <PanelLeftClose class="h-4 w-4" />
                </button>
            </div>

            <!-- Navigation Links (Internally scrollable if needed) -->
            <div class="flex-1 overflow-y-auto py-4 space-y-5" :class="isDesktopSidebarOpen ? 'px-3' : 'px-2'">
                <div v-for="group in navGroups" :key="group.label">
                    <div
                        v-if="isDesktopSidebarOpen"
                        class="overline-label px-3 pb-2 text-text-on-dark-muted"
                    >
                        {{ group.label }}
                    </div>
                    <div v-else class="mx-2 mb-2 border-t border-white/10" aria-hidden="true" />
                    <nav class="space-y-1" :aria-label="group.label">
                        <Link
                            v-for="item in group.items"
                            :key="item.href"
                            :href="item.href"
                            :title="!isDesktopSidebarOpen ? item.name : undefined"
                            :class="[
                                'flex items-center rounded-pill min-h-[40px] text-sm font-semibold transition-all duration-[120ms] ease-standard focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-primary-500',
                                isDesktopSidebarOpen ? 'space-x-3 px-3.5 py-2.5' : 'justify-center h-10 w-10 mx-auto p-0',
                                isCurrentRoute(item.href)
                                    ? 'bg-primary-700 text-white shadow-sm'
                                    : 'text-text-on-dark-muted hover:bg-white/10 hover:text-white',
                            ]"
                        >
                            <component :is="item.icon" class="h-4 w-4 shrink-0" aria-hidden="true" />
                            <span v-if="isDesktopSidebarOpen" class="truncate">{{ item.name }}</span>
                            <span v-else class="sr-only">{{ item.name }}</span>
                        </Link>
                    </nav>
                </div>
            </div>

            <!-- Sidebar Footer: Brand info & Version -->
            <div v-if="isDesktopSidebarOpen" class="p-3.5 border-t border-white/10 shrink-0">
                <div class="rounded-md bg-navy-800 p-3 text-xs text-white">
                    <div class="flex items-center justify-between">
                        <span class="font-heading font-bold text-white">Andik Laundry</span>
                        <span class="text-[10px] bg-primary-700 text-white px-1.5 py-0.5 rounded-sm font-mono font-bold">v1.2</span>
                    </div>
                    <span class="block text-[11px] text-text-on-dark-muted font-medium mt-1">Sistem Kasir & Operasional</span>
                </div>
            </div>
        </aside>

        <!-- 2. Right Column: Top Header + Independently Scrollable Canvas -->
        <div class="flex-1 flex flex-col min-w-0 h-full overflow-hidden bg-bg">
            <!-- Top Header Navigation — white 64px (DESIGN.md 5.2) -->
            <header class="h-16 shrink-0 bg-surface border-b border-border px-4 sm:px-6 flex items-center justify-between z-20">
                <!-- Left: Sidebar Toggle + Title/Context -->
                <div class="flex items-center space-x-3">
                    <!-- Desktop Open Button (Visible only when desktop sidebar is closed) -->
                    <button
                        v-if="!isDesktopSidebarOpen"
                        type="button"
                        class="hidden lg:inline-flex h-9 w-9 items-center justify-center rounded-md border border-border bg-surface text-text hover:bg-primary-100 cursor-pointer transition-all duration-[120ms] ease-standard focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-primary-500"
                        @click="handleMenuToggle"
                        title="Buka menu samping"
                        aria-label="Buka menu samping"
                    >
                        <PanelLeftOpen class="h-4 w-4" />
                    </button>

                    <!-- Mobile Toggle Button (< lg) -->
                    <button
                        type="button"
                        class="lg:hidden inline-flex h-9 w-9 items-center justify-center rounded-md border border-border bg-surface text-text hover:bg-primary-100 cursor-pointer transition-all duration-[120ms] ease-standard focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-primary-500"
                        @click="handleMenuToggle"
                        title="Buka menu samping"
                        aria-label="Buka menu samping"
                    >
                        <Menu class="h-5 w-5" />
                    </button>

                    <!-- Desktop Collapsed Brand Fallback -->
                    <div v-if="!isDesktopSidebarOpen" class="hidden lg:flex items-center space-x-2.5">
                        <div class="h-8 w-8 rounded-md bg-navy-800 text-white flex items-center justify-center font-heading font-bold text-xs">
                            AL
                        </div>
                        <span class="font-heading font-bold text-sm text-text">Andik Laundry</span>
                    </div>

                    <!-- Mobile Brand (< lg) -->
                    <div class="flex lg:hidden items-center space-x-2">
                        <div class="h-8 w-8 rounded-md bg-navy-800 text-white flex items-center justify-center font-heading font-bold text-xs">
                            AL
                        </div>
                        <span class="font-heading font-bold text-sm text-text sm:inline hidden">Andik Laundry</span>
                    </div>

                    <!-- Current Page Context on Desktop when sidebar is open -->
                    <div v-if="isDesktopSidebarOpen" class="hidden lg:flex items-center space-x-2">
                        <span class="font-heading font-bold text-sm text-text tracking-tight">{{ title ?? 'Dashboard' }}</span>
                    </div>
                </div>

                <!-- Right: WhatsApp Mode Notice, User Profile, Logout -->
                <div class="flex items-center space-x-2 sm:space-x-3">
                    <!-- WhatsApp Gateway Mode Pill -->
                    <div class="relative hidden sm:flex items-center">
                        <button
                            type="button"
                            @click="showWaTooltip = !showWaTooltip"
                            class="inline-flex items-center space-x-1.5 px-3 py-1.5 rounded-pill border border-border bg-bg text-text-muted hover:bg-primary-100 text-xs font-medium transition-all duration-[120ms] ease-standard cursor-pointer focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-primary-500"
                            aria-label="Info status WhatsApp fallback"
                        >
                            <WifiOff class="h-3.5 w-3.5 text-text-muted" />
                            <span>WA Manual</span>
                            <HelpCircle class="h-3.5 w-3.5 text-text-muted" />
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
                                Notifikasi struk dan status cucian dapat dikirim langsung oleh kasir melalui tombol WhatsApp otomatis berformat wa.me.
                            </p>
                        </div>
                    </div>

                    <!-- User Profile & Role Badge -->
                    <div class="flex items-center space-x-2.5 bg-bg border border-border rounded-pill px-3 py-1.5">
                        <div class="text-right hidden sm:block">
                            <span class="block text-xs font-bold text-text leading-tight">
                                {{ user?.name ?? 'Staf Toko' }}
                            </span>
                            <span class="block text-[11px] text-text-muted font-mono leading-none mt-0.5">
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
                        class="inline-flex h-9 w-9 items-center justify-center rounded-md border border-border bg-surface text-text-muted hover:text-danger hover:bg-danger/10 hover:border-danger/40 transition-all duration-[120ms] ease-standard cursor-pointer focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-primary-500"
                        title="Keluar dari Aplikasi"
                        aria-label="Keluar dari Aplikasi"
                    >
                        <LogOut class="h-4 w-4" />
                    </Link>
                </div>
            </header>

            <!-- Offline WhatsApp Fallback Banner (Mobile/Tablet Alert) -->
            <div class="sm:hidden bg-primary-100 text-primary-700 px-4 py-1.5 text-xs font-semibold flex items-center justify-between border-b border-primary-100 shrink-0">
                <div class="flex items-center space-x-1.5">
                    <WifiOff class="h-3.5 w-3.5 shrink-0" />
                    <span>Mode WhatsApp Manual Aktif</span>
                </div>
                <span class="text-[10px] bg-primary-700 text-white px-2 py-0.5 rounded-pill font-mono">wa.me</span>
            </div>

            <!-- Independently Scrollable Content Canvas -->
            <div class="flex-1 overflow-y-auto bg-bg relative">
                <!-- Global Flash Toast Notification Banners -->
                <div v-if="flashMessages.length > 0" class="max-w-7xl mx-auto w-full px-4 sm:px-6 lg:px-8 pt-4 space-y-2">
                    <div v-for="flash in flashMessages" :key="flash.type" class="relative">
                        <Alert :variant="flash.type as any" class="pr-12">
                            {{ flash.message }}
                        </Alert>
                        <button
                            type="button"
                            class="absolute right-3 top-3 inline-flex h-7 w-7 items-center justify-center rounded-md text-text-muted hover:bg-black/5 hover:text-text focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-primary-500 transition-all duration-[120ms] ease-standard cursor-pointer"
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
            <SheetContent side="left" class="w-80 max-w-[85vw] bg-navy-900 text-white border-r border-navy-950 p-0 flex flex-col h-full shadow-lg">
                <!-- Mobile Drawer Header -->
                <SheetHeader class="p-5 bg-navy-950 border-b border-white/10 text-left">
                    <div class="flex items-center space-x-3 pr-8">
                        <div class="h-9 w-9 rounded-md bg-primary-600 text-white flex items-center justify-center font-heading font-extrabold text-sm shadow-sm shrink-0">
                            AL
                        </div>
                        <div class="truncate">
                            <SheetTitle class="text-white text-base font-heading font-bold leading-tight tracking-tight">
                                andik<span class="text-accent-500">.</span>
                            </SheetTitle>
                            <SheetDescription class="text-text-on-dark-muted text-xs font-medium">Sistem Manajemen Toko</SheetDescription>
                        </div>
                    </div>
                </SheetHeader>

                <!-- Navigation List -->
                <div class="p-4 space-y-5 flex-1 overflow-y-auto">
                    <div v-for="group in navGroups" :key="group.label">
                        <div class="overline-label px-2 pb-2 text-text-on-dark-muted">
                            {{ group.label }}
                        </div>
                        <nav class="space-y-1" :aria-label="group.label">
                            <Link
                                v-for="item in group.items"
                                :key="item.href"
                                :href="item.href"
                                @click="isMobileMenuOpen = false"
                                :class="[
                                    'flex items-center space-x-3 px-3.5 py-2.5 rounded-pill min-h-[40px] text-sm font-semibold transition-all duration-[120ms] ease-standard cursor-pointer focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-primary-500',
                                    isCurrentRoute(item.href)
                                        ? 'bg-primary-700 text-white font-bold shadow-sm'
                                        : 'text-text-on-dark-muted hover:bg-white/10 hover:text-white',
                                ]"
                            >
                                <component :is="item.icon" class="h-5 w-5 shrink-0" aria-hidden="true" />
                                <span class="truncate">{{ item.name }}</span>
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
                            <span class="block text-[10px] text-text-on-dark-muted truncate">{{ roleLabel }}</span>
                        </div>
                    </div>

                    <Link
                        href="/logout"
                        method="post"
                        as="button"
                        @click="isMobileMenuOpen = false"
                        class="flex w-full items-center justify-center space-x-2 px-4 py-2.5 rounded-pill min-h-[40px] text-xs font-bold text-danger/80 hover:bg-danger/15 hover:text-white transition-all duration-[120ms] ease-standard cursor-pointer focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-primary-500"
                    >
                        <LogOut class="h-4 w-4 shrink-0" />
                        <span>Keluar dari Aplikasi</span>
                    </Link>
                </div>
            </SheetContent>
        </Sheet>
    </div>
</template>
