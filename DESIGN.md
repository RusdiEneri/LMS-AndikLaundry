# DESIGN.md — Laundry Management System (LMS)

> Design system umum untuk seluruh halaman web: landing page, auth, dashboard admin/kasir, halaman pelanggan, dan tracking order.
> Palet warna: brand identity "DataHarbor" (navy `#1F3C88`, biru `#1F7FC4`, teal `#39C5A8`).
> Struktur layout & komponen: landing page referensi sebelumnya (kartu rounded besar, section gelap-terang, pill button).

---

## 1. Prinsip Desain

| # | Prinsip | Penjelasan |
|---|---------|------------|
| 1 | **Bold & friendly** | Heading tebal, uppercase untuk hero/section title, tone ramah (bukan korporat kaku). |
| 2 | **Kontras gelap-terang** | Section bergantian antara background navy gelap dan putih/biru muda. |
| 3 | **Rounded everywhere** | Semua card, tombol, input, dan section pakai radius besar. Hindari sudut tajam. |
| 4 | **Satu aksen dominan** | Teal = aksi utama (CTA) + highlight kata penting. Biru = interaktif/info. Navy = struktur & teks. Jangan campur lebih dari itu. |
| 5 | **Fungsional dulu di area app** | Landing boleh ekspresif; dashboard harus padat, terbaca, dan cepat dipindai. |

---

## 2. Color Tokens

### 2.0 Sumber Palet

| Token | Hex | Status |
|-------|-----|--------|
| Brand Navy | `#1F3C88` | **Resmi** (swatch brand) |
| Brand Blue | `#1F7FC4` | **Resmi** (swatch brand) |
| Brand Teal | `#39C5A8` | **Resmi** (swatch brand) |
| Sisanya (navy gelap, tint, hover, netral) | — | **Turunan**: diestimasi dari mockup, silakan sesuaikan kalau ada brand guideline lengkap |

### 2.1 Brand

| Token | Hex | Pemakaian |
|-------|-----|-----------|
| `--color-navy-950` | `#081050` | Background gelap terdalam (mockup), footer *(turunan)* |
| `--color-navy-900` | `#13276F` | Sidebar, section gelap utama *(turunan)* |
| `--color-navy-800` | `#1F3C88` | **Brand Navy**: card di atas gelap, section brand, tombol primary solid |
| `--color-navy-700` | `#2B4FA6` | Hover/border pada elemen navy *(turunan)* |
| `--color-primary-700` | `#1A6CAA` | Teks link, tombol secondary, pill aktif (kontras aman dengan teks putih) *(turunan)* |
| `--color-primary-600` | `#1F7FC4` | **Brand Blue**: ikon, grafik, fill besar, focus ring, underline tab |
| `--color-primary-500` | `#3F98D8` | Hover primary, gradient terang *(turunan)* |
| `--color-primary-100` | `#D6ECFA` | Tint latar badge/info/selected *(turunan)* |
| `--color-accent-700` | `#0F7A66` | Teks teal di atas background terang *(turunan)* |
| `--color-accent-600` | `#2BA68C` | Hover/pressed CTA *(turunan)* |
| `--color-accent-500` | `#39C5A8` | **Brand Teal**: CTA utama, highlight kata di heading gelap, tab samping card |
| `--color-accent-100` | `#C9F0E6` | Tint latar card/badge teal *(turunan)* |

### 2.2 Neutral

| Token | Hex | Pemakaian |
|-------|-----|-----------|
| `--color-bg` | `#F4F8FB` | Background halaman terang (putih kebiruan) |
| `--color-surface` | `#FFFFFF` | Card, tabel, modal |
| `--color-border` | `#DCE5EE` | Border input/tabel di mode terang |
| `--color-text` | `#0E1B4D` | Teks utama di mode terang (navy, bukan hitam) |
| `--color-text-muted` | `#5E6B8C` | Teks sekunder, placeholder |
| `--color-text-on-dark` | `#FFFFFF` | Teks di atas navy |
| `--color-text-on-dark-muted` | `#B8C6E8` | Teks sekunder di atas navy |

