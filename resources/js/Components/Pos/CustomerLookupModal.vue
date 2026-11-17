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
            newName.value = '';
            newPhone.value = '';
            newAddress.value = '';
            newNotes.value = '';
            activeTab.value = 'search';
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
        class="fixed inset-0 z-50 flex items-center justify-center bg-navy-950/60 backdrop-blur-[4px] p-3 sm:p-4 animate-fadeIn"
        @click.self="closeModal"
    >
        <div class="bg-surface rounded-xl max-w-lg w-full shadow-lg overflow-hidden flex flex-col max-h-[90vh]">
            <div class="px-6 py-4 bg-navy-900 text-white flex items-center justify-between">
                <div>
                    <div class="flex items-center space-x-2">
                        <span class="overline-label bg-white/10 text-text-on-dark-muted px-2.5 py-0.5 rounded-pill border border-white/20">
                            Data Pelanggan
                        </span>
                    </div>
                    <h3 class="font-heading font-bold text-lg text-white tracking-tight mt-0.5">Pencarian & Profil Pelanggan</h3>
                </div>
                <button
                    type="button"
                    @click="closeModal"
                    class="h-8 w-8 rounded-full bg-white/10 text-white hover:bg-white/20 flex items-center justify-center transition-colors duration-[120ms] ease-standard cursor-pointer focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-primary-500"
                    aria-label="Tutup Modal"
                >
                    <X class="h-4 w-4" />
                </button>
            </div>

            <div class="flex border-b border-border bg-bg p-2 gap-2">
                <button
                    type="button"
                    @click="activeTab = 'search'"
                    :class="[
                        'flex-1 min-h-[42px] py-2 text-xs font-semibold rounded-pill transition-all duration-[120ms] ease-standard flex items-center justify-center space-x-2 cursor-pointer focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-primary-500',
                        activeTab === 'search'
                            ? 'bg-navy-800 text-white shadow-sm'
                            : 'bg-transparent text-text-muted hover:text-text hover:bg-primary-100/60',
                    ]"
                >
                    <Search class="h-4 w-4" />
                    <span>Cari Pelanggan</span>
                </button>
                <button
                    type="button"
                    @click="activeTab = 'create'"
                    :class="[
                        'flex-1 min-h-[42px] py-2 text-xs font-semibold rounded-pill transition-all duration-[120ms] ease-standard flex items-center justify-center space-x-2 cursor-pointer focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-primary-500',
                        activeTab === 'create'
                            ? 'bg-navy-800 text-white shadow-sm'
                            : 'bg-transparent text-text-muted hover:text-text hover:bg-primary-100/60',
                    ]"
                >
                    <UserPlus class="h-4 w-4" />
                    <span>Tambah Pelanggan Baru</span>
                </button>
            </div>

            <div v-if="activeTab === 'search'" class="p-5 flex-1 overflow-y-auto space-y-4">
                <div class="relative">
                    <Search class="absolute left-3.5 top-1/2 -translate-y-1/2 h-5 w-5 text-text-muted" />
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Ketik nama atau nomor WhatsApp (mis: 0812...)..."
                        class="min-h-[44px] w-full rounded-md border border-border bg-surface pl-11 pr-10 py-2.5 text-sm font-medium text-text placeholder:text-text-muted focus:outline-none focus:border-primary-600 focus:ring-3 focus:ring-primary-100"
                        autofocus
                    />
                    <div v-if="isSearching" class="absolute right-3.5 top-1/2 -translate-y-1/2">
                        <Loader2 class="h-4 w-4 animate-spin text-primary-600" />
                    </div>
                </div>

                <button
                    type="button"
                    @click="selectWalkIn"
                    class="w-full text-left p-3.5 rounded-lg border border-border bg-bg hover:bg-primary-100/40 transition-all duration-[120ms] ease-standard flex items-center justify-between cursor-pointer group focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-primary-500"
                >
                    <div class="flex items-center space-x-3">
                        <div class="h-10 w-10 rounded-full bg-primary-100 text-primary-700 flex items-center justify-center font-bold">
                            <UserIcon class="h-5 w-5" />
                        </div>
                        <div>
                            <span class="font-bold text-sm text-text block group-hover:text-primary-700 transition-colors">Pelanggan Umum (Walk-in)</span>
                            <span class="text-xs text-text-muted font-medium">Pelanggan langsung / tanpa simpan kontak</span>
                        </div>
                    </div>
                    <span class="text-xs font-semibold text-primary-700 px-3 py-1.5 rounded-pill bg-surface border border-border group-hover:bg-primary-600 group-hover:text-white group-hover:border-primary-600 transition-all duration-[120ms] ease-standard">
                        Pilih
                    </span>
                </button>

                <div class="space-y-2">
                    <div v-if="searchQuery.trim().length > 0 && !isSearching && searchResults.length === 0" class="text-center py-8 text-text-muted font-medium text-xs bg-bg rounded-lg border border-dashed border-border p-5">
                        Tidak ada pelanggan dengan kata kunci "{{ searchQuery }}".
                        <div class="mt-3">
                            <button
                                type="button"
                                @click="activeTab = 'create'; newName = searchQuery"
                                class="bg-accent-500 hover:bg-accent-600 text-navy-950 font-bold text-xs px-4 py-2 rounded-pill shadow-sm transition-all duration-[120ms] ease-standard inline-block cursor-pointer focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-primary-500"
                            >
                                + Daftarkan Sekarang
                            </button>
                        </div>
                    </div>

                    <div
                        v-for="cust in searchResults"
                        :key="cust.id ?? cust.phone"
                        @click="selectCustomer(cust)"
                        class="p-3.5 rounded-lg border border-border bg-surface hover:bg-bg cursor-pointer transition-all duration-[120ms] ease-standard flex items-start justify-between shadow-sm hover:border-primary-500 group focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-primary-500"
                    >
                        <div class="space-y-1">
                            <div class="flex items-center space-x-2">
                                <span class="font-bold text-sm text-text group-hover:text-primary-700 transition-colors">{{ cust.name }}</span>
                                <span
                                    v-if="Number(cust.outstanding_receivables ?? 0) > 0"
                                    class="inline-flex items-center space-x-1 px-2 py-0.5 rounded-pill text-[10px] font-bold bg-[#FDE3E4] text-[#B3262B]"
                                >
                                    <AlertCircle class="h-3 w-3 stroke-[2.5]" />
                                    <span>Piutang: {{ formatRupiah(cust.outstanding_receivables) }}</span>
                                </span>
                            </div>

                            <div class="flex items-center space-x-3 text-xs font-medium text-text-muted">
                                <span class="flex items-center space-x-1">
                                    <Phone class="h-3.5 w-3.5 text-text-muted" />
                                    <span class="font-mono font-medium text-text">{{ cust.phone }}</span>
                                </span>
                                <span>•</span>
                                <span>{{ cust.total_orders_count ?? 0 }} Transaksi</span>
                            </div>

                            <div v-if="cust.address" class="text-[11px] font-medium text-text-muted flex items-center space-x-1">
                                <MapPin class="h-3 w-3 text-text-muted" />
                                <span class="truncate max-w-[280px]">{{ cust.address }}</span>
                            </div>
                        </div>

                        <div class="h-8 w-8 rounded-full bg-bg border border-border text-text-muted group-hover:bg-success group-hover:text-white group-hover:border-success flex items-center justify-center transition-all duration-[120ms] ease-standard">
                            <Check class="h-4 w-4 stroke-[2.5]" />
                        </div>
                    </div>
                </div>
            </div>

            <div v-else class="p-5 flex-1 overflow-y-auto space-y-4">
                <div v-if="formError" class="p-3 rounded-md bg-[#FDE3E4] border-l-4 border-l-danger text-[#B3262B] text-xs font-semibold flex items-center space-x-2">
                    <AlertCircle class="h-4 w-4 shrink-0 stroke-[2.5]" />
                    <span>{{ formError }}</span>
                </div>

                <div class="space-y-3">
                    <div>
                        <label class="overline-label text-text mb-1">Nama Lengkap *</label>
                        <input
                            v-model="newName"
                            type="text"
                            placeholder="Contoh: Budi Santoso"
                            class="min-h-[44px] w-full rounded-md border border-border bg-surface px-3.5 py-2 text-sm font-medium text-text placeholder:text-text-muted focus:outline-none focus:border-primary-600 focus:ring-3 focus:ring-primary-100"
                        />
                    </div>

                    <div>
                        <label class="overline-label text-text mb-1">Nomor WhatsApp *</label>
                        <input
                            v-model="newPhone"
                            type="tel"
                            placeholder="Contoh: 081234567890"
                            class="min-h-[44px] w-full rounded-md border border-border bg-surface px-3.5 py-2 text-sm font-mono font-medium text-text placeholder:text-text-muted focus:outline-none focus:border-primary-600 focus:ring-3 focus:ring-primary-100"
                        />
                        <span class="text-[11px] font-medium text-text-muted mt-1 block">Format: 08xxx atau 628xxx (otomatis dinormalisasi).</span>
                    </div>

                    <div>
                        <label class="overline-label text-text mb-1">Alamat (Opsional)</label>
                        <textarea
                            v-model="newAddress"
                            rows="2"
                            placeholder="Contoh: Jl. Merpati No. 10, RT 02/05"
                            class="w-full rounded-md border border-border bg-surface px-3.5 py-2 text-sm font-medium text-text placeholder:text-text-muted focus:outline-none focus:border-primary-600 focus:ring-3 focus:ring-primary-100"
                        ></textarea>
                    </div>

                    <div>
                        <label class="overline-label text-text mb-1">Catatan Pelanggan (Opsional)</label>
                        <input
                            v-model="newNotes"
                            type="text"
                            placeholder="Mis: Alergi deterjen tertentu, langganan VIP"
                            class="min-h-[44px] w-full rounded-md border border-border bg-surface px-3.5 py-2 text-sm font-medium text-text placeholder:text-text-muted focus:outline-none focus:border-primary-600 focus:ring-3 focus:ring-primary-100"
                        />
                    </div>
                </div>

                <div class="pt-2 flex items-center space-x-3">
                    <button
                        type="button"
                        @click="activeTab = 'search'"
                        class="min-h-[44px] px-5 py-2 rounded-pill border-[1.5px] border-navy-900 bg-surface text-text text-xs font-semibold hover:bg-primary-100/50 transition-all duration-[120ms] ease-standard cursor-pointer focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-primary-500"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        :disabled="isSubmitting"
                        @click="handleCreateCustomer"
                        class="min-h-[44px] flex-1 px-5 py-2 rounded-pill bg-accent-500 hover:bg-accent-600 text-navy-950 text-xs font-bold uppercase tracking-wider shadow-sm disabled:opacity-50 disabled:pointer-events-none flex items-center justify-center space-x-2 cursor-pointer transition-all duration-[120ms] ease-standard focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-primary-500"
                    >
                        <Loader2 v-if="isSubmitting" class="h-4 w-4 animate-spin text-navy-950" />
                        <span>Simpan & Pilih Pelanggan</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
