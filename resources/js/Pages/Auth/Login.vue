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

    <div class="min-h-screen flex items-center justify-center p-4 sm:p-6 bg-[#FFF9D2] text-black">
        <div class="max-w-md w-full bg-white rounded-[12px] border-[3.5px] border-black p-6 sm:p-8 space-y-6 shadow-[6px_6px_0px_#000]">
            <div class="text-center space-y-2">
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-[8px] border-[2.5px] border-black bg-[#FFEB3B] font-display text-xl font-black text-black shadow-[3px_3px_0px_#000]">
                    AL
                </div>
                <h1 class="text-2xl font-display font-black tracking-tight text-black">Masuk ke Sistem</h1>
                <p class="text-sm font-bold text-black/70">LMS Andik Laundry: Sistem Kasir & Manajemen Toko</p>
            </div>

            <form @submit.prevent="submit" class="space-y-4">
                <div>
                    <label for="email" class="block text-xs font-black uppercase tracking-wider text-black mb-1.5">Email</label>
                    <input
                        id="email"
                        v-model="form.email"
                        type="email"
                        required
                        autofocus
                        autocomplete="username"
                        class="w-full px-4 py-2.5 rounded-[8px] border-[2.5px] border-black text-sm font-bold text-black bg-white placeholder:text-black/40 min-h-[44px] shadow-[3px_3px_0px_#000] focus:outline-none focus:shadow-[4px_4px_0px_#000] focus:ring-0"
                        :class="form.errors.email ? 'border-[#FF5252] bg-[#FF5252]/10' : ''"
                        :aria-invalid="Boolean(form.errors.email)"
                        placeholder="nama@andiklaundry.test"
                    />
                    <p v-if="form.errors.email" role="alert" class="mt-1.5 text-xs font-black text-[#FF5252]">
                        {{ form.errors.email }}
                    </p>
                </div>

                <div>
                    <label for="password" class="block text-xs font-black uppercase tracking-wider text-black mb-1.5">Password</label>
                    <input
                        id="password"
                        v-model="form.password"
                        type="password"
                        required
                        autocomplete="current-password"
                        class="w-full px-4 py-2.5 rounded-[8px] border-[2.5px] border-black text-sm font-bold text-black bg-white placeholder:text-black/40 min-h-[44px] shadow-[3px_3px_0px_#000] focus:outline-none focus:shadow-[4px_4px_0px_#000] focus:ring-0"
                        :class="form.errors.password ? 'border-[#FF5252] bg-[#FF5252]/10' : ''"
                        :aria-invalid="Boolean(form.errors.password)"
                        placeholder="••••••••"
                    />
                    <p v-if="form.errors.password" role="alert" class="mt-1.5 text-xs font-black text-[#FF5252]">
                        {{ form.errors.password }}
                    </p>
                </div>

                <div class="flex items-center justify-between text-sm py-1">
                    <label class="flex items-center gap-2.5 cursor-pointer select-none">
                        <input
                            v-model="form.remember"
                            type="checkbox"
                            class="h-5 w-5 rounded-[4px] border-2 border-black text-black accent-black cursor-pointer"
                        />
                        <span class="text-xs font-bold text-black">Ingat Saya</span>
                    </label>
                </div>

                <Button
                    type="submit"
                    variant="default"
                    size="lg"
                    class="w-full min-h-[44px] uppercase tracking-wider font-black"
                    :disabled="form.processing"
                >
                    <span v-if="form.processing">Memproses...</span>
                    <span v-else>Masuk ke Akun</span>
                </Button>
            </form>

            <div class="pt-4 border-t-2 border-black/20 text-center">
                <button
                    type="button"
                    @click="form.email = 'owner@andiklaundry.test'; form.password = 'Password123!'"
                    class="rounded-[8px] border-2 border-black bg-white px-3.5 py-2 text-xs font-black text-black shadow-[2px_2px_0px_#000] hover:bg-[#FFEB3B] active:translate-x-0.5 active:translate-y-0.5 active:shadow-none min-h-[44px] inline-flex items-center justify-center transition-all cursor-pointer"
                >
                    Isi Kredensial Demo: Akun Owner (Andik)
                </button>
            </div>
        </div>
    </div>
</template>