### 2.3 Semantic

| Token | Hex | Pemakaian |
|-------|-----|-----------|
| `--color-success` | `#1E9E6A` | Selesai, lunas, berhasil (selalu sertakan ikon centang supaya beda dari teal aksen) |
| `--color-warning` | `#F5A623` | Menunggu, hampir telat |
| `--color-danger` | `#E5484D` | Gagal, telat, hapus |
| `--color-info` | `#1F7FC4` | Informasi netral (sama dengan primary) |

### 2.4 Gradient

```css
--gradient-hero: linear-gradient(180deg, #F4F8FB 0%, #1F3C88 55%, #081050 100%);
--gradient-dark-section: linear-gradient(180deg, #13276F 0%, #081050 100%);
--gradient-primary-card: linear-gradient(135deg, #1F7FC4 0%, #1F3C88 100%);
--gradient-accent-card: linear-gradient(135deg, #39C5A8 0%, #1F7FC4 100%);
--gradient-footer: linear-gradient(180deg, #13276F 0%, #081050 100%);
```

### 2.5 Aturan Kontras (WAJIB)

Rasio dihitung manual dari hex di atas; verifikasi ulang dengan tool kontras sebelum rilis.

| Kombinasi | Rasio | Keputusan |
|-----------|-------|-----------|
| Putih di atas `#39C5A8` (teal) | ~2.1:1 ❌ | Tombol teal pakai **teks `#081050`** (~8:1) |
| Putih di atas `#1F7FC4` (biru) | ~4.3:1 ⚠️ | Hanya untuk teks besar/bold ≥ 18px. Teks kecil pakai `primary-700` (~5.6:1) |
| Putih di atas `#1F3C88` (navy) | ~10:1 ✅ | Aman untuk semua ukuran |
| `#39C5A8` di atas `#081050` / `#1F3C88` | tinggi ✅ | Aman untuk highlight di section gelap |
| `#39C5A8` sebagai teks di atas putih | ~2.1:1 ❌ | Pakai `accent-700` `#0F7A66` (~5:1) |
| `#1F7FC4` sebagai teks di atas putih | ~4.3:1 ⚠️ | Pakai `primary-700` untuk link/teks kecil |

Minimal rasio: 4.5:1 teks biasa, 3:1 teks besar (≥ 18px bold / 24px regular) dan komponen UI.

---

## 3. Typography

| Peran | Font | Fallback |
|-------|------|----------|
| Heading / Display | **Lexend** (alternatif: Outfit, Syne) | `system-ui, sans-serif` |
| Body / UI | **Poppins** (alternatif: Inter) | `system-ui, sans-serif` |
| Mono (kode order, nomor nota) | **JetBrains Mono** | `ui-monospace, monospace` |

### Type Scale

| Token | Ukuran (desktop / mobile) | Weight | Line-height | Pemakaian |
|-------|---------------------------|--------|-------------|-----------|
| `display` | 64px / 40px | 800 | 1.05 | Hero landing, uppercase |
| `h1` | 44px / 32px | 800 | 1.1 | Judul section landing, uppercase |
| `h2` | 32px / 24px | 700 | 1.2 | Judul halaman dashboard |
| `h3` | 24px / 20px | 700 | 1.25 | Judul card/modal |
| `h4` | 18px | 600 | 1.3 | Sub-judul, header tabel grup |
| `body-lg` | 16px | 400 | 1.6 | Paragraf utama |
| `body` | 14px | 400 | 1.6 | Default UI, isi tabel |
| `caption` | 12px | 400–500 | 1.5 | Meta, label kecil, tanggal |
| `overline` | 11px | 600 | 1.4 | Label kategori, letter-spacing 0.08em, uppercase |

**Aturan:**
- Uppercase **hanya** untuk `display`, `h1`, `overline`, dan tombol kecil. Jangan uppercase untuk tabel/form.
- Highlight kata penting di heading gelap dengan warna `--color-accent-500` (maksimal 1–2 kata per heading).
- Angka di tabel/harga pakai `font-variant-numeric: tabular-nums`.

