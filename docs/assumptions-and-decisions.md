# Architectural Decision Records (ADRs) & Assumptions Log

**Project:** LMS Andik Laundry (Single-Store Laundry Management System)  
**Document Version:** 1.0.0  
**Status:** Approved Catalog  
**Decision Classification Categories:**  
- `Confirmed` — Firmly established by explicit user requirement or core constraint.  
- `Recommended` — Best-practice architectural decision proposed by engineering team.  
- `Assumption` — Baseline working assumption based on single-store laundry domain conventions.  
- `Pending Owner Approval` — Business decision requiring explicit confirmation from store proprietor.  

---

## 1. Classification Summary Matrix

| ADR ID | Decision Title | Category | Impact Area | Primary Driver |
| :--- | :--- | :---: | :--- | :--- |
| **ADR-001** | Monolithic Architecture with Laravel 13, Inertia.js 3, & Vue 3 | `Confirmed` | Core Architecture | Simplified operations, zero API drift |
| **ADR-002** | Single-Store Scope vs Multi-Outlet / Multi-Tenant | `Confirmed` | System Boundaries | Greenfield simplicity, rapid delivery |
| **ADR-003** | MySQL 8.4 LTS as Relational Database with DECIMAL Precision | `Confirmed` | Data Layer | ACID compliance, zero float rounding errors |
| **ADR-004** | Redis for Session Storage, Asynchronous Queues & Rate Limiting | `Recommended` | Infrastructure | High-performance atomic locks & queues |
| **ADR-005** | WuzAPI as Primary MVP WhatsApp Gateway with Interface Abstraction | `Recommended` | Integrations | Zero per-message cost, rapid onboarding |
| **ADR-006** | Orthogonal Decoupled Finite State Machines (Production vs Payment) | `Confirmed` | Domain Logic | Independent garment & payment lifecycles |
| **ADR-007** | Immutable Price Snapshotting on Order Items | `Confirmed` | Financial Logic | Historical transaction integrity |
| **ADR-008** | Blind Count Protocol for Cashier Shift Drawer Closing | `Confirmed` | Loss Prevention | Tamper-proof cash reconciliation |
| **ADR-009** | Dual-Basis Financial Reporting: Cash Flow vs Accrual Revenue | `Confirmed` | Financial Logic | SAK EMKM compliance & liquidity control |
| **ADR-010** | Authentication via Laravel Sanctum Stateful Session Cookies | `Confirmed` | Security & Auth | XSS mitigation, seamless Inertia SPA auth |
| **ADR-011** | Zero-Trust Server-Side Validation via Dedicated Form Requests | `Confirmed` | Security | Integrity enforcement at API boundary |
| **ADR-012** | Manual WhatsApp Link Fallback (`wa.me`) for Gateway Resiliency | `Confirmed` | UX & Operations | 100% notification availability during outages |
| **ADR-013** | Tailwind CSS 4 & 3-Layer Design Token Architecture | `Recommended` | UI/UX & Styling | WCAG AA compliance, maintainable theme |
| **ADR-014** | Minimum Billable Weight Policy (3.00 kg Kiloan Threshold) | `Pending Owner Approval` | Business Rules | Profitability on lightweight laundry bundles |
| **ADR-015** | Default Non-PKP Zero-Tax (0.00%) Invoicing for Indonesian UMKM | `Pending Owner Approval` | Invoicing & Tax | Indonesian MSME tax threshold compliance |
| **ADR-016** | Local Storage for Receipt Uploads & Expense Photos | `Assumption` | Storage | Single-server simplicity without AWS S3 costs |
| **ADR-017** | Browser Native Print Dialog for 58mm/80mm ESC/POS Thermal Printing | `Assumption` | Hardware & POS | Universal printer compatibility without drivers |

---

## 2. Detailed Architectural Decision Records

---

### ADR-001: Monolithic Architecture with Laravel 13, Inertia.js 3, and Vue 3

- **Status:** `Confirmed` (Explicit User Requirement R2)
- **Context & Problem Statement:**
  Building a web-based laundry management system with POS Cashier, Production Tracking, and Owner Dashboard could tempt architects to build separated frontend (SPA) and backend (REST API) repositories or microservices. For a single-store laundry, this introduces unnecessary network latency, CORS complexities, redundant authentication token management, and development velocity drag.
