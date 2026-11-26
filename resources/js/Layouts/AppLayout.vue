<script setup lang="ts">
import { computed, ref, watch, onMounted } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
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
    FileSpreadsheet,
    Users,
    Search,
    LogOut,
    Menu,
    WifiOff,
    HelpCircle,
    X,
    ChevronsLeft,
    ChevronsRight,
} from 'lucide-vue-next';

interface Props {
    title?: string;
    mainClass?: string;
}

const props = defineProps<Props>();

const page = usePage<PageProps>();
const user = computed<User | null>(() => page.props.auth?.user ?? null);
const userRole = computed(() => user.value?.role ?? 'cashier');

// Mobile drawer state
const isMobileMenuOpen = ref(false);

// Desktop sidebar collapse state
const isDesktopSidebarOpen = ref(true);

// Quick receipt search in topbar
const quickReceiptQuery = ref('');

function submitQuickReceiptLookup() {
    const q = quickReceiptQuery.value.trim();
    if (!q) {
        return;
    }
    router.visit(`/nota/${encodeURIComponent(q)}`);
}

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

function toggleDesktopSidebar() {
    isDesktopSidebarOpen.value = !isDesktopSidebarOpen.value;
    localStorage.setItem('lms_desktop_sidebar_open', String(isDesktopSidebarOpen.value));
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
    ].filter((item) => Boolean(item.message) && !dismissedFlashes.value[item.type + item.message]);
});

function dismissFlash(type: string, msg: string) {
    dismissedFlashes.value[type + msg] = true;
}

watch(
    () => page.props.flash,
    () => {
        dismissedFlashes.value = {};
    },
    { deep: true },
);

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
        name: 'Rekap Harian',
        href: '/owner/daily-recap',
        icon: FileSpreadsheet,
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
        name: 'Manajemen Staf',
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
    return navigationItems.filter((item) => item.roles.includes(userRole.value as any));
});

const navGroups = computed(() => {
    const workspace = ['/dashboard', '/pos', '/production/station'];
    const finance = ['/owner/daily-recap', '/owner/expenses', '/owner/receivables'];
    const system = ['/owner/users', '/owner/settings'];

    return [
        { label: 'Operasional', items: filteredNav.value.filter((i) => workspace.includes(i.href)) },
        { label: 'Keuangan', items: filteredNav.value.filter((i) => finance.includes(i.href)) },
        { label: 'Sistem', items: filteredNav.value.filter((i) => system.includes(i.href)) },
    ].filter((group) => group.items.length > 0);
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
            return 'Owner';
        case 'cashier':
            return 'Kasir';
        case 'operator':
            return 'Operator';
        default:
            return 'Staf';
    }
});

const userInitials = computed(() => {
    const name = user.value?.name?.trim() ?? '';
    const words = name.replace(/[^a-zA-Z0-9\s]/g, ' ').trim().split(/\s+/).filter(Boolean);
    if (words.length >= 2) {
        return (words[0][0] + words[1][0]).toUpperCase();
    }
    if (words.length === 1) {
        return words[0].slice(0, 2).toUpperCase();
    }
    return 'AL';
});
</script>

