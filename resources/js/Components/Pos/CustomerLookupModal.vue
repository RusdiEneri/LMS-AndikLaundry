<script setup lang="ts">
import { ref, watch } from 'vue';
import { Search, UserPlus, Phone, MapPin, AlertCircle, Check, X, Loader2, User as UserIcon } from 'lucide-vue-next';
import { formatRupiah } from '@/Support/whatsapp';

export interface CustomerData {
    id: number | null;
    name: string;
    phone: string;
    phone_normalized?: string;
    address?: string | null;
    notes?: string | null;
    total_orders_count?: number;
    total_spend?: number | string;
    outstanding_receivables?: number | string;
}

interface Props {
    isOpen: boolean;
    currentCustomer?: CustomerData | null;
    defaultCustomer?: CustomerData | null;
    creditCeiling?: number;
}

const props = withDefaults(defineProps<Props>(), {
    isOpen: false,
    currentCustomer: null,
    defaultCustomer: null,
    creditCeiling: 150000,
});

const emits = defineEmits<{
    (e: 'update:isOpen', value: boolean): void;
    (e: 'select', customer: CustomerData): void;
}>();

const activeTab = ref<'search' | 'create'>('search');
const searchQuery = ref('');
const searchResults = ref<CustomerData[]>([]);
const isSearching = ref(false);
const searchError = ref('');

// Fast Add Form State
const newName = ref('');
const newPhone = ref('');
const newAddress = ref('');
const newNotes = ref('');
const isSubmitting = ref(false);
const formError = ref('');

let debounceTimer: ReturnType<typeof setTimeout> | null = null;

watch(searchQuery, (newVal) => {
    if (debounceTimer) clearTimeout(debounceTimer);
    const query = newVal.trim();
    if (query.length === 0) {
        searchResults.value = [];
        isSearching.value = false;
        return;
    }

    isSearching.value = true;
    debounceTimer = setTimeout(async () => {
        try {
            searchError.value = '';
            const res = await fetch(`/api/pos/customers/search?q=${encodeURIComponent(query)}`, {
                headers: {
                    'Accept': 'application/json',
                },
            });
            if (res.ok) {
                const data = await res.json();
                searchResults.value = Array.isArray(data) ? data : [];
            } else {
                searchError.value = 'Gagal mencari data pelanggan.';
            }
        } catch {
            searchError.value = 'Terjadi kesalahan koneksi.';
        } finally {
            isSearching.value = false;
        }
    }, 250);
});

function selectCustomer(cust: CustomerData) {
    emits('select', cust);
    closeModal();
}

function selectWalkIn() {
    if (props.defaultCustomer && props.defaultCustomer.id) {
        emits('select', { ...props.defaultCustomer });
    } else {
        emits('select', {
            id: props.defaultCustomer?.id ?? null,
            name: props.defaultCustomer?.name ?? 'Pelanggan Umum',
            phone: props.defaultCustomer?.phone ?? '',
            outstanding_receivables: props.defaultCustomer?.outstanding_receivables ?? 0,
        });
    }
    closeModal();
}

async function handleCreateCustomer() {
    if (!newName.value.trim()) {
        formError.value = 'Nama pelanggan wajib diisi.';
        return;
    }
    if (!newPhone.value.trim()) {
        formError.value = 'Nomor telepon/WhatsApp wajib diisi.';
        return;
    }

    isSubmitting.value = true;
    formError.value = '';

    try {
        const csrfToken = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content;
        const res = await fetch('/api/pos/customers', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken || '',
            },
            body: JSON.stringify({
                name: newName.value.trim(),
                phone: newPhone.value.trim(),
                address: newAddress.value.trim() || null,
                notes: newNotes.value.trim() || null,
                whatsapp_consent: true,
            }),
        });

        if (res.ok) {
            const created = await res.json();
            // Reset form
            newName.value = '';
            newPhone.value = '';
            newAddress.value = '';
            newNotes.value = '';
            activeTab.value = 'search';
            // Select immediately
            selectCustomer(created);
        } else {
            const errData = await res.json();
            formError.value = errData.message || 'Gagal menyimpan pelanggan baru.';
        }
    } catch {
        formError.value = 'Terjadi kesalahan sistem saat membuat pelanggan.';
    } finally {
        isSubmitting.value = false;
    }
}

function closeModal() {
    emits('update:isOpen', false);
}
</script>

