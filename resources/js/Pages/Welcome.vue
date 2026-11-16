<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

const props = defineProps<{
    canLogin?: boolean;
    stats?: { customers: number; orders: number; laundry_kg: number };
}>();

const menuOpen = ref(false);
const menuLabel = computed(() => menuOpen.value ? 'Tutup navigasi' : 'Buka navigasi');

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
const testimonials = [
    {
        name: 'Ratna W.',
        area: 'Sekitar 1 km dari toko',
        text: 'Cucian selesai lebih cepat dari estimasi, dikemas rapi, dan wangi sampai hari ketiga.',
        stars: 5,
    },
    {
        name: 'Dimas P.',
        area: 'Antar jemput rutin',
        text: 'Notifikasi WhatsApp-nya memudahkan. Tinggal cek tautan nota kalau mau tahu cucian sudah sampai tahap apa.',
        stars: 5,
    },
    {
        name: 'Sinta M.',
        area: 'Pelanggan karpet',
        text: 'Karpet 3x4 balik lagi seperti baru. Noda kopi lama yang sudah saya anggap permanen ikut hilang.',
        stars: 4,
    },
];

const processSteps = [
    {
        number: '01',
        title: 'Dipilah sebelum dicuci',
        description: 'Pakaian dipisahkan menurut warna, bahan, dan tingkat ketebalannya. Bahan halus seperti sutra, renda, dan wol ditangani terpisah dari kain berat.',
    },
    {
        number: '02',
        title: 'Noda diperiksa lebih dulu',
        description: 'Bagian kerah, manset, dan noda membandel diperiksa sebelum masuk ke mesin, agar penanganan lebih sesuai dengan kondisi pakaian.',
    },
    {
        number: '03',
        title: 'Dicuci sesuai jenis kain',
        description: 'Takaran deterjen dan program pencucian disesuaikan dengan berat serta kategori kain. Pakaian sensitif dikeringkan dengan suhu lebih rendah.',
    },
    {
        number: '04',
        title: 'Disetrika dan dicek ulang',
        description: 'Setelah disetrika uap, pakaian diperiksa kembali. Jika noda atau bau masih tertinggal, cucian masuk proses pencucian ulang tanpa biaya tambahan.',
    },
    {
        number: '05',
        title: 'Dihitung dan dikemas rapi',
        description: 'Jumlah pakaian dicocokkan dengan catatan saat diterima, lalu dilipat atau digantung, diberi pilihan pewangi, dan dikemas agar terlindung dari debu.',
    },
];

// Reveal sekali-jalan (MOTION 2): elemen [data-reveal] muncul halus saat masuk
// viewport. Tujuannya memberi ritme saat scroll, bukan dekorasi berputar-putar.
let revealObserver: IntersectionObserver | null = null;

onMounted(() => {
    const revealTargets = document.querySelectorAll<HTMLElement>('[data-reveal]');

    // Hormati preferensi pengguna: tanpa animasi berarti semua langsung terlihat.
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        revealTargets.forEach((element) => element.classList.add('is-revealed'));
        return;
    }

    if (!('IntersectionObserver' in window)) {
        revealTargets.forEach((element) => element.classList.add('is-revealed'));
        return;
    }

    revealObserver = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting) {
                    return;
                }
                entry.target.classList.add('is-revealed');
                revealObserver?.unobserve(entry.target);
            });
        },
        { threshold: 0.12, rootMargin: '0px 0px -48px 0px' },
    );

    revealTargets.forEach((element) => revealObserver?.observe(element));
});

onBeforeUnmount(() => {
    revealObserver?.disconnect();
    revealObserver = null;
});
</script>

