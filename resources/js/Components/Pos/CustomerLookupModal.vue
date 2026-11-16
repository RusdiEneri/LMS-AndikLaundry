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
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs p-3 sm:p-4"
        @click.self="closeModal"
    >
        <div class="bg-[#FFFDF5] rounded-[12px] max-w-lg w-full border-[3.5px] border-black shadow-[8px_8px_0px_#000] overflow-hidden flex flex-col max-h-[92vh]">
            <!-- Header: Neubrutalism Yellow Banner -->
            <div class="px-5 py-3.5 bg-[#FFEB3B] border-b-[3.5px] border-black flex items-center justify-between">
                <div>
                    <div class="flex items-center space-x-2">
                        <span class="text-[10px] font-black uppercase tracking-wider bg-black text-[#FFEB3B] px-2 py-0.5 rounded-[4px] border border-black">
                            Data Pelanggan
                        </span>
                    </div>
                    <h3 class="font-black text-lg text-black tracking-tight mt-0.5">Pencarian & Profil Pelanggan</h3>
                </div>
                <button
                    type="button"
                    @click="closeModal"
                    class="h-8 w-8 rounded-[6px] border-2 border-black bg-white text-black hover:bg-[#FF5252] hover:text-white shadow-[2px_2px_0px_#000] active:translate-x-0.5 active:translate-y-0.5 active:shadow-none flex items-center justify-center transition-all cursor-pointer"
                    aria-label="Tutup Modal"
                >
                    <X class="h-4 w-4 stroke-[3]" />
                </button>
            </div>

            <!-- Tabs: Cari Pelanggan vs Tambah Baru -->
            <div class="flex border-b-[3px] border-black bg-white p-2 gap-2">
                <button
                    type="button"
                    @click="activeTab = 'search'"
                    :class="[
                        'flex-1 min-h-[44px] py-2 text-xs font-black uppercase tracking-wider rounded-[8px] transition-all flex items-center justify-center space-x-2 cursor-pointer border-2 border-black active:translate-x-0.5 active:translate-y-0.5',
                        activeTab === 'search'
                            ? 'bg-black text-[#FFEB3B] shadow-[2px_2px_0px_#000]'
                            : 'bg-white text-black/70 hover:text-black hover:bg-[#FFF9D2]',
                    ]"
                >
                    <Search class="h-4 w-4 stroke-[2.5]" />
                    <span>Cari Pelanggan</span>
                </button>
                <button
                    type="button"
                    @click="activeTab = 'create'"
                    :class="[
                        'flex-1 min-h-[44px] py-2 text-xs font-black uppercase tracking-wider rounded-[8px] transition-all flex items-center justify-center space-x-2 cursor-pointer border-2 border-black active:translate-x-0.5 active:translate-y-0.5',
                        activeTab === 'create'
                            ? 'bg-black text-[#FFEB3B] shadow-[2px_2px_0px_#000]'
                            : 'bg-white text-black/70 hover:text-black hover:bg-[#FFF9D2]',
                    ]"
                >
                    <UserPlus class="h-4 w-4 stroke-[2.5]" />
                    <span>Tambah Pelanggan Baru</span>
                </button>
            </div>

            <!-- Tab 1: Search -->
            <div v-if="activeTab === 'search'" class="p-4 sm:p-5 flex-1 overflow-y-auto space-y-4">
                <!-- Search Input -->
                <div class="relative">
                    <Search class="absolute left-3.5 top-1/2 -translate-y-1/2 h-5 w-5 text-black stroke-[2.5]" />
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Ketik nama atau nomor WhatsApp (mis: 0812...)..."
                        class="min-h-[44px] w-full rounded-[8px] border-2 border-black bg-white pl-11 pr-10 py-2.5 text-sm font-bold text-black placeholder:text-black/40 shadow-[3px_3px_0px_#000] focus:outline-none focus:shadow-[4px_4px_0px_#000]"
                        autofocus
                    />
                    <div v-if="isSearching" class="absolute right-3.5 top-1/2 -translate-y-1/2">
                        <Loader2 class="h-4 w-4 animate-spin text-black stroke-[2.5]" />
                    </div>
                </div>

                <!-- Walk-in Shortcut Option -->
                <button
                    type="button"
                    @click="selectWalkIn"
                    class="w-full text-left p-3.5 rounded-[8px] border-2 border-black bg-[#FFF9D2] hover:bg-[#FFEB3B] shadow-[3px_3px_0px_#000] active:translate-x-0.5 active:translate-y-0.5 active:shadow-none transition-all flex items-center justify-between cursor-pointer group"
                >
                    <div class="flex items-center space-x-3">
                        <div class="h-9 w-9 rounded-[6px] bg-black text-[#FFEB3B] border-2 border-black flex items-center justify-center font-black text-xs shadow-[1.5px_1.5px_0px_#000]">
                            <UserIcon class="h-4 w-4" />
                        </div>
                        <div>
                            <span class="font-black text-sm text-black block">Pelanggan Umum (Walk-in)</span>
                            <span class="text-xs font-bold text-black/70">Pelanggan langsung / tanpa simpan kontak</span>
                        </div>
                    </div>
                    <span class="text-xs font-black uppercase tracking-wider text-black px-3 py-1.5 rounded-[6px] bg-white border-2 border-black shadow-[2px_2px_0px_#000] group-hover:bg-black group-hover:text-[#FFEB3B] transition-colors">
                        Pilih
                    </span>
                </button>

                <!-- Search Results List -->
                <div class="space-y-2">
                    <div v-if="searchQuery.trim().length > 0 && !isSearching && searchResults.length === 0" class="text-center py-6 text-black font-bold text-xs bg-white rounded-[8px] border-2 border-dashed border-black/40 p-4">
                        Tidak ada pelanggan dengan kata kunci "{{ searchQuery }}".
                        <div class="mt-2">
                            <button
                                type="button"
                                @click="activeTab = 'create'; newName = searchQuery"
                                class="text-black font-black uppercase text-xs hover:bg-[#FFEB3B] px-3 py-1.5 rounded-[6px] border-2 border-black shadow-[2px_2px_0px_#000] inline-block cursor-pointer bg-white"
                            >
                                + Daftarkan Sekarang
                            </button>
                        </div>
                    </div>

                    <div
                        v-for="cust in searchResults"
                        :key="cust.id ?? cust.phone"
                        @click="selectCustomer(cust)"
                        class="p-3.5 rounded-[8px] border-2 border-black bg-white hover:bg-[#FFF9D2] cursor-pointer transition-all flex items-start justify-between shadow-[2px_2px_0px_#000] hover:shadow-[3px_3px_0px_#000] group active:translate-x-0.5 active:translate-y-0.5"
                    >
                        <div class="space-y-1">
                            <div class="flex items-center space-x-2">
                                <span class="font-black text-sm text-black">{{ cust.name }}</span>
                                <!-- Piutang / Debt Badge if any -->
                                <span
                                    v-if="Number(cust.outstanding_receivables ?? 0) > 0"
                                    class="inline-flex items-center space-x-1 px-2 py-0.5 rounded-[4px] text-[10px] font-black bg-[#FF5252] text-black border-2 border-black shadow-[1px_1px_0px_#000]"
                                >
                                    <AlertCircle class="h-3 w-3 stroke-[2.5]" />
                                    <span>Piutang: {{ formatRupiah(cust.outstanding_receivables) }}</span>
                                </span>
                            </div>

                            <div class="flex items-center space-x-3 text-xs font-bold text-black/80">
                                <span class="flex items-center space-x-1">
                                    <Phone class="h-3.5 w-3.5 text-black stroke-[2.5]" />
                                    <span class="font-mono font-bold text-black">{{ cust.phone }}</span>
                                </span>
                                <span>•</span>
                                <span>{{ cust.total_orders_count ?? 0 }} Transaksi</span>
                            </div>

                            <div v-if="cust.address" class="text-[11px] font-bold text-black/70 flex items-center space-x-1">
                                <MapPin class="h-3 w-3 text-black stroke-[2.5]" />
                                <span class="truncate max-w-[280px]">{{ cust.address }}</span>
                            </div>
                        </div>

                        <div class="h-8 w-8 rounded-[6px] bg-white border-2 border-black shadow-[1.5px_1.5px_0px_#000] group-hover:bg-[#22C55E] group-hover:text-black flex items-center justify-center transition-all">
                            <Check class="h-4 w-4 stroke-[3]" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab 2: Create Customer -->
            <div v-else class="p-4 sm:p-5 flex-1 overflow-y-auto space-y-4">
                <div v-if="formError" class="p-3 rounded-[8px] bg-[#FF5252]/15 border-2 border-black shadow-[3px_3px_0px_#000] text-black text-xs font-bold flex items-center space-x-2">
                    <AlertCircle class="h-4 w-4 shrink-0 stroke-[2.5] text-[#FF5252]" />
                    <span>{{ formError }}</span>
                </div>

                <div class="space-y-3">
                    <div>
                        <label class="block text-xs font-black uppercase tracking-wider text-black mb-1">Nama Lengkap *</label>
                        <input
                            v-model="newName"
                            type="text"
                            placeholder="Contoh: Budi Santoso"
                            class="min-h-[44px] w-full rounded-[8px] border-2 border-black bg-white px-3 py-2 text-sm font-bold text-black placeholder:text-black/40 shadow-[2px_2px_0px_#000] focus:outline-none focus:shadow-[3px_3px_0px_#000]"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-black uppercase tracking-wider text-black mb-1">Nomor WhatsApp *</label>
                        <input
                            v-model="newPhone"
                            type="tel"
                            placeholder="Contoh: 081234567890"
                            class="min-h-[44px] w-full rounded-[8px] border-2 border-black bg-white px-3 py-2 text-sm font-mono font-bold text-black placeholder:text-black/40 shadow-[2px_2px_0px_#000] focus:outline-none focus:shadow-[3px_3px_0px_#000]"
                        />
                        <span class="text-[11px] font-bold text-black/70 mt-1 block">Format: 08xxx atau 628xxx (otomatis dinormalisasi).</span>
                    </div>

                    <div>
                        <label class="block text-xs font-black uppercase tracking-wider text-black mb-1">Alamat (Opsional)</label>
                        <textarea
                            v-model="newAddress"
                            rows="2"
                            placeholder="Contoh: Jl. Merpati No. 10, RT 02/05"
                            class="w-full rounded-[8px] border-2 border-black bg-white px-3 py-2 text-sm font-bold text-black placeholder:text-black/40 shadow-[2px_2px_0px_#000] focus:outline-none focus:shadow-[3px_3px_0px_#000]"
                        ></textarea>
                    </div>

                    <div>
                        <label class="block text-xs font-black uppercase tracking-wider text-black mb-1">Catatan Pelanggan (Opsional)</label>
                        <input
                            v-model="newNotes"
                            type="text"
                            placeholder="Mis: Alergi deterjen tertentu, langganan VIP"
                            class="min-h-[44px] w-full rounded-[8px] border-2 border-black bg-white px-3 py-2 text-sm font-bold text-black placeholder:text-black/40 shadow-[2px_2px_0px_#000] focus:outline-none focus:shadow-[3px_3px_0px_#000]"
                        />
                    </div>
                </div>

                <div class="pt-2 flex items-center space-x-3">
                    <button
                        type="button"
                        @click="activeTab = 'search'"
                        class="min-h-[44px] px-5 py-2 rounded-[8px] border-2 border-black bg-white text-black text-xs font-black uppercase tracking-wider shadow-[2px_2px_0px_#000] hover:bg-black/5 active:translate-x-0.5 active:translate-y-0.5 active:shadow-none cursor-pointer"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        :disabled="isSubmitting"
                        @click="handleCreateCustomer"
                        class="min-h-[44px] flex-1 px-5 py-2 rounded-[8px] bg-[#22C55E] hover:bg-[#16A34A] text-black hover:text-white text-xs font-black uppercase tracking-wider border-[2.5px] border-black shadow-[4px_4px_0px_#000] active:translate-x-0.5 active:translate-y-0.5 active:shadow-none disabled:opacity-50 flex items-center justify-center space-x-2 cursor-pointer transition-all"
                    >
                        <Loader2 v-if="isSubmitting" class="h-4 w-4 animate-spin text-black stroke-[2.5]" />
                        <span>Simpan & Pilih Pelanggan</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
