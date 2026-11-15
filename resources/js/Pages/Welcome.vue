<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

defineProps<{
    canLogin?: boolean;
}>();

const menuOpen = ref(false);
const menuLabel = computed(() => menuOpen.value ? 'Tutup navigasi' : 'Buka navigasi');

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

            <section class="intro-strip" aria-label="Ringkasan pendekatan perawatan">
                <p data-reveal>Dirawat, bukan sekadar dicuci.</p>
                <span data-reveal>Setiap tahap dimulai dari kondisi pakaian yang kami terima.</span>
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
    min-height: 72px;
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
    min-height: 610px;
    margin-inline: auto;
    align-items: center;
    grid-template-columns: minmax(0, 0.96fr) minmax(0, 1.04fr);
    gap: clamp(2rem, 7vw, 7rem);
    padding-block: 5rem 5.5rem;
}

.hero-section__copy {
    max-width: 610px;
    padding-block: 1rem;
}

.eyebrow {
    margin: 0 0 1.25rem;
    color: #51515b;
    font-size: 0.75rem;
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
    letter-spacing: -0.055em;
}

.hero-section h1 {
    max-width: 720px;
    font-size: clamp(3.4rem, 7vw, 6.4rem);
    line-height: 0.98;
}

.hero-section h1 span {
    color: #686873;
}

.hero-section__description {
    max-width: 470px;
    margin: 1.75rem 0 2rem;
    color: #55555e;
    font-size: 1.0625rem;
    line-height: 1.8;
}

.button-primary {
    display: inline-flex;
    min-height: 48px;
    align-items: center;
    justify-content: center;
    padding: 0.75rem 1.35rem;
    border-radius: 6px;
    /* #4F46E5 = primary-hover token dari DESIGN.md; dipakai sebagai warna dasar
       agar teks putih memenuhi kontras WCAG AA 4.5:1 (R-25). */
    background: #4f46e5;
    color: #fff;
    font-size: 0.925rem;
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
    height: clamp(320px, 40vw, 460px);
    object-fit: cover;
}

.hero-photo__credit {
    padding: 0.6rem 0.85rem;
    border-top: 1px solid #e8e8ec;
    background: #fafafa;
    color: #6b6b6b;
    font-size: 0.68rem;
    line-height: 1.5;
}

.hero-section__note {
    max-width: 360px;
    margin: 1rem 0 0 auto;
    color: #55555e;
    font-size: 0.8rem;
    line-height: 1.6;
    text-align: right;
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
    min-height: 600px;
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
        min-height: 0;
        gap: 2.5rem;
        padding-block: 5rem;
    }

    .hero-section h1 {
        font-size: clamp(3.3rem, 8vw, 5.2rem);
    }

    .care-section {
        grid-template-columns: minmax(200px, 0.7fr) minmax(0, 1.3fr);
        gap: 3rem;
        padding-block: 6rem;
    }

    .quality-section__visual {
        min-height: 540px;
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
        min-height: 64px;
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
        gap: 2.75rem;
        padding-block: 4rem 3.5rem;
    }

    .hero-section h1 {
        font-size: clamp(3.1rem, 14vw, 5rem);
    }

    .hero-section__description {
        font-size: 1rem;
    }

    .hero-photo img {
        height: clamp(240px, 52vw, 340px);
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
