<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
    SheetDescription,
} from '@/Components/ui/sheet';
import { Clock, ShoppingBag, ChevronUp } from 'lucide-vue-next';

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
    title: 'Kasir POS',
    cartCount: 0,
    cartTotalFormatted: 'Rp 0',
    storeName: 'Andik Laundry',
});

const emits = defineEmits<{
    (e: 'hotkey-search'): void;
    (e: 'hotkey-checkout'): void;
}>();

const isMobileCartOpen = ref(false);
const currentTime = ref('');
let timerInterval: ReturnType<typeof setInterval> | null = null;

const isShiftOpen = computed(() => props.shiftInfo?.is_open !== false);

function updateClock() {
    currentTime.value = new Date().toLocaleTimeString('id-ID', {
        hour: '2-digit',
        minute: '2-digit',
    });
}

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
    timerInterval = setInterval(updateClock, 15000);
    window.addEventListener('keydown', handleGlobalKeydown);
});

onUnmounted(() => {
    if (timerInterval) clearInterval(timerInterval);
    window.removeEventListener('keydown', handleGlobalKeydown);
});
</script>

<template>
    <AppLayout
        :title="title"
        main-class="mx-auto w-full max-w-[1440px] px-4 py-4 sm:px-6 sm:py-5 pb-24 lg:pb-6"
    >
        <template #topbar-status>
            <div
                class="hidden lg:inline-flex items-center gap-2 h-9 px-3 rounded-md border border-border bg-bg text-xs whitespace-nowrap"
                :title="shiftInfo?.cashier_name ? `Kasir aktif: ${shiftInfo.cashier_name}` : undefined"
            >
                <span
                    class="h-2 w-2 rounded-full shrink-0"
                    :class="isShiftOpen ? 'bg-success' : 'bg-warning'"
                    aria-hidden="true"
                />
                <span class="font-semibold text-navy-950">
                    {{ isShiftOpen ? 'Toko Buka' : 'Shift Tutup' }}
                </span>
                <span class="text-border" aria-hidden="true">|</span>
                <span class="inline-flex items-center gap-1 font-mono font-medium text-text-muted tabular-nums">
                    <Clock class="h-3.5 w-3.5" aria-hidden="true" />
                    {{ currentTime }}
                </span>
            </div>
        </template>

        <!-- Two-Column Responsive POS Workspace -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 items-start">
            <!-- Left Catalog Flows with Single Outer Scrollbar -->
            <section class="lg:col-span-7 min-w-0" aria-label="Katalog layanan">
                <slot name="catalog">
                    <slot />
                </slot>
            </section>

            <!-- Right Cart Stays Sticky on Desktop -->
            <aside
                class="hidden lg:flex lg:col-span-5 flex-col lg:sticky lg:top-5 lg:max-h-[calc(100vh-5.75rem)] rounded-lg border border-border bg-surface p-4 shadow-xs min-h-0"
                aria-label="Keranjang transaksi"
            >
                <slot name="cart" />
            </aside>
        </div>

        <!-- Mobile & Tablet (< 1024px) Fixed Bottom Checkout Bar -->
        <div
            class="lg:hidden fixed bottom-0 inset-x-0 z-40 bg-navy-900 text-white border-t border-navy-950 px-4 h-16 flex items-center justify-between gap-3 pb-[env(safe-area-inset-bottom,0px)] shadow-lg"
        >
            <div class="flex items-center gap-3 min-w-0">
                <div class="relative flex items-center justify-center h-10 w-10 rounded-md bg-navy-800 border border-white/15 text-white shrink-0">
                    <ShoppingBag class="h-5 w-5" aria-hidden="true" />
                    <span
                        v-if="cartCount > 0"
                        class="absolute -top-1.5 -right-1.5 flex h-5 min-w-[20px] items-center justify-center rounded-pill bg-accent-500 px-1 text-[11px] font-bold text-navy-950 tabular-nums"
                    >
                        {{ cartCount }}
                    </span>
                </div>

                <div class="min-w-0">
                    <span class="text-[11px] text-text-on-dark-muted font-medium block leading-none">
                        {{ cartCount }} item
                    </span>
                    <span class="font-heading font-extrabold text-base text-white tracking-tight tabular-nums leading-tight block mt-1 truncate">
                        {{ cartTotalFormatted }}
                    </span>
                </div>
            </div>

            <button
                type="button"
                @click="isMobileCartOpen = true"
                class="inline-flex min-h-[44px] items-center gap-2 px-4 rounded-md bg-accent-500 hover:bg-accent-600 text-navy-950 font-heading font-bold text-xs tracking-wide transition-colors cursor-pointer shrink-0 focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-primary-500"
            >
                <span>Keranjang</span>
                <ChevronUp class="h-4 w-4 shrink-0" aria-hidden="true" />
            </button>
        </div>

        <!-- Mobile & Tablet Bottom Sheet for Cart & Checkout -->
        <Sheet v-model:open="isMobileCartOpen">
            <SheetContent side="bottom" class="p-0 max-h-[92vh] flex flex-col bg-surface border-t border-border">
                <SheetHeader class="p-4 border-b border-border text-left bg-bg">
                    <SheetTitle class="text-base font-bold text-navy-950">Keranjang & Pembayaran</SheetTitle>
                    <SheetDescription class="text-xs font-medium text-text-muted">
                        {{ cartCount }} item cucian · Total {{ cartTotalFormatted }}
                    </SheetDescription>
                </SheetHeader>

                <div class="flex-1 overflow-y-auto p-4">
                    <slot name="cart" />
                </div>
            </SheetContent>
        </Sheet>
    </AppLayout>
</template>