- **Decision Taken:**
  Implement LMS Andik Laundry as a **single, unified monolithic application** using Laravel 13, Inertia.js 3, and Vue 3. Unify POS Cashier, Owner Analytics, and Production Operator stations in the same repository, backend router, and database.
- **Alternatives Considered:**
  1. *Decoupled Laravel API + Nuxt 3 SPA:* Higher deployment complexity, duplicate routing definitions, JWT token storage vulnerabilities in browser.
  2. *Laravel Blade + Livewire:* Feasible, but Vue 3 provides superior reactive state management for complex POS cart math and touch interactions.
- **Consequences & Trade-offs:**
  - ✅ Rapid full-stack development with zero API contract synchronization overhead.
  - ✅ Maximum security using Laravel Sanctum session cookies (no tokens in `localStorage`).
  - ⚠️ Requires node asset build pipeline (`npm run build`) in CI/CD.

---

### ADR-002: Single-Store Scope vs Multi-Outlet / Multi-Tenant

- **Status:** `Confirmed` (Explicit User Requirement R2)
- **Context & Problem Statement:**
  Enterprise laundry systems often support multi-outlet inventory, centralized washing factories, and franchise tenant schemas. However, over-engineering for multi-tenancy increases database complexity (tenant IDs on every table, composite keys, cross-tenant data leaks) when the business operates a single physical storefront.
- **Decision Taken:**
  Explicitly design the schema, services, and business rules for a **single-store, single-tenant operation**. Omit `tenant_id`, `outlet_id`, and inter-branch transfer models.
- **Alternatives Considered:**
  1. *Multi-Tenant by Schema / Row:* Adds massive migration complexity, complex query scoping, and testing overhead not required by the store.
- **Consequences & Trade-offs:**
  - ✅ Streamlined database schema, simple queries, fast indexing, minimal foreign key nesting.
  - ⚠️ If the owner opens a second branch in the future, database refactoring or multi-database deployment will be required.

---

### ADR-003: MySQL 8.4 LTS as Primary Relational Database with DECIMAL Precision

- **Status:** `Confirmed` (Explicit User Requirement R2, R4)
- **Context & Problem Statement:**
  Retail financial transactions and scale measurements require strict transactional consistency, ACID compliance, and zero floating-point rounding errors (e.g., `0.1 + 0.2 != 0.3`).
- **Decision Taken:**
  Standardize on **MySQL 8.4 LTS** as the primary relational database. Enforce `DECIMAL(12, 2)` for all monetary columns (supports up to Rp 9,999,999,999.99) and `DECIMAL(8, 2)` for weights and quantities. Floating-point types (`FLOAT`, `DOUBLE`) are strictly forbidden for money and weights.
- **Alternatives Considered:**
  1. *PostgreSQL:* Excellent relational engine, but MySQL 8.4 LTS was explicitly specified in requirements and offers broad hosting compatibility.
  2. *INTEGER (cents):* Storing cents is common in USD, but Indonesian Rupiah has no circulating subunit (no sen). `DECIMAL(12, 2)` provides clean display and calculation without continuous multiplication/division by 100.
- **Consequences & Trade-offs:**
  - ✅ Zero floating-point rounding discrepancies.
  - ✅ Clean, audit-compliant financial reports.

---

### ADR-004: Redis for Session Storage, Asynchronous Queues & Rate Limiting

- **Status:** `Recommended`
- **Context & Problem Statement:**
  WhatsApp message dispatch, thermal receipt print preparation, and heavy report generation must not block the HTTP request thread at the POS counter. Furthermore, distributed rate limiting and stateful sessions require an in-memory data store.
- **Decision Taken:**
  Deploy **Redis 7.x** as the application's unified caching, queue broker, rate limiting backend, and session driver (`SESSION_DRIVER=redis`, `QUEUE_CONNECTION=redis`).
- **Alternatives Considered:**
  1. *Database Queue Driver (`queue:work database`):* High table write contention on `jobs` table during peak hours; potential lock delays on POS checkout.
  2. *Sync Queue (`QUEUE_CONNECTION=sync`):* Unacceptable POS latency when calling external WuzAPI or Meta endpoints (hangs cashier UI for 1–3 seconds).
