<script setup lang="ts">
import 'lenis/dist/lenis.css';
import { Head, Link, router } from '@inertiajs/vue3';
import gsap from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import Lenis from 'lenis';
import {
    Check,
    ChevronDown,
    ChevronRight,
    Clock,
    CreditCard,
    ExternalLink,
    MapPin,
    Menu,
    Phone,
    ReceiptText,
    RotateCcw,
    Scale,
    ShieldCheck,
    Shirt,
    Thermometer,
    Truck,
    X,
} from 'lucide-vue-next';
import { animate, inView } from 'motion';
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import WhatsAppIcon from '@/Components/WhatsAppIcon.vue';

if (typeof window !== 'undefined') {
    gsap.registerPlugin(ScrollTrigger);
}

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
    { href: '#kenapa', label: 'Keunggulan' },
    { href: '#layanan', label: 'Layanan & Tarif' },
    { href: '#standar', label: 'Standar Mutu' },
    { href: '#proses', label: 'Alur Kerja' },
    { href: '#lokasi', label: 'Lokasi' },
    { href: '#faq', label: 'FAQ' },
];

/* ------------------------------------------------------------------
   Keunggulan Layanan
   ------------------------------------------------------------------ */
const advantages = [
    {
        prompt: 'Takut pakaian tertukar dengan pelanggan lain?',
        title: '1 Mesin 1 Pelanggan',
        desc: 'Pakaian dicuci terpisah di dalam satu drum tersendiri. Tidak pernah dicampur dengan cucian milik pelanggan lain demi higienitas dan ketenangan Anda.',
        icon: Shirt,
    },
    {
        prompt: 'Khawatir bahan halus dan sablon rusak?',
        title: 'Setrika Uap Boiler Stabil',
        desc: 'Uap bertekanan lembut dengan suhu terkontrol melicinkan pakaian hingga serat terdalam. Warna terjaga, sablon kaos dan kain sensitif tetap rapi tanpa bekas mengkilap.',
        icon: Thermometer,
    },
    {
        prompt: 'Sibuk dan tidak sempat antar ke outlet?',
        title: 'Gratis Jemput-Antar 3 KM',
        desc: 'Kirim titik lokasi via WhatsApp. Kurir kami menjemput cucian ke depan rumah Anda dan mengantarkannya kembali setelah selesai.',
        icon: Truck,
    },
    {
        prompt: 'Butuh pakaian kerja atau dinas lebih cepat?',
        title: 'Tersedia Layanan Kilat',
        desc: 'Opsi selesai di hari yang sama untuk seragam kerja maupun cucian mendesak (tambahan Rp 2.000/kg) dengan standar kerapian yang sama.',
        icon: Clock,
    },
];

/* ------------------------------------------------------------------
   Kalkulator Estimasi Biaya Laundry
   ------------------------------------------------------------------ */
const estimatorWeight = ref(3);
const estimatorServiceId = ref('cuci-setrika');

const estimatorSelectedService = computed(
    () => services.find((s) => s.id === estimatorServiceId.value) || services[0],
);

const estimatorPricePerKg = computed(() => {
    if (estimatorServiceId.value === 'cuci-setrika') return 8000;
    if (estimatorServiceId.value === 'cuci-kering') return 6000;
    if (estimatorServiceId.value === 'cuci-basah') return 5000;
    return 8000;
});

const estimatorTotal = computed(() => {
    const weight = Math.max(2, Number(estimatorWeight.value) || 2);
    return weight * estimatorPricePerKg.value;
});

const estimatorTotalLabel = computed(() => {
    return `Rp ${estimatorTotal.value.toLocaleString('id-ID')}`;
});

function getEstimatorWhatsappUrl() {
    const phone = storePhone.value.replace(/[^0-9]/g, '');
    const serviceName = estimatorSelectedService.value.name;
    const weight = Math.max(2, Number(estimatorWeight.value) || 2);
    const total = estimatorTotalLabel.value;
    const text = encodeURIComponent(
        `Halo ${storeName.value}, saya ingin pesan ${serviceName} perkiraan ${weight} kg (estimasi ${total}). Mohon info penjemputan ke alamat saya.`,
    );

    return `https://wa.me/${phone}?text=${text}`;
}

/* ------------------------------------------------------------------
   Metode Pembayaran
   ------------------------------------------------------------------ */
const paymentMethods = [
    {
        title: 'QRIS Semua Bank & E-Wallet',
        desc: 'Scan langsung lewat BCA, Mandiri, BRI, BNI, Bank Jatim, Jago, GoPay, OVO, DANA, atau ShopeePay tanpa biaya admin tambahan.',
        icon: CreditCard,
    },
    {
        title: 'Tunai di Kasir atau saat Antar',
        desc: 'Bayar tunai langsung ketika menyerahkan cucian di outlet atau saat kurir mengantarkan pakaian selesai ke rumah.',
        icon: ReceiptText,
    },
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
            'Setrika uap suhu stabil, aman untuk segala kain',
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
        desc: 'Pengeringan suhu terkontrol dilanjut setrika uap agar pakaian licin, lembut, dan siap pakai.',
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

let countersStarted = false;

function setFinalCounters() {
    animatedCustomers.value = stats.value.customers;
    animatedOrders.value = stats.value.orders;
    animatedLaundryKg.value = stats.value.laundry_kg;
}

function setupStatsObserver() {
    if (!hasStats.value || !statsSection.value || typeof window === 'undefined') {
        return;
    }

    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        setFinalCounters();

        return;
    }

    inView(
        statsSection.value,
        () => {
            if (countersStarted) {
                return;
            }
            countersStarted = true;

            const targets = stats.value;

            animate(0, targets.customers, {
                duration: 1.6,
                ease: [0.16, 1, 0.3, 1],
                onUpdate: (latest) => {
                    animatedCustomers.value = Math.round(latest);
                },
            });

            animate(0, targets.orders, {
                duration: 1.6,
                ease: [0.16, 1, 0.3, 1],
                onUpdate: (latest) => {
                    animatedOrders.value = Math.round(latest);
                },
            });

            animate(0, targets.laundry_kg, {
                duration: 1.8,
                ease: [0.16, 1, 0.3, 1],
                onUpdate: (latest) => {
                    animatedLaundryKg.value = Math.round(latest);
                },
            });
        },
        { amount: 0.3 },
    );
}

/* ------------------------------------------------------------------
   Lenis (Smooth Scroll) & GSAP (ScrollTrigger & Motion)
   ------------------------------------------------------------------ */
let lenisInstance: Lenis | null = null;
let tickerHandler: ((time: number) => void) | null = null;
let revealObserver: IntersectionObserver | null = null;

