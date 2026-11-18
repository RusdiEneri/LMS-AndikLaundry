<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import Button from '@/Components/ui/button/Button.vue';

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post('/login', {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <Head title="Masuk - LMS Andik Laundry" />

    <div class="min-h-screen flex items-center justify-center p-4 sm:p-6 bg-navy-950 text-white selection:bg-accent-500 selection:text-navy-950 relative overflow-hidden">
        <!-- Subtle background glow -->
        <div class="absolute -top-40 -right-40 w-96 h-96 rounded-full bg-primary-600/20 blur-3xl pointer-events-none"></div>
        <div class="absolute -bottom-40 -left-40 w-96 h-96 rounded-full bg-accent-500/15 blur-3xl pointer-events-none"></div>

        <div class="max-w-md w-full bg-surface text-text rounded-lg border border-border p-8 sm:p-10 space-y-6 shadow-2xl relative z-10">
            <!-- Brand & Heading -->
            <div class="text-center space-y-2">
                <div class="flex items-center justify-center gap-1.5 mb-2">
                    <span class="font-heading font-extrabold text-3xl tracking-tight text-navy-900">
                        andik<span class="text-accent-500">.</span>
                    </span>
                    <span class="text-[10px] font-bold tracking-widest text-primary-700 uppercase bg-primary-100 px-2.5 py-0.5 rounded-pill">
                        LAUNDRY
                    </span>
                </div>
                <h1 class="text-2xl font-heading font-extrabold tracking-tight text-text">Masuk ke Sistem</h1>
                <p class="text-xs sm:text-sm font-medium text-text-muted">Sistem Operasional Kasir & Manajemen Toko</p>
            </div>

            <form @submit.prevent="submit" class="space-y-4">
                <div>
                    <label for="email" class="block text-xs font-bold uppercase tracking-wider text-text mb-1.5">Email</label>
                    <input
                        id="email"
                        v-model="form.email"
                        type="email"
                        required
                        autofocus
                        autocomplete="username"
                        class="w-full px-4 py-2.5 rounded-md border border-border text-sm font-medium text-text bg-surface placeholder:text-text-muted min-h-[44px] shadow-xs focus:outline-none focus:border-primary-600 focus:ring-3 focus:ring-primary-100"
                        :class="form.errors.email ? 'border-danger bg-danger/10' : ''"
                        :aria-invalid="Boolean(form.errors.email)"
                        placeholder="nama@andiklaundry.test"
                    />
                    <p v-if="form.errors.email" role="alert" class="mt-1.5 text-xs font-bold text-danger">
                        {{ form.errors.email }}
                    </p>
                </div>

                <div>
                    <label for="password" class="block text-xs font-bold uppercase tracking-wider text-text mb-1.5">Password</label>
                    <input
                        id="password"
                        v-model="form.password"
                        type="password"
                        required
                        autocomplete="current-password"
                        class="w-full px-4 py-2.5 rounded-md border border-border text-sm font-medium text-text bg-surface placeholder:text-text-muted min-h-[44px] shadow-xs focus:outline-none focus:border-primary-600 focus:ring-3 focus:ring-primary-100"
                        :class="form.errors.password ? 'border-danger bg-danger/10' : ''"
                        :aria-invalid="Boolean(form.errors.password)"
                        placeholder="••••••••"
                    />
                    <p v-if="form.errors.password" role="alert" class="mt-1.5 text-xs font-bold text-danger">
                        {{ form.errors.password }}
                    </p>
                </div>

                <div class="flex items-center justify-between text-sm py-1">
                    <label class="flex items-center gap-2.5 cursor-pointer select-none">
                        <input
                            v-model="form.remember"
                            type="checkbox"
                            class="h-4 w-4 rounded-md border-border text-primary-600 focus:ring-primary-500 cursor-pointer"
                        />
                        <span class="text-xs font-medium text-text-muted">Ingat Saya</span>
                    </label>
                </div>

                <button
                    type="submit"
                    class="w-full min-h-[46px] uppercase tracking-wider font-bold rounded-pill bg-accent-500 hover:bg-accent-600 text-navy-950 shadow-sm transition-all duration-[120ms] ease-standard flex items-center justify-center cursor-pointer focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-primary-500 disabled:opacity-50"
                    :disabled="form.processing"
                >
                    <span v-if="form.processing">Memproses...</span>
                    <span v-else>Masuk ke Akun</span>
                </button>
            </form>

            <div class="pt-4 border-t border-border text-center">
                <button
                    type="button"
                    @click="form.email = 'owner@andiklaundry.test'; form.password = 'Password123!'"
                    class="rounded-pill border border-border bg-bg hover:bg-surface px-4 py-2.5 text-xs font-bold text-text shadow-xs min-h-[42px] inline-flex items-center justify-center transition-all cursor-pointer"
                >
                    Isi Kredensial Demo: Akun Owner (Andik)
                </button>
            </div>
        </div>
    </div>
</template>