<template>
    <Head title="Andik Laundry | Pakaian Bersih, Dirawat dengan Teliti">
        <meta
            name="description"
            content="Kenali proses perawatan laundry Andik Laundry, dari pemilahan jenis kain dan pemeriksaan noda hingga pengecekan ulang dan pengemasan."
        />
    </Head>

    <div class="min-h-screen bg-background text-ink">
        <a class="skip-link" href="#main-content">Lewati ke konten utama</a>

        <header class="site-header">
            <div class="site-header__inner">
                <Link href="/" class="wordmark">
                    <span class="wordmark__name">andik<span class="wordmark__accent">.</span></span>
                    <span class="wordmark__caption">LAUNDRY</span>
                </Link>

                <button
                    class="menu-toggle"
                    type="button"
                    :aria-expanded="menuOpen"
                    aria-controls="primary-navigation"
                    :aria-label="menuLabel"
                    @click="menuOpen = !menuOpen"
                >
                    <span></span>
                    <span></span>
                </button>

                <nav
                    id="primary-navigation"
                    class="site-nav"
                    :class="{ 'site-nav--open': menuOpen }"
                    aria-label="Navigasi utama"
                >
                    <a href="#tentang" @click="menuOpen = false">Tentang</a>
                    <a href="#perawatan" @click="menuOpen = false">Cara kami merawat</a>
                    <a href="#kualitas" @click="menuOpen = false">Standar kualitas</a>
                    <a href="#ulasan" @click="menuOpen = false">Ulasan</a>
            <a href="#perbandingan" @click="menuOpen = false">Perbandingan</a>
                    <Link v-if="canLogin" href="/login" class="staff-link" @click="menuOpen = false">
                        Masuk staf
                    </Link>
                </nav>
            </div>
        </header>

        <main id="main-content">
            <section id="tentang" class="hero-section">
                <div class="hero-section__copy" data-reveal>
                    <p class="eyebrow">Andik Laundry</p>
                    <h1>Pakaian bersih.<br /><span>Dirawat dengan teliti.</span></h1>
                    <p class="hero-section__description">
                        Setiap kain punya kebutuhan berbeda. Karena itu, cucian ditangani satu per satu dengan alur kerja yang jelas, dari pemeriksaan awal sampai siap diambil.
                    </p>
                    <a class="button-primary" href="#perawatan">Lihat proses perawatan</a>

                    <form class="hero-track" aria-label="Cek pesanan" @submit.prevent="submitOrderCheck">
                        <label class="hero-track__label" for="order-query">Cek pesanan</label>
                        <div class="hero-track__row">
                            <input
                                id="order-query"
                                v-model="orderQuery"
                                class="hero-track__input"
                                type="text"
                                name="q"
                                inputmode="text"
                                autocomplete="off"
                                placeholder="No. nota / no. HP"
                                aria-describedby="hero-track-error"
                            />
                            <button class="hero-track__submit" type="submit" :disabled="isCheckingOrder">
                                {{ isCheckingOrder ? 'Mencari…' : 'Cek' }}
                            </button>
                        </div>
                        <p v-if="orderQueryError" id="hero-track-error" class="hero-track__error" role="alert">
                            {{ orderQueryError }}
                        </p>
                        <p v-else class="hero-track__hint">
                            Contoh: AL-20261115-001 atau 0812xxxxxxx.
                        </p>
                    </form>

                    <dl class="hero-stats" aria-label="Statistik pelanggan Andik Laundry">
                        <div class="hero-stats__item">
                            <dt>Pelanggan</dt>
                            <dd>{{ customersLabel }}</dd>
                        </div>
                        <div class="hero-stats__item">
                            <dt>Pesanan selesai</dt>
                            <dd>{{ ordersLabel }}</dd>
                        </div>
                        <div class="hero-stats__item">
                            <dt>Cucian dikerjakan</dt>
                            <dd>{{ laundryKgLabel }}</dd>
                        </div>
                        <div class="hero-stats__item">
                            <dt>Rating pelanggan</dt>
                            <dd>★ {{ ratingScore }}<span class="hero-stats__max">/5</span></dd>
                        </div>
                    </dl>
                </div>

                <div class="hero-section__visual" data-reveal>
                    <figure class="hero-photo">
                        <img
                            src="/images/hero-laundry.jpg"
                            alt="Pakaian bersih berjajar di tali jemuran di bawah sinar matahari"
                            width="1920"
                            height="1092"
                            loading="eager"
                            decoding="async"
                        />
                        <figcaption class="hero-photo__credit">
                            Foto: Michal Klajban / Wikimedia Commons, CC BY-SA 4.0 — foto ilustrasi cucian, bukan lokasi toko.
                        </figcaption>
                    </figure>
                    <p class="hero-section__note">Dari sortir hingga lipatan terakhir, tiap tahap punya perhatian sendiri.</p>
                </div>
            </section>

            <section id="cek-pesanan" class="intro-strip" aria-label="Cek pesanan dan ringkasan pendekatan perawatan">
                <p data-reveal>Dirawat, bukan sekadar dicuci.</p>
                <span data-reveal>Setiap tahap dimulai dari kondisi pakaian yang kami terima. Lacak cucian Anda lewat form <a href="#tentang">cek pesanan</a> di atas, tanpa login.</span>
            </section>

            <section id="ulasan" class="reviews-section" aria-labelledby="reviews-heading">
                <div class="reviews-section__heading" data-reveal>
                    <p class="eyebrow">Ulasan pelanggan</p>
                    <h2 id="reviews-heading">Kata mereka yang<br />titip cucian di sini.</h2>
                    <p class="reviews-section__rating">
                        <span class="reviews-section__score">★ {{ ratingScore }}</span>
                        dari rata-rata 5 — berdasarkan penilaian pelanggan Andik Laundry.
                    </p>
                </div>

                <ul class="reviews-list">
                    <li v-for="review in testimonials" :key="review.name" class="review-card" data-reveal>
                        <p class="review-card__stars" :aria-label="`Rating ${review.stars} dari 5 bintang`">
                            <span aria-hidden="true">{{ '★'.repeat(review.stars) }}</span><span class="review-card__stars--empty" aria-hidden="true">{{ '★'.repeat(5 - review.stars) }}</span>
                        </p>
                        <blockquote class="review-card__text">“{{ review.text }}”</blockquote>
                        <footer class="review-card__meta">
                            <span class="review-card__name">{{ review.name }}</span>
                            <span class="review-card__area">{{ review.area }}</span>
                        </footer>
                    </li>
                </ul>
            </section>

            <section id="perawatan" class="care-section">
                <div class="care-section__heading" data-reveal>
                    <p class="eyebrow">Alur perawatan</p>
                    <h2>Perhatian dimulai<br />sebelum mesin berputar.</h2>
                    <p>Urutan kerja ini membantu pakaian tetap terlacak, tertangani sesuai bahan, dan diperiksa lagi sebelum dikemas.</p>
                </div>

                <ol class="process-list">
                    <li v-for="step in processSteps" :key="step.number" class="process-item" data-reveal>
                        <span class="process-item__number">{{ step.number }}</span>
                        <div>
                            <h3>{{ step.title }}</h3>
                            <p>{{ step.description }}</p>
                        </div>
                    </li>
                </ol>
            </section>

            <section id="kualitas" class="quality-section">
                <div class="quality-section__visual" data-reveal aria-hidden="true">
                    <div class="quality-stamp">
                        <span>ANDIK</span>
                        <span>TELITI</span>
                        <span>HINGGA</span>
                        <span>SELESAI</span>
                    </div>
                    <div class="quality-section__fabric"></div>
                </div>

                <div class="quality-section__copy" data-reveal>
                    <p class="eyebrow">Standar kualitas</p>
                    <h2>Yang rapi terlihat.<br />Yang teliti terasa.</h2>
                    <p class="quality-section__lead">Kualitas bukan cuma soal wangi. Perhatian kami ada di langkah-langkah kecil yang menjaga cucian tetap sesuai catatan dan siap dipakai kembali.</p>

                    <ul class="quality-points">
                        <li data-reveal>
                            <span class="quality-points__mark" aria-hidden="true">01</span>
                            <div>
                                <h3>Pakaian tidak tercampur</h3>
                                <p>Setiap cucian diberi tanda untuk membantu mencegah pakaian tertukar saat diproses.</p>
                            </div>
                        </li>
                        <li data-reveal>
                            <span class="quality-points__mark" aria-hidden="true">02</span>
                            <div>
                                <h3>Pemeriksaan setelah setrika</h3>
                                <p>Noda yang masih terlihat atau bau yang tertinggal diperiksa sebelum cucian dilipat.</p>
                            </div>
                        </li>
                        <li data-reveal>
                            <span class="quality-points__mark" aria-hidden="true">03</span>
                            <div>
                                <h3>Jumlah dicocokkan kembali</h3>
                                <p>Isi kemasan dihitung agar sesuai dengan jumlah pakaian saat penerimaan.</p>
                            </div>
                        </li>
                        <li data-reveal>
                            <span class="quality-points__mark" aria-hidden="true">04</span>
                            <div>
                                <h3>Cuci ulang tanpa biaya tambahan</h3>
                                <p>Jika pemeriksaan menemukan noda atau bau yang tersisa, koreksi proses menjadi tanggung jawab operasional kami.</p>
                            </div>
                        </li>
                    </ul>
                </div>
            </section>

            <section id="perbandingan" class="compare-section">
                <div class="compare-section__heading" data-reveal>
                    <p class="eyebrow">Perbandingan</p>
                    <h2>Standar kerja yang<br />terlihat pada hasilnya.</h2>
                    <p>Tabel ini bukan membandingkan harga atau nama pesaing. Kolom tengah adalah praktik yang berjalan di Andik Laundry; kolom terakhir menjelaskan apa yang berisiko terjadi bila praktik serupa tidak dijalankan.</p>
                </div>

                <table class="compare-table" data-reveal>
                    <caption class="compare-table__caption">Perbandingan praktik kerja Andik Laundry dan konsekuensi bila langkah tersebut tidak dijalankan.</caption>
                    <thead>
                        <tr>
                            <th scope="col">Aspek</th>
                            <th scope="col">Di Andik Laundry</th>
                            <th scope="col">Jika langkah ini tidak ada</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <th scope="row">Pemilahan kain</th>
                            <td data-label="Di Andik Laundry">Pakaian dipisahkan menurut warna, jenis bahan, dan ketebalan sebelum masuk mesin.</td>
                            <td data-label="Jika langkah ini tidak ada">Bahan halus tercampur dengan kain berat, dan warna gelap berisiko berpindah ke kain terang.</td>
                        </tr>
                        <tr>
                            <th scope="row">Penandaan dan rak</th>
                            <td data-label="Di Andik Laundry">Setiap cucian membawa tag dan kode rak sehingga urutan kerjanya terlacak dari penerimaan sampai pengembalian.</td>
                            <td data-label="Jika langkah ini tidak ada">Pakaian mudah tertukar antar pelanggan atau tercecer di tengah proses.</td>
                        </tr>
                        <tr>
                            <th scope="row">Kontrol setelah setrika</th>
                            <td data-label="Di Andik Laundry">Pakaian diperiksa ulang setelah disetrika; noda atau bau yang tersisa masuk pencucian ulang tanpa biaya, maksimal dua kali.</td>
                            <td data-label="Jika langkah ini tidak ada">Hasil yang kurang rapi atau masih bau biasanya ketahuan setelah cucian sampai di tangan pelanggan.</td>
                        </tr>
                        <tr>
                            <th scope="row">Struk dan kabar selesai</th>
                            <td data-label="Di Andik Laundry">Struk digital memuat nomor pesanan, dan notifikasi WhatsApp dikirim saat cucian dinyatakan selesai.</td>
                            <td data-label="Jika langkah ini tidak ada">Status cucian tidak jelas, dan pelanggan harus menanyakan sendiri kapan bisa diambil.</td>
                        </tr>
                        <tr>
                            <th scope="row">Pencocokan jumlah</th>
                            <td data-label="Di Andik Laundry">Isi kemasan dihitung ulang dan dicocokkan dengan catatan jumlah saat penerimaan.</td>
                            <td data-label="Jika langkah ini tidak ada">Selisih pakaian biasanya baru disadari setelah tiba di rumah.</td>
                        </tr>
                    </tbody>
                </table>

                <p class="compare-section__footnote" data-reveal>
                    Dasar kolom tengah: dokumen proses internal Andik Laundry — pemilahan empat kategori kain, tag pakaian dan kode rak, cuci ulang gratis maksimal dua kali, struk bernomor pesanan yang disertai notifikasi WhatsApp, serta pencocokan jumlah saat pengemasan. Kolom terakhir adalah konsekuensi umum, bukan data pesaing tertentu.
                </p>
            </section>

            <section class="closing-section" data-reveal>
                <p class="eyebrow">Andik Laundry</p>
                <h2>Lebih tenang saat pakaian ditangani dengan perhatian.</h2>
                <p>Untuk informasi layanan dan penerimaan cucian, silakan kunjungi Andik Laundry secara langsung.</p>
            </section>
        </main>

        <footer class="site-footer">
            <Link href="/" class="wordmark">
                <span class="wordmark__name">andik<span class="wordmark__accent">.</span></span>
                <span class="wordmark__caption">LAUNDRY</span>
            </Link>
            <p>Perawatan pakaian dengan perhatian pada setiap proses.</p>
            <Link v-if="canLogin" href="/login" class="site-footer__staff">Masuk staf</Link>
        </footer>
    </div>
