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
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';

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

watch(menuOpen, (open) => {
    if (typeof document !== 'undefined') {
        if (open) {
            document.body.style.overflow = 'hidden';
        } else {
            document.body.style.overflow = '';
        }
    }
});

function handleKeydown(e: KeyboardEvent) {
    if (e.key === 'Escape' && menuOpen.value) {
        menuOpen.value = false;
    }
}

function closeMenuAndNavigate() {
    menuOpen.value = false;
    if (typeof document !== 'undefined') {
        document.body.style.overflow = '';
    }
}

onMounted(() => {
    startCounterAnimation();
    startTypewriter();
    window.addEventListener('scroll', handleScroll, { passive: true });
    window.addEventListener('keydown', handleKeydown);
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
    window.removeEventListener('keydown', handleKeydown);
    if (typeof document !== 'undefined') {
        document.body.style.overflow = '';
    }
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

    <div class="min-h-screen bg-bg text-white font-sans antialiased selection:bg-accent-500 selection:text-navy-900">
        <!-- Skip link a11y -->
        <a
            href="#main-content"
            class="sr-only focus:not-sr-only focus:fixed focus:top-4 focus:left-4 focus:z-50 focus:px-4 focus:py-2 focus:bg-accent-500 focus:text-navy-900 focus:font-bold focus:rounded-pill focus:shadow-lg focus:outline-none"
        >
            Lewati ke konten utama
        </a>

        <!-- ======================================================== -->
        <!-- HEADER / NAVBAR FLOATING PILL (DESIGN.md)               -->
        <!-- ======================================================== -->
        <header class="fixed top-3 sm:top-4 left-0 right-0 z-50 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto pointer-events-none">
            <div
                :class="[
                    'pointer-events-auto backdrop-blur-md text-navy-900 rounded-pill px-5 sm:px-7 py-3 border transition-all duration-300 flex items-center justify-between',
                    isScrolled
                        ? 'bg-white/98 shadow-[0_12px_32px_rgba(16,25,77,0.16)] border-border'
                        : 'bg-white/95 shadow-[0_8px_24px_rgba(16,25,77,0.08)] border-border/80'
                ]"
            >
                <!-- Logo -->
                <Link href="/" class="flex items-center gap-1.5 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 rounded-lg">
                    <span class="font-heading font-extrabold text-2xl tracking-tight text-navy-900">
                        andik<span class="text-accent-500">.</span>
                    </span>
                    <span class="text-[10px] font-bold tracking-widest text-primary-600 uppercase bg-primary-100 px-2 py-0.5 rounded-pill ml-1">
                        LAUNDRY
                    </span>
                </Link>

                <!-- Desktop Navigation Links -->
                <nav class="hidden lg:flex items-center gap-7 text-[14px] font-medium text-navy-900" aria-label="Navigasi Utama">
                    <a href="#keunggulan" class="hover:text-primary-600 transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 rounded-md px-1 py-0.5">Keunggulan</a>
                    <a href="#tarif" class="hover:text-primary-600 transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 rounded-md px-1 py-0.5">Layanan & Tarif</a>
                    <a href="#lacak" class="hover:text-primary-600 transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 rounded-md px-1 py-0.5">Lacak Nota</a>
                    <a href="#lokasi" class="hover:text-primary-600 transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 rounded-md px-1 py-0.5">Lokasi</a>
                    <a href="#faq" class="hover:text-primary-600 transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 rounded-md px-1 py-0.5">FAQ</a>
                </nav>

                <!-- Right Action Buttons -->
                <div class="hidden sm:flex items-center gap-3">
                    <a
                        :href="whatsappUrl"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="bg-accent-500 hover:bg-accent-600 text-navy-900 font-bold text-xs sm:text-sm px-4 sm:px-5 py-2.5 rounded-pill transition-all duration-200 shadow-sm hover:shadow-md hover:-translate-y-0.5 flex items-center gap-2 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500"
                    >
                        <MessageCircle class="w-4 h-4 text-navy-900" />
                        <span>Pesan Antar Jemput</span>
                    </a>

                    <Link
                        v-if="canLogin"
                        href="/login"
                        class="text-navy-900 hover:bg-primary-100 hover:text-primary-600 font-bold text-xs sm:text-sm px-4 py-2.5 rounded-pill border border-border transition-all duration-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500"
                    >
                        Masuk Staf
                    </Link>
                </div>

                <!-- Mobile Menu Toggle -->
                <button
                    type="button"
                    class="lg:hidden p-2 rounded-pill text-navy-900 hover:bg-gray-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500"
                    :aria-expanded="menuOpen"
                    aria-label="Buka menu navigasi"
                    @click="menuOpen = !menuOpen"
                >
                    <Menu v-if="!menuOpen" class="w-6 h-6" />
                    <X v-else class="w-6 h-6" />
                </button>
            </div>
        </header>

        <!-- Mobile Navigation Drawer Overlay (Teleported to body for zero layout shift) -->
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
                    class="fixed inset-0 bg-navy-950/60 backdrop-blur-sm z-50 lg:hidden"
                    @click="menuOpen = false"
                    aria-hidden="true"
                ></div>
            </Transition>

            <!-- Floating Drawer Card -->
            <Transition
                enter-active-class="transition-all duration-300 cubic-bezier(0.16, 1, 0.3, 1)"
                enter-from-class="opacity-0 -translate-y-4 scale-95"
                enter-to-class="opacity-100 translate-y-0 scale-100"
                leave-active-class="transition-all duration-200 ease-in"
                leave-from-class="opacity-100 translate-y-0 scale-100"
                leave-to-class="opacity-0 -translate-y-4 scale-95"
            >
                <div
                    v-if="menuOpen"
                    class="fixed top-18 left-4 right-4 z-50 lg:hidden bg-white text-navy-900 rounded-[24px] p-5 shadow-[0_20px_48px_rgba(16,25,77,0.28)] border border-border flex flex-col gap-3 max-h-[calc(100dvh-90px)] overflow-y-auto"
                >
                    <div class="flex flex-col gap-1">
                        <a
                            href="#keunggulan"
                            class="px-4 py-3 font-semibold text-base hover:bg-primary-100 hover:text-primary-600 rounded-xl transition-colors flex items-center justify-between min-h-[44px]"
                            @click="closeMenuAndNavigate"
                        >
                            <span>Keunggulan Layanan</span>
                            <ArrowRight class="w-4 h-4 text-slate-400" />
                        </a>
                        <a
                            href="#tarif"
                            class="px-4 py-3 font-semibold text-base hover:bg-primary-100 hover:text-primary-600 rounded-xl transition-colors flex items-center justify-between min-h-[44px]"
                            @click="closeMenuAndNavigate"
                        >
                            <span>Daftar Layanan & Tarif</span>
                            <ArrowRight class="w-4 h-4 text-slate-400" />
                        </a>
                        <a
                            href="#lacak"
                            class="px-4 py-3 font-semibold text-base hover:bg-primary-100 hover:text-primary-600 rounded-xl transition-colors flex items-center justify-between min-h-[44px]"
                            @click="closeMenuAndNavigate"
                        >
                            <span>Cek Status / Lacak Nota</span>
                            <ArrowRight class="w-4 h-4 text-slate-400" />
                        </a>
                        <a
                            href="#lokasi"
                            class="px-4 py-3 font-semibold text-base hover:bg-primary-100 hover:text-primary-600 rounded-xl transition-colors flex items-center justify-between min-h-[44px]"
                            @click="closeMenuAndNavigate"
                        >
                            <span>Lokasi & Peta Outlet</span>
                            <ArrowRight class="w-4 h-4 text-slate-400" />
                        </a>
                        <a
                            href="#faq"
                            class="px-4 py-3 font-semibold text-base hover:bg-primary-100 hover:text-primary-600 rounded-xl transition-colors flex items-center justify-between min-h-[44px]"
                            @click="closeMenuAndNavigate"
                        >
                            <span>Pertanyaan Umum (FAQ)</span>
                            <ArrowRight class="w-4 h-4 text-slate-400" />
                        </a>
                    </div>

                    <hr class="border-border" />

                    <div class="flex flex-col gap-2.5 pt-1">
                        <a
                            :href="whatsappUrl"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="bg-accent-500 hover:bg-accent-600 text-navy-900 font-bold text-sm text-center py-3 px-5 rounded-pill flex items-center justify-center gap-2 shadow-sm transition-all min-h-[44px]"
                            @click="closeMenuAndNavigate"
                        >
                            <MessageCircle class="w-4 h-4" />
                            <span>Pesan Antar Jemput via WA</span>
                        </a>
                        <Link
                            v-if="canLogin"
                            href="/login"
                            class="text-center font-bold text-sm py-3 px-5 rounded-pill border border-border hover:bg-primary-100 hover:text-primary-600 transition-all min-h-[44px] flex items-center justify-center"
                            @click="closeMenuAndNavigate"
                        >
                            Masuk Dashboard Staf
                        </Link>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <!-- ======================================================== -->
        <!-- MAIN CONTENT                                            -->
        <!-- ======================================================== -->
        <main id="main-content" class="overflow-hidden bg-navy-900">
            <!-- ======================================================== -->
            <!-- HERO SECTION: WARM CREAM HERO (Every Sunday Style)        -->
            <!-- ======================================================== -->
            <section class="relative bg-bg pt-24 sm:pt-28 md:pt-32 pb-20 md:pb-24 px-4 sm:px-6 lg:px-8 text-navy-900 rounded-b-[40px] md:rounded-b-[60px] shadow-xl">
                <!-- Decorative subtle road curve in background -->
                <div class="absolute inset-0 pointer-events-none opacity-40 overflow-hidden">
                    <svg class="w-full h-full object-cover" viewBox="0 0 1440 800" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M-100 500 C 300 350, 700 700, 1540 400" stroke="#E0E8F0" stroke-width="120" stroke-linecap="round" opacity="0.6"/>
                        <path d="M-100 500 C 300 350, 700 700, 1540 400" stroke="#FFFFFF" stroke-width="6" stroke-dasharray="24 24" stroke-linecap="round"/>
                    </svg>
                </div>

                <div class="max-w-7xl mx-auto relative z-10 grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">
                    <!-- Left Hero Content -->
                    <div class="lg:col-span-7 flex flex-col items-start pt-4 sm:pt-6">
                        <!-- Sub-heading Tag / Overline -->
                        <div class="animate-fade-slide-up inline-flex items-center gap-2 bg-primary-100 text-primary-700 px-3.5 py-1.5 rounded-pill text-xs font-bold uppercase tracking-wider mb-5 shadow-xs">
                            <Sparkles class="w-4 h-4 text-primary-600 animate-pulse-subtle" />
                            <span>SEJAK 2009 • 1 MESIN 1 PELANGGAN</span>
                        </div>

                        <!-- Main Display Heading (Every Sunday Style) -->
                        <h1 class="animate-fade-slide-up delay-100 font-heading font-extrabold text-4xl sm:text-5xl lg:text-6xl tracking-tight leading-[1.08] text-navy-900 uppercase">
                            CUCI BERSIH,<br />
                            <span class="text-primary-600">WANGI & RAPI!</span>
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
                                class="bg-white rounded-pill p-2 pl-5 sm:pl-6 shadow-[0_12px_32px_rgba(16,25,77,0.12)] border border-border flex items-center justify-between gap-2 transition-all focus-within:ring-3 focus-within:ring-primary-500/40"
                            >
                                <div class="flex items-center gap-3 flex-1 min-w-0">
                                    <Search class="w-5 h-5 text-primary-600 shrink-0" />
                                    <input
                                        v-model="orderQuery"
                                        type="text"
                                        :placeholder="typewriterPlaceholder"
                                        class="w-full bg-transparent text-navy-900 text-sm sm:text-base font-semibold placeholder-slate-400 focus:outline-none"
                                        aria-label="Nomor nota laundry atau nomor WhatsApp"
                                        @focus="onInputFocus"
                                        @blur="onInputBlur"
                                    />
                                </div>
                                <button
                                    type="submit"
                                    :disabled="isCheckingOrder"
                                    class="bg-accent-500 hover:bg-accent-600 text-navy-900 font-bold text-xs sm:text-sm px-5 sm:px-7 py-3 rounded-pill transition-all duration-200 shrink-0 shadow-sm hover:shadow-md disabled:opacity-50 flex items-center gap-2"
                                >
                                    <span v-if="!isCheckingOrder">Cek Pesanan</span>
                                    <span v-else>Memeriksa…</span>
                                    <ArrowRight v-if="!isCheckingOrder" class="w-4 h-4" />
                                </button>
                            </form>

                            <!-- Error Message -->
                            <p v-if="orderQueryError" class="text-xs text-danger font-bold mt-2 pl-4" role="alert">
                                {{ orderQueryError }}
                            </p>
                        </div>

                        <!-- Micro Social Proof Badges with High Contrast -->
                        <div class="animate-fade-slide-up delay-400 flex flex-wrap items-center gap-4 sm:gap-6 mt-8 pt-4 border-t border-slate-300/80 w-full max-w-xl">
                            <div class="flex items-center gap-2.5">
                                <div class="flex -space-x-2">
                                    <div class="w-8 h-8 rounded-pill bg-primary-600 text-white font-extrabold text-xs flex items-center justify-center border-2 border-white shadow-xs">A</div>
                                    <div class="w-8 h-8 rounded-pill bg-accent-500 text-navy-900 font-extrabold text-xs flex items-center justify-center border-2 border-white shadow-xs">N</div>
                                    <div class="w-8 h-8 rounded-pill bg-success text-white font-extrabold text-xs flex items-center justify-center border-2 border-white shadow-xs">D</div>
                                </div>
                                <div class="text-xs">
                                    <div class="font-extrabold text-navy-900 flex items-center gap-1">
                                        <Star class="w-3.5 h-3.5 fill-warning text-warning" />
                                        <span>{{ ratingScore }} / 5.0 Rating</span>
                                    </div>
                                    <div class="text-slate-600 font-semibold">{{ customersLabel }} Pelanggan Puas</div>
                                </div>
                            </div>

                            <div class="h-6 w-[1px] bg-slate-300 hidden sm:block"></div>

                            <div class="text-xs">
                                <div class="font-extrabold text-navy-900">{{ ordersLabel }} Nota Dikerjakan</div>
                                <div class="text-slate-600 font-semibold">Standar 1 Mesin 1 Pelanggan</div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Hero Illustration: Animated Smart Washing Machine (Front-Load Laundry Cycle) -->
                    <div class="lg:col-span-5 flex justify-center items-center relative">
                        <div class="relative w-full max-w-[420px] lg:max-w-none">
                            <!-- Floating Top-Right Badge -->
                            <div class="absolute -top-3 -right-2 sm:right-2 bg-white/95 backdrop-blur-md rounded-pill px-3.5 py-1.5 shadow-[0_8px_20px_rgba(16,25,77,0.12)] border border-border flex items-center gap-2 z-20 animate-float-slow">
                                <span class="relative flex h-2.5 w-2.5">
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-pill bg-success opacity-75"></span>
                                    <span class="relative inline-flex rounded-pill h-2.5 w-2.5 bg-success"></span>
                                </span>
                                <span class="text-xs font-bold text-navy-900">1 Mesin 1 Pelanggan</span>
                            </div>

                            <!-- Floating Bottom-Left Badge -->
                            <div class="absolute -bottom-3 -left-2 sm:left-2 bg-white/95 backdrop-blur-md rounded-pill px-3.5 py-1.5 shadow-[0_8px_20px_rgba(16,25,77,0.12)] border border-border flex items-center gap-2 z-20 animate-float-slow delay-300">
                                <Sparkles class="w-4 h-4 text-warning" />
                                <span class="text-xs font-bold text-navy-900">Busa Melimpah & Wangi</span>
                            </div>

                            <!-- SVG Washing Machine Animation -->
                            <svg class="w-full h-auto drop-shadow-2xl select-none" viewBox="0 0 520 540" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <defs>
                                    <!-- Machine Chassis Gradient -->
                                    <linearGradient id="wmBodyGrad" x1="0" y1="0" x2="1" y2="1">
                                        <stop offset="0%" stop-color="#FFFFFF"/>
                                        <stop offset="60%" stop-color="#F8FAFC"/>
                                        <stop offset="100%" stop-color="#EBF1F6"/>
                                    </linearGradient>

                                    <!-- Top Panel Shading -->
                                    <linearGradient id="wmPanelGrad" x1="0" y1="0" x2="0" y2="1">
                                        <stop offset="0%" stop-color="#FFFFFF"/>
                                        <stop offset="100%" stop-color="#F1F5F9"/>
                                    </linearGradient>

                                    <!-- Ambient Background Aura Glow -->
                                    <radialGradient id="wmAura" cx="50%" cy="50%" r="50%">
                                        <stop offset="0%" stop-color="#D8EDF8" stop-opacity="0.85"/>
                                        <stop offset="70%" stop-color="#D8EDF8" stop-opacity="0.3"/>
                                        <stop offset="100%" stop-color="#FFFFFF" stop-opacity="0"/>
                                    </radialGradient>

                                    <!-- Outer Door Chrome Bezel Gradient -->
                                    <linearGradient id="wmDoorOuter" x1="0" y1="0" x2="1" y2="1">
                                        <stop offset="0%" stop-color="#FFFFFF"/>
                                        <stop offset="25%" stop-color="#E2E8F0"/>
                                        <stop offset="50%" stop-color="#CBD5E1"/>
                                        <stop offset="75%" stop-color="#E2E8F0"/>
                                        <stop offset="100%" stop-color="#94A3B8"/>
                                    </linearGradient>

                                    <!-- Inner Door Ring -->
                                    <linearGradient id="wmDoorInner" x1="0" y1="0" x2="1" y2="1">
                                        <stop offset="0%" stop-color="#F1F5F9"/>
                                        <stop offset="50%" stop-color="#E2E8F0"/>
                                        <stop offset="100%" stop-color="#CBD5E1"/>
                                    </linearGradient>

                                    <!-- Deep Stainless Drum Cavity Gradient -->
                                    <radialGradient id="wmDrumCavity" cx="45%" cy="45%" r="55%">
                                        <stop offset="0%" stop-color="#1E293B"/>
                                        <stop offset="70%" stop-color="#0F172A"/>
                                        <stop offset="100%" stop-color="#081050"/>
                                    </radialGradient>

                                    <!-- Water Pool Gradient -->
                                    <linearGradient id="wmWaterGrad" x1="0" y1="0" x2="0" y2="1">
                                        <stop offset="0%" stop-color="#38BDF8" stop-opacity="0.75"/>
                                        <stop offset="50%" stop-color="#1F7FC4" stop-opacity="0.85"/>
                                        <stop offset="100%" stop-color="#1565A0" stop-opacity="0.95"/>
                                    </linearGradient>

                                    <!-- Clothes: Orange Towel Gradient (Matches Reference Image) -->
                                    <linearGradient id="wmOrangeTowel" x1="0" y1="0" x2="1" y2="1">
                                        <stop offset="0%" stop-color="#FBBF24"/>
                                        <stop offset="50%" stop-color="#F59E0B"/>
                                        <stop offset="100%" stop-color="#D97706"/>
                                    </linearGradient>

                                    <!-- Clothes: Denim Blue Laundry Fabric -->
                                    <linearGradient id="wmBlueShirt" x1="0" y1="0" x2="1" y2="1">
                                        <stop offset="0%" stop-color="#60A5FA"/>
                                        <stop offset="60%" stop-color="#1F7FC4"/>
                                        <stop offset="100%" stop-color="#13276F"/>
                                    </linearGradient>

                                    <!-- Clothes: Teal Brand Clean Towel -->
                                    <linearGradient id="wmTealCloth" x1="0" y1="0" x2="1" y2="1">
                                        <stop offset="0%" stop-color="#5EEAD4"/>
                                        <stop offset="60%" stop-color="#39C5A8"/>
                                        <stop offset="100%" stop-color="#2BA88E"/>
                                    </linearGradient>

                                    <!-- Soap Bubble Iridescent Gradient -->
                                    <linearGradient id="wmBubbleGrad" x1="0" y1="0" x2="1" y2="1">
                                        <stop offset="0%" stop-color="#FFFFFF" stop-opacity="0.9"/>
                                        <stop offset="50%" stop-color="#E0F2FE" stop-opacity="0.75"/>
                                        <stop offset="100%" stop-color="#38BDF8" stop-opacity="0.4"/>
                                    </linearGradient>

                                    <!-- Rotary Knob Gradient -->
                                    <linearGradient id="wmKnobGrad" x1="0" y1="0" x2="1" y2="1">
                                        <stop offset="0%" stop-color="#FFFFFF"/>
                                        <stop offset="50%" stop-color="#E2E8F0"/>
                                        <stop offset="100%" stop-color="#94A3B8"/>
                                    </linearGradient>

                                    <!-- Glass Door Clipping Mask -->
                                    <clipPath id="wmDrumClip">
                                        <circle cx="260" cy="285" r="102"/>
                                    </clipPath>
                                </defs>

                                <!-- Circular Ambient Backdrop Glow -->
                                <circle cx="260" cy="285" r="210" fill="url(#wmAura)"/>

                                <!-- Ambient Soap Bubbles Floating in Room Air -->
                                <circle cx="55" cy="210" r="18" fill="url(#wmBubbleGrad)" stroke="#FFFFFF" stroke-width="1.5" class="animate-soap-bubble-1" />
                                <circle cx="475" cy="180" r="24" fill="url(#wmBubbleGrad)" stroke="#FFFFFF" stroke-width="2" class="animate-soap-bubble-2" />
                                <circle cx="490" cy="290" r="14" fill="url(#wmBubbleGrad)" stroke="#FFFFFF" stroke-width="1.5" class="animate-soap-bubble-3" />
                                <circle cx="45" cy="350" r="15" fill="url(#wmBubbleGrad)" stroke="#FFFFFF" stroke-width="1.5" class="animate-soap-bubble-2" />
                                <circle cx="85" cy="120" r="10" fill="url(#wmBubbleGrad)" stroke="#FFFFFF" stroke-width="1" class="animate-soap-bubble-3" />

                                <!-- Ambient Sparkles -->
                                <path d="M 60 150 Q 60 162 72 162 Q 60 162 60 174 Q 60 162 48 162 Q 60 162 60 150 Z" fill="#F59E0B" class="animate-pulse-subtle" />
                                <path d="M 460 120 Q 460 132 472 132 Q 460 132 460 144 Q 460 132 448 132 Q 460 132 460 120 Z" fill="#39C5A8" class="animate-pulse-subtle delay-200" />
                                <path d="M 445 420 Q 445 430 455 430 Q 445 430 445 440 Q 445 430 435 430 Q 445 430 445 420 Z" fill="#F59E0B" class="animate-pulse-subtle delay-400" />

                                <!-- Floor Base Shadow -->
                                <ellipse cx="260" cy="505" rx="195" ry="18" fill="#081050" opacity="0.14" />
                                <ellipse cx="260" cy="505" rx="145" ry="10" fill="#081050" opacity="0.12" />

                                <!-- Whole Washing Machine Assembly (With active gentle vibration hum) -->
                                <g class="animate-machine-hum">
                                    <!-- Rubber Anti-Vibration Feet -->
                                    <rect x="105" y="475" width="46" height="20" rx="4" fill="#081050"/>
                                    <rect x="369" y="475" width="46" height="20" rx="4" fill="#081050"/>

                                    <!-- Main Cabinet Body -->
                                    <rect x="80" y="45" width="360" height="435" rx="26" fill="url(#wmBodyGrad)" stroke="#D8E2EC" stroke-width="3"/>

                                    <!-- Body Surface Bevel / Chamfer lines -->
                                    <line x1="82" y1="46" x2="438" y2="46" stroke="#FFFFFF" stroke-width="3"/>
                                    <line x1="80" y1="135" x2="440" y2="135" stroke="#E2E8F0" stroke-width="2"/>
                                    <line x1="80" y1="440" x2="440" y2="440" stroke="#CBD5E1" stroke-width="2"/>

                                    <!-- Bottom Maintenance Filter Door (Bottom-Left) -->
                                    <rect x="105" y="447" width="34" height="24" rx="4" fill="#F1F5F9" stroke="#CBD5E1" stroke-width="1.5"/>
                                    <line x1="117" y1="459" x2="127" y2="459" stroke="#94A3B8" stroke-width="2" stroke-linecap="round"/>

                                    <!-- TOP CONTROL FASCIA (y: 45 - 135) -->
                                    <!-- 1. Detergent Dispenser Drawer (Left) -->
                                    <rect x="96" y="58" width="94" height="66" rx="8" fill="#F8FAFC" stroke="#CBD5E1" stroke-width="1.5"/>
                                    <!-- Handle indent -->
                                    <rect x="106" y="72" width="74" height="15" rx="4" fill="#E2E8F0"/>
                                    <!-- Logo on drawer -->
                                    <text x="114" y="108" font-family="Lexend, sans-serif" font-weight="800" font-size="13" fill="#13276F">andik<tspan fill="#39C5A8">.</tspan></text>
                                    <text x="156" y="104" font-family="Poppins, sans-serif" font-weight="700" font-size="6" fill="#1F7FC4">PRO</text>

                                    <!-- 2. Digital LED Control Display (Center) -->
                                    <rect x="202" y="58" width="108" height="66" rx="8" fill="#081050" stroke="#13276F" stroke-width="2"/>
                                    <!-- Digital LED Glow Status -->
                                    <circle cx="215" cy="71" r="3" fill="#39C5A8" class="animate-led-live"/>
                                    <text x="223" y="74" font-family="Poppins, sans-serif" font-weight="700" font-size="7" fill="#39C5A8" letter-spacing="1">WASHING</text>
                                    <!-- Digital Timer Readout -->
                                    <text x="212" y="101" font-family="JetBrains Mono, monospace" font-weight="800" font-size="20" fill="#39C5A8" letter-spacing="0.5">38:40</text>
                                    <!-- Parameter Readout -->
                                    <text x="212" y="116" font-family="Poppins, sans-serif" font-weight="600" font-size="7" fill="#60A5FA">40°C • 1200 RPM</text>

                                    <!-- 3. Program Rotary Dial Selector (Center-Right) -->
                                    <g transform="translate(345, 91)">
                                        <!-- Outer dial ring with notch markings -->
                                        <circle cx="0" cy="0" r="26" fill="url(#wmDoorOuter)" stroke="#CBD5E1" stroke-width="1.5"/>
                                        <circle cx="0" cy="0" r="20" fill="url(#wmKnobGrad)" stroke="#94A3B8" stroke-width="1.5"/>
                                        <!-- Dial grip grooves -->
                                        <line x1="0" y1="-20" x2="0" y2="-12" stroke="#1F7FC4" stroke-width="3" stroke-linecap="round"/>
                                        <circle cx="0" cy="0" r="6" fill="#CBD5E1"/>
                                        <!-- Program Indicator Dots around dial -->
                                        <circle cx="0" cy="-23" r="1.5" fill="#1F7FC4"/>
                                        <circle cx="16" cy="-16" r="1.5" fill="#64748B"/>
                                        <circle cx="23" cy="0" r="1.5" fill="#64748B"/>
                                        <circle cx="-16" cy="-16" r="1.5" fill="#64748B"/>
                                        <circle cx="-23" cy="0" r="1.5" fill="#64748B"/>
                                    </g>

                                    <!-- 4. Power & Start/Pause Button (Right) -->
                                    <g transform="translate(408, 91)">
                                        <circle cx="0" cy="0" r="18" fill="#081050" stroke="#39C5A8" stroke-width="2"/>
                                        <!-- Glowing Start Triangle -->
                                        <polygon points="-3,-7 7,0 -3,7" fill="#39C5A8" class="animate-pulse-subtle"/>
                                    </g>

                                    <!-- MAIN CIRCULAR WASHER DOOR (cx=260, cy=285) -->
                                    <!-- Outer Chrome Bezel Ring -->
                                    <circle cx="260" cy="285" r="136" fill="url(#wmDoorOuter)" stroke="#CBD5E1" stroke-width="3"/>
                                    <!-- Inner Metallic Rim -->
                                    <circle cx="260" cy="285" r="118" fill="url(#wmDoorInner)" stroke="#94A3B8" stroke-width="2"/>
                                    <!-- Door Latch Handle (Right) -->
                                    <path d="M 374 252 Q 394 285 374 318 L 364 312 Q 380 285 364 258 Z" fill="url(#wmDoorOuter)" stroke="#94A3B8" stroke-width="1.5"/>

                                    <!-- DOOR PORTAL & TUMBLING WASHING DRUM (Clipped to Glass Circle) -->
                                    <g clip-path="url(#wmDrumClip)">
                                        <!-- Deep Drum Stainless Cavity Backing -->
                                        <circle cx="260" cy="285" r="102" fill="url(#wmDrumCavity)"/>

                                        <!-- Stainless Drum Radial Hole Grid Background -->
                                        <g opacity="0.3">
                                            <circle cx="260" cy="285" r="82" stroke="#64748B" stroke-width="1" stroke-dasharray="3 6"/>
                                            <circle cx="260" cy="285" r="62" stroke="#64748B" stroke-width="1" stroke-dasharray="3 6"/>
                                            <circle cx="260" cy="285" r="42" stroke="#64748B" stroke-width="1" stroke-dasharray="3 6"/>
                                        </g>

                                        <!-- ROTATING WASH DRUM & CLOTHES TUMBLE GROUP -->
                                        <g transform="translate(260, 285)">
                                            <g class="animate-wash-tumble">
                                                <!-- 3 Stainless Drum Lifter Baffles -->
                                                <g opacity="0.6">
                                                    <polygon points="-8,-98 8,-98 0,-76" fill="#94A3B8"/>
                                                    <polygon points="84,48 76,62 62,42" fill="#94A3B8"/>
                                                    <polygon points="-84,48 -62,42 -76,62" fill="#94A3B8"/>
                                                </g>

                                                <!-- CLOTHES: Orange Towel (Reference Image Match) -->
                                                <path
                                                    d="M -35 25 C -65 5, -55 -45, -15 -52 C 20 -58, 55 -22, 42 18 C 30 52, -10 58, -35 25 Z"
                                                    fill="url(#wmOrangeTowel)"
                                                />
                                                <!-- Orange Towel Folds & Texture -->
                                                <path d="M -25 12 Q -8 -18 18 8 Q 0 30 -25 12" stroke="#B45309" stroke-width="3" fill="none"/>
                                                <path d="M -40 -10 Q -20 -30 10 -25" stroke="#D97706" stroke-width="2.5" fill="none"/>

                                                <!-- CLOTHES: Denim Blue Casual Shirt -->
                                                <path
                                                    d="M 12 -28 C 42 -45, 72 -18, 58 18 C 46 50, 2 62, -22 45 C 2 22, -4 -18, 12 -28 Z"
                                                    fill="url(#wmBlueShirt)"
                                                />
                                                <!-- Blue Shirt Sleeve & Collar Folds -->
                                                <path d="M 22 -18 Q 42 8 18 30" stroke="#081050" stroke-width="2.5" fill="none"/>
                                                <path d="M 5 0 Q 25 15 35 35" stroke="#1565A0" stroke-width="2" fill="none"/>

                                                <!-- CLOTHES: Crisp White Cotton Fabric -->
                                                <path
                                                    d="M -50 -12 C -38 -42, 8 -42, 8 -12 C 8 18, -38 30, -50 -12 Z"
                                                    fill="#FFFFFF"
                                                    opacity="0.95"
                                                />
                                                <path d="M -38 -18 Q -16 -6 -28 12" stroke="#CBD5E1" stroke-width="2.5" fill="none"/>

                                                <!-- CLOTHES: Fresh Teal Accent Fabric -->
                                                <path
                                                    d="M -18 28 C -5 52, 34 50, 40 28 C 22 22, 0 18, -18 28 Z"
                                                    fill="url(#wmTealCloth)"
                                                />
                                                <path d="M -8 32 Q 10 42 26 30" stroke="#0F766E" stroke-width="2" fill="none"/>
                                            </g>
                                        </g>

                                        <!-- Dynamic Sloshing Water Pool -->
                                        <g class="animate-water-slosh">
                                            <path
                                                d="M 155 290 Q 205 272, 260 290 T 365 290 L 365 390 L 155 390 Z"
                                                fill="url(#wmWaterGrad)"
                                            />
                                            <!-- Water Surface Foam Crest -->
                                            <path
                                                d="M 155 290 Q 205 272, 260 290 T 365 290"
                                                stroke="#FFFFFF"
                                                stroke-width="3"
                                                fill="none"
                                                opacity="0.85"
                                            />
                                        </g>

                                        <!-- Rich Soapy Suds & Bubbles Inside Drum Glass -->
                                        <g>
                                            <circle cx="205" cy="305" r="10" fill="#FFFFFF" opacity="0.9" class="animate-soap-bubble-1"/>
                                            <circle cx="225" cy="298" r="15" fill="#FFFFFF" opacity="0.85" class="animate-soap-bubble-2"/>
                                            <circle cx="255" cy="310" r="12" fill="#FFFFFF" opacity="0.9" class="animate-soap-bubble-3"/>
                                            <circle cx="285" cy="302" r="16" fill="#FFFFFF" opacity="0.85" class="animate-soap-bubble-1"/>
                                            <circle cx="310" cy="315" r="11" fill="#FFFFFF" opacity="0.8" class="animate-soap-bubble-2"/>
                                            <circle cx="235" cy="328" r="13" fill="#FFFFFF" opacity="0.75" class="animate-soap-bubble-3"/>
                                            <circle cx="270" cy="335" r="14" fill="#FFFFFF" opacity="0.8" class="animate-soap-bubble-1"/>
                                            <circle cx="300" cy="332" r="9" fill="#FFFFFF" opacity="0.75" class="animate-soap-bubble-2"/>
                                            <circle cx="185" cy="325" r="8" fill="#FFFFFF" opacity="0.7" class="animate-soap-bubble-3"/>
                                            <circle cx="330" cy="320" r="8" fill="#FFFFFF" opacity="0.7" class="animate-soap-bubble-1"/>
                                        </g>

                                        <!-- Curved 3D Convex Glass Door Highlights & Reflections -->
                                        <path
                                            d="M 180 220 A 88 88 0 0 1 330 210 A 78 78 0 0 0 196 238 Z"
                                            fill="#FFFFFF"
                                            opacity="0.45"
                                        />
                                        <path
                                            d="M 172 248 A 90 90 0 0 1 206 198 A 96 96 0 0 0 172 248 Z"
                                            fill="#FFFFFF"
                                            opacity="0.35"
                                        />
                                        <!-- Cyan soft glass tint overlay -->
                                        <circle cx="260" cy="285" r="102" fill="#38BDF8" opacity="0.1" />
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
            <section id="keunggulan" class="relative bg-navy-900 pt-20 pb-24 px-4 sm:px-6 lg:px-8 text-white">
                <div class="max-w-7xl mx-auto">
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 items-center">
                        <!-- Left 3 Feature Cards (Every Sunday Reference Layout) -->
                        <div class="lg:col-span-8 grid grid-cols-1 md:grid-cols-3 gap-5">
                            <!-- Card 1: Highlight Periwinkle Card -->
                            <div class="landing-reveal bg-gradient-to-br from-primary-600 to-primary-700 text-white rounded-[24px] p-6 shadow-xl relative overflow-hidden transition-all duration-300 hover:-translate-y-1.5 flex flex-col justify-between">
                                <div>
                                    <div class="w-12 h-12 rounded-2xl bg-white/20 backdrop-blur-md flex items-center justify-center mb-5">
                                        <ShieldCheck class="w-6 h-6 text-white" />
                                    </div>
                                    <h3 class="font-heading font-bold text-lg mb-2">1 Mesin 1 Pelanggan</h3>
                                    <p class="font-sans text-xs sm:text-sm text-white/90 leading-relaxed">
                                        Pakaian Anda tidak pernah dicampur dengan orang lain. 100% higienis, bersih suci, dan aman tanpa risiko tertukar.
                                    </p>
                                </div>
                                <div class="mt-6 flex items-center text-xs font-bold text-warning gap-1">
                                    <span>Standar Mutlak</span>
                                    <Check class="w-4 h-4" />
                                </div>
                            </div>

                            <!-- Card 2: Navy Outline Card -->
                            <div class="landing-reveal delay-150 bg-navy-800/90 border border-white/20 text-white rounded-[24px] p-6 shadow-lg transition-all duration-300 hover:-translate-y-1.5 flex flex-col justify-between">
                                <div>
                                    <div class="w-12 h-12 rounded-2xl bg-white/10 flex items-center justify-center mb-5">
                                        <Sparkles class="w-6 h-6 text-warning" />
                                    </div>
                                    <h3 class="font-heading font-bold text-lg mb-2">Pewangi & Sabun Premium</h3>
                                    <p class="font-sans text-xs sm:text-sm text-slate-200 leading-relaxed">
                                        Deterjen ramah serat kain dipadukan setrika uap boiler profesional, pakaian rapi licin bebas noda gosong.
                                    </p>
                                </div>
                                <div class="mt-6 text-xs font-semibold text-warning">
                                    Aroma Tahan Lama
                                </div>
                            </div>

                            <!-- Card 3: Navy Outline Card -->
                            <div class="landing-reveal delay-300 bg-navy-800/90 border border-white/20 text-white rounded-[24px] p-6 shadow-lg transition-all duration-300 hover:-translate-y-1.5 flex flex-col justify-between">
                                <div>
                                    <div class="w-12 h-12 rounded-2xl bg-white/10 flex items-center justify-center mb-5">
                                        <Truck class="w-6 h-6 text-accent-500" />
                                    </div>
                                    <h3 class="font-heading font-bold text-lg mb-2">Free Ongkir Maks 3 kg</h3>
                                    <p class="font-sans text-xs sm:text-sm text-slate-200 leading-relaxed">
                                        Layanan antar jemput cucian praktis langsung ke depan pintu rumah Anda di area Hendrosari & sekitarnya.
                                    </p>
                                </div>
                                <div class="mt-6 text-xs font-semibold text-accent-500">
                                    Antar Jemput Cepat
                                </div>
                            </div>
                        </div>

                        <!-- Right Section Header / Description -->
                        <div class="landing-reveal-right lg:col-span-4 flex flex-col items-start lg:pl-6">
                            <!-- Section Number Badge 01 -->
                            <div class="w-10 h-10 rounded-pill border border-white/40 flex items-center justify-center font-heading font-extrabold text-sm text-white mb-4 bg-white/10">
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
                                class="mt-6 bg-accent-500 hover:bg-accent-600 text-navy-900 font-bold text-sm px-6 py-3 rounded-pill transition-all duration-200 shadow-sm hover:shadow-md inline-flex items-center gap-2"
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
            <section id="tarif" class="relative bg-navy-950 pt-20 pb-28 px-4 sm:px-6 lg:px-8 text-white">
                <div class="max-w-7xl mx-auto">
                    <!-- Section Header -->
                    <div class="landing-reveal flex flex-col md:flex-row md:items-end justify-between gap-6 mb-12">
                        <div>
                            <div class="w-10 h-10 rounded-pill border border-white/40 flex items-center justify-center font-heading font-extrabold text-sm text-white mb-4 bg-white/10">
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
                                class="bg-accent-500 hover:bg-accent-600 text-navy-900 font-bold text-sm px-6 py-3 rounded-pill transition-all duration-200 shadow-sm hover:shadow-md flex items-center gap-2"
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
                            class="landing-reveal bg-white text-navy-900 rounded-[28px] p-6 shadow-xl border border-slate-200 hover:shadow-[0_20px_48px_rgba(16,25,77,0.18)] transition-all duration-300 hover:-translate-y-2 flex flex-col justify-between relative group"
                            :style="{ transitionDelay: `${sIdx * 100}ms` }"
                        >
                            <!-- Top Info & Badges -->
                            <div>
                                <div class="flex items-center justify-between mb-4">
                                    <span
                                        class="text-[11px] font-extrabold uppercase px-3 py-1 rounded-pill"
                                        :class="service.featured ? 'bg-accent-500 text-navy-900' : 'bg-primary-100 text-primary-700'"
                                    >
                                        {{ service.badge }}
                                    </span>
                                    <div class="flex items-center gap-1 text-xs font-bold text-navy-900">
                                        <Star class="w-3.5 h-3.5 fill-warning text-warning" />
                                        <span>{{ service.rating }}</span>
                                    </div>
                                </div>

                                <h3 class="font-heading font-extrabold text-xl text-navy-900 mb-1">
                                    {{ service.name }}
                                </h3>
                                <p class="text-xs text-slate-500 font-semibold mb-4">
                                    {{ service.minWeight }}
                                </p>

                                <!-- Price Tag -->
                                <div class="bg-bg rounded-[20px] p-4 mb-5 border border-slate-200">
                                    <div class="text-xs text-slate-500 font-medium">Tarif Mulai</div>
                                    <div class="flex items-baseline gap-1 mt-0.5">
                                        <span class="font-heading font-extrabold text-2xl sm:text-3xl text-navy-900">
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
                                        <Check class="w-4 h-4 text-success shrink-0 mt-0.5" />
                                        <span>{{ feat }}</span>
                                    </li>
                                </ul>
                            </div>

                            <!-- Card Order CTA -->
                            <a
                                :href="getServiceWhatsappUrl(service.name)"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="w-full text-center py-3 rounded-pill font-bold text-xs transition-all duration-200 flex items-center justify-center gap-2"
                                :class="service.featured
                                    ? 'bg-accent-500 hover:bg-accent-600 text-navy-900 shadow-sm'
                                    : 'bg-navy-900 hover:bg-navy-800 text-white'"
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
            <section class="relative bg-gradient-to-b from-navy-950 to-navy-900 pt-12 pb-24 px-4 sm:px-6 lg:px-8 text-white">
                <div class="max-w-6xl mx-auto">
                    <!-- Big Curved Periwinkle Banner (Every Sunday Reference) -->
                    <div class="landing-reveal bg-gradient-to-r from-primary-600 to-primary-700 rounded-[36px] md:rounded-[44px] p-8 sm:p-12 md:p-16 text-white relative shadow-2xl overflow-hidden pb-32 md:pb-36">
                        <!-- Background Glow & Shapes -->
                        <div class="absolute -right-16 -top-16 w-80 h-80 rounded-pill bg-white/10 blur-2xl pointer-events-none"></div>

                        <div class="relative z-10 max-w-2xl">
                            <div class="inline-flex items-center gap-2 bg-white/20 backdrop-blur-md px-3.5 py-1 rounded-pill text-xs font-bold uppercase tracking-wider mb-4">
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
                                <span class="bg-white/20 backdrop-blur-md text-white text-xs font-bold px-4 py-2 rounded-pill">
                                    {{ customersLabel }} Pelanggan Setia
                                </span>
                                <span class="bg-white/20 backdrop-blur-md text-white text-xs font-bold px-4 py-2 rounded-pill">
                                    {{ ordersLabel }} Nota Dikerjakan
                                </span>
                                <span class="bg-white/20 backdrop-blur-md text-white text-xs font-bold px-4 py-2 rounded-pill">
                                    {{ laundryKgLabel }} Pakaian Bersih
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Overlapping White Ticket Card (Every Sunday Signature) -->
                    <div class="landing-reveal delay-200 relative -mt-24 sm:-mt-28 mx-auto max-w-4xl z-20 px-2 sm:px-4">
                        <div class="bg-white text-navy-900 rounded-[28px] sm:rounded-[32px] shadow-[0_20px_48px_rgba(16,25,77,0.22)] border border-slate-200 overflow-hidden flex flex-col md:flex-row items-stretch">
                            <!-- Left: Illustration Mini-car / Basket -->
                            <div class="md:w-5/12 bg-bg p-6 sm:p-8 flex items-center justify-center border-b md:border-b-0 md:border-r border-slate-200">
                                <div class="text-center">
                                    <div class="w-24 h-24 mx-auto rounded-pill bg-primary-100 flex items-center justify-center text-4xl mb-4 shadow-sm">
                                        👕
                                    </div>
                                    <div class="font-heading font-extrabold text-lg text-navy-900">Paket All-In-One</div>
                                    <p class="text-xs text-slate-600 mt-1 font-medium">Cuci + Kering Mesin + Setrika Uap</p>
                                    <span class="inline-block mt-3 bg-success/15 text-success font-bold text-[11px] px-3 py-1 rounded-pill">
                                        Higienis 1 Mesin 1 Pelanggan
                                    </span>
                                </div>
                            </div>

                            <!-- Center: Package Features & Details -->
                            <div class="md:w-6/12 p-6 sm:p-8 flex flex-col justify-between">
                                <div>
                                    <div class="text-[11px] font-bold tracking-wider text-primary-600 uppercase mb-1">
                                        PAKET PALING LARIS
                                    </div>
                                    <div class="flex items-baseline gap-2 mb-4">
                                        <span class="font-heading font-extrabold text-3xl sm:text-4xl text-primary-700">
                                            Rp 8.000
                                        </span>
                                        <span class="text-sm font-bold text-slate-500">/ kg</span>
                                    </div>

                                    <ul class="space-y-2.5 text-xs sm:text-sm text-slate-800 font-medium mb-6">
                                        <li class="flex items-center gap-2">
                                            <Check class="w-4 h-4 text-success shrink-0" />
                                            <span>1 Mesin 1 Pelanggan (Tidak pernah dicampur)</span>
                                        </li>
                                        <li class="flex items-center gap-2">
                                            <Check class="w-4 h-4 text-success shrink-0" />
                                            <span>Setrika Uap Boiler Rapi & Halus</span>
                                        </li>
                                        <li class="flex items-center gap-2">
                                            <Check class="w-4 h-4 text-success shrink-0" />
                                            <span>Pewangi Grade Premium Tahan Lama</span>
                                        </li>
                                        <li class="flex items-center gap-2">
                                            <Check class="w-4 h-4 text-success shrink-0" />
                                            <span>Free Ongkir Antar-Jemput (Maksimal 3 kg)</span>
                                        </li>
                                    </ul>
                                </div>

                                <a
                                    :href="getServiceWhatsappUrl('Cuci Setrika Rp 8.000/kg')"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="inline-flex items-center gap-2 text-sm font-bold text-primary-600 hover:text-primary-700 transition-colors"
                                >
                                    <span>Pesan Paket Ini via WhatsApp</span>
                                    <ArrowRight class="w-4 h-4" />
                                </a>
                            </div>

                            <!-- Right: Signature Vertical Orange Ticket Stub (Every Sunday) -->
                            <div class="w-full md:w-1/12 bg-accent-500 text-navy-900 font-extrabold text-xs uppercase tracking-widest md:writing-vertical flex items-center justify-center py-3 md:py-4 px-4 md:px-3 select-none border-t md:border-t-0 md:border-l-2 md:border-dashed border-navy-900/25">
                                <span>ANDIK LAUNDRY • GRESIK</span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- ======================================================== -->
            <!-- SECTION 04: TIPS & ARTIKEL (ARTICLES)                    -->
            <!-- ======================================================== -->
            <section class="relative bg-navy-900 pt-16 pb-24 px-4 sm:px-6 lg:px-8 text-white">
                <div class="max-w-7xl mx-auto">
                    <!-- Section Header -->
                    <div class="landing-reveal mb-10">
                        <div class="w-10 h-10 rounded-pill border border-white/40 flex items-center justify-center font-heading font-extrabold text-sm text-white mb-4 bg-white/10">
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
                        <div class="landing-reveal group relative rounded-[28px] overflow-hidden shadow-xl bg-navy-800 min-h-[340px] flex flex-col justify-end p-8 border border-white/10 transition-all duration-300 hover:-translate-y-1.5">
                            <!-- Background Pattern / Tint -->
                            <div class="absolute inset-0 bg-gradient-to-br from-[#1F7FC4]/40 via-navy-900/80 to-navy-950 z-10"></div>
                            <div class="absolute inset-0 bg-[radial-gradient(var(--color-accent-400)_1px,transparent_1px)] [background-size:16px_16px] opacity-20"></div>

                            <div class="relative z-20">
                                <span class="bg-accent-500 text-navy-900 font-bold text-xs px-3 py-1 rounded-pill uppercase tracking-wider mb-3 inline-block">
                                    Tips Perawatan
                                </span>
                                <h3 class="font-heading font-bold text-xl sm:text-2xl text-white group-hover:text-warning transition-colors leading-snug">
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
                        <div class="landing-reveal delay-150 group relative rounded-[28px] overflow-hidden shadow-xl bg-navy-800 min-h-[340px] flex flex-col justify-end p-8 border border-white/10 transition-all duration-300 hover:-translate-y-1.5">
                            <!-- Background Pattern / Tint -->
                            <div class="absolute inset-0 bg-gradient-to-br from-[#2F8FD8]/40 via-navy-900/80 to-navy-950 z-10"></div>
                            <div class="absolute inset-0 bg-[radial-gradient(var(--color-primary-400)_1px,transparent_1px)] [background-size:16px_16px] opacity-20"></div>

                            <div class="relative z-20">
                                <span class="bg-warning text-navy-900 font-bold text-xs px-3 py-1 rounded-pill uppercase tracking-wider mb-3 inline-block">
                                    Edukasi Higienis
                                </span>
                                <h3 class="font-heading font-bold text-xl sm:text-2xl text-white group-hover:text-warning transition-colors leading-snug">
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
            <section id="lokasi" class="relative bg-navy-950 pt-16 pb-24 px-4 sm:px-6 lg:px-8 text-white">
                <div class="max-w-7xl mx-auto">
                    <div class="landing-reveal mb-10">
                        <div class="w-10 h-10 rounded-pill border border-white/40 flex items-center justify-center font-heading font-extrabold text-sm text-white mb-4 bg-white/10">
                            05
                        </div>
                        <h2 class="font-heading font-extrabold text-3xl sm:text-4xl uppercase tracking-tight text-white">
                            KUNJUNGI OUTLET KAMI
                        </h2>
                        <p class="font-sans text-sm sm:text-base text-slate-200 mt-2">
                            Datang langsung untuk drop cucian atau gunakan layanan antar jemput kami.
                        </p>
                    </div>

                    <div class="landing-reveal bg-white text-navy-900 rounded-[28px] sm:rounded-[36px] p-6 sm:p-8 shadow-2xl border border-slate-200 grid grid-cols-1 lg:grid-cols-12 gap-8 items-center">
                        <!-- Left Info Card -->
                        <div class="lg:col-span-5 flex flex-col justify-between h-full">
                            <div>
                                <span class="bg-primary-100 text-primary-700 text-xs font-bold px-3 py-1 rounded-pill uppercase tracking-wider">
                                    Outlet Resmi
                                </span>
                                <h3 class="font-heading font-extrabold text-2xl sm:text-3xl text-navy-900 mt-3 mb-4">
                                    Andik Laundry
                                </h3>

                                <div class="space-y-4 text-xs sm:text-sm text-navy-900">
                                    <div class="flex items-start gap-3">
                                        <MapPin class="w-5 h-5 text-primary-600 shrink-0 mt-0.5" />
                                        <div>
                                            <div class="font-bold">Alamat Lengkap:</div>
                                            <p class="text-slate-600 font-medium leading-relaxed mt-0.5">
                                                Hendrosari, RT.06/RW.03, Hendrosari, Kec. Menganti, Kabupaten Gresik, Jawa Timur 61174
                                            </p>
                                        </div>
                                    </div>

                                    <div class="flex items-start gap-3">
                                        <Clock class="w-5 h-5 text-success shrink-0 mt-0.5" />
                                        <div>
                                            <div class="font-bold">Jam Operasional:</div>
                                            <p class="text-slate-600 font-medium mt-0.5">
                                                Buka Setiap Hari: 07.00 – 20.00 WIB
                                            </p>
                                        </div>
                                    </div>

                                    <div class="flex items-start gap-3">
                                        <Truck class="w-5 h-5 text-accent-500 shrink-0 mt-0.5" />
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
                                    class="bg-success hover:bg-success/90 text-white font-bold font-bold text-xs sm:text-sm px-5 py-3 rounded-pill transition-all duration-200 flex items-center justify-center gap-2 shadow-sm"
                                >
                                    <MessageCircle class="w-4 h-4" />
                                    <span>Hubungi WhatsApp Toko</span>
                                </a>

                                <a
                                    href="https://www.google.com/maps/place/Andik+Laundry/@-7.2392419,112.581764,21z/data=!4m12!1m5!3m4!2zN8KwMTQnMjEuMyJTIDExMsKwMzQnNTQuMyJF!8m2!3d-7.2392469!4d112.5817531!3m5!1s0x2e7800f99a230c6b:0xb287a654290078e9!8m2!3d-7.239213!4d112.5817581!16s%2Fg%2F11gdlxnlmk?hl=id-ID"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="bg-navy-900 hover:bg-navy-800 text-white font-bold text-xs sm:text-sm px-5 py-3 rounded-pill transition-all duration-200 flex items-center justify-center gap-2"
                                >
                                    <ExternalLink class="w-4 h-4" />
                                    <span>Buka di Google Maps</span>
                                </a>
                            </div>
                        </div>

                        <!-- Right Google Maps Interactive Embed -->
                        <div class="lg:col-span-7 h-[280px] sm:h-[360px] md:h-[420px] rounded-[24px] overflow-hidden border border-slate-200 shadow-inner relative">
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
            <section id="faq" class="relative bg-navy-900 pt-16 pb-24 px-4 sm:px-6 lg:px-8 text-white">
                <div class="max-w-4xl mx-auto">
                    <div class="landing-reveal text-center mb-12">
                        <div class="w-10 h-10 rounded-pill border border-white/40 mx-auto flex items-center justify-center font-heading font-extrabold text-sm text-white mb-4 bg-white/10">
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
                            class="bg-navy-800/80 border border-white/10 rounded-[20px] overflow-hidden transition-all duration-200"
                        >
                            <button
                                type="button"
                                class="w-full text-left px-6 py-5 flex items-center justify-between gap-4 font-heading font-bold text-base sm:text-lg text-white hover:text-warning transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-warning"
                                :aria-expanded="activeFaqIndex === fIdx"
                                @click="toggleFaq(fIdx)"
                            >
                                <span>{{ faq.q }}</span>
                                <ChevronDown
                                    class="w-5 h-5 text-warning shrink-0 transition-transform duration-300"
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
            <section id="lacak" class="relative bg-gradient-to-b from-navy-900 via-navy-950 to-navy-950 pt-16 pb-24 px-4 sm:px-6 lg:px-8 text-white">
                <div class="landing-reveal max-w-4xl mx-auto text-center">
                    <!-- Heading Every Sunday Style with Yellow Highlight -->
                    <h2 class="font-heading font-extrabold text-3xl sm:text-5xl uppercase tracking-tight leading-tight text-white">
                        SIAP CUCIAN BERSIH TANPA REPOT? <br />
                        <span class="text-warning">PESAN SEKARANG</span> JUGA!
                    </h2>
                    <p class="font-sans text-sm sm:text-base text-slate-200 mt-4 max-w-xl mx-auto leading-relaxed">
                        Kirim pesan ke admin WhatsApp kami untuk penjemputan cucian gratis (maks 3 kg) atau lacak langsung progres nota Anda.
                    </p>

                    <!-- Large Pill Search / Action Form -->
                    <div class="max-w-xl mx-auto mt-8">
                        <form
                            @submit.prevent="submitOrderCheck"
                            class="bg-white rounded-pill p-2 pl-4 sm:pl-6 shadow-2xl border border-slate-200 flex items-center justify-between gap-2 transition-all focus-within:ring-3 focus-within:ring-primary-500/40"
                        >
                            <input
                                v-model="orderQuery"
                                type="text"
                                placeholder="Masukkan nomor nota atau No. HP…"
                                class="w-full bg-transparent text-navy-900 text-xs sm:text-base font-semibold placeholder-slate-400 focus:outline-none"
                            />
                            <button
                                type="submit"
                                :disabled="isCheckingOrder"
                                class="bg-accent-500 hover:bg-accent-600 text-navy-900 font-bold text-xs sm:text-sm px-4 sm:px-6 py-3 sm:py-3.5 rounded-pill transition-all duration-200 shrink-0 shadow-sm hover:shadow-md disabled:opacity-50 flex items-center gap-1.5 sm:gap-2 min-h-[44px]"
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
                            class="inline-flex items-center gap-2.5 bg-transparent border-2 border-white/60 hover:border-white text-white font-bold text-sm px-6 py-3 rounded-pill transition-all hover:bg-white/10"
                        >
                            <Phone class="w-4 h-4 text-warning" />
                            <span>Hubungi Admin: {{ storePhone }}</span>
                        </a>
                    </div>
                </div>
            </section>
        </main>

        <!-- ======================================================== -->
        <!-- FOOTER (DESIGN.md)                                       -->
        <!-- ======================================================== -->
        <footer class="bg-navy-950 text-slate-300 border-t border-white/10 pt-16 pb-12 px-4 sm:px-6 lg:px-8">
            <div class="max-w-7xl mx-auto">
                <div class="grid grid-cols-1 md:grid-cols-12 gap-10 pb-12 border-b border-white/10">
                    <!-- Brand Column -->
                    <div class="md:col-span-5">
                        <Link href="/" class="flex items-center gap-1.5 focus-visible:outline-none">
                            <span class="font-heading font-extrabold text-2xl tracking-tight text-white">
                                andik<span class="text-accent-500">.</span>
                            </span>
                            <span class="text-[10px] font-bold tracking-widest text-primary-600 uppercase bg-primary-100 px-2 py-0.5 rounded-pill ml-1">
                                LAUNDRY
                            </span>
                        </Link>
                        <p class="font-sans text-xs sm:text-sm text-slate-300 mt-4 max-w-sm leading-relaxed font-normal">
                            Layanan binatu harian higienis dan terpercaya di Gresik sejak 2009. Berprinsip 1 mesin 1 pelanggan, menjaga kesucian dan kebersihan pakaian keluarga Anda.
                        </p>
                        <div class="text-xs text-warning font-bold mt-4">
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
                            <li><Link v-if="canLogin" href="/login" class="hover:text-white transition-colors font-bold text-accent-500">Masuk Dashboard Kasir / Owner</Link></li>
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
