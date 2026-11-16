# DESIGN.md — Andik Laundry Modern System

## 1. Overview & Brand Identity
Sistem desain modern, ramah, dan profesional untuk **Andik Laundry** — layanan binatu harian berkualitas tinggi berbasis operasional digital (POS terpadu, nota digital WhatsApp, pelacakan live, dan pembayaran QRIS).
Terinspirasi dari pendekatan binatu modern terkemuka di Indonesia (*Chingu Laundry, Kago Laundry, Ion Laundry*), mengedepankan prinsip:
- **Higienis & Transparan**: 1 mesin 1 pelanggan (tidak pernah dicampur), timbangan riil, nota digital transparan.
- **Modern & Anti-Slop**: Tipografi bersih, spasi bernapas, kartu gallery-frame berborder halus, micro-interaction bermakna tanpa animasi berlebihan atau dekorasi generik AI.
- **Mobile-First & High-Conversion**: Pelanggan mudah memesan antar-jemput via WhatsApp, mengecek tarif resmi, dan melacak progres cucian secara mandiri tanpa login.

---

## 2. Color Palette
- **Primary Interactive** (`#4F46E5` / `#6366F1`): Tombol aksi utama, tab aktif, link, cincin fokus aksesibilitas.
- **Primary Hover** (`#4338CA`): Status hover pada tombol utama.
- **Fresh Clean Accent** (`#0284C7` / `#0EA5E9`): Aksen air bersih, higienitas, dan badge layanan cepat.
- **Success / Healthy** (`#10B981`): Indikator status selesai/lunas, badge jaminan cucian tidak dicampur.
- **Rating Gold** (`#F59E0B`): Bintang ulasan kepuasan pelanggan.
- **Surface Background** (`#FFFFFF`): Kartu layanan, panel input, modal, dan drawer.
- **Canvas Background** (`#FAFAFA`): Latar belakang halaman bersih dan hangat.
- **Text Primary** (`#0A0A0A`): Judul display, label penting, kontras tinggi WCAG AAA (>12:1).
- **Text Secondary** (`#55555E` / `#6B6B6B`): Deskripsi, subjudul, dan metadata (kontras >4.5:1 WCAG AA).
- **Border Recessive** (`#E8E8EC`): Garis pembatas kartu, separator, dan input halus tanpa bayangan kotor.

---

## 3. Typography
- **Display & Headings**: General Sans / Plus Jakarta Sans / system-ui, weight 600-700, tight tracking (`-0.035em` hingga `-0.045em`).
- **Body & Controls**: DM Sans / Inter / system-ui, weight 400-500, line-height 1.55 - 1.6.
- **Numbers / Metrics**: Monospace angka bersih (JetBrains Mono / tabular-nums) untuk harga dan berat timbangan.
- **Fluid Scale**:
  - Hero Display: `clamp(2.25rem, 3.8vw, 3.65rem)`
  - Section Headings: `clamp(1.75rem, 2.8vw, 2.4rem)`
  - Subheads / Card Titles: `1.15rem - 1.35rem`
  - Body: `0.9rem - 1rem`
  - Small / Captions / Badges: `0.72rem - 0.8rem`

---

## 4. Components & Micro-Patterns
- **Buttons**:
  - Radius: `6px`.
  - Tap target: Min. `44x44px` untuk tombol interaktif.
  - Primary: Latar `#4F46E5` dengan teks putih tebal, hover lift `-1px` dengan shadow lembut `0 4px 12px rgba(99,102,241,0.22)`.
  - Secondary / Outline: Border `1px solid #D7D7DF`, teks `#0A0A0A`, hover border `#6366F1`.
  - WhatsApp CTA: Latar hijau WhatsApp `#25D366` / `#16A34A` dengan icon SVG resmi.
- **Cards**:
  - Surface putih, border halus `1px solid #E8E8EC`, radius `10px` hingga `12px`.
  - Hover: Subtly lift `-2px` dengan bayangan ringan `0 8px 24px rgba(0,0,0,0.05)`.
