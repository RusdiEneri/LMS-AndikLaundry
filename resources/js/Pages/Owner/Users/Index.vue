<script setup lang="ts">
import { ref, watch, computed } from 'vue';
import { Head, Link, useForm, usePage, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    Users,
    UserPlus,
    Search,
    Shield,
    CheckCircle2,
    XCircle,
    Edit2,
    Trash2,
    Mail,
    Phone,
    X,
    AlertTriangle,
} from 'lucide-vue-next';
import Button from '@/Components/ui/button/Button.vue';
import type { PageProps, User as UserItem } from '@/types';

interface PaginationLinks {
    url: string | null;
    label: string;
    active: boolean;
}

const props = defineProps<{
    users: {
        data: UserItem[];
        links: PaginationLinks[];
        total: number;
        current_page: number;
        last_page: number;
        from: number;
        to: number;
    };
    filters?: {
        search?: string;
        role?: string;
        is_active?: string;
    };
}>();

const page = usePage<PageProps>();
const currentUser = computed(() => page.props.auth?.user);
const flashSuccess = computed(() => page.props.flash?.success);
const flashError = computed(() => page.props.flash?.error);

// Search & Filter State
const search = ref(props.filters?.search ?? '');
const selectedRole = ref(props.filters?.role ?? '');

watch([search, selectedRole], ([newSearch, newRole]) => {
    router.get(
        '/owner/users',
        {
            search: newSearch || undefined,
            role: newRole || undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        }
    );
});

// Modal State
const isCreateOpen = ref(false);
const isEditOpen = ref(false);
const isDeleteOpen = ref(false);
const userToEdit = ref<UserItem | null>(null);
const userToDelete = ref<UserItem | null>(null);

// Create User Form
const createForm = useForm({
    name: '',
    email: '',
    phone: '',
    role: 'cashier',
    password: '',
    password_confirmation: '',
    is_active: true,
});

const openCreateModal = () => {
    createForm.reset();
    createForm.clearErrors();
    isCreateOpen.value = true;
};

const submitCreate = () => {
    createForm.post('/owner/users', {
        onSuccess: () => {
            isCreateOpen.value = false;
            createForm.reset();
        },
    });
};

// Edit User Form
const editForm = useForm({
    name: '',
    email: '',
    phone: '',
    role: 'cashier',
    password: '',
    password_confirmation: '',
    is_active: true,
});

const openEditModal = (user: UserItem) => {
    userToEdit.value = user;
    editForm.clearErrors();
    editForm.name = user.name;
    editForm.email = user.email;
    editForm.phone = user.phone;
    editForm.role = user.role;
    editForm.is_active = Boolean(user.is_active);
    editForm.password = '';
    editForm.password_confirmation = '';
    isEditOpen.value = true;
};

const submitEdit = () => {
    if (!userToEdit.value) return;
    editForm.put(`/owner/users/${userToEdit.value.id}`, {
        onSuccess: () => {
            isEditOpen.value = false;
            userToEdit.value = null;
        },
    });
};

// Toggle Status Action
const toggleUserStatus = (user: UserItem) => {
    if (user.id === currentUser.value?.id) {
        alert('Anda tidak dapat menonaktifkan akun Anda sendiri.');
        return;
    }
    router.patch(`/owner/users/${user.id}/toggle-status`, {}, { preserveScroll: true });
};

// Delete Action
const openDeleteModal = (user: UserItem) => {
    if (user.id === currentUser.value?.id) {
        alert('Anda tidak dapat menghapus akun Anda sendiri.');
        return;
    }
    userToDelete.value = user;
    isDeleteOpen.value = true;
};

const confirmDelete = () => {
    if (!userToDelete.value) return;
    router.delete(`/owner/users/${userToDelete.value.id}`, {
        onSuccess: () => {
            isDeleteOpen.value = false;
            userToDelete.value = null;
        },
    });
};

// Role styling helpers per DESIGN.md
const getRoleBadgeClass = (role: string) => {
    switch (role) {
        case 'owner':
            return 'bg-primary-100 text-primary-700 border-primary-200';
        case 'cashier':
            return 'bg-accent-100 text-accent-700 border-accent-200';
        case 'operator':
            return 'bg-warning/15 text-warning border-warning/20';
        default:
            return 'bg-bg text-text-muted border-border';
    }
};