---

## 4. Spacing, Radius, Shadow

### Spacing (basis 4px)

`4 · 8 · 12 · 16 · 24 · 32 · 48 · 64 · 96`

| Konteks | Nilai |
|---------|-------|
| Padding card | 24px (landing) / 16–20px (dashboard) |
| Gap antar card | 16–24px |
| Padding section landing | 80–96px vertikal |
| Container max-width | 1200px (landing) / 1440px (dashboard) |

### Radius

| Token | Nilai | Pemakaian |
|-------|-------|-----------|
| `--radius-sm` | 8px | Badge kecil, checkbox |
| `--radius-md` | 12px | Input, dropdown |
| `--radius-lg` | 20px | Card standar |
| `--radius-xl` | 28px | Card besar/feature card |
| `--radius-section` | 40px | Sudut atas/bawah section (transisi gelap ↔ terang) |
| `--radius-pill` | 999px | Tombol, search bar, chip, badge status |

### Shadow & Border

```css
--shadow-sm: 0 1px 2px rgba(8, 16, 80, 0.06);
--shadow-md: 0 8px 24px rgba(8, 16, 80, 0.10);
--shadow-lg: 0 20px 48px rgba(8, 16, 80, 0.18);
--border-on-dark: 2px solid rgba(255, 255, 255, 0.85); /* outline card di atas navy */
```

---

## 5. Layout & Struktur Halaman

### 5.1 Landing / Halaman Publik

Urutan section mengikuti pola referensi:

1. **Navbar**: logo kiri, menu tengah (Beranda, Layanan, Harga, Lacak Order, Kontak), CTA/hamburger kanan.
2. **Hero**: headline besar uppercase + sub-teks + search/input utama (mis. "Cek status order") + ilustrasi besar di kanan.
3. **Feature cards**: 3 card ringkas (1 card highlight berwarna primary, 2 card outline di atas navy).
4. **Section bernomor** (`01`, `02`, ...): judul + deskripsi + tombol, disandingkan carousel card.
5. **Stat/Promo banner**: card primary besar berisi angka kunci ("200+ Pelanggan Puas") + card putih overlapping dengan tab teal vertikal di sisi kanan.
6. **Artikel/Tips**: 2 card gambar dengan overlay gradasi gelap di bawah.
7. **CTA newsletter/daftar**: heading besar + input email pill dengan tombol teal di dalam.
8. **Footer**: garis tipis, logo, link, copyright, ikon sosial.

### 5.2 App (Dashboard)

```
┌──────────────┬──────────────────────────────────────────┐
│              │  Topbar: search · notifikasi · profil    │
│   Sidebar    ├──────────────────────────────────────────┤
│   navy-900   │  Page header: h2 + breadcrumb + aksi     │
│   240px      ├──────────────────────────────────────────┤
│              │  Content: stat cards → tabel/grafik/form │
│              │  Background: --color-bg                  │
└──────────────┴──────────────────────────────────────────┘
```

- Sidebar: background `navy-900`, item aktif = pill `primary-700`, ikon + label, collapsible ke 72px.
- Topbar: putih, tinggi 64px, shadow-sm.
- Konten: grid 12 kolom, gap 24px.
- Mobile: sidebar jadi drawer, tabel jadi card list.

### 5.3 Breakpoint

| Nama | Min-width |
|------|-----------|
| `sm` | 640px |
| `md` | 768px |
| `lg` | 1024px |
| `xl` | 1280px |
| `2xl` | 1536px |

---

## 6. Komponen

### 6.1 Button

| Varian | Style |
|--------|-------|
| **Primary (CTA)** | BG `accent-500` (teal), teks `navy-950`, pill, bold 14px, padding 12×24. Hover `accent-600`. |
| **Secondary** | BG `primary-700`, teks putih, pill. Alternatif: BG `navy-800`, teks putih. |
| **Outline (on dark)** | Transparan, border 1.5px putih, teks putih, pill. |
| **Outline (on light)** | Transparan, border 1.5px `navy-900`, teks navy. |
| **Ghost** | Tanpa border/BG, teks `primary-700`, hover BG `primary-100`. |
| **Danger** | BG `danger`, teks putih. |
| **Icon button** | 40×40, bulat, ikon 20px. |