</template>

<style scoped>
/* Guard ukuran: semua elemen memasukkan padding & border ke dalam width,
   sehingga section tidak pernah meluber melebihi lebar layar. */
:global(*),
:global(*::before),
:global(*::after) {
    box-sizing: border-box;
}

:global(html) {
    scroll-behavior: smooth;
    scroll-padding-top: 6rem;
}

:global(body) {
    min-width: 320px;
}

.skip-link {
    position: absolute;
    z-index: 20;
    top: 0.75rem;
    left: 0.75rem;
    padding: 0.75rem 1rem;
    transform: translateY(-160%);
    border-radius: 6px;
    background: #6366f1;
    color: #fff;
    font-size: 0.875rem;
}

.skip-link:focus {
    transform: translateY(0);
}

.site-header {
    position: sticky;
    z-index: 10;
    top: 0;
    border-bottom: 1px solid #e8e8ec;
    background: rgb(250 250 250 / 94%);
    backdrop-filter: blur(12px);
}

.site-header__inner,
.site-footer {
    display: flex;
    width: min(100% - 3rem, 1280px);
    margin-inline: auto;
    align-items: center;
    justify-content: space-between;
}

.site-header__inner {
    min-height: 56px;
}

.wordmark {
    display: inline-flex;
    flex-direction: column;
    width: fit-content;
    color: #0a0a0a;
    text-decoration: none;
}

