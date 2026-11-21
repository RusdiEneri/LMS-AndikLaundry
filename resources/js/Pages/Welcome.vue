<script setup lang="ts">
import WashingMachineIllustration from '@/Components/Landing/WashingMachineIllustration.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import {
    Check,
    ChevronDown,
    Clock,
    ExternalLink,
    Flame,
    MapPin,
    Menu,
    MessageCircle,
    Phone,
    ReceiptText,
    RotateCcw,
    Scale,
    ShieldCheck,
    Shirt,
    Sparkles,
    Truck,
    X,
} from 'lucide-vue-next';
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';

const props = defineProps<{
    canLogin?: boolean;
    stats?: { customers: number; orders: number; laundry_kg: number };
    store?: { name: string; phone: string; address: string };
}>();

const storeName = computed(() => props.store?.name || 'Andik Laundry');
const storePhone = computed(() => props.store?.phone || '6281234567890');
const storeAddress = computed(
    () =>
        props.store?.address ||
        'Hendrosari, RT.06/RW.03, Hendrosari, Kec. Menganti, Kabupaten Gresik, Jawa Timur 61174',
);

const whatsappUrl = computed(() => {
    const phone = storePhone.value.replace(/[^0-9]/g, '');
    const text = encodeURIComponent(
        `Halo ${storeName.value}, saya ingin bertanya tentang layanan laundry dan antar jemput.`,
    );

    return `https://wa.me/${phone}?text=${text}`;
});

function getServiceWhatsappUrl(serviceName: string) {
    const phone = storePhone.value.replace(/[^0-9]/g, '');
    const text = encodeURIComponent(
        `Halo ${storeName.value}, saya ingin pesan layanan ${serviceName}. Mohon info penjemputan.`,
    );

    return `https://wa.me/${phone}?text=${text}`;
}

const phoneUrl = computed(() => `tel:${storePhone.value.replace(/[^0-9+]/g, '')}`);

const storePhoneLabel = computed(() => {
    const digits = storePhone.value.replace(/[^0-9]/g, '');
    const local = digits.startsWith('62') ? `0${digits.slice(2)}` : digits;

    return local.replace(/^(\d{4})(\d{4})(\d+)$/, '$1-$2-$3');
});

const navLinks = [
    { href: '#lacak', label: 'Cek Nota' },
    { href: '#layanan', label: 'Layanan & Tarif' },
    { href: '#standar', label: 'Standar Mutu' },
    { href: '#proses', label: 'Alur Kerja' },
    { href: '#lokasi', label: 'Lokasi' },
    { href: '#faq', label: 'FAQ' },
];

/* ------------------------------------------------------------------
   Cek nota
   ------------------------------------------------------------------ */
const orderQuery = ref('');
const orderQueryError = ref('');
const isCheckingOrder = ref(false);

function submitOrderCheck() {
    const query = orderQuery.value.trim();
    const digits = query.replace(/[^0-9]/g, '');
    const looksLikePhone = digits.length >= 8 && digits.length <= 15;
    const looksLikeInvoice = /^[A-Za-z0-9-]{4,}$/.test(query);

    if (query === '') {
        orderQueryError.value = 'Masukkan nomor nota atau nomor HP Anda.';

        return;
    }

    if (!looksLikePhone && !looksLikeInvoice) {
        orderQueryError.value = 'Format belum dikenali. Contoh: AL-20261115-001 atau 08123456789.';

        return;
    }

    orderQueryError.value = '';
    isCheckingOrder.value = true;

    router.get(
        '/nota',
        { q: query },
        {
            onFinish: () => {
                isCheckingOrder.value = false;
            },
        },
    );
}

/* ------------------------------------------------------------------
   Layanan
   ------------------------------------------------------------------ */
const services = [
    {
        id: 'cuci-setrika',
        name: 'Cuci Setrika',
        unit: 'kg',
        price: 'Rp 8.000',
        minWeight: 'Min. 2 kg',
        label: 'Paling Lengkap',
        featured: true,
        desc: 'Paket all-in-one: dicuci higienis, dikeringkan mesin, disetrika uap, lalu dikemas wangi dan terlindung debu.',
        features: [
            'Dicuci higienis (1 mesin 1 pelanggan)',
            'Setrika uap profesional, anti-gosong',
            'Pewangi premium tahan lama',
            'Kemasan plastik rapi kedap debu',
        ],
    },
    {
        id: 'cuci-kering',
        name: 'Cuci Kering Lipat',
        unit: 'kg',
        price: 'Rp 6.000',
        minWeight: 'Min. 2 kg',
        desc: 'Dicuci higienis dan dikeringkan mesin dengan suhu terkontrol, lalu disortir dan dilipat rapi siap masuk lemari.',
        features: [],
    },
    {
        id: 'cuci-basah',
        name: 'Cuci Basah',
        unit: 'kg',
        price: 'Rp 5.000',
        minWeight: 'Min. 2 kg',
        desc: 'Dicuci dan dibilas bersih menggunakan deterjen ramah serat, diperas mesin, siap dijemur sendiri di rumah.',
        features: [],
    },
    {
        id: 'cuci-karpet',
        name: 'Cuci Karpet',
        unit: 'm²',
        price: 'Rp 15.000',
        minWeight: 'Hitung P × L',
        desc: 'Pembersihan mendalam untuk karpet rumah, kantor, atau masjid guna mengangkat debu, tungau, dan bau apek.',
        features: [],
    },
];

const featuredService = computed(() => services.find((service) => service.featured)!);
const otherServices = computed(() => services.filter((service) => !service.featured));

/* ------------------------------------------------------------------
   Jaminan Mutu
   ------------------------------------------------------------------ */
const qualityGuarantees = [
    {
        icon: Scale,
        title: 'Timbangan Digital Terbuka',
        desc: 'Ditimbang langsung di depan Anda atau dicatat akurat pada nota digital WhatsApp. Nol manipulasi timbangan, jujur hingga gram terakhir.',
    },
    {
        icon: ShieldCheck,
        title: '1 Mesin 1 Pelanggan',
        desc: 'Cucian Anda diproses pada drum tersendiri. Kami tidak pernah mencampur pakaian Anda dengan milik orang lain demi higienitas maksimal.',
    },
    {
        icon: RotateCcw,
        title: 'Garansi Cuci Ulang',
        desc: 'Kurang wangi atau masih ada noda tertinggal? Beritahu kami dalam 1x24 jam setelah selesai, kami cuci ulang gratis tanpa biaya tambahan.',
    },
];

/* ------------------------------------------------------------------
   Proses
   ------------------------------------------------------------------ */
const processSteps = [
    {
        step: '1',
        label: 'Drop / Jemput',
        desc: 'Bawa cucian ke outlet atau pesan penjemputan gratis via WhatsApp.',
    },
    {
        step: '2',
        label: 'Timbang & Nota',
        desc: 'Ditimbang transparan di depan Anda, tautan nota langsung masuk ke WhatsApp.',
    },
    {
        step: '3',
        label: 'Cuci Higienis',
        desc: 'Deterjen ramah serat, 1 mesin 1 pelanggan tanpa pernah dicampur.',
    },
    {
        step: '4',
        label: 'Kering & Setrika',
        desc: 'Pengering suhu terkontrol dilanjut setrika uap licin tanpa risiko gosong.',
    },
    {
        step: '5',
        label: 'Kemas & Siap',
        desc: 'Dikemas rapi kedap debu, siap diambil atau kami antar kembali ke rumah.',
    },
];

/* ------------------------------------------------------------------
   FAQ
   ------------------------------------------------------------------ */
