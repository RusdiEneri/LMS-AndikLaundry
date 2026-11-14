<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    Store,
    Sliders,
    Coins,
    Printer,
    MessageSquare,
    Save,
    CheckCircle2,
    AlertCircle,
    Send,
    RefreshCw,
    Eye,
    EyeOff,
    ExternalLink,
    Sparkles,
    Radio,
    Copy,
    Check,
    QrCode,
    Smartphone,
    KeyRound,
    LogOut,
} from 'lucide-vue-next';
import Button from '@/Components/ui/button/Button.vue';
import type { PageProps } from '@/types';

interface SettingsDictionary {
    store?: Record<string, any>;
    operational?: Record<string, any>;
    financial?: Record<string, any>;
    printer?: Record<string, any>;
    whatsapp?: Record<string, any>;
    [key: string]: Record<string, any> | undefined;
}

const props = defineProps<{
    settings: SettingsDictionary;
}>();

const page = usePage<PageProps>();
const flashSuccess = computed(() => page.props.flash?.success);
const flashError = computed(() => page.props.flash?.error);

// Active Tab state
type TabKey = 'store' | 'operational' | 'financial' | 'printer' | 'whatsapp';
const activeTab = ref<TabKey>('store');

const tabs = [
    { key: 'store' as TabKey, label: 'Identitas Outlet', icon: Store, desc: 'Profil toko & footer cetak' },
    { key: 'operational' as TabKey, label: 'Operasional', icon: Sliders, desc: 'Batas kas, cas & cuci ulang' },
    { key: 'financial' as TabKey, label: 'Finansial & Pajak', icon: Coins, desc: 'PPN & mata uang' },
    { key: 'printer' as TabKey, label: 'Printer Thermal', icon: Printer, desc: 'Ukuran kertas & cetak nota' },
    { key: 'whatsapp' as TabKey, label: 'WhatsApp Gateway', icon: MessageSquare, desc: 'Mode, gateway & template' },
];

// Default notification templates
const defaultIntakeTemplate = `*{{store_name}}*
Halo Kak *{{customer_name}}*, cucian Anda telah kami terima dengan baik!

📄 *No. Nota:* {{invoice_number}}
🧺 *Rincian Layanan:*
{{items_summary}}💰 *Total Biaya:* {{total_amount}}
{{paid_info}}📌 *Status Pembayaran:* {{status_text}}

🔗 *Pantau Status Cucian & Nota Digital:*
{{receipt_url}}

Terima kasih telah mempercayakan cucian Anda di {{store_name}}!`;

const defaultReadyTemplate = `*{{store_name}}*
Kabar gembira Kak *{{customer_name}}*! Cucian Anda telah *SELESAI* dan siap diambil. ✨

📄 *No. Nota:* {{invoice_number}}
🏷️ *Lokasi Rak:* *{{rack_location}}*
📌 *Status Pembayaran:* {{status_text}}

🔗 *Lihat Nota Digital:*
{{receipt_url}}

Silakan datang ke toko untuk pengambilan. Terima kasih!`;

const defaultReminderTemplate = `*{{store_name}}*
Halo Kak *{{customer_name}}*, sekadar mengingatkan terkait tagihan cucian di {{store_name}}:

📄 *No. Nota:* {{invoice_number}}
💰 *Sisa Tagihan Belum Lunas:* *{{debt_amount}}*

🔗 *Rincian Nota & Rekening Transfer:*
{{receipt_url}}

Pembayaran dapat dilakukan via transfer atau tunai saat mampir ke toko. Terima kasih banyak Kak!`;

// Reactive Form model
const form = useForm({
    // Store
    'store.name': props.settings.store?.['store.name'] ?? 'Andik Laundry',
    'store.phone': props.settings.store?.['store.phone'] ?? '6281234567890',
    'store.address': props.settings.store?.['store.address'] ?? '',
    'store.receipt_footer': props.settings.store?.['store.receipt_footer'] ?? '',

    // Operational
    'operational.default_min_weight': props.settings.operational?.['operational.default_min_weight'] ?? '3.00',
    'operational.customer_credit_ceiling': props.settings.operational?.['operational.customer_credit_ceiling'] ?? '150000.00',
    'operational.default_debt_due_days': props.settings.operational?.['operational.default_debt_due_days'] ?? 7,
    'operational.cashier_petty_cash_limit': props.settings.operational?.['operational.cashier_petty_cash_limit'] ?? '100000.00',
    'operational.max_rewash_limit': props.settings.operational?.['operational.max_rewash_limit'] ?? 2,

    // Financial
    'financial.tax_rate_percent': props.settings.financial?.['financial.tax_rate_percent'] ?? '0.00',
    'financial.currency_code': props.settings.financial?.['financial.currency_code'] ?? 'IDR',

    // Printer
    'printer.paper_width': props.settings.printer?.['printer.paper_width'] ?? '58mm',
    'printer.auto_print': Boolean(props.settings.printer?.['printer.auto_print']),

    // WhatsApp
    'whatsapp.mode': props.settings.whatsapp?.['whatsapp.mode'] || 'automatic',
    'whatsapp.provider': props.settings.whatsapp?.['whatsapp.provider'] || 'wuzapi',
    'whatsapp.server_url': props.settings.whatsapp?.['whatsapp.server_url'] || 'http://localhost:8080',
    'whatsapp.api_key': props.settings.whatsapp?.['whatsapp.api_key'] ?? '',
    'whatsapp.session_id': props.settings.whatsapp?.['whatsapp.session_id'] || (props.settings.store?.['store.phone'] || '6281234567890'),
    'whatsapp.anti_ban_rate_limit': props.settings.whatsapp?.['whatsapp.anti_ban_rate_limit'] ?? 12,
    'whatsapp.template_intake': props.settings.whatsapp?.['whatsapp.template_intake'] || defaultIntakeTemplate,
    'whatsapp.template_ready': props.settings.whatsapp?.['whatsapp.template_ready'] || defaultReadyTemplate,
    'whatsapp.template_reminder': props.settings.whatsapp?.['whatsapp.template_reminder'] || defaultReminderTemplate,
});

// Sync provider when mode changes
function selectMode(mode: 'automatic' | 'manual') {
    form['whatsapp.mode'] = mode;
    form['whatsapp.provider'] = mode === 'automatic' ? 'wuzapi' : 'manual_link';
}

const showApiKey = ref(false);

// Active template editor tab
const activeTemplateTab = ref<'intake' | 'ready' | 'reminder'>('intake');

function resetTemplate(tab: 'intake' | 'ready' | 'reminder') {
    if (tab === 'intake') form['whatsapp.template_intake'] = defaultIntakeTemplate;
    if (tab === 'ready') form['whatsapp.template_ready'] = defaultReadyTemplate;
    if (tab === 'reminder') form['whatsapp.template_reminder'] = defaultReminderTemplate;
}

function insertVariable(variableKey: string) {
    const placeholder = `{{${variableKey}}}`;
    if (activeTemplateTab.value === 'intake') {
        form['whatsapp.template_intake'] += placeholder;
    } else if (activeTemplateTab.value === 'ready') {
        form['whatsapp.template_ready'] += placeholder;
    } else if (activeTemplateTab.value === 'reminder') {
        form['whatsapp.template_reminder'] += placeholder;
    }
}

// Live preview generator
const previewText = computed(() => {
    const store = form['store.name'] || 'Andik Laundry';
    const sample = {
        store_name: store,
        customer_name: 'Budi Santoso',
        invoice_number: 'INV-20261114-0042',
        items_summary: '• Cuci Komplit Reguler (4,50 kg)\n• Bed Cover Sedang (1,00 pcs)\n',
        total_amount: 'Rp 65.000',
        paid_amount: 'Rp 40.000',
        paid_info: '💵 *Telah Dibayar:* Rp 40.000\n',
        debt_amount: 'Rp 25.000',
        outstanding_balance: 'Rp 25.000',
        status_text: '⚠️ Sisa Tagihan: Rp 25.000',
        receipt_url: 'http://localhost:8000/receipts/INV-20261114-0042',
        rack_location: 'Rak B-02',
        staging_rack: 'Rak B-02',
    };

    let template = '';
    if (activeTemplateTab.value === 'intake') {
        template = form['whatsapp.template_intake'];
    } else if (activeTemplateTab.value === 'ready') {
        template = form['whatsapp.template_ready'];
    } else {
        template = form['whatsapp.template_reminder'];
    }

    let rendered = template || '';
    for (const [k, v] of Object.entries(sample)) {
        rendered = rendered.replace(new RegExp(`{{\\s*${k}\\s*}}`, 'g'), () => v);
        rendered = rendered.replace(new RegExp(`{${k}}`, 'g'), () => v);
    }
    return rendered;
});