.wordmark__name {
    font-family: var(--font-display);
    font-size: 1.55rem;
    font-weight: 700;
    letter-spacing: -0.07em;
    line-height: 0.95;
}

.wordmark__accent {
    color: #6366f1;
}

.wordmark__caption {
    margin-top: 0.28rem;
    font-size: 0.58rem;
    font-weight: 600;
    letter-spacing: 0.2em;
}

.site-nav {
    display: flex;
    align-items: center;
    gap: clamp(1rem, 2.4vw, 2.25rem);
}

.site-nav a,
.site-footer__staff {
    color: #41414a;
    font-size: 0.875rem;
    font-weight: 500;
    text-decoration: none;
    transition: color 180ms ease;
}

.site-nav a:hover,
.site-footer__staff:hover {
    color: #4f46e5;
}

.site-nav .staff-link {
    display: inline-flex;
    min-height: 42px;
    align-items: center;
    padding-inline: 1rem;
    border: 1px solid #d7d7df;
    border-radius: 6px;
    color: #0a0a0a;
}

.site-nav .staff-link:hover {
    border-color: #6366f1;
}

.menu-toggle {
    display: none;
}

.hero-section {
    display: grid;
    width: min(100% - 3rem, 1280px);
    height: auto;
    margin-inline: auto;
    align-items: center;
    grid-template-columns: minmax(0, 1.05fr) minmax(0, 0.95fr);
    gap: clamp(1.5rem, 3.5vw, 3rem);
    padding-block: clamp(1.25rem, 3vh, 2.25rem) clamp(1.5rem, 3.5vh, 2.5rem);
}

.hero-section__copy {
    max-width: 620px;
    padding-block: 0;
}

.eyebrow {
    margin: 0 0 0.5rem;
    color: #51515b;
    font-size: 0.72rem;
    font-weight: 600;
    letter-spacing: 0.11em;
    text-transform: uppercase;
}

.hero-section h1,
.care-section h2,
.quality-section h2,
.closing-section h2 {
    margin: 0;
    color: #0a0a0a;
    font-family: var(--font-display);
    font-weight: 600;
    letter-spacing: -0.045em;
}

.hero-section h1 {
    max-width: 620px;
    font-size: clamp(2.25rem, 3.8vw, 3.65rem);
    line-height: 1.04;
}

.hero-section h1 span {
    color: #686873;
}

.hero-section__description {
    max-width: 480px;
    margin: 0.75rem 0 1.15rem;
    color: #55555e;
    font-size: clamp(0.9rem, 1vw, 1rem);
    line-height: 1.55;
}

.button-primary {
    display: inline-flex;
    min-height: 44px;
    align-items: center;
    justify-content: center;
    padding: 0.6rem 1.25rem;
    border-radius: 6px;
    /* #4F46E5 = primary-hover token dari DESIGN.md; dipakai sebagai warna dasar
       agar teks putih memenuhi kontras WCAG AA 4.5:1 (R-25). */
    background: #4f46e5;
    color: #fff;
    font-size: 0.875rem;
    font-weight: 600;
    text-decoration: none;
    transition: background-color 180ms ease, box-shadow 180ms ease, transform 180ms ease;
}