- **Consequences & Trade-offs:**
  - ✅ Sub-millisecond queue dispatch and atomic lock management (`Cache::lock`).
  - ✅ Sliding-window rate limiters execute with minimal CPU overhead.
  - ⚠️ Requires Redis service to be managed and monitored on the production host.

---

### ADR-005: WuzAPI as Primary MVP WhatsApp Gateway with Interface Abstraction

- **Status:** `Recommended`
- **Context & Problem Statement:**
  The laundry needs automated WhatsApp notifications for intake receipts and order readiness alerts. Meta WhatsApp Cloud API charges per conversation (utility template fees) and requires Meta Business verification, credit cards, and pre-approved templates. WuzAPI offers a free, self-hosted Go companion gateway using WhatsApp Web protocols, but carries risks of session disconnection or phone number bans if abused.
- **Decision Taken:**
  Implement a vendor-agnostic `WhatsAppGatewayInterface`. Provide **WuzAPI (`asternic/wuzapi`) as the primary gateway for the MVP**, paired with strict rate limiting (max 12 msg/min + jitter) and manual `wa.me` fallbacks. Keep the architecture ready to switch to Meta Cloud API in Phase 7 without altering domain code.
- **Alternatives Considered:**
  1. *Meta Cloud API from Day 1:* Higher barrier to entry, ongoing operational cost, requires business registration documents.
  2. *Third-party commercial gateways (Fonnte, Wablas):* Monthly subscription costs, third-party dependency risks.
- **Consequences & Trade-offs:**
  - ✅ Zero per-message fee for MVP; fast setup using QR code pairing with existing store phone.
  - ⚠️ Operational risk of WhatsApp disconnection requiring occasional QR re-pairing. Mitigated by automated health checks and manual `wa.me` fallback links.

---

### ADR-006: Orthogonal Decoupled Finite State Machines (Production vs Payment)

- **Status:** `Confirmed` (Explicit User Requirement R4)
- **Context & Problem Statement:**
  Many rudimentary laundry apps couple production status and payment status into a single column (e.g. `status = 'washing_unpaid'`). In reality, laundry processing (physical state) and payment collection (financial state) advance completely independently: an order can be washed while unpaid, or paid in full at intake before washing starts.
- **Decision Taken:**
  Model production and payment as **two orthogonal, decoupled Finite State Machines (FSMs)**:
  - `production_status`: `['received', 'washing', 'drying', 'ironing', 'ready_for_pickup', 'out_for_delivery', 'completed', 'canceled']`
  - `payment_status`: `['unpaid', 'partially_paid', 'paid', 'overpaid', 'refunded', 'partially_refunded']` (derived from immutable payment transactions).
  - Enforce a **Release Guard** blocking transition to `completed` unless `payment_status == 'paid'` or explicit credit approval is granted.
- **Alternatives Considered:**
  1. *Single consolidated status enum:* Results in combinatorial explosion of states (`received_unpaid`, `received_paid`, `drying_unpaid`, etc.) and brittle code.
- **Consequences & Trade-offs:**
  - ✅ Clean business logic representation of real-world laundry workflows.
  - ✅ Prevents accidental garment release to customers without payment settlement.

---

### ADR-007: Immutable Price Snapshotting on Order Items

- **Status:** `Confirmed` (Explicit User Requirement R4)
- **Context & Problem Statement:**
  If a customer's order references catalog service rows directly by foreign key (`service_id`), any future price adjustment by the owner (e.g., raising kiloan price from Rp 8,000 to Rp 9,000) retroactively alters historical order totals, invalidating past tax and revenue figures.
- **Decision Taken:**
  Upon order intake, the system freezes all relevant pricing fields into immutable columns on the `order_items` table:
  - `item_name_snapshot`: `VARCHAR(150)`
  - `pricing_unit_snapshot`: `ENUM('kg', 'pcs', 'm2')`
  - `unit_price_snapshot`: `DECIMAL(12, 2)`
  - `discount_amount_snapshot`: `DECIMAL(12, 2)`
  - `subtotal_snapshot`: `DECIMAL(12, 2)`
  Historical records are permanently insulated from catalog changes.
