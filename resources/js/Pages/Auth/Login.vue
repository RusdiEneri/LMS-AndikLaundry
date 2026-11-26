<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Eye, EyeOff } from 'lucide-vue-next';

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const showPassword = ref(false);

const fillDemoOwner = () => {
    form.email = 'owner@andiklaundry.test';
    form.password = 'Password123!';
    form.clearErrors();
};

const submit = () => {
    form.post('/login', {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <Head title="Masuk - Andik Laundry" />

    <div class="min-h-screen flex flex-col lg:grid lg:grid-cols-2 bg-bg text-text antialiased">
        <!-- Mobile Top Bar (< lg) -->
        <header class="flex items-center justify-between bg-navy-900 px-5 py-3.5 text-white lg:hidden">
            <Link
                href="/"
                class="inline-flex min-h-[44px] items-center gap-2.5 rounded-md focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-primary-500"
                aria-label="Andik Laundry, kembali ke beranda"
            >
                <img
                    src="/images/logo-a.webp"
                    alt=""
                    class="h-7 w-auto shrink-0 object-contain"
                    width="29"
                    height="28"
                    aria-hidden="true"
                />
                <span class="font-heading text-base font-extrabold tracking-tight text-white">
                    Andik Laundry
                </span>
            </Link>

            <Link
                href="/"
                class="inline-flex min-h-[44px] items-center gap-1.5 rounded-md px-2.5 text-xs font-semibold text-text-on-dark-muted transition-colors hover:text-white focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-primary-500"
            >
                <ArrowLeft class="h-3.5 w-3.5" aria-hidden="true" />
                <span>Beranda</span>
            </Link>
        </header>

        <!-- Left Brand Panel (Desktop >= lg) -->
        <aside class="hidden lg:flex lg:flex-col lg:justify-between bg-navy-900 bg-gradient-to-br from-navy-800 via-navy-900 to-navy-950 p-10 xl:p-14 text-white">
            <div>
                <Link
                    href="/"
                    class="inline-flex min-h-[44px] items-center gap-3 rounded-md focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-primary-500"
                    aria-label="Andik Laundry, kembali ke beranda"
                >
                    <img
                        src="/images/logo-a.webp"
                        alt=""
                        class="h-9 w-auto shrink-0 object-contain"
                        width="38"
                        height="36"
                        aria-hidden="true"
                    />
                    <span class="font-heading text-xl font-extrabold tracking-tight text-white">
                        Andik Laundry
                    </span>
                </Link>
            </div>

            <div class="my-auto py-12">
                <p class="max-w-md font-heading text-3xl xl:text-[2.5rem] font-extrabold leading-[1.15] tracking-tight text-white">
                    Kasir, cucian, dan laporan dalam satu sistem.
                </p>
                <p class="mt-4 max-w-sm text-sm leading-relaxed text-text-on-dark-muted">
                    Panel operasional internal untuk pencatatan transaksi, pemantauan produksi, dan rekap harian outlet.
                </p>
            </div>

            <div class="flex items-center gap-5 text-xs font-medium text-text-on-dark-muted">
                <Link
                    href="/"
                    class="inline-flex min-h-[44px] items-center rounded-md transition-colors hover:text-white focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-primary-500"
                >
                    Halaman Utama
                </Link>
                <span aria-hidden="true" class="text-white/25">/</span>
                <Link
                    href="/nota"
                    class="inline-flex min-h-[44px] items-center rounded-md transition-colors hover:text-white focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-primary-500"
                >
                    Cek Nota Pelanggan
                </Link>
            </div>
        </aside>

        <!-- Right Form Panel -->
        <main class="flex flex-1 flex-col px-5 py-8 sm:px-8 lg:justify-between lg:px-12 lg:py-10">
            <div class="hidden lg:flex lg:justify-end">
                <Link
                    href="/"
                    class="inline-flex min-h-[44px] items-center gap-1.5 rounded-md px-3 text-xs font-semibold text-primary-700 transition-colors hover:bg-primary-100/70 focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-primary-500"
                >
                    <ArrowLeft class="h-3.5 w-3.5" aria-hidden="true" />
                    <span>Kembali ke Beranda</span>
                </Link>
            </div>

            <div class="mx-auto my-auto w-full max-w-[380px] py-4 sm:py-8">
                <div class="mb-7">
                    <h1 class="font-heading text-2xl sm:text-[1.75rem] font-extrabold tracking-tight text-navy-950">
                        Masuk ke Sistem
                    </h1>
                    <p class="mt-1.5 text-sm text-text-muted">
                        Masuk dengan akun staf atau owner untuk melanjutkan.
                    </p>
                </div>

                <form class="space-y-4" @submit.prevent="submit">
                    <div>
                        <label
                            for="email"
                            class="mb-1.5 block text-xs font-semibold text-navy-950"
                        >
                            Email
                        </label>
                        <input
                            id="email"
                            v-model="form.email"
                            type="email"
                            required
                            autofocus
                            autocomplete="username"
                            placeholder="email@andiklaundry.test"
                            class="h-11 w-full rounded-md border bg-surface px-3.5 text-sm font-medium text-text placeholder:text-slate-500 shadow-xs transition-colors focus:border-primary-600 focus:outline-none focus:ring-3 focus:ring-primary-100"
                            :class="form.errors.email ? 'border-danger bg-danger/5' : 'border-slate-400/80'"
                            :aria-invalid="Boolean(form.errors.email)"
                        />
                        <p
                            v-if="form.errors.email"
                            role="alert"
                            aria-live="polite"
                            class="mt-1.5 text-xs font-semibold text-danger"
                        >
                            {{ form.errors.email }}
                        </p>
                    </div>

                    <div>
                        <label
                            for="password"
                            class="mb-1.5 block text-xs font-semibold text-navy-950"
                        >
                            Password
                        </label>
                        <div class="relative">
                            <input
                                id="password"
                                v-model="form.password"
                                :type="showPassword ? 'text' : 'password'"
                                required
                                autocomplete="current-password"
                                placeholder="Masukkan password"
                                class="h-11 w-full rounded-md border bg-surface pl-3.5 pr-11 text-sm font-medium text-text placeholder:text-slate-500 shadow-xs transition-colors focus:border-primary-600 focus:outline-none focus:ring-3 focus:ring-primary-100"
                                :class="form.errors.password ? 'border-danger bg-danger/5' : 'border-slate-400/80'"
                                :aria-invalid="Boolean(form.errors.password)"
                            />
                            <button
                                type="button"
                                class="absolute inset-y-0 right-0 flex w-11 items-center justify-center rounded-r-md text-slate-500 transition-colors hover:text-navy-950 focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-primary-500"
                                :aria-label="showPassword ? 'Sembunyikan password' : 'Lihat password'"
                                :aria-pressed="showPassword"
                                @click="showPassword = !showPassword"
                            >
                                <EyeOff v-if="showPassword" class="h-4 w-4" aria-hidden="true" />
                                <Eye v-else class="h-4 w-4" aria-hidden="true" />
                            </button>
                        </div>
                        <p
                            v-if="form.errors.password"
                            role="alert"
                            aria-live="polite"
                            class="mt-1.5 text-xs font-semibold text-danger"
                        >
                            {{ form.errors.password }}
                        </p>
                    </div>

                    <div class="flex items-center justify-between">
                        <label
                            for="remember"
                            class="inline-flex min-h-[44px] cursor-pointer select-none items-center gap-2.5"
                        >
                            <input
                                id="remember"
                                v-model="form.remember"
                                type="checkbox"
                                class="h-4 w-4 cursor-pointer rounded-sm border-slate-400 text-navy-800 focus:ring-primary-500"
                            />
                            <span class="text-xs font-medium text-text-muted">Ingat saya</span>
                        </label>
                    </div>

                    <div class="space-y-3 pt-1">
                        <button
                            type="submit"
                            class="flex min-h-[46px] w-full cursor-pointer items-center justify-center rounded-md bg-navy-800 px-5 font-heading text-sm font-bold text-white shadow-sm transition-colors hover:bg-navy-700 active:bg-navy-900 focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-primary-500 disabled:cursor-not-allowed disabled:opacity-50"
                            :disabled="form.processing"
                        >
                            <span v-if="form.processing">Memproses...</span>
                            <span v-else>Masuk</span>
                        </button>

                        <button
                            type="button"
                            class="flex min-h-[44px] w-full cursor-pointer items-center justify-center rounded-md border border-slate-400/80 bg-surface px-4 text-xs font-semibold text-navy-950 transition-colors hover:border-primary-600 hover:bg-primary-100/50 focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-primary-500"
                            @click="fillDemoOwner"
                        >
                            Isi Kredensial Demo (Owner)
                        </button>
                    </div>
                </form>
            </div>

            <div class="hidden lg:block" aria-hidden="true"></div>
        </main>
    </div>
</template>
