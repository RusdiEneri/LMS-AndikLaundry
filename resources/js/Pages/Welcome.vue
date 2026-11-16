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
const customersLabel = computed(() => `${stats.value.customers.toLocaleString('id-ID')}+`);
const ordersLabel = computed(() => `${stats.value.orders.toLocaleString('id-ID')}+`);
const laundryKgLabel = computed(() => `${stats.value.laundry_kg.toLocaleString('id-ID')} kg`);
const ratingScore = '4,9';

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
});
</script>

<template>
    <Head title="Andik Laundry | Bersih, Wangi & Dirawat Teliti Tanpa Dicampur">
        <meta
            name="description"
            content="Layanan binatu modern kiloan dan karpet terpercaya. 1 mesin 1 pelanggan, nota digital WhatsApp, pelacakan live, pembayaran QRIS, dan gratis antar jemput."
        />
    </Head>

    <div class="landing-page min-h-screen bg-[#FAFAFA] text-[#0A0A0A]">
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
            <!-- Hero Section -->
            <section id="hero" class="hero-section">
                <div class="hero-section__copy" data-reveal>
                    <div class="hero-badge">
                        <span class="hero-badge__dot"></span>
                        <span>1 Mesin 1 Pelanggan • Tidak Dicampur</span>
                    </div>

                    <h1 class="hero-title">
                        Pakaian Bersih, Wangi & Rapi.<br />
                        <span class="text-[#4F46E5]">Dirawat Teliti Tanpa Dicampur.</span>
                    </h1>

                    <p class="hero-description">
                        Layanan binatu modern harian kiloan & karpet profesional. Terima nota digital resmi dan pantau progres cucian Anda secara live via WhatsApp tanpa perlu login.
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
                            <span>Pesan Antar-Jemput</span>
                        </a>
                        <a href="#tarif" class="btn-cta-secondary">
                            <span>Lihat Tarif Layanan</span>
                            <ArrowRight class="w-4 h-4 ml-1.5" />
                        </a>
                    </div>

                    <!-- Form Lacak Nota Cepat -->
                    <form class="hero-track-card" aria-label="Cek pesanan nota" @submit.prevent="submitOrderCheck">
                        <div class="hero-track-card__header">
                            <Search class="w-4 h-4 text-[#4F46E5] mr-1.5" />
                            <span class="font-semibold text-xs tracking-wider uppercase text-[#55555E]">Lacak Progres Cucian</span>
                        </div>
                        <div class="hero-track-card__row">
                            <input
                                id="hero-order-query"
                                v-model="orderQuery"
                                class="hero-track-card__input"
                                type="text"
                                name="q"
                                inputmode="text"
                                autocomplete="off"
                                placeholder="Nomor nota (AL-...) atau No. HP"
                                aria-label="Nomor nota atau nomor HP"
                            />
                            <button class="hero-track-card__submit" type="submit" :disabled="isCheckingOrder">
                                {{ isCheckingOrder ? 'Mencari…' : 'Cek Status' }}
                            </button>
                        </div>
                        <p v-if="orderQueryError" class="hero-track-card__error" role="alert">
                            {{ orderQueryError }}
                        </p>
                        <p v-else class="hero-track-card__hint">
                            Contoh: AL-20261115-001 atau 08123456789. Tanpa perlu login.
                        </p>
                    </form>

                    <!-- Trust Stats Bar -->
                    <dl class="hero-stats-row" aria-label="Statistik Andik Laundry">
                        <div class="hero-stats-col">
                            <dt>Pelanggan Setia</dt>
                            <dd>{{ customersLabel }}</dd>
                        </div>
                        <div class="hero-stats-col">
                            <dt>Pesanan Tuntas</dt>
                            <dd>{{ ordersLabel }}</dd>
                        </div>
                        <div class="hero-stats-col">
                            <dt>Cucian Bersih</dt>
                            <dd>{{ laundryKgLabel }}</dd>
                        </div>
                        <div class="hero-stats-col">
                            <dt>Rating Pelanggan</dt>
                            <dd class="text-[#D97706]">
                                ★ {{ ratingScore }}<span class="text-xs text-[#6B6B6B] font-normal">/5</span>
                            </dd>
                        </div>
                    </dl>
                </div>

                <!-- Hero Visual Media -->
                <div class="hero-section__visual" data-reveal>
                    <div class="hero-visual-card">
                        <img
                            src="/images/hero-laundry.jpg"
                            alt="Pakaian bersih rapi tergantung segar di bawah sinar matahari"
                            width="960"
                            height="640"
                            loading="eager"
                            class="hero-visual-img"
                        />
                        <div class="hero-visual-overlay">
                            <div class="flex items-center gap-2">
                                <ShieldCheck class="w-5 h-5 text-[#10B981]" />
                                <span class="text-xs font-semibold text-[#0A0A0A]">Higienis Terjamin • 1 Mesin 1 Pelanggan</span>
                            </div>
                            <span class="text-[11px] text-[#55555E]">Dicuci terpisah dengan deterjen ramah serat kain.</span>
                        </div>
                    </div>
                </div>
            </section>

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
            <section id="tarif" class="section-container bg-[#F4F4F6] py-14 rounded-2xl my-8">
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
            <section id="lacak" class="section-container bg-[#EEF2FF] p-8 md:p-12 rounded-2xl my-8" data-reveal>
                <div class="max-w-2xl mx-auto text-center">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-[#E0E7FF] text-[#4338CA] mb-3">
                        <FileText class="w-3.5 h-3.5 mr-1.5" />
                        Live Status Tracking
                    </span>
                    <h2 class="text-2xl md:text-3xl font-bold text-[#0A0A0A] tracking-tight">
                        Cek Progres Cucian & Nota Digital Anda
                    </h2>
                    <p class="text-sm md:text-base text-[#55555E] mt-2 mb-6">
                        Pantau status pengerjaan secara real-time (Diproses → Selesai di Rak → Sudah Diambil) tanpa perlu login aplikasi.
                    </p>

                    <form class="flex flex-col sm:flex-row gap-2 max-w-lg mx-auto" @submit.prevent="submitOrderCheck">
                        <input
                            v-model="orderQuery"
                            type="text"
                            placeholder="Ketik No. Nota (AL-...) atau No. HP"
                            class="flex-1 h-12 px-4 rounded-md border border-[#D7D7DF] bg-white text-sm text-[#0A0A0A] focus:outline-none focus:ring-2 focus:ring-[#6366F1]"
                        />
                        <button
                            type="submit"
                            :disabled="isCheckingOrder"
                            class="h-12 px-6 rounded-md bg-[#4F46E5] hover:bg-[#4338CA] text-white font-semibold text-sm transition-colors cursor-pointer flex items-center justify-center"
                        >
                            <Search class="w-4 h-4 mr-1.5" />
                            <span>{{ isCheckingOrder ? 'Mencari…' : 'Lacak Sekarang' }}</span>
                        </button>
                    </form>
                    <p v-if="orderQueryError" class="text-xs text-[#DC2626] font-medium mt-2">
                        {{ orderQueryError }}
                    </p>
                    <p v-else class="text-xs text-[#6B6B6B] mt-2">
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

.skip-link {
    position: absolute;
    top: 0.5rem;
    left: 0.5rem;
    z-index: 50;
    padding: 0.5rem 1rem;
    background: #4F46E5;
    color: #fff;
    border-radius: 6px;
    font-size: 0.875rem;
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
    background: rgba(250, 250, 250, 0.94);
    backdrop-filter: blur(12px);
    border-bottom: 1px solid #E8E8EC;
}

.site-header__inner {
    display: flex;
    align-items: center;
    justify-content: space-between;
    width: min(100% - 2rem, 1200px);
    margin-inline: auto;
    min-height: 56px;
}

.wordmark {
    display: inline-flex;
    flex-direction: column;
    text-decoration: none;
    color: #0A0A0A;
}

.wordmark__name {
    font-size: 1.35rem;
    font-weight: 700;
    letter-spacing: -0.06em;
    line-height: 0.95;
}

.wordmark__accent {
    color: #4F46E5;
}

.wordmark__caption {
    font-size: 0.55rem;
    font-weight: 600;
    letter-spacing: 0.22em;
    margin-top: 0.15rem;
    color: #55555E;
}

.site-nav {
    display: flex;
    align-items: center;
    gap: 1.5rem;
}

.site-nav a:not(.nav-btn-wa):not(.staff-link) {
    font-size: 0.875rem;
    font-weight: 500;
    color: #41414A;
    text-decoration: none;
    transition: color 150ms ease;
}

.site-nav a:not(.nav-btn-wa):not(.staff-link):hover {
    color: #4F46E5;
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
    min-height: 38px;
    padding: 0.4rem 0.9rem;
    border-radius: 6px;
    background: #16A34A;
    color: #fff;
    font-size: 0.825rem;
    font-weight: 600;
    text-decoration: none;
    transition: background-color 150ms ease;
}

.nav-btn-wa:hover {
    background: #15803D;
}

.staff-link {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 38px;
    padding: 0.4rem 0.9rem;
    border: 1px solid #D7D7DF;
    border-radius: 6px;
    background: #fff;
    color: #0A0A0A;
    font-size: 0.825rem;
    font-weight: 600;
    text-decoration: none;
    transition: all 150ms ease;
}

.staff-link:hover {
    border-color: #4F46E5;
    color: #4F46E5;
}

.menu-toggle {
    display: none;
    width: 44px;
    height: 44px;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 5px;
    border: 1px solid #D7D7DF;
    border-radius: 6px;
    background: transparent;
    cursor: pointer;
}

.menu-toggle span {
    width: 20px;
    height: 2px;
    background: #0A0A0A;
    transition: all 180ms ease;
}

/* Hero Section */
.hero-section {
    display: grid;
    grid-template-columns: minmax(0, 1.1fr) minmax(0, 0.9fr);
    align-items: center;
    gap: clamp(1.5rem, 3.5vw, 3rem);
    width: min(100% - 2rem, 1200px);
    margin-inline: auto;
    padding-block: clamp(1.5rem, 3vh, 2.5rem);
}

.hero-section__copy {
    max-width: 620px;
}

.hero-badge {
    display: inline-flex;
    align-items: center;
    padding: 0.35rem 0.85rem;
    border-radius: 9999px;
    background: #EEF2FF;
    border: 1px solid #E0E7FF;
    color: #4338CA;
    font-size: 0.72rem;
    font-weight: 600;
    letter-spacing: 0.04em;
    margin-bottom: 0.85rem;
}

.hero-badge__dot {
    width: 7px;
    height: 7px;
    border-radius: 9999px;
    background: #10B981;
    margin-right: 0.5rem;
}

.hero-title {
    font-size: clamp(2.15rem, 3.6vw, 3.5rem);
    font-weight: 700;
    letter-spacing: -0.04em;
    line-height: 1.05;
    color: #0A0A0A;
    margin: 0;
}

.hero-description {
    font-size: clamp(0.925rem, 1.05vw, 1.025rem);
    line-height: 1.55;
    color: #55555E;
    margin: 0.85rem 0 1.25rem;
    max-width: 520px;
}

.hero-cta-group {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
    align-items: center;
}

.btn-cta-primary {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 44px;
    padding: 0.65rem 1.35rem;
    border-radius: 6px;
    background: #16A34A;
    color: #fff;
    font-size: 0.875rem;
    font-weight: 600;
    text-decoration: none;
    box-shadow: 0 4px 12px rgba(22, 163, 74, 0.22);
    transition: all 150ms ease;
}

.btn-cta-primary:hover {
    background: #15803D;
    transform: translateY(-1px);
}

.btn-cta-secondary {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 44px;
    padding: 0.65rem 1.25rem;
    border-radius: 6px;
    background: #fff;
    border: 1px solid #D7D7DF;
    color: #0A0A0A;
    font-size: 0.875rem;
    font-weight: 600;
    text-decoration: none;
    transition: all 150ms ease;
}

.btn-cta-secondary:hover {
    border-color: #4F46E5;
    color: #4F46E5;
    transform: translateY(-1px);
}

/* Hero Track Card */
.hero-track-card {
    background: #fff;
    border: 1px solid #E8E8EC;
    border-radius: 8px;
    padding: 0.85rem 1rem;
    margin-top: 1.25rem;
    max-width: 520px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.03);
}

