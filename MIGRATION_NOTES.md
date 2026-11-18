# MIGRATION NOTES — UI DESIGN SYSTEM REVAMP

> Dokumen pencatatan migrasi antarmuka LMS Andik Laundry sesuai panduan `DESIGN.md`.

---

## 1. Ringkasan Migrasi

Seluruh komponen, layout, halaman publik, dan halaman operasional internal telah direfaktor mengikuti design system `DESIGN.md` sebagai **satu-satunya sumber kebenaran**. Tidak ada logika bisnis, skema database, atau endpoint API yang diubah.

---

## 2. Pemetaan Token (Lama → Baru)

| Kategori | Token / Nilai Lama | Token DESIGN.md | Keterangan & Kontras |
| :--- | :--- | :--- | :--- |
| **Brand Navy** | `#10194D`, `#0A1033`, `#070B24` | `navy-900` (`#13276F`), `navy-950` (`#081050`), `navy-800` (`#1F3C88`) | Sidebar, header modal, heading primer, background dark section |
| **Brand Blue** | `#4F5FE8`, `#6577F0` | `primary-600` (`#1F7FC4`), `primary-700` (`#1565A0`), `primary-500` (`#2F8FD8`) | Tab aktif, link navigasi, bar chart omzet, progress bar Inertia |
| **Brand Teal (CTA)** | `#FF8A5B` (oranye lama), `teal-600` | `accent-500` (`#39C5A8`), hover `accent-600` (`#2BA88E`) | Tombol aksi utama (CTA). **Wajib teks `text-navy-950`** (~8:1 WCAG AA) |
| **Background Halaman** | `#F6F4EF`, `#F8FAFC`, `#FFFFFF` | `--color-bg: #F4F8FB` (`bg-bg`) | Background seragam di seluruh app shell dan halaman |
| **Surface Card** | `bg-white`, `bg-slate-50` | `--color-surface: #FFFFFF` (`bg-surface`) | Card, drawer, modal dialog |
| **Border** | `border-slate-200/90`, `border-slate-200`, `border-[#E3E1DA]` | `--color-border: #E0E8F0` (`border-border`) | Border terstandarisasi |
| **Text Utama** | `text-slate-900`, `text-slate-800` | `--color-text: #1C2536` (`text-text`) | Teks standar berbobot kontras tinggi |
| **Text Muted** | `text-slate-500`, `text-slate-400` | `--color-text-muted: #64748B` (`text-text-muted`) | Subteks dan label pembantu |
| **Semantic Success** | `#22A06B`, `emerald-600` | `--color-success: #10B981` (`text-success`, `bg-success/15`) | Status lunas, selesai, badge |
| **Semantic Warning** | `#8A5A00`, `amber-600` | `--color-warning: #F59E0B` (`text-warning`, `bg-warning/15`) | Status pending, umur piutang, express kilat |
| **Semantic Danger** | `#B3262B`, `rose-600` | `--color-danger: #EF4444` (`text-danger`, `bg-danger/10`) | Piutang belum lunas, hapus staf, error form |
| **Radius** | `rounded-2xl` (16px), `rounded-3xl` (24px) | `rounded-lg` (`--radius-lg: 20px`), `rounded-pill` (`--radius-pill: 999px`) | Radius kartu 20px, badge & button pill 999px |

---

## 3. Komponen yang Direfaktor / Dibuat

1. **`AppLayout.vue`**:
   - Sidebar Navy 900 (`#13276F`) dengan animasi drawer di mobile.
   - Topbar putih 64px (`bg-surface border-border`).
   - Active navigation item menggunakan `bg-primary-600 text-white font-bold rounded-pill`.
   - Logout button: `text-danger/80 hover:bg-danger/15`.
2. **`PosLayout.vue`**:
   - POS workstation shell dengan sticky cart drawer, layout grid 12 kolom, anti-cutoff.