const faqs = [
    {
        q: 'Berapa minimal berat cucian kiloan?',
        a: 'Batas minimal layanan kiloan adalah 2 kg. Jika berat timbangan di bawah 2 kg, biaya tetap dihitung setara minimal 2 kg.',
    },
    {
        q: 'Apakah pakaian saya aman dan tidak tertukar?',
        a: 'Sangat aman. Kami menerapkan prinsip 1 mesin 1 pelanggan (tidak pernah dicampur). Setiap pesanan memiliki nomor nota unik dan label identifikasi tersendiri.',
    },
    {
        q: 'Berapa lama waktu pengerjaan?',
        a: 'Layanan reguler selesai dalam 2–3 hari kerja. Tersedia juga layanan kilat / ekspres yang selesai di hari yang sama dengan tambahan biaya Rp 2.000 per kg.',
    },
    {
        q: 'Bagaimana ketentuan antar-jemput gratis?',
        a: 'Layanan antar-jemput gratis berlaku untuk radius hingga 3 km dari outlet kami di Hendrosari, Menganti. Cukup kirim alamat atau bagikan lokasi via WhatsApp.',
    },
    {
        q: 'Bagaimana cara melacak progres cucian?',
        a: 'Setelah pesanan diproses, nomor nota dan tautan pelacakan langsung dikirim ke WhatsApp Anda. Anda juga bisa memasukkan nomor nota atau nomor HP di kolom cek nota pada halaman ini tanpa perlu login.',
    },
    {
        q: 'Metode pembayaran apa saja yang diterima?',
        a: 'Kami menerima uang tunai di kasir serta QRIS dari seluruh aplikasi mobile banking dan dompet digital (GoPay, OVO, Dana, ShopeePay). Pembayaran dapat dilakukan di awal atau saat cucian diserahkan.',
    },
];

const activeFaqIndex = ref<number | null>(0);

function toggleFaq(index: number) {
    activeFaqIndex.value = activeFaqIndex.value === index ? null : index;
}

/* ------------------------------------------------------------------
   Statistik nyata dari database
   ------------------------------------------------------------------ */
const stats = computed(() => props.stats ?? { customers: 0, orders: 0, laundry_kg: 0 });
const hasStats = computed(
    () => stats.value.customers > 0 || stats.value.orders > 0 || stats.value.laundry_kg > 0,
);

const animatedCustomers = ref(0);
const animatedOrders = ref(0);
const animatedLaundryKg = ref(0);
const statsSection = ref<HTMLElement | null>(null);

const customersLabel = computed(() =>
    Math.round(animatedCustomers.value).toLocaleString('id-ID'),
);
const ordersLabel = computed(() => Math.round(animatedOrders.value).toLocaleString('id-ID'));
const laundryKgLabel = computed(() =>
    Math.round(animatedLaundryKg.value).toLocaleString('id-ID'),
);

let counterRafId: number | null = null;
let statsObserver: IntersectionObserver | null = null;
let countersStarted = false;

function setFinalCounters() {
    animatedCustomers.value = stats.value.customers;
    animatedOrders.value = stats.value.orders;
    animatedLaundryKg.value = stats.value.laundry_kg;
}

function animateCounters() {
    if (countersStarted || typeof window === 'undefined') {
        return;
    }

    countersStarted = true;

    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        setFinalCounters();

        return;
    }

    const targets = stats.value;
    const duration = 1400;
    const startTime = performance.now();
    const easeOutCubic = (t: number) => 1 - Math.pow(1 - t, 3);

    const step = (now: number) => {
        const progress = Math.min((now - startTime) / duration, 1);
        const ease = easeOutCubic(progress);

        animatedCustomers.value = targets.customers * ease;
        animatedOrders.value = targets.orders * ease;
        animatedLaundryKg.value = targets.laundry_kg * ease;

        if (progress < 1) {
            counterRafId = requestAnimationFrame(step);

            return;
        }

        setFinalCounters();
        counterRafId = null;
    };

    counterRafId = requestAnimationFrame(step);
}

function setupStatsObserver() {
    if (!hasStats.value || !statsSection.value || typeof window === 'undefined') {
        return;
    }

    if (!('IntersectionObserver' in window)) {
        setFinalCounters();

        return;
    }

    statsObserver = new IntersectionObserver(
        (entries) => {
            if (entries.some((entry) => entry.isIntersecting)) {
                animateCounters();
                statsObserver?.disconnect();
            }
        },
        { threshold: 0.35 },
    );

    statsObserver.observe(statsSection.value);
}

/* ------------------------------------------------------------------
   3D Zoom Splash Screen (Vean Style)
   ------------------------------------------------------------------ */
const showSplash = ref(true);
const splashStage = ref(0);
let splashTimer1: number | null = null;
let splashTimer2: number | null = null;
let splashTimer3: number | null = null;

function cleanupSplashListeners() {
    if (typeof window !== 'undefined') {
        window.removeEventListener('wheel', handleSplashWheel);
        window.removeEventListener('touchmove', handleSplashTouch);
    }
}

function triggerZoomOut() {
    if (!showSplash.value || splashStage.value === 2) {
        return;
    }

    if (splashTimer1 !== null) {
        clearTimeout(splashTimer1);
        splashTimer1 = null;
    }
    if (splashTimer2 !== null) {
        clearTimeout(splashTimer2);
        splashTimer2 = null;
    }
    if (splashTimer3 !== null) {
        clearTimeout(splashTimer3);
        splashTimer3 = null;
    }

    cleanupSplashListeners();
    splashStage.value = 2;

    splashTimer3 = window.setTimeout(() => {
        showSplash.value = false;
        if (typeof document !== 'undefined' && !menuOpen.value) {
            document.body.style.overflow = '';
        }
    }, 700);
}

function skipSplash() {
    if (!showSplash.value) {
        return;
    }

    if (splashTimer1 !== null) {
        clearTimeout(splashTimer1);
        splashTimer1 = null;
    }
    if (splashTimer2 !== null) {
        clearTimeout(splashTimer2);
        splashTimer2 = null;
    }
    if (splashTimer3 !== null) {
        clearTimeout(splashTimer3);
        splashTimer3 = null;
    }

    cleanupSplashListeners();
    showSplash.value = false;
    if (typeof document !== 'undefined' && !menuOpen.value) {
        document.body.style.overflow = '';
    }
}

function handleSplashAction() {
    if (splashStage.value < 2) {
        triggerZoomOut();
    } else {
        skipSplash();
    }
}

function handleSplashWheel(e: WheelEvent) {
    if (showSplash.value && e.deltaY > 0) {
        triggerZoomOut();
    }
}

function handleSplashTouch() {
    if (showSplash.value) {
        triggerZoomOut();
    }
}

/* ------------------------------------------------------------------
   Navigasi, scroll, dan reveal
   ------------------------------------------------------------------ */
const menuOpen = ref(false);
const menuButtonRef = ref<HTMLButtonElement | null>(null);
const menuPanelRef = ref<HTMLElement | null>(null);
const isScrolled = ref(false);

watch(menuOpen, async (open) => {
    if (typeof document === 'undefined') {
        return;
    }

    document.body.style.overflow = open ? 'hidden' : '';

    if (open) {
        await nextTick();
        menuPanelRef.value?.focus();
    }
});

function handleKeydown(event: KeyboardEvent) {
    if (event.key === 'Escape' && menuOpen.value) {
        menuOpen.value = false;
        menuButtonRef.value?.focus();
    }
}

let scrollFrame: number | null = null;

function handleScroll() {
    if (typeof window === 'undefined' || scrollFrame !== null) {
        return;
    }

    scrollFrame = requestAnimationFrame(() => {
        scrollFrame = null;

        isScrolled.value = window.scrollY > 12;
    });
}

let revealObserver: IntersectionObserver | null = null;

function setupRevealObserver() {
    if (typeof window === 'undefined') {
        return;
    }

    const elements = document.querySelectorAll<HTMLElement>('.landing-reveal');

    if (!('IntersectionObserver' in window)) {
        elements.forEach((element) => element.classList.add('is-visible'));

        return;
    }

    revealObserver = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    revealObserver?.unobserve(entry.target);
                }
            });
        },
        {
            rootMargin: '0px 0px -48px 0px',
            threshold: 0.08,
        },
    );

    elements.forEach((element) => revealObserver?.observe(element));
}