.hero-track-card__header {
    display: flex;
    align-items: center;
    margin-bottom: 0.45rem;
}

.hero-track-card__row {
    display: flex;
    gap: 0.5rem;
}

.hero-track-card__input {
    flex: 1;
    min-width: 0;
    min-height: 44px;
    padding: 0.45rem 0.85rem;
    border: 1px solid #D7D7DF;
    border-radius: 6px;
    font-size: 0.875rem;
    color: #0A0A0A;
    background: #fff;
}

.hero-track-card__input:focus {
    outline: none;
    border-color: #4F46E5;
    box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.15);
}

.hero-track-card__submit {
    flex-shrink: 0;
    min-height: 44px;
    padding: 0.45rem 1.15rem;
    border: 0;
    border-radius: 6px;
    background: #4F46E5;
    color: #fff;
    font-size: 0.85rem;
    font-weight: 600;
    cursor: pointer;
    transition: background-color 150ms ease;
}

.hero-track-card__submit:hover {
    background: #4338CA;
}

.hero-track-card__submit:disabled {
    background: #A5B4FC;
    cursor: wait;
}

.hero-track-card__hint,
.hero-track-card__error {
    font-size: 0.72rem;
    margin-top: 0.35rem;
}

.hero-track-card__hint {
    color: #6B6B6B;
}