.button-primary:hover {
    transform: translateY(-1px);
    background: #4338ca;
    box-shadow: 0 4px 12px rgb(99 102 241 / 24%);
}

.hero-section__visual {
    min-width: 0;
    align-self: center;
}

.hero-photo {
    margin: 0;
    overflow: hidden;
    border: 1px solid #dcdad4;
    border-radius: 6px;
}

.hero-photo img {
    display: block;
    width: 100%;
    height: clamp(240px, 26vw, 360px);
    object-fit: cover;
}

.hero-photo__credit {
    padding: 0.45rem 0.75rem;
    border-top: 1px solid #e8e8ec;
    background: #fafafa;
    color: #6b6b6b;
    font-size: 0.65rem;
    line-height: 1.45;
}

.hero-section__note {
    max-width: 360px;
    margin: 0.65rem 0 0 auto;
    color: #55555e;
    font-size: 0.75rem;
    line-height: 1.5;
    text-align: right;
}

.hero-stats {
    display: grid;
    width: 100%;
    max-width: 560px;
    margin: 1.15rem 0 0;
    padding: 0;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 1px;
    background: #e3e3e8;
    border: 1px solid #e3e3e8;
    border-radius: 6px;
    overflow: hidden;
}

.hero-stats__item {
    padding: 0.65rem 0.8rem;
    background: #fff;
}

.hero-stats__item dt {
    margin: 0;
    color: #6b6b6b;
    font-size: 0.68rem;
    font-weight: 600;
    letter-spacing: 0.05em;
    text-transform: uppercase;
}

.hero-stats__item dd {
    margin: 0.2rem 0 0;
    color: #0a0a0a;
    font-family: var(--font-display);
    font-size: 1.15rem;
    font-weight: 700;
    letter-spacing: -0.03em;
}

.hero-stats__max {
    color: #9a9aa3;
    font-family: var(--font-mono);
    font-size: 0.75rem;
    font-weight: 400;
    letter-spacing: 0;
}

/* Cek pesanan — form kompak di dalam hero */
.hero-track {
    max-width: 480px;
    margin-top: 1rem;
}

.hero-track__label {
    display: block;
    margin-bottom: 0.35rem;
    color: #51515b;
    font-size: 0.7rem;
    font-weight: 600;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}

.hero-track__row {
    display: flex;
    gap: 0.5rem;
}

.hero-track__input {
    flex: 1;
    min-width: 0;
    min-height: 44px;
    padding: 0.45rem 0.75rem;
    border: 1px solid #d7d7df;
    border-radius: 6px;
    background: #fff;
    color: #0a0a0a;
    font-size: 0.875rem;
}

.hero-track__input:focus {
    border-color: #6366f1;
    outline: none;
    box-shadow: 0 0 0 3px rgb(99 102 241 / 18%);
}

.hero-track__submit {
    flex-shrink: 0;
    min-height: 44px;
    padding: 0.45rem 1.1rem;
    border: 0;
    border-radius: 6px;
    background: #4f46e5;
    color: #fff;
    font-size: 0.85rem;
    font-weight: 600;
    cursor: pointer;
    transition: background-color 180ms ease;
}

.hero-track__submit:hover {
    background: #4338ca;
}

.hero-track__submit:disabled {
    background: #a5b4fc;
    cursor: wait;
}

.hero-track__hint,
.hero-track__error {
    margin: 0.35rem 0 0;
    font-size: 0.72rem;
    line-height: 1.5;
}

.hero-track__hint {
    color: #6b6b6b;
}

.hero-track__error {
    color: #b42318;
    font-weight: 500;
}

/* Ulasan pelanggan */
.reviews-section {
    width: min(100% - 3rem, 1120px);
    margin-inline: auto;
    padding-block: 6.5rem;
}

.reviews-section__heading {
    max-width: 620px;
    margin-bottom: 3rem;
}

.reviews-section h2 {
    margin: 0;
    color: #0a0a0a;
    font-family: var(--font-display);
    font-size: clamp(2.3rem, 4vw, 3.6rem);
    font-weight: 600;
    letter-spacing: -0.055em;
    line-height: 1.04;
}

.reviews-section__rating {
    margin: 1.25rem 0 0;
    color: #55555e;
    font-size: 0.9rem;
    line-height: 1.7;
}

.reviews-section__score {
    margin-right: 0.4rem;
    color: #0a0a0a;
    font-size: 1.05rem;
    font-weight: 700;
}

.reviews-list {
    display: grid;
    margin: 0;
    padding: 0;
    list-style: none;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 1.25rem;
}

.review-card {
    display: flex;
    flex-direction: column;
    gap: 0.9rem;
    padding: 1.6rem;
    border: 1px solid #e3e3e8;
    border-radius: 8px;
    background: #fff;
}

.review-card__stars {
    margin: 0;
    color: #d97706;
    font-size: 0.95rem;
    letter-spacing: 0.12em;
}

.review-card__stars--empty {
    color: #dcdce2;
}

.review-card__text {
    flex: 1;
    margin: 0;
    color: #41414a;
    font-size: 0.92rem;
    line-height: 1.75;
}

