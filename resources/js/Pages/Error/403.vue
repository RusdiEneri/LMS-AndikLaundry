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

    <div class="min-h-screen flex flex-col items-center justify-center p-4 sm:p-6 bg-slate-50 text-slate-900">
        <div class="max-w-md w-full bg-white rounded-2xl p-6 sm:p-8 shadow-sm border border-slate-200 text-center space-y-5">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-rose-50 text-rose-600 mx-auto">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m0 0v2m0-2h2m-2 0H10m2-10a4 4 0 00-4 4v3h8V9a4 4 0 00-4-4z" />
                </svg>
            </div>

            <div>
                <span class="inline-block px-3 py-1 text-xs font-semibold uppercase tracking-wider text-rose-700 bg-rose-100 rounded-full mb-2">
                    Status {{ status }} Terlarang
                </span>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900">Akses Ditolak</h1>
                <p class="mt-2 text-sm text-slate-600 leading-relaxed">
                    {{ message }}
                </p>
            </div>

            <div class="pt-4 flex flex-col sm:flex-row justify-center gap-3">
                <Link href="/" class="w-full sm:w-auto">
                    <Button variant="default" class="w-full min-h-[44px] bg-teal-600 hover:bg-teal-700 text-white">
                        Kembali ke Beranda
                    </Button>
                </Link>
                <Link href="/logout" method="post" as="button" class="w-full sm:w-auto">
                    <Button variant="outline" class="w-full min-h-[44px] border-slate-300 text-slate-700 hover:bg-slate-100">
                        Ganti Akun
                    </Button>
                </Link>
            </div>
        </div>
    </div>
</template>
