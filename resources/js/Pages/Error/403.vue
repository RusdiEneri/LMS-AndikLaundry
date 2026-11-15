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

    <div class="min-h-screen flex flex-col items-center justify-center p-4 sm:p-6 bg-background motif-dot-grid text-ink">
        <div class="max-w-md w-full bg-surface rounded-[12px] border border-line p-6 sm:p-8 text-center space-y-5">
            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-[6px] bg-error/10 text-error">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m0 0v2m0-2h2m-2 0H10m2-10a4 4 0 00-4 4v3h8V9a4 4 0 00-4-4z" />
                </svg>
            </div>

            <div>
                <span class="mb-2 block text-[11px] font-semibold uppercase tracking-wider text-error">
                    Status {{ status }}
                </span>
                <h1 class="text-2xl font-display font-bold tracking-[-0.03em] text-ink">Akses Ditolak</h1>
                <p class="mt-2 text-sm text-ink-muted leading-relaxed">
                    {{ message }}
                </p>
            </div>

            <div class="pt-4 flex flex-col sm:flex-row justify-center gap-3">
                <Link href="/" class="w-full sm:w-auto">
                    <Button variant="default" size="lg" class="w-full min-h-[44px]">
                        Kembali ke Beranda
                    </Button>
                </Link>
                <Link href="/logout" method="post" as="button" class="w-full sm:w-auto">
                    <Button variant="outline" size="lg" class="w-full min-h-[44px]">
                        Ganti Akun
                    </Button>
                </Link>
            </div>
        </div>
    </div>
</template>
