# LMS Andik Laundry

> **Production-Grade Laundry Management, POS & SAK EMKM Accounting System**  
> Tailored for Indonesian Laundry MSMEs (Kiloan, Satuan, and Meteran operations).

---

## 1. System Overview & Tech Stack

LMS Andik Laundry is a modern, high-performance web platform engineered to manage end-to-end operational, financial, and customer tracking workflows for Indonesian laundry businesses. It provides role-based access control (RBAC), multi-unit pricing calculation (weight with minimum weight threshold, item units, square meter measurements), live production station tracking, public digital receipts with WhatsApp notification capabilities, automated daily database backups, and SAK EMKM-compliant dual-basis financial accounting.

### Technology Stack
- **Backend Framework**: [Laravel 13.x](https://laravel.com) on **PHP 8.4**
- **Frontend Engine**: [Inertia.js v3](https://inertiajs.com) with [Vue 3](https://vuejs.org) (Composition API, `<script setup lang="ts">`, TypeScript strict mode)
- **Styling & UI**: [Tailwind CSS v4](https://tailwindcss.com) with `@tailwindcss/vite`, Radix Vue, and Lucide Vue Next
- **Database Engine**: **MySQL 8.4** (with seamless SQLite in-memory support for testing)
- **Automated Test Suite**: [Pest PHP v4](https://pestphp.com) (350 feature and unit tests, 100% passing)
- **Asset Bundler**: [Vite 8](https://vitejs.dev) with clean ESNext chunking

---

## 2. Default Seed & Demo Credentials

The database seeder provisions three canonical user accounts covering each operational role:

| Role | Email | Password | Primary Responsibility & Access Scope |
| :--- | :--- | :--- | :--- |
| **Owner** | `owner@andiklaundry.test` | `Password123!` (or `password`) | Executive dashboard, SAK EMKM accrual & cash flow reports, CSV exports, user management, system settings, audit logs, backup management. |
| **Cashier** | `cashier@andiklaundry.test` | `Password123!` (or `password`) | POS order creation, customer intake, payment processing (Cash, QRIS, Bank Transfer, e-Wallet), digital receipts, cash drawer shift management. |
| **Operator** | `operator@andiklaundry.test` | `Password123!` (or `password`) | Production workstation, status tracking (received -> washing -> drying -> ironing -> packing -> ready for pickup -> completed), rack assignments, rewash handling. |

---

## 3. Owner Operational Runbook

### 3.1 Executive Dashboard & Financial Reporting (`/dashboard`)
The Owner Executive Dashboard delivers real-time visibility into business performance with side-by-side KPI cards distinguishing **Accrual Basis** (SAK EMKM compliance) from **Cash Basis** (liquidity and drawer cash):

1. **Accrual Basis Metrics (SAK EMKM)**:
   - **Recognized Revenue**: Earned exclusively from orders that have achieved `completed` production status within the selected timeframe, regardless of whether payment has been settled.
   - **Operating Expenses**: Recorded operational expenses incurred within the period (e.g. detergents, electricity, LPG, salaries).
   - **Net Operating Profit**: Recognized Revenue minus Operating Expenses.
   - **Operating Profit Margin %**: Calculated as `(Net Profit / Recognized Revenue) * 100%`.
2. **Cash Basis Metrics (Cash Flow & Drawer Audit)**:
   - **Cash Inflow by Channel**: Categorized into physical cash (`tunai`), dynamic QRIS (`qris`), manual bank transfer (`transfer`), and digital wallets (`ewallet`).
   - **Cash Outflow**: Total cash expenses disbursed directly from the register drawer or operational funds.
   - **Net Cash Flow**: Total Cash Inflow minus Total Cash Outflow.
   - **Net Cash on Hand (Fisik Laci Kasir)**: Physical cash received (`tunai`) minus physical cash paid out, reflecting the actual banknotes in the register drawer.
3. **Cash-to-Accrual Gap Reconciliation**:
   - Explicitly explains the gap between cash flow and recognized income:
     - *Uncollected Receivables (Piutang Usaha)*: Orders completed and recognized as revenue, but outstanding balance remains unpaid by customers.
     - *Work-in-Progress Intake (Uang Muka Pelanggan)*: Cash paid upfront at intake for laundry currently in washing/drying/ironing status, which is not yet recognized as accrual revenue.

### 3.2 Date Range Filtering
The dashboard and accounting service support flexible date range queries:
- **Preset Buttons**: Quick 1-click filtering for `Hari Ini` (Today), `7 Hari Terakhir` (Last 7 Days), and `Bulan Ini` (Current Month).
- **Custom Date Picker**: Select arbitrary `start_date` and `end_date`.
- **Validation Guards**: Enforces `end_date >= start_date` and a maximum query ceiling of 366 days to protect database server memory and ensure consistent query latency.

### 3.3 Transaction CSV Export
Download detailed transactional records for external accounting or tax filing:
- **Direct Endpoint**: `GET /owner/reports/export-csv?start_date=YYYY-MM-DD&end_date=YYYY-MM-DD`
- **Dashboard Action**: Click the **Unduh CSV** button on the Executive Dashboard.
- **Security Features**:
  - Encoded with **UTF-8 BOM** (`\xEF\xBB\xBF`) for native character support in Microsoft Excel and LibreOffice Calc.
  - **CSV Formula Injection Guard**: Automatically prepends apostrophes (`'`) to values starting with `=`, `+`, `-`, or `@` to neutralize formula execution risks.
  - Formatted columns: No Nota, Tanggal, Pelanggan, Status Produksi, Total Tagihan, Terbayar, Sisa Piutang, and Metode Pembayaran.

---

## 4. Database Backup & Disaster Recovery Runbook

### 4.1 Automated & Manual Backup Execution
The application includes a native, environment-aware database backup command:

```powershell
# Manual backup execution with default 30-day retention
php artisan db:backup

# Backup with custom retention threshold (e.g. 14 days)
php artisan db:backup --retention=14

# Backup with custom archive name
php artisan db:backup --filename=pre-migration-dump.sql.gz
```

- **Output Directory**: `storage/app/backups/`
- **Naming Pattern**: `backup-YYYY-MM-DD_His.sql.gz` (e.g. `backup-2026-11-14_093000.sql.gz`)
- **Compression**: Maximum gzip compression (Level 9 via PHP `zlib`) directly written to disk.
- **Git Protection**: `storage/app/backups/.gitignore` enforces `*\n!.gitignore\n`, guaranteeing zero accidental repository leakage of customer or financial data.
- **Multi-Driver Engine**:
  - Automatically identifies active connection (`mysql` or `sqlite`).
  - For MySQL: Utilizes `mysqldump` client binary if present, with an automated fallback to pure PHP PDO schema/data extractor (`SET FOREIGN_KEY_CHECKS=0`).
  - For SQLite: Handles both physical SQLite files and in-memory test databases (`sqlite_master` DDL and row INSERT generation).

### 4.2 Automated Daily Backup Schedule
Backups are registered to run automatically every night at **02:00 WIB**:

```php
// routes/console.php
Schedule::command('db:backup')
    ->dailyAt('02:00')
    ->description('Daily database backup with 30-day retention');
```

Verify the active schedule using:
```powershell
php artisan schedule:list
```

In production, ensure the system cron job triggers Laravel's scheduler once every minute:
```bash
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

### 4.3 Automated 30-Day Retention Pruning
During each backup execution, the system scans `storage/app/backups/` and unlinks any `.sql.gz` archive where the last modified timestamp (`filemtime`) exceeds the retention period (default: 30 days). Pruning activity is logged to `storage/logs/laravel.log`.

### 4.4 Disaster Recovery & Restoration Procedure
In the event of hardware failure, database corruption, or disaster recovery drills, restore the database using the following two steps:

**Step 1: Decompress the backup archive**
- *Linux / macOS*:
  ```bash
  gzip -d -k storage/app/backups/backup-2026-11-14_020000.sql.gz
  ```
- *Windows (PowerShell / 7-Zip)*:
  ```powershell
  # Using 7-Zip
  7z x storage\app\backups\backup-2026-11-14_020000.sql.gz -ostorage\app\backups\
  ```

**Step 2: Import SQL dump into MySQL**
```powershell
mysql -u root -p lms_andiklaundry < storage/app/backups/backup-2026-11-14_020000.sql
```

---

## 5. Security Controls & Hardening

### 5.1 HTTP Security Headers Middleware
All incoming web requests pass through `App\Http\Middleware\SecurityHeadersMiddleware`:
- `X-Content-Type-Options: nosniff` (Prevents MIME-sniffing attacks)
- `X-Frame-Options: SAMEORIGIN` (Mitigates clickjacking attacks)
- `X-XSS-Protection: 1; mode=block` (Browser cross-site scripting filter activation)
- `Referrer-Policy: strict-origin-when-cross-origin` (Safeguards sensitive referral parameters)
- `Permissions-Policy: camera=(), microphone=(), geolocation=()` (Disables unauthorized browser hardware APIs)
- `Content-Security-Policy`: Restricts scripts, styles, fonts, and frame ancestors while permitting Google Fonts and Vite HMR.
- `Strict-Transport-Security: max-age=31536000; includeSubDomains` (Enforced on HTTPS connections)

### 5.2 Public Digital Receipt Rate Limiting
To defend against automated scraping, invoice harvesting, or brute-force enumeration of public receipts:
- Endpoints: `GET /receipts/{invoice_number}` and alias `GET /receipt/{orderNumber}`
- Rate Limiter: Named limiter `receipts` configured in `AppServiceProvider`:
  - **Limit**: **60 requests per minute per IP address**.
  - **Exceeding**: Returns HTTP `429 Too Many Requests` with retry headers.

---

## 6. Developer, QA & Verification Commands

### 6.1 Prerequisites
- **PHP**: `>= 8.4` (required extensions: `pdo_mysql`, `pdo_sqlite`, `zlib`, `intl`, `bcmath`, `curl`)
- **Composer**: `>= 2.7`
- **Node.js**: `>= 20.x` & **npm**: `>= 10.x`
- **MySQL**: `>= 8.4`

### 6.2 Running Automated Tests (Pest Suite)
Execute the complete test suite (350 tests):
```powershell
# Set PHP in path if required
$env:PATH = "C:\tools\php;C:\Program Files\MySQL\MySQL Server 8.4\bin;$env:PATH"

# Run all 350 tests
php artisan test

# Run Database Backup & Security tests
php artisan test tests/Feature/DatabaseBackupTest.php

# Run Accounting & Reporting tests
php artisan test tests/Feature/AccountingReportTest.php
```

### 6.3 TypeScript Static Analysis & Frontend Compilation
```powershell
# Static type checking with vue-tsc (0 errors expected)
npx vue-tsc --noEmit

# Compile production Vite assets
npm run build
```

---

## 7. License

LMS Andik Laundry is proprietary software designed for Indonesian laundry business operations. All rights reserved.