<template>
    <div
        v-if="isOpen"
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-3 sm:p-4 animate-fadeIn"
        @click.self="closeModal"
    >
        <div class="bg-white rounded-3xl max-w-lg w-full border border-slate-200/90 shadow-2xl overflow-hidden flex flex-col max-h-[90vh]">
            <!-- Header: Modern Navy-900 Banner -->
            <div class="px-6 py-4 bg-[#10194D] text-white flex items-center justify-between">
                <div>
                    <div class="flex items-center space-x-2">
                        <span class="text-[10px] font-bold uppercase tracking-wider bg-white/10 text-slate-200 px-2.5 py-0.5 rounded-full border border-white/20">
                            Data Pelanggan
                        </span>
                    </div>
                    <h3 class="font-bold text-lg text-white tracking-tight mt-0.5">Pencarian & Profil Pelanggan</h3>
                </div>
                <button
                    type="button"
                    @click="closeModal"
                    class="h-8 w-8 rounded-full bg-white/10 text-white hover:bg-white/20 flex items-center justify-center transition-colors cursor-pointer"
                    aria-label="Tutup Modal"
                >
                    <X class="h-4 w-4" />
                </button>
            </div>

            <!-- Tabs: Cari Pelanggan vs Tambah Baru -->
            <div class="flex border-b border-slate-200 bg-slate-50/70 p-2 gap-2">
                <button
                    type="button"
                    @click="activeTab = 'search'"
                    :class="[
                        'flex-1 min-h-[42px] py-2 text-xs font-bold uppercase tracking-wider rounded-xl transition-all flex items-center justify-center space-x-2 cursor-pointer',
                        activeTab === 'search'
                            ? 'bg-[#4F5FE8] text-white shadow-sm'
                            : 'bg-transparent text-slate-600 hover:text-slate-900 hover:bg-slate-100',
                    ]"
                >
                    <Search class="h-4 w-4" />
                    <span>Cari Pelanggan</span>
                </button>
                <button
                    type="button"
                    @click="activeTab = 'create'"
                    :class="[
                        'flex-1 min-h-[42px] py-2 text-xs font-bold uppercase tracking-wider rounded-xl transition-all flex items-center justify-center space-x-2 cursor-pointer',
                        activeTab === 'create'
                            ? 'bg-[#4F5FE8] text-white shadow-sm'
                            : 'bg-transparent text-slate-600 hover:text-slate-900 hover:bg-slate-100',
                    ]"
                >
                    <UserPlus class="h-4 w-4" />
                    <span>Tambah Pelanggan Baru</span>
                </button>
            </div>

            <!-- Tab 1: Search -->
            <div v-if="activeTab === 'search'" class="p-5 flex-1 overflow-y-auto space-y-4">
                <!-- Search Input -->
                <div class="relative">
                    <Search class="absolute left-3.5 top-1/2 -translate-y-1/2 h-5 w-5 text-slate-400" />
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Ketik nama atau nomor WhatsApp (mis: 0812...)..."
                        class="min-h-[44px] w-full rounded-xl border border-slate-200 bg-white pl-11 pr-10 py-2.5 text-sm font-semibold text-slate-900 placeholder:text-slate-400 shadow-xs focus:outline-none focus:ring-2 focus:ring-[#4F5FE8]"
                        autofocus
                    />
                    <div v-if="isSearching" class="absolute right-3.5 top-1/2 -translate-y-1/2">
                        <Loader2 class="h-4 w-4 animate-spin text-[#4F5FE8]" />
                    </div>
                </div>

                <!-- Walk-in Shortcut Option -->
                <button
                    type="button"
                    @click="selectWalkIn"
                    class="w-full text-left p-3.5 rounded-2xl border border-slate-200 bg-[#F8FAFC] hover:bg-[#E6E9FD]/40 transition-all flex items-center justify-between cursor-pointer group hover:border-[#4F5FE8]/40"
                >
                    <div class="flex items-center space-x-3">
                        <div class="h-10 w-10 rounded-xl bg-[#E6E9FD] text-[#4F5FE8] flex items-center justify-center font-bold">
                            <UserIcon class="h-5 w-5" />
                        </div>
                        <div>
                            <span class="font-bold text-sm text-slate-900 block group-hover:text-[#4F5FE8] transition-colors">Pelanggan Umum (Walk-in)</span>
                            <span class="text-xs text-slate-500 font-medium">Pelanggan langsung / tanpa simpan kontak</span>
                        </div>
                    </div>
                    <span class="text-xs font-bold uppercase tracking-wider text-[#4F5FE8] px-3 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs group-hover:bg-[#4F5FE8] group-hover:text-white group-hover:border-[#4F5FE8] transition-all">
                        Pilih
                    </span>
                </button>

                <!-- Search Results List -->
                <div class="space-y-2">
                    <div v-if="searchQuery.trim().length > 0 && !isSearching && searchResults.length === 0" class="text-center py-8 text-slate-600 font-medium text-xs bg-slate-50 rounded-2xl border border-dashed border-slate-300 p-5">
                        Tidak ada pelanggan dengan kata kunci "{{ searchQuery }}".
                        <div class="mt-3">
                            <button
                                type="button"
                                @click="activeTab = 'create'; newName = searchQuery"
                                class="text-[#10194D] font-bold uppercase text-xs bg-[#FF8A5B] hover:bg-[#F26B3A] px-4 py-2 rounded-xl shadow-xs transition-all inline-block cursor-pointer"
                            >
                                + Daftarkan Sekarang
                            </button>
                        </div>
                    </div>

                    <div
                        v-for="cust in searchResults"
                        :key="cust.id ?? cust.phone"
                        @click="selectCustomer(cust)"
                        class="p-3.5 rounded-2xl border border-slate-200/90 bg-white hover:bg-[#F8FAFC] cursor-pointer transition-all flex items-start justify-between shadow-xs hover:border-[#4F5FE8]/40 group"
                    >
                        <div class="space-y-1">
                            <div class="flex items-center space-x-2">
                                <span class="font-bold text-sm text-slate-900 group-hover:text-[#4F5FE8] transition-colors">{{ cust.name }}</span>
                                <!-- Piutang / Debt Badge if any -->
                                <span
                                    v-if="Number(cust.outstanding_receivables ?? 0) > 0"
                                    class="inline-flex items-center space-x-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200"
                                >
                                    <AlertCircle class="h-3 w-3 stroke-[2.5]" />
                                    <span>Piutang: {{ formatRupiah(cust.outstanding_receivables) }}</span>
                                </span>
                            </div>

                            <div class="flex items-center space-x-3 text-xs font-medium text-slate-500">
                                <span class="flex items-center space-x-1">
                                    <Phone class="h-3.5 w-3.5 text-slate-400" />
                                    <span class="font-mono font-semibold text-slate-700">{{ cust.phone }}</span>
                                </span>
                                <span>•</span>
                                <span>{{ cust.total_orders_count ?? 0 }} Transaksi</span>
                            </div>

                            <div v-if="cust.address" class="text-[11px] font-medium text-slate-500 flex items-center space-x-1">
                                <MapPin class="h-3 w-3 text-slate-400" />
                                <span class="truncate max-w-[280px]">{{ cust.address }}</span>
                            </div>
                        </div>

                        <div class="h-8 w-8 rounded-xl bg-slate-50 border border-slate-200 text-slate-400 group-hover:bg-[#22A06B] group-hover:text-white group-hover:border-[#22A06B] flex items-center justify-center transition-all">
                            <Check class="h-4 w-4 stroke-[2.5]" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab 2: Create Customer -->
            <div v-else class="p-5 flex-1 overflow-y-auto space-y-4">
                <div v-if="formError" class="p-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-xs font-semibold flex items-center space-x-2">
                    <AlertCircle class="h-4 w-4 shrink-0 stroke-[2.5]" />
                    <span>{{ formError }}</span>
                </div>

                <div class="space-y-3">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">Nama Lengkap *</label>
                        <input
                            v-model="newName"
                            type="text"
                            placeholder="Contoh: Budi Santoso"
                            class="min-h-[44px] w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-sm font-semibold text-slate-900 placeholder:text-slate-400 shadow-xs focus:outline-none focus:ring-2 focus:ring-[#4F5FE8]"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">Nomor WhatsApp *</label>
                        <input
                            v-model="newPhone"
                            type="tel"
                            placeholder="Contoh: 081234567890"
                            class="min-h-[44px] w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-sm font-mono font-semibold text-slate-900 placeholder:text-slate-400 shadow-xs focus:outline-none focus:ring-2 focus:ring-[#4F5FE8]"
                        />
                        <span class="text-[11px] font-medium text-slate-500 mt-1 block">Format: 08xxx atau 628xxx (otomatis dinormalisasi).</span>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">Alamat (Opsional)</label>
                        <textarea
                            v-model="newAddress"
                            rows="2"
                            placeholder="Contoh: Jl. Merpati No. 10, RT 02/05"
                            class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-sm font-semibold text-slate-900 placeholder:text-slate-400 shadow-xs focus:outline-none focus:ring-2 focus:ring-[#4F5FE8]"
                        ></textarea>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">Catatan Pelanggan (Opsional)</label>
                        <input
                            v-model="newNotes"
                            type="text"
                            placeholder="Mis: Alergi deterjen tertentu, langganan VIP"
                            class="min-h-[44px] w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-sm font-semibold text-slate-900 placeholder:text-slate-400 shadow-xs focus:outline-none focus:ring-2 focus:ring-[#4F5FE8]"
                        />
                    </div>
                </div>

                <div class="pt-2 flex items-center space-x-3">
                    <button
                        type="button"
                        @click="activeTab = 'search'"
                        class="min-h-[44px] px-5 py-2 rounded-xl border border-slate-200 bg-white text-slate-700 text-xs font-bold uppercase tracking-wider hover:bg-slate-50 transition-all cursor-pointer"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        :disabled="isSubmitting"
                        @click="handleCreateCustomer"
                        class="min-h-[44px] flex-1 px-5 py-2 rounded-xl bg-[#FF8A5B] hover:bg-[#F26B3A] text-[#10194D] text-xs font-bold uppercase tracking-wider shadow-sm disabled:opacity-50 flex items-center justify-center space-x-2 cursor-pointer transition-all"
                    >
                        <Loader2 v-if="isSubmitting" class="h-4 w-4 animate-spin text-[#10194D]" />
                        <span>Simpan & Pilih Pelanggan</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
