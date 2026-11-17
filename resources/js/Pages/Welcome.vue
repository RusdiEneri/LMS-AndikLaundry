<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    ArrowRight,
    Calendar,
    Check,
    ChevronDown,
    Clock,
    ExternalLink,
    MapPin,
    Menu,
    MessageCircle,
    Phone,
    Search,
    ShieldCheck,
    Shirt,
    Sparkles,
    Star,
    Truck,
    X,
} from 'lucide-vue-next';
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue';

const props = defineProps<{
    canLogin?: boolean;
    stats?: { customers: number; orders: number; laundry_kg: number };
    store?: { name: string; phone: string; address: string };
}>();

const menuOpen = ref(false);
const activeFaqIndex = ref<number | null>(0);

function toggleFaq(index: number) {
    activeFaqIndex.value = activeFaqIndex.value === index ? null : index;
}

// Data Toko & Kontak
const storeName = computed(() => props.store?.name || 'Andik Laundry');
const storePhone = computed(() => props.store?.phone || '6281234567890');
const storeAddress = computed(() => props.store?.address || 'Hendrosari, RT.06/RW.03, Hendrosari, Kec. Menganti, Kabupaten Gresik, Jawa Timur 61174');

const whatsappUrl = computed(() => {
    const phone = storePhone.value.replace(/[^0-9]/g, '');
    const text = encodeURIComponent(`Halo ${storeName.value}, saya ingin bertanya tentang layanan laundry / pesan antar-jemput.`);
    return `https://wa.me/${phone}?text=${text}`;
});

function getServiceWhatsappUrl(serviceName: string) {
    const phone = storePhone.value.replace(/[^0-9]/g, '');
    const text = encodeURIComponent(`Halo ${storeName.value}, saya tertarik pesan layanan ${serviceName}. Mohon info penjemputan.`);
    return `https://wa.me/${phone}?text=${text}`;
}

// Cek Pesanan
const orderQuery = ref('');
const orderQueryError = ref('');
const isCheckingOrder = ref(false);
const orderQueryPattern = /^[A-Za-z0-9\-]{4,}$/;

function submitOrderCheck() {
    const query = orderQuery.value.trim();
    const isPhone = query.replace(/[^0-9]/g, '').length >= 8;

    if (query === '') {
        orderQueryError.value = 'Masukkan nomor nota atau nomor HP Anda.';
        return;
    }
    if (!isPhone && !orderQueryPattern.test(query)) {
        orderQueryError.value = 'Format tidak dikenali. Contoh: AL-20261115-001 atau 08123xxxxxxx.';
        return;
    }

    orderQueryError.value = '';
    isCheckingOrder.value = true;
    router.get('/nota', { q: query }, {
        onFinish: () => { isCheckingOrder.value = false; },
    });
}

// Statistik pelanggan
const stats = computed(() => props.stats ?? { customers: 1250, orders: 3400, laundry_kg: 8200 });

const animatedCustomers = ref(0);
const animatedOrders = ref(0);
const animatedLaundryKg = ref(0);
const animatedRating = ref(0);

const customersLabel = computed(() => `${Math.floor(animatedCustomers.value).toLocaleString('id-ID')}+`);
const ordersLabel = computed(() => `${Math.floor(animatedOrders.value).toLocaleString('id-ID')}+`);
const laundryKgLabel = computed(() => `${Math.floor(animatedLaundryKg.value).toLocaleString('id-ID')} kg`);
const ratingScore = computed(() => animatedRating.value.toLocaleString('id-ID', { minimumFractionDigits: 1, maximumFractionDigits: 1 }));

let counterRafId: number | null = null;

function startCounterAnimation() {
    if (typeof window === 'undefined') {
        return;
    }

    const targetCustomers = stats.value.customers;
    const targetOrders = stats.value.orders;
    const targetLaundryKg = stats.value.laundry_kg;
    const targetRating = 4.9;

    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        animatedCustomers.value = targetCustomers;
        animatedOrders.value = targetOrders;
        animatedLaundryKg.value = targetLaundryKg;
        animatedRating.value = targetRating;
        return;
    }

    const duration = 1800;
    const startTime = performance.now();
    const easeOutCubic = (t: number) => 1 - Math.pow(1 - t, 3);

    const step = (now: number) => {
        const elapsed = now - startTime;
        const progress = Math.min(elapsed / duration, 1);
        const ease = easeOutCubic(progress);

        animatedCustomers.value = targetCustomers * ease;
        animatedOrders.value = targetOrders * ease;
        animatedLaundryKg.value = targetLaundryKg * ease;
        animatedRating.value = targetRating * ease;

        if (progress < 1) {
            counterRafId = requestAnimationFrame(step);
        } else {
            animatedCustomers.value = targetCustomers;
            animatedOrders.value = targetOrders;
            animatedLaundryKg.value = targetLaundryKg;
            animatedRating.value = targetRating;
            counterRafId = null;
        }
    };

    counterRafId = requestAnimationFrame(step);
}

// Typewriter effect
const typewriterPlaceholder = ref('Nomor nota (AL-...) atau No. HP');
const isInputFocused = ref(false);
let typewriterTimer: ReturnType<typeof setTimeout> | null = null;

const typingPhrases = [
    'Nomor nota (AL-...) atau No. HP',
    'Contoh: AL-20261115-001',
    'Contoh: 08123456789',
    'Ketik nomor nota Anda…',
    'Ketik nomor WhatsApp Anda…',
];

let phraseIndex = 0;
let charIndex = typingPhrases[0].length;
let isDeleting = true;

function tickTypewriter() {
    if (isInputFocused.value) {
        typewriterPlaceholder.value = 'Nomor nota (AL-...) atau No. HP';
        return;
    }

    const currentPhrase = typingPhrases[phraseIndex];

    if (isDeleting) {
        charIndex = Math.max(0, charIndex - 1);
        typewriterPlaceholder.value = charIndex > 0 ? `${currentPhrase.substring(0, charIndex)}|` : '|';

        if (charIndex === 0) {
            isDeleting = false;
            phraseIndex = (phraseIndex + 1) % typingPhrases.length;
            typewriterTimer = setTimeout(tickTypewriter, 350);
            return;
        }
        typewriterTimer = setTimeout(tickTypewriter, 35);
    } else {
        charIndex = Math.min(currentPhrase.length, charIndex + 1);
        const isDone = charIndex === currentPhrase.length;
        typewriterPlaceholder.value = isDone ? currentPhrase : `${currentPhrase.substring(0, charIndex)}|`;

        if (isDone) {
            isDeleting = true;
            typewriterTimer = setTimeout(tickTypewriter, 2200);
            return;
        }
        typewriterTimer = setTimeout(tickTypewriter, 70);
    }
}

function startTypewriter() {
    if (typeof window === 'undefined') {
        return;
    }
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        typewriterPlaceholder.value = 'Nomor nota (AL-...) atau No. HP';
        return;
    }
    typewriterTimer = setTimeout(tickTypewriter, 2000);
}

function onInputFocus() {
    isInputFocused.value = true;
    if (typewriterTimer) {
        clearTimeout(typewriterTimer);
        typewriterTimer = null;
    }
    typewriterPlaceholder.value = 'Nomor nota (AL-...) atau No. HP';
}

function onInputBlur() {
    isInputFocused.value = false;
    if (!orderQuery.value) {
        typewriterTimer = setTimeout(tickTypewriter, 800);
    }
}

// 4 Layanan Utama POS
const services = [
    {
        id: 'cuci-setrika',
        name: 'Cuci Setrika',
        unit: 'kg',
        price: 'Rp 8.000',
        minWeight: 'Min. 2 kg',
        badge: 'Paling Favorit',
        featured: true,
        rating: '4.9',
        reviews: '520+ ulasan',
        desc: 'Layanan all-in-one paling diminati: dicuci higienis, dikeringkan mesin, disetrika uap boiler halus, dan dikemas wangi terlindung debu.',
        features: [
            '1 mesin 1 pelanggan (tidak dicampur)',
            'Setrika uap boiler profesional (anti-gosong)',
            'Pewangi grade premium tahan lama',
            'Kemasan plastik rapi kedap debu',
        ],
    },
    {
        id: 'cuci-kering',
        name: 'Cuci Kering',
        unit: 'kg',
        price: 'Rp 6.000',
        minWeight: 'Min. 2 kg',
        badge: 'Cepat & Rapi',
        featured: false,
        rating: '4.8',
        reviews: '310+ ulasan',
        desc: 'Dicuci higienis dan dikeringkan mesin pengering suhu terkontrol, bebas bau apek, disortir rapi dan siap dilipat.',
        features: [
            '1 mesin 1 pelanggan (tidak dicampur)',
            'Mesin pengering suhu stabil (anti-kusut)',
            'Kering sempurna tanpa bau apek',
            'Disortir dan dilipat rapi',
        ],
    },
    {
        id: 'cuci-basah',
        name: 'Cuci Basah',
        unit: 'kg',
        price: 'Rp 5.000',
        minWeight: 'Min. 2 kg',
        badge: 'Ekonomis',
        featured: false,
        rating: '4.8',
        reviews: '190+ ulasan',
        desc: 'Pakaian dicuci bersih dan dibilas higienis dengan sabun premium, siap dijemur mandiri di rumah dengan wangi segar.',
        features: [
            '1 mesin 1 pelanggan (tidak dicampur)',
            'Deterjen khusus ramah serat kain',
            'Pembersihan noda awal kerah & manset',
            'Bilas bersih tanpa sisa residu sabun',
        ],
    },
    {
        id: 'cuci-karpet',
        name: 'Cuci Karpet',
        unit: 'm²',
        price: 'Rp 15.000',
        minWeight: 'Hitung P × L',
        badge: 'Deep Clean',
        featured: false,
        rating: '4.9',
        reviews: '140+ ulasan',
        desc: 'Pembersihan karpet rumah, kantor, atau masjid dengan hydro-cleaning untuk mengangkat debu membandel dan tungau hingga tuntas.',
        features: [
            'Pembersihan serat karpet mendalam',
            'Angkat debu, tungau, dan kuman',
            'Pengerolan & pengeringan steril',
            'Kemasan plastik khusus pelindung karpet',
        ],
    },
];

