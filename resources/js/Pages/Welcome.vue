<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    ArrowRight,
    Banknote,
    Check,
    CheckCircle2,
    ChevronDown,
    Clock,
    FileText,
    MapPin,
    MessageCircle,
    Phone,
    QrCode,
    Receipt,
    Search,
    ShieldCheck,
    Sparkles,
    Star,
    Truck,
} from 'lucide-vue-next';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

const props = defineProps<{
    canLogin?: boolean;
    stats?: { customers: number; orders: number; laundry_kg: number };
    store?: { name: string; phone: string; address: string };
}>();

const menuOpen = ref(false);
const menuLabel = computed(() => (menuOpen.value ? 'Tutup navigasi' : 'Buka navigasi'));

// Data Toko & Kontak
const storeName = computed(() => props.store?.name || 'Andik Laundry');
const storePhone = computed(() => props.store?.phone || '6281234567890');
const storeAddress = computed(() => props.store?.address || 'Jl. Raya Laundry No. 12, Jakarta');

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

// Cek Pesanan: nomor nota atau nomor HP, diteruskan ke halaman nota publik.
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

// Statistik pelanggan (riil + basis) untuk social proof di hero.
const stats = computed(() => props.stats ?? { customers: 1250, orders: 3400, laundry_kg: 8200 });

// Counter ++ animasi bertahap dari 0 saat web load
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

// Auto-typing text effect di input lacak progres
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
        name: 'Cuci Basah',
        unit: 'kg',
        price: 'Rp 5.000',
        minWeight: 'Min. 2 kg',
        badge: 'Ekonomis',
        desc: 'Pakaian dicuci bersih dan dibilas higienis dengan deterjen premium, siap dijemur mandiri di rumah.',
        features: [
            '1 mesin 1 pelanggan (tidak dicampur)',
            'Deterjen premium ramah serat kain',
            'Pembersihan noda awal kerah & manset',
            'Bilas bersih tanpa sisa deterjen',
        ],
    },
    {
        name: 'Cuci Kering',
        unit: 'kg',
        price: 'Rp 6.000',
        minWeight: 'Min. 2 kg',
        badge: 'Cepat & Rapi',
        desc: 'Dicuci higienis dan dikeringkan menggunakan mesin pengering suhu terkontrol, bebas apek dan siap dilipat.',
        features: [
            '1 mesin 1 pelanggan (tidak dicampur)',
            'Mesin pengering suhu stabil (anti-kusut)',
            'Pakaian kering sempurna tanpa bau apek',
            'Disortir dan dilipat rapi',
        ],
    },
    {
        name: 'Cuci Setrika',
        unit: 'kg',
        price: 'Rp 8.000',
        minWeight: 'Min. 2 kg',
        badge: 'Paling Favorit',
        featured: true,
        desc: 'Layanan all-in-one paling diminati: dicuci higienis, dikeringkan, disetrika uap halus, dan dikemas wangi terlindung debu.',
        features: [
            '1 mesin 1 pelanggan (tidak dicampur)',
            'Setrika uap profesional (anti-gosong)',
            'Pewangi tahan lama grade premium',
            'Kemasan plastik higienis kedap debu',
        ],
    },
    {
        name: 'Cuci Karpet',
        unit: 'm²',
        price: 'Rp 15.000',
        minWeight: 'Kalkulasi P × L',
        badge: 'Deep Clean',
        desc: 'Pembersihan mendalam karpet rumah, kantor, atau masjid menggunakan hydro-cleaning untuk mengangkat debu dan tungau.',
        features: [
            'Pembersihan serat karpet mendalam',
            'Mengangkat debu, tungau, dan kuman',
            'Pengerolan dan pengeringan steril',
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
        a: 'Sama sekali tidak. Komitmen utama Andik Laundry adalah 1 mesin khusus untuk 1 pelanggan pada setiap proses pencucian. Pakaian Anda dijamin tidak tercampur, lebih higienis, dan terhindar dari risiko tertukar.',
    },
    {
        q: 'Berapa lama waktu pengerjaan cucian?',
        a: 'Untuk layanan Reguler memerlukan waktu 2–3 hari kerja. Kami juga menyediakan opsi Ekspres (selesai di hari yang sama: taruh pagi ambil sore) dengan tambahan biaya hanya Rp 2.000 / kg.',
    },
    {
        q: 'Apakah ada layanan antar jemput cucian?',
        a: 'Ya, kami melayani antar jemput cucian. Gratis ongkir untuk jarak hingga 3 km dari lokasi outlet Andik Laundry. Cukup hubungi admin kami melalui WhatsApp.',
    },
    {
        q: 'Bagaimana cara melacak progres cucian saya?',
        a: 'Begitu pesanan Anda diinput, sistem mengirimkan nota digital langsung ke WhatsApp Anda. Anda cukup mengklik tautan nota tersebut atau memasukkan nomor nota di kolom Cek Pesanan di website ini tanpa perlu membuat akun / login.',
    },
    {
        q: 'Metode pembayaran apa saja yang diterima?',
        a: 'Kami menerima pembayaran Tunai (Cash) di kasir toko dan Non-Tunai QRIS (GoPay, OVO, Dana, ShopeePay, LinkAja, serta seluruh aplikasi mobile banking BCA, Mandiri, BRI, BNI). QRIS juga tertera di nota digital Anda.',
    },
];

const testimonials = [
    {
        name: 'Ratna W.',
        area: 'Pelanggan Kiloan Rutin',
        text: 'Cucian selesai tepat waktu, packing rapi, dan wanginya tahan sampai berhari-hari. Senang sekali pakaian tidak dicampur dengan orang lain.',
        stars: 5,
    },
    {
        name: 'Dimas P.',
        area: 'Antar Jemput Sekitar Toko',
        text: 'Notifikasi WhatsApp sangat memudahkan. Tinggal klik link nota langsung tahu cucian sudah di rak nomor berapa dan siap diambil.',
        stars: 5,
    },
    {
        name: 'Sinta M.',
        area: 'Pelanggan Cuci Karpet',
        text: 'Karpet ruang tamu 3x4 meter kembali bersih cerah dan aromanya segar. Noda kopi yang menempel lama bisa hilang bersih.',
        stars: 5,
    },
];

let revealObserver: IntersectionObserver | null = null;

onMounted(() => {
    startCounterAnimation();
    startTypewriter();

    const revealTargets = document.querySelectorAll<HTMLElement>('[data-reveal]');

    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches || !('IntersectionObserver' in window)) {
        revealTargets.forEach((element) => element.classList.add('is-revealed'));
        return;
    }

    revealObserver = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-revealed');
                    revealObserver?.unobserve(entry.target);
                }
            });
        },
        { threshold: 0.1, rootMargin: '0px 0px -32px 0px' },
    );

    revealTargets.forEach((element) => revealObserver?.observe(element));
});

onBeforeUnmount(() => {
    revealObserver?.disconnect();
    revealObserver = null;

    if (counterRafId !== null) {
        cancelAnimationFrame(counterRafId);
        counterRafId = null;
    }

    if (typewriterTimer !== null) {
        clearTimeout(typewriterTimer);
        typewriterTimer = null;
    }
});
</script>