.hero-track-card__error {
    color: #DC2626;
    font-weight: 500;
}

/* Hero Stats Row */
.hero-stats-row {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 1px;
    background: #E8E8EC;
    border: 1px solid #E8E8EC;
    border-radius: 6px;
    overflow: hidden;
    margin-top: 1.25rem;
    max-width: 520px;
}

.hero-stats-col {
    background: #fff;
    padding: 0.65rem 0.75rem;
}

.hero-stats-col dt {
    font-size: 0.65rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #6B6B6B;
}

.hero-stats-col dd {
    font-size: 1.15rem;
    font-weight: 700;
    color: #0A0A0A;
    margin: 0.2rem 0 0;
    letter-spacing: -0.03em;
}

/* Hero Visual Media */
.hero-section__visual {
    align-self: center;
}

.hero-visual-card {
    position: relative;
    border: 1px solid #E8E8EC;
    border-radius: 12px;
    overflow: hidden;
    background: #fff;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.04);
}

.hero-visual-img {
    display: block;
    width: 100%;
    height: clamp(240px, 28vw, 360px);
    object-fit: cover;
}

.hero-visual-overlay {
    padding: 0.65rem 0.9rem;
    background: #fff;
    border-top: 1px solid #E8E8EC;
    display: flex;
    flex-direction: column;
    gap: 0.15rem;
}