// CSRF Token Helper
function getCsrfToken(): string {
    const meta = document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement | null;
    if (meta?.content) return meta.content;
    const cookieMatch = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
    return cookieMatch ? decodeURIComponent(cookieMatch[1]) : '';
}

// Gateway Connection Testing State
const isTestingConnection = ref(false);
const connectionTestResult = ref<{
    success?: boolean;
    message?: string;
    mode?: string;
    status?: number;
    data?: any;
    error?: string;
} | null>(null);

async function runConnectionTest() {
    isTestingConnection.value = true;
    connectionTestResult.value = null;

    try {
        const response = await fetch('/owner/settings/whatsapp/test-connection', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken(),
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({
                mode: form['whatsapp.mode'],
                server_url: form['whatsapp.server_url'],
                api_key: form['whatsapp.api_key'],
                session_id: form['whatsapp.session_id'],
            }),
        });

        const data = await response.json();
        connectionTestResult.value = data;
        if (data && data.data) {
            if (data.data.loggedIn || data.data.state === 'authenticated') {
                sessionState.value.isLoggedIn = true;
                sessionState.value.connected = true;
                sessionState.value.jid = data.data.jid || sessionState.value.jid;
            } else {
                sessionState.value.isLoggedIn = false;
                if (data.data.qr_image) {
                    sessionState.value.qr_image = data.data.qr_image;
                    sessionState.value.qr = data.data.qr;
                }
            }
        }
    } catch (err: any) {
        connectionTestResult.value = {
            success: false,
            message: err.message || 'Gagal menghubungi server.',
        };
    } finally {
        isTestingConnection.value = false;
    }
}

// WhatsApp Session Pairing State (Scan QR vs OTP Code)
const pairingMethod = ref<'qr' | 'otp'>('qr');
const pairingPhone = ref(
    props.settings.whatsapp?.['whatsapp.session_id'] ||
    props.settings.store?.['store.phone'] ||
    '081234567890'
);
const isConnectingSession = ref(false);
const isDisconnectingSession = ref(false);
const sessionState = ref<{
    isLoggedIn?: boolean;
    connected?: boolean;
    jid?: string;
    qr?: string;
    qr_image?: string;
    pairing_code?: string;
    formatted_code?: string;
    phone?: string;
    instructions?: string[];
}>({
    isLoggedIn: false,
    connected: false,
    jid: '',
    qr: '',
    qr_image: '',
    pairing_code: '',
    formatted_code: '',
});
const sessionError = ref<string | null>(null);
const codeCopied = ref(false);
let sessionPollTimer: any = null;

const setPairingMethod = (method: 'qr' | 'otp') => {
    pairingMethod.value = method;
    sessionError.value = null;
    if (method === 'qr' && !sessionState.value.qr_image && !sessionState.value.isLoggedIn) {
        connectWhatsAppSession();
    }
};

const copyPairingCode = async (code: string) => {
    if (!code) return;
    try {
        await navigator.clipboard.writeText(code.replace('-', ''));
        codeCopied.value = true;
        setTimeout(() => { codeCopied.value = false; }, 2500);
    } catch {
        // fallback
    }
};

const connectWhatsAppSession = async () => {
    isConnectingSession.value = true;
    sessionError.value = null;

    try {
        const payload: Record<string, any> = {
            method: pairingMethod.value,
            server_url: form['whatsapp.server_url'],
            api_key: form['whatsapp.api_key'],
            phone: pairingPhone.value,
        };

        const response = await fetch('/owner/settings/whatsapp/connect-session', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken(),
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify(payload),
        });

        const res = await response.json();
        if (res.success && res.data) {
            const data = res.data;
            sessionState.value = {
                ...sessionState.value,
                connected: !!data.connected,
                isLoggedIn: !!data.loggedIn,
                jid: data.jid || sessionState.value.jid || '',
                qr: data.qr || sessionState.value.qr || '',
                qr_image: data.qr_image || sessionState.value.qr_image || '',
                pairing_code: data.pairing_code || sessionState.value.pairing_code || '',
                formatted_code: data.formatted_code || sessionState.value.formatted_code || '',
                instructions: data.instructions || sessionState.value.instructions,
            };

            if (sessionState.value.isLoggedIn) {
                stopSessionPolling();
            } else {
                startSessionPolling();
            }
        } else {
            sessionError.value = res.message || 'Gagal memulai koneksi WhatsApp.';
        }
    } catch (err: any) {
        sessionError.value = err.message || 'Terjadi kesalahan saat menghubungi server gateway.';
    } finally {
        isConnectingSession.value = false;
    }
};

const disconnectWhatsAppSession = async () => {
    if (!confirm('Apakah Anda yakin ingin memutuskan sesi WhatsApp ini?')) return;

    isDisconnectingSession.value = true;
    sessionError.value = null;

    try {
        const response = await fetch('/owner/settings/whatsapp/disconnect-session', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken(),
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({
                server_url: form['whatsapp.server_url'],
                api_key: form['whatsapp.api_key'],
            }),
        });

        const res = await response.json();
        if (res.success) {
            sessionState.value = {
                isLoggedIn: false,
                connected: false,
                jid: '',
                qr: '',
                qr_image: '',
                pairing_code: '',
                formatted_code: '',
            };
            stopSessionPolling();
            setTimeout(() => {
                connectWhatsAppSession();
            }, 600);
        } else {
            sessionError.value = res.message || 'Gagal memutuskan sesi WhatsApp.';
        }
    } catch (err: any) {
        sessionError.value = err.message || 'Gagal menghubungi server gateway.';
    } finally {
        isDisconnectingSession.value = false;
    }
};

const startSessionPolling = () => {
    stopSessionPolling();
    sessionPollTimer = setInterval(async () => {
        if (sessionState.value.isLoggedIn) {
            stopSessionPolling();
            return;
        }

        try {
            const response = await fetch('/owner/settings/whatsapp/test-connection', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken(),
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({
                    mode: 'automatic',
                    server_url: form['whatsapp.server_url'],
                    api_key: form['whatsapp.api_key'],
                }),
            });

            const res = await response.json();
            const data = res.data;
            if (data) {
                if (data.loggedIn || data.state === 'authenticated') {
                    sessionState.value.isLoggedIn = true;
                    sessionState.value.connected = true;
                    sessionState.value.jid = data.jid || 'WhatsApp Outlet';
                    sessionState.value.qr_image = '';
                    sessionState.value.pairing_code = '';
                    sessionState.value.formatted_code = '';
                    stopSessionPolling();
                } else if (data.qr_image && pairingMethod.value === 'qr') {
                    sessionState.value.qr_image = data.qr_image;
                    sessionState.value.qr = data.qr;
                }
            }
        } catch {
            // silent polling catch
        }
    }, 3000);
};

const stopSessionPolling = () => {
    if (sessionPollTimer) {
        clearInterval(sessionPollTimer);
        sessionPollTimer = null;
    }
};

onMounted(() => {
    if (form['whatsapp.mode'] === 'automatic') {
        connectWhatsAppSession();
    }
});

onUnmounted(() => {
    stopSessionPolling();
});

watch(activeTab, (tab) => {
    if (tab === 'whatsapp' && form['whatsapp.mode'] === 'automatic' && !sessionState.value.isLoggedIn) {
        connectWhatsAppSession();
    }
});

// Test Message State
const testPhone = ref(
    props.settings.whatsapp?.['whatsapp.session_id'] ||
    props.settings.store?.['store.phone'] ||
    '081234567890'
);
const testTemplateType = ref<'intake' | 'ready' | 'reminder' | 'custom'>('intake');
const testCustomMessage = ref('');
const isSendingTestMessage = ref(false);
const testMessageResult = ref<{
    success?: boolean;
    message?: string;
    mode?: string;
    phone?: string;
    wa_url?: string;
    data?: any;
    error?: string;
} | null>(null);

async function runTestMessage() {
    isSendingTestMessage.value = true;
    testMessageResult.value = null;

    try {
        const payload: Record<string, any> = {
            phone: testPhone.value,
            mode: form['whatsapp.mode'],
            server_url: form['whatsapp.server_url'],
            api_key: form['whatsapp.api_key'],
            session_id: form['whatsapp.session_id'],
            template_type: testTemplateType.value,
        };

        if (testTemplateType.value === 'custom') {
            payload.message = testCustomMessage.value || 'Pesan uji coba kustom';
        } else {
            if (testTemplateType.value === 'intake') payload.template = form['whatsapp.template_intake'];
            if (testTemplateType.value === 'ready') payload.template = form['whatsapp.template_ready'];
            if (testTemplateType.value === 'reminder') payload.template = form['whatsapp.template_reminder'];
        }

        const response = await fetch('/owner/settings/whatsapp/test-message', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken(),
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify(payload),
        });

        const data = await response.json();
        testMessageResult.value = data;
    } catch (err: any) {
        testMessageResult.value = {
            success: false,
            message: err.message || 'Gagal mengirim pesan uji coba.',
        };
    } finally {
        isSendingTestMessage.value = false;
    }
}