- **Badge & Pills**:
  - Radius `4px` atau `rounded-full`, padding `4px 10px`, font size `11-12px`, uppercase tracking `0.06em`.
- **Form & Input Lacak Nota**:
  - Input field tinggi `44px`, border `#D7D7DF`, focus outline `3px solid rgba(99,102,241,0.18)`.
  - Tombol submit terintegrasi dalam row yang responsif.
- **Accordion FAQ**:
  - Semantic `<details>` dan `<summary>`, expand halus, ikon chevron berotasi `180deg` saat terbuka.

---

## 5. Layout Architecture (Landing Page)
1. **Header Sticky**: Logo wordmark `andik. LAUNDRY`, menu tautan seksi, tombol Lacak Nota cepat, dan tombol "Masuk Staf". Mobile hamburger drawer responsif.
2. **Hero Modern**:
   - Headline kuat: Solusi cucian bersih, wangi, dan higienis tanpa dicampur.
   - Dual Call-to-Action: Form Lacak Pesanan instan & Tombol WhatsApp Antar-Jemput.
   - Live Social Proof: Counter pelanggan riil, cucian dikerjakan, rating 4.9/5, dan garansi 1 Mesin 1 Pelanggan.
   - Foto visual berkualitas: Ilustrasi kebersihan cucian tertata rapi.
3. **Mengapa Memilih Kami (Keunggulan Utama)**:
   - 4 Kartu USP: 1 Mesin 1 Pelanggan, Tepat Waktu (Pilihan Express & Reguler), Nota Digital Realtime, Gratis Antar Jemput (< 3 km).
4. **Katalog Layanan & Tarif Transparan**:
   - 4 Layanan Utama POS:
     - Cuci Basah (Rp 5.000/kg - Min. 2 kg)
     - Cuci Kering (Rp 6.000/kg - Min. 2 kg)
     - Cuci Setrika (Rp 8.000/kg - Min. 2 kg) — *Paling Favorit*
     - Cuci Karpet (Rp 15.000/m² - Kalkulasi Panjang x Lebar)
   - Tambahan opsi Express (+Rp 2.000/kg) & Gratis Ongkir Antar Jemput < 3 km.
5. **Alur Pemesanan (4 Langkah Mudah)**:
   - Taruh / Chat Jemput -> Timbang & Nota WA -> Proses Higienis -> Siap Diambil / Diantar.
6. **Pelacak Nota Digital (Online Tracking)**:
   - Form input langsung nomor nota (contoh: `AL-20261115-001`) atau nomor HP untuk melihat progres live (Diproses -> Selesai -> Sudah Diambil).
7. **Metode Pembayaran**:
   - Pembayaran mudah Non-Tunai QRIS (GoPay, OVO, Dana, ShopeePay, BCA, Mandiri) dan Tunai.
8. **Testimoni & Ulasan Pelanggan**:
   - Ulasan riil pelanggan sekitar toko dengan bintang kepuasan.
9. **FAQ (Tanya Jawab Pelanggan)**:
   - Jawaban lugas atas minimal timbangan (2 kg), jadwal operasional, garansi cucian, dan area gratis ongkir.
10. **Footer & Kontak Toko**:
    - Alamat lengkap, jam operasional (07.00 - 21.00 WIB), tautan Google Maps, tombol WhatsApp, dan hak cipta.
11. **Mobile Floating Action Button**:
    - Tombol WhatsApp melayang di sudut kanan bawah khusus device mobile untuk kemudahan reservasi antar-jemput 1 ketukan.

---

## 6. Anti-Slop & Mobile Quality Guardrails
- Dilarang menggunakan pola AI slop: tidak ada dot-grid, tidak ada glow berlebihan, tidak ada emoji dekoratif pada tombol.
- Semua target sentuh minimal `44x44px`.
- Bebas overflow horizontal di seluruh lebar layar (320px - 1920px+).
- Reflow alami: tata letak kolom beradaptasi anggun di layar ponsel, tablet, hingga desktop lebar.
