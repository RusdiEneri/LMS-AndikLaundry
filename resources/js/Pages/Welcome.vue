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
    Shirt,
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
    { href: '#beranda', label: 'Beranda' },
    { href: '#layanan', label: 'Layanan' },
    { href: '#tarif', label: 'Tarif' },
    { href: '#lacak', label: 'Cek nota' },
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
        label: 'Paling lengkap',
        featured: true,
        desc: 'Paket all-in-one: dicuci higienis, dikeringkan mesin, disetrika uap, lalu dikemas wangi dan terlindung debu.',
        features: [
            'Dicuci, dikeringkan, lalu disetrika uap',
            'Setrika uap, tidak mudah gosong',
            'Pewangi premium yang tahan lama',
            'Kemasan plastik rapi dan kedap debu',
        ],
    },
    {
        id: 'cuci-kering',
        name: 'Cuci Kering',
        unit: 'kg',
        price: 'Rp 6.000',
        minWeight: 'Min. 2 kg',
        desc: 'Dicuci, dikeringkan mesin dengan suhu terkontrol, lalu disortir dan dilipat rapi.',
        features: [],
    },
    {
        id: 'cuci-basah',
        name: 'Cuci Basah',
        unit: 'kg',
        price: 'Rp 5.000',
        minWeight: 'Min. 2 kg',
        desc: 'Dicuci dan dibilas bersih dengan deterjen ramah serat, siap dijemur sendiri di rumah.',
        features: [],
    },
    {
        id: 'cuci-karpet',
        name: 'Cuci Karpet',
        unit: 'm²',
        price: 'Rp 15.000',
        minWeight: 'Hitung P × L',
        desc: 'Pembersihan karpet rumah, kantor, atau masjid dengan hydro-cleaning untuk mengangkat debu dan tungau.',
        features: [],
    },
];

const featuredService = computed(() => services.find((service) => service.featured)!);
const otherServices = computed(() => services.filter((service) => !service.featured));

/* ------------------------------------------------------------------
   Proses
   ------------------------------------------------------------------ */
const processSteps = [
    { label: 'Diterima', desc: 'Cucian ditimbang, nota dibuat, dan nomornya dikirim ke WhatsApp.' },
    { label: 'Dicuci', desc: 'Deterjen ramah serat dan pembilasan menyeluruh membuat kain bersih.' },
    { label: 'Dikeringkan', desc: 'Mesin pengering dengan suhu terkontrol agar kain tidak kusut.' },
    { label: 'Disetrika', desc: 'Setrika uap membuat serat kain licin dan rapi.' },
    { label: 'Siap diambil', desc: 'Dikemas, lalu diambil di outlet atau diantar ke rumah.' },
];

/* ------------------------------------------------------------------
   FAQ
   ------------------------------------------------------------------ */
const faqs = [
    {
        q: 'Berapa minimal berat cucian kiloan?',
        a: 'Batas minimal layanan kiloan adalah 2 kg. Kalau hasil timbangan kurang dari 2 kg, biaya dihitung setara 2 kg.',
    },
    {
        q: 'Apakah pakaian saya aman dan tidak tertukar?',
        a: 'Setiap nota diberi label dan dicatat secara digital. Pakaian disortir, diproses, lalu dikemas kembali sesuai nota Anda.',
    },
    {
        q: 'Berapa lama waktu pengerjaan?',
        a: 'Layanan reguler selesai dalam 2–3 hari kerja. Ada juga layanan ekspres yang selesai di hari yang sama, dengan tambahan Rp 2.000 per kg.',
    },
    {
        q: 'Apakah ada antar jemput?',
        a: 'Ada. Antar jemput gratis untuk jarak maksimal 3 km di area Hendrosari dan Menganti. Hubungi admin lewat WhatsApp untuk mengatur penjemputan.',
    },
    {
        q: 'Bagaimana cara melacak cucian?',
        a: 'Setelah pesanan masuk, nota digital dikirim ke WhatsApp Anda. Anda juga bisa memasukkan nomor nota atau nomor HP di kolom cek nota pada halaman ini tanpa perlu login.',
    },
    {
        q: 'Pembayaran apa saja yang diterima?',
        a: 'Kami menerima tunai di kasir dan QRIS dari semua mobile banking serta dompet digital yang mendukung QRIS. Pembayaran bisa di muka atau saat cucian diambil.',
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
    handleScroll();
    window.addEventListener('scroll', handleScroll, { passive: true });
    window.addEventListener('keydown', handleKeydown);

    nextTick(() => {
        setupRevealObserver();
        setupStatsObserver();
    });
});

