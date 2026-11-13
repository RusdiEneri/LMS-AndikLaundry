<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import Button from '@/Components/ui/button/Button.vue';

defineProps<{
    canLogin?: boolean;
}>();
</script>

<template>
    <Head title="Selamat Datang" />
    <div class="min-h-screen flex flex-col items-center justify-center p-6 bg-slate-100">
        <div class="max-w-md w-full bg-white rounded-2xl p-8 shadow-sm border border-slate-200 text-center space-y-4">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-teal-50 text-teal-600 mb-2">
                <span class="text-2xl font-bold font-mono">AL</span>
            </div>
            <h1 class="text-2xl font-bold text-slate-900">LMS Andik Laundry</h1>
            <p class="text-sm text-slate-600">Single-Store Laundry Management System — Phase 1 Foundation</p>
            <div class="pt-4 flex justify-center gap-3">
                <Button variant="default">Masuk Kasir</Button>
                <Button variant="outline">Owner Dashboard</Button>
            </div>
        </div>
    </div>
</template>