/* Sections Shared */
.section-container {
    width: min(100% - 2rem, 1200px);
    margin-inline: auto;
    padding-block: clamp(2rem, 4.5vw, 4rem);
}

.section-header {
    max-width: 640px;
    margin-inline: auto;
}

.section-eyebrow {
    font-size: 0.72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.12em;
    color: #4F46E5;
    margin-bottom: 0.35rem;
}

.section-title {
    font-size: clamp(1.75rem, 2.8vw, 2.4rem);
    font-weight: 700;
    letter-spacing: -0.04em;
    line-height: 1.1;
    color: #0A0A0A;
    margin: 0;
}

.section-subtitle {
    font-size: 0.925rem;
    color: #55555E;
    line-height: 1.55;
    margin-top: 0.5rem;
}

/* Feature Cards */
.feature-card {
    background: #fff;
    border: 1px solid #E8E8EC;
    border-radius: 10px;
    padding: 1.5rem 1.25rem;
    display: flex;
    flex-direction: column;
    transition: transform 180ms ease, box-shadow 180ms ease, border-color 180ms ease;
}

.feature-card:hover {
    transform: translateY(-2px);
    border-color: #C7D2FE;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.05);
}

.feature-icon {
    width: 44px;
    height: 44px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 1rem;
}

.feature-title {
    font-size: 1.1rem;
    font-weight: 700;
    color: #0A0A0A;
    margin: 0 0 0.45rem;
    letter-spacing: -0.02em;
}