3. **`resources/js/Components/Pos/`**:
   - `CartDrawer.vue`: Ringkasan pesanan, kalkulasi otomatis, scrollable item container anti-potong.
   - `ServiceCard.vue`: Kartu varian layanan dengan badge kiloan/satuan/meteran ber-token.
   - `CustomerLookupModal.vue`: Pencarian pelanggan & warning piutang menggunakan `bg-danger/10 text-danger border-danger/20`.
   - `CheckoutPaymentModal.vue`: Pilihan tunai, transfer, QRIS, dan bayar pas ambil cucian.
   - `ThermalReceipt.vue`: Format cetak thermal 58mm/80mm monochrome print-ready.
4. **`resources/js/Components/ui/`**:
   - `Button.vue`: Varian `default` (primary blue), `secondary`, `outline`, `ghost`, `danger`, `accent` (CTA teal).
   - `Badge.vue` & `badge/index.ts`: Variant `default`, `secondary`, `outline`, `success`, `warning`, `danger`, `teal`.
   - `OrderStatusBadge.vue`: Refaktor dari hardcoded hex ke class Tailwind semantik.
   - `Table.vue`, `TableHead.vue`, `TableRow.vue`: Header `bg-bg`, hover `hover:bg-bg`.
   - `Alert.vue`: Border-l-4 dengan token semantik.

---

## 4. Halaman yang Dimigrasi

- **`Welcome.vue`**: Landing page publik dengan pola section gelap-terang (`bg-bg` -> `bg-navy-950` -> `bg-bg` -> `bg-gradient` -> `bg-navy-950`), animasi mulus reveal, floating pill navbar, hero car SVG illustration selaras token.
- **`Auth/Login.vue`**: Login kasir & owner dengan tema `navy-950`, container `surface`, input ber-token, dan CTA teal `text-navy-950`.
- **`Error/403.vue`**: Halaman akses ditolak ber-token navy & surface.
- **`Receipt/Show.vue`**: Nota digital publik & pelacakan live pelanggan dengan QRIS dan aksi pelunasan kasir ber-token.
- **`Pos/Index.vue`**: Kasir POS realtime dengan kalkulator kilogram/meter persegi dan drawer keranjang responsif.
- **`Production/Station.vue`**: Stasiun produksi cuci (Kanban kolom Antrean, Dicuci, Dikeringkan, Disetrika, Siap) ber-token.
- **`Owner/Dashboard.vue`**: Dashboard keuangan toko (Akrual SAK EMKM, kas, grafik omzet vs biaya ber-token).
- **`Owner/DailyRecap.vue`**: Rekap harian shift kasir, pencatatan kas fisik laci kasir, selisih kas ber-token.
- **`Owner/Expenses/Index.vue`**: Pencatatan pengeluaran operasional toko ber-token.
- **`Owner/Receivables/Index.vue`**: Buku piutang pelanggan & umur piutang ber-token.
- **`Owner/Users/Index.vue`**: Manajemen staf dan hak akses peran RBAC ber-token.
- **`Owner/Settings/Index.vue`**: Pengaturan toko, tarif layanan CRUD, printer thermal, dan gateway WhatsApp ber-token.

---

## 5. Keputusan & Asumsi Desain

1. **Kontras Tombol CTA Teal (`accent-500`)**:
   Sesuai Bagian 2.5 `DESIGN.md`, tombol teal (`#39C5A8`) selalu dipasangkan dengan teks `navy-950` (`#081050`) untuk mencapai rasio kontras 8:1 (WCAG AA lulus). Penggunaan teks putih dilarang karena rasio hanya ~2.1:1 (FAIL).
2. **Scroll Area Keranjang Kasir (`CartDrawer.vue`)**:
   Untuk mencegah anomali checkout terpotong pada layar beresolusi pendek/kecil (seperti di attachment user), footer subtotal dan tombol bayar dibuat sticky di bawah dengan container daftar cucian memakai `flex-1 overflow-y-auto`.
3. **SVG Illustration di Landing Page**:
   Atribut fill dan stroke pada ilustrasi mobil binatu SVG di `Welcome.vue` diselaraskan secara matematis ke palet warna resmi `DESIGN.md` (`#081050`, `#13276F`, `#1F7FC4`, `#39C5A8`, `#F59E0B`, `#E0E8F0`).
