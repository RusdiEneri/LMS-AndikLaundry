# LMS Andik Laundry

> **Sistem Manajemen Laundry Modern, Kasir POS Cepat, Desain Neubrutalism & Akuntansi Standar SAK EMKM**  
> Dirancang khusus untuk operasional mandiri dan pencatatan keuangan UMKM Laundry di Indonesia (Kiloan, Satuan, Dry Clean, dan Meteran/Karpet).

---

## Daftar Isi
1. [Gambaran Umum & Konteks Proyek](#1-gambaran-umum--konteks-proyek)
2. [Desain Antarmuka: Neubrutalism Modern](#2-desain-antarmuka-neubrutalism-modern)
3. [Alur Peran & Akses: Single Role Owner & Customer Tanpa Login](#3-alur-peran--akses)
4. [Panduan Instalasi Langkah Demi Langkah](#4-panduan-instalasi-langkah-demi-langkah)
5. [Akun Default & Kredensial](#5-akun-default--kredensial)
6. [Panduan Operasional WhatsApp Gateway & QRIS](#6-panduan-operasional-whatsapp-gateway--qris)
7. [Panduan Backup & Pemulihan Database](#7-panduan-backup--pemulihan-database)
8. [Perintah Pengujian & Verifikasi (Testing Suite)](#8-perintah-pengujian--verifikasi)
9. [Tanya Jawab (FAQ) & Solusi Masalah](#9-tanya-jawab-faq--solusi-masalah)

---

## 1. Gambaran Umum & Konteks Proyek

**LMS Andik Laundry** adalah sistem manajemen bisnis laundry berbasis web yang lengkap, tangguh, dan ringkas untuk UMKM laundry rumahan mandiri di Indonesia.

Sistem ini memecahkan berbagai masalah nyata dalam operasional laundry:
- **Navigasi Cepat Tanpa Ribet Ganti Akun**: Menggunakan model **Role Tunggal Internal (Owner)** sehingga pemilik usaha tidak perlu bolak-balik logout/login antara akun kasir, staf cuci, dan owner.
- **Kasir POS Cepat & Akurat**: Menangani laundry kiloan (dengan proteksi *minimum weight threshold*), laundry satuan & dry clean (bed cover, jas, gaun/kebaya, sepatu), serta laundry meteran/luas area (karpet dan gorden).
- **Akses Pelanggan Mandiri Tanpa Perlu Login**: Pelanggan tidak dibebani kewajiban membuat akun. Seluruh nota digital, rincian biaya, kode QRIS, dan pelacakan progres cucian dapat diakses secara instan melalui tautan unik yang dikirimkan ke WhatsApp.
- **Transparansi Keuangan SAK EMKM**: Dual-basis accounting membedakan antara uang kas fisik nyata di laci (*cash in drawer*) dengan laba bersih operasional yang diakui secara akrual saat cucian selesai.

### Fitur Utama Proyek:
- ⚡ **Kasir POS Terpadu**:
  - **Layanan Kiloan (Ambang Batas Berat Minimal)**: Jika pelanggan mencuci 1.2 kg sementara batas minimal toko adalah 3.0 kg, sistem otomatis menagih tarif 3.0 kg agar operasional mesin tidak merugi. Berat asli dan berat tagih keduanya terekam (`actual_quantity` & `billed_quantity`).
  - **Layanan Satuan & Dry Clean + Inspeksi Cacat**: Input potong/item dengan formulir kondisi pakaian (noda kerah, kancing hilang, luntur) dan catatan instruksi khusus. Catatan kondisi awal tercetak di nota sebagai proteksi klaim kerusakan.
  - **Layanan Meteran / Luas Area (Karpet)**: Input panjang dan lebar (meter), sistem langsung menghitung luas ($m^2$) dan menyimpan dimensi serta hasil hitung area ke database.
  - **Tier Kecepatan Pengerjaan (Turnaround)**: Pilihan Reguler (48 jam), Kilat (24 jam), dan Ekspres (8 jam) dengan faktor pengali tarif yang dibekukan (*snapshot*) saat transaksi dibuat.
  - **Metode Pembayaran Kasir**: Wajib lunas (tanpa utang-piutang/kasbon). Mendukung Tunai (Cash) dengan hitung kembalian instan dan QRIS Manual dengan pemindaian barcode toko serta verifikasi mutasi wajib oleh kasir sebelum struk dicetak.
  - **Opsi Pengiriman**: Setiap nota mencatat opsi ambil sendiri (*pickup*) atau diantar (*delivery*).
- 🏭 **Pelacakan Progres Produksi 3 Tahap**:
  - **Diproses**: Sedang dicuci, dikeringkan, atau disetrika.
  - **Selesai**: Cucian siap diambil / diantar, lengkap dengan koordinat lokasi rak penyimpanan (*staging rack*, wajib diisi).
  - **Sudah Diambil**: Cucian telah diterima oleh pelanggan.
  - Di balik layar tersedia juga alur granular 11 tahap (diterima → sortir → cuci → kering → setrika → QC → packing → siap ambil) dengan **loop cuci ulang (rewash)** maksimal 2 siklus beserta alasan yang tercatat di jejak audit.
- 📱 **Notifikasi WhatsApp & Manual Fallback**:
  - Konfirmasi pakaian masuk (*intake message*) dan notifikasi cucian selesai (*ready for pickup*), lengkap dengan template pesan yang dapat dikustomisasi per toko.
  - Koneksi gateway mendukung dua metode: **scan QR Code** dan **kode OTP pairing** (tautkan dengan nomor telepon saja).
  - Jika WhatsApp Gateway offline, sistem kasir menyediakan tombol fallback resmi **wa.me** yang langsung mengisi nomor tujuan dan format teks nota secara otomatis.
- 📊 **Akuntansi SAK EMKM & Rekap Kas Harian**:
  - **Accrual Basis**: Pendapatan diakui sah saat nota selesai diproduksi.
  - **Cash Basis**: Mengaudit uang fisik laci kasir per metode pembayaran.
  - **Pengeluaran Toko**: Pencatatan belanja operasional harian per kategori (deterjen & pewangi, gas LPG, plastik & hanger, listrik/air/Wi-Fi, perawatan mesin, lain-lain).
  - **Buku Piutang Pelanggan**: Meski kasir mewajibkan lunas, halaman piutang tetap tersedia untuk memantau nota bermasalah dengan *aging bracket* (0-7 hari / 8-14 hari / >14 hari) dan tombol kirim pengingat WhatsApp satu klik.
  - **Ekspor CSV**: Laporan keuangan bulanan dapat diekspor ke CSV untuk arsip dan pelaporan.
- 🔒 **Jejak Audit (Audit Log)**: Setiap perubahan pengaturan bisnis dan transisi status produksi terekam immutable lengkap dengan nilai lama/nilai baru dan ID pengguna.

### Arsitektur & Teknologi:
- **Backend Framework**: [Laravel 13.x](https://laravel.com) pada **PHP 8.4**
- **Frontend Engine**: [Inertia.js v3](https://inertiajs.com) dengan [Vue 3](https://vuejs.org) (Composition API, `<script setup lang="ts">`, TypeScript strict mode)
- **Styling**: [Tailwind CSS v4](https://tailwindcss.com) + komponen berbasis [Radix Vue](https://radix-vue.com) + design system tokens Neubrutalism
- **Database Engine**: **MySQL 8.4** (SQLite untuk testing), seluruh kolom moneter `DECIMAL(12,2)` dan berat `DECIMAL(8,2)` — tanpa floating point
- **Automated Test Suite**: [Pest PHP v4](https://pestphp.com) (334 feature dan unit tests dalam 32 file)
- **Asset Bundler**: [Vite 8](https://vitejs.dev)
- **WhatsApp Gateway Service**: Microservice mandiri berbasis **Go** dan pustaka `whatsmeow` (tersedia di folder `gateway/`)

---

## 2. Desain Antarmuka: Neubrutalism Modern

Antarmuka LMS Andik Laundry dibangun mengikuti panduan estetika **Neubrutalism** (`DESIGN.md`):
- **Border Hitam Tegas**: Garis luar `2.5px` hingga `3.5px solid #000` di setiap kartu, tombol, dan formulir.
- **Bayangan Hard Offset 45°**: Bayangan pekat tanpa blur (`box-shadow: 3px 3px 0 #000` hingga `6px 6px 0 #000`).
- **Palet Warna Karakteristik**:
  - Kuning Primer: `#FFEB3B`
  - Merah Sekunder: `#FF5252`
  - Biru Tersier: `#2196F3`
  - Hijau Sukses: `#22C55E`
  - Canvas Hangat: `#FFFDF5` / `#FFF9D2`
- **Tipografi Bold & Tactile**: Gaya visual berani, tombol bertekstur saat ditekan (*active tactile press*), dan ramah sentuhan di layar sentuh ponsel (target minimal 44x44px).
- **Anti-AI Slop**: Layout didesain spesifik untuk kebutuhan laundry nyata, bebas dari template generik dan teks placeholder dummy.

---

## 3. Alur Peran & Akses

Untuk menjamin kecepatan operasional dan kemudahan navigasi, arsitektur hak akses disederhanakan:

1. **Role Tunggal Internal — Owner (Pemilik sekaligus Kasir)**:
   - Tidak ada pemisahan peran kasir vs operator staf. Seluruh fitur berada dalam satu dashboard terpadu:
     - Transaksi kasir POS cepat
     - Pendataan pelanggan & penetapan harga layanan
     - Pembaruan progres cucian di stasiun produksi
     - Pengelolaan biaya belanja operasional toko
     - Laporan laba rugi bulanan SAK EMKM & ekspor CSV
     - Rekap kas harian & buku piutang pelanggan
     - Manajemen pengguna & konfigurasi WhatsApp Gateway
     - Pencadangan database
   - > **Catatan teknis**: Infrastruktur RBAC (role `cashier`/`operator` + 23 permission kanonik) sudah disiapkan di dalam kode untuk pertumbuhan usaha ke depan, namun pada praktik operasional saat ini hanya satu akun **Owner** yang dipakai dan semua hak akses berada di tangannya (bypass super-admin via `Gate::before`).
2. **Akses Eksternal — Customer (Tanpa Login)**:
   - Pelanggan tidak perlu mendaftar atau mengingat kata sandi.
   - Setiap transaksi menghasilkan tautan nota digital publik (`/nota/{invoice_number}`) yang dilindungi rate limiter (60 permintaan/menit per IP).
   - Pelanggan dapat mengecek progres pengerjaan pakaian secara langsung melalui tautan yang dikirimkan ke WhatsApp atau mencari nota menggunakan nomor telepon di halaman utama.
   - Format nomor nota: `INV-YYYYMMDD-XXXX` (contoh: `INV-20261116-0001`).

---

## 4. Panduan Instalasi Langkah Demi Langkah

### 4.1 Persyaratan Sistem
1. **PHP**: Versi **8.4** atau lebih baru (`pdo_mysql`, `pdo_sqlite`, `zlib`, `intl`, `bcmath`, `curl`, `mbstring`, `openssl`).
2. **Composer**: Versi **2.7** atau lebih baru.
3. **Node.js & npm**: Node.js **20.x** ke atas dan npm **10.x** ke atas.
4. **MySQL Database**: Versi **8.4** (atau MySQL 8.0+ / MariaDB 10.4+).
5. **Git**: Untuk mengunduh source code repositori.

---

### 4.2 Langkah Instalasi

#### Langkah 1: Kloning Repositori
```bash
git clone -b dev-ilham https://github.com/RusdiEneri/LMS-AndikLaundry.git
cd LMS-AndikLaundry
```

#### Langkah 2: Salin File Environment (`.env`)
- Linux / macOS / PowerShell:
  ```bash
  cp .env.example .env
  ```
- Windows Command Prompt:
  ```cmd
  copy .env.example .env
  ```

Sesuaikan konfigurasi database di file `.env`:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=lms_andiklaundry
DB_USERNAME=root
DB_PASSWORD=
```

#### Langkah 3: Install Dependensi PHP & Node.js
```bash
composer install
npm install
```

#### Langkah 4: Generate Application Key
```bash
php artisan key:generate
```

#### Langkah 5: Migrasi Database & Seeding Awal
```bash
php artisan migrate --seed
```
Seeder akan otomatis membuat: akun Owner, 3 role + 23 permission, katalog layanan (4 kategori, 11 layanan + tier), data pelanggan contoh, dan pengaturan bisnis awal.

> **Failsafe katalog**: Jika katalog layanan kosong saat POS dibuka, sistem otomatis menjalankan seeder katalog sehingga kasir tidak pernah menemui error "layanan tidak valid".

---

### 4.3 Menjalankan Aplikasi di Komputer Lokal

Buka **3 Terminal Terpisah** di folder proyek:

#### Terminal 1: Backend Laravel Server
```powershell
php artisan serve
```
*Aplikasi web aktif di: `http://localhost:8000`.*

#### Terminal 2: Frontend Asset Bundler (Vite)
```powershell
npm run dev
```

#### Terminal 3: WhatsApp Gateway Daemon (Opsional)
```powershell
cd gateway
.\whatsapp-gateway.exe
```
*Gateway akan standby di: `http://localhost:8080`.*

> **Alternatif satu perintah**: `composer run dev` menjalankan server Laravel, queue, Vite, dan log watcher secara bersamaan.

---

## 5. Akun Default & Kredensial

Database telah dilengkapi dengan akun bawaan resmi untuk pemilik usaha:

| Peran (Role) | Nama Akun | Email Login | Nomor WhatsApp | Kata Sandi (Password) | Hak Akses |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **Owner** | Andik (Owner) | `owner@andiklaundry.test` | `6281234567001` | `Password123!` | Akses penuh seluruh sistem: Kasir POS, Manajemen Pelanggan, Stasiun Produksi, Pengeluaran Toko, Piutang, Dashboard SAK EMKM, dan Pengaturan. |

> **Catatan Login Cepat**: Di halaman `/login`, tersedia tombol instan **"Isi Kredensial Demo: Akun Owner (Andik)"** untuk mempermudah pengujian.

---

## 6. Panduan Operasional WhatsApp Gateway & QRIS

### 6.1 WhatsApp Gateway (Go Microservice)
1. Jalankan daemon gateway di Terminal 3 (`cd gateway && .\whatsapp-gateway.exe`).
2. Masuk sebagai Owner -> menu **Pengaturan Toko** -> tab **WhatsApp Gateway**.
3. Hubungkan sesi dengan salah satu dari dua cara:
   - **Scan QR Code**: Klik **Tampilkan QR Code**, lalu pindai melalui WhatsApp di smartphone Anda (**Perangkat Tertaut**).
   - **Kode OTP Pairing**: Masukkan nomor WhatsApp toko, lalu ketik 8 digit kode yang muncul di menu **Perangkat Tertaut -> Tautkan dengan nomor telepon saja**.
4. Klik **Uji Koneksi Gateway** untuk memastikan nomor terhubung, dan **Kirim Pesan Uji** untuk verifikasi end-to-end.

### 6.2 Mode Cadangan Darurat (Manual Fallback via `wa.me`)
Jika komputer gateway mati atau jaringan bermasalah:
- Operasional kasir **tidak akan terganggu**.
- Di sebelah nota atau keranjang kasir, klik tombol **Kirim via WhatsApp (wa.me)**.
- Aplikasi langsung membuka tautan resmi `https://wa.me/` dengan isi nota dan nomor pelanggan yang sudah otomatis terisi.

### 6.3 Kebijakan Pembayaran & Panduan QRIS Manual
- **Kebijakan Wajib Lunas (No Receivables di Kasir)**: Andik Laundry memberlakukan aturan pembayaran wajib lunas saat transaksi di kasir POS. Sistem tidak mengizinkan pembayaran tertunda, kasbon, atau piutang di layar checkout — tunai kurang dari total tagihan akan ditolak dengan pesan peringatan.
- **Metode Pembayaran Kasir**:
  1. **Tunai (Cash)**: Kasir memasukkan uang tunai yang diterima (menggunakan numpad/preset), sistem menghitung uang kembalian secara real-time. Transaksi tidak dapat diselesaikan jika uang tunai kurang dari total tagihan.
  2. **QRIS Manual**: Layar kasir menampilkan kode QRIS resmi toko (`public/images/qris-andiklaundry.svg` beserta poster QRIS) dengan nominal pas yang wajib ditransfer pelanggan. Pelanggan dapat memindai melalui seluruh m-Banking (BCA, Mandiri, BRI, BNI) dan E-Wallet (GoPay, OVO, Dana, ShopeePay). Kasir wajib memverifikasi mutasi/bukti transfer melalui centang konfirmasi sebelum transaksi diproses.

---

## 7. Panduan Backup & Pemulihan Database

### 7.1 Backup Manual
```powershell
# Backup standar dengan kompresi gzip (retensi default 30 hari)
php artisan db:backup

# Backup dengan batas simpan 14 hari
php artisan db:backup --retention=14
```
File cadangan disimpan secara aman di folder `storage/app/backups/` dengan proteksi `.gitignore` agar tidak bocor ke publik.

### 7.2 Jadwal Backup Otomatis
Sistem diprogram mencadangkan database secara otomatis setiap pukul **02:00** (retensi 30 hari):
```powershell
php artisan schedule:list
```

### 7.3 Pemulihan Database (Restore)
Ekstrak arsip `.sql.gz` lalu impor ke MySQL:
```powershell
7z x storage\app\backups\backup-2026-11-16_020000.sql.gz -ostorage\app\backups\
mysql -u root -p lms_andiklaundry < storage\app\backups\backup-2026-11-16_020000.sql
```

---

## 8. Perintah Pengujian & Verifikasi

### 8.1 Automated Test Suite (Pest PHP)
Aplikasi memiliki **334 test otomatis dalam 32 file** yang mencakup skenario end-to-end POS, mesin status produksi, snapshot harga, pembayaran multi-metode, piutang, rekap kas harian, notifikasi WhatsApp, otorisasi role, dan backup database:
```powershell
# Jalankan seluruh tes
php artisan test --compact

# Menjalankan pengujian spesifik
php artisan test tests/Feature/PosE2EScenarioTest.php
php artisan test tests/Feature/CustomerReceivablesFeatureTest.php
php artisan test tests/Feature/DatabaseBackupTest.php
```

### 8.2 Verifikasi Tipe & Build Aset Frontend
```powershell
npm run typecheck
npm run build
```

---

## 9. Tanya Jawab (FAQ) & Solusi Masalah

### Q1: Mengapa tampilan web tidak berwarna atau berantakan?
> **Solusi**: Kompilasi aset frontend Anda menggunakan perintah `npm run build` atau jalankan `npm run dev` di terminal pendamping.

### Q2: Bagaimana cara melacak status cucian bagi pelanggan?
> **Solusi**: Pelanggan cukup membuka tautan nota digital yang dikirim via WhatsApp atau memasukkan nomor nota (misal `INV-20261116-0001`) atau nomor HP pada kolom pencarian di halaman utama (`http://localhost:8000`).

### Q3: Bagaimana cara menghitung cucian karpet?
> **Solusi**: Di layar kasir POS, pilih layanan **Sepatu & Karpet -> Cuci Karpet**, masukkan dimensi Panjang dan Lebar (meter). Sistem akan menghitung luas $m^2$, menyimpannya ke database, dan mengalikannya dengan tarif per meter secara instan.

### Q4: Mengapa kas di laci berbeda dengan angka laba bersih di dashboard?
> **Solusi**: Ini adalah prinsip standar akuntansi **SAK EMKM**:
> - **Laba Bersih (Akrual)**: Baru diakui saat pakaian selesai dicuci (`completed`), karena jasa laundry telah selesai diberikan.
> - **Kas Nyata di Tangan (Cash Basis)**: Mengaudit uang fisik yang benar-benar ada di laci kasir saat itu juga.
> - Perbedaan timbul karena adanya uang muka (cucian baru masuk tapi sudah dibayar) atau piutang pelanggan (cucian sudah selesai tapi belum dibayar).

### Q5: Mengapa kasir menolak pembayaran tunai yang kurang dari total tagihan?
> **Solusi**: Sesuai kebijakan wajib lunas Andik Laundry, layar checkout tidak menyediakan opsi utang/piutang. Gunakan pembayaran gabungan (sebagian tunai, sisanya QRIS) atau minta pelanggan menyelesaikan nominal penuh.

---

## Lisensi & Hak Cipta

LMS Andik Laundry adalah perangkat lunak manajemen operasional laundry untuk UMKM Indonesia. Seluruh hak cipta dilindungi undang-undang.