- **Alternatives Considered:**
  1. *Temporal catalog tables with valid-from / valid-to dates:* Significantly more complex queries to reconstruct historical invoices; high bug potential.
- **Consequences & Trade-offs:**
  - ✅ 100% financial immutability and audit compliance.
  - ⚠️ Slight data duplication per line item (acceptable in modern storage).

---

### ADR-008: Blind Count Protocol for Cashier Shift Drawer Closing

- **Status:** `Confirmed` (Explicit User Requirement R4)
- **Context & Problem Statement:**
  Cash registers in retail laundry environments are vulnerable to employee skimming and unrecorded cash leakages. If the POS interface reveals the expected drawer cash total at shift closing, a dishonest cashier can easily match the reported number and pocket any surplus, or hide unexplained shortages.
- **Decision Taken:**
  Enforce the **Blind Count Shift Closing Protocol**:
  1. When closing the shift, the POS UI deliberately hides the system-calculated `expected_drawer_cash`.
  2. The cashier must physically count and input exact denomination counts (100k, 50k, 20k, 10k, 5k, 2k, 1k bills and coins).
  3. The system calculates variance (`actual - expected`) server-side upon submission.
  4. Any discrepancy is permanently recorded in the shift audit log and flagged for Owner review.
- **Alternatives Considered:**
  1. *Open count (displaying expected total):* High risk of cash skimming and false balance confirmations.
- **Consequences & Trade-offs:**
  - ✅ Complete cash accountability and loss prevention.
  - ⚠️ Requires cashier to spend 3–5 minutes counting physical bills at shift end.

---

### ADR-009: Dual-Basis Financial Reporting: Cash Flow vs Accrual Revenue

- **Status:** `Confirmed` (Explicit User Requirement R4)
- **Context & Problem Statement:**
  Laundry operations take customer deposits (down payments) before washing, and deliver completed laundry with payment deferred (customer debt / Piutang). Conflating cash collected with earned profit distorts business performance (e.g., cash collected for unwashed orders is unearned liability, not profit).
- **Decision Taken:**
  Implement **Dual-Basis Financial Reporting** complying with Indonesian SAK EMKM standards:
  - **Accrual Basis (Profit & Loss):** Service revenue is recognized only when an order is completed (`ready_for_pickup` or `completed`). Matches expenses to earned revenue.
  - **Cash Basis (Cash Flow Statement):** Tracks physical cash liquidity entering and leaving the store register and bank accounts.
  - Clearly label metrics on the Owner Dashboard so the owner never confuses cash on hand with operating profit.
- **Alternatives Considered:**
  1. *Pure Cash-Basis reporting:* Distorts monthly performance when high customer debts or advance deposits exist.
- **Consequences & Trade-offs:**
  - ✅ Professional, accurate financial insight for the store owner.
  - ✅ Clear distinction between accounts receivable (`Piutang`) and earned revenue.

---

### ADR-010: Authentication via Laravel Sanctum Stateful Session Cookies

- **Status:** `Confirmed`
- **Context & Problem Statement:**
  SPAs frequently use JWT Bearer tokens stored in browser `localStorage`. If an XSS vulnerability exists, attackers can steal tokens and impersonate staff.
- **Decision Taken:**
  Use **Laravel Sanctum's stateful session cookie guard**. Sessions are encrypted, stored in Redis, and transmitted via HTTP-only, `SameSite=Lax`, and `Secure` cookies. No JWTs are stored in browser storage.
- **Alternatives Considered:**
  1. *Bearer Tokens in localStorage:* Insecure against XSS token harvesting.
  2. *Laravel Passport (OAuth2):* Extreme overkill for a single-store internal management application.
- **Consequences & Trade-offs:**
  - ✅ Robust XSS defense; automatic session rotation; native CSRF protection.
  - ⚠️ Session cookies require same-origin or carefully configured `SANCTUM_STATEFUL_DOMAINS`.

---

### ADR-011: Zero-Trust Server-Side Validation via Dedicated Form Requests

- **Status:** `Confirmed`
- **Context & Problem Statement:**
  Client-side validation can be bypassed using proxy tools (Postman, Burp Suite, or browser console). Trusting prices, weights, or role inputs from the frontend introduces critical security and financial vulnerabilities.