onMounted(() => {
    if (typeof window !== 'undefined') {
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            showSplash.value = false;
        } else {
            document.body.style.overflow = 'hidden';

            window.addEventListener('wheel', handleSplashWheel, { passive: true });
            window.addEventListener('touchmove', handleSplashTouch, { passive: true });

            splashTimer1 = window.setTimeout(() => {
                splashStage.value = 1;
            }, 60);

            splashTimer2 = window.setTimeout(() => {
                triggerZoomOut();
            }, 950);
        }
    }

    handleScroll();
    window.addEventListener('scroll', handleScroll, { passive: true });
    window.addEventListener('keydown', handleKeydown);

    nextTick(() => {
        setupRevealObserver();
        setupStatsObserver();
    });
});

onBeforeUnmount(() => {
    cleanupSplashListeners();
    if (splashTimer1 !== null) {
        clearTimeout(splashTimer1);
        splashTimer1 = null;
    }
    if (splashTimer2 !== null) {
        clearTimeout(splashTimer2);
        splashTimer2 = null;
    }
    if (splashTimer3 !== null) {
        clearTimeout(splashTimer3);
        splashTimer3 = null;
    }

    if (counterRafId !== null) {
        cancelAnimationFrame(counterRafId);
        counterRafId = null;
    }

    if (scrollFrame !== null) {
        cancelAnimationFrame(scrollFrame);
        scrollFrame = null;
    }

    window.removeEventListener('scroll', handleScroll);
    window.removeEventListener('keydown', handleKeydown);

    revealObserver?.disconnect();
    statsObserver?.disconnect();

    if (typeof document !== 'undefined') {
        document.body.style.overflow = '';
    }
});
</script>