const faqs = [
    {
        q: 'Berapa minimal berat cucian kiloan?',
        a: 'Batas minimal layanan kiloan adalah 2 kg. Apabila berat cucian yang ditimbang kurang dari 2 kg (misalnya 1,2 kg), sistem otomatis menghitung total biaya setara batas minimal 2 kg demi menjaga standar 1 mesin 1 pelanggan.',
    },
    {
        q: 'Apakah pakaian saya dicampur dengan pelanggan lain?',
        a: 'Sama sekali tidak. Komitmen utama Andik Laundry sejak 2009 adalah 1 mesin khusus untuk 1 pelanggan pada setiap proses pencucian. Pakaian Anda dijamin tidak tercampur, lebih higienis, dan terhindar dari risiko tertukar.',
    },
    {
        q: 'Berapa lama waktu pengerjaan cucian?',
        a: 'Untuk layanan Reguler memerlukan waktu 2–3 hari kerja. Kami juga menyediakan opsi Ekspres (selesai di hari yang sama: taruh pagi ambil sore) dengan tambahan biaya hanya Rp 2.000 / kg.',
    },
    {
        q: 'Apakah ada layanan antar jemput cucian?',
        a: 'Ya, kami melayani antar jemput cucian. Gratis ongkir untuk cucian maksimal 3 kg di area sekitar Hendrosari & Menganti. Cukup hubungi admin kami melalui WhatsApp.',
    },
    {
        q: 'Bagaimana cara melacak progres cucian saya?',
        a: 'Begitu pesanan Anda diinput di kasir, sistem mengirimkan nota digital langsung ke WhatsApp Anda. Anda cukup mengklik tautan nota tersebut atau memasukkan nomor nota di kolom Cek Pesanan di website ini tanpa perlu repot login.',
    },
    {
        q: 'Metode pembayaran apa saja yang diterima?',
        a: 'Kami menerima pembayaran Tunai (Cash) di kasir toko dan Non-Tunai QRIS resmi (Dana, Mandiri, BCA, GoPay, OVO, ShopeePay, dan semua mobile banking berlogo QRIS). Bisa bayar di muka atau saat pakaian diambil.',
    },
];

const isScrolled = ref(false);
function handleScroll() {
    if (typeof window !== 'undefined') {
        isScrolled.value = window.scrollY > 20;
    }
}

let revealObserver: IntersectionObserver | null = null;
function setupRevealObserver() {
    if (typeof window === 'undefined') return;

    if (!('IntersectionObserver' in window)) {
        document.querySelectorAll('.landing-reveal, .landing-reveal-left, .landing-reveal-right').forEach((el) => {
            el.classList.add('is-visible');
        });
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
            rootMargin: '0px 0px -40px 0px',
            threshold: 0.08,
        }
    );

    document.querySelectorAll('.landing-reveal, .landing-reveal-left, .landing-reveal-right').forEach((el) => {
        revealObserver?.observe(el);
    });
}

onMounted(() => {
    startCounterAnimation();
    startTypewriter();
    window.addEventListener('scroll', handleScroll, { passive: true });
    nextTick(() => {
        setupRevealObserver();
    });
});

onBeforeUnmount(() => {
    if (counterRafId !== null) {
        cancelAnimationFrame(counterRafId);
        counterRafId = null;
    }
    if (typewriterTimer !== null) {
        clearTimeout(typewriterTimer);
        typewriterTimer = null;
    }
    window.removeEventListener('scroll', handleScroll);
    revealObserver?.disconnect();
});
</script>