State wajib: `hover`, `focus-visible` (ring 3px `primary-500` offset 2px), `active`, `disabled` (opacity 0.5, no pointer), `loading` (spinner + teks tetap).
Ukuran: `sm` 32px · `md` 40px · `lg` 48px.

### 6.2 Input & Form

- Tinggi 44px, radius `md`, border 1px `--color-border`, BG putih, padding 0 16px.
- Focus: border `primary-600` + ring 3px `primary-100`.
- Error: border `danger`, pesan 12px `danger` di bawah input.
- Label di atas input, 13px weight 500, jangan pakai placeholder sebagai label.
- **Search pill** (hero): BG putih, radius pill, tombol teal menempel di dalam sisi kanan.
- Komponen yang dibutuhkan: text, number, select, combobox/search, date picker, textarea, checkbox, radio, toggle, file upload.

### 6.3 Card

| Varian | Spesifikasi |
|--------|-------------|
| **Card putih** | BG putih, radius `lg`, shadow-md, padding 20–24px. |
| **Card highlight** | `gradient-primary-card`, teks putih, border putih tipis, radius `xl`. |
| **Card outline-dark** | BG `navy-800` semi-transparan, border `--border-on-dark`, teks putih. |
| **Card gambar** | Gambar penuh + overlay gradasi gelap bawah, judul putih bold di bawah. |
| **Card dengan tab samping** | Card putih + strip teal vertikal di sisi kanan berisi teks vertikal (label/kategori/nama paket). |
| **Stat card (dashboard)** | Ikon dalam lingkaran tint, angka `h2`, label `caption`, delta (naik hijau / turun merah). |

### 6.4 Section Number Badge

Lingkaran 40px, border 1.5px (putih di gelap / navy di terang), isi angka `01`–`0X` 14px bold. Dipakai di samping judul section pada landing.

### 6.5 Badge Status (khusus laundry)

Pill, 12px weight 600, padding 4×12, dot 6px di kiri.

| Status | BG | Teks |
|--------|----|----|
| Diterima | `#E3E8F7` | `#1F3C88` |
| Dicuci | `#D6ECFA` | `#14598F` |
| Dikeringkan | `#FFF1D6` | `#8A5A00` |
| Disetrika | `#FFE8D6` | `#A84A12` |
| Siap diambil | `#C9F0E6` | `#0F6F5B` |
| Selesai | `#E8EAF2` | `#4A4F73` |
| Dibatalkan / Telat | `#FDE3E4` | `#B3262B` |

Pembayaran: **Lunas** (success), **DP/Sebagian** (warning), **Belum bayar** (danger).

### 6.6 Tabel

- Header: BG `#EDF3F9`, teks 12px uppercase muted, weight 600.
- Row: tinggi 52px, border-bottom 1px `--color-border`, hover BG `#F7FAFD`.
- Kolom angka rata kanan, tabular-nums.
- Fitur wajib: sort, filter, search, pagination, bulk select, row action menu (⋯), empty state, skeleton loading.
- Mobile: ubah jadi card list (nama + status + aksi utama).

### 6.7 Navigasi

- **Navbar landing**: transparan di atas hero, jadi putih + shadow saat scroll; link aktif berwarna `accent-700` di background terang, `accent-500` di background gelap.
- **Sidebar**: grup menu dengan overline label (OPERASIONAL, KEUANGAN, PENGATURAN).
- **Tabs**: underline 2px `primary-600` untuk aktif.
- **Breadcrumb**: 13px muted, separator `/`.
- **Pagination**: pill kecil, aktif BG `primary-700` teks putih.

### 6.8 Overlay & Feedback

