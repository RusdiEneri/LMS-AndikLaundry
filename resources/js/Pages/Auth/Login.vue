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

    <div class="min-h-screen flex items-center justify-center p-4 sm:p-6 bg-background text-ink">
        <div class="max-w-md w-full bg-surface rounded-[12px] border border-line p-6 sm:p-8 space-y-6">
            <div class="text-center space-y-2">
                <div class="mx-auto flex h-10 w-10 items-center justify-center rounded-[6px] bg-primary font-display text-base font-bold text-white">
                    AL
                </div>
                <h1 class="text-2xl font-display font-bold tracking-[-0.03em] text-ink">Masuk ke Sistem</h1>
                <p class="text-sm text-ink-muted">LMS Andik Laundry: Sistem Kasir & Manajemen Toko</p>
            </div>

            <form @submit.prevent="submit" class="space-y-4">
                <div>
                    <label for="email" class="block text-[13px] font-semibold text-ink mb-1">Email</label>
                    <input
                        id="email"
                        v-model="form.email"
                        type="email"
                        required
                        autofocus
                        autocomplete="username"
                        class="w-full px-3.5 py-2.5 rounded-[6px] border text-sm text-ink bg-surface placeholder:text-ink-subtle min-h-[44px] focus-visible:outline-none"
                        :class="form.errors.email ? 'border-error' : 'border-line'"
                        :aria-invalid="Boolean(form.errors.email)"
                        placeholder="nama@andiklaundry.test"
                    />
                    <p v-if="form.errors.email" role="alert" class="mt-1.5 text-[12px] font-medium text-error">
                        {{ form.errors.email }}
                    </p>
                </div>

                <div>
                    <label for="password" class="block text-[13px] font-semibold text-ink mb-1">Password</label>
                    <input
                        id="password"
                        v-model="form.password"
                        type="password"
                        required
                        autocomplete="current-password"
                        class="w-full px-3.5 py-2.5 rounded-[6px] border text-sm text-ink bg-surface placeholder:text-ink-subtle min-h-[44px] focus-visible:outline-none"
                        :class="form.errors.password ? 'border-error' : 'border-line'"
                        :aria-invalid="Boolean(form.errors.password)"
                        placeholder="••••••••"
                    />
                    <p v-if="form.errors.password" role="alert" class="mt-1.5 text-[12px] font-medium text-error">
                        {{ form.errors.password }}
                    </p>
                </div>

                <div class="flex items-center justify-between text-sm py-1">
                    <label class="flex items-center gap-2 cursor-pointer select-none">
                        <input
                            v-model="form.remember"
                            type="checkbox"
                            class="h-4 w-4 rounded-[4px] border-line text-primary cursor-pointer"
                        />
                        <span class="text-ink-muted text-sm">Ingat Saya</span>
                    </label>
                </div>

                <Button
                    type="submit"
                    variant="default"
                    size="lg"
                    class="w-full min-h-[44px]"
                    :disabled="form.processing"
                >
                    <span v-if="form.processing">Memproses...</span>
                    <span v-else>Masuk ke Akun</span>
                </Button>
            </form>

            <div class="pt-3 border-t border-line text-center">
                <button
                    type="button"
                    @click="form.email = 'owner@andiklaundry.test'; form.password = 'Password123!'"
                    class="text-xs text-primary hover:underline font-semibold min-h-[44px] inline-flex items-center justify-center px-3"
                >
                    Isi Kredensial Demo: Akun Owner (Andik)
                </button>
            </div>
        </div>
    </div>
</template>
