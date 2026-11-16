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
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white p-5 rounded-[12px] border-[3.5px] border-black shadow-[6px_6px_0px_#000]">
                <div>
                    <div class="flex items-center gap-2.5">
                        <h1 class="font-display text-2xl sm:text-3xl font-black tracking-tight text-black">Pengaturan Toko & Bisnis</h1>
                        <span class="px-2.5 py-0.5 rounded-[6px] text-xs font-black uppercase tracking-wider bg-[#FFEB3B] text-black border-2 border-black shadow-[2px_2px_0px_#000]">
                            Owner Control
                        </span>
                    </div>
                    <p class="text-xs sm:text-sm font-bold text-black/70 mt-1">Parameter operasional, batas piutang, hardware thermal & gateway WhatsApp</p>
                </div>
            </div>

            <!-- Flash Message Banner -->
            <div
                v-if="flashSuccess"
                class="flex items-center gap-3 p-4 rounded-[10px] bg-[#4CAF50]/20 border-[3px] border-black text-black font-bold shadow-[4px_4px_0px_#000]"
            >
                <CheckCircle2 class="w-5 h-5 text-black shrink-0 stroke-[2.5]" />
                <span class="text-sm font-black">{{ flashSuccess }}</span>
            </div>

            <div
                v-if="flashError"
                class="flex items-center gap-3 p-4 rounded-[10px] bg-[#FF5252]/20 border-[3px] border-black text-black font-bold shadow-[4px_4px_0px_#000]"
            >
                <AlertCircle class="w-5 h-5 text-black shrink-0 stroke-[2.5]" />
                <span class="text-sm font-black">{{ flashError }}</span>
            </div>

            <!-- Settings Layout: Sidebar Tabs + Content Panel -->
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                <!-- Navigation Tabs Rail -->
                <aside class="lg:col-span-1 space-y-4">
                    <div class="bg-white rounded-[12px] border-[3.5px] border-black p-3 shadow-[5px_5px_0px_#000] space-y-1.5">
                        <button
                            v-for="tab in tabs"
                            :key="tab.key"
                            type="button"
                            @click="activeTab = tab.key"
                            :class="[
                                'w-full flex items-start gap-3 p-3 rounded-[8px] text-left transition-all min-h-[48px] border-2 cursor-pointer',
                                activeTab === tab.key
                                    ? 'bg-[#FFEB3B] text-black border-black font-black shadow-[3px_3px_0px_#000] translate-x-[2px]'
                                    : 'bg-white text-black border-transparent hover:border-black hover:bg-[#FFFDF5] font-bold'
                            ]"
                        >
                            <component
                                :is="tab.icon"
                                :class="['w-5 h-5 mt-0.5 shrink-0 stroke-[2.5]', activeTab === tab.key ? 'text-black' : 'text-black/70']"
                            />
                            <div>
                                <div class="text-sm font-black leading-tight">{{ tab.label }}</div>
                                <div
                                    :class="[
                                        'text-xs mt-0.5 leading-tight font-bold',
                                        activeTab === tab.key ? 'text-black/80' : 'text-black/50'
                                    ]"
                                >
                                    {{ tab.desc }}
                                </div>
                            </div>
                        </button>
                    </div>

                    <!-- Quick Info Card -->
                    <div class="bg-[#2196F3]/15 rounded-[12px] border-[3px] border-black p-4 text-black shadow-[4px_4px_0px_#000] space-y-2">
                        <div class="font-black flex items-center gap-2 text-sm uppercase tracking-wide">
                            <CheckCircle2 class="w-4 h-4 text-black stroke-[2.5]" />
                            Audit Compliance
                        </div>
                        <p class="text-xs font-bold leading-relaxed text-black/80">
                            Setiap perubahan parameter dicatat secara otomatis dalam buku audit digital yang tidak dapat dimanipulasi.
                        </p>
                    </div>
                </aside>

                <!-- Form Panel -->
                <div class="lg:col-span-3">
                    <form @submit.prevent="submitSettings" class="space-y-6">
                        <!-- Tab 1: Identitas Toko -->
                        <div v-show="activeTab === 'store'" class="bg-white rounded-[12px] border-[3.5px] border-black p-6 shadow-[6px_6px_0px_#000] space-y-6">
                            <div class="border-b-[2.5px] border-black pb-4">
                                <h2 class="text-lg sm:text-xl font-black text-black flex items-center gap-2.5 tracking-tight">
                                    <Store class="w-5 h-5 text-black stroke-[2.5]" />
                                    Profil & Identitas Toko
                                </h2>
                                <p class="text-xs sm:text-sm font-bold text-black/70 mt-0.5">Informasi resmi yang tertera pada nota cetak dan pesan pelanggan</p>
                            </div>

                            <div class="space-y-4">
                                <div>
                                    <label class="block text-xs sm:text-sm font-black text-black mb-1.5 uppercase tracking-wide">
                                        Nama Toko / Outlet <span class="text-[#FF5252]">*</span>
                                    </label>
                                    <input
                                        v-model="form['store.name']"
                                        type="text"
                                        class="w-full px-3.5 py-2.5 rounded-[8px] border-[2.5px] border-black bg-white text-black font-bold text-sm min-h-[44px] shadow-[3px_3px_0px_#000] focus:outline-none focus:ring-0 focus:shadow-[4px_4px_0px_#000] focus:bg-[#FFFDF5] transition"
                                        placeholder="Contoh: Andik Laundry"
                                        required
                                    />
                                </div>

                                <div>
                                    <label class="block text-xs sm:text-sm font-black text-black mb-1.5 uppercase tracking-wide">
                                        Nomor WhatsApp Toko <span class="text-[#FF5252]">*</span>
                                    </label>
                                    <input
                                        v-model="form['store.phone']"
                                        type="text"
                                        class="w-full px-3.5 py-2.5 rounded-[8px] border-[2.5px] border-black bg-white text-black font-bold text-sm min-h-[44px] shadow-[3px_3px_0px_#000] focus:outline-none focus:ring-0 focus:shadow-[4px_4px_0px_#000] focus:bg-[#FFFDF5] transition"
                                        placeholder="081234567890 / 6281234567890"
                                        required
                                    />
                                    <p class="text-xs font-bold text-black/60 mt-1">Nomor kontak resmi yang digunakan untuk pengiriman nota dan konfirmasi.</p>
                                </div>

                                <div>
                                    <label class="block text-xs sm:text-sm font-black text-black mb-1.5 uppercase tracking-wide">
                                        Alamat Fisik Outlet
                                    </label>
                                    <textarea
                                        v-model="form['store.address']"
                                        rows="2"
                                        class="w-full px-3.5 py-2.5 rounded-[8px] border-[2.5px] border-black bg-white text-black font-bold text-sm shadow-[3px_3px_0px_#000] focus:outline-none focus:ring-0 focus:shadow-[4px_4px_0px_#000] focus:bg-[#FFFDF5] transition"
                                        placeholder="Alamat lengkap outlet untuk kepala nota..."
                                    ></textarea>
                                </div>

                                <div>
                                    <label class="block text-xs sm:text-sm font-black text-black mb-1.5 uppercase tracking-wide">
                                        Catatan Kaki Nota (Receipt Footer)
                                    </label>
                                    <textarea
                                        v-model="form['store.receipt_footer']"
                                        rows="3"
                                        class="w-full px-3.5 py-2.5 rounded-[8px] border-[2.5px] border-black bg-white text-black font-bold text-sm shadow-[3px_3px_0px_#000] focus:outline-none focus:ring-0 focus:shadow-[4px_4px_0px_#000] focus:bg-[#FFFDF5] transition"
                                        placeholder="Syarat, ketentuan, dan garansi cuci bersih pada bagian bawah nota thermal..."
                                    ></textarea>
                                    <p class="text-xs font-bold text-black/60 mt-1">Dapat memuat teks panjang hingga ketentuan klaim kehilangan/kerusakan.</p>
                                </div>
                            </div>
                        </div>

                        <!-- Tab 2: Operasional -->
                        <div v-show="activeTab === 'operational'" class="bg-white rounded-[12px] border-[3.5px] border-black p-6 shadow-[6px_6px_0px_#000] space-y-6">
                            <div class="border-b-[2.5px] border-black pb-4">
                                <h2 class="text-lg sm:text-xl font-black text-black flex items-center gap-2.5 tracking-tight">
                                    <Sliders class="w-5 h-5 text-black stroke-[2.5]" />
                                    Batas & Ambang Batas Operasional
                                </h2>
                                <p class="text-xs sm:text-sm font-bold text-black/70 mt-0.5">Aturan kasir, minimum kiloan, plafon piutang, dan cuci ulang</p>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs sm:text-sm font-black text-black mb-1.5 uppercase tracking-wide">
                                        Cas Kiloan Minimum Default (kg)
                                    </label>
                                    <div class="relative">
                                        <input
                                            v-model="form['operational.default_min_weight']"
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            class="w-full pl-3.5 pr-12 py-2.5 rounded-[8px] border-[2.5px] border-black bg-white text-black font-bold text-sm min-h-[44px] shadow-[3px_3px_0px_#000] focus:outline-none focus:ring-0 focus:shadow-[4px_4px_0px_#000] focus:bg-[#FFFDF5] transition"
                                            required
                                        />
                                        <span class="absolute right-3.5 top-3 text-xs font-black text-black">KG</span>
                                    </div>
                                    <p class="text-xs font-bold text-black/60 mt-1">Berat pesanan kiloan di bawah angka ini akan dibulatkan ke batas ini.</p>
                                </div>

                                <div>
                                    <label class="block text-xs sm:text-sm font-black text-black mb-1.5 uppercase tracking-wide">
                                        Plafon Piutang Pelanggan (Rp)
                                    </label>
                                    <div class="relative">
                                        <span class="absolute left-3.5 top-3 text-xs font-black text-black">RP</span>
                                        <input
                                            v-model="form['operational.customer_credit_ceiling']"
                                            type="number"
                                            step="1000"
                                            min="0"
                                            class="w-full pl-10 pr-3.5 py-2.5 rounded-[8px] border-[2.5px] border-black bg-white text-black font-bold text-sm min-h-[44px] shadow-[3px_3px_0px_#000] focus:outline-none focus:ring-0 focus:shadow-[4px_4px_0px_#000] focus:bg-[#FFFDF5] transition"
                                            required
                                        />
                                    </div>
                                    <p class="text-xs font-bold text-black/60 mt-1">Batas maksimal saldo belum lunas per pelanggan sebelum dicegat sistem.</p>
                                </div>

                                <div>
                                    <label class="block text-xs sm:text-sm font-black text-black mb-1.5 uppercase tracking-wide">
                                        Jatuh Tempo Piutang (Hari)
                                    </label>
                                    <div class="relative">
                                        <input
                                            v-model.number="form['operational.default_debt_due_days']"
                                            type="number"
                                            min="1"
                                            class="w-full pl-3.5 pr-16 py-2.5 rounded-[8px] border-[2.5px] border-black bg-white text-black font-bold text-sm min-h-[44px] shadow-[3px_3px_0px_#000] focus:outline-none focus:ring-0 focus:shadow-[4px_4px_0px_#000] focus:bg-[#FFFDF5] transition"
                                            required
                                        />
                                        <span class="absolute right-3.5 top-3 text-xs font-black text-black">HARI</span>
                                    </div>
                                    <p class="text-xs font-bold text-black/60 mt-1">Tenggat pembayaran sebelum peringatan WhatsApp dikirim.</p>
                                </div>

                                <div>
                                    <label class="block text-xs sm:text-sm font-black text-black mb-1.5 uppercase tracking-wide">
                                        Batas Kas Kecil Kasir (Rp)
                                    </label>
                                    <div class="relative">
                                        <span class="absolute left-3.5 top-3 text-xs font-black text-black">RP</span>
                                        <input
                                            v-model="form['operational.cashier_petty_cash_limit']"
                                            type="number"
                                            step="1000"
                                            min="0"
                                            class="w-full pl-10 pr-3.5 py-2.5 rounded-[8px] border-[2.5px] border-black bg-white text-black font-bold text-sm min-h-[44px] shadow-[3px_3px_0px_#000] focus:outline-none focus:ring-0 focus:shadow-[4px_4px_0px_#000] focus:bg-[#FFFDF5] transition"
                                            required
                                        />
                                    </div>
                                    <p class="text-xs font-bold text-black/60 mt-1">Pengeluaran kas kecil kasir di atas batas ini memerlukan persetujuan Owner.</p>
                                </div>

                                <div>
                                    <label class="block text-xs sm:text-sm font-black text-black mb-1.5 uppercase tracking-wide">
                                        Batas Siklus Cuci Ulang (Maksimal)
                                    </label>
                                    <div class="relative">
                                        <input
                                            v-model.number="form['operational.max_rewash_limit']"
                                            type="number"
                                            min="1"
                                            max="5"
                                            class="w-full pl-3.5 pr-16 py-2.5 rounded-[8px] border-[2.5px] border-black bg-white text-black font-bold text-sm min-h-[44px] shadow-[3px_3px_0px_#000] focus:outline-none focus:ring-0 focus:shadow-[4px_4px_0px_#000] focus:bg-[#FFFDF5] transition"
                                            required
                                        />
                                        <span class="absolute right-3.5 top-3 text-xs font-black text-black">KALI</span>
                                    </div>
                                    <p class="text-xs font-bold text-black/60 mt-1">Jika cuci ulang melebihi batas ini, order dialihkan untuk review Owner.</p>
                                </div>
                            </div>
                        </div>

                        <!-- Tab 3: Finansial -->
                        <div v-show="activeTab === 'financial'" class="bg-white rounded-[12px] border-[3.5px] border-black p-6 shadow-[6px_6px_0px_#000] space-y-6">
                            <div class="border-b-[2.5px] border-black pb-4">
                                <h2 class="text-lg sm:text-xl font-black text-black flex items-center gap-2.5 tracking-tight">
                                    <Coins class="w-5 h-5 text-black stroke-[2.5]" />
                                    Finansial & Perpajakan
                                </h2>
                                <p class="text-xs sm:text-sm font-bold text-black/70 mt-0.5">Konfigurasi tarif PPN dan kode mata uang transaksi</p>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs sm:text-sm font-black text-black mb-1.5 uppercase tracking-wide">
                                        Tarif Pajak PPN (%)
                                    </label>
                                    <div class="relative">
                                        <input
                                            v-model="form['financial.tax_rate_percent']"
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            max="100"
                                            class="w-full pl-3.5 pr-10 py-2.5 rounded-[8px] border-[2.5px] border-black bg-white text-black font-bold text-sm min-h-[44px] shadow-[3px_3px_0px_#000] focus:outline-none focus:ring-0 focus:shadow-[4px_4px_0px_#000] focus:bg-[#FFFDF5] transition"
                                            required
                                        />
                                        <span class="absolute right-3.5 top-3 text-xs font-black text-black">%</span>
                                    </div>
                                    <p class="text-xs font-bold text-black/60 mt-1">0.00% untuk UMKM non-PKP.</p>
                                </div>

                                <div>
                                    <label class="block text-xs sm:text-sm font-black text-black mb-1.5 uppercase tracking-wide">
                                        Mata Uang Standar
                                    </label>
                                    <select
                                        v-model="form['financial.currency_code']"
                                        class="w-full px-3.5 py-2.5 rounded-[8px] border-[2.5px] border-black bg-white text-black font-bold text-sm min-h-[44px] shadow-[3px_3px_0px_#000] focus:outline-none focus:ring-0 focus:shadow-[4px_4px_0px_#000] focus:bg-[#FFFDF5] transition"
                                    >
                                        <option value="IDR">IDR — Rupiah Indonesia</option>
                                        <option value="USD">USD — US Dollar</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Tab 4: Printer -->
                        <div v-show="activeTab === 'printer'" class="bg-white rounded-[12px] border-[3.5px] border-black p-6 shadow-[6px_6px_0px_#000] space-y-6">
                            <div class="border-b-[2.5px] border-black pb-4">
                                <h2 class="text-lg sm:text-xl font-black text-black flex items-center gap-2.5 tracking-tight">
                                    <Printer class="w-5 h-5 text-black stroke-[2.5]" />
                                    Printer Nota Thermal (ESC/POS)
                                </h2>
                                <p class="text-xs sm:text-sm font-bold text-black/70 mt-0.5">Pengaturan ukuran kertas dan perilaku pencetakan otomatis kasir</p>
                            </div>

                            <div class="space-y-4">
                                <div>
                                    <label class="block text-xs sm:text-sm font-black text-black mb-2 uppercase tracking-wide">
                                        Lebar Kertas Printer Nota
                                    </label>
                                    <div class="grid grid-cols-2 gap-3 max-w-md">
                                        <label
                                            :class="[
                                                'relative flex items-center justify-center gap-2 p-3.5 rounded-[8px] border-[2.5px] border-black cursor-pointer min-h-[44px] transition-all',
                                                form['printer.paper_width'] === '58mm'
                                                    ? 'bg-[#FFEB3B] text-black font-black shadow-[3px_3px_0px_#000]'
                                                    : 'bg-white hover:bg-[#FFFDF5] text-black font-bold shadow-[2px_2px_0px_#000]'
                                            ]"
                                        >
                                            <input
                                                type="radio"
                                                v-model="form['printer.paper_width']"
                                                value="58mm"
                                                class="sr-only"
                                            />
                                            <span class="text-sm font-black">58mm (Kecil / Mobile)</span>
                                        </label>

                                        <label
                                            :class="[
                                                'relative flex items-center justify-center gap-2 p-3.5 rounded-[8px] border-[2.5px] border-black cursor-pointer min-h-[44px] transition-all',
                                                form['printer.paper_width'] === '80mm'
                                                    ? 'bg-[#FFEB3B] text-black font-black shadow-[3px_3px_0px_#000]'
                                                    : 'bg-white hover:bg-[#FFFDF5] text-black font-bold shadow-[2px_2px_0px_#000]'
                                            ]"
                                        >
                                            <input
                                                type="radio"
                                                v-model="form['printer.paper_width']"
                                                value="80mm"
                                                class="sr-only"
                                            />
                                            <span class="text-sm font-black">80mm (Standar POS)</span>
                                        </label>
                                    </div>
                                </div>

                                <div class="pt-2">
                                    <label class="flex items-center gap-3 cursor-pointer select-none">
                                        <input
                                            type="checkbox"
                                            v-model="form['printer.auto_print']"
                                            class="w-5 h-5 rounded-[4px] border-2 border-black text-black focus:ring-0 accent-black cursor-pointer shadow-[2px_2px_0px_#000]"
                                        />
                                        <div>
                                            <div class="text-sm font-black text-black">
                                                Cetak Nota Otomatis Saat Transaksi Selesai
                                            </div>
                                            <div class="text-xs font-bold text-black/60">
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
                            <div class="bg-white rounded-[12px] border-[3.5px] border-black p-6 shadow-[6px_6px_0px_#000] space-y-4">
                                <div class="border-b-[2.5px] border-black pb-3">
                                    <h2 class="text-lg sm:text-xl font-black text-black flex items-center gap-2.5 tracking-tight">
                                        <MessageSquare class="w-5 h-5 text-black stroke-[2.5]" />
                                        Mode Operasi WhatsApp
                                    </h2>
                                    <p class="text-xs sm:text-sm font-bold text-black/70 mt-0.5">
                                        Tentukan metode pengiriman pesan: Gateway Otomatis di latar belakang atau tautan Manual wa.me
                                    </p>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-1">
                                    <!-- Automatic Gateway Card -->
                                    <div
                                        @click="selectMode('automatic')"
                                        :class="[
                                            'p-4 rounded-[10px] border-[3px] border-black cursor-pointer transition-all flex flex-col justify-between space-y-3',
                                            form['whatsapp.mode'] === 'automatic'
                                                ? 'bg-[#FFEB3B] text-black font-black shadow-[4px_4px_0px_#000]'
                                                : 'bg-white hover:bg-[#FFFDF5] text-black font-bold shadow-[2px_2px_0px_#000]'
                                        ]"
                                    >
                                        <div class="flex items-start justify-between">
                                            <div class="flex items-center gap-2.5">
                                                <div
                                                    :class="[
                                                        'w-4 h-4 rounded-full border-2 border-black flex items-center justify-center shrink-0',
                                                        form['whatsapp.mode'] === 'automatic' ? 'bg-black' : 'bg-white'
                                                    ]"
                                                >
                                                    <div v-if="form['whatsapp.mode'] === 'automatic'" class="w-1.5 h-1.5 rounded-full bg-[#FFEB3B]"></div>
                                                </div>
                                                <span class="font-black text-black text-sm">Automatic Gateway (WuzAPI)</span>
                                            </div>
                                            <span class="px-2 py-0.5 rounded-[4px] text-[11px] font-black uppercase tracking-wider bg-black text-[#FFEB3B]">
                                                Rekomendasi
                                            </span>
                                        </div>
                                        <p class="text-xs font-bold leading-relaxed opacity-80">
                                            Notifikasi dikirim otomatis di latar belakang melalui server gateway (WuzAPI). Bebas pulsa, tanpa repot klik kirim manual per transaksi.
                                        </p>
                                    </div>

                                    <!-- Manual wa.me Card -->
                                    <div
                                        @click="selectMode('manual')"
                                        :class="[
                                            'p-4 rounded-[10px] border-[3px] border-black cursor-pointer transition-all flex flex-col justify-between space-y-3',
                                            form['whatsapp.mode'] === 'manual'
                                                ? 'bg-[#FFEB3B] text-black font-black shadow-[4px_4px_0px_#000]'
                                                : 'bg-white hover:bg-[#FFFDF5] text-black font-bold shadow-[2px_2px_0px_#000]'
                                        ]"
                                    >
                                        <div class="flex items-start justify-between">
                                            <div class="flex items-center gap-2.5">
                                                <div
                                                    :class="[
                                                        'w-4 h-4 rounded-full border-2 border-black flex items-center justify-center shrink-0',
                                                        form['whatsapp.mode'] === 'manual' ? 'bg-black' : 'bg-white'
                                                    ]"
                                                >
                                                    <div v-if="form['whatsapp.mode'] === 'manual'" class="w-1.5 h-1.5 rounded-full bg-[#FFEB3B]"></div>
                                                </div>
                                                <span class="font-black text-black text-sm">Manual wa.me Fallback</span>
                                            </div>
                                            <span class="px-2 py-0.5 rounded-[4px] text-[11px] font-black uppercase tracking-wider bg-slate-200 text-black border border-black">
                                                Tanpa Server
                                            </span>
                                        </div>
                                        <p class="text-xs font-bold leading-relaxed opacity-80">
                                            Sistem menghasilkan tombol klik `wa.me` langsung di antarmuka kasir. Membuka aplikasi WhatsApp Web/HP dengan pesan siap kirim.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Section 1.5: WhatsApp Multi-Device Session Connection -->
                            <div v-if="form['whatsapp.mode'] === 'automatic'" class="bg-white rounded-[12px] border-[3.5px] border-black p-6 shadow-[6px_6px_0px_#000] space-y-5">
                                <div class="border-b-[2.5px] border-black pb-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                                    <div>
                                        <h3 class="text-base sm:text-lg font-black text-black flex items-center gap-2">
                                            <Radio class="w-4 h-4 text-black stroke-[2.5]" />
                                            Penyambungan Sesi WhatsApp Gateway
                                        </h3>
                                        <p class="text-xs sm:text-sm font-bold text-black/70 mt-0.5">
                                            Hubungkan nomor WhatsApp outlet Anda ke gateway otomatis menggunakan Scan QR Code atau Kode OTP Pairing
                                        </p>
                                    </div>

                                    <!-- Connection Status Badge -->
                                    <div class="flex items-center gap-2">
                                        <span
                                            v-if="sessionState.isLoggedIn"
                                            class="inline-flex items-center gap-1.5 px-3 py-1 rounded-[6px] text-xs font-black uppercase tracking-wider bg-[#4CAF50] text-black border-2 border-black shadow-[2px_2px_0px_#000]"
                                        >
                                            <span class="w-2 h-2 rounded-full bg-black"></span>
                                            Sesi Terhubung ({{ sessionState.jid || 'Aktif' }})
                                        </span>
                                        <span
                                            v-else
                                            class="inline-flex items-center gap-1.5 px-3 py-1 rounded-[6px] text-xs font-black uppercase tracking-wider bg-[#FFEB3B] text-black border-2 border-black shadow-[2px_2px_0px_#000]"
                                        >
                                            <span class="w-2 h-2 rounded-full bg-red-600 animate-pulse"></span>
                                            Belum Terhubung (Unpaired)
                                        </span>
                                    </div>
                                </div>

                                <!-- Active Connected State View -->
                                <div v-if="sessionState.isLoggedIn" class="p-4 rounded-[10px] bg-[#4CAF50]/15 border-[3px] border-black flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 shadow-[4px_4px_0px_#000]">
                                    <div class="space-y-1">
                                        <div class="text-sm font-black text-black flex items-center gap-2">
                                            <CheckCircle2 class="w-4 h-4 text-black stroke-[2.5] shrink-0" />
                                            WhatsApp Siap Digunakan untuk Notifikasi Otomatis
                                        </div>
                                        <p class="text-xs font-bold text-black/80 leading-relaxed">
                                            Nomor akun: <span class="font-mono font-black">{{ sessionState.jid }}</span>. Notifikasi intake, cucian selesai, dan pengingat tagihan akan dikirim otomatis dari nomor ini.
                                        </p>
                                    </div>
                                    <button
                                        type="button"
                                        @click="disconnectWhatsAppSession"
                                        :disabled="isDisconnectingSession"
                                        class="bg-[#FF5252] hover:bg-red-600 text-white border-[2.5px] border-black rounded-[8px] text-xs font-black uppercase tracking-wider px-3.5 py-2 shadow-[3px_3px_0px_#000] active:translate-x-[2px] active:translate-y-[2px] active:shadow-none transition-all flex items-center gap-1.5 shrink-0 cursor-pointer"
                                    >
                                        <LogOut class="w-3.5 h-3.5 stroke-[2.5]" />
                                        <span>{{ isDisconnectingSession ? 'Memutuskan...' : 'Putuskan Sesi' }}</span>
                                    </button>
                                </div>

                                <!-- Unpaired / Pairing Interface -->
                                <div v-else class="space-y-5">
                                    <!-- Step 1: Select Pairing Method -->
                                    <div>
                                        <label class="block text-xs font-black uppercase tracking-wider text-black mb-2">
                                            Pilih Metode Penyambungan Sesi
                                        </label>
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                            <!-- Method 1: Scan QR Code -->
                                            <div
                                                @click="setPairingMethod('qr')"
                                                :class="[
                                                    'p-4 rounded-[10px] border-[3px] border-black cursor-pointer transition-all flex items-start gap-3.5',
                                                    pairingMethod === 'qr'
                                                        ? 'bg-[#FFEB3B] text-black font-black shadow-[4px_4px_0px_#000]'
                                                        : 'bg-white hover:bg-[#FFFDF5] text-black font-bold shadow-[2px_2px_0px_#000]'
                                                ]"
                                            >
                                                <div :class="['w-9 h-9 rounded-[6px] border-2 border-black flex items-center justify-center shrink-0 mt-0.5 shadow-[2px_2px_0px_#000]', pairingMethod === 'qr' ? 'bg-black text-[#FFEB3B]' : 'bg-white text-black']">
                                                    <QrCode class="w-5 h-5 stroke-[2.5]" />
                                                </div>
                                                <div class="space-y-1">
                                                    <div class="flex items-center gap-2">
                                                        <span class="text-sm font-black text-black">Pindai Kode QR (Scan QR)</span>
                                                        <span v-if="pairingMethod === 'qr'" class="w-2 h-2 rounded-full bg-black"></span>
                                                    </div>
                                                    <p class="text-xs font-bold text-black/70 leading-relaxed">
                                                        Pindai kode QR langsung dari aplikasi WhatsApp di HP Anda (menu Perangkat Tertaut).
                                                    </p>
                                                </div>
                                            </div>

                                            <!-- Method 2: Kode OTP / Pairing Code -->
                                            <div
                                                @click="setPairingMethod('otp')"
                                                :class="[
                                                    'p-4 rounded-[10px] border-[3px] border-black cursor-pointer transition-all flex items-start gap-3.5',
                                                    pairingMethod === 'otp'
                                                        ? 'bg-[#FFEB3B] text-black font-black shadow-[4px_4px_0px_#000]'
                                                        : 'bg-white hover:bg-[#FFFDF5] text-black font-bold shadow-[2px_2px_0px_#000]'
                                                ]"
                                            >
                                                <div :class="['w-9 h-9 rounded-[6px] border-2 border-black flex items-center justify-center shrink-0 mt-0.5 shadow-[2px_2px_0px_#000]', pairingMethod === 'otp' ? 'bg-black text-[#FFEB3B]' : 'bg-white text-black']">
                                                    <KeyRound class="w-5 h-5 stroke-[2.5]" />
                                                </div>
                                                <div class="space-y-1">
                                                    <div class="flex items-center gap-2">
                                                        <span class="text-sm font-black text-black">Kode OTP / Pairing Code</span>
                                                        <span v-if="pairingMethod === 'otp'" class="w-2 h-2 rounded-full bg-black"></span>
                                                    </div>
                                                    <p class="text-xs font-bold text-black/70 leading-relaxed">
                                                        Tautkan dengan nomor HP tanpa kamera. Cukup masukkan kode 8 karakter di WhatsApp HP.
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Step 2: Content for Method: Scan QR Code -->
                                    <div v-if="pairingMethod === 'qr'" class="bg-[#FFFDF5] border-[3px] border-black rounded-[10px] p-5 shadow-[4px_4px_0px_#000] space-y-4">
                                        <div class="flex flex-col md:flex-row items-center gap-6">
                                            <!-- QR Code Box -->
                                            <div class="shrink-0 flex flex-col items-center gap-3">
                                                <div class="w-56 h-56 bg-white border-[3px] border-black rounded-[10px] p-3 shadow-[4px_4px_0px_#000] flex items-center justify-center relative overflow-hidden">
                                                    <img
                                                        v-if="sessionState.qr_image"
                                                        :src="sessionState.qr_image"
                                                        alt="Scan QR WhatsApp"
                                                        class="w-full h-full object-contain"
                                                    />
                                                    <div v-else-if="isConnectingSession" class="text-center p-4 space-y-2">
                                                        <RefreshCw class="w-8 h-8 text-black animate-spin mx-auto stroke-[2.5]" />
                                                        <p class="text-xs text-black font-black uppercase tracking-wider">Memuat QR Code...</p>
                                                    </div>
                                                    <div v-else class="text-center p-4 space-y-2">
                                                        <QrCode class="w-10 h-10 text-black/30 mx-auto" />
                                                        <p class="text-xs text-black/60 font-bold">Klik tombol di bawah untuk menampilkan QR code</p>
                                                    </div>
                                                </div>

                                                <button
                                                    type="button"
                                                    @click="connectWhatsAppSession"
                                                    :disabled="isConnectingSession"
                                                    class="bg-[#FFEB3B] hover:bg-[#fff066] text-black border-[2.5px] border-black rounded-[8px] text-xs font-black uppercase tracking-wider px-4 min-h-[40px] flex items-center gap-2 w-full justify-center shadow-[3px_3px_0px_#000] active:translate-x-[2px] active:translate-y-[2px] active:shadow-none transition-all cursor-pointer"
                                                >
                                                    <RefreshCw :class="['w-3.5 h-3.5 stroke-[2.5]', isConnectingSession ? 'animate-spin' : '']" />
                                                    <span>{{ sessionState.qr_image ? 'Perbarui QR Code' : 'Sambungkan & Tampilkan QR' }}</span>
                                                </button>
                                            </div>

                                            <!-- Instructions -->
                                            <div class="space-y-3 text-sm text-black font-medium">
                                                <div class="font-black text-black flex items-center gap-2 uppercase tracking-wide">
                                                    <Smartphone class="w-4 h-4 text-black stroke-[2.5]" />
                                                    Langkah Menghubungkan via Scan QR:
                                                </div>
                                                <ol class="space-y-2 text-xs text-black/80 font-bold list-decimal list-inside leading-relaxed pl-1">
                                                    <li>Buka aplikasi <span class="font-black text-black">WhatsApp</span> di HP Anda.</li>
                                                    <li>Ketuk menu <span class="font-black text-black">Titik Tiga</span> (Android) atau <span class="font-black text-black">Pengaturan</span> (iPhone).</li>
                                                    <li>Pilih <span class="font-black text-black">Perangkat Tertaut (Linked Devices)</span>.</li>
                                                    <li>Ketuk tombol <span class="font-black text-black">Tautkan Perangkat</span>.</li>
                                                    <li>Arahkan kamera HP ke gambar <span class="font-black text-black">QR Code</span> di samping.</li>
                                                </ol>
                                                <div class="text-[11px] font-bold text-black bg-[#4CAF50]/15 p-3 rounded-[8px] border-2 border-black flex items-center gap-2 shadow-[2px_2px_0px_#000]">
                                                    <CheckCircle2 class="w-4 h-4 text-black stroke-[2.5] shrink-0" />
                                                    <span>Halaman ini akan otomatis mendeteksi ketika WhatsApp di ponsel Anda berhasil dipindai.</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Step 2: Content for Method: Kode OTP / Pairing Code -->
                                    <div v-else class="bg-[#FFFDF5] border-[3px] border-black rounded-[10px] p-5 shadow-[4px_4px_0px_#000] space-y-5">
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5 items-start">
                                            <div class="space-y-4">
                                                <div>
                                                    <label class="block text-xs sm:text-sm font-black text-black mb-1.5 uppercase tracking-wide">
                                                        Nomor WhatsApp HP Anda <span class="text-[#FF5252]">*</span>
                                                    </label>
                                                    <div class="flex gap-2">
                                                        <input
                                                            v-model="pairingPhone"
                                                            type="text"
                                                            class="w-full px-3.5 py-2.5 rounded-[8px] border-[2.5px] border-black bg-white text-black font-mono font-bold text-sm shadow-[3px_3px_0px_#000] focus:outline-none focus:ring-0 focus:shadow-[4px_4px_0px_#000] focus:bg-[#FFFDF5] transition"
                                                            placeholder="Contoh: 081234567890 / 6281234567890"
                                                        />
                                                        <button
                                                            type="button"
                                                            @click="connectWhatsAppSession"
                                                            :disabled="isConnectingSession || !pairingPhone"
                                                            class="bg-[#FFEB3B] hover:bg-[#fff066] text-black border-[2.5px] border-black rounded-[8px] text-xs font-black uppercase tracking-wider px-4 min-h-[42px] shrink-0 flex items-center gap-1.5 shadow-[3px_3px_0px_#000] active:translate-x-[2px] active:translate-y-[2px] active:shadow-none transition-all cursor-pointer"
                                                        >
                                                            <RefreshCw :class="['w-3.5 h-3.5 stroke-[2.5]', isConnectingSession ? 'animate-spin' : '']" />
                                                            <span>{{ isConnectingSession ? 'Meminta...' : 'Dapatkan OTP' }}</span>
                                                        </button>
                                                    </div>
                                                    <p class="text-xs font-bold text-black/60 mt-1">Masukkan nomor WhatsApp HP yang akan dijadikan pengirim notifikasi.</p>
                                                </div>

                                                <!-- Pairing Code Display Box -->
                                                <div v-if="sessionState.pairing_code || sessionState.formatted_code" class="p-4 rounded-[10px] bg-white border-[3px] border-black shadow-[4px_4px_0px_#000] space-y-3">
                                                    <div class="text-xs font-black text-black uppercase tracking-wider">
                                                        Kode Pairing WhatsApp Anda:
                                                    </div>
                                                    <div class="flex items-center justify-between gap-3 bg-black text-[#FFEB3B] p-3.5 rounded-[8px] border-2 border-black">
                                                        <span class="font-mono text-2xl font-black tracking-widest text-[#FFEB3B]">
                                                            {{ sessionState.formatted_code || sessionState.pairing_code }}
                                                        </span>
                                                        <button
                                                            type="button"
                                                            @click="copyPairingCode(sessionState.formatted_code || sessionState.pairing_code || '')"
                                                            class="bg-white text-black border-2 border-black rounded-[6px] text-xs font-black uppercase tracking-wider px-3 py-1.5 min-h-[36px] flex items-center gap-1.5 shadow-[2px_2px_0px_#000] active:translate-x-[1px] active:translate-y-[1px] active:shadow-none cursor-pointer"
                                                        >
                                                            <Check v-if="codeCopied" class="w-3.5 h-3.5 stroke-[2.5] text-green-600" />
                                                            <Copy v-else class="w-3.5 h-3.5 stroke-[2.5]" />
                                                            <span>{{ codeCopied ? 'Tersalin!' : 'Salin' }}</span>
                                                        </button>
                                                    </div>
                                                    <p class="text-xs font-bold text-black/70 leading-relaxed">
                                                        Kode ini berlaku sekitar 2-3 menit. Masukkan segera pada WhatsApp di ponsel Anda.
                                                    </p>
                                                </div>
                                            </div>

                                            <!-- Instructions for OTP Code -->
                                            <div class="space-y-3 text-sm text-black font-medium bg-white p-4 rounded-[10px] border-[2.5px] border-black shadow-[3px_3px_0px_#000]">
                                                <div class="font-black text-black flex items-center gap-2 uppercase tracking-wide">
                                                    <KeyRound class="w-4 h-4 text-black stroke-[2.5]" />
                                                    Cara Memasukkan Kode di WhatsApp Ponsel:
                                                </div>
                                                <ol class="space-y-2 text-xs text-black/80 font-bold list-decimal list-inside leading-relaxed pl-1">
                                                    <li>Buka aplikasi <span class="font-black text-black">WhatsApp</span> di HP Anda.</li>
                                                    <li>Masuk ke menu <span class="font-black text-black">Perangkat Tertaut (Linked Devices)</span>.</li>
                                                    <li>Ketuk <span class="font-black text-black">Tautkan Perangkat</span>.</li>
                                                    <li>Ketuk opsi <span class="font-black text-black">"Tautkan dengan nomor telepon saja"</span> di bagian bawah.</li>
                                                    <li>Masukkan <span class="font-black text-black">8 karakter kode pairing</span> di samping.</li>
                                                </ol>
                                                <div class="text-[11px] font-bold text-black bg-[#4CAF50]/15 p-2.5 rounded-[8px] border-2 border-black flex items-center gap-2 shadow-[2px_2px_0px_#000]">
                                                    <CheckCircle2 class="w-4 h-4 text-black stroke-[2.5] shrink-0" />
                                                    <span>Sistem otomatis menyambungkan begitu kode dimasukkan di WhatsApp Anda.</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Session Error Alert -->
                                    <div v-if="sessionError" class="p-3.5 rounded-[10px] bg-[#FF5252]/15 border-[2.5px] border-black text-black font-bold text-xs flex items-center gap-2 shadow-[3px_3px_0px_#000]">
                                        <AlertCircle class="w-4 h-4 text-black stroke-[2.5] shrink-0" />
                                        <span>{{ sessionError }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Section 2: Gateway Connection Parameters -->
                            <div class="bg-white rounded-[12px] border-[3.5px] border-black p-6 shadow-[6px_6px_0px_#000] space-y-5">
                                <div class="border-b-[2.5px] border-black pb-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                                    <div>
                                        <h3 class="text-base sm:text-lg font-black text-black">
                                            Parameter Koneksi Gateway
                                        </h3>
                                        <p class="text-xs sm:text-sm font-bold text-black/70 mt-0.5">
                                            Konfigurasi server backend pengirim pesan WhatsApp (WuzAPI REST API)
                                        </p>
                                    </div>
                                    <div v-if="form['whatsapp.mode'] === 'manual'" class="text-xs font-black uppercase tracking-wider px-2.5 py-1 rounded-[6px] bg-[#FFEB3B] text-black border-2 border-black shadow-[2px_2px_0px_#000]">
                                        Mode manual aktif: Gateway dilewati
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <!-- Server URL -->
                                    <div class="sm:col-span-2">
                                        <label class="block text-xs sm:text-sm font-black text-black mb-1.5 uppercase tracking-wide">
                                            URL Endpoint Server Gateway <span class="text-[#FF5252]">*</span>
                                        </label>
                                        <input
                                            v-model="form['whatsapp.server_url']"
                                            type="url"
                                            class="w-full px-3.5 py-2.5 rounded-[8px] border-[2.5px] border-black bg-white text-black font-mono font-bold text-sm min-h-[44px] shadow-[3px_3px_0px_#000] focus:outline-none focus:ring-0 focus:shadow-[4px_4px_0px_#000] focus:bg-[#FFFDF5] transition"
                                            placeholder="http://localhost:8080 atau https://wa-gateway.internal"
                                            :required="form['whatsapp.mode'] === 'automatic'"
                                        />
                                        <p class="text-xs font-bold text-black/60 mt-1">Alamat host dan port binary whatsapp-gateway atau API gateway WhatsApp Anda.</p>
                                    </div>

                                    <!-- API Key / Token -->
                                    <div>
                                        <label class="block text-xs sm:text-sm font-black text-black mb-1.5 uppercase tracking-wide">
                                            API Key / Secret Token
                                        </label>
                                        <div class="relative">
                                            <input
                                                v-model="form['whatsapp.api_key']"
                                                :type="showApiKey ? 'text' : 'password'"
                                                class="w-full pl-3.5 pr-10 py-2.5 rounded-[8px] border-[2.5px] border-black bg-white text-black font-mono font-bold text-sm min-h-[44px] shadow-[3px_3px_0px_#000] focus:outline-none focus:ring-0 focus:shadow-[4px_4px_0px_#000] focus:bg-[#FFFDF5] transition"
                                                placeholder="Token autentikasi WuzAPI..."
                                            />
                                            <button
                                                type="button"
                                                @click="showApiKey = !showApiKey"
                                                class="absolute right-3 top-3 text-black hover:text-black/70 cursor-pointer"
                                                tabindex="-1"
                                            >
                                                <component :is="showApiKey ? EyeOff : Eye" class="w-4 h-4 stroke-[2.5]" />
                                            </button>
                                        </div>
                                        <p class="text-xs font-bold text-black/60 mt-1">Digunakan untuk header autentikasi `Authorization: Bearer` dan `Token:`.</p>
                                    </div>

                                    <!-- Session ID / Phone -->
                                    <div>
                                        <label class="block text-xs sm:text-sm font-black text-black mb-1.5 uppercase tracking-wide">
                                            Session ID / Nomor WhatsApp Pengirim
                                        </label>
                                        <input
                                            v-model="form['whatsapp.session_id']"
                                            type="text"
                                            class="w-full px-3.5 py-2.5 rounded-[8px] border-[2.5px] border-black bg-white text-black font-bold text-sm min-h-[44px] shadow-[3px_3px_0px_#000] focus:outline-none focus:ring-0 focus:shadow-[4px_4px_0px_#000] focus:bg-[#FFFDF5] transition"
                                            placeholder="Contoh: 6281234567890"
                                        />
                                        <p class="text-xs font-bold text-black/60 mt-1">Identitas nomor WhatsApp yang telah terhubung pada multi-device gateway.</p>
                                    </div>

                                    <!-- Rate Limit -->
                                    <div>
                                        <label class="block text-xs sm:text-sm font-black text-black mb-1.5 uppercase tracking-wide">
                                            Anti-Ban Rate Limit (Pesan / Menit)
                                        </label>
                                        <div class="relative">
                                            <input
                                                v-model.number="form['whatsapp.anti_ban_rate_limit']"
                                                type="number"
                                                min="1"
                                                max="60"
                                                class="w-full pl-3.5 pr-28 py-2.5 rounded-[8px] border-[2.5px] border-black bg-white text-black font-bold text-sm min-h-[44px] shadow-[3px_3px_0px_#000] focus:outline-none focus:ring-0 focus:shadow-[4px_4px_0px_#000] focus:bg-[#FFFDF5] transition"
                                                required
                                            />
                                            <span class="absolute right-3.5 top-3 text-xs font-black text-black uppercase">PESAN/MENIT</span>
                                        </div>
                                        <p class="text-xs font-bold text-black/60 mt-1">Batas aman pengiriman pesan berkala guna mencegah pencekalan spam oleh WhatsApp.</p>
                                    </div>
                                </div>

                                <!-- Action: Test Gateway Connectivity -->
                                <div class="pt-3 border-t-[2.5px] border-black flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                    <div class="text-xs font-bold text-black/70">
                                        Periksa kesehatan sesi dan komunikasi dengan server gateway secara langsung.
                                    </div>
                                    <button
                                        type="button"
                                        @click="runConnectionTest"
                                        :disabled="isTestingConnection"
                                        class="bg-white hover:bg-slate-100 text-black border-[2.5px] border-black rounded-[8px] min-h-[42px] px-4 font-black uppercase tracking-wider text-xs flex items-center gap-2 cursor-pointer shrink-0 shadow-[3px_3px_0px_#000] active:translate-x-[2px] active:translate-y-[2px] active:shadow-none transition-all"
                                    >
                                        <RefreshCw :class="['w-4 h-4 stroke-[2.5]', isTestingConnection ? 'animate-spin' : '']" />
                                        <span>{{ isTestingConnection ? 'Memeriksa...' : 'Uji Koneksi Gateway' }}</span>
                                    </button>
                                </div>

                                <!-- Connection Test Feedback Box -->
                                <div
                                    v-if="connectionTestResult"
                                    :class="[
                                        'p-4 rounded-[10px] border-[3px] border-black text-sm space-y-1 transition shadow-[4px_4px_0px_#000]',
                                        connectionTestResult.success
                                            ? 'bg-[#4CAF50]/15 text-black'
                                            : 'bg-[#FF5252]/15 text-black'
                                    ]"
                                >
                                    <div class="font-black flex items-center gap-2">
                                        <CheckCircle2 v-if="connectionTestResult.success" class="w-4 h-4 text-black stroke-[2.5] shrink-0" />
                                        <AlertCircle v-else class="w-4 h-4 text-black stroke-[2.5] shrink-0" />
                                        <span>{{ connectionTestResult.message }}</span>
                                    </div>
                                    <div v-if="connectionTestResult.data" class="text-xs font-mono font-bold mt-1 pt-1 border-t border-black/20 break-all overflow-x-auto">
                                        {{ typeof connectionTestResult.data === 'string' ? connectionTestResult.data : JSON.stringify(connectionTestResult.data) }}
                                    </div>
                                </div>
                            </div>

                            <!-- Section 3: Diagnostic Test Message Delivery -->
                            <div class="bg-white rounded-[12px] border-[3.5px] border-black p-6 shadow-[6px_6px_0px_#000] space-y-4">
                                <div class="border-b-[2.5px] border-black pb-3">
                                    <h3 class="text-base sm:text-lg font-black text-black flex items-center gap-2">
                                        <Send class="w-4 h-4 text-black stroke-[2.5]" />
                                        Kirim Pesan Uji Coba (Live Test)
                                    </h3>
                                    <p class="text-xs sm:text-sm font-bold text-black/70 mt-0.5">
                                        Kirimkan pesan uji coba ke nomor Anda untuk memastikan kredensial dan pesan terkirim dengan sempurna
                                    </p>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                    <div>
                                        <label class="block text-xs sm:text-sm font-black text-black mb-1.5 uppercase tracking-wide">
                                            Nomor Tujuan Uji Coba
                                        </label>
                                        <input
                                            v-model="testPhone"
                                            type="text"
                                            class="w-full px-3.5 py-2.5 rounded-[8px] border-[2.5px] border-black bg-white text-black font-bold text-sm min-h-[44px] shadow-[3px_3px_0px_#000] focus:outline-none focus:ring-0 focus:shadow-[4px_4px_0px_#000] focus:bg-[#FFFDF5] transition"
                                            placeholder="Contoh: 081234567890"
                                        />
                                    </div>

                                    <div>
                                        <label class="block text-xs sm:text-sm font-black text-black mb-1.5 uppercase tracking-wide">
                                            Jenis Pesan Uji Coba
                                        </label>
                                        <select
                                            v-model="testTemplateType"
                                            class="w-full px-3.5 py-2.5 rounded-[8px] border-[2.5px] border-black bg-white text-black font-bold text-sm min-h-[44px] shadow-[3px_3px_0px_#000] focus:outline-none focus:ring-0 focus:shadow-[4px_4px_0px_#000] focus:bg-[#FFFDF5] transition"
                                        >
                                            <option value="intake">Contoh Template Intake (Penerimaan)</option>
                                            <option value="ready">Contoh Template Siap Ambil (Ready)</option>
                                            <option value="reminder">Contoh Template Pengingat Tagihan</option>
                                            <option value="custom">Teks Kustom Sendiri</option>
                                        </select>
                                    </div>

                                    <div class="flex items-end">
                                        <button
                                            type="button"
                                            @click="runTestMessage"
                                            :disabled="isSendingTestMessage || !testPhone"
                                            class="w-full bg-[#FFEB3B] hover:bg-[#fff066] text-black border-[2.5px] border-black rounded-[8px] min-h-[44px] px-4 font-black uppercase tracking-wider text-xs flex items-center justify-center gap-2 cursor-pointer shadow-[3px_3px_0px_#000] active:translate-x-[2px] active:translate-y-[2px] active:shadow-none transition-all"
                                        >
                                            <Send class="w-4 h-4 stroke-[2.5]" />
                                            <span>{{ isSendingTestMessage ? 'Mengirim...' : 'Kirim Uji Coba' }}</span>
                                        </button>
                                    </div>
                                </div>

                                <div v-if="testTemplateType === 'custom'">
                                    <label class="block text-xs sm:text-sm font-black text-black mb-1.5 uppercase tracking-wide">
                                        Isi Teks Uji Coba
                                    </label>
                                    <textarea
                                        v-model="testCustomMessage"
                                        rows="2"
                                        class="w-full px-3.5 py-2.5 rounded-[8px] border-[2.5px] border-black bg-white text-black font-bold text-sm shadow-[3px_3px_0px_#000] focus:outline-none focus:ring-0 focus:shadow-[4px_4px_0px_#000] focus:bg-[#FFFDF5] transition"
                                        placeholder="Tulis pesan pengujian di sini..."
                                    ></textarea>
                                </div>

                                <!-- Test Message Feedback Box -->
                                <div
                                    v-if="testMessageResult"
                                    :class="[
                                        'p-4 rounded-[10px] border-[3px] border-black text-sm space-y-2 transition shadow-[4px_4px_0px_#000]',
                                        testMessageResult.success
                                            ? 'bg-[#4CAF50]/15 text-black'
                                            : 'bg-[#FF5252]/15 text-black'
                                    ]"
                                >
                                    <div class="font-black flex items-center gap-2">
                                        <CheckCircle2 v-if="testMessageResult.success" class="w-4 h-4 text-black stroke-[2.5] shrink-0" />
                                        <AlertCircle v-else class="w-4 h-4 text-black stroke-[2.5] shrink-0" />
                                        <span>{{ testMessageResult.message }}</span>
                                    </div>

                                    <div v-if="testMessageResult.wa_url" class="pt-2">
                                        <a
                                            :href="testMessageResult.wa_url"
                                            target="_blank"
                                            class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-[#4CAF50] hover:bg-green-600 text-black border-2 border-black font-black uppercase tracking-wider text-xs rounded-[6px] shadow-[2px_2px_0px_#000] active:translate-x-[1px] active:translate-y-[1px] active:shadow-none transition-all cursor-pointer"
                                        >
                                            <ExternalLink class="w-3.5 h-3.5 stroke-[2.5]" />
                                            <span>Buka Tautan wa.me di Tab Baru</span>
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Section 4: Notification Message Templates -->
                            <div class="bg-white rounded-[12px] border-[3.5px] border-black p-6 shadow-[6px_6px_0px_#000] space-y-5">
                                <div class="border-b-[2.5px] border-black pb-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                                    <div>
                                        <h3 class="text-base sm:text-lg font-black text-black flex items-center gap-2">
                                            <Sparkles class="w-4 h-4 text-black stroke-[2.5]" />
                                            Template Pesan Notifikasi Otomatis
                                        </h3>
                                        <p class="text-xs sm:text-sm font-bold text-black/70 mt-0.5">
                                            Sesuaikan format pesan WhatsApp untuk Intake, Siap Ambil, dan Pengingat Piutang
                                        </p>
                                    </div>

                                    <!-- Template Sub-tabs Selector -->
                                    <div class="flex items-center gap-1.5 p-1.5 bg-[#FFFDF5] border-[2px] border-black rounded-[8px] shrink-0">
                                        <button
                                            type="button"
                                            @click="activeTemplateTab = 'intake'"
                                            :class="[
                                                'px-3 py-1.5 text-xs font-black uppercase tracking-wider rounded-[6px] transition-all cursor-pointer border-2',
                                                activeTemplateTab === 'intake'
                                                    ? 'bg-[#FFEB3B] text-black border-black shadow-[2px_2px_0px_#000]'
                                                    : 'text-black/70 hover:text-black border-transparent'
                                            ]"
                                        >
                                            Intake (Penerimaan)
                                        </button>
                                        <button
                                            type="button"
                                            @click="activeTemplateTab = 'ready'"
                                            :class="[
                                                'px-3 py-1.5 text-xs font-black uppercase tracking-wider rounded-[6px] transition-all cursor-pointer border-2',
                                                activeTemplateTab === 'ready'
                                                    ? 'bg-[#FFEB3B] text-black border-black shadow-[2px_2px_0px_#000]'
                                                    : 'text-black/70 hover:text-black border-transparent'
                                            ]"
                                        >
                                            Siap Ambil (Ready)
                                        </button>
                                        <button
                                            type="button"
                                            @click="activeTemplateTab = 'reminder'"
                                            :class="[
                                                'px-3 py-1.5 text-xs font-black uppercase tracking-wider rounded-[6px] transition-all cursor-pointer border-2',
                                                activeTemplateTab === 'reminder'
                                                    ? 'bg-[#FFEB3B] text-black border-black shadow-[2px_2px_0px_#000]'
                                                    : 'text-black/70 hover:text-black border-transparent'
                                            ]"
                                        >
                                            Pengingat Tagihan
                                        </button>
                                    </div>
                                </div>

                                <!-- Variable Chips Helper -->
                                <div class="bg-[#FFFDF5] p-4 rounded-[10px] border-[2.5px] border-black shadow-[3px_3px_0px_#000] space-y-2.5">
                                    <div class="text-xs font-black text-black flex items-center justify-between uppercase tracking-wide">
                                        <span>Klik variabel untuk disisipkan ke template:</span>
                                        <button
                                            type="button"
                                            @click="resetTemplate(activeTemplateTab)"
                                            class="text-black hover:text-[#FF5252] font-black underline text-xs cursor-pointer transition"
                                        >
                                            Kembalikan ke Template Default
                                        </button>
                                    </div>
                                    <div class="flex flex-wrap gap-2">
                                        <button
                                            type="button"
                                            @click="insertVariable('store_name')"
                                            class="px-2.5 py-1 rounded-[6px] border-[2px] border-black bg-white text-xs font-mono font-bold text-black shadow-[2px_2px_0px_#000] hover:bg-[#FFEB3B] active:translate-x-[1px] active:translate-y-[1px] active:shadow-none cursor-pointer transition-all"
                                        >
                                            + store_name
                                        </button>
                                        <button
                                            type="button"
                                            @click="insertVariable('customer_name')"
                                            class="px-2.5 py-1 rounded-[6px] border-[2px] border-black bg-white text-xs font-mono font-bold text-black shadow-[2px_2px_0px_#000] hover:bg-[#FFEB3B] active:translate-x-[1px] active:translate-y-[1px] active:shadow-none cursor-pointer transition-all"
                                        >
                                            + customer_name
                                        </button>
                                        <button
                                            type="button"
                                            @click="insertVariable('invoice_number')"
                                            class="px-2.5 py-1 rounded-[6px] border-[2px] border-black bg-white text-xs font-mono font-bold text-black shadow-[2px_2px_0px_#000] hover:bg-[#FFEB3B] active:translate-x-[1px] active:translate-y-[1px] active:shadow-none cursor-pointer transition-all"
                                        >
                                            + invoice_number
                                        </button>
                                        <button
                                            type="button"
                                            @click="insertVariable('items_summary')"
                                            class="px-2.5 py-1 rounded-[6px] border-[2px] border-black bg-white text-xs font-mono font-bold text-black shadow-[2px_2px_0px_#000] hover:bg-[#FFEB3B] active:translate-x-[1px] active:translate-y-[1px] active:shadow-none cursor-pointer transition-all"
                                        >
                                            + items_summary
                                        </button>
                                        <button
                                            type="button"
                                            @click="insertVariable('total_amount')"
                                            class="px-2.5 py-1 rounded-[6px] border-[2px] border-black bg-white text-xs font-mono font-bold text-black shadow-[2px_2px_0px_#000] hover:bg-[#FFEB3B] active:translate-x-[1px] active:translate-y-[1px] active:shadow-none cursor-pointer transition-all"
                                        >
                                            + total_amount
                                        </button>
                                        <button
                                            type="button"
                                            @click="insertVariable('paid_amount')"
                                            class="px-2.5 py-1 rounded-[6px] border-[2px] border-black bg-white text-xs font-mono font-bold text-black shadow-[2px_2px_0px_#000] hover:bg-[#FFEB3B] active:translate-x-[1px] active:translate-y-[1px] active:shadow-none cursor-pointer transition-all"
                                        >
                                            + paid_amount
                                        </button>
                                        <button
                                            type="button"
                                            @click="insertVariable('paid_info')"
                                            class="px-2.5 py-1 rounded-[6px] border-[2px] border-black bg-white text-xs font-mono font-bold text-black shadow-[2px_2px_0px_#000] hover:bg-[#FFEB3B] active:translate-x-[1px] active:translate-y-[1px] active:shadow-none cursor-pointer transition-all"
                                        >
                                            + paid_info
                                        </button>
                                        <button
                                            type="button"
                                            @click="insertVariable('rack_location')"
                                            class="px-2.5 py-1 rounded-[6px] border-[2px] border-black bg-white text-xs font-mono font-bold text-black shadow-[2px_2px_0px_#000] hover:bg-[#FFEB3B] active:translate-x-[1px] active:translate-y-[1px] active:shadow-none cursor-pointer transition-all"
                                        >
                                            + rack_location
                                        </button>
                                        <button
                                            type="button"
                                            @click="insertVariable('debt_amount')"
                                            class="px-2.5 py-1 rounded-[6px] border-[2px] border-black bg-white text-xs font-mono font-bold text-black shadow-[2px_2px_0px_#000] hover:bg-[#FFEB3B] active:translate-x-[1px] active:translate-y-[1px] active:shadow-none cursor-pointer transition-all"
                                        >
                                            + debt_amount
                                        </button>
                                        <button
                                            type="button"
                                            @click="insertVariable('status_text')"
                                            class="px-2.5 py-1 rounded-[6px] border-[2px] border-black bg-white text-xs font-mono font-bold text-black shadow-[2px_2px_0px_#000] hover:bg-[#FFEB3B] active:translate-x-[1px] active:translate-y-[1px] active:shadow-none cursor-pointer transition-all"
                                        >
                                            + status_text
                                        </button>
                                        <button
                                            type="button"
                                            @click="insertVariable('receipt_url')"
                                            class="px-2.5 py-1 rounded-[6px] border-[2px] border-black bg-white text-xs font-mono font-bold text-black shadow-[2px_2px_0px_#000] hover:bg-[#FFEB3B] active:translate-x-[1px] active:translate-y-[1px] active:shadow-none cursor-pointer transition-all"
                                        >
                                            + receipt_url
                                        </button>
                                    </div>
                                </div>

                                <!-- Editor & Live Preview Grid -->
                                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                                    <!-- Editor Column -->
                                    <div>
                                        <label class="block text-xs sm:text-sm font-black text-black mb-1.5 uppercase tracking-wide">
                                            Teks Template (Markdown WhatsApp: *bold*, _italic_)
                                        </label>

                                        <textarea
                                            v-if="activeTemplateTab === 'intake'"
                                            v-model="form['whatsapp.template_intake']"
                                            rows="12"
                                            class="w-full px-3.5 py-2.5 rounded-[8px] border-[2.5px] border-black bg-white text-black font-mono font-bold text-xs leading-relaxed shadow-[3px_3px_0px_#000] focus:outline-none focus:ring-0 focus:shadow-[4px_4px_0px_#000] focus:bg-[#FFFDF5] transition"
                                            placeholder="Tulis template konfirmasi intake..."
                                        ></textarea>

                                        <textarea
                                            v-else-if="activeTemplateTab === 'ready'"
                                            v-model="form['whatsapp.template_ready']"
                                            rows="12"
                                            class="w-full px-3.5 py-2.5 rounded-[8px] border-[2.5px] border-black bg-white text-black font-mono font-bold text-xs leading-relaxed shadow-[3px_3px_0px_#000] focus:outline-none focus:ring-0 focus:shadow-[4px_4px_0px_#000] focus:bg-[#FFFDF5] transition"
                                            placeholder="Tulis template siap ambil..."
                                        ></textarea>

                                        <textarea
                                            v-else
                                            v-model="form['whatsapp.template_reminder']"
                                            rows="12"
                                            class="w-full px-3.5 py-2.5 rounded-[8px] border-[2.5px] border-black bg-white text-black font-mono font-bold text-xs leading-relaxed shadow-[3px_3px_0px_#000] focus:outline-none focus:ring-0 focus:shadow-[4px_4px_0px_#000] focus:bg-[#FFFDF5] transition"
                                            placeholder="Tulis template pengingat piutang..."
                                        ></textarea>
                                    </div>

                                    <!-- Live Preview Column -->
                                    <div>
                                        <label class="block text-xs sm:text-sm font-black text-black mb-1.5 uppercase tracking-wide">
                                            Pratinjau Tampilan Pesan Pelanggan
                                        </label>
                                        <div class="bg-[#4CAF50]/10 border-[2.5px] border-black rounded-[10px] p-4 text-xs font-mono font-bold text-black whitespace-pre-wrap break-words leading-relaxed shadow-[3px_3px_0px_#000] min-h-[260px] max-h-[480px] overflow-y-auto">
                                            {{ previewText }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Sticky Actions Footer -->
                        <div class="bg-white rounded-[12px] border-[3.5px] border-black p-5 shadow-[6px_6px_0px_#000] flex flex-col sm:flex-row items-center justify-between gap-4">
                            <div class="text-xs sm:text-sm font-bold text-black/70">
                                Pastikan seluruh parameter telah ditinjau sebelum disimpan ke database.
                            </div>
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="bg-[#FFEB3B] hover:bg-[#fff066] text-black border-[3px] border-black rounded-[8px] min-h-[46px] px-6 font-black uppercase tracking-wider text-sm flex items-center justify-center gap-2 cursor-pointer shadow-[4px_4px_0px_#000] active:translate-x-[2px] active:translate-y-[2px] active:shadow-none transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                <Save class="w-4 h-4 stroke-[2.5]" />
                                <span v-if="form.processing">Menyimpan...</span>
                                <span v-else>Simpan Pengaturan</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
