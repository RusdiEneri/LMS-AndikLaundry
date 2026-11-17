# DESIGN.md — Laundry Management System (LMS)

> Design system umum untuk seluruh halaman web: landing page, auth, dashboard admin/kasir, halaman pelanggan, dan tracking order.
> Referensi visual: landing page "Every Sunday" (navy gelap + biru periwinkle + aksen oranye, kartu rounded besar, ilustrasi flat penuh warna).

---

## 1. Prinsip Desain

| # | Prinsip | Penjelasan |
|---|---------|------------|
| 1 | **Bold & friendly** | Heading tebal, uppercase untuk hero/section title, tone ramah (bukan korporat kaku). |
| 2 | **Kontras gelap-terang** | Section bergantian antara background navy gelap dan cream/putih. |
| 3 | **Rounded everywhere** | Semua card, tombol, input, dan section pakai radius besar. Hindari sudut tajam. |
| 4 | **Satu aksen dominan** | Oranye = aksi utama (CTA). Kuning = highlight kata penting. Jangan campur lebih dari itu. |
| 5 | **Fungsional dulu di area app** | Landing boleh ekspresif; dashboard harus padat, terbaca, dan cepat dipindai. |

---

## 2. Color Tokens

### 2.1 Brand

| Token | Hex | Pemakaian |
|-------|-----|-----------|
| `--color-navy-950` | `#0A1033` | Background gelap terdalam, footer |
| `--color-navy-900` | `#10194D` | Background section gelap utama, sidebar |
| `--color-navy-800` | `#1A2566` | Card di atas background gelap |
| `--color-navy-700` | `#26338A` | Border/hover pada elemen gelap |
| `--color-primary-600` | `#4F5FE8` | Primary: card highlight, link aktif, header card biru |
| `--color-primary-500` | `#6577F0` | Gradient atas card biru, hover primary |
| `--color-primary-100` | `#E6E9FD` | Tint latar badge/info |
| `--color-accent-500` | `#FF8A5B` | CTA utama (tombol oranye), tab samping card |
| `--color-accent-600` | `#F26B3A` | Hover/pressed CTA, teks oranye di atas putih |
| `--color-highlight` | `#FFE14D` | Highlight kata di heading gelap (mis. "UPDATE") |

### 2.2 Neutral

| Token | Hex | Pemakaian |
|-------|-----|-----------|
| `--color-bg` | `#F6F4EF` | Background halaman terang (cream) |
| `--color-surface` | `#FFFFFF` | Card, tabel, modal |
| `--color-border` | `#E3E1DA` | Border input/tabel di mode terang |
| `--color-text` | `#10194D` | Teks utama di mode terang (pakai navy, bukan hitam) |
| `--color-text-muted` | `#6B7194` | Teks sekunder, placeholder |
| `--color-text-on-dark` | `#FFFFFF` | Teks di atas navy |
| `--color-text-on-dark-muted` | `#B5BAE0` | Teks sekunder di atas navy |

### 2.3 Semantic

| Token | Hex | Pemakaian |
|-------|-----|-----------|
| `--color-success` | `#22A06B` | Selesai, lunas, berhasil |
| `--color-warning` | `#F5A623` | Menunggu, hampir telat |
| `--color-danger` | `#E5484D` | Gagal, telat, hapus |
| `--color-info` | `#4F5FE8` | Informasi netral (sama dengan primary) |

### 2.4 Gradient

```css
--gradient-hero: linear-gradient(180deg, #F6F4EF 0%, #10194D 55%, #0A1033 100%);
--gradient-dark-section: linear-gradient(180deg, #10194D 0%, #1B1440 100%);
--gradient-primary-card: linear-gradient(135deg, #6577F0 0%, #4F5FE8 100%);
--gradient-footer: linear-gradient(180deg, #10194D 0%, #3A1E3F 100%);
```

### 2.5 Aturan Kontras (WAJIB)