function revealVisibleElements(root: Document | Element = document) {
    root.querySelectorAll<HTMLElement>(
        '.landing-reveal, .landing-reveal-left, .landing-reveal-right',
    ).forEach((el) => {
        el.classList.add('is-visible');
    });
}

async function smoothScrollTo(target: string) {
    if (menuOpen.value) {
        menuOpen.value = false;
        if (typeof document !== 'undefined') {
            document.body.style.overflow = '';
        }
        lenisInstance?.start();
        await nextTick();
    }

    if (typeof document !== 'undefined') {
        const el = document.querySelector<HTMLElement>(target);
        if (el) {
            revealVisibleElements(el);
        }
    }

    if (lenisInstance) {
        lenisInstance.scrollTo(target, { offset: 0, duration: 1.0, force: true });
    } else if (typeof document !== 'undefined') {
        const el = document.querySelector<HTMLElement>(target);
        if (el) {
            const scrollOffset = target === '#tarif' ? -92 : -44;
            const top = el.getBoundingClientRect().top + window.scrollY + scrollOffset;
            window.scrollTo({ top: Math.max(0, top), behavior: 'smooth' });
        }
    }
}

function initSmoothScrollAndAnimations() {
    if (typeof window === 'undefined') {
        return;
    }

    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const revealTargets = document.querySelectorAll<HTMLElement>(
        '.landing-reveal, .landing-reveal-left, .landing-reveal-right',
    );

    if (!prefersReducedMotion) {
        if ('IntersectionObserver' in window) {
            revealObserver = new IntersectionObserver(
                (entries) => {
                    entries.forEach((entry) => {
                        if (entry.isIntersecting) {
                            entry.target.classList.add('is-visible');
                            revealObserver?.unobserve(entry.target);
                        }
                    });
                },
                { rootMargin: '0px 0px -4% 0px', threshold: 0.02 },
            );

            revealTargets.forEach((el) => {
                if (el.getBoundingClientRect().top < window.innerHeight * 0.96) {
                    el.classList.add('is-visible');
                } else {
                    revealObserver?.observe(el);
                }
            });
        } else {
            revealVisibleElements();
        }

        lenisInstance = new Lenis({
            duration: 1.1,
            easing: (t) => Math.min(1, 1.001 - Math.pow(2, -10 * t)),
            smoothWheel: true,
            touchMultiplier: 1.2,
        });

        lenisInstance.on('scroll', ScrollTrigger.update);

        tickerHandler = (time: number) => {
            lenisInstance?.raf(time * 1000);
        };
        gsap.ticker.add(tickerHandler);
        gsap.ticker.lagSmoothing(0);

        // Desktop-only subtle parallax on hero background video inside clipped layer
        const heroVideoEl = document.querySelector('.hero-bg-video');
        if (heroVideoEl && window.matchMedia('(min-width: 768px)').matches) {
            gsap.fromTo(
                heroVideoEl,
                { yPercent: -2, scale: 1.06 },
                {
                    yPercent: 2,
                    scale: 1.06,
                    ease: 'none',
                    scrollTrigger: {
                        trigger: '#beranda',
                        start: 'top top',
                        end: 'bottom top',
                        scrub: 1,
                    },
                },
            );
        }

        if (heroVideoRef.value) {
            heroVideoRef.value.muted = true;
            void heroVideoRef.value.play().catch(() => {});
        }
    } else {
        heroVideoRef.value?.pause();
        revealVisibleElements();
    }
}