- **Decision Taken:**
  Enforce **Zero-Trust Server-Side Validation**. Every mutating route is bound to a dedicated Laravel `FormRequest` class (`StoreOrderRequest`, `CloseShiftRequest`, etc.). Prices sent by the client are strictly ignored and recalculated from server master data.
- **Alternatives Considered:**
  1. *Inline controller validation (`$request->validate()`):* Violates Single Responsibility Principle and clutters controller methods.
- **Consequences & Trade-offs:**
  - ✅ Clean separation of validation logic from business logic.
  - ✅ Reusable, testable request rules.

---

### ADR-012: Manual WhatsApp Link Fallback (`wa.me`) for Gateway Resiliency

- **Status:** `Confirmed` (Explicit User Requirement R3)
- **Context & Problem Statement:**
  If the companion phone loses battery, Wi-Fi disconnects, or the WuzAPI container experiences an outage, automated WhatsApp queue jobs will fail. Counter cashiers must not be left without a way to notify customers.
- **Decision Taken:**
  Incorporate a **Manual WhatsApp Fallback Link** directly into the POS Cashier and Order screens:
  - Button: "Kirim Manual WA"
  - Generates deep-link URL: `https://wa.me/{phone}?text={url_encoded_message}`
  - Opens in new tab on POS terminal or mobile browser, allowing cashier to send the pre-composed receipt with one tap via WhatsApp Web / Desktop.
- **Alternatives Considered:**
  1. *SMS Fallback:* Requires paid SMS gateway integration (e.g. Twilio) with recurring per-SMS charges.
- **Consequences & Trade-offs:**
  - ✅ 100% operational resiliency: zero notification blockage during internet or gateway disruptions.
  - ✅ Zero extra financial cost.

---

### ADR-013: Tailwind CSS 4 & 3-Layer Design Token Architecture

- **Status:** `Recommended`
- **Context & Problem Statement:**
  POS cashier screens require high contrast, rapid scanning, and comfortable touch targets (min 44x44px) under bright retail store lighting, while owner analytics require dense data tables.
- **Decision Taken:**
  Implement a **3-Layer Design Token Architecture** using **Tailwind CSS 4**:
  - *Primitive Tokens:* Raw color scales (Slate, Emerald, Amber, Rose).
  - *Semantic Tokens:* Purpose-bound tokens (`primary`, `surface-canvas`, `text-heading`, `status-ready`).
  - *Component Tokens:* Specialized classes (`pos-btn-numpad`, `receipt-paper`).
  - Enforce WCAG AA contrast (minimum 4.5:1) and minimum 44x44px touch targets.
- **Alternatives Considered:**
  1. *Generic UI Framework (Vuetify, Element Plus):* Heavy bundle size, difficult to customize for specialized POS touch and ESC/POS thermal receipt layouts.
- **Consequences & Trade-offs:**
  - ✅ High performance, zero runtime CSS overhead, accessible, modern UI.
  - ⚠️ Requires initial design token binding in CSS configuration.

---

### ADR-014: Minimum Billable Weight Policy (3.00 kg Kiloan Threshold)

- **Status:** `Pending Owner Approval`
- **Context & Problem Statement:**
  Washing very small laundry bundles (e.g. 1.2 kg) consumes almost the same machine capacity, water, detergent, and operator handling time as a standard 3 kg bundle. Without a minimum threshold, processing small orders causes operational losses. Indonesian commercial laundries typically enforce a minimum charge threshold (usually 3.00 kg).
- **Decision Taken:**
  Recommend a **configurable default minimum weight of 3.00 kg** for all per-kilogram (`kg`) laundry services. If a customer brings laundry weighing `< 3.00 kg`, the system records `actual_weight = 1.20 kg` (for machine loading and chemical metrics) but charges `billed_weight = 3.00 kg`.
- **Alternatives Considered:**
  1. *No minimum weight:* Results in thin or negative margins on small wash orders.
  2. *Minimum 2.00 kg or 4.00 kg:* Varies by local market competition.
- **Action Required for Owner:**
  Owner must explicitly confirm whether **3.00 kg** is the desired default minimum charge threshold for Andik Laundry, or specify a customized store threshold.