- Teks **putih di atas oranye `#FF8A5B` hanya ~2.4:1**, gagal WCAG AA. Untuk tombol oranye, pakai **teks navy `#10194D`**, atau gunakan `--color-accent-600` kalau tetap mau teks putih (untuk teks bold besar saja).
- Teks oranye di atas putih: pakai `--color-accent-600`, bukan `--color-accent-500`.
- Minimal rasio kontras: 4.5:1 teks biasa, 3:1 teks besar (≥ 18px bold / 24px regular) dan komponen UI.

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
- Highlight kata penting di heading gelap dengan warna `--color-highlight` (maksimal 1–2 kata per heading).
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
--shadow-sm: 0 1px 2px rgba(16, 25, 77, 0.06);
--shadow-md: 0 8px 24px rgba(16, 25, 77, 0.10);
--shadow-lg: 0 20px 48px rgba(16, 25, 77, 0.18);
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
5. **Stat/Promo banner**: card primary besar berisi angka kunci ("200+ Pelanggan Puas") + card putih overlapping dengan tab oranye vertikal di sisi kanan.
6. **Artikel/Tips**: 2 card gambar dengan overlay gradasi gelap di bawah.
7. **CTA newsletter/daftar**: heading besar + input email pill dengan tombol oranye di dalam.
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

- Sidebar: background `navy-900`, item aktif = pill `primary-600`, ikon + label, collapsible ke 72px.
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
| **Primary (CTA)** | BG `accent-500`, teks navy, pill, bold 14px, padding 12×24. Hover `accent-600`. |
| **Secondary** | BG `primary-600`, teks putih, pill. |
| **Outline (on dark)** | Transparan, border 1.5px putih, teks putih, pill. |
| **Outline (on light)** | Transparan, border 1.5px `navy-900`, teks navy. |
| **Ghost** | Tanpa border/BG, teks primary, hover BG `primary-100`. |
| **Danger** | BG `danger`, teks putih. |
| **Icon button** | 40×40, bulat, ikon 20px. |

State wajib: `hover`, `focus-visible` (ring 3px `primary-500` offset 2px), `active`, `disabled` (opacity 0.5, no pointer), `loading` (spinner + teks tetap).
Ukuran: `sm` 32px · `md` 40px · `lg` 48px.

### 6.2 Input & Form

- Tinggi 44px, radius `md`, border 1px `--color-border`, BG putih, padding 0 16px.
- Focus: border `primary-600` + ring 3px `primary-100`.
- Error: border `danger`, pesan 12px `danger` di bawah input.
- Label di atas input, 13px weight 500, jangan pakai placeholder sebagai label.
- **Search pill** (hero): BG putih, radius pill, tombol oranye menempel di dalam sisi kanan.
- Komponen yang dibutuhkan: text, number, select, combobox/search, date picker, textarea, checkbox, radio, toggle, file upload.

### 6.3 Card

| Varian | Spesifikasi |
|--------|-------------|
| **Card putih** | BG putih, radius `lg`, shadow-md, padding 20–24px. |
| **Card highlight** | `gradient-primary-card`, teks putih, border putih tipis, radius `xl`. |
| **Card outline-dark** | BG `navy-800` semi-transparan, border `--border-on-dark`, teks putih. |
| **Card gambar** | Gambar penuh + overlay gradasi gelap bawah, judul putih bold di bawah. |
| **Card dengan tab samping** | Card putih + strip oranye vertikal di sisi kanan berisi teks vertikal (label/kategori/nama paket). |
| **Stat card (dashboard)** | Ikon dalam lingkaran tint, angka `h2`, label `caption`, delta (naik hijau / turun merah). |

### 6.4 Section Number Badge

Lingkaran 40px, border 1.5px (putih di gelap / navy di terang), isi angka `01`–`0X` 14px bold. Dipakai di samping judul section pada landing.

### 6.5 Badge Status (khusus laundry)

Pill, 12px weight 600, padding 4×12, dot 6px di kiri.

| Status | BG | Teks |
|--------|----|----|
| Diterima | `#E6E9FD` | `#26338A` |
| Dicuci | `#DCEBFF` | `#1B5FBF` |
| Dikeringkan | `#FFF1D6` | `#8A5A00` |
| Disetrika | `#FFE4D9` | `#B2411A` |
| Siap diambil | `#D9F5E8` | `#12724A` |
| Selesai | `#E8EAF2` | `#4A4F73` |
| Dibatalkan / Telat | `#FDE3E4` | `#B3262B` |

Pembayaran: **Lunas** (success), **DP/Sebagian** (warning), **Belum bayar** (danger).

### 6.6 Tabel

- Header: BG `#F1F2FA`, teks 12px uppercase muted, weight 600.
- Row: tinggi 52px, border-bottom 1px `--color-border`, hover BG `#FAFAFD`.
- Kolom angka rata kanan, tabular-nums.
- Fitur wajib: sort, filter, search, pagination, bulk select, row action menu (⋯), empty state, skeleton loading.
- Mobile: ubah jadi card list (nama + status + aksi utama).

### 6.7 Navigasi