<template>
    <Head title="Andik Laundry - Tempat Laundry Modern di Hendrosari, Gresik">
        <meta
            name="description"
            content="Layanan cuci kiloan dan cuci karpet di Hendrosari, Menganti, Gresik. Bersih higienis 1 mesin 1 pelanggan, setrika uap rapi, nota digital WhatsApp, dan antar jemput gratis hingga 3 km."
        />
        <link rel="icon" type="image/webp" href="/images/logo-a.webp?v=1.2" />
        <link rel="shortcut icon" href="/images/logo-a.webp?v=1.2" />
        <link rel="apple-touch-icon" href="/images/logo-a.png?v=1.2" />
    </Head>

    <div class="min-h-screen overflow-x-clip bg-bg font-sans text-navy-950 antialiased">
        <!-- 3D Card Zoom Splash Screen -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition-opacity duration-200"
                leave-active-class="transition-opacity duration-500 ease-out"
                leave-to-class="opacity-0"
            >
                <div
                    v-if="showSplash"
                    class="fixed inset-0 z-[100] flex items-center justify-center overflow-hidden bg-black select-none splash-perspective cursor-pointer transition-colors duration-700 ease-out"
                    :class="splashStage === 2 ? 'bg-black/0 pointer-events-none' : 'bg-black'"
                    aria-hidden="true"
                    @click="handleSplashAction"
                >
                    <!-- Deep Ambient Radial Glow matching Logo A -->
                    <div
                        class="pointer-events-none absolute h-[320px] w-[320px] sm:h-[460px] sm:w-[460px] rounded-full bg-gradient-to-tr from-[#1f7fc4]/35 via-[#39c5a8]/25 to-transparent blur-[90px] transition-all duration-700 ease-out"
                        :class="splashStage === 2 ? 'scale-[3.5] opacity-0' : splashStage === 1 ? 'scale-100 opacity-100' : 'scale-75 opacity-0'"
                    ></div>

                    <!-- 3D Logo A Only (Vean Style) -->
                    <div
                        class="splash-logo relative flex items-center justify-center will-change-transform"
                        :class="{
                            'splash-logo-stage-0': splashStage === 0,
                            'splash-logo-stage-1': splashStage === 1,
                            'splash-logo-stage-2': splashStage === 2,
                        }"
                    >
                        <img
                            src="/images/logo-a.webp"
                            alt="Andik Laundry"
                            class="h-36 w-auto sm:h-48 md:h-56 object-contain filter drop-shadow-[0_20px_50px_rgba(31,127,196,0.6)]"
                            width="224"
                            height="215"
                            loading="eager"
                            decoding="sync"
                        />
                    </div>

                    <!-- Subtle hint -->
                    <div
                        class="pointer-events-none absolute bottom-8 text-[11px] font-semibold tracking-widest uppercase text-slate-500/80 transition-opacity duration-300"
                        :class="splashStage === 1 ? 'opacity-100' : 'opacity-0'"
                    >
                        Scroll atau klik untuk lanjut
                    </div>
                </div>
            </Transition>
        </Teleport>

        <a
            href="#main-content"
            class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-[60] focus:rounded-pill focus:bg-accent-500 focus:px-4 focus:py-2 focus:font-bold focus:text-navy-950 focus:shadow-lg focus:outline-none"
        >
            Lewati ke konten utama
        </a>

        <!-- Floating Navbar -->
        <header
            class="fixed inset-x-3 sm:inset-x-6 top-3 sm:top-4 z-40 mx-auto max-w-5xl transition-all duration-300"
        >
            <div
                class="flex h-14 sm:h-16 items-center justify-between gap-3 rounded-full border px-3.5 sm:px-6 shadow-[0_8px_30px_rgba(8,16,80,0.08)] backdrop-blur-md transition-all duration-300"
                :class="
                    isScrolled
                        ? 'border-border/90 bg-white/95 shadow-[0_12px_36px_rgba(8,16,80,0.12)]'
                        : 'border-white/80 bg-white/90'
                "
            >
                <!-- Logo didalam navbar -->
                <Link
                    href="/"
                    class="flex items-center gap-2 rounded-full py-1 focus-visible:outline-none transition-transform hover:scale-105 active:scale-95"
                    aria-label="Andik Laundry, kembali ke beranda"
                >
                    <img
                        src="/images/logo-a.webp"
                        alt="Logo"
                        class="h-7 w-auto sm:h-8 object-contain"
                        width="36"
                        height="34"
                    />
                </Link>

                <!-- Nav Links Desktop (tanpa Beranda) -->
                <nav class="hidden items-center gap-1 md:flex" aria-label="Navigasi utama">
                    <a
                        v-for="link in navLinks"
                        :key="link.href"
                        :href="link.href"
                        class="nav-link rounded-full px-3 py-1.5 text-xs lg:text-[13px] font-semibold text-slate-700 transition-colors hover:bg-slate-100 hover:text-navy-950 focus-visible:outline-none"
                    >
                        <span class="roll">
                            <span :data-t="link.label">{{ link.label }}</span>
                        </span>
                    </a>
                </nav>

                <!-- Action Button didalam navbar (WhatsApp CTA) -->
                <div class="flex items-center gap-2">
                    <a
                        :href="whatsappUrl"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="nav-btn inline-flex h-9 sm:h-10 items-center justify-center gap-2 rounded-full bg-accent-500 px-3.5 sm:px-4 text-xs sm:text-sm font-bold text-navy-950 shadow-xs transition-all hover:bg-accent-600 hover:scale-105 active:scale-95 focus-visible:outline-none"
                        aria-label="Hubungi WhatsApp Andik Laundry"
                        title="Chat WhatsApp"
                    >
                        <MessageCircle class="h-4 w-4 stroke-[2.2]" aria-hidden="true" />
                        <span class="roll hidden sm:inline-block">
                            <span data-t="WhatsApp">WhatsApp</span>
                        </span>
                    </a>

                    <!-- Mobile Menu Trigger -->
                    <button
                        ref="menuButtonRef"
                        type="button"
                        class="inline-flex h-9 w-9 sm:h-10 sm:w-10 items-center justify-center rounded-full text-navy-950 transition-colors hover:bg-accent-100 focus-visible:outline-none md:hidden"
                        :aria-expanded="menuOpen"
                        aria-controls="mobile-menu"
                        :aria-label="menuOpen ? 'Tutup menu' : 'Buka menu'"
                        @click="menuOpen = !menuOpen"
                    >
                        <X v-if="menuOpen" class="h-5 w-5" aria-hidden="true" />
                        <Menu v-else class="h-5 w-5" aria-hidden="true" />
                    </button>
                </div>
            </div>
        </header>

        <!-- Menu seluler -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition-opacity duration-200 ease-out"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition-opacity duration-150 ease-in"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div
                    v-if="menuOpen"
                    class="fixed inset-0 z-50 bg-navy-950/50 md:hidden"
                    aria-hidden="true"
                    @click="menuOpen = false"
                ></div>
            </Transition>

            <Transition
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="opacity-0 -translate-y-3"
                enter-to-class="opacity-100 translate-y-0"
                leave-active-class="transition duration-150 ease-in"
                leave-from-class="opacity-100 translate-y-0"
                leave-to-class="opacity-0 -translate-y-3"
            >
                <div
                    v-if="menuOpen"
                    id="mobile-menu"
                    ref="menuPanelRef"
                    role="dialog"
                    aria-modal="true"
                    aria-label="Menu navigasi"
                    tabindex="-1"
                    class="fixed inset-x-3 sm:inset-x-6 top-[72px] sm:top-[80px] z-50 max-h-[calc(100dvh-92px)] overflow-y-auto rounded-[24px] border border-border/90 bg-white/95 backdrop-blur-md p-3 text-navy-950 shadow-2xl focus:outline-none md:hidden"
                >
                    <nav class="flex flex-col" aria-label="Navigasi seluler">
                        <a
                            v-for="link in navLinks"
                            :key="link.href"
                            :href="link.href"
                            class="flex min-h-[48px] items-center rounded-xl px-4 text-base font-semibold transition-colors hover:bg-accent-100 hover:text-accent-700"
                            @click="menuOpen = false"
                        >
                            {{ link.label }}
                        </a>
                    </nav>

                    <div class="mt-2 border-t border-border pt-3">
                        <a
                            :href="whatsappUrl"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="flex min-h-[48px] items-center justify-center gap-2 rounded-full bg-accent-500 px-4 text-sm font-bold text-navy-950 transition-colors hover:bg-accent-600"
                            @click="menuOpen = false"
                        >
                            <MessageCircle class="h-5 w-5" aria-hidden="true" />
                            Chat WhatsApp
                        </a>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <main id="main-content">
            <!-- Hero -->
            <section
                id="beranda"
                class="relative overflow-hidden bg-bg pb-16 pt-28 sm:pb-20 sm:pt-32 lg:pb-24 lg:pt-36"
            >
                <div class="pointer-events-none absolute inset-0" aria-hidden="true">
                    <div
                        class="absolute -right-40 top-20 hidden h-[560px] w-[560px] rounded-full border border-border/80 lg:block"
                    ></div>
                    <div
                        class="absolute -right-24 top-36 hidden h-[400px] w-[400px] rounded-full border border-accent-100 lg:block"
                    ></div>
                </div>

                <div
                    class="relative mx-auto grid max-w-7xl grid-cols-1 items-center gap-10 px-4 sm:px-6 lg:grid-cols-12 lg:gap-10 lg:px-8"
                >
                    <div class="min-w-0 lg:col-span-6">
                        <div
                            class="landing-enter relative overflow-hidden rounded-[28px] bg-navy-950 p-6 text-white shadow-[0_20px_50px_rgba(8,16,80,0.22)] ring-1 ring-white/10 sm:p-8 lg:overflow-visible lg:rounded-none lg:bg-transparent lg:p-0 lg:text-inherit lg:shadow-none lg:ring-0"
                            style="--enter-delay: 80ms"
                        >
                            <div class="relative">
                                <div
                                    class="inline-flex items-center rounded-pill bg-white/10 px-3.5 py-1.5 text-[11px] font-bold uppercase tracking-[0.16em] text-accent-400 backdrop-blur-xs ring-1 ring-white/15 lg:bg-accent-100 lg:px-3.5 lg:py-1.5 lg:text-accent-700 lg:ring-1 lg:ring-accent-600/20"
                                >
                                    Hendrosari, Gresik
                                </div>

                                <h1
                                    class="mt-4 font-heading text-[2.1rem] font-extrabold uppercase leading-[1.04] tracking-tight text-white sm:text-5xl sm:leading-[0.98] lg:text-[3.75rem] lg:text-navy-950"
                                >
                                    Cucian Kotor,<br />
                                    <span class="text-accent-400 lg:text-accent-600">Pulang Kinclong.</span>
                                </h1>

                                <p
                                    class="mt-5 text-sm leading-relaxed text-slate-300 sm:text-base lg:mt-6 lg:max-w-xl lg:text-lg lg:text-slate-700"
                                >
                                    Tinggal chat WhatsApp atau mampir ke outlet, tumpukan baju kotor langsung
                                    beres. Di Andik Laundry, pakaian dicuci higienis terpisah 1 mesin 1 pelanggan,
                                    timbangan transparan, nota digital langsung ke WhatsApp, dan gratis jemput-antar
                                    hingga 3 km.
                                </p>

                                <div class="mt-7 flex flex-col gap-3 sm:flex-row lg:mt-8">
                                    <a
                                        :href="whatsappUrl"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="inline-flex min-h-[48px] items-center justify-center gap-2 rounded-pill bg-accent-500 px-6 text-sm font-bold text-navy-950 shadow-md shadow-accent-500/20 transition-colors hover:bg-accent-600 focus-visible:outline-none lg:shadow-none"
                                    >
                                        <MessageCircle class="h-4 w-4" aria-hidden="true" />
                                        Pesan antar jemput
                                    </a>
                                    <a
                                        href="#tarif"
                                        class="inline-flex min-h-[48px] items-center justify-center rounded-pill border border-white/20 bg-white/5 px-6 text-sm font-bold text-white transition-colors hover:border-white/30 hover:bg-white/10 focus-visible:outline-none lg:border-navy-900/20 lg:bg-white lg:text-navy-950 lg:hover:bg-slate-50 lg:shadow-xs"
                                    >
                                        Lihat tarif
                                    </a>
                                </div>

                                <div
                                    class="mt-6 flex flex-wrap items-center gap-x-5 gap-y-2 border-t border-white/10 pt-5 text-xs font-medium text-slate-300 lg:border-t-0 lg:pt-0 lg:text-slate-600"
                                >
                                    <span class="inline-flex items-center gap-2">
                                        <Clock class="h-4 w-4 text-accent-400 lg:text-accent-700" aria-hidden="true" />
                                        Buka setiap hari, 07.00–20.00 WIB
                                    </span>
                                    <span class="inline-flex items-center gap-2">
                                        <Truck class="h-4 w-4 text-accent-400 lg:text-accent-700" aria-hidden="true" />
                                        Antar jemput gratis maksimal 3 km
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="min-w-0 lg:col-span-6">
                        <div class="landing-enter mx-auto max-w-[440px]" style="--enter-delay: 140ms">
                            <WashingMachineIllustration />
                            <div
                                class="mt-3 flex items-center justify-center text-center text-[11px] font-bold uppercase tracking-[0.16em] text-accent-700"
                            >
                                <span>Bersih • Wangi • Rapi</span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Cek nota (Floating Utility Card) -->
            <section id="lacak" class="relative mb-14 sm:mb-20">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div
                        class="landing-reveal rounded-[28px] border border-navy-800 bg-navy-900 p-6 text-white shadow-[0_16px_40px_rgba(8,16,80,0.18)] transition-all duration-200 hover:-translate-y-1 hover:shadow-xl sm:p-9"
                    >
                        <div class="grid grid-cols-1 items-center gap-6 lg:grid-cols-12 lg:gap-10">
                            <div class="lg:col-span-5">
                                <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-accent-400">
                                    Lacak Pesanan
                                </p>
                                <h2 class="mt-1.5 font-heading text-2xl font-bold text-white sm:text-3xl">
                                    Sudah punya nota cucian?
                                </h2>
                                <p class="mt-2 text-sm leading-relaxed text-slate-300">
                                    Masukkan nomor nota atau nomor HP Anda. Status pencucian terbuka
                                    secara transparan tanpa perlu login.
                                </p>
                            </div>

                            <div class="min-w-0 lg:col-span-7">
                                <form
                                    class="flex flex-col gap-2 rounded-[20px] bg-white p-2 sm:flex-row sm:items-center sm:rounded-pill"
                                    @submit.prevent="submitOrderCheck"
                                >
                                    <div class="flex min-w-0 flex-1 items-center gap-3 px-3 py-1.5">
                                        <ReceiptText class="h-5 w-5 shrink-0 text-accent-700" aria-hidden="true" />
                                        <label for="order-query" class="sr-only">
                                            Nomor nota atau nomor HP
                                        </label>
                                        <input
                                            id="order-query"
                                            v-model="orderQuery"
                                            type="text"
                                            inputmode="text"
                                            autocomplete="off"
                                            placeholder="Nomor nota (AL-...) atau nomor HP terdaftar"
                                            class="h-10 w-full min-w-0 bg-transparent text-sm font-semibold text-navy-950 placeholder-slate-400 focus:outline-none"
                                            @input="orderQueryError = ''"
                                        />
                                    </div>
                                    <button
                                        type="submit"
                                        :disabled="isCheckingOrder"
                                        class="inline-flex min-h-[48px] items-center justify-center gap-2 rounded-pill bg-accent-500 px-6 text-sm font-bold text-navy-950 transition-colors hover:bg-accent-600 disabled:opacity-60"
                                    >
                                        {{ isCheckingOrder ? 'Memeriksa…' : 'Cek status' }}
                                    </button>
                                </form>

                                <p
                                    v-if="orderQueryError"
                                    class="mt-2 rounded-xl bg-danger/20 border border-danger/30 px-3 py-2 text-xs font-semibold text-white"
                                    role="alert"
                                >
                                    {{ orderQueryError }}
                                </p>
                                <p v-else class="mt-2 px-1 text-xs text-slate-300">
                                    Contoh format: AL-20261115-001 atau 08123456789
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Keunggulan Layanan -->
            <section id="kenapa" class="py-16 sm:py-20">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div
                        class="landing-reveal grid grid-cols-1 gap-8 md:grid-cols-12 md:items-end lg:gap-12"
                    >
                        <div class="md:col-span-7">
                            <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-accent-700">
                                Keunggulan Layanan
                            </p>
                            <h2
                                class="mt-2 text-balance font-heading text-3xl font-extrabold uppercase leading-tight tracking-tight text-navy-950 sm:text-4xl"
                            >
                                Bersih, wangi, rapi. Setiap kali.
                            </h2>
                        </div>
                        <div class="md:col-span-5">
                            <p class="text-sm leading-relaxed text-slate-700 sm:text-base">
                                Sejak 2009, kami menjaga satu komitmen sederhana: memperlakukan pakaian pelanggan
                                seperti pakaian kami sendiri dengan standar higienis yang tidak pernah ditawar.
                            </p>
                        </div>
                    </div>

                    <div class="mt-10 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                        <article
                            class="landing-reveal flex flex-col rounded-[24px] border border-border/80 bg-white p-7 text-navy-950 shadow-xs transition-all duration-200 hover:-translate-y-1 hover:shadow-md"
                        >
                            <div
                                class="flex h-12 w-12 items-center justify-center rounded-2xl bg-accent-100 text-accent-700"
                            >
                                <Shirt class="h-6 w-6" aria-hidden="true" />
                            </div>
                            <h3 class="mt-5 font-heading text-lg font-bold text-navy-950">
                                1 Mesin 1 Pelanggan
                            </h3>
                            <p class="mt-2 text-sm leading-relaxed text-slate-600">
                                Pakaian Anda dicuci tersendiri dalam satu drum. Tidak pernah dicampur dengan pakaian
                                keluarga lain demi higienitas maksimal dan mencegah baju tertukar.
                            </p>
                        </article>

                        <article
                            class="landing-reveal flex flex-col rounded-[24px] border border-border/80 bg-white p-7 text-navy-950 shadow-xs transition-all duration-200 hover:-translate-y-1 hover:shadow-md"
                            style="--reveal-delay: 80ms"
                        >
                            <div
                                class="flex h-12 w-12 items-center justify-center rounded-2xl bg-accent-100 text-accent-700"
                            >
                                <Sparkles class="h-6 w-6" aria-hidden="true" />
                            </div>
                            <h3 class="mt-5 font-heading text-lg font-bold text-navy-950">
                                Setrika Uap Profesional
                            </h3>
                            <p class="mt-2 text-sm leading-relaxed text-slate-600">
                                Uap bertekanan dengan suhu stabil melicinkan pakaian tanpa merusak serat kain.
                                Pakaian halus, bebas bau apek, dan tidak berisiko gosong atau mengkilap.
                            </p>
                        </article>

                        <article
                            class="landing-reveal flex flex-col rounded-[24px] border border-border/80 bg-white p-7 text-navy-950 shadow-xs transition-all duration-200 hover:-translate-y-1 hover:shadow-md sm:col-span-2 lg:col-span-1"
                            style="--reveal-delay: 160ms"
                        >
                            <div
                                class="flex h-12 w-12 items-center justify-center rounded-2xl bg-accent-100 text-accent-700"
                            >
                                <Truck class="h-6 w-6" aria-hidden="true" />
                            </div>
                            <h3 class="mt-5 font-heading text-lg font-bold text-navy-950">
                                Gratis Jemput-Antar 3 KM
                            </h3>
                            <p class="mt-2 text-sm leading-relaxed text-slate-600">
                                Cukup bagikan alamat atau titik penjemputan via WhatsApp. Tim kami segera menjemput
                                cucian kotor dan mengantarkannya kembali saat sudah bersih dan rapi.
                            </p>
                        </article>
                    </div>
                </div>
            </section>

            <!-- Tarif & Layanan -->
            <section id="layanan" class="py-16 sm:py-20">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div
                        class="landing-reveal flex flex-col justify-between gap-6 md:flex-row md:items-end"
                    >
                        <div class="max-w-2xl">
                            <p
                                class="text-[11px] font-bold uppercase tracking-[0.16em] text-accent-700"
                            >
                                Pilihan Layanan & Tarif
                            </p>
                            <h2
                                class="mt-3 text-balance font-heading text-3xl font-extrabold uppercase leading-tight tracking-tight text-navy-950 sm:text-4xl"
                            >
                                Harganya jelas,
                                <span class="text-accent-700">pelanggan puas.</span>
                            </h2>
                            <p class="mt-3 text-sm leading-relaxed text-slate-700 sm:text-base">
                                Minimal 2 kg untuk cucian kiloan. Layanan reguler selesai 2–3 hari kerja,
                                atau pilih paket kilat / ekspres untuk selesai di hari yang sama.
                            </p>
                        </div>

                        <a
                            :href="whatsappUrl"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="inline-flex min-h-[48px] shrink-0 items-center justify-center gap-2 rounded-pill bg-accent-500 px-5 text-sm font-bold text-navy-950 transition-colors hover:bg-accent-600 focus-visible:outline-none"
                        >
                            <MessageCircle class="h-4 w-4" aria-hidden="true" />
                            Pesan via WhatsApp
                        </a>
                    </div>

                    <div id="tarif" class="mt-10 grid grid-cols-1 gap-6 lg:grid-cols-12">
                        <!-- Featured Card: Cuci Setrika -->
                        <div class="landing-reveal min-w-0 lg:col-span-5">
                            <article
                                class="flex h-full flex-col rounded-[24px] border-2 border-accent-500/80 bg-white p-6 text-navy-950 shadow-md transition-all duration-200 hover:-translate-y-1 hover:shadow-lg sm:p-8"
                            >
                                <div class="flex items-start justify-between gap-3">
                                    <p
                                        class="text-[11px] font-bold uppercase tracking-[0.16em] text-accent-700"
                                    >
                                        {{ featuredService.label }}
                                    </p>
                                    <span
                                        class="inline-flex shrink-0 items-center gap-1 rounded-pill bg-navy-950 px-3 py-1 text-[10px] font-bold uppercase tracking-[0.08em] text-white"
                                    >
                                        <Flame
                                            class="h-3.5 w-3.5 fill-warning text-warning"
                                            aria-hidden="true"
                                        />
                                        Best seller
                                    </span>
                                </div>

                                <h3
                                    class="mt-3 font-heading text-2xl font-extrabold sm:text-3xl"
                                >
                                    {{ featuredService.name }}
                                </h3>

                                <div class="mt-2 flex items-baseline gap-1">
                                    <span class="font-heading text-3xl font-extrabold sm:text-4xl">
                                        {{ featuredService.price }}
                                    </span>
                                    <span class="text-sm font-semibold text-slate-500">
                                        / {{ featuredService.unit }}
                                    </span>
                                </div>

                                <p class="mt-1 text-xs font-semibold text-slate-500">
                                    {{ featuredService.minWeight }}
                                </p>

                                <p
                                    class="mt-4 text-sm leading-relaxed text-slate-700"
                                >
                                    {{ featuredService.desc }}
                                </p>

                                <ul
                                    class="mt-5 space-y-2.5 text-sm text-slate-700"
                                >
                                    <li
                                        v-for="feature in featuredService.features"
                                        :key="feature"
                                        class="flex items-start gap-2.5"
                                    >
                                        <Check
                                            class="mt-0.5 h-4 w-4 shrink-0 text-success"
                                            aria-hidden="true"
                                        />
                                        <span>{{ feature }}</span>
                                    </li>
                                </ul>

                                <div class="mt-auto pt-6 sm:pt-8">
                                    <a
                                        :href="getServiceWhatsappUrl(featuredService.name)"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="inline-flex min-h-[48px] w-full items-center justify-center gap-2 rounded-pill bg-accent-500 px-5 text-sm font-bold text-navy-950 transition-colors hover:bg-accent-600 focus-visible:outline-none"
                                    >
                                        <MessageCircle class="h-4 w-4" aria-hidden="true" />
                                        Pesan {{ featuredService.name }}
                                    </a>
                                </div>
                            </article>
                        </div>

                        <!-- Other Services -->
                        <div class="min-w-0 lg:col-span-7">
                            <div class="grid gap-4">
                                <article
                                    v-for="(service, index) in otherServices"
                                    :key="service.id"
                                    class="landing-reveal flex flex-col rounded-[20px] border border-border/80 bg-white p-5 text-navy-950 shadow-xs transition-all duration-200 hover:-translate-y-1 hover:shadow-md sm:p-6"
                                    :style="{ transitionDelay: `${index * 70}ms` }"
                                >
                                    <div class="flex items-start justify-between gap-4">
                                        <div class="min-w-0">
                                            <h3
                                                class="font-heading text-lg font-bold sm:text-xl"
                                            >
                                                {{ service.name }}
                                            </h3>
                                            <p
                                                class="mt-0.5 text-xs font-semibold text-slate-500"
                                            >
                                                {{ service.minWeight }}
                                            </p>
                                        </div>
                                        <div class="shrink-0 text-right">
                                            <div class="font-heading text-2xl font-extrabold">
                                                {{ service.price }}
                                            </div>
                                            <div
                                                class="text-xs font-semibold text-slate-500"
                                            >
                                                / {{ service.unit }}
                                            </div>
                                        </div>
                                    </div>

                                    <p
                                        class="mt-3 text-sm leading-relaxed text-slate-600"
                                    >
                                        {{ service.desc }}
                                    </p>

                                    <div class="mt-4 pt-2">
                                        <a
                                            :href="getServiceWhatsappUrl(service.name)"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="inline-flex min-h-[44px] w-full items-center justify-center gap-1.5 rounded-pill bg-accent-500 px-5 text-sm font-bold text-navy-950 transition-colors hover:bg-accent-600 focus-visible:outline-none"
                                        >
                                            <MessageCircle class="h-4 w-4" aria-hidden="true" />
                                            Pesan {{ service.name }}
                                        </a>
                                    </div>
                                </article>
                            </div>

                            <p class="mt-5 text-xs leading-relaxed text-slate-500">
                                * Layanan ekspres selesai hari yang sama dengan tambahan Rp 2.000 per kg.
                                Layanan cuci karpet dihitung berdasarkan luas (panjang × lebar).
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Standar Kualitas & Komitmen -->
            <section id="standar" class="py-16 sm:py-20">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="landing-reveal max-w-2xl">
                        <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-accent-700">
                            Standar Kualitas
                        </p>
                        <h2
                            class="mt-2 text-balance font-heading text-3xl font-extrabold uppercase leading-tight tracking-tight text-navy-950 sm:text-4xl"
                        >
                            3 Jaminan Mutu Andik Laundry
                        </h2>
                        <p class="mt-3 text-sm leading-relaxed text-slate-700 sm:text-base">
                            Kepercayaan pelanggan di Hendrosari dan sekitarnya dibangun dari konsistensi mutu.
                            Berikut prinsip kerja yang kami pegang teguh pada setiap pesanan.
                        </p>
                    </div>

                    <div class="mt-10 grid grid-cols-1 gap-6 md:grid-cols-3">
                        <div
                            v-for="(guarantee, idx) in qualityGuarantees"
                            :key="guarantee.title"
                            class="landing-reveal flex flex-col rounded-[24px] border border-border/80 bg-white p-7 text-navy-950 shadow-xs transition-all duration-200 hover:-translate-y-1 hover:shadow-md"
                            :style="{ transitionDelay: `${idx * 80}ms` }"
                        >
                            <div
                                class="flex h-12 w-12 items-center justify-center rounded-2xl bg-accent-100 text-accent-700"
                            >
                                <component :is="guarantee.icon" class="h-6 w-6" aria-hidden="true" />
                            </div>
                            <h3 class="mt-5 font-heading text-lg font-bold">
                                {{ guarantee.title }}
                            </h3>
                            <p class="mt-2 text-sm leading-relaxed text-slate-600">
                                {{ guarantee.desc }}
                            </p>
                        </div>
                    </div>

                    <!-- Database Statistics / Operational Banner -->
                    <div
                        v-if="hasStats"
                        ref="statsSection"
                        class="landing-reveal mt-12 rounded-[28px] border border-navy-800 bg-navy-900 p-8 text-white shadow-md transition-all duration-200 hover:-translate-y-1 hover:shadow-lg sm:p-10"
                        style="--reveal-delay: 150ms"
                    >
                        <div class="text-center sm:text-left sm:flex sm:items-center sm:justify-between sm:gap-8 pb-6 border-b border-white/10">
                            <div>
                                <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-accent-400">
                                    Catatan Kinerja Nyata
                                </p>
                                <h3 class="mt-1 font-heading text-2xl font-bold">
                                    Dipercaya Masyarakat Sejak 2009
                                </h3>
                            </div>
                            <p class="mt-2 sm:mt-0 text-xs text-slate-300 max-w-xs">
                                Data terakumulasi otomatis dari sistem manajemen operasional kami.
                            </p>
                        </div>

                        <div class="mt-8 grid grid-cols-1 gap-6 text-center sm:grid-cols-3 sm:gap-8">
                            <div>
                                <div class="font-heading text-3xl font-extrabold tabular-nums sm:text-4xl text-accent-400">
                                    {{ customersLabel }}
                                </div>
                                <div class="mt-1 text-xs font-semibold uppercase tracking-[0.12em] text-slate-300">
                                    Pelanggan Terdaftar
                                </div>
                            </div>
                            <div>
                                <div class="font-heading text-3xl font-extrabold tabular-nums sm:text-4xl text-accent-400">
                                    {{ ordersLabel }}
                                </div>
                                <div class="mt-1 text-xs font-semibold uppercase tracking-[0.12em] text-slate-300">
                                    Nota Diselesaikan
                                </div>
                            </div>
                            <div>
                                <div class="font-heading text-3xl font-extrabold tabular-nums sm:text-4xl text-accent-400">
                                    {{ laundryKgLabel }}
                                </div>
                                <div class="mt-1 text-xs font-semibold uppercase tracking-[0.12em] text-slate-300">
                                    Kg Cucian Diproses
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Alur Pengerjaan -->
            <section id="proses" class="py-16 sm:py-20">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="landing-reveal max-w-2xl">
                        <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-accent-700">
                            Alur Pengerjaan
                        </p>
                        <h2
                            class="mt-2 text-balance font-heading text-3xl font-extrabold uppercase leading-tight tracking-tight text-navy-950 sm:text-4xl"
                        >
                            5 Langkah Mudah Cucian Beres
                        </h2>
                        <p class="mt-3 text-sm leading-relaxed text-slate-700 sm:text-base">
                            Setiap tahap memiliki status yang dapat Anda pantau langsung melalui nota digital WhatsApp.
                        </p>
                    </div>

                    <div
                        class="mt-10 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5"
                    >
                        <div
                            v-for="(step, index) in processSteps"
                            :key="step.label"
                            class="landing-reveal flex flex-col rounded-[20px] border border-border/80 bg-white p-5 sm:p-6 transition-all duration-200 hover:-translate-y-1 hover:shadow-md"
                            :style="{ transitionDelay: `${index * 60}ms` }"
                        >
                            <div class="flex items-center justify-between">
                                <span
                                    class="flex h-8 w-8 items-center justify-center rounded-full bg-accent-500 font-heading text-xs font-extrabold text-navy-950"
                                >
                                    {{ step.step }}
                                </span>
                            </div>
                            <h3 class="mt-4 font-heading text-base font-bold text-navy-950">
                                {{ step.label }}
                            </h3>
                            <p class="mt-1.5 text-xs leading-relaxed text-slate-600">
                                {{ step.desc }}
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Lokasi Outlet -->
            <section id="lokasi" class="py-16 sm:py-20">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="landing-reveal max-w-2xl">
                        <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-accent-700">
                            Lokasi Outlet
                        </p>
                        <h2
                            class="mt-2 text-balance font-heading text-3xl font-extrabold uppercase leading-tight tracking-tight text-navy-950 sm:text-4xl"
                        >
                            Kunjungi Outlet Kami
                        </h2>
                        <p class="mt-3 text-sm leading-relaxed text-slate-700 sm:text-base">
                            Bisa mampir langsung untuk drop cucian, atau chat kami untuk penjemputan gratis hingga 3 km.
                        </p>
                    </div>

                    <div
                        class="landing-reveal mt-10 grid grid-cols-1 items-center gap-8 rounded-[28px] border border-border/80 bg-white p-6 text-navy-950 shadow-[0_16px_40px_rgba(8,16,80,0.06)] transition-all duration-200 hover:-translate-y-1 hover:shadow-lg sm:p-8 lg:grid-cols-12"
                    >
                        <div class="min-w-0 lg:col-span-5">
                            <p
                                class="text-[11px] font-bold uppercase tracking-[0.16em] text-accent-700"
                            >
                                Outlet Resmi
                            </p>
                            <h3 class="mt-1 font-heading text-2xl font-extrabold sm:text-3xl">
                                {{ storeName }}
                            </h3>

                            <div class="mt-5 space-y-4 text-sm">
                                <div class="flex items-start gap-3">
                                    <MapPin
                                        class="mt-0.5 h-5 w-5 shrink-0 text-accent-700"
                                        aria-hidden="true"
                                    />
                                    <div>
                                        <div class="font-bold">Alamat</div>
                                        <p class="mt-0.5 leading-relaxed text-slate-600">
                                             {{ storeAddress }}
                                        </p>
                                    </div>
                                </div>

                                <div class="flex items-start gap-3">
                                    <Clock
                                        class="mt-0.5 h-5 w-5 shrink-0 text-accent-700"
                                        aria-hidden="true"
                                    />
                                    <div>
                                        <div class="font-bold">Jam Operasional</div>
                                        <p class="mt-0.5 text-slate-600">
                                            Buka setiap hari, 07.00–20.00 WIB
                                        </p>
                                    </div>
                                </div>

                                <div class="flex items-start gap-3">
                                    <Truck
                                        class="mt-0.5 h-5 w-5 shrink-0 text-accent-700"
                                        aria-hidden="true"
                                    />
                                    <div>
                                        <div class="font-bold">Antar Jemput</div>
                                        <p class="mt-0.5 text-slate-600">
                                            Gratis untuk jarak maksimal 3 km di sekitar Hendrosari & Menganti.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-6 flex flex-col gap-2.5 sm:flex-row">
                                <a
                                    :href="whatsappUrl"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="inline-flex min-h-[44px] items-center justify-center gap-2 rounded-pill bg-accent-500 px-5 text-sm font-bold text-navy-950 transition-colors hover:bg-accent-600 focus-visible:outline-none"
                                >
                                    <MessageCircle class="h-4 w-4" aria-hidden="true" />
                                    Hubungi WhatsApp
                                </a>
                                <a
                                    href="https://www.google.com/maps/place/Andik+Laundry/@-7.2392419,112.581764,21z/data=!4m12!1m5!3m4!2zN8KwMTQnMjEuMyJTIDExMsKwMzQnNTQuMyJF!8m2!3d-7.2392469!4d112.5817531!3m5!1s0x2e7800f99a230c6b:0xb287a654290078e9!8m2!3d-7.239213!4d112.5817581!16s%2Fg%2F11gdlxnlmk?hl=id-ID"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="inline-flex min-h-[44px] items-center justify-center gap-2 rounded-pill border border-navy-900/20 px-5 text-sm font-bold text-navy-950 transition-colors hover:bg-accent-100 focus-visible:outline-none"
                                >
                                    <ExternalLink class="h-4 w-4" aria-hidden="true" />
                                    Buka di Google Maps
                                </a>
                            </div>
                        </div>

                        <div class="min-w-0 lg:col-span-7">
                            <div
                                class="h-[280px] overflow-hidden rounded-[20px] border border-border sm:h-[360px]"
                            >
                                <iframe
                                    title="Peta lokasi Andik Laundry di Hendrosari, Menganti, Gresik"
                                    class="h-full w-full border-0"
                                    loading="lazy"
                                    allowfullscreen
                                    referrerpolicy="no-referrer-when-downgrade"
                                    src="https://maps.google.com/maps?q=Andik+Laundry,+Hendrosari,+Menganti,+Gresik&t=&z=18&ie=UTF8&iwloc=&output=embed"
                                ></iframe>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- FAQ -->
            <section id="faq" class="py-16 sm:py-20">
                <div
                    class="mx-auto grid max-w-7xl grid-cols-1 gap-10 px-4 sm:px-6 lg:grid-cols-12 lg:gap-16 lg:px-8"
                >
                    <div class="landing-reveal min-w-0 lg:col-span-4">
                        <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-accent-700">
                            Tanya Jawab
                        </p>
                        <h2
                            class="mt-2 text-balance font-heading text-3xl font-extrabold uppercase leading-tight tracking-tight text-navy-950 sm:text-4xl"
                        >
                            Pertanyaan Umum
                        </h2>
                        <p class="mt-4 text-sm leading-relaxed text-slate-700">
                            Masih ada hal yang ingin ditanyakan seputar pengerjaan atau jadwal penjemputan?
                            Silakan chat admin kami langsung.
                        </p>
                        <a
                            :href="whatsappUrl"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="mt-6 inline-flex min-h-[44px] items-center gap-2 rounded-pill bg-accent-500 px-5 text-sm font-bold text-navy-950 transition-colors hover:bg-accent-600 focus-visible:outline-none"
                        >
                            <MessageCircle class="h-4 w-4" aria-hidden="true" />
                            Chat Admin Sekarang
                        </a>
                    </div>

                    <div class="min-w-0 lg:col-span-8">
                        <div class="border-y border-slate-200/80">
                            <div
                                v-for="(faq, index) in faqs"
                                :key="faq.q"
                                class="landing-reveal border-b border-slate-200/80 last:border-b-0"
                                :style="{ transitionDelay: `${index * 40}ms` }"
                            >
                                <h3>
                                    <button
                                        :id="`faq-question-${index}`"
                                        type="button"
                                        class="flex min-h-[56px] w-full items-center justify-between gap-4 py-5 text-left font-heading text-base font-bold text-navy-950 transition-colors hover:text-accent-700 focus-visible:outline-none sm:text-lg"
                                        :aria-expanded="activeFaqIndex === index"
                                        :aria-controls="`faq-answer-${index}`"
                                        @click="toggleFaq(index)"
                                    >
                                        <span>{{ faq.q }}</span>
                                        <ChevronDown
                                            class="h-5 w-5 shrink-0 text-accent-700 transition-transform duration-300"
                                            :class="{ 'rotate-180': activeFaqIndex === index }"
                                            aria-hidden="true"
                                        />
                                    </button>
                                </h3>
                                <div
                                    v-show="activeFaqIndex === index"
                                    :id="`faq-answer-${index}`"
                                    role="region"
                                    :aria-labelledby="`faq-question-${index}`"
                                    class="pb-5 pr-6 text-sm leading-relaxed text-slate-700 sm:pr-10"
                                >
                                    {{ faq.a }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Final CTA -->
            <section class="py-12 sm:py-16">
                <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
                    <div
                        class="landing-reveal rounded-[28px] bg-navy-950 px-6 py-12 text-center text-white shadow-xl transition-all duration-200 hover:-translate-y-1 hover:shadow-2xl sm:px-12 sm:py-16"
                    >
                        <h2
                            class="text-balance font-heading text-3xl font-extrabold uppercase leading-tight tracking-tight text-white sm:text-4xl"
                        >
                            Punya Tumpukan Baju Kotor Hari Ini?
                        </h2>
                        <p class="mx-auto mt-4 max-w-xl text-sm leading-relaxed text-slate-300 sm:text-base">
                            Biar kami yang bantu selesaikan. Pesan antar jemput praktis via WhatsApp tanpa repot,
                            atau antar langsung ke outlet kami kapan saja Anda sempat.
                        </p>
                        <div class="mt-7 flex flex-col justify-center gap-3 sm:flex-row">
                            <a
                                :href="whatsappUrl"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="inline-flex min-h-[48px] items-center justify-center gap-2 rounded-pill bg-accent-500 px-6 text-sm font-bold text-navy-950 transition-colors hover:bg-accent-600 focus-visible:outline-none"
                            >
                                <MessageCircle class="h-4 w-4" aria-hidden="true" />
                                Pesan via WhatsApp
                            </a>
                            <a
                                :href="phoneUrl"
                                class="inline-flex min-h-[48px] items-center justify-center gap-2 rounded-pill border border-white/25 px-6 text-sm font-bold text-white transition-colors hover:bg-white/10 focus-visible:outline-none"
                            >
                                <Phone class="h-4 w-4 text-accent-400" aria-hidden="true" />
                                {{ storePhoneLabel }}
                            </a>
                        </div>
                        <p class="mt-5 text-xs text-slate-400">Buka setiap hari, 07.00–20.00 WIB</p>
                    </div>
                </div>
            </section>
        </main>

        <!-- Footer -->
        <footer class="border-t border-white/10 bg-navy-950 pb-10 pt-12 text-slate-300">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 gap-8 border-b border-white/10 pb-10 md:grid-cols-12">
                    <div class="md:col-span-5">
                        <Link
                            href="/"
                            class="inline-flex items-center gap-2 rounded-lg focus-visible:outline-none"
                            aria-label="Andik Laundry, kembali ke beranda"
                        >
                            <span class="font-heading text-2xl font-extrabold tracking-tight text-white">
                                andik<span class="text-accent-400">.</span>
                            </span>
                            <span
                                class="text-[10px] font-bold uppercase tracking-[0.22em] text-accent-400"
                            >
                                Laundry
                            </span>
                        </Link>
                        <p class="mt-4 max-w-sm text-sm leading-relaxed text-slate-300">
                            Binatu harian di Hendrosari, Menganti. Bersih, wangi, dan rapi sejak 2009.
                        </p>
                        <p class="mt-4 inline-flex items-center gap-2 text-xs font-semibold text-accent-400">
                            <Clock class="h-4 w-4" aria-hidden="true" />
                            Buka setiap hari, 07.00–20.00 WIB
                        </p>
                    </div>

                    <div class="md:col-span-3">
                        <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-slate-400">
                            Layanan
                        </p>
                        <ul class="mt-2 space-y-0 text-sm">
                            <li>
                                <a href="#tarif" class="flex min-h-[44px] items-center transition-colors hover:text-white">
                                    Cuci setrika
                                </a>
                            </li>
                            <li>
                                <a href="#tarif" class="flex min-h-[44px] items-center transition-colors hover:text-white">
                                    Cuci kering lipat
                                </a>
                            </li>
                            <li>
                                <a href="#tarif" class="flex min-h-[44px] items-center transition-colors hover:text-white">
                                    Cuci basah
                                </a>
                            </li>
                            <li>
                                <a href="#tarif" class="flex min-h-[44px] items-center transition-colors hover:text-white">
                                    Cuci karpet
                                </a>
                            </li>
                        </ul>
                    </div>

                    <div class="md:col-span-4">
                        <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-slate-400">
                            Akses Cepat
                        </p>
                        <ul class="mt-2 space-y-0 text-sm">
                            <li>
                                <a href="#lacak" class="flex min-h-[44px] items-center transition-colors hover:text-white">
                                    Cek status nota
                                </a>
                            </li>
                            <li>
                                <a href="#lokasi" class="flex min-h-[44px] items-center transition-colors hover:text-white">
                                    Lokasi dan peta
                                </a>
                            </li>
                            <li>
                                <a
                                    :href="whatsappUrl"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="flex min-h-[44px] items-center transition-colors hover:text-white"
                                >
                                    Chat WhatsApp
                                </a>
                            </li>
                            <li v-if="canLogin">
                                <Link
                                    href="/login"
                                    class="flex min-h-[44px] items-center font-semibold text-accent-400 transition-colors hover:text-white"
                                >
                                    Masuk dashboard staf
                                </Link>
                            </li>
                        </ul>
                    </div>
                </div>

                <div
                    class="flex flex-col gap-3 pt-6 text-xs text-slate-400 sm:flex-row sm:items-center sm:justify-between"
                >
                    <p>
                        © {{ new Date().getFullYear() }} {{ storeName }}. Hendrosari, Menganti, Gresik.
                    </p>
                    <p>Bersih · Wangi · Rapi</p>
                </div>
            </div>
        </footer>
    </div>
</template>

<style scoped>
.roll {
    position: relative;
    display: inline-flex;
    overflow: hidden;
    vertical-align: middle;
    line-height: 1.25;
}

.roll > span {
    display: block;
    position: relative;
    transition: transform 0.5s cubic-bezier(0.4, 0, 0.1, 1);
}

.roll > span:after {
    content: attr(data-t);
    position: absolute;
    left: 0;
    top: 100%;
    display: block;
    white-space: nowrap;
}

.nav-link:hover .roll > span,
.nav-btn:hover .roll > span {
    transform: translateY(-100%);
}

@media (prefers-reduced-motion: reduce) {
    .roll > span {
        transition: none !important;
    }
    .nav-link:hover .roll > span,
    .nav-btn:hover .roll > span {
        transform: none !important;
    }
    .splash-logo {
        transition: none !important;
        transform: none !important;
    }
}

/* ------------------------------------------------------------------
   3D Zoom Splash (Vean Style)
   ------------------------------------------------------------------ */
.splash-perspective {
    perspective: 1000px;
    transform-style: preserve-3d;
}

.splash-logo {
    transform-origin: center center;
    transform-style: preserve-3d;
    backface-visibility: hidden;
    will-change: transform, opacity;
    transition: transform 0.6s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.4s ease;
}

.splash-logo-stage-0 {
    opacity: 0;
    transform: perspective(1000px) scale(0.85) translateZ(-80px);
}

.splash-logo-stage-1 {
    opacity: 1;
    transform: perspective(1000px) scale(1) translateZ(0);
}

.splash-logo-stage-2 {
    opacity: 0;
    transform: perspective(1000px) scale(5.2) translateZ(420px);
    transition:
        transform 0.75s cubic-bezier(0.4, 0, 0.2, 1),
        opacity 0.5s cubic-bezier(0.4, 0, 0.2, 1) 0.15s;
}
</style>