<template>
    <div class="fixed inset-0 flex overflow-hidden bg-bg font-body text-text antialiased">
        <Head :title="title ? `${title} - Andik Laundry` : 'Andik Laundry'" />

        <!-- 1. Left Desktop Sidebar (>= 1024px) -->
        <aside
            :class="[
                'hidden lg:flex lg:flex-col bg-navy-900 text-white shrink-0 select-none transition-[width] duration-300 ease-standard z-30 border-r border-navy-950',
                isDesktopSidebarOpen ? 'w-60' : 'w-[72px]',
            ]"
        >
            <!-- Sidebar Header -->
            <div
                class="h-14 shrink-0 flex items-center border-b border-white/10"
                :class="isDesktopSidebarOpen ? 'justify-between px-4' : 'justify-center px-2'"
            >
                <Link
                    href="/"
                    class="flex items-center gap-2.5 group min-w-0 rounded-md focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-primary-500"
                    aria-label="Andik Laundry, halaman utama"
                >
                    <img
                        src="/images/logo-a.webp"
                        alt=""
                        class="h-7 w-auto shrink-0 object-contain transition-transform duration-[200ms] ease-standard group-hover:scale-105"
                        width="29"
                        height="28"
                        aria-hidden="true"
                    />
                    <span v-if="isDesktopSidebarOpen" class="truncate font-heading font-extrabold text-sm text-white tracking-tight">
                        Andik Laundry
                    </span>
                </Link>
                <button
                    v-if="isDesktopSidebarOpen"
                    type="button"
                    class="h-8 w-8 flex items-center justify-center rounded-md text-text-on-dark-muted hover:text-white hover:bg-white/10 transition-colors cursor-pointer shrink-0 focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-primary-500"
                    @click="toggleDesktopSidebar"
                    title="Ciutkan menu samping"
                    aria-label="Ciutkan menu samping"
                >
                    <ChevronsLeft class="h-4 w-4" aria-hidden="true" />
                </button>
            </div>

            <!-- Navigation Links -->
            <div class="flex-1 overflow-y-auto py-4 space-y-5" :class="isDesktopSidebarOpen ? 'px-3' : 'px-2'">
                <div v-for="group in navGroups" :key="group.label">
                    <div
                        v-if="isDesktopSidebarOpen"
                        class="px-3 pb-1.5 text-[10px] font-bold uppercase tracking-[0.14em] text-text-on-dark-muted/75"
                    >
                        {{ group.label }}
                    </div>
                    <div v-else class="mx-2 mb-2 border-t border-white/10" aria-hidden="true" />
                    <nav class="space-y-0.5" :aria-label="group.label">
                        <Link
                            v-for="item in group.items"
                            :key="item.href"
                            :href="item.href"
                            :title="!isDesktopSidebarOpen ? item.name : undefined"
                            :class="[
                                'flex items-center rounded-md min-h-[40px] text-[13px] font-medium transition-colors duration-[120ms] ease-standard focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-primary-500',
                                isDesktopSidebarOpen ? 'gap-2.5 px-3 py-2' : 'justify-center h-10 w-10 mx-auto p-0',
                                isCurrentRoute(item.href)
                                    ? 'bg-navy-800 border border-white/15 text-white font-semibold shadow-xs'
                                    : 'border border-transparent text-text-on-dark-muted hover:bg-white/8 hover:text-white',
                            ]"
                        >
                            <component :is="item.icon" class="h-4 w-4 shrink-0" aria-hidden="true" />
                            <span v-if="isDesktopSidebarOpen" class="truncate">{{ item.name }}</span>
                            <span v-else class="sr-only">{{ item.name }}</span>
                        </Link>
                    </nav>
                </div>
            </div>

            <!-- Sidebar Bottom User Identity (matches clean workspace footer card) -->
            <div class="p-3 border-t border-white/10 shrink-0">
                <div
                    class="flex items-center rounded-md bg-navy-950/60 border border-white/10"
                    :class="isDesktopSidebarOpen ? 'gap-2.5 px-3 py-2.5' : 'justify-center p-2'"
                >
                    <div class="h-8 w-8 rounded-md bg-primary-700 text-white flex items-center justify-center font-heading font-bold text-xs shrink-0">
                        {{ userInitials }}
                    </div>
                    <div v-if="isDesktopSidebarOpen" class="min-w-0 flex-1">
                        <div class="flex items-center gap-1.5">
                            <span class="truncate text-xs font-semibold text-white">
                                {{ user?.name ?? 'Staf Toko' }}
                            </span>
                            <span class="shrink-0 rounded-sm bg-navy-800 border border-white/15 px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-wider text-accent-500">
                                {{ roleLabel }}
                            </span>
                        </div>
                        <span class="block truncate text-[11px] text-text-on-dark-muted">
                            {{ user?.email ?? user?.phone ?? 'Andik Laundry' }}
                        </span>
                    </div>
                </div>
            </div>
        </aside>

        <!-- 2. Right Column: Compact Topbar + Scrollable Canvas -->
        <div class="flex-1 flex flex-col min-w-0 h-full overflow-hidden bg-bg">
            <!-- Top Header Bar -->
            <header class="h-14 shrink-0 bg-surface border-b border-border px-4 sm:px-6 flex items-center justify-between gap-4 z-20">
                <div class="flex items-center gap-3 min-w-0">
                    <!-- Desktop Expand Button -->
                    <button
                        v-if="!isDesktopSidebarOpen"
                        type="button"
                        class="hidden lg:inline-flex h-9 w-9 items-center justify-center rounded-md border border-border bg-surface text-text hover:bg-primary-100 cursor-pointer transition-colors focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-primary-500"
                        @click="toggleDesktopSidebar"
                        title="Buka menu samping"
                        aria-label="Buka menu samping"
                    >
                        <ChevronsRight class="h-4 w-4" aria-hidden="true" />
                    </button>

                    <!-- Mobile Toggle Button (< lg) -->
                    <button
                        type="button"
                        class="lg:hidden inline-flex h-10 w-10 items-center justify-center rounded-md border border-border bg-surface text-text hover:bg-primary-100 cursor-pointer transition-colors focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-primary-500"
                        @click="handleMenuToggle"
                        title="Buka menu samping"
                        aria-label="Buka menu samping"
                    >
                        <Menu class="h-5 w-5" aria-hidden="true" />
                    </button>

                    <!-- Mobile Brand (< lg) -->
                    <div class="flex lg:hidden items-center gap-2 min-w-0">
                        <img
                            src="/images/logo-a.webp"
                            alt=""
                            class="h-6 w-auto shrink-0 object-contain"
                            width="25"
                            height="24"
                            aria-hidden="true"
                        />
                        <span class="font-heading font-bold text-sm text-navy-950 truncate">Andik Laundry</span>
                    </div>

                    <!-- Page Title Context on Desktop -->
                    <div class="hidden lg:flex items-center gap-2 min-w-0">
                        <span class="text-xs font-medium text-text-muted truncate">{{ title ?? 'Dashboard' }}</span>
                    </div>
                </div>

                <!-- Right Controls: Optional Status Slot + Quick Nota Lookup + WA Notice + Logout -->
                <div class="flex items-center gap-2 sm:gap-2.5 shrink-0">
                    <slot name="topbar-status" />

                    <form class="hidden sm:flex items-center relative" @submit.prevent="submitQuickReceiptLookup">
                        <Search class="pointer-events-none absolute left-2.5 h-3.5 w-3.5 text-slate-500" aria-hidden="true" />
                        <label for="topbar-receipt-search" class="sr-only">Cari nomor nota</label>
                        <input
                            id="topbar-receipt-search"
                            v-model="quickReceiptQuery"
                            type="search"
                            placeholder="Cari no. nota / HP..."
                            class="h-9 w-44 md:w-56 rounded-md border border-border bg-bg pl-8 pr-3 text-xs font-medium text-text placeholder:text-slate-500 focus:border-primary-600 focus:bg-surface focus:outline-none focus:ring-2 focus:ring-primary-100 transition-colors"
                        />
                    </form>

                    <!-- WhatsApp Gateway Mode Pill -->
                    <div class="relative hidden md:flex items-center">
                        <button
                            type="button"
                            @click="showWaTooltip = !showWaTooltip"
                            class="inline-flex h-9 items-center gap-1.5 px-3 rounded-md border border-border bg-bg text-text-muted hover:bg-primary-100/70 hover:text-navy-950 text-xs font-medium transition-colors cursor-pointer focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-primary-500"
                            aria-label="Info status WhatsApp fallback"
                        >
                            <WifiOff class="h-3.5 w-3.5 text-text-muted" aria-hidden="true" />
                            <span>WA Manual</span>
                            <HelpCircle class="h-3.5 w-3.5 text-text-muted" aria-hidden="true" />
                        </button>

                        <div
                            v-if="showWaTooltip"
                            class="absolute right-0 top-11 z-50 w-72 rounded-md bg-surface p-4 text-xs text-text shadow-lg border border-border"
                        >
                            <div class="flex justify-between items-start mb-1.5">
                                <span class="font-bold uppercase tracking-wider text-[11px] text-navy-950">Mode WhatsApp Manual</span>
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

                    <!-- Logout Button -->
                    <Link
                        href="/logout"
                        method="post"
                        as="button"
                        class="inline-flex h-9 sm:px-3 w-9 sm:w-auto items-center justify-center gap-1.5 rounded-md border border-border bg-surface text-text-muted hover:text-danger hover:bg-danger/10 hover:border-danger/40 transition-colors cursor-pointer focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-primary-500"
                        title="Keluar dari Aplikasi"
                        aria-label="Keluar dari Aplikasi"
                    >
                        <LogOut class="h-4 w-4 shrink-0" aria-hidden="true" />
                        <span class="hidden sm:inline text-xs font-semibold">Keluar</span>
                    </Link>
                </div>
            </header>

            <!-- Offline WhatsApp Fallback Banner (Mobile Only) -->
            <div class="sm:hidden bg-primary-100 text-primary-700 px-4 py-1.5 text-xs font-semibold flex items-center justify-between border-b border-primary-100 shrink-0">
                <div class="flex items-center space-x-1.5">
                    <WifiOff class="h-3.5 w-3.5 shrink-0" />
                    <span>Mode WhatsApp Manual Aktif</span>
                </div>
                <span class="text-[10px] bg-primary-700 text-white px-2 py-0.5 rounded-pill font-mono">wa.me</span>
            </div>

            <!-- Scrollable Content Canvas -->
            <div class="flex-1 overflow-y-auto bg-bg relative">
                <!-- Flash Messages -->
                <div v-if="flashMessages.length > 0" class="max-w-7xl mx-auto w-full px-4 sm:px-6 lg:px-8 pt-4 space-y-2">
                    <div v-for="flash in flashMessages" :key="flash.type" class="relative">
                        <Alert :variant="flash.type as any" class="pr-12">
                            {{ flash.message }}
                        </Alert>
                        <button
                            type="button"
                            class="absolute right-3 top-3 inline-flex h-7 w-7 items-center justify-center rounded-md text-text-muted hover:bg-black/5 hover:text-text focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-primary-500 transition-colors cursor-pointer"
                            @click="dismissFlash(flash.type, flash.message as string)"
                            aria-label="Tutup pemberitahuan"
                        >
                            <X class="h-4 w-4" />
                        </button>
                    </div>
                </div>

                <main :class="props.mainClass ?? 'p-4 sm:p-6 lg:p-8 max-w-7xl mx-auto w-full'">
                    <slot />
                </main>
            </div>
        </div>

        <!-- 3. Mobile Sliding Navigation Drawer (< 1024px) -->
        <Sheet v-model:open="isMobileMenuOpen">
            <SheetContent side="left" class="w-80 max-w-[85vw] bg-navy-900 text-white border-r border-navy-950 p-0 flex flex-col h-full shadow-lg">
                <!-- Mobile Drawer Header -->
                <SheetHeader class="p-5 bg-navy-950 border-b border-white/10 text-left">
                    <div class="flex items-center gap-3 pr-8">
                        <img
                            src="/images/logo-a.webp"
                            alt=""
                            class="h-8 w-auto shrink-0 object-contain"
                            width="33"
                            height="32"
                            aria-hidden="true"
                        />
                        <div class="truncate">
                            <SheetTitle class="text-white text-base font-heading font-extrabold leading-tight tracking-tight">
                                Andik Laundry
                            </SheetTitle>
                            <SheetDescription class="text-text-on-dark-muted text-xs font-medium">
                                Sistem Manajemen Toko
                            </SheetDescription>
                        </div>
                    </div>
                </SheetHeader>

                <!-- Navigation List -->
                <div class="p-4 space-y-5 flex-1 overflow-y-auto">
                    <div v-for="group in navGroups" :key="group.label">
                        <div class="px-2 pb-1.5 text-[10px] font-bold uppercase tracking-[0.14em] text-text-on-dark-muted/75">
                            {{ group.label }}
                        </div>
                        <nav class="space-y-0.5" :aria-label="group.label">
                            <Link
                                v-for="item in group.items"
                                :key="item.href"
                                :href="item.href"
                                @click="isMobileMenuOpen = false"
                                :class="[
                                    'flex items-center gap-3 px-3.5 py-2.5 rounded-md min-h-[44px] text-sm font-medium transition-colors cursor-pointer focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-primary-500',
                                    isCurrentRoute(item.href)
                                        ? 'bg-navy-800 border border-white/15 text-white font-semibold shadow-xs'
                                        : 'border border-transparent text-text-on-dark-muted hover:bg-white/10 hover:text-white',
                                ]"
                            >
                                <component :is="item.icon" class="h-4 w-4 shrink-0" aria-hidden="true" />
                                <span class="truncate">{{ item.name }}</span>
                            </Link>
                        </nav>
                    </div>
                </div>

                <!-- Drawer Footer: User Profile & Logout -->
                <div class="p-4 border-t border-white/10 bg-navy-950 space-y-2.5">
                    <div class="flex items-center gap-2.5 p-2.5 rounded-md bg-navy-800 text-white border border-white/10">
                        <div class="h-8 w-8 rounded-md bg-primary-700 text-white flex items-center justify-center font-bold text-xs shrink-0">
                            {{ userInitials }}
                        </div>
                        <div class="truncate flex-1">
                            <span class="block text-xs font-bold text-white leading-tight truncate">{{ user?.name ?? 'Staf Toko' }}</span>
                            <span class="block text-[10px] text-text-on-dark-muted truncate">{{ user?.email ?? user?.phone ?? roleLabel }}</span>
                        </div>
                        <Badge :variant="roleBadgeVariant as any" class="text-[10px] shrink-0">
                            {{ roleLabel }}
                        </Badge>
                    </div>

                    <Link
                        href="/logout"
                        method="post"
                        as="button"
                        @click="isMobileMenuOpen = false"
                        class="flex w-full items-center justify-center gap-2 px-4 py-2.5 rounded-md min-h-[44px] text-xs font-bold text-danger/90 hover:bg-danger/15 hover:text-white transition-colors cursor-pointer focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-primary-500"
                    >
                        <LogOut class="h-4 w-4 shrink-0" />
                        <span>Keluar dari Aplikasi</span>
                    </Link>
                </div>
            </SheetContent>
        </Sheet>
    </div>
</template>