| Komponen | Spesifikasi |
|----------|-------------|
| **Modal** | Putih, radius `xl`, max-width 480–640px, backdrop `rgba(8,16,80,0.6)` blur 4px. |
| **Drawer** | Sisi kanan, lebar 420px, untuk detail order / form cepat. |
| **Toast** | Pojok kanan atas, radius `md`, ikon semantic, auto-dismiss 4 detik. |
| **Alert inline** | BG tint semantic, border kiri 4px semantic. |
| **Tooltip** | BG `navy-900`, teks putih 12px, radius 8px. |
| **Empty state** | Ilustrasi flat + judul + 1 kalimat + 1 tombol CTA. |
| **Skeleton** | BG `#E6EDF4`, animasi shimmer 1.4s. |

### 6.9 Komponen Spesifik LMS Laundry

| Komponen | Isi |
|----------|-----|
| **Order card / row** | No. nota (mono), nama pelanggan, layanan, berat/qty, status badge, total, tanggal masuk → estimasi selesai. |
| **Timeline tracking** | Stepper vertikal/horizontal: Diterima → Dicuci → Dikeringkan → Disetrika → Siap → Selesai. Step selesai = lingkaran `primary-600` + centang; aktif = ring teal (`accent-500`); belum = abu. |
| **Service picker** | Card selectable (kiloan, satuan, express, dry clean) dengan harga, state terpilih = border `primary-600` + BG `primary-100`. |
| **Nota / receipt** | Layout putih, font mono untuk nomor, siap cetak (lebar 58mm / 80mm / A5). |
| **Stat ringkasan** | Order hari ini, pendapatan, order telat, siap diambil. |
| **Grafik** | Line/bar pakai `primary-600` + `accent-500`; grid garis `--color-border`. |

---

## 7. Ilustrasi & Ikon

- **Ilustrasi**: flat, warna jenuh, karakter ekspresif, tema laundry (keranjang, mesin cuci, sabun, gelembung, setrika, baju). Palet harus mengikuti token di atas (navy, biru, teal, putih; bentuk geometris sederhana, flat). Dipakai di hero, empty state, halaman error, dan onboarding.
- **Ikon**: line icon konsisten, stroke 1.75–2px, ukuran 20/24px, pakai **Lucide** atau **Phosphor**. Jangan campur dua library.
- **Gambar foto/kartu**: radius `lg`, rasio 4:3 atau 16:9, selalu ada overlay gradasi kalau ada teks di atasnya.

---

## 8. Motion

| Token | Nilai |
|-------|-------|
| `--ease-standard` | `cubic-bezier(0.2, 0, 0, 1)` |
| `--duration-fast` | 120ms (hover, focus) |
| `--duration-base` | 200ms (dropdown, tab) |
| `--duration-slow` | 320ms (modal, drawer, page transition) |

- Hover card: naik 2–4px + shadow-lg.
- Carousel: geser horizontal + tombol panah bulat outline.
- Ilustrasi hero boleh ada float halus (translateY ±6px, 4s loop) **hanya di landing**.
- Wajib hormati `prefers-reduced-motion: reduce` (matikan animasi non-esensial).

---

## 9. Aksesibilitas

- Kontras minimal sesuai bagian 2.5.
- Semua elemen interaktif bisa diakses keyboard; `focus-visible` selalu terlihat.
- Target sentuh minimal 44×44px di mobile.
- Status **tidak boleh hanya mengandalkan warna**: selalu sertakan teks atau ikon.
- Gambar bermakna punya `alt`; ilustrasi dekoratif `alt=""`.
- Form: label terhubung (`for`/`id`), error diumumkan lewat `aria-live="polite"`.
- Heading hierarkis (satu `h1` per halaman).

---

## 10. Implementasi

### 10.1 CSS Variables