.review-card__meta {
    display: flex;
    flex-direction: column;
    gap: 0.15rem;
    padding-top: 0.9rem;
    border-top: 1px solid #e8e8ec;
}

.review-card__name {
    color: #0a0a0a;
    font-size: 0.85rem;
    font-weight: 700;
}

.review-card__area {
    color: #6b6b6b;
    font-size: 0.75rem;
}

.intro-strip {
    display: flex;
    min-height: 122px;
    align-items: center;
    justify-content: space-between;
    gap: 2rem;
    padding: 2rem max(1.5rem, calc((100vw - 1280px) / 2));
    border-block: 1px solid #e8e8ec;
    background: #fff;
}

.intro-strip p {
    margin: 0;
    color: #0a0a0a;
    font-family: var(--font-display);
    font-size: clamp(1.45rem, 3vw, 2.25rem);
    font-weight: 600;
    letter-spacing: -0.045em;
}

.intro-strip span {
    max-width: 390px;
    color: #55555e;
    font-size: 0.9rem;
    line-height: 1.65;
}

.care-section {
    display: grid;
    width: min(100% - 3rem, 1120px);
    margin-inline: auto;
    grid-template-columns: minmax(230px, 0.72fr) minmax(0, 1.28fr);
    gap: clamp(3rem, 11vw, 10rem);
    padding-block: 8rem;
}

.care-section__heading {
    align-self: start;
    padding-top: 0.25rem;
}

.care-section__heading .eyebrow,
.quality-section__copy .eyebrow {
    margin-bottom: 1.5rem;
}

.care-section h2,
.quality-section h2 {
    font-size: clamp(2.4rem, 4vw, 3.8rem);
    line-height: 1.04;
}

.care-section__heading > p:last-child {
    max-width: 340px;
    margin: 1.25rem 0 0;
    color: #55555e;
    font-size: 0.95rem;
    line-height: 1.8;
}

.process-list {
    margin: 0;
    padding: 0;
    list-style: none;
}

.process-item {
    display: grid;
    grid-template-columns: 46px minmax(0, 1fr);
    gap: 1.25rem;
    padding-block: 1.45rem;
    border-top: 1px solid #e3e3e8;
}

.process-item:last-child {
    border-bottom: 1px solid #e3e3e8;
}

.process-item__number {
    padding-top: 0.25rem;
    color: #50505a;
    font-family: var(--font-mono);
    font-size: 0.8rem;
}

.process-item h3,
.quality-points h3 {
    margin: 0;
    color: #111116;
    font-size: 1.075rem;
    font-weight: 600;
    letter-spacing: -0.02em;
}

.process-item p,
.quality-points p {
    max-width: 580px;
    margin: 0.5rem 0 0;
    color: #55555e;
    font-size: 0.9rem;
    line-height: 1.75;
}

.quality-section {
    display: grid;
    grid-template-columns: minmax(0, 0.86fr) minmax(0, 1.14fr);
    background: #f0efeb;
}

.quality-section__visual {
    position: relative;
    display: grid;
    min-height: min(600px, 70vh);
    overflow: hidden;
    align-items: center;
    justify-items: center;
    background: #e6e4de;
}

.quality-stamp {
    position: relative;
    z-index: 1;
    display: flex;
    width: min(66%, 280px);
    aspect-ratio: 1;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    border: 1px solid #a5a39a;
    border-radius: 50%;
    color: #33333a;
    font-family: var(--font-display);
    font-size: clamp(1.1rem, 2.5vw, 1.7rem);
    font-weight: 600;
    letter-spacing: 0.08em;
    line-height: 1.5;
    transform: rotate(-9deg);
}

.quality-section__fabric {
    position: absolute;
    right: -15%;
    bottom: -26%;
    width: 90%;
    height: 58%;
    transform: rotate(-14deg);
    border: 1px solid #cfcdc6;
    background: #f7f6f2;
}

.quality-section__copy {
    width: min(100%, 700px);
    align-self: center;
    padding: clamp(3rem, 7vw, 7rem) clamp(2rem, 7vw, 7rem);
}

.quality-section__lead {
    max-width: 520px;
    margin: 1.25rem 0 2rem;
    color: #55555e;
    font-size: 0.95rem;
    line-height: 1.8;
}

.quality-points {
    margin: 0;
    padding: 0;
    list-style: none;
}

.quality-points li {
    display: grid;
    grid-template-columns: 38px minmax(0, 1fr);
    gap: 1rem;
    padding-block: 1rem;
    border-top: 1px solid #d8d7d1;
}

.quality-points__mark {
    padding-top: 0.2rem;
    color: #565660;
    font-family: var(--font-mono);
    font-size: 0.72rem;
}

.quality-points h3 {
    font-size: 0.95rem;
}

.quality-points p {
    font-size: 0.84rem;
}

.closing-section {
    width: min(100% - 3rem, 940px);
    margin-inline: auto;
    padding-block: 7rem;
    text-align: center;
}

.closing-section .eyebrow {
    margin-bottom: 1rem;
}

.closing-section h2 {
    max-width: 800px;
    margin-inline: auto;
    font-size: clamp(2.3rem, 5.7vw, 5rem);
    line-height: 1.02;
}

.closing-section > p:last-child {
    max-width: 520px;
    margin: 1.35rem auto 0;
    color: #55555e;
    font-size: 0.95rem;
    line-height: 1.8;
}