.feature-desc {
    font-size: 0.875rem;
    color: #55555E;
    line-height: 1.55;
    margin: 0;
}

/* Service Pricing Cards */
.service-pricing-card {
    background: #fff;
    border: 1px solid #E8E8EC;
    border-radius: 12px;
    padding: 1.5rem 1.25rem;
    display: flex;
    flex-direction: column;
    position: relative;
    transition: all 180ms ease;
}

.service-pricing-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.06);
}

.service-pricing-card--popular {
    border-color: #4F46E5;
    box-shadow: 0 4px 18px rgba(79, 70, 229, 0.12);
}

.popular-ribbon {
    position: absolute;
    top: -10px;
    right: 16px;
    background: #4F46E5;
    color: #fff;
    font-size: 0.68rem;
    font-weight: 700;
    padding: 2px 10px;
    border-radius: 9999px;
    letter-spacing: 0.04em;
    text-transform: uppercase;
}

.service-pricing-card__header {
    border-bottom: 1px solid #F0F0F4;
    padding-bottom: 1rem;
}

.service-badge {
    display: inline-block;
    font-size: 0.68rem;
    font-weight: 600;
    color: #4F46E5;
    background: #EEF2FF;
    padding: 2px 8px;
    border-radius: 4px;
    margin-bottom: 0.5rem;
}

.service-name {
    font-size: 1.25rem;
    font-weight: 700;
    color: #0A0A0A;
    margin: 0 0 0.5rem;
}

.service-price-row {
    display: flex;
    align-items: baseline;
    gap: 0.25rem;
}

.service-price {
    font-size: 1.65rem;
    font-weight: 700;
    color: #0A0A0A;
    letter-spacing: -0.04em;
}

.service-unit {
    font-size: 0.85rem;
    color: #6B6B6B;
}

.service-min {
    display: block;
    font-size: 0.72rem;
    color: #6B6B6B;
    margin-top: 0.2rem;
}

.service-desc {
    font-size: 0.85rem;
    color: #55555E;
    line-height: 1.5;
    margin: 1rem 0;
}

.service-feature-list {
    list-style: none;
    padding: 0;
    margin: 0 0 1.5rem;
    display: flex;
    flex-direction: column;
    gap: 0.6rem;
    flex: 1;
}

.service-feature-list li {
    font-size: 0.825rem;
    color: #33333A;
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
    border-radius: 6px;
    background: #F4F4F6;
    color: #0A0A0A;
    font-size: 0.825rem;
    font-weight: 600;
    text-decoration: none;
    border: 1px solid #E8E8EC;
    transition: all 150ms ease;
}

.btn-service-order:hover {
    background: #E8E8EC;
    color: #0A0A0A;
}

.btn-service-order--primary {
    background: #16A34A;
    border-color: #16A34A;
    color: #fff;
}

.btn-service-order--primary:hover {
    background: #15803D;
    color: #fff;
}

/* Service Addons Strip */
.service-addons-strip {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    justify-content: center;
    background: #fff;
    border: 1px solid #E8E8EC;
    border-radius: 8px;
    padding: 0.9rem 1.25rem;
}

.service-addon-item {
    display: flex;
    align-items: center;
}