const submitSettings = () => {
    // Transform flat form keys into required backend batch array: [{ key, value }]
    const payload = Object.entries(form.data()).map(([key, value]) => ({
        key,
        value: typeof value === 'boolean' ? (value ? 1 : 0) : value,
    }));

    form.transform(() => ({ settings: payload })).put('/owner/settings', {
        preserveScroll: true,
    });
};
</script>

<template>
    <AppLayout title="Pengaturan Toko & Bisnis">
        <div class="space-y-6">
            <!-- Header Title Banner -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white p-5 rounded-xl border border-slate-200 shadow-xs">
                <div>
                    <div class="flex items-center gap-2">
                        <h1 class="text-xl font-bold text-slate-900">Pengaturan Toko & Bisnis</h1>
                        <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-teal-50 text-teal-800 border border-teal-200">
                            Owner Control
                        </span>
                    </div>
                    <p class="text-xs text-slate-500 mt-1">Parameter operasional, batas piutang, hardware & gateway WhatsApp</p>
                </div>
            </div>

            <!-- Flash Message Banner -->
            <div
                v-if="flashSuccess"
                class="flex items-center gap-3 p-4 rounded-xl bg-teal-50 border border-teal-200 text-teal-900"
            >
                <CheckCircle2 class="w-5 h-5 text-teal-600 shrink-0" />
                <span class="text-sm font-medium">{{ flashSuccess }}</span>
            </div>

            <div
                v-if="flashError"
                class="flex items-center gap-3 p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-900"
            >
                <AlertCircle class="w-5 h-5 text-rose-600 shrink-0" />
                <span class="text-sm font-medium">{{ flashError }}</span>
            </div>

            <!-- Settings Layout: Sidebar Tabs + Content Panel -->
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                <!-- Navigation Tabs Rail -->
                <aside class="lg:col-span-1 space-y-2">
                    <div class="bg-white rounded-xl border border-slate-200 p-2 shadow-xs space-y-1">
                        <button
                            v-for="tab in tabs"
                            :key="tab.key"
                            type="button"
                            @click="activeTab = tab.key"
                            :class="[
                                'w-full flex items-start gap-3 p-3 rounded-lg text-left transition min-h-[48px]',
                                activeTab === tab.key
                                    ? 'bg-teal-600 text-white shadow-xs font-semibold'
                                    : 'text-slate-700 hover:bg-slate-100 font-medium'
                            ]"
                        >
                            <component
                                :is="tab.icon"
                                :class="['w-5 h-5 mt-0.5 shrink-0', activeTab === tab.key ? 'text-white' : 'text-slate-500']"
                            />
                            <div>
                                <div class="text-sm leading-tight">{{ tab.label }}</div>
                                <div
                                    :class="[
                                        'text-xs mt-0.5 leading-tight',
                                        activeTab === tab.key ? 'text-teal-100' : 'text-slate-400'
                                    ]"
                                >
                                    {{ tab.desc }}
                                </div>
                            </div>
                        </button>
                    </div>

                    <!-- Quick Info Card -->
                    <div class="bg-teal-50/60 rounded-xl border border-teal-100 p-4 text-xs text-teal-900 space-y-2">
                        <div class="font-semibold flex items-center gap-1.5 text-teal-950">
                            <CheckCircle2 class="w-4 h-4 text-teal-700" />
                            Audit Compliance
                        </div>
                        <p class="leading-relaxed text-teal-800">
                            Setiap perubahan parameter dicatat secara otomatis dalam buku audit digital yang tidak dapat dihapus.
                        </p>
                    </div>
                </aside>

                <!-- Form Panel -->
                <div class="lg:col-span-3">
                    <form @submit.prevent="submitSettings" class="space-y-6">
                        <!-- Tab 1: Identitas Toko -->
                        <div v-show="activeTab === 'store'" class="bg-white rounded-xl border border-slate-200 p-6 shadow-xs space-y-6">
                            <div class="border-b border-slate-100 pb-4">
                                <h2 class="text-lg font-bold text-slate-900 flex items-center gap-2">
                                    <Store class="w-5 h-5 text-teal-600" />
                                    Profil & Identitas Toko
                                </h2>
                                <p class="text-xs text-slate-500 mt-0.5">Informasi resmi yang tertera pada nota cetak dan pesan pelanggan</p>
                            </div>

                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-semibold text-slate-800 mb-1.5">
                                        Nama Toko / Outlet <span class="text-rose-500">*</span>
                                    </label>
                                    <input
                                        v-model="form['store.name']"
                                        type="text"
                                        class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 focus:ring-2 focus:ring-teal-500 focus:border-teal-500 text-sm min-h-[44px]"
                                        placeholder="Contoh: Andik Laundry"
                                        required
                                    />
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-slate-800 mb-1.5">
                                        Nomor WhatsApp Toko <span class="text-rose-500">*</span>
                                    </label>
                                    <input
                                        v-model="form['store.phone']"
                                        type="text"
                                        class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 focus:ring-2 focus:ring-teal-500 focus:border-teal-500 text-sm min-h-[44px]"
                                        placeholder="081234567890 / 6281234567890"
                                        required
                                    />
                                    <p class="text-xs text-slate-400 mt-1">Nomor kontak resmi yang digunakan untuk pengiriman nota dan konfirmasi.</p>
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-slate-800 mb-1.5">
                                        Alamat Fisik Outlet
                                    </label>
                                    <textarea
                                        v-model="form['store.address']"
                                        rows="2"
                                        class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 focus:ring-2 focus:ring-teal-500 focus:border-teal-500 text-sm"
                                        placeholder="Alamat lengkap outlet untuk kepala nota..."
                                    ></textarea>
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-slate-800 mb-1.5">
                                        Catatan Kaki Nota (Receipt Footer)
                                    </label>
                                    <textarea
                                        v-model="form['store.receipt_footer']"
                                        rows="3"
                                        class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 focus:ring-2 focus:ring-teal-500 focus:border-teal-500 text-sm"
                                        placeholder="Syarat, ketentuan, dan garansi cuci bersih pada bagian bawah nota thermal..."
                                    ></textarea>
                                    <p class="text-xs text-slate-400 mt-1">Dapat memuat teks panjang hingga ketentuan klaim kehilangan/kerusakan.</p>
                                </div>
                            </div>
                        </div>

                        <!-- Tab 2: Operasional -->
                        <div v-show="activeTab === 'operational'" class="bg-white rounded-xl border border-slate-200 p-6 shadow-xs space-y-6">
                            <div class="border-b border-slate-100 pb-4">
                                <h2 class="text-lg font-bold text-slate-900 flex items-center gap-2">
                                    <Sliders class="w-5 h-5 text-teal-600" />
                                    Batas & Ambang Batas Operasional
                                </h2>
                                <p class="text-xs text-slate-500 mt-0.5">Aturan kasir, minimum kiloan, plafon piutang, dan cuci ulang</p>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-semibold text-slate-800 mb-1.5">
                                        Cas Kiloan Minimum Default (kg)
                                    </label>
                                    <div class="relative">
                                        <input
                                            v-model="form['operational.default_min_weight']"
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            class="w-full pl-3.5 pr-12 py-2.5 rounded-lg border border-slate-300 focus:ring-2 focus:ring-teal-500 focus:border-teal-500 text-sm min-h-[44px]"
                                            required
                                        />
                                        <span class="absolute right-3.5 top-3 text-xs font-semibold text-slate-500">kg</span>
                                    </div>
                                    <p class="text-xs text-slate-400 mt-1">Berat pesanan kiloan di bawah angka ini akan dibulatkan ke batas ini.</p>
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-slate-800 mb-1.5">
                                        Plafon Piutang Pelanggan (Rp)
                                    </label>
                                    <div class="relative">
                                        <span class="absolute left-3.5 top-3 text-xs font-semibold text-slate-500">Rp</span>
                                        <input
                                            v-model="form['operational.customer_credit_ceiling']"
                                            type="number"
                                            step="1000"
                                            min="0"
                                            class="w-full pl-10 pr-3.5 py-2.5 rounded-lg border border-slate-300 focus:ring-2 focus:ring-teal-500 focus:border-teal-500 text-sm min-h-[44px]"
                                            required
                                        />
                                    </div>
                                    <p class="text-xs text-slate-400 mt-1">Batas maksimal saldo belum lunas per pelanggan sebelum dicegat sistem.</p>
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-slate-800 mb-1.5">
                                        Jatuh Tempo Piutang (Hari)
                                    </label>
                                    <div class="relative">
                                        <input
                                            v-model.number="form['operational.default_debt_due_days']"
                                            type="number"
                                            min="1"
                                            class="w-full pl-3.5 pr-14 py-2.5 rounded-lg border border-slate-300 focus:ring-2 focus:ring-teal-500 focus:border-teal-500 text-sm min-h-[44px]"
                                            required
                                        />
                                        <span class="absolute right-3.5 top-3 text-xs font-semibold text-slate-500">Hari</span>
                                    </div>
                                    <p class="text-xs text-slate-400 mt-1">Tenggat pembayaran sebelum peringatan WhatsApp dikirim.</p>
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-slate-800 mb-1.5">
                                        Batas Kas Kecil Kasir (Rp)
                                    </label>
                                    <div class="relative">
                                        <span class="absolute left-3.5 top-3 text-xs font-semibold text-slate-500">Rp</span>
                                        <input
                                            v-model="form['operational.cashier_petty_cash_limit']"
                                            type="number"
                                            step="1000"
                                            min="0"
                                            class="w-full pl-10 pr-3.5 py-2.5 rounded-lg border border-slate-300 focus:ring-2 focus:ring-teal-500 focus:border-teal-500 text-sm min-h-[44px]"
                                            required
                                        />
                                    </div>
                                    <p class="text-xs text-slate-400 mt-1">Pengeluaran kas kecil kasir di atas batas ini memerlukan persetujuan Owner.</p>
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-slate-800 mb-1.5">
                                        Batas Siklus Cuci Ulang (Maksimal)
                                    </label>
                                    <div class="relative">
                                        <input
                                            v-model.number="form['operational.max_rewash_limit']"
                                            type="number"
                                            min="1"
                                            max="5"
                                            class="w-full pl-3.5 pr-14 py-2.5 rounded-lg border border-slate-300 focus:ring-2 focus:ring-teal-500 focus:border-teal-500 text-sm min-h-[44px]"
                                            required
                                        />
                                        <span class="absolute right-3.5 top-3 text-xs font-semibold text-slate-500">Kali</span>
                                    </div>
                                    <p class="text-xs text-slate-400 mt-1">Jika cuci ulang melebihi batas ini, order dialihkan untuk review Owner.</p>
                                </div>
                            </div>
                        </div>

                        <!-- Tab 3: Finansial -->
                        <div v-show="activeTab === 'financial'" class="bg-white rounded-xl border border-slate-200 p-6 shadow-xs space-y-6">
                            <div class="border-b border-slate-100 pb-4">
                                <h2 class="text-lg font-bold text-slate-900 flex items-center gap-2">
                                    <Coins class="w-5 h-5 text-teal-600" />
                                    Finansial & Perpajakan
                                </h2>
                                <p class="text-xs text-slate-500 mt-0.5">Konfigurasi tarif PPN dan kode mata uang transaksi</p>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-semibold text-slate-800 mb-1.5">
                                        Tarif Pajak PPN (%)
                                    </label>
                                    <div class="relative">
                                        <input
                                            v-model="form['financial.tax_rate_percent']"
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            max="100"
                                            class="w-full pl-3.5 pr-10 py-2.5 rounded-lg border border-slate-300 focus:ring-2 focus:ring-teal-500 focus:border-teal-500 text-sm min-h-[44px]"
                                            required
                                        />
                                        <span class="absolute right-3.5 top-3 text-xs font-semibold text-slate-500">%</span>
                                    </div>
                                    <p class="text-xs text-slate-400 mt-1">0.00% untuk UMKM non-PKP.</p>
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-slate-800 mb-1.5">
                                        Mata Uang Standar
                                    </label>
                                    <select
                                        v-model="form['financial.currency_code']"
                                        class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 focus:ring-2 focus:ring-teal-500 focus:border-teal-500 text-sm min-h-[44px] bg-white"
                                    >
                                        <option value="IDR">IDR — Rupiah Indonesia</option>
                                        <option value="USD">USD — US Dollar</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Tab 4: Printer -->
                        <div v-show="activeTab === 'printer'" class="bg-white rounded-xl border border-slate-200 p-6 shadow-xs space-y-6">
                            <div class="border-b border-slate-100 pb-4">
                                <h2 class="text-lg font-bold text-slate-900 flex items-center gap-2">
                                    <Printer class="w-5 h-5 text-teal-600" />
                                    Printer Nota Thermal (ESC/POS)
                                </h2>
                                <p class="text-xs text-slate-500 mt-0.5">Pengaturan ukuran kertas dan perilaku pencetakan otomatis kasir</p>
                            </div>

                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-semibold text-slate-800 mb-2">
                                        Lebar Kertas Printer Nota
                                    </label>
                                    <div class="grid grid-cols-2 gap-3 max-w-md">
                                        <label
                                            :class="[
                                                'flex items-center justify-center gap-2 p-3.5 rounded-xl border cursor-pointer min-h-[44px] transition',
                                                form['printer.paper_width'] === '58mm'
                                                    ? 'border-teal-600 bg-teal-50/50 text-teal-900 font-semibold ring-1 ring-teal-600'
                                                    : 'border-slate-300 hover:bg-slate-50 text-slate-700'
                                            ]"
                                        >
                                            <input
                                                type="radio"
                                                v-model="form['printer.paper_width']"
                                                value="58mm"
                                                class="sr-only"
                                            />
                                            <span>58mm (Kecil / Mobile)</span>
                                        </label>

                                        <label
                                            :class="[
                                                'flex items-center justify-center gap-2 p-3.5 rounded-xl border cursor-pointer min-h-[44px] transition',
                                                form['printer.paper_width'] === '80mm'
                                                    ? 'border-teal-600 bg-teal-50/50 text-teal-900 font-semibold ring-1 ring-teal-600'
                                                    : 'border-slate-300 hover:bg-slate-50 text-slate-700'
                                            ]"
                                        >
                                            <input
                                                type="radio"
                                                v-model="form['printer.paper_width']"
                                                value="80mm"
                                                class="sr-only"
                                            />
                                            <span>80mm (Standar POS)</span>
                                        </label>
                                    </div>
                                </div>

                                <div class="pt-2">
                                    <label class="flex items-center gap-3 cursor-pointer select-none">
                                        <input
                                            type="checkbox"
                                            v-model="form['printer.auto_print']"
                                            class="w-5 h-5 rounded border-slate-300 text-teal-600 focus:ring-teal-500"
                                        />
                                        <div>
                                            <div class="text-sm font-semibold text-slate-900">
                                                Cetak Nota Otomatis Saat Transaksi Selesai
                                            </div>
                                            <div class="text-xs text-slate-500">
                                                Dialog cetak browser/ESC-POS akan langsung terbuka tanpa klik tombol cetak manual.
                                            </div>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Tab 5: WhatsApp Gateway (Comprehensive Interface) -->
                        <div v-show="activeTab === 'whatsapp'" class="space-y-6">
                            <!-- Section 1: Mode Gateway Toggle -->
                            <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-xs space-y-4">
                                <div class="border-b border-slate-100 pb-3">
                                    <h2 class="text-lg font-bold text-slate-900 flex items-center gap-2">
                                        <MessageSquare class="w-5 h-5 text-teal-600" />
                                        Mode Operasi WhatsApp
                                    </h2>
                                    <p class="text-xs text-slate-500 mt-0.5">
                                        Tentukan metode pengiriman pesan: Gateway Otomatis di latar belakang atau tautan Manual wa.me
                                    </p>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-1">
                                    <!-- Automatic Gateway Card -->
                                    <div
                                        @click="selectMode('automatic')"
                                        :class="[
                                            'p-4 rounded-xl border-2 cursor-pointer transition flex flex-col justify-between space-y-3',
                                            form['whatsapp.mode'] === 'automatic'
                                                ? 'border-teal-600 bg-teal-50/40 ring-1 ring-teal-600 shadow-xs'
                                                : 'border-slate-200 hover:border-slate-300 hover:bg-slate-50'
                                        ]"
                                    >
                                        <div class="flex items-start justify-between">
                                            <div class="flex items-center gap-2.5">
                                                <div
                                                    :class="[
                                                        'w-4 h-4 rounded-full border-2 flex items-center justify-center shrink-0',
                                                        form['whatsapp.mode'] === 'automatic' ? 'border-teal-600' : 'border-slate-400'
                                                    ]"
                                                >
                                                    <div v-if="form['whatsapp.mode'] === 'automatic'" class="w-2 h-2 rounded-full bg-teal-600"></div>
                                                </div>
                                                <span class="font-bold text-slate-900 text-sm">Automatic Gateway (WuzAPI)</span>
                                            </div>
                                            <span class="px-2 py-0.5 rounded text-[11px] font-semibold bg-teal-100 text-teal-800">
                                                Rekomendasi
                                            </span>
                                        </div>
                                        <p class="text-xs text-slate-600 leading-relaxed">
                                            Notifikasi dikirim otomatis di latar belakang melalui server gateway (WuzAPI). Bebas pulsa, tanpa repot klik kirim manual per transaksi.
                                        </p>
                                    </div>

                                    <!-- Manual wa.me Card -->
                                    <div
                                        @click="selectMode('manual')"
                                        :class="[
                                            'p-4 rounded-xl border-2 cursor-pointer transition flex flex-col justify-between space-y-3',
                                            form['whatsapp.mode'] === 'manual'
                                                ? 'border-teal-600 bg-teal-50/40 ring-1 ring-teal-600 shadow-xs'
                                                : 'border-slate-200 hover:border-slate-300 hover:bg-slate-50'
                                        ]"
                                    >
                                        <div class="flex items-start justify-between">
                                            <div class="flex items-center gap-2.5">
                                                <div
                                                    :class="[
                                                        'w-4 h-4 rounded-full border-2 flex items-center justify-center shrink-0',
                                                        form['whatsapp.mode'] === 'manual' ? 'border-teal-600' : 'border-slate-400'
                                                    ]"
                                                >
                                                    <div v-if="form['whatsapp.mode'] === 'manual'" class="w-2 h-2 rounded-full bg-teal-600"></div>
                                                </div>
                                                <span class="font-bold text-slate-900 text-sm">Manual wa.me Fallback</span>
                                            </div>
                                            <span class="px-2 py-0.5 rounded text-[11px] font-semibold bg-slate-100 text-slate-700">
                                                Tanpa Server
                                            </span>
                                        </div>
                                        <p class="text-xs text-slate-600 leading-relaxed">
                                            Sistem menghasilkan tombol klik `wa.me` langsung di antarmuka kasir. Membuka aplikasi WhatsApp Web/HP dengan pesan siap kirim.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Section 1.5: WhatsApp Multi-Device Session Connection -->
                            <div v-if="form['whatsapp.mode'] === 'automatic'" class="bg-white rounded-xl border border-slate-200 p-6 shadow-xs space-y-5">
                                <div class="border-b border-slate-100 pb-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                                    <div>
                                        <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                                            <Radio class="w-4 h-4 text-teal-600" />
                                            Penyambungan Sesi WhatsApp Gateway
                                        </h3>
                                        <p class="text-xs text-slate-500 mt-0.5">
                                            Hubungkan nomor WhatsApp outlet Anda ke gateway otomatis menggunakan Scan QR Code atau Kode OTP Pairing
                                        </p>
                                    </div>

                                    <!-- Connection Status Badge -->
                                    <div class="flex items-center gap-2">
                                        <span
                                            v-if="sessionState.isLoggedIn"
                                            class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200"
                                        >
                                            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                            Sesi Terhubung ({{ sessionState.jid || 'Aktif' }})
                                        </span>
                                        <span
                                            v-else
                                            class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200"
                                        >
                                            <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                                            Belum Terhubung (Unpaired)
                                        </span>
                                    </div>
                                </div>

                                <!-- Active Connected State View -->
                                <div v-if="sessionState.isLoggedIn" class="p-4 rounded-xl bg-teal-50/60 border border-teal-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                                    <div class="space-y-1">
                                        <div class="text-sm font-bold text-teal-950 flex items-center gap-2">
                                            <CheckCircle2 class="w-4 h-4 text-teal-600 shrink-0" />
                                            WhatsApp Siap Digunakan untuk Notifikasi Otomatis
                                        </div>
                                        <p class="text-xs text-teal-800 leading-relaxed">
                                            Nomor akun: <span class="font-mono font-semibold">{{ sessionState.jid }}</span>. Notifikasi intake, cucian selesai, dan pengingat tagihan akan dikirim otomatis dari nomor ini.
                                        </p>
                                    </div>
                                    <Button
                                        type="button"
                                        @click="disconnectWhatsAppSession"
                                        :disabled="isDisconnectingSession"
                                        variant="outline"
                                        class="border-rose-300 text-rose-700 hover:bg-rose-50 hover:text-rose-800 text-xs font-semibold flex items-center gap-1.5 shrink-0"
                                    >
                                        <LogOut class="w-3.5 h-3.5" />
                                        <span>{{ isDisconnectingSession ? 'Memutuskan...' : 'Putuskan Sesi' }}</span>
                                    </Button>
                                </div>

                                <!-- Unpaired / Pairing Interface -->
                                <div v-else class="space-y-5">
                                    <!-- Step 1: Select Pairing Method -->
                                    <div>
                                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">
                                            Pilih Metode Penyambungan Sesi
                                        </label>
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                            <!-- Method 1: Scan QR Code -->
                                            <div
                                                @click="setPairingMethod('qr')"
                                                :class="[
                                                    'p-4 rounded-xl border-2 cursor-pointer transition flex items-start gap-3.5',
                                                    pairingMethod === 'qr'
                                                        ? 'border-teal-600 bg-teal-50/40 ring-1 ring-teal-600 shadow-xs'
                                                        : 'border-slate-200 hover:border-slate-300 hover:bg-slate-50'
                                                ]"
                                            >
                                                <div :class="['w-9 h-9 rounded-lg flex items-center justify-center shrink-0 mt-0.5', pairingMethod === 'qr' ? 'bg-teal-600 text-white' : 'bg-slate-100 text-slate-600']">
                                                    <QrCode class="w-5 h-5" />
                                                </div>
                                                <div class="space-y-1">
                                                    <div class="flex items-center gap-2">
                                                        <span class="text-sm font-bold text-slate-900">Pindai Kode QR (Scan QR)</span>
                                                        <span v-if="pairingMethod === 'qr'" class="w-2 h-2 rounded-full bg-teal-600"></span>
                                                    </div>
                                                    <p class="text-xs text-slate-500 leading-relaxed">
                                                        Pindai kode QR langsung dari aplikasi WhatsApp di HP Anda (menu Perangkat Tertaut).
                                                    </p>
                                                </div>
                                            </div>

                                            <!-- Method 2: Kode OTP / Pairing Code -->
                                            <div
                                                @click="setPairingMethod('otp')"
                                                :class="[
                                                    'p-4 rounded-xl border-2 cursor-pointer transition flex items-start gap-3.5',
                                                    pairingMethod === 'otp'
                                                        ? 'border-teal-600 bg-teal-50/40 ring-1 ring-teal-600 shadow-xs'
                                                        : 'border-slate-200 hover:border-slate-300 hover:bg-slate-50'
                                                ]"
                                            >
                                                <div :class="['w-9 h-9 rounded-lg flex items-center justify-center shrink-0 mt-0.5', pairingMethod === 'otp' ? 'bg-teal-600 text-white' : 'bg-slate-100 text-slate-600']">
                                                    <KeyRound class="w-5 h-5" />
                                                </div>
                                                <div class="space-y-1">
                                                    <div class="flex items-center gap-2">
                                                        <span class="text-sm font-bold text-slate-900">Kode OTP / Pairing Code</span>
                                                        <span v-if="pairingMethod === 'otp'" class="w-2 h-2 rounded-full bg-teal-600"></span>
                                                    </div>
                                                    <p class="text-xs text-slate-500 leading-relaxed">
                                                        Tautkan dengan nomor HP tanpa kamera. Cukup masukkan kode 8 karakter di WhatsApp HP.
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Step 2: Content for Method: Scan QR Code -->
                                    <div v-if="pairingMethod === 'qr'" class="bg-slate-50/70 border border-slate-200 rounded-xl p-5 space-y-4">
                                        <div class="flex flex-col md:flex-row items-center gap-6">
                                            <!-- QR Code Box -->
                                            <div class="shrink-0 flex flex-col items-center gap-2">
                                                <div class="w-56 h-56 bg-white border border-slate-300 rounded-xl p-3 shadow-xs flex items-center justify-center relative overflow-hidden">
                                                    <img
                                                        v-if="sessionState.qr_image"
                                                        :src="sessionState.qr_image"
                                                        alt="Scan QR WhatsApp"
                                                        class="w-full h-full object-contain"
                                                    />
                                                    <div v-else-if="isConnectingSession" class="text-center p-4 space-y-2">
                                                        <RefreshCw class="w-8 h-8 text-teal-600 animate-spin mx-auto" />
                                                        <p class="text-xs text-slate-500 font-medium">Memuat QR Code...</p>
                                                    </div>
                                                    <div v-else class="text-center p-4 space-y-2">
                                                        <QrCode class="w-10 h-10 text-slate-300 mx-auto" />
                                                        <p class="text-xs text-slate-400">Klik tombol di bawah untuk menampilkan QR code</p>
                                                    </div>
                                                </div>

                                                <Button
                                                    type="button"
                                                    @click="connectWhatsAppSession"
                                                    :disabled="isConnectingSession"
                                                    class="bg-teal-600 hover:bg-teal-700 text-white text-xs font-semibold px-4 min-h-[38px] flex items-center gap-2 w-full justify-center"
                                                >
                                                    <RefreshCw :class="['w-3.5 h-3.5', isConnectingSession ? 'animate-spin' : '']" />
                                                    <span>{{ sessionState.qr_image ? 'Perbarui QR Code' : 'Sambungkan & Tampilkan QR' }}</span>
                                                </Button>
                                            </div>

                                            <!-- Instructions -->
                                            <div class="space-y-3 text-sm text-slate-700">
                                                <div class="font-bold text-slate-900 flex items-center gap-2">
                                                    <Smartphone class="w-4 h-4 text-teal-600" />
                                                    Langkah Menghubungkan via Scan QR:
                                                </div>
                                                <ol class="space-y-2 text-xs text-slate-600 list-decimal list-inside leading-relaxed pl-1">
                                                    <li>Buka aplikasi <strong>WhatsApp</strong> di HP Anda.</li>
                                                    <li>Ketuk menu <strong>Titik Tiga</strong> (Android) atau <strong>Pengaturan</strong> (iPhone).</li>
                                                    <li>Pilih <strong>Perangkat Tertaut (Linked Devices)</strong>.</li>
                                                    <li>Ketuk tombol <strong>Tautkan Perangkat</strong>.</li>
                                                    <li>Arahkan kamera HP ke gambar <strong>QR Code</strong> di samping.</li>
                                                </ol>
                                                <div class="text-[11px] text-teal-700 bg-teal-50 p-2.5 rounded-lg border border-teal-100 flex items-center gap-2">
                                                    <CheckCircle2 class="w-4 h-4 text-teal-600 shrink-0" />
                                                    <span>Halaman ini akan otomatis mendeteksi ketika WhatsApp di ponsel Anda berhasil dipindai.</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Step 2: Content for Method: Kode OTP / Pairing Code -->
                                    <div v-else class="bg-slate-50/70 border border-slate-200 rounded-xl p-5 space-y-5">
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5 items-start">
                                            <div class="space-y-4">
                                                <div>
                                                    <label class="block text-sm font-semibold text-slate-800 mb-1.5">
                                                        Nomor WhatsApp HP Anda <span class="text-rose-500">*</span>
                                                    </label>
                                                    <div class="flex gap-2">
                                                        <input
                                                            v-model="pairingPhone"
                                                            type="text"
                                                            class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 focus:ring-2 focus:ring-teal-500 focus:border-teal-500 text-sm font-mono"
                                                            placeholder="Contoh: 081234567890 / 6281234567890"
                                                        />
                                                        <Button
                                                            type="button"
                                                            @click="connectWhatsAppSession"
                                                            :disabled="isConnectingSession || !pairingPhone"
                                                            class="bg-teal-600 hover:bg-teal-700 text-white text-xs font-semibold px-4 min-h-[42px] shrink-0 flex items-center gap-1.5"
                                                        >
                                                            <RefreshCw :class="['w-3.5 h-3.5', isConnectingSession ? 'animate-spin' : '']" />
                                                            <span>{{ isConnectingSession ? 'Meminta Kode...' : 'Dapatkan Kode OTP' }}</span>
                                                        </Button>
                                                    </div>
                                                    <p class="text-xs text-slate-400 mt-1">Masukkan nomor WhatsApp HP yang akan dijadikan pengirim notifikasi.</p>
                                                </div>

                                                <!-- Pairing Code Display Box -->
                                                <div v-if="sessionState.pairing_code || sessionState.formatted_code" class="p-4 rounded-xl bg-white border-2 border-teal-500 shadow-sm space-y-3">
                                                    <div class="text-xs font-bold text-slate-500 uppercase tracking-wider">
                                                        Kode Pairing WhatsApp Anda:
                                                    </div>
                                                    <div class="flex items-center justify-between gap-3 bg-slate-900 text-white p-3.5 rounded-lg">
                                                        <span class="font-mono text-2xl font-black tracking-widest text-teal-400">
                                                            {{ sessionState.formatted_code || sessionState.pairing_code }}
                                                        </span>
                                                        <Button
                                                            type="button"
                                                            @click="copyPairingCode(sessionState.formatted_code || sessionState.pairing_code || '')"
                                                            variant="outline"
                                                            class="bg-slate-800 border-slate-700 text-slate-200 hover:bg-slate-700 hover:text-white text-xs px-3 py-1.5 min-h-[36px] flex items-center gap-1.5"
                                                        >
                                                            <Check v-if="codeCopied" class="w-3.5 h-3.5 text-emerald-400" />
                                                            <Copy v-else class="w-3.5 h-3.5" />
                                                            <span>{{ codeCopied ? 'Tersalin!' : 'Salin Kode' }}</span>
                                                        </Button>
                                                    </div>
                                                    <p class="text-xs text-slate-600 leading-relaxed">
                                                        Kode ini berlaku sekitar 2-3 menit. Masukkan segera pada WhatsApp di ponsel Anda.
                                                    </p>
                                                </div>
                                            </div>

                                            <!-- Instructions for OTP Code -->
                                            <div class="space-y-3 text-sm text-slate-700 bg-white p-4 rounded-xl border border-slate-200">
                                                <div class="font-bold text-slate-900 flex items-center gap-2">
                                                    <KeyRound class="w-4 h-4 text-teal-600" />
                                                    Cara Memasukkan Kode di WhatsApp Ponsel:
                                                </div>
                                                <ol class="space-y-2 text-xs text-slate-600 list-decimal list-inside leading-relaxed pl-1">
                                                    <li>Buka aplikasi <strong>WhatsApp</strong> di HP Anda.</li>
                                                    <li>Masuk ke menu <strong>Perangkat Tertaut (Linked Devices)</strong>.</li>
                                                    <li>Ketuk <strong>Tautkan Perangkat</strong>.</li>
                                                    <li>Ketuk opsi <strong>"Tautkan dengan nomor telepon saja"</strong> (Link with phone number instead) di bagian bawah.</li>
                                                    <li>Masukkan <strong>8 karakter kode pairing</strong> di atas.</li>
                                                </ol>
                                                <div class="text-[11px] text-teal-700 bg-teal-50 p-2.5 rounded-lg border border-teal-100 flex items-center gap-2">
                                                    <CheckCircle2 class="w-4 h-4 text-teal-600 shrink-0" />
                                                    <span>Sistem otomatis menyambungkan begitu kode dimasukkan di WhatsApp Anda.</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Session Error Alert -->
                                    <div v-if="sessionError" class="p-3.5 rounded-xl bg-rose-50 border border-rose-200 text-rose-900 text-xs flex items-center gap-2">
                                        <AlertCircle class="w-4 h-4 text-rose-600 shrink-0" />
                                        <span>{{ sessionError }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Section 2: Gateway Connection Parameters -->
                            <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-xs space-y-5">
                                <div class="border-b border-slate-100 pb-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                                    <div>
                                        <h3 class="text-base font-bold text-slate-900">
                                            Parameter Koneksi Gateway
                                        </h3>
                                        <p class="text-xs text-slate-500 mt-0.5">
                                            Konfigurasi server backend pengirim pesan WhatsApp (WuzAPI REST API)
                                        </p>
                                    </div>
                                    <div v-if="form['whatsapp.mode'] === 'manual'" class="text-xs px-2.5 py-1 rounded bg-amber-50 text-amber-800 border border-amber-200">
                                        Mode manual aktif: Gateway server dilewati saat pengiriman
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <!-- Server URL -->
                                    <div class="sm:col-span-2">
                                        <label class="block text-sm font-semibold text-slate-800 mb-1.5">
                                            URL Endpoint Server Gateway <span class="text-rose-500">*</span>
                                        </label>
                                        <input
                                            v-model="form['whatsapp.server_url']"
                                            type="url"
                                            class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 focus:ring-2 focus:ring-teal-500 focus:border-teal-500 text-sm min-h-[44px] font-mono"
                                            placeholder="http://localhost:8080 atau https://wa-gateway.internal"
                                            :required="form['whatsapp.mode'] === 'automatic'"
                                        />
                                        <p class="text-xs text-slate-400 mt-1">Alamat host dan port container WuzAPI atau API gateway WhatsApp Anda.</p>
                                    </div>

                                    <!-- API Key / Token -->
                                    <div>
                                        <label class="block text-sm font-semibold text-slate-800 mb-1.5">
                                            API Key / Secret Token
                                        </label>
                                        <div class="relative">
                                            <input
                                                v-model="form['whatsapp.api_key']"
                                                :type="showApiKey ? 'text' : 'password'"
                                                class="w-full pl-3.5 pr-10 py-2.5 rounded-lg border border-slate-300 focus:ring-2 focus:ring-teal-500 focus:border-teal-500 text-sm min-h-[44px] font-mono"
                                                placeholder="Token autentikasi WuzAPI..."
                                            />
                                            <button
                                                type="button"
                                                @click="showApiKey = !showApiKey"
                                                class="absolute right-3 top-3 text-slate-400 hover:text-slate-600 cursor-pointer"
                                                tabindex="-1"
                                            >
                                                <component :is="showApiKey ? EyeOff : Eye" class="w-4 h-4" />
                                            </button>
                                        </div>
                                        <p class="text-xs text-slate-400 mt-1">Digunakan untuk header autentikasi `Authorization: Bearer` dan `Token:`.</p>
                                    </div>

                                    <!-- Session ID / Phone -->
                                    <div>
                                        <label class="block text-sm font-semibold text-slate-800 mb-1.5">
                                            Session ID / Nomor WhatsApp Pengirim
                                        </label>
                                        <input
                                            v-model="form['whatsapp.session_id']"
                                            type="text"
                                            class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 focus:ring-2 focus:ring-teal-500 focus:border-teal-500 text-sm min-h-[44px]"
                                            placeholder="Contoh: 6281234567890"
                                        />
                                        <p class="text-xs text-slate-400 mt-1">Identitas nomor WhatsApp yang telah terhubung pada multi-device gateway.</p>
                                    </div>

                                    <!-- Rate Limit -->
                                    <div>
                                        <label class="block text-sm font-semibold text-slate-800 mb-1.5">
                                            Anti-Ban Rate Limit (Pesan / Menit)
                                        </label>
                                        <div class="relative">
                                            <input
                                                v-model.number="form['whatsapp.anti_ban_rate_limit']"
                                                type="number"
                                                min="1"
                                                max="60"
                                                class="w-full pl-3.5 pr-24 py-2.5 rounded-lg border border-slate-300 focus:ring-2 focus:ring-teal-500 focus:border-teal-500 text-sm min-h-[44px]"
                                                required
                                            />
                                            <span class="absolute right-3.5 top-3 text-xs font-semibold text-slate-500">Pesan/menit</span>
                                        </div>
                                        <p class="text-xs text-slate-400 mt-1">Batas aman pengiriman pesan berkala guna mencegah pencekalan spam oleh WhatsApp.</p>
                                    </div>
                                </div>

                                <!-- Action: Test Gateway Connectivity -->
                                <div class="pt-2 border-t border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                    <div class="text-xs text-slate-500">
                                        Periksa kesehatan sesi dan komunikasi dengan server gateway secara langsung.
                                    </div>
                                    <Button
                                        type="button"
                                        @click="runConnectionTest"
                                        :disabled="isTestingConnection"
                                        variant="outline"
                                        class="border-teal-600 text-teal-700 hover:bg-teal-50 min-h-[42px] px-4 font-semibold flex items-center gap-2 cursor-pointer shrink-0"
                                    >
                                        <RefreshCw :class="['w-4 h-4', isTestingConnection ? 'animate-spin' : '']" />
                                        <span>{{ isTestingConnection ? 'Memeriksa Koneksi...' : 'Uji Koneksi Gateway' }}</span>
                                    </Button>
                                </div>

                                <!-- Connection Test Feedback Box -->
                                <div
                                    v-if="connectionTestResult"
                                    :class="[
                                        'p-4 rounded-xl border text-sm space-y-1 transition',
                                        connectionTestResult.success
                                            ? 'bg-teal-50 border-teal-200 text-teal-900'
                                            : 'bg-rose-50 border-rose-200 text-rose-900'
                                    ]"
                                >
                                    <div class="font-bold flex items-center gap-2">
                                        <CheckCircle2 v-if="connectionTestResult.success" class="w-4 h-4 text-teal-600 shrink-0" />
                                        <AlertCircle v-else class="w-4 h-4 text-rose-600 shrink-0" />
                                        <span>{{ connectionTestResult.message }}</span>
                                    </div>
                                    <div v-if="connectionTestResult.data" class="text-xs opacity-80 font-mono mt-1 pt-1 border-t border-slate-200/50 break-all overflow-x-auto">
                                        {{ typeof connectionTestResult.data === 'string' ? connectionTestResult.data : JSON.stringify(connectionTestResult.data) }}
                                    </div>
                                </div>
                            </div>

                            <!-- Section 3: Diagnostic Test Message Delivery -->
                            <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-xs space-y-4">
                                <div class="border-b border-slate-100 pb-3">
                                    <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                                        <Send class="w-4 h-4 text-teal-600" />
                                        Kirim Pesan Uji Coba (Live Test)
                                    </h3>
                                    <p class="text-xs text-slate-500 mt-0.5">
                                        Kirimkan pesan uji coba ke nomor Anda untuk memastikan kredensial dan pesan terkirim dengan sempurna
                                    </p>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                    <div>
                                        <label class="block text-sm font-semibold text-slate-800 mb-1.5">
                                            Nomor Tujuan Uji Coba
                                        </label>
                                        <input
                                            v-model="testPhone"
                                            type="text"
                                            class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 focus:ring-2 focus:ring-teal-500 focus:border-teal-500 text-sm min-h-[44px]"
                                            placeholder="Contoh: 081234567890"
                                        />
                                    </div>

                                    <div>
                                        <label class="block text-sm font-semibold text-slate-800 mb-1.5">
                                            Jenis Pesan Uji Coba
                                        </label>
                                        <select
                                            v-model="testTemplateType"
                                            class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 focus:ring-2 focus:ring-teal-500 focus:border-teal-500 text-sm min-h-[44px] bg-white"
                                        >
                                            <option value="intake">Contoh Template Intake (Penerimaan)</option>
                                            <option value="ready">Contoh Template Siap Ambil (Ready)</option>
                                            <option value="reminder">Contoh Template Pengingat Tagihan</option>
                                            <option value="custom">Teks Kustom Sendiri</option>
                                        </select>
                                    </div>

                                    <div class="flex items-end">
                                        <Button
                                            type="button"
                                            @click="runTestMessage"
                                            :disabled="isSendingTestMessage || !testPhone"
                                            class="w-full bg-slate-900 hover:bg-slate-800 text-white min-h-[44px] px-4 font-semibold flex items-center justify-center gap-2 cursor-pointer"
                                        >
                                            <Send class="w-4 h-4" />
                                            <span>{{ isSendingTestMessage ? 'Mengirim...' : 'Kirim Uji Coba' }}</span>
                                        </Button>
                                    </div>
                                </div>

                                <div v-if="testTemplateType === 'custom'">
                                    <label class="block text-sm font-semibold text-slate-800 mb-1.5">
                                        Isi Teks Uji Coba
                                    </label>
                                    <textarea
                                        v-model="testCustomMessage"
                                        rows="2"
                                        class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 focus:ring-2 focus:ring-teal-500 focus:border-teal-500 text-sm"
                                        placeholder="Tulis pesan pengujian di sini..."
                                    ></textarea>
                                </div>

                                <!-- Test Message Feedback Box -->
                                <div
                                    v-if="testMessageResult"
                                    :class="[
                                        'p-4 rounded-xl border text-sm space-y-2 transition',
                                        testMessageResult.success
                                            ? 'bg-teal-50 border-teal-200 text-teal-900'
                                            : 'bg-rose-50 border-rose-200 text-rose-900'
                                    ]"
                                >
                                    <div class="font-bold flex items-center gap-2">
                                        <CheckCircle2 v-if="testMessageResult.success" class="w-4 h-4 text-teal-600 shrink-0" />
                                        <AlertCircle v-else class="w-4 h-4 text-rose-600 shrink-0" />
                                        <span>{{ testMessageResult.message }}</span>
                                    </div>

                                    <div v-if="testMessageResult.wa_url" class="pt-2">
                                        <a
                                            :href="testMessageResult.wa_url"
                                            target="_blank"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-600 text-white font-semibold text-xs rounded-lg hover:bg-emerald-700 shadow-xs cursor-pointer"
                                        >
                                            <ExternalLink class="w-3.5 h-3.5" />
                                            <span>Buka Tautan wa.me di Tab Baru</span>
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Section 4: Notification Message Templates -->
                            <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-xs space-y-5">
                                <div class="border-b border-slate-100 pb-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                                    <div>
                                        <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                                            <Sparkles class="w-4 h-4 text-teal-600" />
                                            Template Pesan Notifikasi Otomatis
                                        </h3>
                                        <p class="text-xs text-slate-500 mt-0.5">
                                            Sesuaikan format pesan WhatsApp untuk Intake, Siap Ambil, dan Pengingat Piutang
                                        </p>
                                    </div>

                                    <!-- Template Sub-tabs Selector -->
                                    <div class="flex items-center gap-1 p-1 bg-slate-100 rounded-lg shrink-0">
                                        <button
                                            type="button"
                                            @click="activeTemplateTab = 'intake'"
                                            :class="[
                                                'px-3 py-1.5 text-xs font-semibold rounded-md transition cursor-pointer',
                                                activeTemplateTab === 'intake'
                                                    ? 'bg-white text-teal-900 shadow-xs'
                                                    : 'text-slate-600 hover:text-slate-900'
                                            ]"
                                        >
                                            Intake (Penerimaan)
                                        </button>
                                        <button
                                            type="button"
                                            @click="activeTemplateTab = 'ready'"
                                            :class="[
                                                'px-3 py-1.5 text-xs font-semibold rounded-md transition cursor-pointer',
                                                activeTemplateTab === 'ready'
                                                    ? 'bg-white text-teal-900 shadow-xs'
                                                    : 'text-slate-600 hover:text-slate-900'
                                            ]"
                                        >
                                            Siap Ambil (Ready)
                                        </button>
                                        <button
                                            type="button"
                                            @click="activeTemplateTab = 'reminder'"
                                            :class="[
                                                'px-3 py-1.5 text-xs font-semibold rounded-md transition cursor-pointer',
                                                activeTemplateTab === 'reminder'
                                                    ? 'bg-white text-teal-900 shadow-xs'
                                                    : 'text-slate-600 hover:text-slate-900'
                                            ]"
                                        >
                                            Pengingat Tagihan
                                        </button>
                                    </div>
                                </div>

                                <!-- Variable Chips Helper -->
                                <div class="bg-slate-50 p-3.5 rounded-xl border border-slate-200/80 space-y-2">
                                    <div class="text-xs font-bold text-slate-700 flex items-center justify-between">
                                        <span>Klik variabel untuk disisipkan ke template:</span>
                                        <button
                                            type="button"
                                            @click="resetTemplate(activeTemplateTab)"
                                            class="text-teal-700 hover:text-teal-900 font-semibold underline text-[11px] cursor-pointer"
                                        >
                                            Kembalikan ke Template Default
                                        </button>
                                    </div>
                                    <div class="flex flex-wrap gap-1.5">
                                        <button
                                            type="button"
                                            @click="insertVariable('store_name')"
                                            class="px-2 py-1 rounded bg-white border border-slate-300 text-xs text-slate-700 hover:border-teal-500 hover:text-teal-700 cursor-pointer"
                                        >
                                            + store_name
                                        </button>
                                        <button
                                            type="button"
                                            @click="insertVariable('customer_name')"
                                            class="px-2 py-1 rounded bg-white border border-slate-300 text-xs text-slate-700 hover:border-teal-500 hover:text-teal-700 cursor-pointer"
                                        >
                                            + customer_name
                                        </button>
                                        <button
                                            type="button"
                                            @click="insertVariable('invoice_number')"
                                            class="px-2 py-1 rounded bg-white border border-slate-300 text-xs text-slate-700 hover:border-teal-500 hover:text-teal-700 cursor-pointer"
                                        >
                                            + invoice_number
                                        </button>
                                        <button
                                            type="button"
                                            @click="insertVariable('items_summary')"
                                            class="px-2 py-1 rounded bg-white border border-slate-300 text-xs text-slate-700 hover:border-teal-500 hover:text-teal-700 cursor-pointer"
                                        >
                                            + items_summary
                                        </button>
                                        <button
                                            type="button"
                                            @click="insertVariable('total_amount')"
                                            class="px-2 py-1 rounded bg-white border border-slate-300 text-xs text-slate-700 hover:border-teal-500 hover:text-teal-700 cursor-pointer"
                                        >
                                            + total_amount
                                        </button>
                                        <button
                                            type="button"
                                            @click="insertVariable('paid_amount')"
                                            class="px-2 py-1 rounded bg-white border border-slate-300 text-xs text-slate-700 hover:border-teal-500 hover:text-teal-700 cursor-pointer"
                                        >
                                            + paid_amount
                                        </button>
                                        <button
                                            type="button"
                                            @click="insertVariable('paid_info')"
                                            class="px-2 py-1 rounded bg-white border border-slate-300 text-xs text-slate-700 hover:border-teal-500 hover:text-teal-700 cursor-pointer"
                                        >
                                            + paid_info
                                        </button>
                                        <button
                                            type="button"
                                            @click="insertVariable('rack_location')"
                                            class="px-2 py-1 rounded bg-white border border-slate-300 text-xs text-slate-700 hover:border-teal-500 hover:text-teal-700 cursor-pointer"
                                        >
                                            + rack_location
                                        </button>
                                        <button
                                            type="button"
                                            @click="insertVariable('debt_amount')"
                                            class="px-2 py-1 rounded bg-white border border-slate-300 text-xs text-slate-700 hover:border-teal-500 hover:text-teal-700 cursor-pointer"
                                        >
                                            + debt_amount
                                        </button>
                                        <button
                                            type="button"
                                            @click="insertVariable('status_text')"
                                            class="px-2 py-1 rounded bg-white border border-slate-300 text-xs text-slate-700 hover:border-teal-500 hover:text-teal-700 cursor-pointer"
                                        >
                                            + status_text
                                        </button>
                                        <button
                                            type="button"
                                            @click="insertVariable('receipt_url')"
                                            class="px-2 py-1 rounded bg-white border border-slate-300 text-xs text-slate-700 hover:border-teal-500 hover:text-teal-700 cursor-pointer"
                                        >
                                            + receipt_url
                                        </button>
                                    </div>
                                </div>

                                <!-- Editor & Live Preview Grid -->
                                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                                    <!-- Editor Column -->
                                    <div>
                                        <label class="block text-sm font-semibold text-slate-800 mb-1.5">
                                            Teks Template (Mendukung Markdown WhatsApp: *bold*, _italic_)
                                        </label>

                                        <textarea
                                            v-if="activeTemplateTab === 'intake'"
                                            v-model="form['whatsapp.template_intake']"
                                            rows="12"
                                            class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 focus:ring-2 focus:ring-teal-500 focus:border-teal-500 text-xs font-mono leading-relaxed"
                                            placeholder="Tulis template konfirmasi intake..."
                                        ></textarea>

                                        <textarea
                                            v-else-if="activeTemplateTab === 'ready'"
                                            v-model="form['whatsapp.template_ready']"
                                            rows="12"
                                            class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 focus:ring-2 focus:ring-teal-500 focus:border-teal-500 text-xs font-mono leading-relaxed"
                                            placeholder="Tulis template siap ambil..."
                                        ></textarea>

                                        <textarea
                                            v-else
                                            v-model="form['whatsapp.template_reminder']"
                                            rows="12"
                                            class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 focus:ring-2 focus:ring-teal-500 focus:border-teal-500 text-xs font-mono leading-relaxed"
                                            placeholder="Tulis template pengingat piutang..."
                                        ></textarea>
                                    </div>

                                    <!-- Live Preview Column -->
                                    <div>
                                        <label class="block text-sm font-semibold text-slate-800 mb-1.5">
                                            Pratinjau Tampilan Pesan Pelanggan
                                        </label>
                                        <div class="bg-emerald-50/70 border border-emerald-200 rounded-xl p-4 text-xs font-sans text-slate-900 whitespace-pre-wrap break-words leading-relaxed shadow-xs min-h-[260px] max-h-[480px] overflow-y-auto">
                                            {{ previewText }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Sticky Actions Footer -->
                        <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-xs flex items-center justify-between">
                            <div class="text-xs text-slate-500">
                                Pastikan seluruh parameter telah ditinjau sebelum disimpan ke database.
                            </div>
                            <Button
                                type="submit"
                                :disabled="form.processing"
                                class="bg-teal-600 hover:bg-teal-700 text-white min-h-[44px] px-6 font-semibold flex items-center gap-2 cursor-pointer"
                            >
                                <Save class="w-4 h-4" />
                                <span v-if="form.processing">Menyimpan...</span>
                                <span v-else>Simpan Pengaturan</span>
                            </Button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