const getRoleLabel = (role: string) => {
    switch (role) {
        case 'owner':
            return 'Owner Toko';
        case 'cashier':
            return 'Kasir POS';
        case 'operator':
            return 'Operator Cuci';
        default:
            return role;
    }
};
</script>

<template>
    <AppLayout title="Manajemen Pengguna">
        <div class="space-y-6">
            <!-- Header Title Banner -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-surface p-5 rounded-lg border border-border shadow-xs">
                <div>
                    <div class="flex items-center gap-2">
                        <h1 class="text-xl font-bold font-heading text-text">Manajemen Pengguna & Staf</h1>
                        <span class="px-2.5 py-0.5 rounded-pill text-xs font-semibold bg-primary-100 text-primary-700 border border-primary-200">
                            RBAC Security
                        </span>
                    </div>
                    <p class="text-xs text-text-muted mt-1">Kelola akun kasir, operator produksi, dan hak akses sistem</p>
                </div>
            </div>

            <!-- Flash Banners -->
            <div
                v-if="flashSuccess"
                class="flex items-center gap-3 p-4 rounded-lg bg-success/10 border border-success/20 text-success font-medium text-sm"
            >
                <CheckCircle2 class="w-5 h-5 shrink-0 stroke-[2.5]" />
                <span>{{ flashSuccess }}</span>
            </div>

            <div
                v-if="flashError"
                class="flex items-center gap-3 p-4 rounded-lg bg-danger/10 border border-danger/20 text-danger font-medium text-sm"
            >
                <AlertTriangle class="w-5 h-5 shrink-0 stroke-[2.5]" />
                <span>{{ flashError }}</span>
            </div>

            <!-- Header Controls: Search, Filter, Add Action -->
            <div class="bg-surface rounded-lg border border-border p-4 shadow-xs flex flex-col sm:flex-row gap-4 items-center justify-between">
                <div class="flex flex-1 items-center gap-3 w-full sm:w-auto">
                    <!-- Search Input -->
                    <div class="relative flex-1 max-w-md">
                        <Search class="w-4 h-4 text-text-muted absolute left-3.5 top-1/2 -translate-y-1/2 stroke-[2]" />
                        <input
                            v-model="search"
                            type="text"
                            placeholder="Cari nama, email, atau no. telepon..."
                            class="w-full pl-10 pr-4 py-2.5 rounded-md border border-border bg-surface text-text placeholder:text-text-muted focus:outline-none focus:border-primary-600 focus:ring-3 focus:ring-primary-100 text-sm min-h-[44px]"
                        />
                    </div>

                    <!-- Role Filter -->
                    <select
                        v-model="selectedRole"
                        class="px-3.5 py-2.5 rounded-md border border-border bg-surface text-text focus:outline-none focus:border-primary-600 focus:ring-3 focus:ring-primary-100 text-sm min-h-[44px]"
                    >
                        <option value="">Semua Peranan</option>
                        <option value="owner">Owner</option>
                        <option value="cashier">Kasir</option>
                        <option value="operator">Operator Cuci</option>
                    </select>
                </div>

                <button
                    type="button"
                    @click="openCreateModal"
                    class="w-full sm:w-auto bg-accent-500 hover:bg-accent-600 text-navy-950 rounded-pill min-h-[44px] px-5 font-bold text-sm flex items-center justify-center gap-2 cursor-pointer shadow-xs transition-all duration-[120ms] ease-standard focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-primary-500"
                >
                    <UserPlus class="w-4 h-4 stroke-[2.5]" />
                    <span>Tambah Staf Baru</span>
                </button>
            </div>

            <!-- Users Data Table -->
            <div class="bg-surface rounded-lg border border-border shadow-xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-text">
                        <thead class="bg-bg border-b border-border text-xs font-semibold text-text-muted uppercase tracking-wider">
                            <tr>
                                <th class="px-6 py-4">Nama & Email</th>
                                <th class="px-6 py-4">Kontak Telepon</th>
                                <th class="px-6 py-4">Peranan</th>
                                <th class="px-6 py-4">Status Akun</th>
                                <th class="px-6 py-4 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            <tr v-for="user in users.data" :key="user.id" class="hover:bg-bg/60 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-full bg-primary-100 border border-primary-200 flex items-center justify-center font-bold font-mono text-primary-700 shrink-0">
                                            {{ user.name.slice(0, 2).toUpperCase() }}
                                        </div>
                                        <div>
                                            <div class="font-bold text-text flex items-center gap-2">
                                                {{ user.name }}
                                                <span
                                                    v-if="user.id === currentUser?.id"
                                                    class="text-[10px] px-1.5 py-0.5 rounded-pill bg-accent-100 text-accent-700 font-bold"
                                                >
                                                    Anda
                                                </span>
                                            </div>
                                            <div class="text-xs text-text-muted flex items-center gap-1 mt-0.5">
                                                <Mail class="w-3 h-3 text-text-muted" />
                                                {{ user.email }}
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <td class="px-6 py-4">
                                    <a
                                        :href="`https://wa.me/${user.phone}`"
                                        target="_blank"
                                        class="inline-flex items-center gap-1.5 text-xs font-mono font-medium text-text hover:text-primary-700 transition-colors"
                                    >
                                        <Phone class="w-3.5 h-3.5 text-primary-600" />
                                        {{ user.phone }}
                                    </a>
                                </td>

                                <td class="px-6 py-4">
                                    <span
                                        :class="[
                                            'px-2.5 py-1 rounded-pill text-xs font-semibold border inline-flex items-center gap-1',
                                            getRoleBadgeClass(user.role)
                                        ]"
                                    >
                                        <Shield class="w-3 h-3" />
                                        {{ getRoleLabel(user.role) }}
                                    </span>
                                </td>

                                <td class="px-6 py-4">
                                    <button
                                        type="button"
                                        @click="toggleUserStatus(user)"
                                        :disabled="user.id === currentUser?.id"
                                        :title="user.id === currentUser?.id ? 'Tidak dapat menonaktifkan akun sendiri' : 'Klik untuk ubah status'"
                                        :class="[
                                            'inline-flex items-center gap-1.5 px-3 py-1.5 rounded-pill text-xs font-bold transition-all min-h-[36px]',
                                            user.is_active
                                                ? 'bg-success/10 text-success hover:bg-success/20 border border-success/20'
                                                : 'bg-danger/10 text-danger hover:bg-danger/20 border border-danger/20',
                                            user.id === currentUser?.id ? 'opacity-60 cursor-not-allowed' : 'cursor-pointer'
                                        ]"
                                    >
                                        <CheckCircle2 v-if="user.is_active" class="w-3.5 h-3.5 stroke-[2.5]" />
                                        <XCircle v-else class="w-3.5 h-3.5 stroke-[2.5]" />
                                        <span>{{ user.is_active ? 'Aktif' : 'Non-Aktif' }}</span>
                                    </button>
                                </td>

                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <button
                                            type="button"
                                            @click="openEditModal(user)"
                                            class="p-2 rounded-md text-text-muted hover:text-text hover:bg-bg min-h-[44px] min-w-[44px] inline-flex items-center justify-center transition-colors cursor-pointer"
                                            title="Ubah Data"
                                        >
                                            <Edit2 class="w-4 h-4" />
                                        </button>

                                        <button
                                            type="button"
                                            @click="openDeleteModal(user)"
                                            :disabled="user.id === currentUser?.id"
                                            :class="[
                                                'p-2 rounded-md min-h-[44px] min-w-[44px] inline-flex items-center justify-center transition-colors',
                                                user.id === currentUser?.id
                                                    ? 'text-text-muted/40 cursor-not-allowed'
                                                    : 'text-danger hover:text-danger hover:bg-danger/10 cursor-pointer'
                                            ]"
                                            :title="user.id === currentUser?.id ? 'Akun Anda tidak dapat dihapus' : 'Hapus Staf'"
                                        >
                                            <Trash2 class="w-4 h-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="users.data.length === 0">
                                <td colspan="5" class="px-6 py-12 text-center text-text-muted">
                                    <Users class="w-10 h-10 text-text-muted/50 mx-auto mb-3" />
                                    <div class="font-semibold text-text">Tidak ada pengguna ditemukan</div>
                                    <p class="text-xs text-text-muted mt-1">Coba sesuaikan kata kunci pencarian atau filter peranan.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Footer -->
                <div v-if="users.total > 0" class="border-t border-border px-6 py-4 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-text-muted">
                    <div>
                        Menampilkan <span class="font-semibold text-text">{{ users.from }}</span> hingga <span class="font-semibold text-text">{{ users.to }}</span> dari <span class="font-semibold text-text">{{ users.total }}</span> staf
                    </div>
                    <div class="flex items-center gap-1">
                        <Link
                            v-for="(link, idx) in users.links"
                            :key="idx"
                            :href="link.url ?? '#'"
                            :class="[
                                'px-3 py-1.5 rounded-md min-h-[36px] inline-flex items-center justify-center transition font-medium',
                                link.active ? 'bg-primary-600 text-white font-bold' : 'hover:bg-bg text-text',
                                !link.url ? 'opacity-40 pointer-events-none' : ''
                            ]"
                            v-html="link.label"
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- Create User Dialog Modal -->
        <div v-if="isCreateOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-navy-950/60 backdrop-blur-xs p-4">
            <div class="bg-surface rounded-lg max-w-lg w-full shadow-xl overflow-hidden border border-border">
                <div class="px-6 py-4 border-b border-border flex items-center justify-between">
                    <h3 class="font-bold text-lg text-text flex items-center gap-2 font-heading">
                        <UserPlus class="w-5 h-5 text-primary-600" />
                        Tambah Pengguna Staf Baru
                    </h3>
                    <button @click="isCreateOpen = false" class="text-text-muted hover:text-text p-1 rounded-md cursor-pointer">
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <form @submit.prevent="submitCreate" class="p-6 space-y-4">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-text mb-1">Nama Lengkap *</label>
                        <input
                            v-model="createForm.name"
                            type="text"
                            required
                            class="w-full px-3.5 py-2 rounded-md border border-border bg-surface text-text placeholder:text-text-muted focus:outline-none focus:border-primary-600 focus:ring-3 focus:ring-primary-100 text-sm min-h-[44px]"
                            placeholder="Contoh: Siti Aminah"
                        />
                        <div v-if="createForm.errors.name" class="text-xs text-danger mt-1 font-semibold">{{ createForm.errors.name }}</div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-text mb-1">Alamat Email *</label>
                            <input
                                v-model="createForm.email"
                                type="email"
                                required
                                class="w-full px-3.5 py-2 rounded-md border border-border bg-surface text-text placeholder:text-text-muted focus:outline-none focus:border-primary-600 focus:ring-3 focus:ring-primary-100 text-sm min-h-[44px]"
                                placeholder="siti@andiklaundry.test"
                            />
                            <div v-if="createForm.errors.email" class="text-xs text-danger mt-1 font-semibold">{{ createForm.errors.email }}</div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-text mb-1">No. WhatsApp *</label>
                            <input
                                v-model="createForm.phone"
                                type="text"
                                required
                                class="w-full px-3.5 py-2 rounded-md border border-border bg-surface text-text font-mono placeholder:text-text-muted focus:outline-none focus:border-primary-600 focus:ring-3 focus:ring-primary-100 text-sm min-h-[44px]"
                                placeholder="0812-3456-7890"
                            />
                            <div v-if="createForm.errors.phone" class="text-xs text-danger mt-1 font-semibold">{{ createForm.errors.phone }}</div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-text mb-1">Peranan Akses *</label>
                        <select
                            v-model="createForm.role"
                            required
                            class="w-full px-3.5 py-2 rounded-md border border-border bg-surface text-text focus:outline-none focus:border-primary-600 focus:ring-3 focus:ring-primary-100 text-sm min-h-[44px]"
                        >
                            <option value="cashier">Kasir POS (Front-Desk)</option>
                            <option value="operator">Operator Produksi (Cuci / Finishing)</option>
                            <option value="owner">Owner (Hak Penuh)</option>
                        </select>
                        <div v-if="createForm.errors.role" class="text-xs text-danger mt-1 font-semibold">{{ createForm.errors.role }}</div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-text mb-1">Kata Sandi *</label>
                            <input
                                v-model="createForm.password"
                                type="password"
                                required
                                minlength="8"
                                class="w-full px-3.5 py-2 rounded-md border border-border bg-surface text-text placeholder:text-text-muted focus:outline-none focus:border-primary-600 focus:ring-3 focus:ring-primary-100 text-sm min-h-[44px]"
                                placeholder="Min. 8 karakter"
                            />
                            <div v-if="createForm.errors.password" class="text-xs text-danger mt-1 font-semibold">{{ createForm.errors.password }}</div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-text mb-1">Konfirmasi Sandi *</label>
                            <input
                                v-model="createForm.password_confirmation"
                                type="password"
                                required
                                class="w-full px-3.5 py-2 rounded-md border border-border bg-surface text-text placeholder:text-text-muted focus:outline-none focus:border-primary-600 focus:ring-3 focus:ring-primary-100 text-sm min-h-[44px]"
                                placeholder="Ulangi kata sandi"
                            />
                        </div>
                    </div>

                    <div class="pt-2 flex items-center justify-between border-t border-border">
                        <label class="flex items-center gap-2 cursor-pointer select-none">
                            <input
                                type="checkbox"
                                v-model="createForm.is_active"
                                class="w-4 h-4 text-primary-600 rounded border-border focus:ring-primary-500"
                            />
                            <span class="text-sm font-medium text-text">Langsung aktifkan akun</span>
                        </label>

                        <div class="flex items-center gap-2">
                            <Button type="button" variant="outline" @click="isCreateOpen = false" class="min-h-[44px] cursor-pointer">Batal</Button>
                            <Button type="submit" :disabled="createForm.processing" class="bg-accent-500 hover:bg-accent-600 text-navy-950 font-bold min-h-[44px] cursor-pointer">
                                {{ createForm.processing ? 'Menyimpan...' : 'Simpan Staf' }}
                            </Button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Edit User Dialog Modal -->
        <div v-if="isEditOpen && userToEdit" class="fixed inset-0 z-50 flex items-center justify-center bg-navy-950/60 backdrop-blur-xs p-4">
            <div class="bg-surface rounded-lg max-w-lg w-full shadow-xl overflow-hidden border border-border">
                <div class="px-6 py-4 border-b border-border flex items-center justify-between">
                    <h3 class="font-bold text-lg text-text flex items-center gap-2 font-heading">
                        <Edit2 class="w-5 h-5 text-primary-600" />
                        Ubah Data Staf: {{ userToEdit.name }}
                    </h3>
                    <button @click="isEditOpen = false" class="text-text-muted hover:text-text p-1 rounded-md cursor-pointer">
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <form @submit.prevent="submitEdit" class="p-6 space-y-4">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-text mb-1">Nama Lengkap *</label>
                        <input
                            v-model="editForm.name"
                            type="text"
                            required
                            class="w-full px-3.5 py-2 rounded-md border border-border bg-surface text-text placeholder:text-text-muted focus:outline-none focus:border-primary-600 focus:ring-3 focus:ring-primary-100 text-sm min-h-[44px]"
                        />
                        <div v-if="editForm.errors.name" class="text-xs text-danger mt-1 font-semibold">{{ editForm.errors.name }}</div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-text mb-1">Alamat Email *</label>
                            <input
                                v-model="editForm.email"
                                type="email"
                                required
                                class="w-full px-3.5 py-2 rounded-md border border-border bg-surface text-text placeholder:text-text-muted focus:outline-none focus:border-primary-600 focus:ring-3 focus:ring-primary-100 text-sm min-h-[44px]"
                            />
                            <div v-if="editForm.errors.email" class="text-xs text-danger mt-1 font-semibold">{{ editForm.errors.email }}</div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-text mb-1">No. WhatsApp *</label>
                            <input
                                v-model="editForm.phone"
                                type="text"
                                required
                                class="w-full px-3.5 py-2 rounded-md border border-border bg-surface text-text font-mono placeholder:text-text-muted focus:outline-none focus:border-primary-600 focus:ring-3 focus:ring-primary-100 text-sm min-h-[44px]"
                            />
                            <div v-if="editForm.errors.phone" class="text-xs text-danger mt-1 font-semibold">{{ editForm.errors.phone }}</div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-text mb-1">Peranan Akses *</label>
                        <select
                            v-model="editForm.role"
                            :disabled="userToEdit.id === currentUser?.id"
                            class="w-full px-3.5 py-2 rounded-md border border-border bg-surface text-text focus:outline-none focus:border-primary-600 focus:ring-3 focus:ring-primary-100 text-sm min-h-[44px] disabled:opacity-60 disabled:cursor-not-allowed"
                        >
                            <option value="cashier">Kasir POS (Front-Desk)</option>
                            <option value="operator">Operator Produksi (Cuci / Finishing)</option>
                            <option value="owner">Owner (Hak Penuh)</option>
                        </select>
                        <p v-if="userToEdit.id === currentUser?.id" class="text-xs text-warning mt-1 font-medium">
                            Peranan Owner untuk akun login Anda sendiri tidak dapat diturunkan.
                        </p>
                        <div v-if="editForm.errors.role" class="text-xs text-danger mt-1 font-semibold">{{ editForm.errors.role }}</div>
                    </div>

                    <div class="border-t border-border pt-3">
                        <div class="text-xs font-bold uppercase tracking-wider text-text-muted mb-2">Ganti Kata Sandi (Opsional, min. 8 karakter)</div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <input
                                    v-model="editForm.password"
                                    type="password"
                                    class="w-full px-3.5 py-2 rounded-md border border-border bg-surface text-text placeholder:text-text-muted focus:outline-none focus:border-primary-600 focus:ring-3 focus:ring-primary-100 text-sm min-h-[44px]"
                                    placeholder="Kosongkan jika tetap"
                                />
                                <div v-if="editForm.errors.password" class="text-xs text-danger mt-1 font-semibold">{{ editForm.errors.password }}</div>
                            </div>
                            <div>
                                <input
                                    v-model="editForm.password_confirmation"
                                    type="password"
                                    class="w-full px-3.5 py-2 rounded-md border border-border bg-surface text-text placeholder:text-text-muted focus:outline-none focus:border-primary-600 focus:ring-3 focus:ring-primary-100 text-sm min-h-[44px]"
                                    placeholder="Konfirmasi sandi baru"
                                />
                            </div>
                        </div>
                    </div>

                    <div class="pt-2 flex items-center justify-between border-t border-border">
                        <label class="flex items-center gap-2 cursor-pointer select-none">
                            <input
                                type="checkbox"
                                v-model="editForm.is_active"
                                :disabled="userToEdit.id === currentUser?.id"
                                class="w-4 h-4 text-primary-600 rounded border-border focus:ring-primary-500 disabled:opacity-50"
                            />
                            <span class="text-sm font-medium text-text">Akun Aktif</span>
                        </label>

                        <div class="flex items-center gap-2">
                            <Button type="button" variant="outline" @click="isEditOpen = false" class="min-h-[44px] cursor-pointer">Batal</Button>
                            <Button type="submit" :disabled="editForm.processing" class="bg-accent-500 hover:bg-accent-600 text-navy-950 font-bold min-h-[44px] cursor-pointer">
                                {{ editForm.processing ? 'Menyimpan...' : 'Perbarui Staf' }}
                            </Button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Delete User Confirmation Modal -->
        <div v-if="isDeleteOpen && userToDelete" class="fixed inset-0 z-50 flex items-center justify-center bg-navy-950/60 backdrop-blur-xs p-4">
            <div class="bg-surface rounded-lg max-w-md w-full shadow-xl p-6 border border-border space-y-4">
                <div class="flex items-center gap-3 text-danger">
                    <div class="w-10 h-10 rounded-full bg-danger/10 flex items-center justify-center shrink-0">
                        <AlertTriangle class="w-5 h-5 stroke-[2.5]" />
                    </div>
                    <div>
                        <h3 class="font-bold text-text font-heading">Konfirmasi Hapus Staf</h3>
                        <p class="text-xs text-text-muted">Tindakan ini akan menonaktifkan akun staf terkait.</p>
                    </div>
                </div>

                <p class="text-sm text-text">
                    Apakah Anda yakin ingin menghapus akun staf <span class="font-bold text-text">{{ userToDelete.name }}</span> ({{ userToDelete.email }})?
                </p>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-border">
                    <Button type="button" variant="outline" @click="isDeleteOpen = false" class="min-h-[44px] cursor-pointer">Batal</Button>
                    <button type="button" @click="confirmDelete" class="bg-danger hover:bg-danger/90 text-white rounded-md min-h-[44px] px-4 font-bold text-sm cursor-pointer transition-colors">
                        Ya, Hapus Staf
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
