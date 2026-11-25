# LMS Andik Laundry

Aplikasi manajemen operasional laundry berbasis web (kasir POS, pelacakan produksi, notifikasi WhatsApp, nota digital publik, dan rekap keuangan UMKM). Awalnya dibangun untuk **Andik Laundry** (Hendrosari, Menganti, Gresik), dan dapat langsung dijalankan maupun disesuaikan untuk usaha laundry lain maupun keperluan pengembangan tim.

## Daftar Isi

1. [Untuk Siapa Dokumentasi Ini](#1-untuk-siapa-dokumentasi-ini)
2. [Fitur Utama & Teknologi](#2-fitur-utama--teknologi)
3. [Instalasi & Menjalankan di Lokal](#3-instalasi--menjalankan-di-lokal)
4. [Panduan Pengguna Umum (Di Luar Tim / Toko Lain)](#4-panduan-pengguna-umum-di-luar-tim--toko-lain)
5. [Panduan Anggota Kelompok (Kolaborasi Git & Verifikasi)](#5-panduan-anggota-kelompok-kolaborasi-git--verifikasi)
6. [WhatsApp Gateway & Fallback Manual](#6-whatsapp-gateway--fallback-manual)
7. [Backup & Restore Database](#7-backup--restore-database)
8. [Troubleshooting Error Umum](#8-troubleshooting-error-umum)

---

## 1. Untuk Siapa Dokumentasi Ini

- **Anggota Kelompok / Developer**: Panduan setup cepat di Windows, macOS, atau Linux, alur branch Git tim, perintah tes/lint, serta solusi error yang paling sering muncul saat sinkronisasi kode.
- **Pengguna Umum / Pemilik Laundry Lain**: Cara memakai aplikasi ini untuk outlet sendiri (ganti nama usaha, tarif layanan, QRIS, akun login, dan jalankan tanpa pelanggan harus login).

---

## 2. Fitur Utama & Teknologi

### Alur Operasional
- **Kasir POS (`/pos`)**: Mendukung layanan **Kiloan** (dengan batas minimal berat default `2.00` kg, mencatat berat asli dan berat tagih), **Satuan** (bedcover, selimut, sprei, boneka/bantal beserta catatan kondisi item), dan **Karpet Meteran** (hitung otomatis panjang x lebar). Tersedia tier kecepatan (**Reguler** dan **Ekspres**), pilihan ambil sendiri atau antar, serta pembayaran **Tunai (Cash)** dan **QRIS**.
- **Stasiun Produksi (`/production/station`)**: Memperbarui status pengerjaan cucian dari diterima, proses cuci/kering/setrika, penempatan rak penyimpanan, siap ambil, hingga selesai diambil pelanggan (mendukung penandaan cuci ulang / rewash dengan batas siklus yang dapat diatur).
- **Nota Digital Publik Tanpa Login (`/nota/{invoice_number}` atau `/receipt/{orderNumber}`)**: Pelanggan cukup membuka tautan dari WhatsApp atau mencari nomor nota (`INV-YYYYMMDD-XXXX`) maupun nomor HP di halaman utama (`/`), dilindungi rate limit.
- **Keuangan & Laporan Owner (`/dashboard`, `/owner/expenses`, `/owner/daily-recap`, `/owner/receivables`)**: Pencatatan pengeluaran operasional, rekap kas harian per metode bayar, pemantauan piutang, ekspor laporan bulanan ke CSV, serta jejak audit perubahan data penting.
- **Peran & Hak Akses**: Tersedia 3 peran (`owner`, `cashier`, `operator`) dengan 23 izin terkelola (`RoleAndPermissionSeeder`). Usaha skala kecil bisa memakai 1 akun `owner` saja, atau menambah akun kasir dan operator di menu `/owner/users`.

### Stack & Desain
- **Backend**: Laravel `^13.17` pada PHP `^8.3` (dikembangkan dan diuji pada PHP `8.4`).
- **Frontend**: Inertia.js v3 (`@inertiajs/vue3`), Vue 3 (`<script setup lang="ts">`), Tailwind CSS v4, Radix Vue, Vite 8.
- **Desain UI**: Sistem token **DataHarbor** (`DESIGN.md` & `resources/css/app.css`), menggunakan palet Navy (`#081050`, `#1F3C88`), Primary Blue (`#1F7FC4`), Teal Accent (`#39C5A8`), Canvas `#F4F8FB`, dan font `Lexend`, `Poppins`, serta `JetBrains Mono`.
- **Database**: SQLite (default di `.env.example` untuk coba cepat) atau MySQL 8.4 (untuk produksi / lingkungan lokal yang memakai MySQL).
- **WhatsApp Gateway (Opsional)**: Service Go mandiri (`gateway/`) berbasis `whatsmeow`. Jika tidak dijalankan, sistem tetap bisa mengirim struk lewat tautan `wa.me`.
- **Automated Tests**: Pest PHP (`364` tes dalam `32` file di `tests/`).

---

## 3. Instalasi & Menjalankan di Lokal

### Persyaratan Sistem
1. **PHP >= 8.3** (disarankan **PHP 8.4**) dengan ekstensi: `pdo_sqlite` (untuk SQLite) atau `pdo_mysql` (untuk MySQL), `mbstring`, `openssl`, `intl`, `bcmath`, `curl`, `zlib`.
2. **Composer >= 2.7**
3. **Node.js >= 20** & **npm >= 10**
4. **Database**: SQLite (sudah bawaan PHP) atau **MySQL 8.0+ / 8.4**
5. **Go >= 1.21** (opsional, hanya dibutuhkan jika menjalankan/build ulang WhatsApp Gateway di macOS, Linux, atau ingin memodifikasi kode di `gateway/`)

### Langkah Setup Awal

#### 1. Clone Repositori & Install Paket
```bash
git clone https://github.com/RusdiEneri/LMS-AndikLaundry.git
cd LMS-AndikLaundry
git checkout staging
composer install
npm install
```
*(Anggota tim bisa langsung checkout ke branch masing-masing setelahnya; pengguna umum dapat memakai `staging` atau `main` sesuai rilis yang tersedia.)*

#### 2. Salin `.env` & Buat Application Key
- **Linux / macOS / PowerShell**:
  ```bash
  cp .env.example .env
  php artisan key:generate
  ```
- **Windows Command Prompt (CMD)**:
  ```cmd
  copy .env.example .env
  php artisan key:generate
  ```

#### 3. Pilih & Siapkan Database

**Opsi A: Mode Cepat dengan SQLite (Default `.env.example`, tanpa install MySQL)**
Pastikan file database SQLite tersedia, lalu jalankan migrasi:
- **PowerShell (Windows)**:
  ```powershell
  if (!(Test-Path database\database.sqlite)) { New-Item database\database.sqlite -ItemType File }
  php artisan migrate --seed
  ```
- **Linux / macOS / Git Bash**:
  ```bash
  touch database/database.sqlite
  php artisan migrate --seed
  ```

**Opsi B: Mode MySQL 8.4 (Cocok untuk data permanen / setara server produksi)**
1. Buat database kosong bernama `lms_andiklaundry` di MySQL (`CREATE DATABASE lms_andiklaundry CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;`).
2. Ubah bagian database di `.env`:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=lms_andiklaundry
   DB_USERNAME=root
   DB_PASSWORD=
   ```
3. Jalankan migrasi dan data awal:
   ```bash
   php artisan migrate --seed
   ```

#### 4. Jalankan Server Pengembangan

Cara paling praktis (menjalankan server Laravel dan Vite bersamaan lewat script `dev`):
```bash
composer run dev
```

Atau jalankan secara terpisah di 2 terminal (berguna jika ingin melihat log masing-masing secara terpisah):
```bash
# Terminal 1 (Backend Laravel -> http://127.0.0.1:8000)
php artisan serve

# Terminal 2 (Frontend Vite)
npm run dev
```

Buka browser dan akses `http://127.0.0.1:8000` (atau `http://localhost:8000`).

### Akun Login Awal (Hasil Seeder)
- Halaman login: `/login`
- **Email**: `owner@andiklaundry.test`
- **Password**: `Password123!`
- **Role**: `owner` (akses penuh)

Di halaman `/login` juga terdapat tombol **"Isi Kredensial Demo: Akun Owner (Andik)"** untuk mempercepat pengujian di lokal.

---

## 4. Panduan Pengguna Umum (Di Luar Tim / Toko Lain)

Jika Anda bukan bagian dari tim pengembang maupun pemilik Andik Laundry dan ingin memakai sistem ini untuk outlet laundry Anda sendiri, ikuti checklist berikut agar data usaha dan keamanan akun sesuai milik Anda:

### 4.1 Inisialisasi Database Bersih (Tanpa Data Sampel Pelanggan/Pengeluaran)
Perintah `php artisan migrate --seed` mengisi **5 pelanggan contoh** (`CustomerSeeder`) dan **4 pengeluaran contoh** (`ExpenseSeeder`) untuk keperluan demo. Jika ingin database bersih untuk toko asli tanpa data contoh tersebut, jalankan migrasi lalu panggil hanya seeder inti:
```bash
php artisan migrate
php artisan db:seed --class=RoleAndPermissionSeeder
php artisan db:seed --class=UserSeeder
php artisan db:seed --class=BusinessSettingSeeder
php artisan db:seed --class=ServiceCatalogSeeder
```

### 4.2 Ganti Kredensial Default Sebelum Online (Wajib)
1. Masuk ke `/login` menggunakan akun bawaan (`owner@andiklaundry.test` / `Password123!`).
2. Buka menu **Manajemen Pengguna** (`/owner/users`).
3. Tambahkan akun `owner` baru dengan email, nomor HP, dan password kuat milik Anda sendiri, lalu login ulang dengan akun baru tersebut.
4. Nonaktifkan atau hapus akun demo `owner@andiklaundry.test`, dan jika Anda tidak membutuhkan tombol isi demo di halaman login produksi, hapus tombol *"Isi Kredensial Demo"* di `resources/js/Pages/Auth/Login.vue` lalu jalankan `npm run build`.
5. Dari `/owner/users`, Anda juga dapat membuat akun staf dengan peran **`cashier`** (kasir & pelanggan) atau **`operator`** (hanya akses `/production/station`).

### 4.3 Ubah Profil Usaha & Katalog Tarif Lewat Menu Pengaturan
Sebagian besar pengaturan outlet tidak perlu mengubah kode. Login sebagai Owner, lalu buka **`/owner/settings`**:
- **Tab Layanan & Tarif**: Ubah harga default, minimal kilogram, atau tambah/nonaktifkan jenis layanan (`Kiloan`, `Satuan`, `Karpet`).
- **Tab Identitas Outlet**: Ganti nama outlet, nomor WhatsApp utama toko, alamat nota, dan catatan kaki (*receipt footer*). Perubahan `store.name`, `store.phone`, dan `store.address` otomatis tampil di halaman utama (`/`), struk digital, dan pesan WhatsApp.
- **Tab Operasional & Finansial**: Sesuaikan batas minimal berat (`2.00` kg), plafon piutang, maksimal siklus cuci ulang (`2`), tarif PPN (`0.00%`), serta ukuran kertas printer thermal (`58mm` atau `80mm`).
- **Tab WhatsApp Gateway**: Pilih mode **Manual (`wa.me`)** jika tidak ingin menjalankan server WhatsApp Go, atau mode otomatis jika menjalankan gateway.

### 4.4 Rebranding Aset Visual & Halaman Depan (Opsional)
Bagian yang bersifat visual statis di kode sumber dapat diganti langsung sebelum `npm run build`:
- **Gambar QRIS Toko**: Ganti `public/images/qris-andiklaundry.svg` dan `public/images/qris-andiklaundry-poster.jpg` dengan kode QRIS milik outlet Anda.
- **Logo & Hero Landing Page**: Ganti `public/images/logo-a.webp` / `public/images/logo-a.png`, serta `public/videos/bg-hero.mp4` dan `public/images/bg-hero-poster.webp`.
- **Teks Lokasi, Peta Google Maps & FAQ Landing Page**: Sesuaikan di `resources/js/Pages/Welcome.vue` (tautan Google Maps `iframe`, jam buka, dan jawaban FAQ).

Setelah mengubah file di `resources/`, jalankan:
```bash
npm run build
```

---

## 5. Panduan Anggota Kelompok (Kolaborasi Git & Verifikasi)

### 5.1 Struktur Branch Repositori
Branch yang tercatat di repositori:
- `main`: rilis stabil
- `staging`: integrasi gabungan antar anggota tim sebelum ke `main`
- Branch masing-masing anggota: `dev-ikbar`, `dev-ilham`, `dev-nazar`, `dev-ricky`

### 5.2 Kebiasaan Wajib Saat Mengerjakan Fitur / Perbaikan
1. **Sinkronkan kode terbaru sebelum mulai**:
   ```bash
   git fetch origin
   git checkout dev-<nama>
   git pull origin staging
   ```
2. **Setelah `git pull` dari rekan tim**, selalu cek apakah ada paket baru, migrasi baru, atau perubahan tampilan:
   ```bash
   composer install
   npm install
   php artisan migrate
   npm run build
   ```
3. **Verifikasi sebelum commit & push** agar tidak mengirim error ke branch tim:
   ```bash
   # Cek tipe TypeScript (Vue)
   npm run typecheck

   # Cek build produksi Vite berhasil
   npm run build

   # Cek formatting PHP sesuai Laravel Pint (untuk file yang berubah)
   vendor/bin/pint --dirty

   # Jalankan test suite otomatis (Pest menggunakan SQLite :memory:, aman bagi DB lokal)
   php artisan test --compact
   ```
4. **Catatan file lokal di VPS/server bersama**: Gunakan `git pull --ff-only` dan hindari perintah destruktif seperti `git reset --hard` pada mesin server agar tidak menimpa konfigurasi/lockfile lingkungan yang belum di-commit.

---

## 6. WhatsApp Gateway & Fallback Manual

Aplikasi tetap dapat dipakai penuh tanpa WhatsApp Gateway karena memiliki tombol kirim manual `wa.me` yang mengisi nomor tujuan dan isi nota secara otomatis. Namun jika ingin pengiriman pesan otomatis di latar belakang:

1. **Jalankan Gateway**:
   - **Windows**:
     ```powershell
     cd gateway
     .\whatsapp-gateway.exe
     ```
   - **macOS / Linux** (atau jika ingin compile ulang dari source Go):
     ```bash
     cd gateway
     go run .
     ```
   Gateway berjalan secara default di `http://127.0.0.1:8080` (dapat diubah lewat env `HOST` dan `PORT`) dan menyimpan sesi WhatsApp di `gateway/wuzapi.db`.
2. **Tautkan Nomor WhatsApp**:
   - Login Owner -> `/owner/settings` -> tab **WhatsApp Gateway**.
   - Pastikan URL gateway mengarah ke `http://localhost:8080` (atau URL server gateway Anda).
   - Hubungkan lewat **Scan QR Code** atau **Pairing Code OTP** dari menu *Perangkat Tertaut* di aplikasi WhatsApp HP.
   - Uji dengan tombol **Uji Koneksi** / **Kirim Pesan Uji**.

---

## 7. Backup & Restore Database

Perintah `php artisan db:backup` mendukung driver **SQLite**, **MySQL**, dan **MariaDB** (memakai `mysqldump` bila tersedia, atau fallback dump lewat PHP PDO bila `mysqldump` tidak ada di `PATH`). File hasil backup dikompres `.sql.gz` ke `storage/app/backups/` (otomatis terabaikan Git).

```bash
# Backup manual (retensi default 30 hari)
php artisan db:backup

# Backup dengan batas simpan 14 hari atau nama file khusus
php artisan db:backup --retention=14
php artisan db:backup --filename=sebelum-update.sql.gz
```

Jadwal backup otomatis harian pukul `02:00` terdaftar di `routes/console.php` (aktif di server jika cron Laravel scheduler `php artisan schedule:run` dijalankan setiap menit).

---

## 8. Troubleshooting Error Umum

### 1. Error: `Vite manifest not found at: public/build/manifest.json` atau tampilan polos/CSS tidak muncul
- **Penyebab**: Aset frontend belum dibuild atau server Vite tidak sedang menyala.
- **Solusi**: Jalankan `npm run build` (untuk file statis sekali jadi) atau jalankan `npm run dev` / `composer run dev` saat mengembangkan frontend.

### 2. Error: `No application encryption key has been specified` (HTTP 500 saat buka awal)
- **Penyebab**: File `.env` baru disalin dari `.env.example` dan `APP_KEY` masih kosong.
- **Solusi**:
  ```bash
  php artisan key:generate
  php artisan optimize:clear
  ```

### 3. Error Database: `Unknown database 'lms_andiklaundry'` atau `Database file at path [...database.sqlite] does not exist`
- **Penyebab**: DB MySQL belum dibuat, atau `DB_CONNECTION=sqlite` aktif tapi file `database/database.sqlite` belum ada.
- **Solusi**:
  - Jika memakai SQLite: buat file `database/database.sqlite` lalu `php artisan migrate --seed`.
  - Jika memakai MySQL: pastikan service MySQL menyala, buat database `lms_andiklaundry`, sesuaikan `DB_*` di `.env`, lalu `php artisan migrate --seed`.

### 4. Error `could not find driver` saat migrate atau tes
- **Penyebab**: Ekstensi PHP `pdo_sqlite` (dipakai oleh SQLite dan Pest test suite `:memory:`) atau `pdo_mysql` belum aktif di `php.ini`.
- **Solusi**: Jalankan `php -m` untuk memeriksa ekstensi yang aktif. Buka `php.ini` (cek lokasinya lewat `php --ini`), aktifkan baris `extension=pdo_sqlite` dan `extension=pdo_mysql` (serta `sqlite3`), lalu restart terminal/server.

### 5. Error kolom/tabel tidak ditemukan setelah `git pull` dari anggota tim
- **Penyebab**: Rekan tim menambahkan migrasi atau seeder baru yang belum dijalankan di komputer Anda.
- **Solusi**:
  ```bash
  php artisan migrate
  # Jika masih bermasalah di lokal dan aman mereset data demo lokal:
  php artisan migrate:fresh --seed
  ```

### 6. Perubahan di `.env` atau route tidak berefek / error 404 padahal route ada
- **Penyebab**: Cache konfigurasi atau route Laravel masih menyimpan versi lama.
- **Solusi**:
  ```bash
  php artisan optimize:clear
  ```

### 7. Port `8000`, `5173`, atau `8080` bentrok (`Address already in use`)
- **Penyebab**: Masih ada proses Laravel, Vite, atau Go gateway sebelumnya yang berjalan di latar belakang.
- **Solusi**: Tutup terminal/proses lama yang memakai port tersebut, atau jalankan Laravel di port lain sementara (`php artisan serve --port=8001`).

### 8. `.\whatsapp-gateway.exe` tidak bisa dijalankan di macOS / Linux atau koneksi WhatsApp gagal
- **Penyebab**: File `.exe` hanya untuk Windows. Di sistem lain harus dijalankan lewat Go runtime, atau jika HP ter-logout, sesi `wuzapi.db` perlu ditautkan ulang.
- **Solusi**: Di macOS/Linux gunakan `cd gateway && go run .`. Jika gateway tidak bisa dipakai atau mati, operasional POS tetap jalan normal menggunakan tombol **Kirim via WhatsApp (`wa.me`)** pada nota atau daftar transaksi.

### 9. Error saat menjalankan `php artisan test`
- **Penyebab**: Biasanya `pdo_sqlite` belum aktif (karena `phpunit.xml` memaksa `:memory:` SQLite), atau cache konfigurasi dari sesi lama nyangkut.
- **Solusi**: Bersihkan cache konfigurasi (`php artisan config:clear`) lalu jalankan `php artisan test --compact`. Untuk menguji satu file saja:
  ```bash
  php artisan test tests/Feature/LandingPageTest.php --compact
  ```