```css
:root {
  /* Brand */
  --color-navy-950: #081050;
  --color-navy-900: #13276F;
  --color-navy-800: #1F3C88; /* brand navy */
  --color-navy-700: #2B4FA6;
  --color-primary-700: #1A6CAA;
  --color-primary-600: #1F7FC4; /* brand blue */
  --color-primary-500: #3F98D8;
  --color-primary-100: #D6ECFA;
  --color-accent-700: #0F7A66;
  --color-accent-600: #2BA68C;
  --color-accent-500: #39C5A8; /* brand teal */
  --color-accent-100: #C9F0E6;

  /* Neutral */
  --color-bg: #F4F8FB;
  --color-surface: #FFFFFF;
  --color-border: #DCE5EE;
  --color-text: #0E1B4D;
  --color-text-muted: #5E6B8C;
  --color-text-on-dark: #FFFFFF;
  --color-text-on-dark-muted: #B8C6E8;

  /* Semantic */
  --color-success: #1E9E6A;
  --color-warning: #F5A623;
  --color-danger: #E5484D;
  --color-info: #1F7FC4;

  /* Radius */
  --radius-sm: 8px;
  --radius-md: 12px;
  --radius-lg: 20px;
  --radius-xl: 28px;
  --radius-section: 40px;
  --radius-pill: 999px;

  /* Shadow */
  --shadow-sm: 0 1px 2px rgba(16, 25, 77, 0.06);
  --shadow-md: 0 8px 24px rgba(16, 25, 77, 0.10);
  --shadow-lg: 0 20px 48px rgba(16, 25, 77, 0.18);

  /* Font */
  --font-heading: "Lexend", system-ui, sans-serif;
  --font-body: "Poppins", system-ui, sans-serif;
  --font-mono: "JetBrains Mono", ui-monospace, monospace;
}
```

### 10.2 Tailwind Config (opsional)

```js
// tailwind.config.js
export default {
  theme: {
    extend: {
      colors: {
        navy: { 950: "#081050", 900: "#13276F", 800: "#1F3C88", 700: "#2B4FA6" },
        primary: { 100: "#D6ECFA", 500: "#3F98D8", 600: "#1F7FC4", 700: "#1A6CAA" },
        accent: { 100: "#C9F0E6", 500: "#39C5A8", 600: "#2BA68C", 700: "#0F7A66" },
        surface: "#F4F8FB",
      },
      borderRadius: { lg: "20px", xl: "28px", section: "40px" },
      fontFamily: {
        heading: ["Lexend", "system-ui", "sans-serif"],
        body: ["Poppins", "system-ui", "sans-serif"],
        mono: ["JetBrains Mono", "ui-monospace", "monospace"],
      },
      boxShadow: {
        md: "0 8px 24px rgba(8,16,80,0.10)",
        lg: "0 20px 48px rgba(8,16,80,0.18)",
      },
    },
  },
};
```

### 10.3 Google Fonts

```html
<link rel="preconnect" href="https://fonts.googleapis.com" />
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
<link href="https://fonts.googleapis.com/css2?family=Lexend:wght@600;700;800&family=Poppins:wght@400;500;600&family=JetBrains+Mono:wght@500&display=swap" rel="stylesheet" />
```

---

## 11. Do & Don't

| ✅ Do | ❌ Don't |
|------|---------|
| Pakai teal hanya untuk aksi utama per layar | Pakai teal di banyak elemen sekaligus |
| Radius besar dan konsisten | Campur sudut tajam dan membulat |
| Teks navy di atas background terang | Teks hitam murni `#000` |
| Highlight 1–2 kata dengan teal di heading gelap | Mewarnai seluruh heading teal |
| Dashboard padat, tenang, mudah dipindai | Bawa ilustrasi besar dan animasi hero ke halaman kerja kasir |
| Status = warna + teks/ikon | Status hanya berupa warna |
| Satu library ikon | Campur Lucide + FontAwesome + emoji |

---

## 12. Checklist Sebelum Rilis Halaman Baru

- [ ] Hanya memakai token warna, radius, dan font dari dokumen ini
- [ ] Ada state: default, hover, focus, disabled, loading, error, empty
- [ ] Responsif di 360px, 768px, 1280px
- [ ] Kontras lolos WCAG AA
- [ ] Bisa dioperasikan penuh dengan keyboard
- [ ] Tidak ada hardcoded hex di luar token