<template>
    <Head title="Andik Laundry | Bersih, Wangi & Dirawat Teliti Tanpa Dicampur">
        <meta
            name="description"
            content="Layanan binatu modern kiloan dan karpet terpercaya sejak 2009 di Gresik. 1 mesin 1 pelanggan, nota digital WhatsApp, pelacakan live, pembayaran QRIS, dan gratis antar jemput."
        />
    </Head>

    <div class="min-h-screen bg-[#F6F4EF] text-white font-sans antialiased selection:bg-[#FF8A5B] selection:text-[#10194D]">
        <!-- Skip link a11y -->
        <a
            href="#main-content"
            class="sr-only focus:not-sr-only focus:fixed focus:top-4 focus:left-4 focus:z-50 focus:px-4 focus:py-2 focus:bg-[#FF8A5B] focus:text-[#10194D] focus:font-bold focus:rounded-full focus:shadow-lg focus:outline-none"
        >
            Lewati ke konten utama
        </a>

        <!-- ======================================================== -->
        <!-- HEADER / NAVBAR FLOATING PILL (DESIGN.md)               -->
        <!-- ======================================================== -->
        <header class="fixed top-3 sm:top-4 left-0 right-0 z-50 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto pointer-events-none transition-all duration-300">
            <div
                :class="[
                    'pointer-events-auto backdrop-blur-md text-[#10194D] rounded-full px-5 sm:px-7 py-3 border transition-all duration-300 flex items-center justify-between',
                    isScrolled
                        ? 'bg-white/98 shadow-[0_12px_32px_rgba(16,25,77,0.16)] border-[#E3E1DA]'
                        : 'bg-white/95 shadow-[0_8px_24px_rgba(16,25,77,0.08)] border-[#E3E1DA]/80'
                ]"
            >
                <!-- Logo -->
                <Link href="/" class="flex items-center gap-1.5 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#4F5FE8] rounded-lg">
                    <span class="font-heading font-extrabold text-2xl tracking-tight text-[#10194D]">
                        andik<span class="text-[#FF8A5B]">.</span>
                    </span>
                    <span class="text-[10px] font-bold tracking-widest text-[#4F5FE8] uppercase bg-[#E6E9FD] px-2 py-0.5 rounded-full ml-1">
                        LAUNDRY
                    </span>
                </Link>

                <!-- Desktop Navigation Links -->
                <nav class="hidden lg:flex items-center gap-7 text-[14px] font-medium text-[#10194D]" aria-label="Navigasi Utama">
                    <a href="#keunggulan" class="hover:text-[#4F5FE8] transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#4F5FE8] rounded-md px-1 py-0.5">Keunggulan</a>
                    <a href="#tarif" class="hover:text-[#4F5FE8] transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#4F5FE8] rounded-md px-1 py-0.5">Layanan & Tarif</a>
                    <a href="#lacak" class="hover:text-[#4F5FE8] transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#4F5FE8] rounded-md px-1 py-0.5">Lacak Nota</a>
                    <a href="#lokasi" class="hover:text-[#4F5FE8] transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#4F5FE8] rounded-md px-1 py-0.5">Lokasi</a>
                    <a href="#faq" class="hover:text-[#4F5FE8] transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#4F5FE8] rounded-md px-1 py-0.5">FAQ</a>
                </nav>

                <!-- Right Action Buttons -->
                <div class="hidden sm:flex items-center gap-3">
                    <a
                        :href="whatsappUrl"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="bg-[#FF8A5B] hover:bg-[#F26B3A] text-[#10194D] font-bold text-xs sm:text-sm px-4 sm:px-5 py-2.5 rounded-full transition-all duration-200 shadow-sm hover:shadow-[0_4px_14px_rgba(255,138,91,0.4)] hover:-translate-y-0.5 flex items-center gap-2 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#10194D]"
                    >
                        <MessageCircle class="w-4 h-4 text-[#10194D]" />
                        <span>Pesan Antar Jemput</span>
                    </a>

                    <Link
                        v-if="canLogin"
                        href="/login"
                        class="text-[#10194D] hover:bg-[#E6E9FD] hover:text-[#4F5FE8] font-bold text-xs sm:text-sm px-4 py-2.5 rounded-full border border-[#E3E1DA] transition-all duration-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#4F5FE8]"
                    >
                        Masuk Staf
                    </Link>
                </div>

                <!-- Mobile Menu Toggle -->
                <button
                    type="button"
                    class="lg:hidden p-2 rounded-full text-[#10194D] hover:bg-gray-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#4F5FE8]"
                    :aria-expanded="menuOpen"
                    aria-label="Buka menu navigasi"
                    @click="menuOpen = !menuOpen"
                >
                    <Menu v-if="!menuOpen" class="w-6 h-6" />
                    <X v-else class="w-6 h-6" />
                </button>
            </div>

            <!-- Mobile Navigation Drawer -->
            <div
                v-if="menuOpen"
                class="pointer-events-auto lg:hidden mt-2 bg-white text-[#10194D] rounded-[24px] p-5 shadow-2xl border border-[#E3E1DA] flex flex-col gap-3 animate-fadeIn"
            >
                <a href="#keunggulan" class="px-3 py-2 font-medium hover:text-[#4F5FE8] rounded-lg" @click="menuOpen = false">Keunggulan</a>
                <a href="#tarif" class="px-3 py-2 font-medium hover:text-[#4F5FE8] rounded-lg" @click="menuOpen = false">Layanan & Tarif</a>
                <a href="#lacak" class="px-3 py-2 font-medium hover:text-[#4F5FE8] rounded-lg" @click="menuOpen = false">Lacak Nota</a>
                <a href="#lokasi" class="px-3 py-2 font-medium hover:text-[#4F5FE8] rounded-lg" @click="menuOpen = false">Lokasi</a>
                <a href="#faq" class="px-3 py-2 font-medium hover:text-[#4F5FE8] rounded-lg" @click="menuOpen = false">FAQ</a>
                <hr class="border-[#E3E1DA]" />
                <div class="flex flex-col gap-2 pt-1">
                    <a
                        :href="whatsappUrl"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="bg-[#FF8A5B] text-[#10194D] font-bold text-center py-2.5 rounded-full flex items-center justify-center gap-2"
                        @click="menuOpen = false"
                    >
                        <MessageCircle class="w-4 h-4" />
                        <span>Pesan Antar Jemput</span>
                    </a>
                    <Link
                        v-if="canLogin"
                        href="/login"
                        class="text-center font-bold py-2 rounded-full border border-[#E3E1DA]"
                        @click="menuOpen = false"
                    >
                        Masuk Staf
                    </Link>
                </div>
            </div>
        </header>

        <!-- ======================================================== -->
        <!-- MAIN CONTENT                                            -->
        <!-- ======================================================== -->
        <main id="main-content" class="overflow-hidden bg-[#10194D]">
            <!-- ======================================================== -->
            <!-- HERO SECTION: WARM CREAM HERO (Every Sunday Style)        -->
            <!-- ======================================================== -->
            <section class="relative bg-[#F6F4EF] pt-24 sm:pt-28 md:pt-32 pb-20 md:pb-24 px-4 sm:px-6 lg:px-8 text-[#10194D] rounded-b-[40px] md:rounded-b-[60px] shadow-xl">
                <!-- Decorative subtle road curve in background -->
                <div class="absolute inset-0 pointer-events-none opacity-40 overflow-hidden">
                    <svg class="w-full h-full object-cover" viewBox="0 0 1440 800" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M-100 500 C 300 350, 700 700, 1540 400" stroke="#E3E1DA" stroke-width="120" stroke-linecap="round" opacity="0.6"/>
                        <path d="M-100 500 C 300 350, 700 700, 1540 400" stroke="#FFFFFF" stroke-width="6" stroke-dasharray="24 24" stroke-linecap="round"/>
                    </svg>
                </div>

                <div class="max-w-7xl mx-auto relative z-10 grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">
                    <!-- Left Hero Content -->
                    <div class="lg:col-span-7 flex flex-col items-start pt-4 sm:pt-6">
                        <!-- Sub-heading Tag / Overline -->
                        <div class="animate-fade-slide-up inline-flex items-center gap-2 bg-[#E6E9FD] text-[#26338A] px-3.5 py-1.5 rounded-full text-xs font-bold uppercase tracking-wider mb-5 shadow-xs">
                            <Sparkles class="w-4 h-4 text-[#4F5FE8] animate-pulse-subtle" />
                            <span>SEJAK 2009 • 1 MESIN 1 PELANGGAN</span>
                        </div>

                        <!-- Main Display Heading (Every Sunday Style) -->
                        <h1 class="animate-fade-slide-up delay-100 font-heading font-extrabold text-4xl sm:text-5xl lg:text-6xl tracking-tight leading-[1.08] text-[#10194D] uppercase">
                            CUCI BERSIH,<br />
                            <span class="text-[#4F5FE8]">WANGI & RAPI!</span>
                            <span class="inline-block ml-3 text-3xl sm:text-4xl">🧺</span>
                        </h1>

                        <!-- Subtitle with high-contrast Slate-700 readability -->
                        <p class="animate-fade-slide-up delay-200 font-sans text-base sm:text-lg text-slate-700 mt-5 max-w-xl leading-relaxed font-medium">
                            Layanan binatu modern kiloan & karpet terpercaya di Hendrosari, Menganti, Gresik. Pakaian tidak pernah dicampur, timbangan jujur, gratis antar-jemput maksimal 3 kg, dan pantau nota digital langsung dari WhatsApp.
                        </p>

                        <!-- Search Pill (Lacak Order) - Every Sunday Style -->
                        <div class="animate-fade-slide-up delay-300 w-full max-w-xl mt-8">
                            <form
                                @submit.prevent="submitOrderCheck"
                                class="bg-white rounded-full p-2 pl-5 sm:pl-6 shadow-[0_12px_32px_rgba(16,25,77,0.12)] border border-[#E3E1DA] flex items-center justify-between gap-2 transition-all focus-within:ring-3 focus-within:ring-[#4F5FE8]/40"
                            >
                                <div class="flex items-center gap-3 flex-1 min-w-0">
                                    <Search class="w-5 h-5 text-[#4F5FE8] shrink-0" />
                                    <input
                                        v-model="orderQuery"
                                        type="text"
                                        :placeholder="typewriterPlaceholder"
                                        class="w-full bg-transparent text-[#10194D] text-sm sm:text-base font-semibold placeholder-slate-400 focus:outline-none"
                                        aria-label="Nomor nota laundry atau nomor WhatsApp"
                                        @focus="onInputFocus"
                                        @blur="onInputBlur"
                                    />
                                </div>
                                <button
                                    type="submit"
                                    :disabled="isCheckingOrder"
                                    class="bg-[#FF8A5B] hover:bg-[#F26B3A] text-[#10194D] font-bold text-xs sm:text-sm px-5 sm:px-7 py-3 rounded-full transition-all duration-200 shrink-0 shadow-sm hover:shadow-[0_4px_14px_rgba(255,138,91,0.4)] disabled:opacity-50 flex items-center gap-2"
                                >
                                    <span v-if="!isCheckingOrder">Cek Pesanan</span>
                                    <span v-else>Memeriksa…</span>
                                    <ArrowRight v-if="!isCheckingOrder" class="w-4 h-4" />
                                </button>
                            </form>

                            <!-- Error Message -->
                            <p v-if="orderQueryError" class="text-xs text-[#E5484D] font-bold mt-2 pl-4" role="alert">
                                {{ orderQueryError }}
                            </p>
                        </div>

                        <!-- Micro Social Proof Badges with High Contrast -->
                        <div class="animate-fade-slide-up delay-400 flex flex-wrap items-center gap-4 sm:gap-6 mt-8 pt-4 border-t border-slate-300/80 w-full max-w-xl">
                            <div class="flex items-center gap-2.5">
                                <div class="flex -space-x-2">
                                    <div class="w-8 h-8 rounded-full bg-[#4F5FE8] text-white font-extrabold text-xs flex items-center justify-center border-2 border-white shadow-xs">A</div>
                                    <div class="w-8 h-8 rounded-full bg-[#FF8A5B] text-[#10194D] font-extrabold text-xs flex items-center justify-center border-2 border-white shadow-xs">N</div>
                                    <div class="w-8 h-8 rounded-full bg-[#22A06B] text-white font-extrabold text-xs flex items-center justify-center border-2 border-white shadow-xs">D</div>
                                </div>
                                <div class="text-xs">
                                    <div class="font-extrabold text-[#10194D] flex items-center gap-1">
                                        <Star class="w-3.5 h-3.5 fill-[#F5A623] text-[#F5A623]" />
                                        <span>{{ ratingScore }} / 5.0 Rating</span>
                                    </div>
                                    <div class="text-slate-600 font-semibold">{{ customersLabel }} Pelanggan Puas</div>
                                </div>
                            </div>

                            <div class="h-6 w-[1px] bg-slate-300 hidden sm:block"></div>

                            <div class="text-xs">
                                <div class="font-extrabold text-[#10194D]">{{ ordersLabel }} Nota Dikerjakan</div>
                                <div class="text-slate-600 font-semibold">Standar 1 Mesin 1 Pelanggan</div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Hero Illustration: Playful Laundry Delivery Van (Every Sunday Style) -->
                    <div class="lg:col-span-5 flex justify-center items-center relative">
                        <div class="relative w-full max-w-md lg:max-w-none animate-float-slow">
                            <!-- SVG Laundry Delivery Van & Happy Characters -->
                            <svg class="w-full h-auto drop-shadow-2xl" viewBox="0 0 540 440" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <defs>
                                    <linearGradient id="skyGrad" x1="0" y1="0" x2="0" y2="1">
                                        <stop offset="0%" stop-color="#E6E9FD"/>
                                        <stop offset="100%" stop-color="#FFFFFF" stop-opacity="0"/>
                                    </linearGradient>
                                    <linearGradient id="vanGrad" x1="0" y1="0" x2="1" y2="1">
                                        <stop offset="0%" stop-color="#4F5FE8"/>
                                        <stop offset="100%" stop-color="#26338A"/>
                                    </linearGradient>
                                    <linearGradient id="basketGrad" x1="0" y1="0" x2="1" y2="1">
                                        <stop offset="0%" stop-color="#FF8A5B"/>
                                        <stop offset="100%" stop-color="#F26B3A"/>
                                    </linearGradient>
                                    <linearGradient id="bubbleGrad" x1="0" y1="0" x2="1" y2="1">
                                        <stop offset="0%" stop-color="#FFFFFF" stop-opacity="0.9"/>
                                        <stop offset="100%" stop-color="#6577F0" stop-opacity="0.3"/>
                                    </linearGradient>
                                </defs>

                                <!-- Circular soft backdrop glow -->
                                <circle cx="270" cy="220" r="180" fill="url(#skyGrad)"/>

                                <!-- Road track -->
                                <path d="M40 370 C 180 340, 360 380, 500 350" stroke="#10194D" stroke-width="46" stroke-linecap="round"/>
                                <path d="M60 370 C 180 340, 360 380, 480 350" stroke="#FFE14D" stroke-width="5" stroke-dasharray="16 14" stroke-linecap="round"/>

                                <!-- Decorative Floating Soap Bubbles -->
                                <circle cx="90" cy="180" r="22" fill="url(#bubbleGrad)" stroke="#FFFFFF" stroke-width="2" class="animate-bubble-1" />
                                <circle cx="130" cy="140" r="14" fill="url(#bubbleGrad)" stroke="#FFFFFF" stroke-width="1.5" class="animate-bubble-2" />
                                <circle cx="440" cy="120" r="26" fill="url(#bubbleGrad)" stroke="#FFFFFF" stroke-width="2" class="animate-bubble-1" />
                                <circle cx="480" cy="170" r="16" fill="url(#bubbleGrad)" stroke="#FFFFFF" stroke-width="1.5" class="animate-bubble-2" />
                                <circle cx="410" cy="90" r="12" fill="url(#bubbleGrad)" stroke="#FFFFFF" stroke-width="1" class="animate-bubble-1" />

                                <!-- Sparkles -->
                                <path d="M100 110 Q 100 125, 115 125 Q 100 125, 100 140 Q 100 125, 85 125 Q 100 125, 100 110 Z" fill="#FFE14D" class="animate-pulse-subtle" />
                                <path d="M460 70 Q 460 82, 472 82 Q 460 82, 460 94 Q 460 82, 448 82 Q 460 82, 460 70 Z" fill="#FFE14D" class="animate-pulse-subtle delay-200" />

                                <!-- Laundry Car / Van Body -->
                                <g transform="translate(100, 140)">
                                    <!-- Roof Luggage Rack with Fresh Laundry Baskets -->
                                    <rect x="50" y="25" width="180" height="8" rx="4" fill="#10194D"/>
                                    <line x1="75" y1="33" x2="75" y2="40" stroke="#10194D" stroke-width="4"/>
                                    <line x1="205" y1="33" x2="205" y2="40" stroke="#10194D" stroke-width="4"/>

                                    <!-- Laundry Basket 1 (Orange) on roof -->
                                    <rect x="60" y="-15" width="70" height="40" rx="8" fill="url(#basketGrad)"/>
                                    <!-- Folded Towels overflowing -->
                                    <path d="M65 -15 Q 75 -28, 85 -15" stroke="#FFFFFF" stroke-width="8" stroke-linecap="round"/>
                                    <path d="M85 -15 Q 95 -28, 105 -15" stroke="#FFE14D" stroke-width="8" stroke-linecap="round"/>
                                    <path d="M105 -15 Q 115 -28, 125 -15" stroke="#38BDF8" stroke-width="8" stroke-linecap="round"/>

                                    <!-- Laundry Basket 2 (White/Clean) on roof -->
                                    <rect x="140" y="-8" width="75" height="33" rx="8" fill="#FFFFFF" stroke="#E3E1DA" stroke-width="2"/>
                                    <path d="M148 -8 Q 160 -22, 172 -8" stroke="#FF8A5B" stroke-width="8" stroke-linecap="round"/>
                                    <path d="M172 -8 Q 185 -22, 198 -8" stroke="#6577F0" stroke-width="8" stroke-linecap="round"/>

                                    <!-- Cheerful Smiley Mascot on roof basket -->
                                    <g class="animate-float-gentle">
                                        <circle cx="230" cy="5" r="18" fill="#FFE14D"/>
                                        <!-- eyes & smile -->
                                        <circle cx="225" cy="2" r="2.5" fill="#10194D"/>
                                        <circle cx="235" cy="2" r="2.5" fill="#10194D"/>
                                        <path d="M225 9 Q 230 15, 235 9" stroke="#10194D" stroke-width="2.5" stroke-linecap="round" fill="none"/>
                                    </g>

                                    <!-- Main Van Cabin -->
                                    <path d="M40 90 L 60 40 Q 65 35, 75 35 L 210 35 Q 220 35, 230 48 L 270 95 L 285 105 Q 295 115, 295 125 L 295 180 Q 295 190, 285 190 L 30 190 Q 20 190, 20 180 L 20 115 Q 20 95, 40 90 Z" fill="url(#vanGrad)"/>

                                    <!-- Front Hood Highlight -->
                                    <path d="M270 95 L 285 105 L 295 125 L 225 125 Z" fill="#6577F0" opacity="0.4"/>

                                    <!-- Windows (Cab & Driver) -->
                                    <path d="M68 45 L 140 45 L 140 90 L 52 90 Z" fill="#FFFFFF" opacity="0.9" rx="4"/>
                                    <path d="M152 45 L 215 45 L 255 90 L 152 90 Z" fill="#FFFFFF" opacity="0.9" rx="4"/>

                                    <!-- Happy Driver in front window -->
                                    <circle cx="185" cy="65" r="14" fill="#FF8A5B"/>
                                    <circle cx="182" cy="63" r="2" fill="#10194D"/>
                                    <circle cx="189" cy="63" r="2" fill="#10194D"/>
                                    <path d="M182 69 Q 186 73, 190 69" stroke="#10194D" stroke-width="1.8" stroke-linecap="round" fill="none"/>
                                    <path d="M175 56 Q 185 50, 195 56" fill="#10194D"/> <!-- cap -->

                                    <!-- Van Door Line & Handle -->
                                    <line x1="145" y1="45" x2="145" y2="185" stroke="#1A2566" stroke-width="3"/>
                                    <rect x="155" y="105" width="16" height="5" rx="2.5" fill="#FFE14D"/>

                                    <!-- Van Brand Wordmark on Side -->
                                    <rect x="42" y="108" width="90" height="34" rx="8" fill="#FFFFFF"/>
                                    <text x="50" y="130" font-family="Lexend, sans-serif" font-weight="800" font-size="14" fill="#10194D">andik<tspan fill="#FF8A5B">.</tspan></text>
                                    <text x="96" y="125" font-family="Poppins, sans-serif" font-weight="700" font-size="7" fill="#4F5FE8">LAUNDRY</text>

                                    <!-- Front Bumper & Headlight -->
                                    <rect x="280" y="145" width="22" height="30" rx="6" fill="#FFE14D"/>
                                    <rect x="290" y="165" width="18" height="15" rx="4" fill="#10194D"/>

                                    <!-- Left Wheel -->
                                    <g transform="translate(65, 185)">
                                        <circle cx="0" cy="0" r="30" fill="#0A1033"/>
                                        <circle cx="0" cy="0" r="16" fill="#E3E1DA"/>
                                        <circle cx="0" cy="0" r="6" fill="#4F5FE8"/>
                                    </g>

                                    <!-- Right Wheel -->
                                    <g transform="translate(245, 185)">
                                        <circle cx="0" cy="0" r="30" fill="#0A1033"/>
                                        <circle cx="0" cy="0" r="16" fill="#E3E1DA"/>
                                        <circle cx="0" cy="0" r="6" fill="#4F5FE8"/>
                                    </g>
                                </g>
                            </svg>
                        </div>
                    </div>
                </div>
            </section>

            <!-- ======================================================== -->
            <!-- SECTION 01: LAYANAN UTAMA KAMI (OUR SERVICES)            -->
            <!-- ======================================================== -->
            <section id="keunggulan" class="relative bg-[#10194D] pt-20 pb-24 px-4 sm:px-6 lg:px-8 text-white">
                <div class="max-w-7xl mx-auto">
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 items-center">
                        <!-- Left 3 Feature Cards (Every Sunday Reference Layout) -->
                        <div class="lg:col-span-8 grid grid-cols-1 md:grid-cols-3 gap-5">
                            <!-- Card 1: Highlight Periwinkle Card -->
                            <div class="landing-reveal bg-gradient-to-br from-[#6577F0] to-[#4F5FE8] text-white rounded-[24px] p-6 shadow-xl relative overflow-hidden transition-all duration-300 hover:-translate-y-1.5 flex flex-col justify-between">
                                <div>
                                    <div class="w-12 h-12 rounded-2xl bg-white/20 backdrop-blur-md flex items-center justify-center mb-5">
                                        <ShieldCheck class="w-6 h-6 text-white" />
                                    </div>
                                    <h3 class="font-heading font-bold text-lg mb-2">1 Mesin 1 Pelanggan</h3>
                                    <p class="font-sans text-xs sm:text-sm text-white/90 leading-relaxed">
                                        Pakaian Anda tidak pernah dicampur dengan orang lain. 100% higienis, bersih suci, dan aman tanpa risiko tertukar.
                                    </p>
                                </div>
                                <div class="mt-6 flex items-center text-xs font-bold text-[#FFE14D] gap-1">
                                    <span>Standar Mutlak</span>
                                    <Check class="w-4 h-4" />
                                </div>
                            </div>

                            <!-- Card 2: Navy Outline Card -->
                            <div class="landing-reveal delay-150 bg-[#1A2566]/90 border border-white/20 text-white rounded-[24px] p-6 shadow-lg transition-all duration-300 hover:-translate-y-1.5 flex flex-col justify-between">
                                <div>
                                    <div class="w-12 h-12 rounded-2xl bg-white/10 flex items-center justify-center mb-5">
                                        <Sparkles class="w-6 h-6 text-[#FFE14D]" />
                                    </div>
                                    <h3 class="font-heading font-bold text-lg mb-2">Pewangi & Sabun Premium</h3>
                                    <p class="font-sans text-xs sm:text-sm text-slate-200 leading-relaxed">
                                        Deterjen ramah serat kain dipadukan setrika uap boiler profesional, pakaian rapi licin bebas noda gosong.
                                    </p>
                                </div>
                                <div class="mt-6 text-xs font-semibold text-[#FFE14D]">
                                    Aroma Tahan Lama
                                </div>
                            </div>

                            <!-- Card 3: Navy Outline Card -->
                            <div class="landing-reveal delay-300 bg-[#1A2566]/90 border border-white/20 text-white rounded-[24px] p-6 shadow-lg transition-all duration-300 hover:-translate-y-1.5 flex flex-col justify-between">
                                <div>
                                    <div class="w-12 h-12 rounded-2xl bg-white/10 flex items-center justify-center mb-5">
                                        <Truck class="w-6 h-6 text-[#FF8A5B]" />
                                    </div>
                                    <h3 class="font-heading font-bold text-lg mb-2">Free Ongkir Maks 3 kg</h3>
                                    <p class="font-sans text-xs sm:text-sm text-slate-200 leading-relaxed">
                                        Layanan antar jemput cucian praktis langsung ke depan pintu rumah Anda di area Hendrosari & sekitarnya.
                                    </p>
                                </div>
                                <div class="mt-6 text-xs font-semibold text-[#FF8A5B]">
                                    Antar Jemput Cepat
                                </div>
                            </div>
                        </div>

                        <!-- Right Section Header / Description -->
                        <div class="landing-reveal-right lg:col-span-4 flex flex-col items-start lg:pl-6">
                            <!-- Section Number Badge 01 -->
                            <div class="w-10 h-10 rounded-full border border-white/40 flex items-center justify-center font-heading font-extrabold text-sm text-white mb-4 bg-white/10">
                                01
                            </div>
                            <h2 class="font-heading font-extrabold text-3xl sm:text-4xl uppercase tracking-tight text-white leading-tight">
                                LAYANAN UTAMA KAMI
                            </h2>
                            <p class="font-sans text-sm text-slate-200 mt-4 leading-relaxed">
                                Sejak 2009, Andik Laundry berkomitmen memberikan pelayanan binatu paling rapi, jujur timbangannya, dan terjaga kebersihannya untuk warga Gresik.
                            </p>
                            <a
                                href="#tarif"
                                class="mt-6 bg-[#FF8A5B] hover:bg-[#F26B3A] text-[#10194D] font-bold text-sm px-6 py-3 rounded-full transition-all duration-200 shadow-sm hover:shadow-[0_4px_14px_rgba(255,138,91,0.4)] inline-flex items-center gap-2"
                            >
                                <span>Lihat Daftar Tarif</span>
                                <ArrowRight class="w-4 h-4" />
                            </a>
                        </div>
                    </div>
                </div>
            </section>

            <!-- ======================================================== -->
            <!-- SECTION 02: PAKET SPESIAL & TARIF (SPECIAL PACKAGE)      -->
            <!-- ======================================================== -->
            <section id="tarif" class="relative bg-[#0A1033] pt-20 pb-28 px-4 sm:px-6 lg:px-8 text-white">
                <div class="max-w-7xl mx-auto">
                    <!-- Section Header -->
                    <div class="landing-reveal flex flex-col md:flex-row md:items-end justify-between gap-6 mb-12">
                        <div>
                            <div class="w-10 h-10 rounded-full border border-white/40 flex items-center justify-center font-heading font-extrabold text-sm text-white mb-4 bg-white/10">
                                02
                            </div>
                            <h2 class="font-heading font-extrabold text-3xl sm:text-4xl uppercase tracking-tight text-white">
                                PAKET SPESIAL & TARIF
                            </h2>
                            <p class="font-sans text-sm sm:text-base text-slate-200 mt-2 max-w-xl">
                                Tarif transparan dan jujur. Minimal order kiloan 2 kg. Tersedia pengerjaan Reguler (2-3 hari) dan Ekspres (pagi antar, sore ambil).
                            </p>
                        </div>

                        <div class="flex items-center gap-3">
                            <a
                                :href="whatsappUrl"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="bg-[#FF8A5B] hover:bg-[#F26B3A] text-[#10194D] font-bold text-sm px-6 py-3 rounded-full transition-all duration-200 shadow-sm hover:shadow-[0_4px_14px_rgba(255,138,91,0.4)] flex items-center gap-2"
                            >
                                <MessageCircle class="w-4 h-4" />
                                <span>Pesan via WhatsApp</span>
                            </a>
                        </div>
                    </div>

                    <!-- Cards Grid (4 Core Services from Riil Catalog) -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                        <div
                            v-for="(service, sIdx) in services"
                            :key="service.id"
                            class="landing-reveal bg-white text-[#10194D] rounded-[28px] p-6 shadow-xl border border-slate-200 hover:shadow-[0_20px_48px_rgba(16,25,77,0.18)] transition-all duration-300 hover:-translate-y-2 flex flex-col justify-between relative group"
                            :style="{ transitionDelay: `${sIdx * 100}ms` }"
                        >
                            <!-- Top Info & Badges -->
                            <div>
                                <div class="flex items-center justify-between mb-4">
                                    <span
                                        class="text-[11px] font-extrabold uppercase px-3 py-1 rounded-full"
                                        :class="service.featured ? 'bg-[#FF8A5B] text-[#10194D]' : 'bg-[#E6E9FD] text-[#26338A]'"
                                    >
                                        {{ service.badge }}
                                    </span>
                                    <div class="flex items-center gap-1 text-xs font-bold text-[#10194D]">
                                        <Star class="w-3.5 h-3.5 fill-[#F5A623] text-[#F5A623]" />
                                        <span>{{ service.rating }}</span>
                                    </div>
                                </div>

                                <h3 class="font-heading font-extrabold text-xl text-[#10194D] mb-1">
                                    {{ service.name }}
                                </h3>
                                <p class="text-xs text-slate-500 font-semibold mb-4">
                                    {{ service.minWeight }}
                                </p>

                                <!-- Price Tag -->
                                <div class="bg-[#F8FAFC] rounded-[20px] p-4 mb-5 border border-slate-200">
                                    <div class="text-xs text-slate-500 font-medium">Tarif Mulai</div>
                                    <div class="flex items-baseline gap-1 mt-0.5">
                                        <span class="font-heading font-extrabold text-2xl sm:text-3xl text-[#10194D]">
                                            {{ service.price }}
                                        </span>
                                        <span class="text-xs font-bold text-slate-500">/ {{ service.unit }}</span>
                                    </div>
                                </div>

                                <p class="text-xs text-slate-600 leading-relaxed mb-5 font-medium">
                                    {{ service.desc }}
                                </p>

                                <!-- Feature Checklist -->
                                <ul class="space-y-2 text-xs text-slate-800 font-medium mb-6">
                                    <li v-for="(feat, fIdx) in service.features" :key="fIdx" class="flex items-start gap-2">
                                        <Check class="w-4 h-4 text-[#22A06B] shrink-0 mt-0.5" />
                                        <span>{{ feat }}</span>
                                    </li>
                                </ul>
                            </div>

                            <!-- Card Order CTA -->
                            <a
                                :href="getServiceWhatsappUrl(service.name)"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="w-full text-center py-3 rounded-full font-bold text-xs transition-all duration-200 flex items-center justify-center gap-2"
                                :class="service.featured
                                    ? 'bg-[#FF8A5B] hover:bg-[#F26B3A] text-[#10194D] shadow-sm'
                                    : 'bg-[#10194D] hover:bg-[#1A2566] text-white'"
                            >
                                <MessageCircle class="w-3.5 h-3.5" />
                                <span>Pesan {{ service.name }}</span>
                            </a>
                        </div>
                    </div>
                </div>
            </section>

            <!-- ======================================================== -->
            <!-- SECTION 03: FULL FEATURED BANNER & OVERLAPPING TICKET     -->
            <!-- ======================================================== -->
            <section class="relative bg-gradient-to-b from-[#0A1033] to-[#10194D] pt-12 pb-24 px-4 sm:px-6 lg:px-8 text-white">
                <div class="max-w-6xl mx-auto">
                    <!-- Big Curved Periwinkle Banner (Every Sunday Reference) -->
                    <div class="landing-reveal bg-gradient-to-r from-[#6577F0] to-[#4F5FE8] rounded-[36px] md:rounded-[44px] p-8 sm:p-12 md:p-16 text-white relative shadow-2xl overflow-hidden pb-32 md:pb-36">
                        <!-- Background Glow & Shapes -->
                        <div class="absolute -right-16 -top-16 w-80 h-80 rounded-full bg-white/10 blur-2xl pointer-events-none"></div>

                        <div class="relative z-10 max-w-2xl">
                            <div class="inline-flex items-center gap-2 bg-white/20 backdrop-blur-md px-3.5 py-1 rounded-full text-xs font-bold uppercase tracking-wider mb-4">
                                <span>03</span>
                                <span>•</span>
                                <span>KOMITMEN TERBAIK</span>
                            </div>
                            <h2 class="font-heading font-extrabold text-2xl sm:text-4xl md:text-5xl uppercase tracking-tight leading-tight">
                                DIPERCAYA LEBIH DARI 15 TAHUN (SEJAK 2009)
                            </h2>
                            <p class="font-sans text-sm sm:text-base text-white/90 mt-4 leading-relaxed max-w-lg">
                                Dari cucian rumahan tetangga hingga ribuan keluarga di Gresik. Kami senantiasa menjaga kepercayaan Anda dengan kebersihan higienis, timbangan jujur, dan nota digital WhatsApp tanpa ribet.
                            </p>

                            <!-- Stat Pill Chips -->
                            <div class="flex flex-wrap gap-3 mt-6">
                                <span class="bg-white/20 backdrop-blur-md text-white text-xs font-bold px-4 py-2 rounded-full">
                                    {{ customersLabel }} Pelanggan Setia
                                </span>
                                <span class="bg-white/20 backdrop-blur-md text-white text-xs font-bold px-4 py-2 rounded-full">
                                    {{ ordersLabel }} Nota Dikerjakan
                                </span>
                                <span class="bg-white/20 backdrop-blur-md text-white text-xs font-bold px-4 py-2 rounded-full">
                                    {{ laundryKgLabel }} Pakaian Bersih
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Overlapping White Ticket Card (Every Sunday Signature) -->
                    <div class="landing-reveal delay-200 relative -mt-24 sm:-mt-28 mx-auto max-w-4xl z-20 px-2 sm:px-4">
                        <div class="bg-white text-[#10194D] rounded-[28px] sm:rounded-[32px] shadow-[0_20px_48px_rgba(16,25,77,0.22)] border border-slate-200 overflow-hidden flex flex-col md:flex-row items-stretch">
                            <!-- Left: Illustration Mini-car / Basket -->
                            <div class="md:w-5/12 bg-[#F6F4EF] p-6 sm:p-8 flex items-center justify-center border-b md:border-b-0 md:border-r border-slate-200">
                                <div class="text-center">
                                    <div class="w-24 h-24 mx-auto rounded-full bg-[#E6E9FD] flex items-center justify-center text-4xl mb-4 shadow-sm">
                                        👕
                                    </div>
                                    <div class="font-heading font-extrabold text-lg text-[#10194D]">Paket All-In-One</div>
                                    <p class="text-xs text-slate-600 mt-1 font-medium">Cuci + Kering Mesin + Setrika Uap</p>
                                    <span class="inline-block mt-3 bg-[#22A06B]/15 text-[#12724A] font-bold text-[11px] px-3 py-1 rounded-full">
                                        Higienis 1 Mesin 1 Pelanggan
                                    </span>
                                </div>
                            </div>

                            <!-- Center: Package Features & Details -->
                            <div class="md:w-6/12 p-6 sm:p-8 flex flex-col justify-between">
                                <div>
                                    <div class="text-[11px] font-bold tracking-wider text-[#4F5FE8] uppercase mb-1">
                                        PAKET PALING LARIS
                                    </div>
                                    <div class="flex items-baseline gap-2 mb-4">
                                        <span class="font-heading font-extrabold text-3xl sm:text-4xl text-[#F26B3A]">
                                            Rp 8.000
                                        </span>
                                        <span class="text-sm font-bold text-slate-500">/ kg</span>
                                    </div>

                                    <ul class="space-y-2.5 text-xs sm:text-sm text-slate-800 font-medium mb-6">
                                        <li class="flex items-center gap-2">
                                            <Check class="w-4 h-4 text-[#22A06B] shrink-0" />
                                            <span>1 Mesin 1 Pelanggan (Tidak pernah dicampur)</span>
                                        </li>
                                        <li class="flex items-center gap-2">
                                            <Check class="w-4 h-4 text-[#22A06B] shrink-0" />
                                            <span>Setrika Uap Boiler Rapi & Halus</span>
                                        </li>
                                        <li class="flex items-center gap-2">
                                            <Check class="w-4 h-4 text-[#22A06B] shrink-0" />
                                            <span>Pewangi Grade Premium Tahan Lama</span>
                                        </li>
                                        <li class="flex items-center gap-2">
                                            <Check class="w-4 h-4 text-[#22A06B] shrink-0" />
                                            <span>Free Ongkir Antar-Jemput (Maksimal 3 kg)</span>
                                        </li>
                                    </ul>
                                </div>

                                <a
                                    :href="getServiceWhatsappUrl('Cuci Setrika Rp 8.000/kg')"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="inline-flex items-center gap-2 text-sm font-bold text-[#4F5FE8] hover:text-[#26338A] transition-colors"
                                >
                                    <span>Pesan Paket Ini via WhatsApp</span>
                                    <ArrowRight class="w-4 h-4" />
                                </a>
                            </div>

                            <!-- Right: Signature Vertical Orange Ticket Stub (Every Sunday) -->
                            <div class="md:w-1/12 bg-[#FF8A5B] text-[#10194D] font-extrabold text-xs uppercase tracking-widest writing-vertical flex items-center justify-center py-4 px-3 select-none">
                                <span>ANDIK LAUNDRY • GRESIK</span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- ======================================================== -->
            <!-- SECTION 04: TIPS & ARTIKEL (ARTICLES)                    -->
            <!-- ======================================================== -->
            <section class="relative bg-[#10194D] pt-16 pb-24 px-4 sm:px-6 lg:px-8 text-white">
                <div class="max-w-7xl mx-auto">
                    <!-- Section Header -->
                    <div class="landing-reveal mb-10">
                        <div class="w-10 h-10 rounded-full border border-white/40 flex items-center justify-center font-heading font-extrabold text-sm text-white mb-4 bg-white/10">
                            04
                        </div>
                        <h2 class="font-heading font-extrabold text-3xl sm:text-4xl uppercase tracking-tight text-white">
                            TIPS & INFORMASI CUCIAN
                        </h2>
                        <p class="font-sans text-sm sm:text-base text-slate-200 mt-2">
                            Edukasi perawatan pakaian dan komitmen higienitas kami untuk kenyamanan keluarga Anda.
                        </p>
                    </div>

                    <!-- 2 Large Article Cards with Dark Gradient Overlay -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <!-- Article Card 1 -->
                        <div class="landing-reveal group relative rounded-[28px] overflow-hidden shadow-xl bg-[#1A2566] min-h-[340px] flex flex-col justify-end p-8 border border-white/10 transition-all duration-300 hover:-translate-y-1.5">
                            <!-- Background Pattern / Tint -->
                            <div class="absolute inset-0 bg-gradient-to-br from-[#4F5FE8]/40 via-[#10194D]/80 to-[#0A1033] z-10"></div>
                            <div class="absolute inset-0 bg-[radial-gradient(#FFE14D_1px,transparent_1px)] [background-size:16px_16px] opacity-20"></div>

                            <div class="relative z-20">
                                <span class="bg-[#FF8A5B] text-[#10194D] font-bold text-xs px-3 py-1 rounded-full uppercase tracking-wider mb-3 inline-block">
                                    Tips Perawatan
                                </span>
                                <h3 class="font-heading font-bold text-xl sm:text-2xl text-white group-hover:text-[#FFE14D] transition-colors leading-snug">
                                    Tips Merawat Baju & Sprei Agar Tidak Cepat Kusam dan Tetap Wangi Semerbak
                                </h3>
                                <p class="text-xs sm:text-sm text-slate-200 mt-2 line-clamp-2">
                                    Ketahui cara memisahkan pakaian warna pekat, penggunaan air bersih terfilter, dan teknik setrika uap yang aman bagi serat kain.
                                </p>
                                <div class="flex items-center gap-4 text-xs text-slate-300 mt-4 pt-4 border-t border-white/10">
                                    <span class="flex items-center gap-1.5"><Calendar class="w-3.5 h-3.5" /> 16 Nov 2026</span>
                                    <span>•</span>
                                    <span>Tim Andik Laundry</span>
                                </div>
                            </div>
                        </div>

                        <!-- Article Card 2 -->
                        <div class="landing-reveal delay-150 group relative rounded-[28px] overflow-hidden shadow-xl bg-[#1A2566] min-h-[340px] flex flex-col justify-end p-8 border border-white/10 transition-all duration-300 hover:-translate-y-1.5">
                            <!-- Background Pattern / Tint -->
                            <div class="absolute inset-0 bg-gradient-to-br from-[#6577F0]/40 via-[#10194D]/80 to-[#0A1033] z-10"></div>
                            <div class="absolute inset-0 bg-[radial-gradient(#4F5FE8_1px,transparent_1px)] [background-size:16px_16px] opacity-20"></div>

                            <div class="relative z-20">
                                <span class="bg-[#FFE14D] text-[#10194D] font-bold text-xs px-3 py-1 rounded-full uppercase tracking-wider mb-3 inline-block">
                                    Edukasi Higienis
                                </span>
                                <h3 class="font-heading font-bold text-xl sm:text-2xl text-white group-hover:text-[#FFE14D] transition-colors leading-snug">
                                    Mengapa Standar 1 Mesin 1 Pelanggan Sangat Penting Bagi Kesehatan & Kesucian?
                                </h3>
                                <p class="text-xs sm:text-sm text-slate-200 mt-2 line-clamp-2">
                                    Mencampur pakaian dengan orang tak dikenal berisiko memindahkan kuman dan melanggar kesucian pakaian ibadah. Inilah prinsip yang kami jaga sejak 2009.
                                </p>
                                <div class="flex items-center gap-4 text-xs text-slate-300 mt-4 pt-4 border-t border-white/10">
                                    <span class="flex items-center gap-1.5"><Calendar class="w-3.5 h-3.5" /> 15 Nov 2026</span>
                                    <span>•</span>
                                    <span>Tim Andik Laundry</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- ======================================================== -->
            <!-- SECTION 05: LOKASI & PETA OUTLET (GOOGLE MAPS RESMI)     -->
            <!-- ======================================================== -->
            <section id="lokasi" class="relative bg-[#0A1033] pt-16 pb-24 px-4 sm:px-6 lg:px-8 text-white">
                <div class="max-w-7xl mx-auto">
                    <div class="landing-reveal mb-10">
                        <div class="w-10 h-10 rounded-full border border-white/40 flex items-center justify-center font-heading font-extrabold text-sm text-white mb-4 bg-white/10">
                            05
                        </div>
                        <h2 class="font-heading font-extrabold text-3xl sm:text-4xl uppercase tracking-tight text-white">
                            KUNJUNGI OUTLET KAMI
                        </h2>
                        <p class="font-sans text-sm sm:text-base text-slate-200 mt-2">
                            Datang langsung untuk drop cucian atau gunakan layanan antar jemput kami.
                        </p>
                    </div>

                    <div class="landing-reveal bg-white text-[#10194D] rounded-[28px] sm:rounded-[36px] p-6 sm:p-8 shadow-2xl border border-slate-200 grid grid-cols-1 lg:grid-cols-12 gap-8 items-center">
                        <!-- Left Info Card -->
                        <div class="lg:col-span-5 flex flex-col justify-between h-full">
                            <div>
                                <span class="bg-[#E6E9FD] text-[#26338A] text-xs font-bold px-3 py-1 rounded-full uppercase tracking-wider">
                                    Outlet Resmi
                                </span>
                                <h3 class="font-heading font-extrabold text-2xl sm:text-3xl text-[#10194D] mt-3 mb-4">
                                    Andik Laundry
                                </h3>

                                <div class="space-y-4 text-xs sm:text-sm text-[#10194D]">
                                    <div class="flex items-start gap-3">
                                        <MapPin class="w-5 h-5 text-[#4F5FE8] shrink-0 mt-0.5" />
                                        <div>
                                            <div class="font-bold">Alamat Lengkap:</div>
                                            <p class="text-slate-600 font-medium leading-relaxed mt-0.5">
                                                Hendrosari, RT.06/RW.03, Hendrosari, Kec. Menganti, Kabupaten Gresik, Jawa Timur 61174
                                            </p>
                                        </div>
                                    </div>

                                    <div class="flex items-start gap-3">
                                        <Clock class="w-5 h-5 text-[#22A06B] shrink-0 mt-0.5" />
                                        <div>
                                            <div class="font-bold">Jam Operasional:</div>
                                            <p class="text-slate-600 font-medium mt-0.5">
                                                Buka Setiap Hari: 07.00 – 20.00 WIB
                                            </p>
                                        </div>
                                    </div>

                                    <div class="flex items-start gap-3">
                                        <Truck class="w-5 h-5 text-[#FF8A5B] shrink-0 mt-0.5" />
                                        <div>
                                            <div class="font-bold">Antar Jemput:</div>
                                            <p class="text-slate-600 font-medium mt-0.5">
                                                Gratis ongkir untuk cucian maksimal 3 kg di area sekitar.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="flex flex-col sm:flex-row gap-3 mt-8">
                                <a
                                    :href="whatsappUrl"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="bg-[#22A06B] hover:bg-[#12724A] text-white font-bold text-xs sm:text-sm px-5 py-3 rounded-full transition-all duration-200 flex items-center justify-center gap-2 shadow-sm"
                                >
                                    <MessageCircle class="w-4 h-4" />
                                    <span>Hubungi WhatsApp Toko</span>
                                </a>

                                <a
                                    href="https://www.google.com/maps/place/Andik+Laundry/@-7.2392419,112.581764,21z/data=!4m12!1m5!3m4!2zN8KwMTQnMjEuMyJTIDExMsKwMzQnNTQuMyJF!8m2!3d-7.2392469!4d112.5817531!3m5!1s0x2e7800f99a230c6b:0xb287a654290078e9!8m2!3d-7.239213!4d112.5817581!16s%2Fg%2F11gdlxnlmk?hl=id-ID"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="bg-[#10194D] hover:bg-[#1A2566] text-white font-bold text-xs sm:text-sm px-5 py-3 rounded-full transition-all duration-200 flex items-center justify-center gap-2"
                                >
                                    <ExternalLink class="w-4 h-4" />
                                    <span>Buka di Google Maps</span>
                                </a>
                            </div>
                        </div>

                        <!-- Right Google Maps Interactive Embed -->
                        <div class="lg:col-span-7 h-[360px] sm:h-[420px] rounded-[24px] overflow-hidden border border-slate-200 shadow-inner relative">
                            <iframe
                                title="Peta Lokasi Resmi Andik Laundry Gresik"
                                class="w-full h-full border-0"
                                loading="lazy"
                                allowfullscreen
                                referrerpolicy="no-referrer-when-downgrade"
                                src="https://maps.google.com/maps?q=Andik+Laundry,+Hendrosari,+Menganti,+Gresik&t=&z=18&ie=UTF8&iwloc=&output=embed"
                            ></iframe>
                        </div>
                    </div>
                </div>
            </section>

            <!-- ======================================================== -->
            <!-- SECTION 06: PERTANYAAN UMUM (FAQ)                        -->
            <!-- ======================================================== -->
            <section id="faq" class="relative bg-[#10194D] pt-16 pb-24 px-4 sm:px-6 lg:px-8 text-white">
                <div class="max-w-4xl mx-auto">
                    <div class="landing-reveal text-center mb-12">
                        <div class="w-10 h-10 rounded-full border border-white/40 mx-auto flex items-center justify-center font-heading font-extrabold text-sm text-white mb-4 bg-white/10">
                            06
                        </div>
                        <h2 class="font-heading font-extrabold text-3xl sm:text-4xl uppercase tracking-tight text-white">
                            PERTANYAAN YANG SERING DITANYAKAN
                        </h2>
                        <p class="font-sans text-sm sm:text-base text-slate-200 mt-2">
                            Punya pertanyaan lain seputar pencucian, timbangan, atau antar-jemput? Cek jawaban di bawah ini.
                        </p>
                    </div>

                    <!-- Accordion List -->
                    <div class="landing-reveal space-y-4">
                        <div
                            v-for="(faq, fIdx) in faqs"
                            :key="fIdx"
                            class="bg-[#1A2566]/80 border border-white/10 rounded-[20px] overflow-hidden transition-all duration-200"
                        >
                            <button
                                type="button"
                                class="w-full text-left px-6 py-5 flex items-center justify-between gap-4 font-heading font-bold text-base sm:text-lg text-white hover:text-[#FFE14D] transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#FFE14D]"
                                :aria-expanded="activeFaqIndex === fIdx"
                                @click="toggleFaq(fIdx)"
                            >
                                <span>{{ faq.q }}</span>
                                <ChevronDown
                                    class="w-5 h-5 text-[#FFE14D] shrink-0 transition-transform duration-300"
                                    :class="{ 'rotate-180': activeFaqIndex === fIdx }"
                                />
                            </button>

                            <div
                                v-show="activeFaqIndex === fIdx"
                                class="px-6 pb-6 text-xs sm:text-sm text-slate-200 leading-relaxed border-t border-white/5 pt-4 animate-fadeIn"
                            >
                                {{ faq.a }}
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- ======================================================== -->
            <!-- SECTION 07: CTA NEWSLETTER / KONSULTASI (Every Sunday)   -->
            <!-- ======================================================== -->
            <section id="lacak" class="relative bg-gradient-to-b from-[#10194D] via-[#0A1033] to-[#0A1033] pt-16 pb-24 px-4 sm:px-6 lg:px-8 text-white">
                <div class="landing-reveal max-w-4xl mx-auto text-center">
                    <!-- Heading Every Sunday Style with Yellow Highlight -->
                    <h2 class="font-heading font-extrabold text-3xl sm:text-5xl uppercase tracking-tight leading-tight text-white">
                        SIAP CUCIAN BERSIH TANPA REPOT? <br />
                        <span class="text-[#FFE14D]">PESAN SEKARANG</span> JUGA!
                    </h2>
                    <p class="font-sans text-sm sm:text-base text-slate-200 mt-4 max-w-xl mx-auto leading-relaxed">
                        Kirim pesan ke admin WhatsApp kami untuk penjemputan cucian gratis (maks 3 kg) atau lacak langsung progres nota Anda.
                    </p>

                    <!-- Large Pill Search / Action Form -->
                    <div class="max-w-xl mx-auto mt-8">
                        <form
                            @submit.prevent="submitOrderCheck"
                            class="bg-white rounded-full p-2 pl-6 shadow-2xl border border-slate-200 flex items-center justify-between gap-2 transition-all focus-within:ring-3 focus-within:ring-[#4F5FE8]/40"
                        >
                            <input
                                v-model="orderQuery"
                                type="text"
                                placeholder="Masukkan nomor nota atau No. HP…"
                                class="w-full bg-transparent text-[#10194D] text-sm sm:text-base font-semibold placeholder-slate-400 focus:outline-none"
                            />
                            <button
                                type="submit"
                                :disabled="isCheckingOrder"
                                class="bg-[#FF8A5B] hover:bg-[#F26B3A] text-[#10194D] font-bold text-xs sm:text-sm px-6 py-3.5 rounded-full transition-all duration-200 shrink-0 shadow-sm hover:shadow-[0_4px_14px_rgba(255,138,91,0.4)] disabled:opacity-50 flex items-center gap-2"
                            >
                                <span v-if="!isCheckingOrder">Lacak Nota</span>
                                <span v-else>Memeriksa…</span>
                                <ArrowRight v-if="!isCheckingOrder" class="w-4 h-4" />
                            </button>
                        </form>
                    </div>

                    <!-- Direct WhatsApp Quick Call Button -->
                    <div class="mt-8 flex justify-center">
                        <a
                            :href="whatsappUrl"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="inline-flex items-center gap-2.5 bg-transparent border-2 border-white/60 hover:border-white text-white font-bold text-sm px-6 py-3 rounded-full transition-all hover:bg-white/10"
                        >
                            <Phone class="w-4 h-4 text-[#FFE14D]" />
                            <span>Hubungi Admin: {{ storePhone }}</span>
                        </a>
                    </div>
                </div>
            </section>
        </main>

        <!-- ======================================================== -->
        <!-- FOOTER (DESIGN.md)                                       -->
        <!-- ======================================================== -->
        <footer class="bg-[#070B24] text-slate-300 border-t border-white/10 pt-16 pb-12 px-4 sm:px-6 lg:px-8">
            <div class="max-w-7xl mx-auto">
                <div class="grid grid-cols-1 md:grid-cols-12 gap-10 pb-12 border-b border-white/10">
                    <!-- Brand Column -->
                    <div class="md:col-span-5">
                        <Link href="/" class="flex items-center gap-1.5 focus-visible:outline-none">
                            <span class="font-heading font-extrabold text-2xl tracking-tight text-white">
                                andik<span class="text-[#FF8A5B]">.</span>
                            </span>
                            <span class="text-[10px] font-bold tracking-widest text-[#4F5FE8] uppercase bg-[#E6E9FD] px-2 py-0.5 rounded-full ml-1">
                                LAUNDRY
                            </span>
                        </Link>
                        <p class="font-sans text-xs sm:text-sm text-slate-300 mt-4 max-w-sm leading-relaxed font-normal">
                            Layanan binatu harian higienis dan terpercaya di Gresik sejak 2009. Berprinsip 1 mesin 1 pelanggan, menjaga kesucian dan kebersihan pakaian keluarga Anda.
                        </p>
                        <div class="text-xs text-[#FFE14D] font-bold mt-4">
                            Buka Setiap Hari: 07.00 – 20.00 WIB
                        </div>
                    </div>

                    <!-- Links: Layanan -->
                    <div class="md:col-span-3">
                        <div class="font-heading font-bold text-sm text-white uppercase tracking-wider mb-4">
                            Layanan Unggulan
                        </div>
                        <ul class="space-y-2.5 text-xs text-slate-300">
                            <li><a href="#tarif" class="hover:text-white transition-colors">Cuci Setrika Komplit (Rp 8.000/kg)</a></li>
                            <li><a href="#tarif" class="hover:text-white transition-colors">Cuci Kering Lipat (Rp 6.000/kg)</a></li>
                            <li><a href="#tarif" class="hover:text-white transition-colors">Cuci Basah Ekonomis (Rp 5.000/kg)</a></li>
                            <li><a href="#tarif" class="hover:text-white transition-colors">Cuci Karpet Rumah & Masjid (Rp 15.000/m²)</a></li>
                        </ul>
                    </div>

                    <!-- Links: Navigasi & Staf -->
                    <div class="md:col-span-4">
                        <div class="font-heading font-bold text-sm text-white uppercase tracking-wider mb-4">
                            Akses & Bantuan
                        </div>
                        <ul class="space-y-2.5 text-xs text-slate-300 mb-6">
                            <li><a href="#lacak" class="hover:text-white transition-colors">Cek Status Nota Digital</a></li>
                            <li><a href="#lokasi" class="hover:text-white transition-colors">Petunjuk Arah Google Maps</a></li>
                            <li><a :href="whatsappUrl" target="_blank" rel="noopener noreferrer" class="hover:text-white transition-colors">Chat Admin WhatsApp</a></li>
                            <li><Link v-if="canLogin" href="/login" class="hover:text-white transition-colors font-bold text-[#FF8A5B]">Masuk Dashboard Kasir / Owner</Link></li>
                        </ul>
                    </div>
                </div>

                <!-- Copyright -->
                <div class="pt-8 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-slate-400">
                    <div>
                        © 2009 – 2026 Andik Laundry (Hendrosari, Menganti, Gresik). Seluruh hak cipta dilindungi.
                    </div>
                    <div class="flex items-center gap-4">
                        <span>Standar Higienis 1 Mesin 1 Pelanggan</span>
                    </div>
                </div>
            </div>
        </footer>
    </div>
</template>

<style scoped>
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(-6px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.animate-fadeIn {
    animation: fadeIn 0.2s ease-out forwards;
}
</style>