<template>
    <Head title="Andik Laundry | Bersih, Wangi & Dirawat Teliti Tanpa Dicampur">
        <meta
            name="description"
            content="Layanan binatu modern kiloan dan karpet terpercaya. 1 mesin 1 pelanggan, nota digital WhatsApp, pelacakan live, pembayaran QRIS, dan gratis antar jemput."
        />
    </Head>

    <div class="landing-page min-h-screen bg-[#FFFDF5] text-[#000000]">
        <a class="skip-link" href="#main-content">Lewati ke konten utama</a>

        <!-- Sticky Header Navigasi -->
        <header class="site-header">
            <div class="site-header__inner">
                <Link href="/" class="wordmark" aria-label="Andik Laundry Beranda">
                    <span class="wordmark__name">andik<span class="wordmark__accent">.</span></span>
                    <span class="wordmark__caption">LAUNDRY</span>
                </Link>

                <nav
                    id="primary-navigation"
                    class="site-nav"
                    :class="{ 'site-nav--open': menuOpen }"
                    aria-label="Navigasi utama"
                >
                    <a href="#keunggulan" @click="menuOpen = false">Keunggulan</a>
                    <a href="#tarif" @click="menuOpen = false">Layanan & Tarif</a>
                    <a href="#cara-pesan" @click="menuOpen = false">Cara Pesan</a>
                    <a href="#lacak" @click="menuOpen = false">Lacak Nota</a>
                    <a href="#faq" @click="menuOpen = false">FAQ</a>
                    <a href="#kontak" @click="menuOpen = false">Kontak</a>

                    <div class="site-nav__actions">
                        <a
                            :href="whatsappUrl"
                            target="_blank"
                            rel="noopener"
                            class="nav-btn-wa"
                            aria-label="Pesan via WhatsApp"
                        >
                            <MessageCircle class="w-4 h-4 mr-1.5" />
                            <span>Pesan WA</span>
                        </a>
                        <Link v-if="canLogin" href="/login" class="staff-link" @click="menuOpen = false">
                            Masuk Staf
                        </Link>
                    </div>
                </nav>

                <button
                    class="menu-toggle"
                    type="button"
                    :aria-expanded="menuOpen"
                    aria-controls="primary-navigation"
                    :aria-label="menuLabel"
                    @click="menuOpen = !menuOpen"
                >
                    <span :class="{ 'rotate-45 translate-y-[6px]': menuOpen }"></span>
                    <span :class="{ 'opacity-0': menuOpen }"></span>
                    <span :class="{ '-rotate-45 -translate-y-[6px]': menuOpen }"></span>
                </button>
            </div>
        </header>

        <main id="main-content">
            <!-- Hero Section Wrapper with background -->
            <div class="hero-wrapper">
                <section id="hero" class="hero-section">
                    <div class="hero-section__copy" data-reveal>
                        <div class="hero-badge">
                            <span class="hero-badge__dot"></span>
                            <span>1 Mesin 1 Pelanggan • Tidak Dicampur</span>
                        </div>

                        <h1 class="hero-title">
                            Pakaian Bersih, Wangi & Rapi.<br />
                            <span class="hero-title__highlight">Dirawat Teliti Tanpa Dicampur.</span>
                        </h1>

                        <p class="hero-description">
                            Layanan binatu modern harian kiloan & karpet profesional. Nikmati pencucian higienis dengan nota digital otomatis terkirim ke WhatsApp Anda dan pelacakan progres secara live tanpa perlu login.
                        </p>

                        <!-- Dual Call To Action -->
                        <div class="hero-cta-group">
                            <a
                                :href="whatsappUrl"
                                target="_blank"
                                rel="noopener"
                                class="btn-cta-primary"
                            >
                                <MessageCircle class="w-5 h-5 mr-2" />
                                <span>Pesan Antar-Jemput (WA)</span>
                            </a>
                            <a href="#tarif" class="btn-cta-secondary">
                                <span>Lihat Tarif Layanan</span>
                                <ArrowRight class="w-4 h-4 ml-1.5" />
                            </a>
                        </div>

                        <!-- Quick Key Guarantees -->
                        <div class="hero-guarantees">
                            <div class="hero-guarantee-item">
                                <Check class="w-4 h-4 text-[#16A34A] mr-1.5 flex-shrink-0" />
                                <span>Deterjen & Pewangi Ramah Serat</span>
                            </div>
                            <div class="hero-guarantee-item">
                                <Check class="w-4 h-4 text-[#16A34A] mr-1.5 flex-shrink-0" />
                                <span>Opsi Ekspres Selesai Hari Ini</span>
                            </div>
                            <div class="hero-guarantee-item">
                                <Check class="w-4 h-4 text-[#16A34A] mr-1.5 flex-shrink-0" />
                                <span>Garansi Cuci Ulang 1x24 Jam</span>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column: Interactive Hub (Form Lacak + Counter Grid) -->
                    <div class="hero-section__interactive" data-reveal>
                        <!-- Card 1: Form Lacak Progres Cucian -->
                        <form class="hero-track-panel" aria-label="Cek pesanan nota" @submit.prevent="submitOrderCheck">
                            <div class="hero-track-panel__header">
                                <div class="flex items-center gap-2">
                                    <div class="w-7 h-7 rounded-md bg-[#EEF2FF] text-[#4F46E5] flex items-center justify-center">
                                        <Search class="w-4 h-4" />
                                    </div>
                                    <div>
                                        <h3 class="font-bold text-xs uppercase tracking-wider text-[#0A0A0A]">Lacak Progres Cucian</h3>
                                        <p class="text-[11px] text-[#6B6B6B]">Pantau status cucian & nota digital</p>
                                    </div>
                                </div>
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-[#ECFDF5] text-[#059669]">
                                    <span class="w-1.5 h-1.5 rounded-full bg-[#10B981]"></span>
                                    Live Realtime
                                </span>
                            </div>

                            <div class="hero-track-panel__row mt-3.5">
                                <input
                                    id="hero-order-query"
                                    v-model="orderQuery"
                                    class="hero-track-panel__input"
                                    type="text"
                                    name="q"
                                    inputmode="text"
                                    autocomplete="off"
                                    :placeholder="typewriterPlaceholder"
                                    aria-label="Nomor nota atau nomor HP"
                                    @focus="onInputFocus"
                                    @blur="onInputBlur"
                                />
                                <button class="hero-track-panel__submit" type="submit" :disabled="isCheckingOrder">
                                    {{ isCheckingOrder ? 'Mencari…' : 'Cek Status' }}
                                </button>
                            </div>

                            <p v-if="orderQueryError" class="hero-track-panel__error" role="alert">
                                {{ orderQueryError }}
                            </p>
                            <p v-else class="hero-track-panel__hint">
                                Contoh: AL-20261115-001 atau 08123456789. Tanpa perlu login.
                            </p>
                        </form>

                        <!-- Card 2: Counter / Stats 4-Grid -->
                        <dl class="hero-stats-panel" aria-label="Statistik Andik Laundry">
                            <div class="hero-stats-panel__item">
                                <dt>Pelanggan Setia</dt>
                                <dd>{{ customersLabel }}</dd>
                            </div>
                            <div class="hero-stats-panel__item">
                                <dt>Pesanan Tuntas</dt>
                                <dd>{{ ordersLabel }}</dd>
                            </div>
                            <div class="hero-stats-panel__item">
                                <dt>Cucian Bersih</dt>
                                <dd>{{ laundryKgLabel }}</dd>
                            </div>
                            <div class="hero-stats-panel__item">
                                <dt>Rating Pelanggan</dt>
                                <dd class="text-[#D97706]">
                                    ★ {{ ratingScore }}<span class="text-xs text-[#6B6B6B] font-normal">/5</span>
                                </dd>
                            </div>
                        </dl>

                        <!-- Trust Badges Footer -->
                        <div class="hero-trust-pills">
                            <div class="trust-pill-item">
                                <ShieldCheck class="w-3.5 h-3.5 text-[#10B981] mr-1.5" />
                                <span>1 Mesin 1 Pelanggan</span>
                            </div>
                            <div class="trust-pill-item">
                                <QrCode class="w-3.5 h-3.5 text-[#4F46E5] mr-1.5" />
                                <span>QRIS & Tunai</span>
                            </div>
                            <div class="trust-pill-item">
                                <Truck class="w-3.5 h-3.5 text-[#16A34A] mr-1.5" />
                                <span>Gratis Antar &lt; 3 km</span>
                            </div>
                        </div>
                    </div>
                </section>
            </div>

            <!-- Mengapa Memilih Kami (USP Section) -->
            <section id="keunggulan" class="section-container">
                <div class="section-header text-center" data-reveal>
                    <p class="section-eyebrow">Standar Kualitas</p>
                    <h2 class="section-title">Mengapa Harus Andik Laundry?</h2>
                    <p class="section-subtitle">
                        Empat alasan pelanggan mempercayakan pakaian harian dan karpet mereka kepada kami.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mt-8" data-reveal>
                    <div class="feature-card">
                        <div class="feature-icon bg-[#EEF2FF] text-[#4F46E5]">
                            <ShieldCheck class="w-6 h-6" />
                        </div>
                        <h3 class="feature-title">1 Mesin 1 Pelanggan</h3>
                        <p class="feature-desc">
                            Pakaian Anda tidak pernah dicampur dengan cucian pelanggan lain. Menjaga higienitas maksimal dan anti-tertukar.
                        </p>
                    </div>

                    <div class="feature-card">
                        <div class="feature-icon bg-[#F0FDF4] text-[#16A34A]">
                            <Clock class="w-6 h-6" />
                        </div>
                        <h3 class="feature-title">Tepat Waktu & Opsi Kilat</h3>
                        <p class="feature-desc">
                            Komitmen waktu yang pasti: Reguler 2–3 hari atau layanan Ekspres selesai di hari yang sama (taruh pagi ambil sore).
                        </p>
                    </div>

                    <div class="feature-card">
                        <div class="feature-icon bg-[#F0F9FF] text-[#0284C7]">
                            <Receipt class="w-6 h-6" />
                        </div>
                        <h3 class="feature-title">Nota Digital & Live Track</h3>
                        <p class="feature-desc">
                            Struk resmi otomatis terkirim ke WhatsApp Anda. Lacak posisi cucian di rak dan progres pengerjaan secara real-time.
                        </p>
                    </div>

                    <div class="feature-card">
                        <div class="feature-icon bg-[#FEF3C7] text-[#D97706]">
                            <Truck class="w-6 h-6" />
                        </div>
                        <h3 class="feature-title">Gratis Antar Jemput</h3>
                        <p class="feature-desc">
                            Bebas ongkos kirim penjemputan dan pengantaran cucian untuk radius hingga 3 km dari lokasi outlet kami.
                        </p>
                    </div>
                </div>
            </section>

            <!-- Layanan & Tarif Transparan (Pricing Section) -->
            <section id="tarif" class="section-container bg-[#FEF9C3] border-[3px] border-black shadow-[6px_6px_0px_#000] rounded-xl py-12 px-4 md:px-8 my-10">
                <div class="section-header text-center" data-reveal>
                    <p class="section-eyebrow">Daftar Harga Resmi</p>
                    <h2 class="section-title">Layanan Lengkap & Tarif Transparan</h2>
                    <p class="section-subtitle">
                        Harga jujur tanpa biaya siluman. Timbangan digital akurat dicatat langsung pada nota WhatsApp Anda.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5 mt-10" data-reveal>
                    <div
                        v-for="service in services"
                        :key="service.name"
                        class="service-pricing-card"
                        :class="{ 'service-pricing-card--popular': service.featured }"
                    >
                        <div v-if="service.featured" class="popular-ribbon">
                            Paling Diminati
                        </div>

                        <div class="service-pricing-card__header">
                            <span class="service-badge">{{ service.badge }}</span>
                            <h3 class="service-name">{{ service.name }}</h3>
                            <div class="service-price-row">
                                <span class="service-price">{{ service.price }}</span>
                                <span class="service-unit">/ {{ service.unit }}</span>
                            </div>
                            <span class="service-min">{{ service.minWeight }}</span>
                        </div>

                        <p class="service-desc">{{ service.desc }}</p>

                        <ul class="service-feature-list">
                            <li v-for="feat in service.features" :key="feat">
                                <Check class="w-4 h-4 text-[#16A34A] flex-shrink-0 mr-2 mt-0.5" />
                                <span>{{ feat }}</span>
                            </li>
                        </ul>

                        <div class="service-pricing-card__footer">
                            <a
                                :href="getServiceWhatsappUrl(service.name)"
                                target="_blank"
                                rel="noopener"
                                class="btn-service-order"
                                :class="{ 'btn-service-order--primary': service.featured }"
                            >
                                <MessageCircle class="w-4 h-4 mr-1.5" />
                                <span>Pesan {{ service.name }}</span>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Addon Speed & Delivery Notes -->
                <div class="service-addons-strip mt-8" data-reveal>
                    <div class="service-addon-item">
                        <Sparkles class="w-5 h-5 text-[#4F46E5] mr-2" />
                        <div>
                            <span class="font-semibold text-sm text-[#0A0A0A]">Opsi Ekspres (+Rp 2.000 / kg):</span>
                            <span class="text-sm text-[#55555E] ml-1">Taruh pagi, ambil sore di hari yang sama.</span>
                        </div>
                    </div>
                    <div class="service-addon-item">
                        <Truck class="w-5 h-5 text-[#16A34A] mr-2" />
                        <div>
                            <span class="font-semibold text-sm text-[#0A0A0A]">Gratis Ongkir Antar-Jemput:</span>
                            <span class="text-sm text-[#55555E] ml-1">Bebas biaya antar-jemput untuk jarak maksimal 3 km dari toko.</span>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Alur Cara Pesan (How It Works) -->
            <section id="cara-pesan" class="section-container">
                <div class="section-header text-center" data-reveal>
                    <p class="section-eyebrow">Alur Kerja Praktis</p>
                    <h2 class="section-title">4 Langkah Mudah Cucian Beres</h2>
                    <p class="section-subtitle">
                        Tanpa repot, pakaian Anda ditangani rapi dari penjemputan hingga siap pakai kembali.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mt-10 relative" data-reveal>
                    <div class="step-card">
                        <div class="step-number">01</div>
                        <h3 class="step-title">Taruh atau Minta Jemput</h3>
                        <p class="step-desc">
                            Bawa cucian langsung ke outlet Andik Laundry, atau cukup chat WhatsApp kami untuk request jemput di rumah.
                        </p>
                    </div>

                    <div class="step-card">
                        <div class="step-number">02</div>
                        <h3 class="step-title">Timbang & Terima Nota WA</h3>
                        <p class="step-desc">
                            Pakaian ditimbang transparan. Nomor nota resmi beserta rincian biaya langsung terkirim otomatis ke WhatsApp Anda.
                        </p>
                    </div>

                    <div class="step-card">
                        <div class="step-number">03</div>
                        <h3 class="step-title">Dicuci Higienis 1 Mesin</h3>
                        <p class="step-desc">
                            Pakaian disortir dan dicuci di 1 mesin tersendiri, dikeringkan, dan disetrika uap dengan teliti.
                        </p>
                    </div>

                    <div class="step-card">
                        <div class="step-number">04</div>
                        <h3 class="step-title">Siap Diambil atau Diantar</h3>
                        <p class="step-desc">
                            Notifikasi WA cucian selesai masuk beserta nomor rak penyimpanan. Siap Anda ambil atau kami antar ke rumah.
                        </p>
                    </div>
                </div>
            </section>

            <!-- Dedicated Tracking Section -->
            <section id="lacak" class="section-container bg-[#BAE6FD] border-[3px] border-black shadow-[6px_6px_0px_#000] rounded-xl p-8 md:p-12 my-10" data-reveal>
                <div class="max-w-2xl mx-auto text-center">
                    <span class="inline-flex items-center px-3 py-1 rounded-md text-xs font-black bg-[#FFEB3B] text-black border-2 border-black shadow-[2px_2px_0px_#000] mb-3">
                        <FileText class="w-3.5 h-3.5 mr-1.5" />
                        Live Status Tracking
                    </span>
                    <h2 class="text-2xl md:text-3xl font-extrabold text-black tracking-tight">
                        Cek Progres Cucian & Nota Digital Anda
                    </h2>
                    <p class="text-sm md:text-base text-gray-800 font-semibold mt-2 mb-6">
                        Pantau status pengerjaan secara real-time (Diproses → Selesai di Rak → Sudah Diambil) tanpa perlu login aplikasi.
                    </p>

                    <form class="flex flex-col sm:flex-row gap-3 max-w-lg mx-auto" @submit.prevent="submitOrderCheck">
                        <input
                            v-model="orderQuery"
                            type="text"
                            :placeholder="typewriterPlaceholder"
                            class="flex-1 h-12 px-4 rounded-lg border-[3px] border-black shadow-[3px_3px_0px_#000] bg-white text-sm font-bold text-black focus:outline-none focus:ring-0 focus:border-black"
                            @focus="onInputFocus"
                            @blur="onInputBlur"
                        />
                        <button
                            type="submit"
                            :disabled="isCheckingOrder"
                            class="h-12 px-6 rounded-lg bg-[#FFEB3B] hover:bg-[#FDE047] text-black font-extrabold text-sm border-[3px] border-black shadow-[3px_3px_0px_#000] active:translate-x-0.5 active:translate-y-0.5 active:shadow-[1px_1px_0px_#000] transition-all cursor-pointer flex items-center justify-center"
                        >
                            <Search class="w-4 h-4 mr-1.5 stroke-[3]" />
                            <span>{{ isCheckingOrder ? 'Mencari…' : 'Lacak Sekarang' }}</span>
                        </button>
                    </form>
                    <p v-if="orderQueryError" class="text-xs text-red-600 font-bold mt-2">
                        {{ orderQueryError }}
                    </p>
                    <p v-else class="text-xs text-gray-800 font-semibold mt-2">
                        Pesan WA terhapus? Cukup ketik nomor WhatsApp yang didaftarkan saat drop-off cucian.
                    </p>
                </div>
            </section>

            <!-- Metode Pembayaran Cashless & Tunai -->
            <section class="section-container" data-reveal>
                <div class="payment-banner">
                    <div class="payment-banner__copy">
                        <span class="text-xs font-semibold tracking-wider text-[#4F46E5] uppercase">Kemudahan Transaksi</span>
                        <h3 class="text-xl md:text-2xl font-bold text-[#0A0A0A] mt-1 mb-2">
                            Pembayaran Mudah: Tunai & Non-Tunai QRIS
                        </h3>
                        <p class="text-sm text-[#55555E] max-w-xl">
                            Bayar di tempat dengan uang tunai, atau bayar praktis dari rumah menggunakan scan QRIS resmi Andik Laundry yang tertera langsung di nota digital Anda.
                        </p>
                    </div>

                    <div class="payment-methods-grid">
                        <div class="payment-chip">
                            <QrCode class="w-4 h-4 text-[#4F46E5] mr-1.5" />
                            <span>QRIS Semua Bank & E-Wallet</span>
                        </div>
                        <div class="payment-chip">
                            <Banknote class="w-4 h-4 text-[#16A34A] mr-1.5" />
                            <span>Uang Tunai (Cash)</span>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Ulasan Pelanggan -->
            <section id="ulasan" class="section-container">
                <div class="section-header text-center" data-reveal>
                    <p class="section-eyebrow">Kepuasan Pelanggan</p>
                    <h2 class="section-title">Apa Kata Mereka Tentang Andik Laundry?</h2>
                    <p class="section-subtitle">
                        Nilai rata-rata ★ {{ ratingScore }} dari 5 berdasarkan ratusan pesanan pelanggan setia.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mt-8" data-reveal>
                    <div v-for="review in testimonials" :key="review.name" class="testimonial-card">
                        <div class="flex text-[#D97706] mb-3">
                            <Star v-for="i in review.stars" :key="i" class="w-4 h-4 fill-current" />
                        </div>
                        <blockquote class="text-sm text-[#41414A] leading-relaxed flex-1">
                            “{{ review.text }}”
                        </blockquote>
                        <div class="mt-4 pt-3 border-t border-[#E8E8EC] flex items-center justify-between text-xs">
                            <span class="font-bold text-[#0A0A0A]">{{ review.name }}</span>
                            <span class="text-[#6B6B6B] bg-[#F4F4F6] px-2 py-0.5 rounded">{{ review.area }}</span>
                        </div>
                    </div>
                </div>
            </section>

            <!-- FAQ Section (Pertanyaan Umum) -->
            <section id="faq" class="section-container max-w-3xl">
                <div class="section-header text-center" data-reveal>
                    <p class="section-eyebrow">Informasi Pelanggan</p>
                    <h2 class="section-title">Pertanyaan yang Sering Diajukan</h2>
                    <p class="section-subtitle">
                        Segala hal penting yang perlu Anda ketahui mengenai operasional layanan kami.
                    </p>
                </div>

                <div class="faq-accordion-list mt-8" data-reveal>
                    <details v-for="faq in faqs" :key="faq.q" class="faq-item">
                        <summary class="faq-question">
                            <span>{{ faq.q }}</span>
                            <ChevronDown class="w-4 h-4 text-[#6B6B6B] transition-transform duration-200 faq-icon" />
                        </summary>
                        <div class="faq-answer">
                            <p>{{ faq.a }}</p>
                        </div>
                    </details>
                </div>
            </section>

            <!-- Lokasi & Jam Buka Toko -->
            <section id="kontak" class="section-container" data-reveal>
                <div class="store-info-card">
                    <div class="store-info-col">
                        <span class="text-xs font-semibold text-[#4F46E5] uppercase tracking-wider">Kunjungi Outlet Kami</span>
                        <h3 class="text-xl md:text-2xl font-bold text-[#0A0A0A] mt-1">{{ storeName }}</h3>
                        <p class="text-sm text-[#55555E] mt-2 flex items-start">
                            <MapPin class="w-4 h-4 text-[#DC2626] mr-2 mt-0.5 flex-shrink-0" />
                            <span>{{ storeAddress }}</span>
                        </p>
                        <p class="text-sm text-[#55555E] mt-2 flex items-center">
                            <Clock class="w-4 h-4 text-[#16A34A] mr-2 flex-shrink-0" />
                            <span>Buka Setiap Hari: 07.00 – 21.00 WIB</span>
                        </p>

                        <div class="flex flex-wrap gap-3 mt-6">
                            <a
                                :href="whatsappUrl"
                                target="_blank"
                                rel="noopener"
                                class="btn-cta-primary text-sm py-2 px-4"
                            >
                                <MessageCircle class="w-4 h-4 mr-1.5" />
                                <span>Hubungi WhatsApp Toko</span>
                            </a>
                            <a
                                :href="`https://maps.google.com/?q=${encodeURIComponent(storeAddress)}`"
                                target="_blank"
                                rel="noopener"
                                class="btn-cta-secondary text-sm py-2 px-4"
                            >
                                <MapPin class="w-4 h-4 mr-1.5" />
                                <span>Buka Google Maps</span>
                            </a>
                        </div>
                    </div>
                </div>
            </section>
        </main>

        <!-- Floating Mobile WhatsApp CTA -->
        <aside class="mobile-floating-wa" aria-label="Pesan cepat via WhatsApp">
            <a
                :href="whatsappUrl"
                target="_blank"
                rel="noopener"
                class="mobile-floating-wa__btn"
                title="Pesan Antar-Jemput via WhatsApp"
            >
                <MessageCircle class="w-6 h-6 text-white" />
                <span class="text-xs font-bold text-white pr-1">Pesan WA</span>
            </a>
        </aside>

        <!-- Modern Footer -->
        <footer class="site-footer">
            <div class="site-footer__inner">
                <div class="site-footer__brand">
                    <Link href="/" class="wordmark">
                        <span class="wordmark__name">andik<span class="wordmark__accent">.</span></span>
                        <span class="wordmark__caption">LAUNDRY</span>
                    </Link>
                    <p class="text-xs text-[#6B6B6B] mt-2 max-w-sm">
                        Layanan binatu harian higienis dan terpercaya. 1 mesin 1 pelanggan dengan nota digital terpadu dan pembayaran QRIS.
                    </p>
                </div>

                <div class="site-footer__links">
                    <span class="text-xs font-semibold text-[#0A0A0A] uppercase tracking-wider">Navigasi</span>
                    <a href="#keunggulan">Keunggulan</a>
                    <a href="#tarif">Daftar Tarif</a>
                    <a href="#cara-pesan">Cara Pesan</a>
                    <a href="#lacak">Lacak Nota</a>
                    <a href="#faq">FAQ</a>
                </div>

                <div class="site-footer__staff-panel">
                    <span class="text-xs font-semibold text-[#0A0A0A] uppercase tracking-wider">Akses Internal</span>
                    <p class="text-xs text-[#6B6B6B] mb-3">Portal login operasional kasir & manajemen toko.</p>
                    <Link v-if="canLogin" href="/login" class="staff-link text-xs py-2 px-4">
                        Masuk Dashboard Owner
                    </Link>
                </div>
            </div>

            <div class="site-footer__bottom">
                <p>© 2026 {{ storeName }}. Hak cipta dilindungi. Dirancang untuk kebersihan & kenyamanan pakaian Anda.</p>
            </div>
        </footer>
    </div>
</template>

<style scoped>
:global(*),
:global(*::before),
:global(*::after) {
    box-sizing: border-box;
}

:global(html) {
    scroll-behavior: smooth;
    scroll-padding-top: 5rem;
}

:global(body) {
    background-color: #FFFDF5;
    color: #000000;
}

.skip-link {
    position: absolute;
    top: 0.5rem;
    left: 0.5rem;
    z-index: 50;
    padding: 0.5rem 1rem;
    background: #000000;
    color: #FFEB3B;
    border: 2px solid #000000;
    box-shadow: 3px 3px 0 #000000;
    border-radius: 6px;
    font-size: 0.875rem;
    font-weight: 800;
    transform: translateY(-200%);
    transition: transform 150ms ease;
}

.skip-link:focus {
    transform: translateY(0);
}

/* Site Header */
.site-header {
    position: sticky;
    top: 0;
    z-index: 40;
    background: #FFEB3B;
    border-bottom: 3.5px solid #000000;
}

.site-header__inner {
    display: flex;
    align-items: center;
    justify-content: space-between;
    width: min(100% - 2rem, 1200px);
    margin-inline: auto;
    min-height: 60px;
}

.wordmark {
    display: inline-flex;
    flex-direction: column;
    text-decoration: none;
    color: #000000;
}

.wordmark__name {
    font-size: 1.45rem;
    font-weight: 900;
    letter-spacing: -0.05em;
    line-height: 0.95;
    color: #000000;
}

.wordmark__accent {
    color: #FF5252;
}

.wordmark__caption {
    display: inline-block;
    width: fit-content;
    font-size: 0.55rem;
    font-weight: 800;
    letter-spacing: 0.22em;
    margin-top: 0.15rem;
    background: #000000;
    color: #FFEB3B;
    padding: 1px 6px;
    border-radius: 4px;
}

.site-nav {
    display: flex;
    align-items: center;
    gap: 1.5rem;
}

.site-nav a:not(.nav-btn-wa):not(.staff-link) {
    font-size: 0.875rem;
    font-weight: 800;
    color: #000000;
    text-decoration: none;
    padding: 0.25rem 0.5rem;
    border-radius: 6px;
    transition: all 120ms ease;
}

.site-nav a:not(.nav-btn-wa):not(.staff-link):hover {
    background: #000000;
    color: #FFEB3B;
}

.site-nav__actions {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-left: 0.5rem;
}

.nav-btn-wa {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 40px;
    padding: 0.4rem 1rem;
    border-radius: 8px;
    background: #22C55E;
    color: #000000;
    border: 2.5px solid #000000;
    box-shadow: 3px 3px 0 #000000;
    font-size: 0.85rem;
    font-weight: 800;
    text-decoration: none;
    transition: all 120ms ease;
}

.nav-btn-wa:hover {
    transform: translate(-1px, -1px);
    box-shadow: 4px 4px 0 #000000;
}

.nav-btn-wa:active {
    transform: translate(2px, 2px);
    box-shadow: 1px 1px 0 #000000;
}

.staff-link {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 40px;
    padding: 0.4rem 1rem;
    border: 2.5px solid #000000;
    border-radius: 8px;
    box-shadow: 3px 3px 0 #000000;
    background: #FFFFFF;
    color: #000000;
    font-size: 0.85rem;
    font-weight: 800;
    text-decoration: none;
    transition: all 120ms ease;
}

.staff-link:hover {
    background: #000000;
    color: #FFEB3B;
    transform: translate(-1px, -1px);
    box-shadow: 4px 4px 0 #000000;
}

.staff-link:active {
    transform: translate(2px, 2px);
    box-shadow: 1px 1px 0 #000000;
}

.menu-toggle {
    display: none;
    width: 44px;
    height: 44px;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 5px;
    border: 2.5px solid #000000;
    box-shadow: 3px 3px 0 #000000;
    border-radius: 8px;
    background: #FFFFFF;
    cursor: pointer;
}

.menu-toggle span {
    width: 20px;
    height: 2.5px;
    background: #000000;
    transition: all 180ms ease;
}

/* Hero Wrapper */
.hero-wrapper {
    position: relative;
    background: #FFF9D2;
    border-bottom: 3.5px solid #000000;
    overflow: hidden;
}

.hero-section {
    display: grid;
    grid-template-columns: minmax(0, 1.08fr) minmax(0, 0.92fr);
    align-items: center;
    gap: clamp(1.75rem, 3.5vw, 3.5rem);
    width: min(100% - 2rem, 1200px);
    margin-inline: auto;
    padding-block: clamp(2.5rem, 5vh, 4.25rem);
}

.hero-section__copy {
    max-width: 620px;
}

.hero-badge {
    display: inline-flex;
    align-items: center;
    padding: 0.4rem 0.9rem;
    border-radius: 8px;
    background: #2196F3;
    border: 2.5px solid #000000;
    box-shadow: 3px 3px 0 #000000;
    color: #FFFFFF;
    font-size: 0.75rem;
    font-weight: 800;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    margin-bottom: 1rem;
}

.hero-badge__dot {
    width: 8px;
    height: 8px;
    border-radius: 9999px;
    background: #FFEB3B;
    border: 1.5px solid #000000;
    margin-right: 0.5rem;
}

.hero-title {
    font-size: clamp(2.25rem, 4.2vw, 3.75rem);
    font-weight: 900;
    letter-spacing: -0.04em;
    line-height: 1.08;
    color: #000000;
    margin: 0;
}

.hero-title__highlight {
    background: #FF5252;
    color: #FFFFFF;
    border: 2.5px solid #000000;
    box-shadow: 3px 3px 0 #000000;
    padding: 2px 10px;
    border-radius: 8px;
    display: inline-block;
    margin-top: 0.25rem;
}

.hero-description {
    font-size: clamp(0.95rem, 1.1vw, 1.1rem);
    line-height: 1.6;
    color: #1F2937;
    font-weight: 500;
    margin: 1.25rem 0 1.5rem;
    max-width: 540px;
}

.hero-cta-group {
    display: flex;
    flex-wrap: wrap;
    gap: 0.85rem;
    align-items: center;
}

.btn-cta-primary {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 48px;
    padding: 0.75rem 1.45rem;
    border-radius: 8px;
    background: #22C55E;
    color: #000000;
    border: 3px solid #000000;
    box-shadow: 4px 4px 0 #000000;
    font-size: 0.925rem;
    font-weight: 800;
    text-decoration: none;
    transition: all 120ms ease;
}

.btn-cta-primary:hover {
    transform: translate(-2px, -2px);
    box-shadow: 6px 6px 0 #000000;
}

.btn-cta-primary:active {
    transform: translate(2px, 2px);
    box-shadow: 2px 2px 0 #000000;
}

.btn-cta-secondary {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 48px;
    padding: 0.75rem 1.35rem;
    border-radius: 8px;
    background: #FFEB3B;
    border: 3px solid #000000;
    box-shadow: 4px 4px 0 #000000;
    color: #000000;
    font-size: 0.925rem;
    font-weight: 800;
    text-decoration: none;
    transition: all 120ms ease;
}

.btn-cta-secondary:hover {
    background: #FFFFFF;
    transform: translate(-2px, -2px);
    box-shadow: 6px 6px 0 #000000;
}

.btn-cta-secondary:active {
    transform: translate(2px, 2px);
    box-shadow: 2px 2px 0 #000000;
}

.hero-guarantees {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
    margin-top: 1.75rem;
    padding-top: 1.25rem;
    border-top: 2px dashed #000000;
}

.hero-guarantee-item {
    display: flex;
    align-items: center;
    font-size: 0.8rem;
    color: #000000;
    font-weight: 700;
    background: #FFFFFF;
    border: 2px solid #000000;
    box-shadow: 2px 2px 0 #000000;
    border-radius: 6px;
    padding: 0.35rem 0.65rem;
}

/* Hero Right Column: Interactive Hub */
.hero-section__interactive {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    align-self: center;
    max-width: 490px;
    width: 100%;
}

/* Card 1: Form Lacak Panel */
.hero-track-panel {
    background: #FFFFFF;
    border: 3px solid #000000;
    border-radius: 8px;
    padding: 1.35rem;
    box-shadow: 5px 5px 0 #000000;
}

.hero-track-panel__header {
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.hero-track-panel__row {
    display: flex;
    gap: 0.5rem;
}

.hero-track-panel__input {
    flex: 1;
    min-width: 0;
    min-height: 46px;
    padding: 0.5rem 0.85rem;
    border: 2.5px solid #000000;
    box-shadow: 3px 3px 0 #000000;
    border-radius: 8px;
    font-size: 0.875rem;
    font-weight: 600;
    color: #000000;
    background: #FFFFFF;
    transition: all 120ms ease;
}

.hero-track-panel__input:focus {
    outline: none;
    border-color: #000000;
    box-shadow: 4px 4px 0 #2196F3;
}

.hero-track-panel__submit {
    flex-shrink: 0;
    min-height: 46px;
    padding: 0.5rem 1.25rem;
    border: 2.5px solid #000000;
    border-radius: 8px;
    box-shadow: 3px 3px 0 #000000;
    background: #2196F3;
    color: #FFFFFF;
    font-size: 0.875rem;
    font-weight: 800;
    cursor: pointer;
    transition: all 120ms ease;
}

.hero-track-panel__submit:hover {
    transform: translate(-1px, -1px);
    box-shadow: 4px 4px 0 #000000;
}

.hero-track-panel__submit:active {
    transform: translate(2px, 2px);
    box-shadow: 1px 1px 0 #000000;
}

.hero-track-panel__submit:disabled {
    background: #93C5FD;
    cursor: wait;
}

.hero-track-panel__hint,
.hero-track-panel__error {
    font-size: 0.75rem;
    margin-top: 0.5rem;
    line-height: 1.45;
    font-weight: 600;
}

.hero-track-panel__hint {
    color: #4B5563;
}

.hero-track-panel__error {
    color: #DC2626;
    font-weight: 700;
}

/* Card 2: Stats Panel (2x2 Grid) */
.hero-stats-panel {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 3px;
    background: #000000;
    border: 3px solid #000000;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 5px 5px 0 #000000;
}

.hero-stats-panel__item {
    background: #FFFFFF;
    padding: 1rem 1.15rem;
}

.hero-stats-panel__item dt {
    font-size: 0.72rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #4B5563;
}

.hero-stats-panel__item dd {
    font-size: 1.45rem;
    font-weight: 900;
    color: #000000;
    margin: 0.25rem 0 0;
    letter-spacing: -0.03em;
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
}

/* Trust Pills Row */
.hero-trust-pills {
    display: flex;
    flex-wrap: wrap;
    gap: 0.6rem;
}

.trust-pill-item {
    display: inline-flex;
    align-items: center;
    padding: 0.45rem 0.85rem;
    border-radius: 8px;
    background: #FFEB3B;
    border: 2px solid #000000;
    box-shadow: 3px 3px 0 #000000;
    font-size: 0.75rem;
    font-weight: 800;
    color: #000000;
}

/* Sections Shared */
.section-container {
    width: min(100% - 2rem, 1200px);
    margin-inline: auto;
    padding-block: clamp(2.5rem, 5vw, 4.5rem);
}

.section-header {
    max-width: 680px;
    margin-inline: auto;
}

.section-eyebrow {
    font-size: 0.75rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    color: #000000;
    background: #FFEB3B;
    border: 2px solid #000000;
    box-shadow: 2px 2px 0 #000000;
    padding: 2px 10px;
    border-radius: 6px;
    display: inline-block;
    margin-bottom: 0.6rem;
}

.section-title {
    font-size: clamp(2rem, 3.2vw, 2.75rem);
    font-weight: 900;
    letter-spacing: -0.04em;
    line-height: 1.1;
    color: #000000;
    margin: 0;
}

.section-subtitle {
    font-size: 0.975rem;
    color: #374151;
    font-weight: 500;
    line-height: 1.6;
    margin-top: 0.6rem;
}

/* Feature Cards */
.feature-card {
    background: #FFFFFF;
    border: 3px solid #000000;
    border-radius: 8px;
    box-shadow: 4px 4px 0 #000000;
    padding: 1.5rem 1.25rem;
    display: flex;
    flex-direction: column;
    transition: transform 150ms ease, box-shadow 150ms ease;
}

.feature-card:hover {
    transform: translate(-3px, -3px);
    box-shadow: 7px 7px 0 #000000;
}

.feature-icon {
    width: 48px;
    height: 48px;
    border-radius: 8px;
    border: 2.5px solid #000000;
    box-shadow: 3px 3px 0 #000000;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 1rem;
}

.feature-title {
    font-size: 1.15rem;
    font-weight: 800;
    color: #000000;
    margin: 0 0 0.5rem;
    letter-spacing: -0.02em;
}

.feature-desc {
    font-size: 0.875rem;
    color: #374151;
    line-height: 1.55;
    margin: 0;
    font-weight: 500;
}

/* Service Pricing Cards */
.service-pricing-card {
    background: #FFFFFF;
    border: 3px solid #000000;
    border-radius: 8px;
    box-shadow: 4px 4px 0 #000000;
    padding: 1.5rem 1.25rem;
    display: flex;
    flex-direction: column;
    position: relative;
    transition: transform 150ms ease, box-shadow 150ms ease;
}

.service-pricing-card:hover {
    transform: translate(-3px, -3px);
    box-shadow: 7px 7px 0 #000000;
}

.service-pricing-card--popular {
    background: #FEF08A;
    border: 3.5px solid #000000;
    box-shadow: 6px 6px 0 #000000;
}

.popular-ribbon {
    position: absolute;
    top: -12px;
    right: 14px;
    background: #FF5252;
    color: #FFFFFF;
    font-size: 0.72rem;
    font-weight: 800;
    padding: 3px 12px;
    border: 2px solid #000000;
    box-shadow: 2px 2px 0 #000000;
    border-radius: 6px;
    letter-spacing: 0.04em;
    text-transform: uppercase;
}

.service-pricing-card__header {
    border-bottom: 2px dashed #000000;
    padding-bottom: 1rem;
}

.service-badge {
    display: inline-block;
    font-size: 0.72rem;
    font-weight: 800;
    color: #000000;
    background: #FFEB3B;
    border: 1.5px solid #000000;
    box-shadow: 1.5px 1.5px 0 #000000;
    padding: 2px 8px;
    border-radius: 4px;
    margin-bottom: 0.5rem;
}

.service-name {
    font-size: 1.3rem;
    font-weight: 900;
    color: #000000;
    margin: 0 0 0.5rem;
    letter-spacing: -0.02em;
}

.service-price-row {
    display: flex;
    align-items: baseline;
    gap: 0.35rem;
}

.service-price {
    font-size: 1.85rem;
    font-weight: 900;
    color: #000000;
    letter-spacing: -0.04em;
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
}

.service-unit {
    font-size: 0.9rem;
    font-weight: 700;
    color: #000000;
}

.service-min {
    display: block;
    font-size: 0.75rem;
    font-weight: 700;
    color: #4B5563;
    margin-top: 0.25rem;
}

.service-desc {
    font-size: 0.875rem;
    color: #1F2937;
    font-weight: 500;
    line-height: 1.5;
    margin: 1rem 0;
}

.service-feature-list {
    list-style: none;
    padding: 0;
    margin: 0 0 1.5rem;
    display: flex;
    flex-direction: column;
    gap: 0.65rem;
    flex: 1;
}

.service-feature-list li {
    font-size: 0.825rem;
    font-weight: 600;
    color: #000000;
    display: flex;
    align-items: flex-start;
    line-height: 1.4;
}

.btn-service-order {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    min-height: 44px;
    border-radius: 8px;
    background: #FFEB3B;
    color: #000000;
    font-size: 0.85rem;
    font-weight: 800;
    text-decoration: none;
    border: 2.5px solid #000000;
    box-shadow: 3px 3px 0 #000000;
    transition: all 120ms ease;
}

.btn-service-order:hover {
    transform: translate(-1px, -1px);
    box-shadow: 4px 4px 0 #000000;
}

.btn-service-order:active {
    transform: translate(2px, 2px);
    box-shadow: 1px 1px 0 #000000;
}

.btn-service-order--primary {
    background: #22C55E;
    color: #000000;
}

/* Service Addons Strip */
.service-addons-strip {
    display: flex;
    flex-wrap: wrap;
    gap: 1.25rem;
    justify-content: center;
    background: #FFFFFF;
    border: 3px solid #000000;
    box-shadow: 4px 4px 0 #000000;
    border-radius: 8px;
    padding: 1.15rem 1.5rem;
}

.service-addon-item {
    display: flex;
    align-items: center;
}

/* Step Cards */
.step-card {
    background: #FFFFFF;
    border: 3px solid #000000;
    border-radius: 8px;
    box-shadow: 4px 4px 0 #000000;
    padding: 1.5rem 1.25rem;
    position: relative;
    transition: transform 150ms ease, box-shadow 150ms ease;
}

.step-card:hover {
    transform: translate(-2px, -2px);
    box-shadow: 6px 6px 0 #000000;
}

.step-number {
    display: inline-block;
    font-size: 1.25rem;
    font-weight: 900;
    background: #000000;
    color: #FFEB3B;
    border: 2px solid #000000;
    box-shadow: 2px 2px 0 #000000;
    padding: 3px 10px;
    border-radius: 6px;
    line-height: 1;
    margin-bottom: 0.85rem;
}

.step-title {
    font-size: 1.1rem;
    font-weight: 800;
    color: #000000;
    margin: 0 0 0.5rem;
}

.step-desc {
    font-size: 0.85rem;
    color: #374151;
    font-weight: 500;
    line-height: 1.55;
    margin: 0;
}

/* Payment Banner */
.payment-banner {
    background: #FED7AA;
    border: 3px solid #000000;
    box-shadow: 5px 5px 0 #000000;
    border-radius: 8px;
    padding: 1.75rem 2rem;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 1.5rem;
}

.payment-methods-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
}

.payment-chip {
    display: inline-flex;
    align-items: center;
    padding: 0.65rem 1.15rem;
    border-radius: 8px;
    background: #FFFFFF;
    border: 2.5px solid #000000;
    box-shadow: 3px 3px 0 #000000;
    font-size: 0.875rem;
    font-weight: 800;
    color: #000000;
}

/* Testimonials */
.testimonial-card {
    background: #FFFFFF;
    border: 3px solid #000000;
    border-radius: 8px;
    box-shadow: 4px 4px 0 #000000;
    padding: 1.5rem;
    display: flex;
    flex-direction: column;
}

/* FAQ Accordion */
.faq-accordion-list {
    display: flex;
    flex-direction: column;
    gap: 0.85rem;
}

.faq-item {
    background: #FFFFFF;
    border: 3px solid #000000;
    box-shadow: 3px 3px 0 #000000;
    border-radius: 8px;
    overflow: hidden;
    transition: all 150ms ease;
}

.faq-question {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1.1rem 1.35rem;
    font-size: 0.975rem;
    font-weight: 800;
    color: #000000;
    cursor: pointer;
    list-style: none;
    min-height: 48px;
}

.faq-question::-webkit-details-marker {
    display: none;
}

.faq-item[open] {
    background: #FFFBEB;
    box-shadow: 5px 5px 0 #000000;
}

.faq-item[open] .faq-question {
    border-bottom: 2px solid #000000;
    background: #FEF08A;
}

.faq-item[open] .faq-icon {
    transform: rotate(180deg);
}

.faq-answer {
    padding: 1.1rem 1.35rem;
    font-size: 0.9rem;
    color: #1F2937;
    font-weight: 500;
    line-height: 1.6;
}

/* Store Info Card */
.store-info-card {
    background: #FFFFFF;
    border: 3.5px solid #000000;
    border-radius: 10px;
    box-shadow: 6px 6px 0 #000000;
    padding: 2rem;
}

/* Floating Mobile WhatsApp */
.mobile-floating-wa {
    display: none;
}

@media (max-width: 768px) {
    .mobile-floating-wa {
        display: block;
        position: fixed;
        bottom: calc(env(safe-area-inset-bottom, 0px) + 1.25rem);
        right: 1.25rem;
        z-index: 50;
    }

    .mobile-floating-wa__btn {
        display: flex;
        align-items: center;
        gap: 0.4rem;
        background: #22C55E;
        color: #000000;
        border: 3px solid #000000;
        box-shadow: 4px 4px 0 #000000;
        padding: 0.65rem 1rem 0.65rem 0.85rem;
        border-radius: 9999px;
        text-decoration: none;
        min-height: 48px;
        transition: transform 120ms ease;
    }

    .mobile-floating-wa__btn:hover {
        transform: scale(1.05);
    }
}

/* Footer */
.site-footer {
    border-top: 3.5px solid #000000;
    background: #FFEB3B;
    color: #000000;
    margin-top: 2rem;
    padding-top: 3rem;
}

.site-footer__inner {
    width: min(100% - 2rem, 1200px);
    margin-inline: auto;
    display: grid;
    grid-template-columns: 2fr 1fr 1.5fr;
    gap: 2.5rem;
    padding-bottom: 2.5rem;
}

.site-footer__links {
    display: flex;
    flex-direction: column;
    gap: 0.6rem;
}

.site-footer__links a {
    font-size: 0.875rem;
    font-weight: 700;
    color: #000000;
    text-decoration: none;
    transition: color 150ms ease;
}

.site-footer__links a:hover {
    color: #FF5252;
    text-decoration: underline;
}

.site-footer__staff-panel {
    display: flex;
    flex-direction: column;
}

.site-footer__bottom {
    border-top: 2px solid #000000;
    padding-block: 1.25rem;
    text-align: center;
    font-size: 0.8rem;
    font-weight: 700;
    color: #000000;
}

/* Responsive Overrides */
@media (max-width: 900px) {
    .hero-section {
        grid-template-columns: 1fr;
        gap: 2rem;
    }

    .hero-section__interactive {
        max-width: 100%;
    }

    .site-footer__inner {
        grid-template-columns: 1fr 1fr;
        gap: 2rem;
    }
}

@media (max-width: 680px) {
    .menu-toggle {
        display: flex;
    }

    .site-nav {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        background: #FFEB3B;
        border-bottom: 3px solid #000000;
        flex-direction: column;
        align-items: stretch;
        padding: 1rem;
        gap: 0;
        display: none;
    }

    .site-nav--open {
        display: flex;
    }

    .site-nav a:not(.nav-btn-wa):not(.staff-link) {
        display: flex;
        align-items: center;
        min-height: 48px;
        border-bottom: 2px solid #000000;
    }

    .site-nav__actions {
        flex-direction: column;
        margin-top: 1rem;
        margin-left: 0;
        gap: 0.5rem;
    }

    .nav-btn-wa,
    .staff-link {
        width: 100%;
        min-height: 44px;
    }

    .hero-title {
        font-size: clamp(2rem, 8vw, 2.6rem);
    }

    .hero-cta-group {
        flex-direction: column;
        align-items: stretch;
    }

    .btn-cta-primary,
    .btn-cta-secondary {
        width: 100%;
    }

    .hero-stats-panel {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .site-footer__inner {
        grid-template-columns: 1fr;
    }
}

/* Data Reveal Animations */
[data-reveal] {
    opacity: 0;
    transform: translateY(12px);
    transition: opacity 350ms cubic-bezier(0.16, 1, 0.3, 1), transform 350ms cubic-bezier(0.16, 1, 0.3, 1);
}

[data-reveal].is-revealed {
    opacity: 1;
    transform: translateY(0);
}

@media (prefers-reduced-motion: reduce) {
    [data-reveal] {
        opacity: 1 !important;
        transform: none !important;
        transition: none !important;
    }

    *,
    *::before,
    *::after {
        scroll-behavior: auto !important;
        transition-duration: 0.01ms !important;
    }
}
</style>
