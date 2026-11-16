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

    <div class="min-h-screen flex flex-col items-center justify-center p-4 sm:p-6 bg-[#FFF9D2] text-black">
        <div class="max-w-md w-full bg-white rounded-[12px] border-[3.5px] border-black p-6 sm:p-8 text-center space-y-5 shadow-[6px_6px_0px_#000]">
            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-[8px] border-[2.5px] border-black bg-[#FF5252] text-black shadow-[3px_3px_0px_#000]">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 15v2m0 0v2m0-2h2m-2 0H10m2-10a4 4 0 00-4 4v3h8V9a4 4 0 00-4-4z" />
                </svg>
            </div>

            <div>
                <span class="mb-2 inline-block text-xs font-black uppercase tracking-wider bg-[#FF5252] text-black border-2 border-black rounded-[6px] px-3 py-0.5 shadow-[2px_2px_0px_#000]">
                    Status {{ status }}
                </span>
                <h1 class="text-2xl font-display font-black tracking-tight text-black mt-2">Akses Ditolak</h1>
                <p class="mt-2 text-sm font-bold text-black/70 leading-relaxed">
                    {{ message }}
                </p>
            </div>

            <div class="pt-4 flex flex-col sm:flex-row justify-center gap-3">
                <Link href="/" class="w-full sm:w-auto">
                    <Button variant="default" size="lg" class="w-full min-h-[44px] uppercase font-black tracking-wider">
                        Kembali ke Beranda
                    </Button>
                </Link>
                <Link href="/logout" method="post" as="button" class="w-full sm:w-auto">
                    <Button variant="outline" size="lg" class="w-full min-h-[44px] uppercase font-black tracking-wider">
                        Ganti Akun
                    </Button>
                </Link>
            </div>
        </div>
    </div>
</template>