---

### ADR-015: Default Non-PKP Zero-Tax (0.00%) Invoicing for Indonesian UMKM

- **Status:** `Pending Owner Approval`
- **Context & Problem Statement:**
  Indonesian Value-Added Tax (PPN) is currently 11% (or 12% under upcoming regulations). However, Indonesian Micro, Small, and Medium Enterprises (UMKM) with gross annual turnover below Rp 4.8 Billion are Non-PKP (Pengusaha Non-Kena Pajak) and are legally exempt from collecting PPN from retail customers.
- **Decision Taken:**
  Architect the database schema and financial calculation engine with full tax capabilities (`tax_rate`, `tax_amount`), but set the **default system tax rate to `0.00%`**. The system prints invoices as Non-PKP retail receipts.
- **Alternatives Considered:**
  1. *Hardcode 11% PPN on all receipts:* Unpopular with retail laundry customers; legally incorrect for Non-PKP businesses.
  2. *Omit tax columns completely:* Prevents future compliance if the business grows beyond the UMKM threshold.
- **Action Required for Owner:**
  Owner must confirm that Andik Laundry currently operates as a Non-PKP entity and that invoices should display `PPN 0%` by default.

---

### ADR-016: Local Disk Storage for Receipt Uploads & Expense Photos

- **Status:** `Assumption`
- **Context & Problem Statement:**
  Cashiers and owners upload expense receipt photos (petty cash receipts, supplier invoices). The storage solution must balance cost, simplicity, and backup convenience.
- **Decision Taken:**
  Store uploaded receipts and documents on the **local server disk** under `storage/app/public/expenses/` with symbolic link to `public/storage`. Include uploaded files in the automated daily server backup archive.
- **Alternatives Considered:**
  1. *AWS S3 / Cloudflare R2:* Adds cloud provider dependency, monthly billing, and AWS IAM credential management overhead.
- **Consequences & Trade-offs:**
  - ✅ Simple, zero cloud hosting cost.
  - ⚠️ Requires regular local disk capacity monitoring and inclusion in backup scripts.

---

### ADR-017: Browser Native Print Dialog for 58mm/80mm ESC/POS Thermal Printing

- **Status:** `Assumption`
- **Context & Problem Statement:**
  POS cashier terminals need to print physical receipts on 58mm or 80mm thermal receipt printers. Options range from browser native printing (`window.print()`) to direct raw WebUSB / ESC-POS socket printing.
- **Decision Taken:**
  Implement a dedicated, pixel-perfect CSS `@media print` thermal receipt layout and invoke the **browser native print dialog (`window.print()`)**. This ensures out-of-the-box compatibility with any USB, Bluetooth, or Wi-Fi thermal printer configured as the system default printer on Windows, macOS, or Android. Direct raw socket printing is deferred to future hardware plugin enhancements.
- **Alternatives Considered:**
  1. *WebUSB / Raw ESC-POS Socket:* Requires specific USB vendor IDs, browser flags, or custom local printing agents.
- **Consequences & Trade-offs:**
  - ✅ Universal hardware compatibility across all operating systems without proprietary drivers.
  - ⚠️ Browser print dialog displays briefly before physical printing starts.

---

## 3. Owner Action Items & Approvals Checklist

The following items are designated `Pending Owner Approval` and require the store proprietor's formal decision:

- [ ] **Approval Item 1 (ADR-014):** Confirm minimum billable weight threshold:
  - [ ] Standard 3.00 kg (Recommended)
  - [ ] Custom threshold: `_____` kg
- [ ] **Approval Item 2 (ADR-015):** Confirm tax registration status:
  - [ ] Non-PKP (Default 0.00% PPN)
  - [ ] PKP (Apply 11.00% PPN to receipts)
- [ ] **Approval Item 3 (Turnaround Surcharges):** Confirm service tier pricing add-ons:
  - [ ] Regular (48h): Base price
  - [ ] Kilat (24h): +Rp 3,000 / kg (or +40%)
  - [ ] Express (6-8h): +Rp 6,000 / kg (or +80%)
  - [ ] Super Express (2-3h): +Rp 10,000 / kg (or +100%)