/* Step Cards */
.step-card {
    background: #fff;
    border: 1px solid #E8E8EC;
    border-radius: 10px;
    padding: 1.5rem 1.25rem;
    position: relative;
}

.step-number {
    font-size: 1.85rem;
    font-weight: 800;
    color: #E0E7FF;
    line-height: 1;
    margin-bottom: 0.75rem;
}

.step-title {
    font-size: 1.05rem;
    font-weight: 700;
    color: #0A0A0A;
    margin: 0 0 0.5rem;
}

.step-desc {
    font-size: 0.85rem;
    color: #55555E;
    line-height: 1.55;
    margin: 0;
}

/* Payment Banner */
.payment-banner {
    background: #fff;
    border: 1px solid #E8E8EC;
    border-radius: 12px;
    padding: 1.5rem 1.75rem;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 1.25rem;
}

.payment-methods-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 0.65rem;
}

.payment-chip {
    display: inline-flex;
    align-items: center;
    padding: 0.55rem 0.95rem;
    border-radius: 6px;
    background: #FAFAFA;
    border: 1px solid #E8E8EC;
    font-size: 0.825rem;
    font-weight: 600;
    color: #0A0A0A;
}

/* Testimonials */
.testimonial-card {
    background: #fff;
    border: 1px solid #E8E8EC;
    border-radius: 10px;
    padding: 1.5rem;
    display: flex;
    flex-direction: column;
}

/* FAQ Accordion */
.faq-accordion-list {
    display: flex;
    flex-direction: column;
    gap: 0.65rem;
}

.faq-item {
    background: #fff;
    border: 1px solid #E8E8EC;
    border-radius: 8px;
    overflow: hidden;
}

.faq-question {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1rem 1.25rem;
    font-size: 0.925rem;
    font-weight: 600;
    color: #0A0A0A;
    cursor: pointer;
    list-style: none;
    min-height: 44px;
}

.faq-question::-webkit-details-marker {
    display: none;
}

.faq-item[open] .faq-icon {
    transform: rotate(180deg);
}

.faq-answer {
    padding: 0 1.25rem 1.15rem;
    font-size: 0.875rem;
    color: #55555E;
    line-height: 1.6;
}

/* Store Info Card */
.store-info-card {
    background: #fff;
    border: 1px solid #E8E8EC;
    border-radius: 12px;
    padding: 1.75rem;
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
        background: #16A34A;
        padding: 0.65rem 1rem 0.65rem 0.85rem;
        border-radius: 9999px;
        box-shadow: 0 4px 16px rgba(22, 163, 74, 0.4);
        text-decoration: none;
        min-height: 44px;
        transition: transform 150ms ease;
    }

    .mobile-floating-wa__btn:hover {
        transform: scale(1.05);
    }
}

/* Footer */
.site-footer {
    border-top: 1px solid #E8E8EC;
    background: #fff;
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
    font-size: 0.85rem;
    color: #55555E;
    text-decoration: none;
    transition: color 150ms ease;
}

.site-footer__links a:hover {
    color: #4F46E5;
}

.site-footer__staff-panel {
    display: flex;
    flex-direction: column;
}

.site-footer__bottom {
    border-top: 1px solid #F0F0F4;
    padding-block: 1.25rem;
    text-align: center;
    font-size: 0.75rem;
    color: #6B6B6B;
}

/* Responsive Overrides */
@media (max-width: 900px) {
    .hero-section {
        grid-template-columns: 1fr;
        gap: 2rem;
    }

    .hero-stats-row {
        max-width: 100%;
        grid-template-columns: repeat(2, minmax(0, 1fr));
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
        background: #FAFAFA;
        border-bottom: 1px solid #E8E8EC;
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
        border-bottom: 1px solid #E8E8EC;
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
        font-size: clamp(1.85rem, 8vw, 2.5rem);
    }

    .hero-cta-group {
        flex-direction: column;
        align-items: stretch;
    }

    .btn-cta-primary,
    .btn-cta-secondary {
        width: 100%;
    }

    .hero-stats-row {
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