- **Navbar landing**: transparan di atas hero, jadi putih + shadow saat scroll; link aktif berwarna `accent-500`.
- **Sidebar**: grup menu dengan overline label (OPERASIONAL, KEUANGAN, PENGATURAN).
- **Tabs**: underline 2px `primary-600` untuk aktif.
- **Breadcrumb**: 13px muted, separator `/`.
- **Pagination**: pill kecil, aktif BG `primary-600` teks putih.

### 6.8 Overlay & Feedback

| Komponen | Spesifikasi |
|----------|-------------|
| **Modal** | Putih, radius `xl`, max-width 480–640px, backdrop `rgba(10,16,51,0.6)` blur 4px. |
| **Drawer** | Sisi kanan, lebar 420px, untuk detail order / form cepat. |
| **Toast** | Pojok kanan atas, radius `md`, ikon semantic, auto-dismiss 4 detik. |
| **Alert inline** | BG tint semantic, border kiri 4px semantic. |
| **Tooltip** | BG `navy-900`, teks putih 12px, radius 8px. |
| **Empty state** | Ilustrasi flat + judul + 1 kalimat + 1 tombol CTA. |
| **Skeleton** | BG `#ECEBF3`, animasi shimmer 1.4s. |

### 6.9 Komponen Spesifik LMS Laundry

| Komponen | Isi |
|----------|-----|
| **Order card / row** | No. nota (mono), nama pelanggan, layanan, berat/qty, status badge, total, tanggal masuk → estimasi selesai. |
| **Timeline tracking** | Stepper vertikal/horizontal: Diterima → Dicuci → Dikeringkan → Disetrika → Siap → Selesai. Step selesai = lingkaran `primary-600` + centang; aktif = ring oranye; belum = abu. |
| **Service picker** | Card selectable (kiloan, satuan, express, dry clean) dengan harga, state terpilih = border `primary-600` + BG `primary-100`. |
| **Nota / receipt** | Layout putih, font mono untuk nomor, siap cetak (lebar 58mm / 80mm / A5). |
| **Stat ringkasan** | Order hari ini, pendapatan, order telat, siap diambil. |
| **Grafik** | Line/bar pakai `primary-600` + `accent-500`; grid garis `--color-border`. |

---

## 7. Ilustrasi & Ikon

- **Ilustrasi**: flat, warna jenuh, karakter ekspresif, tema laundry (keranjang, mesin cuci, sabun, gelembung, setrika, baju). Palet harus mengikuti token di atas (biru, oranye, kuning, ungu gelap). Dipakai di hero, empty state, halaman error, dan onboarding.
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
  --color-navy-950: #0A1033;
  --color-navy-900: #10194D;
  --color-navy-800: #1A2566;
  --color-navy-700: #26338A;
  --color-primary-600: #4F5FE8;
  --color-primary-500: #6577F0;
  --color-primary-100: #E6E9FD;
  --color-accent-500: #FF8A5B;
  --color-accent-600: #F26B3A;
  --color-highlight: #FFE14D;

  /* Neutral */
  --color-bg: #F6F4EF;
  --color-surface: #FFFFFF;
  --color-border: #E3E1DA;
  --color-text: #10194D;
  --color-text-muted: #6B7194;

  /* Semantic */
  --color-success: #22A06B;
  --color-warning: #F5A623;
  --color-danger: #E5484D;

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
        navy: { 950: "#0A1033", 900: "#10194D", 800: "#1A2566", 700: "#26338A" },
        primary: { 100: "#E6E9FD", 500: "#6577F0", 600: "#4F5FE8" },
        accent: { 500: "#FF8A5B", 600: "#F26B3A" },
        highlight: "#FFE14D",
        cream: "#F6F4EF",
      },
      borderRadius: { lg: "20px", xl: "28px", section: "40px" },
      fontFamily: {
        heading: ["Lexend", "system-ui", "sans-serif"],
        body: ["Poppins", "system-ui", "sans-serif"],
        mono: ["JetBrains Mono", "ui-monospace", "monospace"],
      },
      boxShadow: {
        md: "0 8px 24px rgba(16,25,77,0.10)",
        lg: "0 20px 48px rgba(16,25,77,0.18)",
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
| Pakai oranye hanya untuk aksi utama per layar | Pakai oranye di banyak elemen sekaligus |
| Radius besar dan konsisten | Campur sudut tajam dan membulat |
| Teks navy di atas background terang | Teks hitam murni `#000` |
| Highlight 1–2 kata dengan kuning di heading gelap | Mewarnai seluruh heading kuning |
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