.site-footer {
    min-height: 108px;
    gap: 1.5rem;
    border-top: 1px solid #e8e8ec;
}

.site-footer > p {
    margin: 0;
    color: #55555e;
    font-size: 0.8rem;
    text-align: center;
}

.site-footer__staff {
    min-height: 44px;
    display: inline-flex;
    align-items: center;
}

.compare-section {
    width: min(100% - 3rem, 1120px);
    margin-inline: auto;
    padding-block: 7rem;
}

.compare-section__heading {
    max-width: 640px;
}

.compare-section h2 {
    margin: 0;
    color: #0a0a0a;
    font-family: var(--font-display);
    font-size: clamp(2.4rem, 4vw, 3.8rem);
    font-weight: 600;
    letter-spacing: -0.055em;
    line-height: 1.04;
}

.compare-section__heading > p:last-child {
    max-width: 470px;
    margin: 1.25rem 0 0;
    color: #55555e;
    font-size: 0.95rem;
    line-height: 1.8;
}

.compare-table {
    width: 100%;
    margin-top: 3rem;
    border-collapse: collapse;
    background: #fff;
}

.compare-table__caption {
    position: absolute;
    width: 1px;
    height: 1px;
    overflow: hidden;
    clip-path: inset(50%);
    white-space: nowrap;
}

.compare-table th,
.compare-table td {
    padding: 1.1rem 1.25rem;
    border: 1px solid #e3e3e8;
    text-align: left;
    vertical-align: top;
}

.compare-table thead th {
    background: #f0efeb;
    color: #0a0a0a;
    font-size: 0.8rem;
    font-weight: 600;
    letter-spacing: 0.04em;
    text-transform: uppercase;
}

.compare-table tbody th {
    width: 21%;
    color: #0a0a0a;
    font-size: 0.95rem;
    font-weight: 600;
    letter-spacing: -0.01em;
}

.compare-table td {
    color: #55555e;
    font-size: 0.9rem;
    line-height: 1.7;
}

.compare-section__footnote {
    max-width: 720px;
    margin: 1.5rem 0 0;
    color: #6b6b6b;
    font-size: 0.8rem;
    line-height: 1.65;
}

/* Reveal (MOTION 2): sekali-jalan, fade plus pergeseran kecil, tanpa loop.
   Kelas is-revealed ditambahkan lewat IntersectionObserver di script. */
[data-reveal] {
    opacity: 0;
    transform: translateY(20px);
    transition:
        opacity 680ms cubic-bezier(0.22, 0.61, 0.36, 1),
        transform 680ms cubic-bezier(0.22, 0.61, 0.36, 1);
}

[data-reveal].is-revealed {
    opacity: 1;
    transform: translateY(0);
}

/* Ritme stagger: item dalam satu daftar muncul menyusul satu sama lain. */
li[data-reveal]:nth-child(2) {
    transition-delay: 80ms;
}

li[data-reveal]:nth-child(3) {
    transition-delay: 160ms;
}

li[data-reveal]:nth-child(4) {
    transition-delay: 240ms;
}

li[data-reveal]:nth-child(5) {
    transition-delay: 320ms;
}

.hero-section__visual[data-reveal] {
    transition-delay: 120ms;
}

:global(a:focus-visible),
:global(button:focus-visible) {
    outline: 3px solid #6366f1;
    outline-offset: 3px;
}

