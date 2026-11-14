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
    <Head title="Masuk — LMS Andik Laundry" />

    <div class="min-h-screen flex items-center justify-center p-4 sm:p-6 bg-slate-50 text-slate-900">
        <div class="max-w-md w-full bg-white rounded-2xl p-6 sm:p-8 shadow-sm border border-slate-200 space-y-6">
            <div class="text-center space-y-2">
                <div class="inline-flex items-center justify-center w-14 h-14 rounded-full bg-teal-50 text-teal-700 font-bold text-xl tracking-tight shadow-sm">
                    AL
                </div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900">Masuk ke Sistem</h1>
                <p class="text-sm text-slate-600">LMS Andik Laundry — Sistem Kasir & Manajemen Toko</p>
            </div>

            <form @submit.prevent="submit" class="space-y-4">
                <div>
                    <label for="email" class="block text-sm font-medium text-slate-700 mb-1">Email</label>
                    <input
                        id="email"
                        v-model="form.email"
                        type="email"
                        required
                        autofocus
                        autocomplete="username"
                        class="w-full px-3.5 py-2.5 rounded-lg border text-sm text-slate-900 bg-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-teal-600 min-h-[44px]"
                        :class="form.errors.email ? 'border-rose-500 ring-1 ring-rose-500' : 'border-slate-300'"
                        placeholder="nama@andiklaundry.test"
                    />
                    <p v-if="form.errors.email" class="mt-1.5 text-xs text-rose-600 font-medium">
                        {{ form.errors.email }}
                    </p>
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-slate-700 mb-1">Password</label>
                    <input
                        id="password"
                        v-model="form.password"
                        type="password"
                        required
                        autocomplete="current-password"
                        class="w-full px-3.5 py-2.5 rounded-lg border text-sm text-slate-900 bg-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-teal-600 min-h-[44px]"
                        :class="form.errors.password ? 'border-rose-500 ring-1 ring-rose-500' : 'border-slate-300'"
                        placeholder="••••••••"
                    />
                    <p v-if="form.errors.password" class="mt-1.5 text-xs text-rose-600 font-medium">
                        {{ form.errors.password }}
                    </p>
                </div>

                <div class="flex items-center justify-between text-sm py-1">
                    <label class="flex items-center gap-2 cursor-pointer select-none">
                        <input
                            v-model="form.remember"
                            type="checkbox"
                            class="w-4 h-4 rounded border-slate-300 text-teal-600 focus:ring-teal-500 cursor-pointer"
                        />
                        <span class="text-slate-600 text-sm">Ingat Saya</span>
                    </label>
                </div>

                <Button
                    type="submit"
                    class="w-full min-h-[44px] text-sm font-medium bg-teal-600 hover:bg-teal-700 text-white transition-colors"
                    :disabled="form.processing"
                >
                    <span v-if="form.processing">Memproses...</span>
                    <span v-else>Masuk ke Akun</span>
                </Button>
            </form>
        </div>
    </div>
</template>