const heroVideoRef = ref<HTMLVideoElement | null>(null);

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
        lenisInstance?.stop();
        await nextTick();
        menuPanelRef.value?.focus();
    } else {
        lenisInstance?.start();
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

onMounted(() => {
    handleScroll();
    window.addEventListener('scroll', handleScroll, { passive: true });
    window.addEventListener('keydown', handleKeydown);

    nextTick(() => {
        initSmoothScrollAndAnimations();
        setupStatsObserver();
    });
});

onBeforeUnmount(() => {
    if (revealObserver) {
        revealObserver.disconnect();
        revealObserver = null;
    }
    if (lenisInstance) {
        lenisInstance.destroy();
        lenisInstance = null;
    }
    if (tickerHandler) {
        gsap.ticker.remove(tickerHandler);
        tickerHandler = null;
    }
    ScrollTrigger.getAll().forEach((trigger) => trigger.kill());

    if (scrollFrame !== null) {
        cancelAnimationFrame(scrollFrame);
        scrollFrame = null;
    }

    window.removeEventListener('scroll', handleScroll);
    window.removeEventListener('keydown', handleKeydown);

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
        <a
            href="#main-content"
            class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-[60] focus:rounded-pill focus:bg-accent-500 focus:px-4 focus:py-2 focus:font-bold focus:text-navy-950 focus:shadow-lg focus:outline-none"
            @click.prevent="smoothScrollTo('#main-content')"
        >
            Lewati ke konten utama
        </a>

        <!-- Floating Navbar -->
        <header
            class="fixed inset-x-3 sm:inset-x-6 top-3 sm:top-4 z-40 mx-auto max-w-6xl transition-all duration-300"
        >
            <div
                class="flex h-14 sm:h-16 items-center justify-between gap-2 lg:gap-3 rounded-full border px-3.5 sm:px-5 lg:px-6 shadow-[0_8px_30px_rgba(8,16,80,0.08)] backdrop-blur-md transition-all duration-300"
                :class="
                    isScrolled
                        ? 'border-border/90 bg-white/95 shadow-[0_12px_36px_rgba(8,16,80,0.12)]'
                        : 'border-white/80 bg-white/90'
                "
            >
                <!-- Logo didalam navbar -->
                <Link
                    href="/"
                    class="flex shrink-0 items-center gap-2 rounded-full py-1 focus-visible:outline-none transition-transform hover:scale-105 active:scale-95"
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

                <!-- Nav Links Desktop (single line, no wrapping) -->
                <nav class="hidden items-center gap-0.5 xl:gap-1 lg:flex" aria-label="Navigasi utama">
                    <a
                        v-for="link in navLinks"
                        :key="link.href"
                        :href="link.href"
                        class="nav-link whitespace-nowrap rounded-full px-2 xl:px-3 py-1.5 text-xs xl:text-[13px] font-semibold text-slate-700 transition-colors hover:bg-slate-100 hover:text-navy-950 focus-visible:outline-none"
                        @click.prevent="smoothScrollTo(link.href)"
                    >
                        <span class="roll">
                            <span :data-t="link.label">{{ link.label }}</span>
                        </span>
                    </a>
                </nav>

                <!-- Action Button didalam navbar (WhatsApp CTA) -->
                <div class="flex shrink-0 items-center gap-2">
                    <a
                        :href="whatsappUrl"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="nav-btn inline-flex h-9 sm:h-10 shrink-0 items-center justify-center gap-2 whitespace-nowrap rounded-full bg-accent-500 px-3.5 sm:px-4 text-xs sm:text-sm font-bold text-navy-950 shadow-xs transition-all hover:bg-accent-600 hover:scale-105 active:scale-95 focus-visible:outline-none"
                        aria-label="Hubungi WhatsApp Andik Laundry"
                        title="Chat WhatsApp"
                    >
                        <WhatsAppIcon class="h-4 w-4" />
                        <span class="roll hidden sm:inline-block">
                            <span data-t="WhatsApp">WhatsApp</span>
                        </span>
                    </a>

                    <!-- Mobile & Tablet Menu Trigger -->
                    <button
                        ref="menuButtonRef"
                        type="button"
                        class="inline-flex min-h-[44px] min-w-[44px] items-center justify-center rounded-full text-navy-950 transition-colors hover:bg-accent-100 focus-visible:outline-none active:scale-95 lg:hidden"
                        :aria-expanded="menuOpen"
                        aria-controls="mobile-menu"
                        :aria-label="menuOpen ? 'Tutup menu' : 'Buka menu navigasi'"
                        @click="menuOpen = !menuOpen"
                    >
                        <X v-if="menuOpen" class="h-5 w-5" aria-hidden="true" />
                        <Menu v-else class="h-5 w-5" aria-hidden="true" />
                    </button>
                </div>
            </div>
        </header>

        <!-- Menu seluler (Side Drawer Kanan) -->
        <Teleport to="body">
            <!-- Backdrop -->
            <Transition
                enter-active-class="transition-opacity duration-300 ease-out"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition-opacity duration-200 ease-in"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div
                    v-if="menuOpen"
                    class="fixed inset-0 z-50 bg-navy-950/45 backdrop-blur-xs lg:hidden"
                    aria-hidden="true"
                    @click="menuOpen = false"
                ></div>
            </Transition>

            <!-- Drawer Panel dari Sisi Kanan -->
            <Transition
                enter-active-class="transition-transform duration-300 ease-out"
                enter-from-class="translate-x-full"
                enter-to-class="translate-x-0"
                leave-active-class="transition-transform duration-200 ease-in"
                leave-from-class="translate-x-0"
                leave-to-class="translate-x-full"
            >
                <div
                    v-if="menuOpen"
                    id="mobile-menu"
                    ref="menuPanelRef"
                    role="dialog"
                    aria-modal="true"
                    aria-label="Menu navigasi samping"
                    tabindex="-1"
                    class="fixed inset-y-0 right-0 z-50 flex h-full w-[290px] sm:w-[320px] max-w-[85vw] flex-col justify-between overflow-y-auto overscroll-contain bg-white p-5 text-navy-950 shadow-2xl focus:outline-none lg:hidden"
                >
                    <!-- Header Drawer -->
                    <div>
                        <div class="flex items-center justify-between pb-4 border-b border-border/70">
                            <Link
                                href="/"
                                class="inline-flex min-h-[44px] items-center gap-2.5 rounded-lg focus-visible:outline-none"
                                aria-label="Andik Laundry, kembali ke beranda"
                                @click="menuOpen = false"
                            >
                                <img
                                    src="/images/logo-a.webp"
                                    alt=""
                                    class="h-8 w-auto shrink-0 object-contain"
                                    width="33"
                                    height="32"
                                    aria-hidden="true"
                                />
                                <span class="font-heading text-base font-extrabold tracking-tight text-navy-950">
                                    {{ storeName }}
                                </span>
                            </Link>
                            <button
                                type="button"
                                class="inline-flex min-h-[40px] min-w-[40px] items-center justify-center rounded-full text-slate-500 hover:bg-slate-100 hover:text-navy-950 transition-colors focus-visible:outline-none active:scale-95"
                                aria-label="Tutup menu navigasi"
                                @click="menuOpen = false"
                            >
                                <X class="h-5 w-5" aria-hidden="true" />
                            </button>
                        </div>

                        <!-- Nav links list -->
                        <nav class="mt-4 flex flex-col gap-1" aria-label="Navigasi seluler samping">
                            <a
                                v-for="link in navLinks"
                                :key="link.href"
                                :href="link.href"
                                class="flex min-h-[46px] items-center justify-between rounded-xl px-3.5 text-sm font-semibold text-slate-700 transition-colors hover:bg-accent-50 hover:text-navy-950 active:bg-accent-100"
                                @click.prevent="smoothScrollTo(link.href)"
                            >
                                <span>{{ link.label }}</span>
                                <ChevronRight class="h-4 w-4 text-slate-400 opacity-60" aria-hidden="true" />
                            </a>
                        </nav>
                    </div>

                    <!-- Footer Drawer -->
                    <div class="pt-4 border-t border-border/70 space-y-3">
                        <div class="rounded-xl bg-slate-50 p-3 border border-border/60 text-xs">
                            <div class="flex items-center gap-2 font-bold text-navy-950">
                                <Clock class="h-3.5 w-3.5 text-accent-700" aria-hidden="true" />
                                Buka Setiap Hari (07.00 - 20.00)
                            </div>
                            <p class="mt-0.5 text-[11px] text-slate-600">Hendrosari, Menganti, Gresik</p>
                        </div>

                        <a
                            :href="whatsappUrl"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="flex min-h-[46px] items-center justify-center gap-2 rounded-full bg-accent-500 px-4 text-sm font-bold text-navy-950 shadow-xs transition-colors hover:bg-accent-600 active:scale-98"
                            @click="menuOpen = false"
                        >
                            <WhatsAppIcon class="h-4 w-4" />
                            Chat WhatsApp
                        </a>

                        <div v-if="canLogin" class="text-center pt-1">
                            <Link
                                href="/login"
                                class="text-xs font-semibold text-slate-600 hover:text-accent-700 transition-colors py-1 inline-block"
                                @click="menuOpen = false"
                            >
                                Masuk Dashboard Staf
                            </Link>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <main id="main-content">
            <!-- Hero -->
            <section
                id="beranda"
                class="relative overflow-hidden bg-bg pb-10 pt-[220px] sm:pb-12 sm:pt-[250px] md:pt-24 md:pb-8 lg:pt-24 lg:pb-10"
            >
                <!-- Background Video & Contrast Scrims -->
                <div
                    class="hero-video-shell pointer-events-none absolute inset-x-0 top-0 h-[330px] overflow-hidden sm:h-[370px] md:inset-0 md:bottom-3 md:h-auto"
                    aria-hidden="true"
                >
                    <video
                        ref="heroVideoRef"
                        class="hero-bg-video h-full w-full object-cover object-[72%_42%] sm:object-[68%_42%] md:object-center will-change-transform"
                        autoplay
                        muted
                        loop
                        playsinline
                        preload="auto"
                        disablepictureinpicture
                        poster="/images/bg-hero-poster.webp"
                    >
                        <source src="/videos/bg-hero.mp4" type="video/mp4" />
                    </video>

                    <div class="hero-scrim-mobile absolute inset-0 md:hidden"></div>
                    <div class="hero-scrim-desktop absolute inset-0 hidden md:block"></div>
                </div>

                <!-- Seamless Bottom Cover Overlay -->
                <div
                    class="pointer-events-none absolute inset-x-0 bottom-0 z-[5] hidden h-24 bg-gradient-to-t from-bg via-bg/85 to-transparent md:block"
                    aria-hidden="true"
                ></div>
                <div
                    class="pointer-events-none absolute inset-x-0 bottom-0 z-[5] hidden h-4 bg-bg md:block"
                    aria-hidden="true"
                ></div>

                <div
                    class="relative z-10 mx-auto grid max-w-7xl grid-cols-1 items-center gap-6 px-4 sm:px-6 md:grid-cols-12 md:gap-8 lg:gap-10 lg:px-8"
                >
                    <div class="min-w-0 md:col-span-7 lg:col-span-6">
                        <div
                            class="landing-enter"
                            style="--enter-delay: 80ms"
                        >
                            <p
                                class="inline-flex items-center gap-1.5 text-xs font-bold uppercase tracking-[0.14em] text-navy-950"
                            >
                                <MapPin class="h-3.5 w-3.5 shrink-0 text-accent-700" aria-hidden="true" />
                                <span>Hendrosari, Menganti, Gresik</span>
                            </p>

                            <h1
                                class="mt-2.5 font-heading text-[2rem] font-extrabold uppercase leading-[1.02] tracking-tight text-navy-950 sm:text-5xl sm:leading-[0.98] md:text-[2.4rem] lg:text-[3.25rem] xl:text-[3.65rem]"
                            >
                                Cucian Kotor,<br />
                                <span class="text-accent-700">Pulang Kinclong.</span>
                            </h1>

                            <p
                                class="mt-3 text-sm leading-relaxed text-navy-950/90 sm:text-base md:max-w-lg lg:mt-4 lg:max-w-xl lg:text-[17px]"
                            >
                                Tinggal chat WhatsApp atau mampir ke outlet, tumpukan baju kotor langsung
                                beres. Di Andik Laundry, pakaian dicuci higienis terpisah 1 mesin 1 pelanggan,
                                timbangan transparan, nota digital langsung ke WhatsApp, dan gratis jemput-antar
                                hingga 3 km.
                            </p>

                            <div class="mt-5 flex flex-col gap-2.5 sm:flex-row lg:mt-6">
                                <a
                                    :href="whatsappUrl"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="inline-flex min-h-[46px] items-center justify-center gap-2 rounded-pill bg-accent-500 px-6 text-sm font-bold text-navy-950 shadow-xs transition-colors hover:bg-accent-600 focus-visible:outline-none"
                                >
                                    <WhatsAppIcon class="h-4 w-4" />
                                    Pesan antar jemput
                                </a>
                                <a
                                    href="#tarif"
                                    class="inline-flex min-h-[46px] items-center justify-center rounded-pill border border-navy-900/15 bg-white px-6 text-sm font-bold text-navy-950 shadow-xs transition-colors hover:bg-slate-50 focus-visible:outline-none"
                                    @click.prevent="smoothScrollTo('#tarif')"
                                >
                                    Lihat tarif & estimasi
                                </a>
                            </div>

                            <dl
                                class="mt-5 flex flex-wrap items-center gap-x-5 gap-y-1.5 border-t border-navy-950/10 pt-4 text-xs font-semibold text-navy-950 lg:mt-6"
                            >
                                <div class="flex items-center gap-1.5">
                                    <Clock class="h-3.5 w-3.5 text-accent-700" aria-hidden="true" />
                                    <dd>07.00–20.00 WIB</dd>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <Truck class="h-3.5 w-3.5 text-accent-700" aria-hidden="true" />
                                    <dd>Antar Jemput Gratis ≤3 km</dd>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <ShieldCheck class="h-3.5 w-3.5 text-accent-700" aria-hidden="true" />
                                    <dd>1 Mesin 1 Pelanggan</dd>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <ReceiptText class="h-3.5 w-3.5 text-accent-700" aria-hidden="true" />
                                    <dd>Nota Digital WhatsApp</dd>
                                </div>
                            </dl>
                        </div>
                    </div>

                    <div class="hidden min-w-0 md:block md:col-span-5 md:min-h-[380px] lg:col-span-6 lg:min-h-[420px]" aria-hidden="true"></div>
                </div>
            </section>

            <!-- Cek nota -->
            <section id="lacak" class="relative scroll-mt-11 py-10 md:py-12">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div
                        class="rounded-[24px] border border-navy-800 bg-navy-900 p-5 text-white shadow-[0_16px_40px_rgba(8,16,80,0.14)] md:p-7"
                    >
                        <div class="grid grid-cols-1 items-center gap-5 md:grid-cols-12 md:gap-8">
                            <div class="md:col-span-5">
                                <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-accent-400">
                                    Lacak Pesanan
                                </p>
                                <h2 class="mt-1 font-heading text-xl font-bold text-white md:text-2xl">
                                    Sudah punya nota cucian?
                                </h2>
                                <p class="mt-1.5 text-sm leading-relaxed text-slate-300">
                                    Masukkan nomor nota atau nomor HP Anda. Status pencucian terbuka
                                    secara transparan tanpa perlu login.
                                </p>
                            </div>

                            <div class="min-w-0 md:col-span-7">
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
            <section id="kenapa" class="scroll-mt-11 py-10 md:py-12">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="landing-reveal mx-auto max-w-3xl text-center">
                        <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-accent-700">
                            Keunggulan Layanan
                        </p>
                        <h2
                            class="mt-1.5 text-balance font-heading text-2xl font-extrabold uppercase leading-tight tracking-tight text-navy-950 sm:text-3xl md:text-[2rem]"
                        >
                            Mengapa Memilih Andik Laundry?
                        </h2>
                        <p class="mx-auto mt-2.5 max-w-2xl text-sm leading-relaxed text-slate-700">
                            Sejak 2009 melayani keluarga di Hendrosari & Menganti dengan standar higienis yang konsisten untuk setiap cucian.
                        </p>
                    </div>

                    <div class="mt-7 grid grid-cols-1 gap-4 md:grid-cols-2">
                        <article
                            v-for="(adv, index) in advantages"
                            :key="adv.title"
                            class="landing-reveal rounded-[20px] border border-border/80 bg-white p-5 text-navy-950"
                            :style="{ transitionDelay: `${index * 60}ms` }"
                        >
                            <div class="flex items-start gap-4">
                                <div
                                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-accent-100/70 text-accent-700"
                                >
                                    <component :is="adv.icon" class="h-5 w-5" aria-hidden="true" />
                                </div>
                                <div class="min-w-0">
                                    <p class="text-xs font-semibold text-accent-700">
                                        {{ adv.prompt }}
                                    </p>
                                    <h3 class="mt-0.5 font-heading text-lg font-bold text-navy-950">
                                        {{ adv.title }}
                                    </h3>
                                    <p class="mt-1.5 text-sm leading-relaxed text-slate-600">
                                        {{ adv.desc }}
                                    </p>
                                </div>
                            </div>
                        </article>
                    </div>
                </div>
            </section>

            <!-- Tarif & Layanan -->
            <section id="layanan" class="scroll-mt-11 py-10 md:py-12">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="landing-reveal mx-auto max-w-3xl text-center">
                        <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-accent-700">
                            Pilihan Layanan & Tarif
                        </p>
                        <h2
                            class="mt-1.5 text-balance font-heading text-2xl font-extrabold uppercase leading-tight tracking-tight text-navy-950 sm:text-3xl md:text-[2rem]"
                        >
                            Harganya jelas, <span class="text-accent-700">pelanggan puas.</span>
                        </h2>
                        <p class="mx-auto mt-2 max-w-2xl text-sm leading-relaxed text-slate-700">
                            Minimal 2 kg untuk cucian kiloan. Reguler 2–3 hari kerja, atau kilat hari yang sama (+Rp 2.000/kg).
                        </p>
                    </div>

                    <div id="tarif" class="mt-5 grid scroll-mt-[92px] grid-cols-1 gap-4 md:grid-cols-12">
                        <!-- Featured Card: Cuci Setrika -->
                        <div class="landing-reveal min-w-0 md:col-span-5">
                            <article
                                class="flex h-full flex-col rounded-[22px] border-2 border-accent-500/80 bg-white p-5 text-navy-950 shadow-sm"
                            >
                                <div class="flex items-center justify-between gap-3">
                                    <p
                                        class="text-[11px] font-bold uppercase tracking-[0.16em] text-accent-700"
                                    >
                                        {{ featuredService.label }}
                                    </p>
                                    <span class="text-xs font-bold text-navy-950">
                                        Paling Diminati
                                    </span>
                                </div>

                                <h3
                                    class="mt-1.5 font-heading text-2xl font-extrabold"
                                >
                                    {{ featuredService.name }}
                                </h3>

                                <div class="mt-1 flex items-baseline gap-1">
                                    <span class="font-heading text-3xl font-extrabold">
                                        {{ featuredService.price }}
                                    </span>
                                    <span class="text-sm font-semibold text-slate-600">
                                        / {{ featuredService.unit }}
                                    </span>
                                </div>

                                <p class="mt-0.5 text-xs font-semibold text-slate-600">
                                    {{ featuredService.minWeight }}
                                </p>

                                <p
                                    class="mt-2.5 text-sm leading-snug text-slate-700"
                                >
                                    {{ featuredService.desc }}
                                </p>

                                <ul
                                    class="mt-3 space-y-1.5 text-sm text-slate-700"
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

                                <div class="mt-auto pt-4">
                                    <a
                                        :href="getServiceWhatsappUrl(featuredService.name)"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="inline-flex min-h-[44px] w-full items-center justify-center gap-2 rounded-pill bg-accent-500 px-5 text-sm font-bold text-navy-950 transition-colors hover:bg-accent-600 focus-visible:outline-none"
                                    >
                                        <WhatsAppIcon class="h-4 w-4" />
                                        Pesan {{ featuredService.name }}
                                    </a>
                                </div>
                            </article>
                        </div>

                        <!-- Other Services -->
                        <div class="min-w-0 md:col-span-7">
                            <div class="grid gap-2.5">
                                <article
                                    v-for="(service, index) in otherServices"
                                    :key="service.id"
                                    class="landing-reveal flex flex-col rounded-[18px] border border-border/80 bg-white px-4 py-3.5 text-navy-950"
                                    :style="{ transitionDelay: `${index * 70}ms` }"
                                >
                                    <div class="flex items-start justify-between gap-4">
                                        <div class="min-w-0">
                                            <h3
                                                class="font-heading text-base font-bold leading-tight md:text-lg"
                                            >
                                                {{ service.name }}
                                            </h3>
                                            <p
                                                class="mt-0.5 text-xs font-semibold text-slate-600"
                                            >
                                                {{ service.minWeight }}
                                            </p>
                                        </div>
                                        <div class="shrink-0 text-right leading-tight">
                                            <div class="font-heading text-xl font-extrabold md:text-2xl">
                                                {{ service.price }}
                                            </div>
                                            <div
                                                class="text-xs font-semibold text-slate-600"
                                            >
                                                / {{ service.unit }}
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mt-2 flex flex-col gap-2.5 sm:flex-row sm:items-end sm:justify-between">
                                        <p
                                            class="text-[13px] leading-snug text-slate-600"
                                        >
                                            {{ service.desc }}
                                        </p>

                                        <a
                                            :href="getServiceWhatsappUrl(service.name)"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="inline-flex min-h-[44px] w-full shrink-0 items-center justify-center gap-1.5 whitespace-nowrap rounded-pill bg-accent-500 px-4 text-xs font-bold text-navy-950 transition-colors hover:bg-accent-600 focus-visible:outline-none sm:w-auto"
                                        >
                                            <WhatsAppIcon class="h-3.5 w-3.5" />
                                            Pesan {{ service.name }}
                                        </a>
                                    </div>
                                </article>
                            </div>

                            <p class="mt-2.5 text-xs leading-snug text-slate-600">
                                * Layanan kilat selesai hari yang sama dengan tambahan Rp 2.000 per kg.
                                Layanan cuci karpet dihitung berdasarkan luas (panjang × lebar).
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Kalkulator Estimasi Biaya Laundry -->
            <section id="estimasi" class="scroll-mt-11 py-10 md:py-12">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="landing-reveal rounded-[24px] border border-border/80 bg-white p-6 text-navy-950 md:p-7">
                        <div class="grid grid-cols-1 gap-6 md:grid-cols-12 md:items-center">
                            <div class="md:col-span-5">
                                <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-accent-700">
                                    Kalkulator Biaya
                                </p>
                                <h2 class="mt-1.5 font-heading text-2xl font-extrabold text-navy-950 md:text-[1.75rem]">
                                    Hitung Perkiraan Cucian Anda
                                </h2>
                                <p class="mt-2 text-sm leading-relaxed text-slate-600">
                                    Pilih jenis layanan dan masukkan perkiraan berat pakaian. Berat pasti ditimbang terbuka saat kurir menjemput atau di kasir outlet.
                                </p>
                            </div>

                            <div class="md:col-span-7 rounded-2xl bg-slate-50/70 p-5 border border-border/80">
                                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                    <div>
                                        <label for="estimator-service" class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-slate-600">
                                            Pilih Layanan
                                        </label>
                                        <select
                                            id="estimator-service"
                                            name="estimator_service"
                                            v-model="estimatorServiceId"
                                            class="h-11 w-full rounded-xl border border-slate-300 bg-white px-3 text-sm font-semibold text-navy-950 focus:border-accent-500 focus:outline-none"
                                        >
                                            <option value="cuci-setrika">Cuci Setrika (Rp 8.000/kg)</option>
                                            <option value="cuci-kering">Cuci Kering Lipat (Rp 6.000/kg)</option>
                                            <option value="cuci-basah">Cuci Basah (Rp 5.000/kg)</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label for="estimator-weight-range" class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-slate-600">
                                            Perkiraan Berat: <span class="font-extrabold text-accent-700">{{ estimatorWeight }} kg</span>
                                        </label>
                                        <div class="flex items-center gap-2">
                                            <input
                                                id="estimator-weight-range"
                                                name="estimator_weight_range"
                                                aria-label="Geser untuk memilih perkiraan berat cucian dalam kilogram"
                                                v-model.number="estimatorWeight"
                                                type="range"
                                                min="2"
                                                max="20"
                                                step="1"
                                                class="w-full cursor-pointer accent-accent-500"
                                            />
                                            <input
                                                id="estimator-weight-number"
                                                name="estimator_weight_number"
                                                aria-label="Ketik angka perkiraan berat cucian dalam kilogram"
                                                v-model.number="estimatorWeight"
                                                type="number"
                                                min="2"
                                                max="50"
                                                class="h-11 w-16 rounded-xl border border-slate-300 bg-white px-2 text-center text-sm font-bold text-navy-950 focus:border-accent-500 focus:outline-none"
                                            />
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-4 flex flex-col gap-4 border-t border-slate-200/80 pt-4 sm:flex-row sm:items-center sm:justify-between">
                                    <div>
                                        <div class="text-xs font-medium text-slate-600">Estimasi Biaya Cucian:</div>
                                        <div class="font-heading text-2xl font-extrabold tabular-nums text-navy-950 sm:text-3xl">
                                            {{ estimatorTotalLabel }}
                                        </div>
                                        <div class="text-xs font-medium tabular-nums text-slate-600">
                                            {{ estimatorWeight }} kg × Rp {{ estimatorPricePerKg.toLocaleString('id-ID') }}/kg
                                        </div>
                                    </div>

                                    <a
                                        :href="getEstimatorWhatsappUrl()"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="inline-flex min-h-[44px] items-center justify-center gap-2 rounded-pill bg-accent-500 px-6 text-sm font-bold text-navy-950 shadow-xs transition-colors hover:bg-accent-600 focus-visible:outline-none"
                                    >
                                        <WhatsAppIcon class="h-4 w-4" />
                                        Pesan Sesuai Estimasi
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Standar Kualitas & Komitmen -->
            <section id="standar" class="scroll-mt-11 py-10 md:py-12">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="landing-reveal mx-auto max-w-3xl text-center">
                        <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-accent-700">
                            Standar Kualitas
                        </p>
                        <h2
                            class="mt-1.5 text-balance font-heading text-2xl font-extrabold uppercase leading-tight tracking-tight text-navy-950 sm:text-3xl md:text-[2rem]"
                        >
                            3 Jaminan Mutu Andik Laundry
                        </h2>
                        <p class="mx-auto mt-2.5 max-w-2xl text-sm leading-relaxed text-slate-700">
                            Kepercayaan pelanggan di Hendrosari dan sekitarnya dibangun dari konsistensi mutu.
                            Berikut prinsip kerja yang kami pegang teguh pada setiap pesanan.
                        </p>
                    </div>

                    <div class="landing-reveal mt-6 grid grid-cols-1 divide-y divide-border/80 rounded-[22px] border border-border/80 bg-white md:grid-cols-3 md:divide-x md:divide-y-0">
                        <div
                            v-for="(guarantee, idx) in qualityGuarantees"
                            :key="guarantee.title"
                            class="flex flex-col p-5 text-navy-950 md:p-6"
                        >
                            <div class="flex items-center justify-between gap-3">
                                <span class="font-mono text-xs font-bold tracking-wider text-accent-700">
                                    0{{ idx + 1 }}
                                </span>
                                <component :is="guarantee.icon" class="h-5 w-5 text-accent-700" aria-hidden="true" />
                            </div>
                            <h3 class="mt-3 font-heading text-lg font-bold">
                                {{ guarantee.title }}
                            </h3>
                            <p class="mt-1.5 text-sm leading-relaxed text-slate-600">
                                {{ guarantee.desc }}
                            </p>
                        </div>
                    </div>

                    <!-- Database Statistics / Operational Banner -->
                    <div
                        v-if="hasStats"
                        ref="statsSection"
                        class="landing-reveal mt-5 rounded-[24px] border border-navy-800 bg-navy-900 p-5 text-white md:p-6"
                        style="--reveal-delay: 150ms"
                    >
                        <div class="border-b border-white/10 pb-4 text-center md:flex md:items-center md:justify-between md:gap-8 md:text-left">
                            <div>
                                <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-accent-400">
                                    Catatan Kinerja Nyata
                                </p>
                                <h3 class="mt-1 font-heading text-xl font-bold md:text-2xl">
                                    Dipercaya Masyarakat Sejak 2009
                                </h3>
                            </div>
                            <p class="mt-1.5 max-w-xs text-xs text-slate-300 md:mt-0">
                                Data terakumulasi otomatis dari sistem manajemen operasional kami.
                            </p>
                        </div>

                        <div class="mt-5 grid grid-cols-1 gap-4 text-center md:grid-cols-3 md:gap-6">
                            <div>
                                <div class="font-heading text-3xl font-extrabold tabular-nums text-accent-400">
                                    {{ customersLabel }}
                                </div>
                                <div class="mt-1 text-xs font-semibold uppercase tracking-[0.12em] text-slate-300">
                                    Pelanggan Terdaftar
                                </div>
                            </div>
                            <div>
                                <div class="font-heading text-3xl font-extrabold tabular-nums text-accent-400">
                                    {{ ordersLabel }}
                                </div>
                                <div class="mt-1 text-xs font-semibold uppercase tracking-[0.12em] text-slate-300">
                                    Nota Diselesaikan
                                </div>
                            </div>
                            <div>
                                <div class="font-heading text-3xl font-extrabold tabular-nums text-accent-400">
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
            <section id="proses" class="scroll-mt-11 py-10 md:py-12">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="landing-reveal mx-auto max-w-3xl text-center">
                        <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-accent-700">
                            Alur Pengerjaan
                        </p>
                        <h2
                            class="mt-1.5 text-balance font-heading text-2xl font-extrabold uppercase leading-tight tracking-tight text-navy-950 sm:text-3xl md:text-[2rem]"
                        >
                            5 Langkah Mudah Cucian Beres
                        </h2>
                        <p class="mx-auto mt-2.5 max-w-2xl text-sm leading-relaxed text-slate-700">
                            Setiap tahap memiliki status yang dapat Anda pantau langsung melalui nota digital WhatsApp.
                        </p>
                    </div>

                    <div
                        class="mt-7 grid grid-cols-1 gap-5 sm:grid-cols-2 md:grid-cols-5 md:gap-4"
                    >
                        <div
                            v-for="(step, index) in processSteps"
                            :key="step.label"
                            class="landing-reveal flex flex-col border-t-2 border-accent-600/30 pt-3.5"
                            :style="{ transitionDelay: `${index * 60}ms` }"
                        >
                            <span
                                class="font-mono text-xs font-bold tracking-wider text-accent-700"
                            >
                                LANGKAH 0{{ step.step }}
                            </span>
                            <h3 class="mt-1.5 font-heading text-base font-bold text-navy-950">
                                {{ step.label }}
                            </h3>
                            <p class="mt-1 text-sm leading-relaxed text-slate-600">
                                {{ step.desc }}
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Metode Pembayaran -->
            <section id="pembayaran" class="scroll-mt-11 py-10 md:py-12">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="landing-reveal mx-auto max-w-3xl text-center">
                        <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-accent-700">
                            Metode Pembayaran
                        </p>
                        <h2
                            class="mt-1.5 text-balance font-heading text-2xl font-extrabold uppercase leading-tight tracking-tight text-navy-950 sm:text-3xl md:text-[2rem]"
                        >
                            Bayar Praktis, Cashless & Tunai
                        </h2>
                        <p class="mx-auto mt-2.5 max-w-2xl text-sm leading-relaxed text-slate-700">
                            Pilih pembayaran nontunai via QRIS tanpa biaya tambahan atau bayar tunai saat cucian diantar.
                        </p>
                    </div>

                    <div class="mx-auto mt-7 grid max-w-4xl grid-cols-1 gap-4 md:grid-cols-2">
                        <div
                            v-for="(pay, idx) in paymentMethods"
                            :key="pay.title"
                            class="landing-reveal flex items-start gap-4 rounded-[20px] border border-border/80 bg-white p-5 text-navy-950 md:p-6"
                            :style="{ transitionDelay: `${idx * 70}ms` }"
                        >
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-accent-100/70 text-accent-700">
                                <component :is="pay.icon" class="h-5 w-5" aria-hidden="true" />
                            </div>
                            <div class="min-w-0">
                                <h3 class="font-heading text-lg font-bold text-navy-950">
                                    {{ pay.title }}
                                </h3>
                                <p class="mt-1.5 text-sm leading-relaxed text-slate-600">
                                    {{ pay.desc }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Lokasi Outlet -->
            <section id="lokasi" class="scroll-mt-11 py-10 md:py-12">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="landing-reveal mx-auto max-w-3xl text-center">
                        <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-accent-700">
                            Lokasi Outlet
                        </p>
                        <h2
                            class="mt-1.5 text-balance font-heading text-2xl font-extrabold uppercase leading-tight tracking-tight text-navy-950 sm:text-3xl md:text-[2rem]"
                        >
                            Kunjungi Outlet Kami
                        </h2>
                        <p class="mx-auto mt-2.5 max-w-2xl text-sm leading-relaxed text-slate-700">
                            Bisa mampir langsung untuk drop cucian, atau chat kami untuk penjemputan gratis hingga 3 km.
                        </p>
                    </div>

                    <div
                        class="landing-reveal mt-6 grid grid-cols-1 items-center gap-6 rounded-[24px] border border-border/80 bg-white p-5 text-navy-950 shadow-[0_12px_32px_rgba(8,16,80,0.05)] md:grid-cols-12 md:p-6"
                    >
                        <div class="min-w-0 md:col-span-5">
                            <p
                                class="text-[11px] font-bold uppercase tracking-[0.16em] text-accent-700"
                            >
                                Outlet Resmi
                            </p>
                            <h3 class="mt-1 font-heading text-2xl font-extrabold">
                                {{ storeName }}
                            </h3>

                            <div class="mt-4 space-y-3 text-sm">
                                <div class="flex items-start gap-3">
                                    <MapPin
                                        class="mt-0.5 h-5 w-5 shrink-0 text-accent-700"
                                        aria-hidden="true"
                                    />
                                    <div>
                                        <div class="font-bold">Alamat</div>
                                        <p class="mt-0.5 leading-snug text-slate-600">
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

                            <div class="mt-5 flex flex-wrap gap-2">
                                <a
                                    :href="whatsappUrl"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="inline-flex min-h-[44px] items-center justify-center gap-2 whitespace-nowrap rounded-pill bg-accent-500 px-4 text-sm font-bold text-navy-950 transition-colors hover:bg-accent-600 focus-visible:outline-none"
                                >
                                    <WhatsAppIcon class="h-4 w-4" />
                                    Hubungi WhatsApp
                                </a>
                                <a
                                    href="https://www.google.com/maps/place/Andik+Laundry/@-7.2392419,112.581764,21z/data=!4m12!1m5!3m4!2zN8KwMTQnMjEuMyJTIDExMsKwMzQnNTQuMyJF!8m2!3d-7.2392469!4d112.5817531!3m5!1s0x2e7800f99a230c6b:0xb287a654290078e9!8m2!3d-7.239213!4d112.5817581!16s%2Fg%2F11gdlxnlmk?hl=id-ID"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="inline-flex min-h-[44px] items-center justify-center gap-2 whitespace-nowrap rounded-pill border border-navy-900/20 px-4 text-sm font-bold text-navy-950 transition-colors hover:bg-accent-100 focus-visible:outline-none"
                                >
                                    <ExternalLink class="h-4 w-4" aria-hidden="true" />
                                    Buka di Google Maps
                                </a>
                            </div>
                        </div>

                        <div class="min-w-0 md:col-span-7">
                            <div
                                class="h-[240px] overflow-hidden rounded-[18px] border border-border md:h-[290px]"
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
            <section id="faq" class="scroll-mt-11 py-10 md:py-12">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="landing-reveal mx-auto max-w-3xl text-center">
                        <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-accent-700">
                            Tanya Jawab
                        </p>
                        <h2
                            class="mt-1.5 text-balance font-heading text-2xl font-extrabold uppercase leading-tight tracking-tight text-navy-950 sm:text-3xl md:text-[2rem]"
                        >
                            Pertanyaan Umum
                        </h2>
                        <p class="mx-auto mt-2.5 max-w-2xl text-sm leading-relaxed text-slate-700">
                            Jawaban ringkas seputar pengerjaan, timbangan transparan, dan jadwal antar jemput.
                        </p>
                    </div>

                    <div class="mx-auto mt-6 max-w-3xl">
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
                                        class="flex min-h-[44px] w-full items-center justify-between gap-4 py-3 text-left font-heading text-sm font-bold text-navy-950 transition-colors hover:text-accent-700 focus-visible:outline-none sm:text-base"
                                        :aria-expanded="activeFaqIndex === index"
                                        :aria-controls="`faq-answer-${index}`"
                                        @click="toggleFaq(index)"
                                    >
                                        <span>{{ faq.q }}</span>
                                        <ChevronDown
                                            class="h-4 w-4 shrink-0 text-accent-700 transition-transform duration-300"
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
                                    class="pb-3.5 pr-6 text-sm leading-relaxed text-slate-700 sm:pr-10"
                                >
                                    {{ faq.a }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Final CTA -->
            <section class="py-10 md:py-12">
                <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
                    <div
                        class="landing-reveal rounded-[24px] bg-navy-950 px-6 py-10 text-center text-white shadow-lg md:px-10 md:py-12"
                    >
                        <h2
                            class="text-balance font-heading text-2xl font-extrabold uppercase leading-tight tracking-tight text-white sm:text-3xl md:text-[2rem]"
                        >
                            Punya Tumpukan Baju Kotor Hari Ini?
                        </h2>
                        <p class="mx-auto mt-3 max-w-xl text-sm leading-relaxed text-slate-300">
                            Biar kami yang bantu selesaikan. Pesan antar jemput praktis via WhatsApp tanpa repot,
                            atau antar langsung ke outlet kami kapan saja Anda sempat.
                        </p>
                        <div class="mt-6 flex flex-col justify-center gap-3 sm:flex-row">
                            <a
                                :href="whatsappUrl"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="inline-flex min-h-[46px] items-center justify-center gap-2 rounded-pill bg-accent-500 px-6 text-sm font-bold text-navy-950 transition-colors hover:bg-accent-600 focus-visible:outline-none"
                            >
                                <WhatsAppIcon class="h-4 w-4" />
                                Pesan via WhatsApp
                            </a>
                            <a
                                :href="phoneUrl"
                                class="inline-flex min-h-[46px] items-center justify-center gap-2 rounded-pill border border-white/25 px-6 text-sm font-bold text-white transition-colors hover:bg-white/10 focus-visible:outline-none"
                            >
                                <Phone class="h-4 w-4 text-accent-400" aria-hidden="true" />
                                {{ storePhoneLabel }}
                            </a>
                        </div>
                        <p class="mt-4 text-xs text-slate-400">Buka setiap hari, 07.00–20.00 WIB</p>
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
                            class="inline-flex min-h-[44px] items-center gap-3 rounded-lg focus-visible:outline-none"
                            aria-label="Andik Laundry, kembali ke beranda"
                        >
                            <img
                                src="/images/logo-a.webp"
                                alt=""
                                class="h-9 w-auto shrink-0 object-contain"
                                width="38"
                                height="36"
                                aria-hidden="true"
                            />
                            <span class="font-heading text-xl font-extrabold tracking-tight text-white">
                                {{ storeName }}
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
                                <a
                                    href="#tarif"
                                    class="flex min-h-[44px] items-center transition-colors hover:text-white"
                                    @click.prevent="smoothScrollTo('#tarif')"
                                >
                                    Cuci setrika
                                </a>
                            </li>
                            <li>
                                <a
                                    href="#tarif"
                                    class="flex min-h-[44px] items-center transition-colors hover:text-white"
                                    @click.prevent="smoothScrollTo('#tarif')"
                                >
                                    Cuci kering lipat
                                </a>
                            </li>
                            <li>
                                <a
                                    href="#tarif"
                                    class="flex min-h-[44px] items-center transition-colors hover:text-white"
                                    @click.prevent="smoothScrollTo('#tarif')"
                                >
                                    Cuci basah
                                </a>
                            </li>
                            <li>
                                <a
                                    href="#tarif"
                                    class="flex min-h-[44px] items-center transition-colors hover:text-white"
                                    @click.prevent="smoothScrollTo('#tarif')"
                                >
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
                                <a
                                    href="#lacak"
                                    class="flex min-h-[44px] items-center transition-colors hover:text-white"
                                    @click.prevent="smoothScrollTo('#lacak')"
                                >
                                    Cek status nota
                                </a>
                            </li>
                            <li>
                                <a
                                    href="#lokasi"
                                    class="flex min-h-[44px] items-center transition-colors hover:text-white"
                                    @click.prevent="smoothScrollTo('#lokasi')"
                                >
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

.hero-video-shell {
    -webkit-mask-image: linear-gradient(180deg, #000 0%, #000 58%, transparent 92%);
    mask-image: linear-gradient(180deg, #000 0%, #000 58%, transparent 92%);
}

@media (min-width: 768px) {
    .hero-video-shell {
        -webkit-mask-image: linear-gradient(180deg, #000 0%, #000 78%, transparent 94%);
        mask-image: linear-gradient(180deg, #000 0%, #000 78%, transparent 94%);
    }
}

.hero-scrim-mobile {
    background: linear-gradient(
        180deg,
        rgba(244, 248, 251, 0.12) 0%,
        rgba(244, 248, 251, 0) 20%,
        rgba(244, 248, 251, 0) 48%,
        rgba(244, 248, 251, 0.88) 68%,
        var(--color-bg) 88%
    );
}

.hero-scrim-desktop {
    background: linear-gradient(
        90deg,
        var(--color-bg) 0%,
        rgba(244, 248, 251, 0.96) 46%,
        rgba(244, 248, 251, 0.90) 54%,
        rgba(244, 248, 251, 0.32) 62%,
        rgba(244, 248, 251, 0.04) 69%,
        rgba(244, 248, 251, 0) 76%
    );
}

@media (min-width: 1024px) {
    .hero-scrim-desktop {
        background: linear-gradient(
            90deg,
            var(--color-bg) 0%,
            rgba(244, 248, 251, 0.96) 36%,
            rgba(244, 248, 251, 0.90) 45%,
            rgba(244, 248, 251, 0.30) 53%,
            rgba(244, 248, 251, 0.04) 60%,
            rgba(244, 248, 251, 0) 68%
        );
    }
}

@media (prefers-reduced-motion: reduce) {
    .roll > span {
        transition: none !important;
    }
    .nav-link:hover .roll > span,
    .nav-btn:hover .roll > span {
        transform: none !important;
    }
}
</style>