onBeforeUnmount(() => {
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
    <Head title="Andik Laundry | Cucian kotor, pulang kinclong">
        <meta
            name="description"
            content="Layanan cuci kiloan dan cuci karpet di Hendrosari, Menganti, Gresik. Hasil bersih, wangi, dan rapi, nota digital WhatsApp, pelacakan status, serta antar jemput gratis hingga 3 km."
        />
    </Head>

    <div class="min-h-screen overflow-x-clip bg-bg font-sans text-navy-950 antialiased">
        <a
            href="#main-content"
            class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-[60] focus:rounded-pill focus:bg-accent-500 focus:px-4 focus:py-2 focus:font-bold focus:text-navy-950 focus:shadow-lg focus:outline-none"
        >
            Lewati ke konten utama
        </a>

        <!-- Navbar -->
        <header
            class="fixed inset-x-0 top-0 z-40 border-b transition-colors duration-300"
            :class="
                isScrolled || menuOpen
                    ? 'border-border bg-white shadow-[0_4px_20px_rgba(8,16,80,0.06)]'
                    : 'border-transparent bg-transparent'
            "
        >
            <div
                class="mx-auto flex h-16 max-w-7xl items-center justify-between gap-4 px-4 sm:h-[76px] sm:px-6 lg:px-8"
            >
                <Link
                    href="/"
                    class="flex shrink-0 items-center gap-2 rounded-lg focus-visible:outline-none"
                    aria-label="Andik Laundry, kembali ke beranda"
                >
                    <span class="font-heading text-[26px] font-extrabold tracking-tight text-navy-950">
                        andik<span class="text-accent-600">.</span>
                    </span>
                    <span
                        class="hidden text-[10px] font-bold uppercase tracking-[0.22em] text-accent-600 sm:inline"
                    >
                        Laundry
                    </span>
                </Link>

                <nav class="hidden items-center gap-1 lg:flex" aria-label="Navigasi utama">
                    <a
                        v-for="link in navLinks"
                        :key="link.href"
                        :href="link.href"
                        class="rounded-lg px-3 py-2 text-sm font-medium text-slate-700 transition-colors hover:text-accent-600 focus-visible:outline-none"
                    >
                        {{ link.label }}
                    </a>
                </nav>

                <div class="ml-auto flex items-center gap-2">
                    <Link
                        v-if="canLogin"
                        href="/login"
                        class="inline-flex min-h-[44px] items-center justify-center rounded-pill bg-accent-500 px-4 text-sm font-bold text-navy-950 transition-colors hover:bg-accent-600 focus-visible:outline-none sm:px-5"
                    >
                        Masuk
                    </Link>

                    <button
                        ref="menuButtonRef"
                        type="button"
                        class="inline-flex h-11 w-11 items-center justify-center rounded-full text-navy-950 transition-colors hover:bg-accent-100 focus-visible:outline-none lg:hidden"
                        :aria-expanded="menuOpen"
                        aria-controls="mobile-menu"
                        :aria-label="menuOpen ? 'Tutup menu' : 'Buka menu'"
                        @click="menuOpen = !menuOpen"
                    >
                        <X v-if="menuOpen" class="h-6 w-6" aria-hidden="true" />
                        <Menu v-else class="h-6 w-6" aria-hidden="true" />
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
                    class="fixed inset-0 z-50 bg-navy-950/50 lg:hidden"
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
                    class="fixed inset-x-3 top-[72px] z-50 max-h-[calc(100dvh-88px)] overflow-y-auto rounded-[24px] border border-border bg-white p-3 text-navy-950 shadow-lg focus:outline-none lg:hidden"
                >
                    <nav class="flex flex-col" aria-label="Navigasi seluler">
                        <a
                            v-for="link in navLinks"
                            :key="link.href"
                            :href="link.href"
                            class="flex min-h-[48px] items-center rounded-xl px-4 text-base font-semibold transition-colors hover:bg-accent-100 hover:text-accent-600"
                            @click="menuOpen = false"
                        >
                            {{ link.label }}
                        </a>
                    </nav>

                    <div v-if="canLogin" class="mt-2 border-t border-border pt-3">
                        <Link
                            href="/login"
                            class="flex min-h-[48px] items-center justify-center rounded-pill bg-accent-500 px-4 text-sm font-bold text-navy-950 transition-colors hover:bg-accent-600"
                            @click="menuOpen = false"
                        >
                            Masuk
                        </Link>
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
                    class="relative mx-auto grid max-w-7xl grid-cols-1 items-center gap-12 px-4 sm:px-6 lg:grid-cols-12 lg:gap-10 lg:px-8"
                >
                    <div class="min-w-0 lg:col-span-6">
                        <p
                            class="landing-enter text-[11px] font-bold uppercase tracking-[0.16em] text-accent-600"
                            style="--enter-delay: 0ms"
                        >
                            Sejak 2009 · Hendrosari, Gresik
                        </p>

                        <h1
                            class="landing-enter mt-4 font-heading text-[1.95rem] font-extrabold uppercase leading-[1.02] tracking-tight text-navy-950 sm:text-5xl sm:leading-[0.98] lg:text-[3.6rem]"
                            style="--enter-delay: 80ms"
                        >
                            Cucian Kotor,<br />
                            <span class="text-accent-600">Pulang Kinclong.</span>
                        </h1>

                        <p
                            class="landing-enter mt-6 max-w-xl text-base leading-relaxed text-slate-700 sm:text-lg"
                            style="--enter-delay: 160ms"
                        >
                            Tinggal chat WhatsApp atau mampir ke outlet, tumpukan baju kotor
                            langsung beres. Di Andik Laundry, hasilnya pasti bersih, wangi, dan
                            rapi. Timbangan bisa dicek sendiri, nota langsung dikirim ke WhatsApp,
                            plus gratis antar-jemput maksimal 3 km di sekitar Hendrosari.
                        </p>

                        <div
                            class="landing-enter mt-8 flex flex-col gap-3 sm:flex-row"
                            style="--enter-delay: 240ms"
                        >
                            <a
                                :href="whatsappUrl"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="inline-flex min-h-[48px] items-center justify-center gap-2 rounded-pill bg-accent-500 px-6 text-sm font-bold text-navy-950 transition-colors hover:bg-accent-600 focus-visible:outline-none"
                            >
                                <MessageCircle class="h-4 w-4" aria-hidden="true" />
                                Pesan antar jemput
                            </a>
                            <a
                                href="#tarif"
                                class="inline-flex min-h-[48px] items-center justify-center rounded-pill border border-navy-900/20 px-6 text-sm font-bold text-navy-950 transition-colors hover:bg-white focus-visible:outline-none"
                            >
                                Lihat tarif
                            </a>
                        </div>

                        <div
                            class="landing-enter mt-6 flex flex-wrap items-center gap-x-5 gap-y-2 text-xs font-medium text-slate-600"
                            style="--enter-delay: 320ms"
                        >
                            <span class="inline-flex items-center gap-2">
                                <Clock class="h-4 w-4 text-accent-600" aria-hidden="true" />
                                Buka setiap hari, 07.00–20.00 WIB
                            </span>
                            <span class="inline-flex items-center gap-2">
                                <Truck class="h-4 w-4 text-accent-600" aria-hidden="true" />
                                Antar jemput gratis maksimal 3 km
                            </span>
                        </div>
                    </div>

                    <div class="min-w-0 lg:col-span-6">
                        <div class="landing-enter mx-auto max-w-[440px]" style="--enter-delay: 140ms">
                            <WashingMachineIllustration />
                            <div
                                class="mt-3 flex items-center justify-center text-center text-[11px] font-bold uppercase tracking-[0.16em] text-accent-600"
                            >
                                <span>Bersih • Wangi • Rapi</span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Cek nota -->
            <section id="lacak" class="border-y border-white/10 bg-navy-900 py-10 text-white sm:py-12">
                <div
                    class="mx-auto grid max-w-7xl grid-cols-1 items-center gap-6 px-4 sm:px-6 lg:grid-cols-12 lg:gap-10 lg:px-8"
                >
                    <div class="lg:col-span-5">
                        <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-accent-500">
                            Cek progres
                        </p>
                        <h2 class="mt-2 font-heading text-2xl font-bold text-white sm:text-3xl">
                            Sudah punya nota?
                        </h2>
                        <p class="mt-2 max-w-md text-sm leading-relaxed text-slate-300">
                            Masukkan nomor nota atau nomor HP yang terdaftar. Status cucian terbuka
                            tanpa perlu login.
                        </p>
                    </div>

                    <div class="min-w-0 lg:col-span-7">
                        <form
                            class="flex flex-col gap-2 rounded-[24px] bg-white p-2 sm:flex-row sm:items-center sm:rounded-pill"
                            @submit.prevent="submitOrderCheck"
                        >
                            <div class="flex min-w-0 flex-1 items-center gap-3 px-3 py-1.5">
                                <ReceiptText class="h-5 w-5 shrink-0 text-accent-600" aria-hidden="true" />
                                <label for="order-query" class="sr-only">
                                    Nomor nota atau nomor HP
                                </label>
                                <input
                                    id="order-query"
                                    v-model="orderQuery"
                                    type="text"
                                    inputmode="text"
                                    autocomplete="off"
                                    placeholder="Nomor nota (AL-...) atau nomor HP"
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
                            class="mt-2 rounded-xl bg-white/10 px-3 py-2 text-xs font-medium text-white"
                            role="alert"
                        >
                            {{ orderQueryError }}
                        </p>
                        <p v-else class="mt-2 px-1 text-xs text-slate-300">
                            Contoh: AL-20261115-001 atau 08123456789
                        </p>
                    </div>
                </div>
            </section>

            <!-- Keunggulan -->
            <section id="kenapa" class="bg-bg py-20 sm:py-24">
                <div
                    class="mx-auto grid max-w-7xl grid-cols-1 gap-12 px-4 sm:px-6 lg:grid-cols-12 lg:gap-16 lg:px-8"
                >
                    <div class="landing-reveal min-w-0 lg:col-span-5">
                        <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-accent-600">
                            01 / Kenapa Andik Laundry
                        </p>
                        <h2
                            class="mt-4 text-balance font-heading text-3xl font-extrabold uppercase leading-tight tracking-tight text-navy-950 sm:text-4xl"
                        >
                            Bersih, wangi, rapi. Setiap kali.
                        </h2>
                        <p class="mt-5 text-sm leading-relaxed text-slate-700 sm:text-base">
                            Sejak 2009, setiap cucian melewati urutan yang sama: dicuci dengan
                            deterjen ramah serat, dikeringkan dengan suhu terkontrol, lalu
                            disetrika uap. Statusnya bisa dilacak lewat nota digital.
                        </p>
                        <a
                            href="#tarif"
                            class="mt-6 inline-block border-b-2 border-accent-600 pb-0.5 text-sm font-bold text-accent-600 transition-colors hover:border-accent-500 hover:text-accent-600 focus-visible:outline-none"
                        >
                            Lihat layanan dan tarif
                        </a>
                    </div>

                    <div class="min-w-0 lg:col-span-7">
                        <div class="border-y border-navy-900/10">
                            <article
                                class="landing-reveal grid gap-3 py-6 sm:grid-cols-[52px_minmax(0,1fr)] sm:gap-6"
                            >
                                <span class="font-mono text-xs font-medium text-accent-600 sm:pt-1">
                                    01
                                </span>
                                <div>
                                    <h3 class="font-heading text-lg font-bold text-navy-950">
                                        Bersih maksimal
                                    </h3>
                                    <p class="mt-1.5 text-sm leading-relaxed text-slate-700">
                                        Deterjen ramah serat dan pembilasan menyeluruh mengangkat
                                        kotoran tanpa merusak warna kain.
                                    </p>
                                </div>
                            </article>

                            <article
                                class="landing-reveal grid gap-3 border-t border-navy-900/10 py-6 sm:grid-cols-[52px_minmax(0,1fr)] sm:gap-6"
                                style="--reveal-delay: 70ms"
                            >
                                <span class="font-mono text-xs font-medium text-accent-600 sm:pt-1">
                                    02
                                </span>
                                <div>
                                    <h3 class="font-heading text-lg font-bold text-navy-950">
                                        Disetrika uap
                                    </h3>
                                    <p class="mt-1.5 text-sm leading-relaxed text-slate-700">
                                        Setrika uap dengan suhu terkontrol membuat serat kain licin,
                                        rapi, dan tidak mudah gosong.
                                    </p>
                                </div>
                            </article>

                            <article
                                class="landing-reveal grid gap-3 border-t border-navy-900/10 py-6 sm:grid-cols-[52px_minmax(0,1fr)] sm:gap-6"
                                style="--reveal-delay: 140ms"
                            >
                                <span class="font-mono text-xs font-medium text-accent-600 sm:pt-1">
                                    03
                                </span>
                                <div>
                                    <h3 class="font-heading text-lg font-bold text-navy-950">
                                        Antar jemput gratis
                                    </h3>
                                    <p class="mt-1.5 text-sm leading-relaxed text-slate-700">
                                        Untuk jarak maksimal 3 km di area Hendrosari dan Menganti.
                                        Cucian diambil dan diantar kembali ke rumah.
                                    </p>
                                </div>
                            </article>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Tarif -->
            <section id="layanan" class="bg-navy-950 py-20 text-white sm:py-24">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div
                        class="landing-reveal flex flex-col justify-between gap-6 md:flex-row md:items-end"
                    >
                        <div class="max-w-2xl">
                            <p
                                class="text-[11px] font-bold uppercase tracking-[0.16em] text-accent-500"
                            >
                                02 / Layanan dan tarif
                            </p>
                            <h2
                                class="mt-4 text-balance font-heading text-3xl font-extrabold uppercase leading-tight tracking-tight text-white sm:text-4xl"
                            >
                                Harganya jelas,
                                <span class="text-accent-500">pelanggan puas.</span>
                            </h2>
                            <p class="mt-4 text-sm leading-relaxed text-slate-300 sm:text-base">
                                Minimal 2 kg untuk layanan kiloan. Reguler selesai 2–3 hari, ekspres
                                selesai di hari yang sama.
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
                        <div class="landing-reveal min-w-0 lg:col-span-5">
                            <article
                                class="flex h-full flex-col rounded-[24px] bg-white p-6 text-navy-950 shadow-[0_12px_32px_rgba(8,16,80,0.12)] ring-1 ring-accent-500/50 sm:p-8"
                            >
                                <div class="flex items-start justify-between gap-3">
                                    <p
                                        class="text-[11px] font-bold uppercase tracking-[0.16em] text-accent-600"
                                    >
                                        {{ featuredService.label }}
                                    </p>
                                    <span
                                        class="inline-flex shrink-0 items-center gap-1 rounded-pill bg-navy-950 px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.08em] text-white"
                                    >
                                        <Flame
                                            class="h-3.5 w-3.5 fill-warning text-warning"
                                            aria-hidden="true"
                                        />
                                        Best seller
                                    </span>
                                </div>
                                <h3
                                    class="mt-2 font-heading text-xl font-extrabold sm:mt-3 sm:text-2xl"
                                >
                                    {{ featuredService.name }}
                                </h3>
                                <div class="mt-1 flex items-baseline gap-1 sm:mt-2">
                                    <span class="font-heading text-3xl font-extrabold">
                                        {{ featuredService.price }}
                                    </span>
                                    <span class="text-sm font-semibold text-slate-500">
                                        / {{ featuredService.unit }}
                                    </span>
                                </div>
                                <p class="mt-0.5 text-xs font-semibold text-slate-500 sm:mt-1">
                                    {{ featuredService.minWeight }}
                                </p>
                                <p
                                    class="mt-4 text-[13px] leading-relaxed text-slate-700 sm:text-sm"
                                >
                                    {{ featuredService.desc }}
                                </p>
                                <ul
                                    class="mt-4 space-y-2 text-[13px] text-slate-700 sm:mt-5 sm:text-sm"
                                >
                                    <li
                                        v-for="feature in featuredService.features"
                                        :key="feature"
                                        class="flex items-start gap-2"
                                    >
                                        <Check
                                            class="mt-0.5 h-4 w-4 shrink-0 text-success"
                                            aria-hidden="true"
                                        />
                                        <span>{{ feature }}</span>
                                    </li>
                                </ul>
                                <div class="mt-auto pt-5 sm:pt-6">
                                    <a
                                        :href="getServiceWhatsappUrl(featuredService.name)"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="inline-flex min-h-[44px] w-full items-center justify-center gap-2 rounded-pill bg-accent-500 px-5 text-sm font-bold text-navy-950 transition-colors hover:bg-accent-600 focus-visible:outline-none sm:min-h-[48px]"
                                    >
                                        <MessageCircle class="h-4 w-4" aria-hidden="true" />
                                        Pesan {{ featuredService.name }}
                                    </a>
                                </div>
                            </article>
                        </div>

                        <div class="min-w-0 lg:col-span-7">
                            <div class="grid gap-3 sm:gap-4">
                                <article
                                    v-for="(service, index) in otherServices"
                                    :key="service.id"
                                    class="landing-reveal flex flex-col rounded-[20px] bg-white p-5 text-navy-950 shadow-[0_10px_28px_rgba(8,16,80,0.12)] sm:p-6"
                                    :style="{ transitionDelay: `${index * 70}ms` }"
                                >
                                    <div class="flex items-start justify-between gap-4">
                                        <div class="min-w-0">
                                            <h3
                                                class="font-heading text-base font-bold sm:text-lg"
                                            >
                                                {{ service.name }}
                                            </h3>
                                            <p
                                                class="mt-0.5 text-[11px] font-semibold text-slate-500"
                                            >
                                                {{ service.minWeight }}
                                            </p>
                                        </div>
                                        <div class="shrink-0 text-right">
                                            <div class="font-heading text-xl font-extrabold">
                                                {{ service.price }}
                                            </div>
                                            <div
                                                class="text-[11px] font-semibold text-slate-500"
                                            >
                                                / {{ service.unit }}
                                            </div>
                                        </div>
                                    </div>

                                    <p
                                        class="mt-3 text-[13px] leading-relaxed text-slate-600 sm:text-sm"
                                    >
                                        {{ service.desc }}
                                    </p>

                                    <a
                                        :href="getServiceWhatsappUrl(service.name)"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="mt-4 inline-flex min-h-[44px] w-full items-center justify-center gap-1.5 rounded-pill bg-accent-500 px-5 text-sm font-bold text-navy-950 transition-colors hover:bg-accent-600 focus-visible:outline-none"
                                    >
                                        <MessageCircle class="h-4 w-4" aria-hidden="true" />
                                        Pesan
                                    </a>
                                </article>
                            </div>

                            <p class="mt-4 text-xs leading-relaxed text-slate-400">
                                Ekspres selesai hari yang sama dengan tambahan Rp 2.000 per kg.
                                Cuci karpet dihitung per meter persegi.
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Komitmen -->
            <section class="bg-bg py-20 sm:py-24">
                <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
                    <div
                        class="landing-reveal relative overflow-hidden rounded-[32px] bg-navy-900 px-6 py-12 text-white sm:rounded-[40px] sm:px-12 sm:py-14"
                    >
                        <div
                            class="pointer-events-none absolute -right-24 -top-24 h-[420px] w-[420px] rounded-full border border-white/10"
                            aria-hidden="true"
                        ></div>
                        <div
                            class="pointer-events-none absolute -right-10 -top-10 h-[260px] w-[260px] rounded-full border border-white/10"
                            aria-hidden="true"
                        ></div>

                        <div class="relative max-w-2xl">
                            <p
                                class="text-[11px] font-bold uppercase tracking-[0.16em] text-accent-500"
                            >
                                03 / Sejak 2009
                            </p>
                            <h2
                                class="mt-4 text-balance font-heading text-3xl font-extrabold uppercase leading-tight tracking-tight text-white sm:text-4xl"
                            >
                                Sejak 2009, satu kebiasaan: menjaga kualitas cucian.
                            </h2>
                            <p class="mt-4 text-sm leading-relaxed text-slate-300 sm:text-base">
                                Kepercayaan tidak dibangun dari angka. Kami menjaganya dengan
                                timbangan yang bisa dicek, proses yang rapi, dan nota yang bisa
                                dilacak.
                            </p>

                            <div
                                v-if="hasStats"
                                ref="statsSection"
                                class="mt-8 grid grid-cols-3 gap-4 border-t border-white/15 pt-6 sm:gap-8"
                            >
                                <div>
                                    <div
                                        class="font-heading text-2xl font-extrabold tabular-nums sm:text-3xl"
                                    >
                                        {{ customersLabel }}
                                    </div>
                                    <div
                                        class="mt-1 text-[11px] font-semibold uppercase tracking-[0.12em] text-slate-400"
                                    >
                                        Pelanggan
                                    </div>
                                </div>
                                <div>
                                    <div
                                        class="font-heading text-2xl font-extrabold tabular-nums sm:text-3xl"
                                    >
                                        {{ ordersLabel }}
                                    </div>
                                    <div
                                        class="mt-1 text-[11px] font-semibold uppercase tracking-[0.12em] text-slate-400"
                                    >
                                        Nota selesai
                                    </div>
                                </div>
                                <div>
                                    <div
                                        class="font-heading text-2xl font-extrabold tabular-nums sm:text-3xl"
                                    >
                                        {{ laundryKgLabel }}
                                    </div>
                                    <div
                                        class="mt-1 text-[11px] font-semibold uppercase tracking-[0.12em] text-slate-400"
                                    >
                                        Kg cucian
                                    </div>
                                </div>
                            </div>

                            <div
                                v-else
                                class="mt-8 grid gap-3 border-t border-white/15 pt-6 text-sm text-slate-300 sm:grid-cols-2"
                            >
                                <p class="flex items-center gap-2">
                                    <Clock class="h-4 w-4 text-accent-500" aria-hidden="true" />
                                    Buka setiap hari, 07.00–20.00 WIB
                                </p>
                                <p class="flex items-center gap-2">
                                    <Truck class="h-4 w-4 text-accent-500" aria-hidden="true" />
                                    Antar jemput gratis maksimal 3 km
                                </p>
                            </div>
                        </div>

                        <div
                            class="landing-reveal relative z-10 mt-10 sm:mt-12"
                            style="--reveal-delay: 120ms"
                        >
                        <div
                            class="flex flex-col overflow-hidden rounded-[24px] border border-border bg-white text-navy-950 shadow-lg md:flex-row"
                        >
                            <div
                                class="flex flex-col justify-center border-b border-border bg-accent-100 p-6 sm:p-8 md:w-[38%] md:border-b-0 md:border-r"
                            >
                                <div
                                    class="flex h-12 w-12 items-center justify-center rounded-full border border-border bg-white text-accent-600"
                                >
                                    <Shirt class="h-6 w-6" aria-hidden="true" />
                                </div>
                                <h3 class="mt-4 font-heading text-lg font-bold">
                                    Paket all-in-one
                                </h3>
                                <p class="mt-1 text-xs leading-relaxed text-slate-600">
                                    Cuci, kering mesin, setrika uap, lalu dikemas rapi.
                                </p>
                                <p
                                    class="mt-3 inline-flex items-center gap-1 text-[11px] font-bold uppercase tracking-[0.12em] text-accent-600"
                                >
                                    <Flame class="h-3.5 w-3.5" aria-hidden="true" />
                                    Paling sering dipesan
                                </p>
                            </div>

                            <div class="flex-1 p-6 sm:p-8">
                                <div class="flex flex-wrap items-end justify-between gap-3">
                                    <div>
                                        <p
                                            class="text-[11px] font-bold uppercase tracking-[0.16em] text-accent-600"
                                        >
                                            Cuci setrika
                                        </p>
                                        <div class="mt-1 flex items-baseline gap-1">
                                            <span
                                                class="font-heading text-3xl font-extrabold text-navy-950"
                                            >
                                                Rp 8.000
                                            </span>
                                            <span class="text-sm font-semibold text-slate-500">
                                                / kg
                                            </span>
                                        </div>
                                    </div>
                                    <span class="text-xs font-semibold text-slate-500">
                                        Min. 2 kg
                                    </span>
                                </div>

                                <ul class="mt-5 grid gap-2 text-sm text-slate-700 sm:grid-cols-2">
                                    <li class="flex items-start gap-2">
                                        <Check
                                            class="mt-0.5 h-4 w-4 shrink-0 text-success"
                                            aria-hidden="true"
                                        />
                                        <span>Dicuci higienis dan dikeringkan mesin</span>
                                    </li>
                                    <li class="flex items-start gap-2">
                                        <Check
                                            class="mt-0.5 h-4 w-4 shrink-0 text-success"
                                            aria-hidden="true"
                                        />
                                        <span>Setrika uap</span>
                                    </li>
                                    <li class="flex items-start gap-2">
                                        <Check
                                            class="mt-0.5 h-4 w-4 shrink-0 text-success"
                                            aria-hidden="true"
                                        />
                                        <span>Pewangi premium</span>
                                    </li>
                                    <li class="flex items-start gap-2">
                                        <Check
                                            class="mt-0.5 h-4 w-4 shrink-0 text-success"
                                            aria-hidden="true"
                                        />
                                        <span>Antar jemput gratis maks. 3 km</span>
                                    </li>
                                </ul>

                                <a
                                    :href="getServiceWhatsappUrl('Cuci Setrika Rp 8.000/kg')"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="mt-6 inline-flex min-h-[44px] items-center gap-2 rounded-pill bg-accent-500 px-5 text-sm font-bold text-navy-950 transition-colors hover:bg-accent-600 focus-visible:outline-none"
                                >
                                    <MessageCircle class="h-4 w-4" aria-hidden="true" />
                                    Pesan paket ini
                                </a>
                            </div>

                            <div
                                class="hidden w-12 items-center justify-center bg-accent-500 md:flex md:writing-vertical"
                            >
                                <span
                                    class="font-heading text-[10px] font-extrabold uppercase tracking-[0.22em] text-navy-950"
                                >
                                    Andik Laundry · Gresik
                                </span>
                            </div>
                            <div class="bg-accent-500 py-2 text-center md:hidden">
                                <span
                                    class="font-heading text-[10px] font-extrabold uppercase tracking-[0.22em] text-navy-950"
                                >
                                    Andik Laundry · Gresik
                                </span>
                            </div>
                        </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Proses -->
            <section class="bg-surface py-20 sm:py-24">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="landing-reveal max-w-2xl">
                        <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-accent-600">
                            04 / Proses
                        </p>
                        <h2
                            class="mt-4 text-balance font-heading text-3xl font-extrabold uppercase leading-tight tracking-tight text-navy-950 sm:text-4xl"
                        >
                            Dari nota masuk sampai siap diambil.
                        </h2>
                        <p class="mt-4 text-sm leading-relaxed text-slate-700 sm:text-base">
                            Setiap tahap punya status yang bisa Anda lihat lewat nota digital.
                        </p>
                    </div>

                    <div
                        class="landing-reveal mt-10 overflow-hidden rounded-[24px] border border-border bg-border"
                    >
                        <div class="grid gap-px md:grid-cols-5">
                            <div
                                v-for="(step, index) in processSteps"
                                :key="step.label"
                                class="bg-bg p-5 sm:p-6"
                            >
                                <div class="font-mono text-xs font-medium text-accent-600">
                                    {{ String(index + 1).padStart(2, '0') }}
                                </div>
                                <h3 class="mt-3 font-heading text-base font-bold text-navy-950">
                                    {{ step.label }}
                                </h3>
                                <p class="mt-1.5 text-xs leading-relaxed text-slate-600">
                                    {{ step.desc }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Lokasi -->
            <section id="lokasi" class="bg-navy-900 py-20 text-white sm:py-24">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="landing-reveal max-w-2xl">
                        <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-accent-500">
                            05 / Lokasi
                        </p>
                        <h2
                            class="mt-4 text-balance font-heading text-3xl font-extrabold uppercase leading-tight tracking-tight text-white sm:text-4xl"
                        >
                            Kunjungi outlet kami.
                        </h2>
                        <p class="mt-4 text-sm leading-relaxed text-slate-300 sm:text-base">
                            Datang langsung untuk drop cucian, atau minta penjemputan lewat
                            WhatsApp.
                        </p>
                    </div>

                    <div
                        class="landing-reveal mt-10 grid grid-cols-1 items-center gap-8 rounded-[28px] border border-white/10 bg-white p-5 text-navy-950 sm:p-8 lg:grid-cols-12"
                    >
                        <div class="min-w-0 lg:col-span-5">
                            <p
                                class="text-[11px] font-bold uppercase tracking-[0.16em] text-accent-600"
                            >
                                Outlet resmi
                            </p>
                            <h3 class="mt-2 font-heading text-2xl font-extrabold">
                                {{ storeName }}
                            </h3>

                            <div class="mt-5 space-y-4 text-sm">
                                <div class="flex items-start gap-3">
                                    <MapPin
                                        class="mt-0.5 h-5 w-5 shrink-0 text-accent-600"
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
                                        class="mt-0.5 h-5 w-5 shrink-0 text-accent-600"
                                        aria-hidden="true"
                                    />
                                    <div>
                                        <div class="font-bold">Jam operasional</div>
                                        <p class="mt-0.5 text-slate-600">
                                            Buka setiap hari, 07.00–20.00 WIB
                                        </p>
                                    </div>
                                </div>

                                <div class="flex items-start gap-3">
                                    <Truck
                                        class="mt-0.5 h-5 w-5 shrink-0 text-accent-600"
                                        aria-hidden="true"
                                    />
                                    <div>
                                        <div class="font-bold">Antar jemput</div>
                                        <p class="mt-0.5 text-slate-600">
                                            Gratis untuk jarak maksimal 3 km di area sekitar.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-6 flex flex-col gap-2 sm:flex-row">
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
                                    Buka di Maps
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
            <section id="faq" class="bg-bg py-20 sm:py-24">
                <div
                    class="mx-auto grid max-w-7xl grid-cols-1 gap-10 px-4 sm:px-6 lg:grid-cols-12 lg:gap-16 lg:px-8"
                >
                    <div class="landing-reveal min-w-0 lg:col-span-4">
                        <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-accent-600">
                            06 / FAQ
                        </p>
                        <h2
                            class="mt-4 text-balance font-heading text-3xl font-extrabold uppercase leading-tight tracking-tight text-navy-950 sm:text-4xl"
                        >
                            Pertanyaan yang sering ditanyakan.
                        </h2>
                        <p class="mt-4 text-sm leading-relaxed text-slate-700">
                            Kalau yang Anda cari belum ada di sini, chat admin langsung.
                        </p>
                        <a
                            :href="whatsappUrl"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="mt-5 inline-flex min-h-[44px] items-center gap-2 rounded-pill bg-accent-500 px-5 text-sm font-bold text-navy-950 transition-colors hover:bg-accent-600 focus-visible:outline-none"
                        >
                            <MessageCircle class="h-4 w-4" aria-hidden="true" />
                            Chat admin
                        </a>
                    </div>

                    <div class="min-w-0 lg:col-span-8">
                        <div class="border-y border-navy-900/10">
                            <div
                                v-for="(faq, index) in faqs"
                                :key="faq.q"
                                class="landing-reveal border-b border-navy-900/10 last:border-b-0"
                                :style="{ transitionDelay: `${index * 40}ms` }"
                            >
                                <h3>
                                    <button
                                        :id="`faq-question-${index}`"
                                        type="button"
                                        class="flex min-h-[56px] w-full items-center justify-between gap-4 py-5 text-left font-heading text-base font-bold text-navy-950 transition-colors hover:text-accent-600 focus-visible:outline-none sm:text-lg"
                                        :aria-expanded="activeFaqIndex === index"
                                        :aria-controls="`faq-answer-${index}`"
                                        @click="toggleFaq(index)"
                                    >
                                        <span>{{ faq.q }}</span>
                                        <ChevronDown
                                            class="h-5 w-5 shrink-0 text-accent-600 transition-transform duration-300"
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

            <!-- CTA -->
            <section class="bg-navy-950 py-16 text-white sm:py-20">
                <div class="mx-auto max-w-4xl px-4 text-center sm:px-6">
                    <h2
                        class="text-balance font-heading text-3xl font-extrabold uppercase leading-tight tracking-tight text-white sm:text-4xl"
                    >
                        Lagi sibuk dan cucian numpuk?
                    </h2>
                    <p class="mx-auto mt-4 max-w-xl text-sm leading-relaxed text-slate-300 sm:text-base">
                        Biar kami yang bantu bereskan. Pesan layanan jemput-antar praktis lewat
                        WhatsApp tanpa biaya ribet, atau lebih suka antar sendiri? Silakan mampir
                        langsung ke outlet kami kapan saja.
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
                            <Phone class="h-4 w-4 text-accent-500" aria-hidden="true" />
                            {{ storePhoneLabel }}
                        </a>
                    </div>
                    <p class="mt-5 text-xs text-slate-400">Buka setiap hari, 07.00–20.00 WIB</p>
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
                                andik<span class="text-accent-500">.</span>
                            </span>
                            <span
                                class="text-[10px] font-bold uppercase tracking-[0.22em] text-accent-500"
                            >
                                Laundry
                            </span>
                        </Link>
                        <p class="mt-4 max-w-sm text-sm leading-relaxed">
                            Binatu harian di Hendrosari, Menganti. Bersih, wangi, dan rapi sejak
                            2009.
                        </p>
                        <p class="mt-4 inline-flex items-center gap-2 text-xs font-semibold text-accent-500">
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
                                    Cuci kering
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
                            Akses
                        </p>
                        <ul class="mt-2 space-y-0 text-sm">
                            <li>
                                <a href="#lacak" class="flex min-h-[44px] items-center transition-colors hover:text-white">
                                    Cek status nota
                                </a>
                            </li>
                            <li>
                                <a href="#lokasi" class="flex min-h-[44px] items-center transition-colors hover:text-white">
                                    Lokasi dan maps
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
                                    class="flex min-h-[44px] items-center font-semibold text-accent-500 transition-colors hover:text-white"
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
                        © {{ new Date().getFullYear() }} {{ storeName }}. Sejak 2009 di
                        Hendrosari, Menganti, Gresik.
                    </p>
                    <p>Bersih · Wangi · Rapi</p>
                </div>
            </div>
        </footer>
    </div>
</template>
