<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import Button from '@/Components/ui/button/Button.vue';

withDefaults(
    defineProps<{
        status?: number;
        message?: string;
    }>(),
    {
        status: 403,
        message: 'Akses Ditolak: Anda tidak memiliki wewenang untuk mengakses halaman ini.',
    }
);
</script>

<template>
    <Head title="403 - Akses Ditolak" />

    <div class="min-h-screen flex flex-col items-center justify-center p-4 sm:p-6 bg-navy-950 text-text relative overflow-hidden">
        <!-- Ambient background glows -->
        <div class="pointer-events-none absolute -top-40 -left-40 h-96 w-96 rounded-full bg-primary-600/20 blur-3xl"></div>
        <div class="pointer-events-none absolute -bottom-40 -right-40 h-96 w-96 rounded-full bg-accent-500/15 blur-3xl"></div>

        <div class="max-w-md w-full bg-surface rounded-lg border border-border p-6 sm:p-8 text-center space-y-5 shadow-2xl relative z-10">
            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-lg bg-danger/10 text-danger border border-danger/20 shadow-xs">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m0 0v2m0-2h2m-2 0H10m2-10a4 4 0 00-4 4v3h8V9a4 4 0 00-4-4z" />
                </svg>
            </div>

            <div>
                <span class="mb-2 inline-block text-xs font-bold uppercase tracking-wider bg-danger/10 text-danger rounded-pill px-3 py-0.5 border border-danger/20">
                    Status {{ status }}
                </span>
                <h1 class="text-2xl font-heading font-extrabold tracking-tight text-navy-900 mt-2">Akses Ditolak</h1>
                <p class="mt-2 text-sm font-medium text-text-muted leading-relaxed">
                    {{ message }}
                </p>
            </div>

            <div class="pt-4 flex flex-col sm:flex-row justify-center gap-3">
                <Link href="/" class="w-full sm:w-auto">
                    <button type="button" class="w-full min-h-[44px] font-bold rounded-pill bg-accent-500 hover:bg-accent-600 text-navy-950 shadow-xs transition-all duration-[120ms] ease-standard px-5 cursor-pointer">
                        Kembali ke Beranda
                    </button>
                </Link>
                <Link href="/logout" method="post" as="button" class="w-full sm:w-auto">
                    <Button variant="outline" size="lg" class="w-full min-h-[44px] font-bold rounded-pill border-border text-text hover:bg-bg cursor-pointer">
                        Ganti Akun
                    </Button>
                </Link>
            </div>
        </div>
    </div>
</template>