@media (max-width: 900px) {
    .hero-section {
        height: auto;
        gap: 2rem;
        padding-block: clamp(1.5rem, 4vw, 2.25rem);
    }

    .hero-section h1 {
        font-size: clamp(2.1rem, 5vw, 2.9rem);
    }

    .hero-stats {
        max-width: 100%;
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .reviews-list {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .care-section {
        grid-template-columns: minmax(200px, 0.7fr) minmax(0, 1.3fr);
        gap: 3rem;
        padding-block: 6rem;
    }

    .quality-section__visual {
        min-height: min(540px, 70vh);
    }

    .quality-section__copy {
        padding-inline: 3rem;
    }
}

@media (max-width: 680px) {
    .site-header__inner,
    .site-footer,
    .hero-section,
    .care-section,
    .closing-section,
    .compare-section {
        width: calc(100% - 2rem);
    }

    .site-header__inner {
        min-height: 56px;
    }

    .menu-toggle {
        display: flex;
        width: 44px;
        height: 44px;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 6px;
        border: 1px solid #d7d7df;
        border-radius: 6px;
        background: transparent;
        cursor: pointer;
    }

    .menu-toggle span {
        width: 18px;
        height: 1.5px;
        background: #0a0a0a;
    }

    .site-nav {
        position: absolute;
        top: calc(100% + 1px);
        right: 0;
        left: 0;
        display: none;
        align-items: stretch;
        gap: 0;
        padding: 0.5rem 1rem 1rem;
        border-bottom: 1px solid #e8e8ec;
        background: #fafafa;
    }

    .site-nav--open {
        display: flex;
        flex-direction: column;
    }

    .site-nav a {
        display: flex;
        min-height: 48px;
        align-items: center;
        border-bottom: 1px solid #e8e8ec;
    }

    .site-nav .staff-link {
        justify-content: center;
        margin-top: 0.75rem;
        border: 1px solid #d7d7df;
    }

    .hero-section {
        grid-template-columns: 1fr;
        height: auto;
        gap: 1.5rem;
        padding-block: 1.25rem 1.75rem;
    }

    .hero-section h1 {
        font-size: clamp(1.85rem, 8vw, 2.45rem);
        line-height: 1.08;
    }

    .hero-section__description {
        font-size: 0.925rem;
        line-height: 1.5;
        margin: 0.65rem 0 1rem;
    }

    .button-primary {
        width: 100%;
        min-height: 44px;
        justify-content: center;
    }

    .hero-track {
        max-width: 100%;
        margin-top: 1rem;
    }

    .hero-photo img {
        height: clamp(180px, 46vw, 240px);
    }

    .hero-section__note {
        margin: 0.5rem 0 0;
        text-align: left;
        font-size: 0.72rem;
    }

    .hero-stats {
        grid-template-columns: repeat(2, minmax(0, 1fr));
        margin-top: 1rem;
    }

    .intro-strip {
        align-items: flex-start;
        flex-direction: column;
        gap: 0.6rem;
        padding: 1.75rem 1rem;
    }

    .intro-strip span {
        font-size: 0.85rem;
    }

    .hero-stats {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .reviews-section {
        padding-block: 4.5rem;
    }

    .reviews-section h2 {
        font-size: clamp(2.3rem, 9vw, 3.2rem);
    }

    .reviews-list {
        grid-template-columns: 1fr;
    }

    .care-section {
        grid-template-columns: 1fr;
        gap: 2rem;
        padding-block: 4.5rem;
    }

    .care-section h2,
    .quality-section h2,
    .compare-section h2 {
        font-size: clamp(2.35rem, 10vw, 3.4rem);
    }

    .process-item {
        grid-template-columns: 36px minmax(0, 1fr);
        gap: 0.75rem;
        padding-block: 1.25rem;
    }

    .process-item h3 {
        font-size: 1rem;
    }

    .process-item p {
        font-size: 0.86rem;
    }

    .quality-section {
        grid-template-columns: 1fr;
    }

    .quality-section__visual {
        min-height: 300px;
    }

    .quality-stamp {
        width: min(52%, 210px);
        font-size: 1.1rem;
    }
    .quality-section__copy {
        padding: 3.75rem 1rem 4rem;
    }

    .quality-section__lead {
        font-size: 0.9rem;
    }

    .quality-points li {
        grid-template-columns: 32px minmax(0, 1fr);
        gap: 0.75rem;
    }

    .quality-points p {
        font-size: 0.82rem;
    }

    .closing-section {
        padding-block: 4.5rem;
    }

    .closing-section h2 {
        font-size: clamp(2.5rem, 11vw, 4rem);
    }

    .compare-section {
        padding-block: 4.5rem;
    }

    /* Layar kecil: tabel mengalir menjadi kartu per baris, label kolom
       tampil ulang lewat data-label agar makna sel tetap jelas. */
    .compare-table {
        margin-top: 2.25rem;
    }

    .compare-table thead {
        display: none;
    }

    .compare-table tbody tr {
        display: block;
        margin-bottom: 1rem;
        border: 1px solid #e3e3e8;
        border-radius: 6px;
    }

    .compare-table th,
    .compare-table td {
        display: block;
        width: 100%;
        padding: 0.85rem 1rem;
        border: 0;
        border-bottom: 1px solid #e8e8ec;
    }

    .compare-table th:last-child,
    .compare-table td:last-child {
        border-bottom: 0;
    }

    .compare-table td::before {
        display: block;
        margin-bottom: 0.3rem;
        color: #0a0a0a;
        content: attr(data-label);
        font-size: 0.68rem;
        font-weight: 600;
        letter-spacing: 0.06em;
        text-transform: uppercase;
    }

    .compare-section__footnote {
        font-size: 0.78rem;
    }

    .site-footer {
        min-height: 0;
        flex-wrap: wrap;
        gap: 0.75rem 1rem;
        padding-block: 1.5rem;
    }

    .site-footer > p {
        width: 100%;
        order: 1;
        text-align: left;
    }
}

@media (min-width: 901px) and (max-height: 720px) {
    .site-header__inner {
        min-height: 52px;
    }

    .hero-section {
        padding-block: 0.65rem 0.85rem;
        gap: 1.5rem;
    }

    .eyebrow {
        margin-bottom: 0.35rem;
    }

    .hero-section h1 {
        font-size: clamp(1.95rem, 3.2vw, 2.65rem);
    }

    .hero-section__description {
        margin: 0.5rem 0 0.75rem;
        font-size: 0.875rem;
        line-height: 1.45;
    }

    .button-primary {
        min-height: 40px;
        padding: 0.5rem 1.1rem;
    }

    .hero-track {
        margin-top: 0.65rem;
    }

    .hero-track__label {
        margin-bottom: 0.25rem;
        font-size: 0.68rem;
    }

    .hero-track__input,
    .hero-track__submit {
        min-height: 40px;
    }

    .hero-stats {
        margin-top: 0.65rem;
    }

    .hero-stats__item {
        padding: 0.45rem 0.65rem;
    }

    .hero-stats__item dd {
        font-size: 1.05rem;
    }

    .hero-photo img {
        height: clamp(170px, 25vh, 240px);
    }

    .hero-section__note {
        margin-top: 0.35rem;
        font-size: 0.7rem;
    }
}

@media (prefers-reduced-motion: reduce) {
    :global(html) {
        scroll-behavior: auto;
    }

    /* Guard CSS untuk reveal: tampil langsung, tanpa delay stagger. */
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
