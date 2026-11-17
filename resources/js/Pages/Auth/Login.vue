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

    <div class="min-h-screen flex items-center justify-center p-4 sm:p-6 bg-[#0A1033] text-white selection:bg-[#FF8A5B] selection:text-[#10194D] relative overflow-hidden">
        <!-- Subtle background glow -->
        <div class="absolute -top-40 -right-40 w-96 h-96 rounded-full bg-[#4F5FE8]/20 blur-3xl pointer-events-none"></div>
        <div class="absolute -bottom-40 -left-40 w-96 h-96 rounded-full bg-[#FF8A5B]/15 blur-3xl pointer-events-none"></div>

        <div class="max-w-md w-full bg-white text-slate-900 rounded-3xl border border-slate-200/90 p-8 sm:p-10 space-y-6 shadow-2xl relative z-10">
            <!-- Brand & Heading -->
            <div class="text-center space-y-2">
                <div class="flex items-center justify-center gap-1.5 mb-2">
                    <span class="font-heading font-extrabold text-3xl tracking-tight text-[#10194D]">
                        andik<span class="text-[#FF8A5B]">.</span>
                    </span>
                    <span class="text-[10px] font-bold tracking-widest text-[#4F5FE8] uppercase bg-[#E6E9FD] px-2 py-0.5 rounded-full">
                        LAUNDRY
                    </span>
                </div>
                <h1 class="text-2xl font-heading font-extrabold tracking-tight text-slate-900">Masuk ke Sistem</h1>
                <p class="text-xs sm:text-sm font-medium text-slate-500">Sistem Operasional Kasir & Manajemen Toko</p>
            </div>

            <form @submit.prevent="submit" class="space-y-4">
                <div>
                    <label for="email" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Email</label>
                    <input
                        id="email"
                        v-model="form.email"
                        type="email"
                        required
                        autofocus
                        autocomplete="username"
                        class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm font-semibold text-slate-900 bg-white placeholder:text-slate-400 min-h-[44px] shadow-xs focus:outline-none focus:ring-2 focus:ring-[#4F5FE8]"
                        :class="form.errors.email ? 'border-rose-300 bg-rose-50/50' : ''"
                        :aria-invalid="Boolean(form.errors.email)"
                        placeholder="nama@andiklaundry.test"
                    />
                    <p v-if="form.errors.email" role="alert" class="mt-1.5 text-xs font-bold text-rose-600">
                        {{ form.errors.email }}
                    </p>
                </div>

                <div>
                    <label for="password" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Password</label>
                    <input
                        id="password"
                        v-model="form.password"
                        type="password"
                        required
                        autocomplete="current-password"
                        class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm font-semibold text-slate-900 bg-white placeholder:text-slate-400 min-h-[44px] shadow-xs focus:outline-none focus:ring-2 focus:ring-[#4F5FE8]"
                        :class="form.errors.password ? 'border-rose-300 bg-rose-50/50' : ''"
                        :aria-invalid="Boolean(form.errors.password)"
                        placeholder="••••••••"
                    />
                    <p v-if="form.errors.password" role="alert" class="mt-1.5 text-xs font-bold text-rose-600">
                        {{ form.errors.password }}
                    </p>
                </div>

                <div class="flex items-center justify-between text-sm py-1">
                    <label class="flex items-center gap-2.5 cursor-pointer select-none">
                        <input
                            v-model="form.remember"
                            type="checkbox"
                            class="h-4 w-4 rounded-md border-slate-300 text-[#4F5FE8] focus:ring-[#4F5FE8] cursor-pointer"
                        />
                        <span class="text-xs font-medium text-slate-600">Ingat Saya</span>
                    </label>
                </div>

                <Button
                    type="submit"
                    variant="default"
                    size="lg"
                    class="w-full min-h-[46px] uppercase tracking-wider font-bold rounded-xl bg-[#FF8A5B] hover:bg-[#F26B3A] text-[#10194D] shadow-sm transition-all"
                    :disabled="form.processing"
                >
                    <span v-if="form.processing">Memproses...</span>
                    <span v-else>Masuk ke Akun</span>
                </Button>
            </form>

            <div class="pt-4 border-t border-slate-100 text-center">
                <button
                    type="button"
                    @click="form.email = 'owner@andiklaundry.test'; form.password = 'Password123!'"
                    class="rounded-xl border border-slate-200 bg-slate-50 hover:bg-slate-100 px-4 py-2.5 text-xs font-bold text-slate-700 shadow-xs min-h-[42px] inline-flex items-center justify-center transition-all cursor-pointer"
                >
                    Isi Kredensial Demo: Akun Owner (Andik)
                </button>
            </div>
        </div>
    </div>
</template>
