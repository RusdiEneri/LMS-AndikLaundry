# LMS Andik Laundry

> **Sistem Manajemen Laundry Modern, Kasir POS Cepat & Akuntansi Standar SAK EMKM**  
> Dirancang khusus untuk kemudahan operasional dan pencatatan keuangan UMKM Laundry di Indonesia (Kiloan, Satuan, dan Meteran/Karpet).

---

## Daftar Isi
1. [Gambaran Umum & Konteks Proyek (Project Overview)](#1-gambaran-umum--konteks-proyek)
2. [Panduan Instalasi Langkah Demi Langkah (Step-by-Step Installation Guide)](#2-panduan-instalasi-langkah-demi-langkah)
3. [Akun Default & Hak Akses (Default Account Credentials)](#3-akun-default--hak-akses)
4. [Panduan Operasional WhatsApp Gateway (WhatsApp Gateway Guide)](#4-panduan-operasional-whatsapp-gateway)
5. [Panduan Backup & Pemulihan Database (Database Backup & Restore Guide)](#5-panduan-backup--pemulihan-database)
6. [Perintah Pengujian & Verifikasi (Verification & Testing Commands)](#6-perintah-pengujian--verifikasi)
7. [Tanya Jawab (FAQ) & Panduan Solusi Masalah (Troubleshooting)](#7-tanya-jawab-faq--panduan-solusi-masalah)

---

## 1. Gambaran Umum & Konteks Proyek

**LMS Andik Laundry** adalah aplikasi manajemen bisnis laundry berbasis web yang lengkap, modern, dan dirancang agar mudah digunakan oleh siapa saja—mulai dari kasir pemula hingga pemilik usaha laundry berpengalaman. 

Aplikasi ini mengatasi kendala nyata yang sering dialami oleh pengusaha laundry di Indonesia: antrean kasir yang lambat, cucian yang hilang atau tertukar di ruang produksi, pelanggan yang lupa mengambil cucian, serta kesulitan membedakan antara uang kas fisik di laci dengan pendapatan bersih usaha yang sebenarnya.

### Fitur Utama Proyek:

- ⚡ **Kasir POS Cepat (Fast POS Cashier)**:
  - **Layanan Kiloan (dengan Minimum Weight Threshold)**: Menghitung tarif kiloan secara instan. Jika pelanggan mencuci di bawah batas minimal (misalnya pakaian hanya seberat 1.5 kg padahal batas minimum toko adalah 3 kg), sistem secara otomatis menerapkan tarif batas minimal toko agar operasional tetap efisien dan tidak merugi.
  - **Layanan Satuan**: Cocok untuk pakaian dan barang khusus seperti jas, kemeja, gaun, jaket, bed cover, tas, dan sepatu dengan penetapan harga per potong/item.
  - **Layanan Meteran / Luas Area (Karpet & Gorden)**: Untuk pencucian karpet, gorden, atau ambal. Kasir cukup memasukkan dimensi panjang dan lebar (dalam meter), sistem langsung menghitung total luas (m²) dan total biaya secara otomatis dan akurat.
  - **Input Pelanggan Cepat**: Tambah data pelanggan baru langsung dari antarmuka kasir tanpa perlu keluar halaman transaksi.
  - **Multi-Metode Pembayaran**: Mendukung pembayaran Tunai (Cash), QRIS dinamis, Transfer Bank, dan Saldo e-Wallet (GoPay, OVO, Dana).
  - **Nota Digital Publik**: Setiap transaksi menghasilkan tautan nota digital unik yang dapat diakses langsung oleh pelanggan dari smartphone mereka. Dilengkapi proteksi keamanan *Rate Limiting* (maksimal 60 request/menit per IP) untuk mencegah pencurian atau penyalahgunaan data nota.

- 🏭 **Stasiun Produksi Terpantau (Production Workstations)**:
  - Alur cucian terorganisir rapi dalam 5 stasiun kerja:
    1. **Cuci (Washing)**: Pakaian kotor mulai diproses dalam mesin cuci.
    2. **Kering (Drying)**: Proses pengeringan pakaian.
    3. **Setrika (Ironing)**: Proses penyetrikaan dan perapian pakaian.
    4. **Packing (Pengemasan)**: Pakaian dilipat rapi dan dibungkus plastik steril.
    5. **Rak (Storage Rack)**: Penempatan bungkusan cucian pada nomor rak tertentu agar kasir dapat mengambilnya dalam hitungan detik saat pelanggan datang mengambil.
  - Mendukung pencatatan cuci ulang (*rewash handling*) jika ada pakaian yang membutuhkan penanganan tambahan.

- 📱 **Notifikasi WhatsApp Otomatis & Terintegrasi**:
  - Kirim pesan konfirmasi otomatis saat pakaian baru masuk kasir (*intake message*).
  - Beritahu pelanggan secara instan saat cucian telah selesai dipacking dan siap diambil (*ready for pickup*).
  - Kirimkan pengingat ramah untuk tagihan cucian yang belum lunas (*receivable reminder*).

- 📊 **Akuntansi Standar SAK EMKM & Pelacakan Dual-Basis**:
  - **Accrual Basis (Kepatuhan Standar SAK EMKM)**: Pendapatan usaha diakui secara sah saat cucian selesai diproduksi (`completed`), bukan semata-mata saat kas diterima di kasir. Dilengkapi pencatatan biaya operasional (deterjen, gas LPG, listrik, parfum, gaji karyawan) dan perhitungan laba rugi operasional bersih.
  - **Cash Basis (Audit Uang Fisik Laci Kasir / Cash in Drawer)**: Memantau arus kas masuk riil per saluran (Tunai, QRIS, Transfer, e-Wallet) vs pengeluaran kas langsung dari laci. Memastikan uang tunai fisik yang ada di laci kasir cocok 100% pada saat pergantian shift kasir.
  - **Rekonsiliasi Gap Cash-to-Accrual**: Menjelaskan secara transparan mengapa saldo uang kas berbeda dengan angka laba akuntansi (mencakup rincian Piutang Usaha dari cucian selesai yang belum dibayar, serta Uang Muka dari cucian yang baru masuk dan masih dicuci).
  - **Ekspor CSV Terlindungi**: Download rekapan data transaksi ke format CSV (Excel) dengan enkripsi UTF-8 BOM serta proteksi anti formula injection.

### Arsitektur & Teknologi:
- **Backend Framework**: [Laravel 13.x](https://laravel.com) pada **PHP 8.4**
- **Frontend Engine**: [Inertia.js v3](https://inertiajs.com) dengan [Vue 3](https://vuejs.org) (Composition API, `<script setup lang="ts">`, TypeScript strict mode)
- **Styling & UI**: [Tailwind CSS v4](https://tailwindcss.com), Radix Vue, dan Lucide Icons
- **Database Engine**: **MySQL 8.4** (dengan dukungan SQLite in-memory untuk automated testing)
- **Automated Test Suite**: [Pest PHP v4](https://pestphp.com) (353 feature dan unit tests, 100% passing)
- **Asset Bundler**: [Vite 8](https://vitejs.dev)
- **WhatsApp Companion Service**: Microservice mandiri berbasis **Go** dan pustaka `whatsmeow`

---

## 2. Panduan Instalasi Langkah Demi Langkah

Panduan ini disusun secara runtut agar pengguna pemula dapat menginstal dan menjalankan aplikasi di komputer lokal (Windows, macOS, atau Linux) tanpa kendala.

### 2.1 Persyaratan Sistem (Prerequisites)
Sebelum memulai, pastikan perangkat komputer Anda telah terpasang perangkat lunak berikut:
1. **PHP**: Versi **8.4** atau lebih baru (pastikan ekstensi berikut aktif di `php.ini`: `pdo_mysql`, `pdo_sqlite`, `zlib`, `intl`, `bcmath`, `curl`, `mbstring`, `openssl`).
2. **Composer**: Versi **2.7** atau lebih baru (pengelola dependensi paket PHP).
3. **Node.js & npm**: Node.js versi **20.x** ke atas dan npm versi **10.x** ke atas.
4. **MySQL Database**: Versi **8.4** (atau MySQL 8.0+ / MariaDB 10.4+).
5. **Git**: Untuk mengunduh source code repositori.

> **Tips Pengguna Windows**: Jika perintah `php` atau `composer` belum terbaca di terminal, tambahkan path direktori PHP Anda (contoh: `C:\tools\php` atau `C:\xampp\php`) ke **Environment Variables** Windows (bagian System Path).

---

### 2.2 Langkah-Langkah Instalasi

#### Langkah 1: Kloning Repositori & Masuk ke Direktori Proyek
Buka terminal (PowerShell, Command Prompt, atau Terminal Linux/macOS) lalu jalankan:
```bash
git clone -b dev-ilham https://github.com/RusdiEneri/LMS-AndikLaundry.git
cd LMS-AndikLaundry
```

#### Langkah 2: Salin File Konfigurasi Environment (`.env`)
Salin file konfigurasi contoh `.env.example` menjadi `.env`:
- Di Linux / macOS / PowerShell:
  ```bash
  cp .env.example .env
  ```
- Di Windows Command Prompt (cmd):
  ```cmd
  copy .env.example .env
  ```

Buka file `.env` menggunakan teks editor (seperti VS Code atau Notepad), lalu sesuaikan konfigurasi koneksi database Anda:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=lms_andiklaundry
DB_USERNAME=root
DB_PASSWORD=
```
*(Pastikan Anda telah membuat database kosong bernama `lms_andiklaundry` di MySQL atau phpMyAdmin).*

#### Langkah 3: Install Dependensi PHP (Composer)
Unduh seluruh library backend yang diperlukan aplikasi:
```bash
composer install
```

#### Langkah 4: Install Dependensi Frontend (NPM)
Unduh seluruh library antarmuka Vue 3 dan styling Tailwind:
```bash
npm install
```

#### Langkah 5: Generate Application Key Laravel
Buat kunci enkripsi keamanan aplikasi:
```bash
php artisan key:generate
```

#### Langkah 6: Jalankan Migrasi Database dan Seeder Awal
Perintah ini akan membuat seluruh struktur tabel database dan langsung mengisi data awal toko (termasuk 3 akun pengguna bawaan):
```bash
php artisan migrate --seed
```

---

### 2.3 Cara Menjalankan Aplikasi (Gunakan 3 Terminal Terpisah)

Agar seluruh ekosistem aplikasi LMS Andik Laundry berjalan dengan lancar (termasuk fitur pengiriman WhatsApp otomatis), buka **3 jendela Terminal terpisah** pada folder proyek:

#### Terminal 1: Backend Laravel Server
Menjalankan server web backend utama:
```powershell
php artisan serve
```
*Aplikasi web sekarang aktif dan dapat dibuka melalui browser di alamat: `http://127.0.0.1:8000` (atau `http://localhost:8000`).*

#### Terminal 2: Frontend Asset Bundler (Vite Development)
Menjalankan server compiler frontend Vue 3 dengan fitur Hot Module Replacement (HMR):
```powershell
npm run dev
```

#### Terminal 3: WhatsApp Gateway Daemon
Menjalankan microservice WhatsApp Gateway pendamping:
```powershell
cd gateway
.\whatsapp-gateway.exe
```
*(Catatan: Pengguna Linux/macOS dapat menjalankan gateway menggunakan perintah `go run .` di dalam folder `gateway`).*  
*Gateway akan aktif dan standby pada alamat `http://localhost:8080`.*

---

## 3. Akun Default & Hak Akses

Setelah menjalankan perintah `php artisan migrate --seed`, database Anda telah dilengkapi dengan 3 akun bawaan resmi dari `UserSeeder.php`. Anda dapat masuk menggunakan alamat **Email** atau **Nomor WhatsApp**:

| Peran (Role) | Nama Akun | Email Login | Nomor WhatsApp / Telepon | Kata Sandi (Password) | Hak Akses & Tanggung Jawab Utama |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **Owner** | Andik (Owner) | `owner@andiklaundry.test` | `6281234567001` | `Password123!` | **Akses Penuh Seluruh Sistem**: Dashboard eksekutif, analisis laporan laba rugi SAK EMKM (Akrual & Kas), ekspor CSV laporan keuangan, rekap kas harian, manajemen data pengguna/karyawan, pengaturan toko, integrasi WhatsApp Gateway, riwayat audit log, serta backup database. |
| **Cashier (Kasir)** | Siti (Kasir) | `cashier@andiklaundry.test` | `6281234567002` | `Password123!` | **Operasional Kasir Depan**: Input transaksi pelanggan baru/lama (Kiloan, Satuan, Meteran), proses pembayaran (Tunai, QRIS, Transfer, e-Wallet), cetak struk kasir & bagikan nota digital via WhatsApp, serta kelola uang kas laci per shift kerja. |
| **Operator** | Budi (Operator) | `operator@andiklaundry.test` | `6281234567003` | `Password123!` | **Workstation Ruang Produksi**: Memantau cucian yang masuk dan memperbarui status pengerjaan (Cuci -> Kering -> Setrika -> Packing -> Rak), menetapkan nomor rak penyimpanan cucian siap ambil, dan mencatat cuci ulang (*rewash*). |

---

## 4. Panduan Operasional WhatsApp Gateway

Aplikasi LMS Andik Laundry dilengkapi dengan modul WhatsApp Gateway mandiri yang dibangun menggunakan bahasa Go dan pustaka `whatsmeow` (tersedia di folder `gateway/`). Modul ini memungkinkan pengiriman pesan WhatsApp resmi langsung dari nomor bisnis laundry Anda tanpa biaya langganan pihak ketiga.

### 4.1 Cara Menghubungkan Nomor WhatsApp Bisnis Anda
1. Pastikan gateway berjalan di **Terminal 3** (`cd gateway && .\whatsapp-gateway.exe`).
2. Masuk ke aplikasi web LMS Andik Laundry menggunakan akun **Owner**.
3. Klik menu **Pengaturan Toko** (Store Settings) di navigasi sebelah kiri, lalu pilih tab **WhatsApp Gateway**.
4. Pilih salah satu dari dua metode penghubungan:
   - **Metode 1: Pindai QR Code (Rekomendasi)**:
     - Klik tombol **Tampilkan QR Code**.
     - Buka aplikasi WhatsApp di ponsel Anda -> ketuk ikon titik tiga (Android) atau Pengaturan (iOS) -> pilih **Perangkat Tertaut (Linked Devices)** -> ketuk **Tautkan Perangkat** -> pindai QR Code yang tampil di layar komputer.
   - **Metode 2: Kode Pairing OTP (Pairing Code)**:
     - Masukkan nomor WhatsApp toko Anda (format internasional, misalnya: `6281234567890`).
     - Sistem akan meminta 8 digit kode pairing unik dari WhatsApp untuk dimasukkan langsung pada ponsel Anda tanpa membutuhkan kamera.
5. Setelah terhubung, klik tombol **Uji Koneksi Gateway** untuk memverifikasi status koneksi.
6. Anda juga dapat mengirimkan pesan uji coba ke nomor Anda sendiri melalui formulir **Kirim Pesan Uji Coba**.

### 4.2 Fitur Pengaman & Anti-Ban Otomatis
- **Pembatasan Frekuensi Kirim (Rate Limiter Anti-Ban)**: Sistem secara bawaan membatasi kecepatan pengiriman maksimal 12 pesan per menit dengan jeda acak manusiawi, meminimalkan risiko nomor terdeteksi sebagai spam oleh WhatsApp.
- **Template Pesan Dinamis**: Pesan konfirmasi penerimaan (*intake*), cucian selesai (*ready*), dan pengingat tagihan (*reminder*) dapat diubah kata-katanya sesuai gaya komunikasi toko Anda.

### 4.3 Mode Cadangan Darurat (Manual Fallback via Official `wa.me`)
Bagaimana jika komputer gateway mati, internet putus, atau nomor WhatsApp ter-logout?
- **Transaksi kasir tetap aman dan tidak akan terhenti!**
- Jika WhatsApp Gateway offline, sistem kasir secara otomatis menyediakan tombol alternatif: **Kirim via WhatsApp (wa.me)**.
- Saat tombol ini diklik, aplikasi akan membuka tautan resmi `https://wa.me/nomor-pelanggan?text=pesan-nota` di browser atau WhatsApp Web/HP kasir. Kasir cukup menekan tombol "Kirim" tanpa perlu mengetik ulang isi nota maupun nomor tujuan.

---

## 5. Panduan Backup & Pemulihan Database

Data transaksi dan keuangan adalah aset paling berharga dalam bisnis laundry. LMS Andik Laundry menyediakan modul pencadangan (*backup*) dan pemulihan (*restore*) otomatis yang tangguh untuk melindungi bisnis Anda dari risiko kehilangan data.

### 5.1 Perintah Backup Manual
Kapan saja Anda ingin membuat cadangan data (misalnya sebelum melakukan pembaruan sistem), jalankan perintah Artisan berikut:

```powershell
# 1. Backup standar (otomatis kompresi gzip & hapus file backup lama > 30 hari)
php artisan db:backup

# 2. Backup dengan batas retensi khusus (contoh: simpan 14 hari terakhir saja)
php artisan db:backup --retention=14

# 3. Backup dengan nama file kustom yang Anda tentukan
php artisan db:backup --filename=backup-sebelum-tutup-buku.sql.gz
```

### 5.2 Fitur Unggulan Modul Backup
- **Penyimpanan Aman**: File backup disimpan di direktori internal `storage/app/backups/`.
- **Penamaan Standar Otomatis**: Berformat `backup-YYYY-MM-DD_His.sql.gz` (contoh: `backup-2026-11-14_020000.sql.gz`).
- **Kompresi Gzip Maksimal (Level 9)**: Mengurangi ukuran file hingga 90%, menghemat kapasitas hard disk secara signifikan.
- **Perlindungan Privasi Git (.gitignore Guard)**: Direktori backup secara otomatis dilindungi file `.gitignore` khusus (`*\n!.gitignore\n`), menjamin file cadangan database Anda tidak akan pernah bocor ke repositori GitHub publik/privat.
- **Pembersihan Otomatis (30-Day Retention Pruning)**: Sistem akan memindai folder backup setiap kali perintah dijalankan dan secara otomatis menghapus arsip yang usianya sudah melewati batas retensi (default 30 hari).
- **Multi-Driver Engine**: Bekerja mulus baik menggunakan MySQL (via `mysqldump` dengan fallback native PHP PDO) maupun SQLite.

### 5.3 Jadwal Pencadangan Otomatis Harian (02:00 WIB)
Aplikasi telah diprogram untuk melakukan backup otomatis setiap malam pada pukul **02:00 WIB** (saat operasional toko sedang tutup):

```php
// Terdaftar di routes/console.php
Schedule::command('db:backup')
    ->dailyAt('02:00')
    ->description('Daily database backup with 30-day retention');
```

Untuk mengaktifkannya di server produksi, cukup daftarkan satu baris cron job di server Anda:
```bash
* * * * * cd /path-ke-proyek-lms && php artisan schedule:run >> /dev/null 2>&1
```
Anda dapat mengecek jadwal aktif kapan saja dengan mengetik:
```powershell
php artisan schedule:list
```

### 5.4 Prosedur Pemulihan Data (Disaster Recovery & Restore)
Jika terjadi kerusakan sistem, hard disk error, atau Anda ingin memindahkan data ke komputer baru:

**Langkah 1: Ekstrak arsip backup `.sql.gz`**
- *Di Windows (menggunakan 7-Zip atau PowerShell)*:
  ```powershell
  7z x storage\app\backups\backup-2026-11-14_020000.sql.gz -ostorage\app\backups\
  ```
- *Di Linux / macOS*:
  ```bash
  gzip -d -k storage/app/backups/backup-2026-11-14_020000.sql.gz
  ```

**Langkah 2: Import file SQL ke database MySQL**
```powershell
mysql -u root -p lms_andiklaundry < storage/app/backups/backup-2026-11-14_020000.sql
```
*Data toko, transaksi, dan riwayat pelanggan Anda akan kembali pulih 100%.*

---

## 6. Perintah Pengujian & Verifikasi

Untuk menjamin mutu kode, stabilitas sistem, dan mencegah terjadinya bug dalam operasional sehari-hari, proyek ini dilengkapi dengan rangkaian pengujian otomatis lengkap.

### 6.1 Menjalankan Rangkaian Tes Otomatis (Pest PHP)
Aplikasi memiliki **353 test otomatis** yang mencakup fitur POS, perhitungan tarif kasir, stasiun produksi, akuntansi SAK EMKM, keamanan middleware, hingga sistem backup:

```powershell
# Jalankan seluruh rangkaian 353 automated tests (100% lolos/passed)
php artisan test

# Menjalankan pengujian khusus modul Backup Database & Keamanan
php artisan test tests/Feature/DatabaseBackupTest.php

# Menjalankan pengujian khusus modul Laporan Akuntansi SAK EMKM & Rekonsiliasi Kas
php artisan test tests/Feature/AccountingReportTest.php
```

### 6.2 Pengecekan Tipe TypeScript & Build Frontend
Pastikan antarmuka pengguna tidak memiliki kesalahan tipe statis dan siap dipublikasikan ke produksi:

```powershell
# Uji tipe statis Vue 3 & TypeScript (hasil yang diharapkan: 0 error)
npx vue-tsc --noEmit

# Kompilasi bundel aset produksi menggunakan Vite 8
npm run build
```

---

## 7. Tanya Jawab (FAQ) & Panduan Solusi Masalah

Berikut kumpulan pertanyaan umum dan solusi langkah demi langkah bagi pengguna awam:

### Q1: Saat mengetik perintah `php` atau `php artisan`, muncul pesan error `'php' is not recognized` atau `command not found`. Apa yang harus saya lakukan?
> **Solusi**: Sistem operasi Anda belum mengenali lokasi folder instalasi PHP.  
> 1. Cari lokasi folder PHP Anda di komputer (misalnya: `C:\tools\php` atau `C:\xampp\php`).  
> 2. Buka menu Windows -> ketik **Edit the system environment variables** -> klik **Environment Variables**.  
> 3. Pada bagian *System variables*, cari variabel **Path**, klik **Edit**, lalu tambahkan baris baru dengan path folder PHP Anda.  
> 4. Sebagai solusi instan pada jendela PowerShell yang sedang aktif, ketikkan:
>    ```powershell
>    $env:PATH = "C:\tools\php;$env:PATH"
>    ```
> 5. Restart terminal Anda dan coba jalankan kembali `php -v`.

---

### Q2: Mengapa tampilan web di browser berantakan dan warnanya hilang?
> **Solusi**: Hal ini terjadi karena aset CSS dan JavaScript belum ter-compile atau server Vite belum berjalan.  
> - Jika sedang dalam tahap pengembangan (development), pastikan **Terminal 2** sedang menjalankan perintah `npm run dev`.  
> - Jika ingin digunakan tanpa membuka Terminal 2 secara terus menerus, jalankan perintah kompilasi produksi sekali saja:
>   ```powershell
>   npm run build
>   ```
> - Muat ulang (*refresh*) halaman browser Anda dengan menekan `Ctrl + F5` (Hard Reload).

---

### Q3: Mengapa pesan WhatsApp otomatis tidak terkirim ke nomor pelanggan?
> **Solusi**: Lakukan pengecekan berurutan berikut:
> 1. **Periksa Terminal 3**: Pastikan jendela `whatsapp-gateway.exe` masih menyala dan tidak ditutup.
> 2. **Periksa Status Sesi**: Masuk sebagai Owner -> menu **Pengaturan Toko** -> tab **WhatsApp Gateway**. Pastikan status menampilkan tulisan *Connected*. Jika status terputus (*Disconnected*), lakukan scan QR code ulang.
> 3. **Format Nomor Telepon Pelanggan**: Pastikan nomor telepon diawali dengan format internasional (contoh: `628123456789`, bukan `08123456789`). Sistem kasir sudah memiliki penormal otomatis nomor telepon, namun pastikan nomor pelanggan aktif di WhatsApp.
> 4. **Gunakan Solusi Alternatif**: Jika gateway sedang bermasalah, gunakan tombol **Kirim via wa.me** di nota transaksi agar kasir tetap dapat mengirim nota secara manual melalui WhatsApp Web/HP.

---

### Q4: Bagaimana cara kasir menghitung cucian karpet yang menggunakan ukuran meteran?
> **Solusi**:  
> 1. Di layar kasir POS, pilih kategori layanan **Meteran/Karpet**.  
> 2. Masukkan angka panjang (meter) dan lebar (meter) pada kolom yang tersedia (misal: panjang `3` m dan lebar `2` m).  
> 3. Sistem akan mengalikan kedua angka tersebut secara otomatis (`3 x 2 = 6 m²`) dan langsung mengalikannya dengan tarif harga per meter persegi toko Anda.

---

### Q5: Bagaimana sistem menangani cucian kiloan yang beratnya sangat sedikit?
> **Solusi**:  
> Di menu **Pengaturan Toko -> Operasional**, Owner dapat menentukan batas berat minimum (*Minimum Weight Threshold*), misalnya 3 kg. Jika ada pelanggan yang hanya membawa cucian seberat 1.2 kg, kasir cukup mengetikkan `1.2`. Sistem POS akan otomatis mendeteksi bahwa berat tersebut berada di bawah ambang batas minimal dan langsung menghitung biaya setara 3 kg. Hal ini melindungi pemilik laundry dari inefisiensi biaya operasional mesin cuci.

---

### Q6: Mengapa angka pendapatan di Laporan Akuntansi (Akrual) berbeda dengan jumlah uang di Laci Kasir?
> **Solusi**:  
> Ini adalah prinsip akuntansi standar **SAK EMKM**:
> - **Pendapatan Akrual**: Baru diakui secara resmi saat pakaian selesai dicuci dan siap diambil (`completed`), karena pada titik itulah jasa laundry selesai diberikan kepada pelanggan.
> - **Uang Kas di Laci**: Melacak uang tunai fisik yang nyata masuk dan keluar dari laci saat itu juga.
> - **Penyebab Selisih**:
>   1. *Uang Muka Cucian*: Pelanggan sudah bayar lunas di awal saat baju masih kotor (kas sudah masuk laci, tapi cucian belum selesai dicuci).
>   2. *Piutang Usaha*: Cucian sudah selesai dicuci dan dilipat, tetapi pelanggan belum membayar saat mengambil cucian.
> - Buka menu **Dashboard Eksekutif** sebagai Owner untuk melihat penjelasan rekonsiliasi selisih ini secara transparan dan detail.

---

## Lisensi & Hak Cipta

LMS Andik Laundry adalah perangkat lunak manajemen operasional laundry untuk UMKM Indonesia. Seluruh hak cipta dilindungi undang-undang.
