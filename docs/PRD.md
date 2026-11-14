# Product Requirements Document (PRD)
## Laundry Management System (LMS-AndikLaundry)

**Document Version:** 1.0.0  
**Status:** Approved for Implementation (Phase 0 Planning Milestone)  
**Date:** 2026-11-13  
**Target Platform:** Single-Store Monolithic Web Application (Laravel 13, Inertia.js 3, Vue 3, Tailwind CSS 4, MySQL 8.4 LTS)  
**Author:** Technical Architecture & Product Engineering Team  

---

## 1. Executive Summary & Product Vision

### 1.1 Executive Summary
**LMS-AndikLaundry** is a streamlined, single-store Laundry Management System engineered specifically for **home-based family laundry operations (usaha rumahan yang dikelola keluarga)**. The system unifies a high-speed Point of Sale (POS) Cashier interface, an intuitive Production Tracking pipeline, a pragmatic Daily Cash Summary (Rekap Kas Harian) & Expense Tracker, and an Owner Analytics Dashboard into a single, cohesive monolithic web application.

In this home-based operating model, the **Store Owner acts directly as the primary Cashier and Operator (kasirnya ya ownernya sendiri)**, supported collaboratively by family members. Unnecessary corporate bureaucracy—such as artificial shift opening locks, blind-count cash drawer disputes, and manager PIN overrides for handing over clothes—is completely eliminated in favor of operational speed, practical cash flow visibility, and seamless 1-click navigation across all features.

Unlike generic POS systems or fragmented multi-app spreadsheets, LMS-AndikLaundry is tailored specifically to Indonesian laundry retail realities: handling per-kilogram weighing with minimum billable thresholds, per-piece garment condition inspections, scent/perfume selections, orthogonal production and payment states, split-tender payments (Cash + QRIS), daily cash and expense reconciliation, and automated WhatsApp notifications with an instant manual `wa.me` fallback.

### 1.2 Product Vision
To establish Andik Laundry as the benchmark for operational efficiency, zero garment loss, transparent daily cash flow, and responsive customer communication through a robust, sub-second responsive web application running on local hardware and cloud VPS without recurring per-message software overhead.

---

## 2. Target Market, Problem Statement & Strategic Objectives

### 2.1 Problem Statement
Indonesian home-based and family-run retail laundries face distinct operational, financial, and customer relationship bottlenecks:

1. **Unrecorded Customer Credit & Handovers (Piutang Tak Tercatat):**
   - Garments are handed over to regular neighborhood customers before payment is verified, resulting in untracked accounts receivable.
   - The owner needs an effortless way to record customer debt (piutang) without clunky bureaucratic PIN barriers.
2. **Mixed Household & Business Cash Flow:**
   - In home-based laundries, daily cash received from customers is often mixed with operational purchases (detergent, softener, 3kg LPG gas, plastic packaging, water refills).
   - Without an integrated Daily Cash Recap (Rekap Kas Harian) and expense log, tracking actual daily take-home earnings is difficult.
3. **Garment Loss, Dye Bleeding & Quality Blame-Games:**
   - Garments entering the wash drum lack condition tagging (e.g., pre-existing tears, missing buttons, color run risks).
   - Incomplete tracking across wash, dry, and iron stages makes it difficult to manage re-wash cycles and keep bundles organized.
4. **Customer Anxiety & Status Inquiry Bottlenecks:**
   - Customers repeatedly call or WhatsApp asking if their laundry is ready, distracting the family during washing and pressing.
   - Manual WhatsApp messaging is tedious, prone to typing incorrect phone numbers, and inconsistent in formatting.
5. **Historical Pricing Corruption:**
   - Updating master service prices (e.g., Cuci Komplit from Rp 7,000 to Rp 8,000) accidentally mutates past transaction records and distorts historical financial reports when database relations lack price snapshotting.

### 2.2 Strategic Objectives & Key Results (OKRs)
- **Objective 1 (Frictionless Daily Cash & Expense Control):** Achieve 100% transparency for daily cash intake, non-cash payments (QRIS/Transfer), and operational disbursements via Rekap Kas Harian without shift lockouts.
  - *KR 1.1:* 100% of daily operational expenses (detergent, LPG gas, packaging) recorded with category and amount.
  - *KR 1.2:* 100% of customer debt (piutang) captured with customer ID, promised due date, and aging tracking.
- **Objective 2 (Operational Velocity & Quality):** Eliminate laundry mix-ups and streamline intake-to-wash queue times.
  - *KR 2.1:* POS order intake completed in under 45 seconds per customer (including scale weighing and receipt printing).
  - *KR 2.2:* 100% of garments staged on designated rack coordinates (`Rack-Shelf-Bin`) upon entering `ready_for_pickup`.
  - *KR 2.3:* Quality inspection catches 95%+ of residual stains at the steam ironing station before packaging, routing them through an audited re-wash loop.
- **Objective 3 (Automated Customer Communication):** Deliver timely notifications without monthly SaaS messaging fees.
  - *KR 3.1:* Automated WhatsApp notification dispatched within 5 seconds of an order reaching `ready_for_pickup`.
  - *KR 3.2:* 100% POS fallback availability via one-click `wa.me` deep-link if the automated gateway experiences an outage.

---

## 3. Scope Boundaries: In-Scope vs Out-of-Scope

To guarantee laser focus, high reliability, and rapid delivery, explicit architectural and operational boundaries are established:

```
┌────────────────────────────────────────────────────────────────────────┐
│                      LMS-AndikLaundry System Scope                     │
├──────────────────────────────────┬─────────────────────────────────────┤
│             IN SCOPE             │            OUT OF SCOPE             │
├──────────────────────────────────┼─────────────────────────────────────┤
│ • Single-store home business hub │ • Multi-outlet inventory transfers  │
│ • Unified MySQL 8.4 LTS database │ • Multi-tenant SaaS architecture    │
│ • POS Cashier interface (Vue 3)  │ • Native mobile apps (iOS/Android)  │
│ • Production Tracking board      │ • Customer self-service portal/web  │
│ • Owner Dashboard & analytics    │ • Microservices or separate backends│
│ • Digital scale integration      │ • Automated conveyor/RFID robotics  │
│ • Orthogonal state machines      │ • Complex corporate HR/payroll      │
│ • Daily Cash Recap & Expenses    │ • Integrated bank payment gateways  │
│ • Split payments (Cash + QRIS)   │   (Uses manual/static QRIS in POS)  │
│ • Price snapshot immutability    │ • Wholesale B2B laundry contracts   │
│ • WuzAPI WhatsApp + wa.me link   │ • Multi-currency accounting         │
└──────────────────────────────────┴─────────────────────────────────────┘
```

### 3.1 In-Scope Capabilities
1. **Single-Store Modular Monolith:** A cohesive Laravel 13 application with Inertia.js 3 and Vue 3 frontend running on a unified MySQL database.
2. **High-Speed POS Cashier Workflow:** Customer search/registration, digital scale weight input (`DECIMAL(8,2)`), minimum billable weight enforcement, per-piece inspection notes, fragrance selection, down payment, and split payment. Always ready without shift gates.
3. **Production Pipeline Tracking:** Status transitions across physical operational stages with station-specific validation, re-wash loops, and rack coordinate assignment (`R-XX-YY`).
4. **Orthogonal State Decoupling:** Complete separation of `production_status` and `payment_status`.
5. **Daily Cash Recap (Rekap Kas Harian) & Expense Tracker:** Direct recording of daily incoming payments (cash, QRIS, transfer), business expenses (detergent, perfume, LPG 3kg, plastic packaging), and net cash calculations.
6. **Financial & Accounting Management:** Immutable price snapshots on order items, accrual vs cash basis reporting, customer receivables (piutang) aging, and operational expense tracking.
7. **Dual-Channel WhatsApp Messaging:** Asynchronous queue-driven notifications via WuzAPI (Go + whatsmeow companion container) with one-click manual `wa.me` deep-link fallback in the cashier UI.
8. **Unified Family Team Access:** Direct, friction-free access for the Owner (as Cashier & Manager) and family assistants without artificial permission barriers.

### 3.2 Out-of-Scope (Explicitly Excluded)
1. **Multi-Outlet / Multi-Tenant Support:** No central headquarters routing, inter-branch garment transfers, or multi-tenant database partitioning. The system is dedicated to a single store.
2. **Customer Self-Service Mobile Apps:** No native Android/iOS customer applications. Customers receive notifications and digital receipts directly through WhatsApp.
3. **Automated RFID / Conveyor Hardware:** No automated mechanical sorting hardware. Garment tracking relies on thermal printed tags, QR codes, and physical staging racks.
4. **Third-Party Payment Gateway Settlement:** No automated API webhooks from payment gateways (e.g. Midtrans/Xendit) in MVP. Cashiers manually verify static QRIS or bank transfer receipts before marking payments as successful.
5. **Complex Multi-Tier Tax Compliance:** No Indonesian e-Faktur or corporate PKP tax integration (Andik Laundry operates as an MSME/UMKM with 0% PPN default).

---

## 4. User Personas & Stakeholder Profiles

The system serves three primary user personas within the single-store operating environment:

### Persona 1: Store Owner (Pak Andik)
- **Role:** Business Owner & General Manager
- **Primary Goals:**
  - Monitor daily store revenue, net cash flow, and operating profit in real time from home or on the go.
  - Prevent internal theft, register shortages, and unauthorized laundry release.
  - Track customer debt (piutang) aging and audit cashier shift variances.
  - Update master catalog prices and service offerings without corrupting historical sales records.
  - Review operational expenses (LPG gas, chemical supplies, electricity, rent) against gross income.
- **Pain Points:**
  - Cannot tell whether the cash reported in the register matches actual physical collections without manual counting.
  - Unsure how much money is currently tied up in uncollected laundry.
  - Inconsistent WhatsApp messages sent by staff damaging store reputation.
- **Technical Device:** Desktop PC, Laptop, or Tablet/Smartphone via web browser.

### Persona 2: Store Cashier (Mbak Siti)
- **Role:** Front-Counter Cashier & Customer Service
- **Primary Goals:**
  - Check in customer laundry rapidly during morning and evening rush hours (< 45s per intake).
  - Accurate weight capture directly from digital scale with automatic minimum billing rules.
  - Accept split payments (e.g., customer pays partial cash and remainder via QRIS).
  - Look up ready laundry bundles instantly by customer name, phone, or rack location.
  - Close shift with ease using a structured denomination counting form.
  - Send WhatsApp receipts and pickup notifications with a single click even if the internet gateway fluctuates.
- **Pain Points:**
  - Customers claiming they already paid when records are ambiguous.
  - Long queues when customers bring mixed bundles (kiloan + bedcover + shoes).
  - Unfair wage deductions when register cash doesn't balance due to unrecorded petty cash expenses.
- **Technical Device:** Touchscreen POS Terminal or Counter PC with Thermal Slip Printer and Digital Scale.

### Persona 3: Production Operator (Mas Joko)
- **Role:** Washer, Dryer & Steam Iron Specialist
- **Primary Goals:**
  - View the active queue of orders waiting for washing, drying, or ironing.
  - Know immediately which orders are Kilat/Express to prioritize processing before regular orders.
  - Flag stubborn stains during steam ironing and route garments back to washing without confusing the cashier.
  - Assign completed bundles to specific shelf/rack coordinates so the cashier finds them in seconds.
- **Pain Points:**
  - Washing garments with missing buttons or tears and getting blamed by the customer later.
  - Searching through mountains of plastic bundles at the front counter when a customer arrives for pickup.
  - Misplacing rush orders among regular bundles.
- **Technical Device:** Ruggedized Tablet or Touchscreen Monitor mounted in the workshop production area.

---

## 5. System Feature Inventory & Functional Specifications

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                          LMS Module Architecture                            │
├───────────────────┬───────────────────┬───────────────────┬─────────────────┤
│    MODULE 1:      │    MODULE 2:      │    MODULE 3:      │   MODULE 4:     │
│  POS & Intake     │  Production Track │  Financial & Pay  │ Cashier Shifts  │
├───────────────────┼───────────────────┼───────────────────┼─────────────────┤
│ • Scale Weighing  │ • 11-Stage Flow   │ • Split Payments  │ • Opening Float │
│ • Min Weight Rule │ • Express Queue   │ • Price Snapshots │ • Cash Drops    │
│ • Scent Selection │ • Rewash Loop     │ • Piutang Ledger  │ • Petty Cash    │
│ • Condition Notes │ • Rack Staging    │ • Accrual Revenue │ • Blind Close   │
├───────────────────┼───────────────────┼───────────────────┼─────────────────┤
│    MODULE 5:      │    MODULE 6:      │    MODULE 7:      │   MODULE 8:     │
│ Customer & CRM    │ Expenses & COGS   │ Master Catalog    │ WhatsApp Engine │
├───────────────────┼───────────────────┼───────────────────┼─────────────────┤
│ • Phone Sanitizer │ • Categorized Exp │ • Tier Pricing    │ • WuzAPI Queue  │
│ • Credit Limits   │ • Drawer Linkage  │ • Service Speeds  │ • HMAC Webhooks │
│ • Order History   │ • Profit Analysis │ • Scent Master    │ • wa.me Link    │
└───────────────────┴───────────────────┴───────────────────┴─────────────────┘
```

### Module 1: POS Order Intake & Customer Management
- **FEAT-101 (Customer Lookup & Fast Registration):** Fast search by WhatsApp phone number or name with auto-complete. If not found, inline registration requiring Name and WhatsApp Phone. Centralized phone sanitizer converts Indonesian formats (`0812...`, `+62812...`, `812...`) to normalized E.164 (`62812...`).
- **FEAT-102 (Digital Scale Weighing & Kiloan Intake):** Captures gross weight with 2 decimal places (`DECIMAL(8,2)` kg). Enforces minimum billable weight rule configured per service (e.g., minimum 3.00 kg; 1.45 kg intake calculates price against 3.00 kg).
- **FEAT-103 (Satuan / Unit Piece Inspection & Notes):** Per-piece itemization for suits, bedcovers, gowns, shoes, and carpets. Supports input of pre-existing defect notes (e.g. "Kancing kedua lepas, noda luntur di kerah") printed directly on customer intake slips.
- **FEAT-104 (Service Tier & Scent Selection):** Selection of turnaround speed (Regular 48-72h, Kilat 24h, Express 6-8h, Super Express 2-3h) and fragrance preference (Lavender, Sakura, Ocean Fresh, Snappy, Non-Parfum).
- **FEAT-105 (Thermal Receipt & QR Tag Printing):** Formats 58mm / 80mm thermal receipt containing Order Number, Customer Details, Itemized Weights, Scent, Due Date, Payment Status, and scannable QR Code.

### Module 2: Production Tracking & Queue Management
- **FEAT-201 (Visual Kanban & Station Queue):** Interactive production board showing orders grouped by stage: `received`, `washing`, `drying`, `ironing`, `ready_for_pickup`. Prioritizes Express orders with prominent visual tags and countdown timers.
- **FEAT-202 (Station Check-In & Machine Assignment):** Operators check orders into specific washing machines (#1 - #4) or gas tumbler dryers (#1 - #4), capturing operator user ID and machine timestamp.
- **FEAT-203 (Quality Control & Production Rewash Loop):** At the steam ironing station, operator inspects garments. If a residual stain or odor is found, operator triggers a "Rewash Cycle" with reason code (`stain_detected`, `odor_remaining`, `wrinkle_quality_fail`). Order routes back to `washing`, incrementing the rewash counter. If rewash count reaches 2, manager escalation is triggered.
- **FEAT-204 (Packing & Rack Coordinate Staging):** When packing is complete, operator assigns the bundle to a physical rack coordinate (e.g. `R-02-B` = Rack 2, Shelf B). Submitting this stage automatically transitions order to `ready_for_pickup` and dispatches the customer WhatsApp notification.

### Module 3: Payment & Financial Settlement
- **FEAT-301 (Orthogonal State Decoupling):** Complete architectural separation of `production_status` and `payment_status`. Production moves forward independently of payment timing.
- **FEAT-302 (Immutable Line-Item Price Snapshots):** Every order line item freezes catalog unit price, item name, unit type, discount, and subtotal at time of order creation. Future price updates never alter past orders.
- **FEAT-303 (Multi-Tender Split Payments):** Allows settling a single transaction with multiple payment methods (e.g. Rp 30,000 Cash + Rp 25,000 QRIS). Each payment creates an immutable `payment_records` row.
- **FEAT-304 (Down Payment & Pickup Settlement):** Supports partial down payments at intake with remainder paid upon pickup. Real-time calculation of remaining balance.
- **FEAT-305 (Counter Release Guard & Credit Release):** System strictly prohibits changing order status to `completed` if `payment_status != 'paid'`, unless an authorized Cashier/Owner approves credit (`is_debt_approved = true`), logging debtor name, customer ID, and promised payment due date.
- **FEAT-306 (Customer Debt / Piutang Ledger):** Dedicated ledger tracking outstanding customer debts, aging brackets (0-7 days, 8-14 days, >14 days), and automated WhatsApp debt reminder templates.

### Module 4: Cashier Shift Management & Cash Drawer Audit
- **FEAT-401 (Shift Opening & Float Injection):** Cashier cannot access POS sales screens without opening an active shift and logging the starting cash float (modal kembalian, e.g. Rp 200,000).
- **FEAT-402 (Mid-Shift Cash Drops):** Transfer of excess cash from register drawer to the store safe during peak hours, logged with authorized manager signature to maintain low drawer risk.
- **FEAT-403 (Petty Cash Emergency Outflow):** Allows cashier to disburse cash from the register for emergency store operational supplies (e.g. Rp 22,000 for 3kg LPG gas), requiring category, receipt photo upload, and descriptive notes.
- **FEAT-404 (Blind Count Shift Closing):** When closing a shift, the POS intentionally conceals the system's calculated expected cash. Cashier must enter physical counts per Indonesian currency denomination (Rp 100k, 50k, 20k, 10k, 5k, 2k, 1k, coins).
- **FEAT-405 (Variance Calculation & Shift Reports):** System calculates `variance = actual_cash - expected_cash`. Logs balanced, surplus, or shortage state. Generates printable X-Report (mid-shift summary) and Z-Report (final closed shift audit).

### Module 5: Operational Expenses & Cost Tracking
- **FEAT-501 (Expense Categorization):** Logs expenses under COGS/Bahan Baku (detergent, softener, plastic packaging), Utilities (electricity, water, maintenance), Payroll (staff wages), and General/Admin (rent, supplies).
- **FEAT-502 (Payment Source Tracking):** Differentiates whether an expense was paid from the active cashier drawer cash (impacting register reconciliation) or directly from the owner's bank account.
- **FEAT-503 (Operational Profitability Analysis):** Computes Gross Profit (Recognized Revenue - COGS) and Net Operating Profit (Gross Profit - Utilities, Payroll, Rent).

### Module 6: WhatsApp Messaging & Fallback Engine
- **FEAT-601 (Asynchronous Queue Dispatch):** Decoupled notification dispatch via Redis/MySQL queue worker, keeping POS checkout response times under 200ms.
- **FEAT-602 (WuzAPI Companion Gateway Integration):** RESTful integration with self-hosted WuzAPI container, supporting token authentication, QR session pairing, and HMAC-SHA256 webhook signatures.
- **FEAT-603 (Anti-Ban Sliding Rate Limiter & Jitter):** Enforces a maximum of 12 messages per minute with 1.5 to 3.5 seconds of random jitter sleep to protect the business WhatsApp number from spam bans.
- **FEAT-604 (Circuit Breaker & Fallback Detection):** Detects consecutive gateway timeouts or disconnects; trips circuit breaker to prevent worker hanging and alerts cashier.
- **FEAT-605 (One-Click POS Manual `wa.me` Fallback):** If the automated gateway is offline, POS displays a prominent button generating a deep-link `https://wa.me/{phone}?text={encoded}` opening WhatsApp Web/Desktop with pre-populated, personalized text.

### Module 7: Master Data & Catalog Management
- **FEAT-701 (Service Catalog Management):** Definition of service types (Kiloan, Satuan), base pricing, billing units (`kg`, `pcs`, `m2`), minimum billable thresholds, and estimated turnaround hours.
- **FEAT-702 (Turnaround Surcharges & Tiers):** Configuration of express multipliers (Kilat, Express, Flash) as flat surcharges or percentage markups.
- **FEAT-703 (Fragrance / Perfume Catalog):** Master list of available scents with active/inactive status toggles.
- **FEAT-704 (User Management & Role Permissions):** Administration of staff accounts assigned to Owner, Cashier, or Production Operator roles.

### Module 8: Owner Executive Analytics & Reporting
- **FEAT-801 (Real-Time Executive Dashboard):** Displays today's intake weight, orders completed, cash collected, accounts receivable, and active shift status.
- **FEAT-802 (Dual-Basis Financial Statements):** Clear toggle between Cash Flow Basis (actual cash in drawer + bank) and Accrual Basis (revenue recognized when laundry is ready/completed per SAK EMKM guidelines).
- **FEAT-803 (Production Productivity Metrics):** Daily turnaround time distribution, machine utilization rates, and operator re-wash incident counts.

---

## 6. User Stories & Acceptance Criteria

Every core user journey is specified using Gherkin syntax (Given, When, Then) to provide unambiguous acceptance criteria for developers and QA engineers.

### US-01: Scale Intake & Minimum Billable Weight Enforcement
- **As a** Cashier,
- **I want to** enter the customer's actual laundry weight from the digital scale and have the system automatically enforce the minimum billable weight,
- **So that** the store never undercharges for small, sub-capacity wash loads.

```gherkin
Scenario: Customer brings laundry below minimum billable weight
  Given the service "Cuci Komplit Reguler" has a base price of Rp 7,000/kg and a minimum weight of 3.00 kg
  When the Cashier enters an actual weight of 1.65 kg for customer "Budi Santoso"
  Then the system records "actual_quantity = 1.65"
  And the system records "billed_quantity = 3.00"
  And the calculated line item subtotal is exactly Rp 21,000 (3.00 kg * Rp 7,000)
  And the printed intake slip clearly displays "Berat Asli: 1.65 kg (Min. Cas: 3.00 kg)"

Scenario: Customer brings laundry above minimum billable weight
  Given the service "Cuci Komplit Reguler" has a base price of Rp 7,000/kg and a minimum weight of 3.00 kg
  When the Cashier enters an actual weight of 4.25 kg for customer "Ani Wijaya"
  Then the system records "actual_quantity = 4.25"
  And the system records "billed_quantity = 4.25"
  And the calculated line item subtotal is exactly Rp 29,750 (4.25 kg * Rp 7,000)
```

### US-02: Satuan Inspection Tagging with Defect Notes
- **As a** Cashier,
- **I want to** add itemized condition inspection notes when accepting luxury or satuan garments (e.g. Jas, Bedcover, Gaun),
- **So that** the store is protected against false customer damage claims upon pickup.

```gherkin
Scenario: Intake of luxury blazer with pre-existing tear
  Given the Cashier selects item "Jas Pria" at Rp 35,000
  When the Cashier types condition note "Kancing lengan kanan hilang 1, noda minyak di saku dada"
  And saves the order
  Then the order item stores the condition note in "item_condition_notes"
  And the printed thermal slip includes the bold warning "*Kondisi Awal: Kancing lengan kanan hilang 1, noda minyak di saku dada*"
  And the digital WhatsApp receipt contains the condition disclaimer
```

### US-03: Multi-Tender Split Payment at Counter
- **As a** Cashier,
- **I want to** accept payment split between Cash and QRIS for a single order,
- **So that** customers with insufficient cash can settle their bills conveniently.

```gherkin
Scenario: Customer settles Rp 75,000 bill using Cash and QRIS
  Given an order with Final Invoice Amount of Rp 75,000
  When the Cashier enters Payment 1 of Rp 50,000 with method "QRIS"
  And the Cashier enters Payment 2 of Rp 30,000 cash tendered against the remaining Rp 25,000 balance
  Then the system creates two immutable payment records:
    | Method | Amount Tendered | Amount Applied | Change Returned |
    | QRIS   | Rp 50,000       | Rp 50,000      | Rp 0            |
    | Cash   | Rp 30,000       | Rp 25,000      | Rp 5,000        |
  And the POS UI displays change due: "Kembalian Tunai: Rp 5,000"
  And the order "payment_status" dynamically transitions to "paid"
  And only the Rp 25,000 net cash is added to the active shift drawer balance
```

### US-04: Counter Release Guard & Customer Credit (Piutang) Authorization
- **As a** Store Owner,
- **I want to** prevent cashiers from marking orders as completed unless they are fully paid or have authorized debt approval,
- **So that** laundry is never handed over without payment or traceable accountability.

```gherkin
Scenario: Attempting to release unpaid order without credit authorization
  Given an order has "production_status = ready_for_pickup" and "payment_status = unpaid"
  When the Cashier clicks "Serahkan Cucian (Selesai)"
  Then the system blocks the transition with error "Pesanan Belum Lunas! Tagihan Rp 45.000 wajib diselesaikan."
  And the order remains in "ready_for_pickup"

Scenario: Releasing order with authorized customer debt (Piutang)
  Given an order has "production_status = ready_for_pickup" and "payment_status = unpaid"
  When the Cashier toggles "Izinkan Piutang / Bayar Nanti"
  And enters debtor name "Ibu Maya", verified customer phone, and due date "2026-11-20"
  And confirms credit release
  Then the system sets "is_debt_approved = true" and logs the cashier's user ID
  And the order transitions "production_status" to "completed"
  And the order "payment_status" remains "unpaid"
  And the transaction is logged in the Customer Receivables Ledger
```

### US-05: Quality Inspection & Production Rewash Loop
- **As a** Production Operator (Ironing),
- **I want to** route a garment bundle back to washing if I detect residual stains or bad odors during steam pressing,
- **So that** sub-standard laundry is never packaged or delivered to customers.

```gherkin
Scenario: Garment fails steam ironing quality check
  Given an order is in stage "ironing" with "rewash_count = 0"
  When the Operator clicks "Kirim Ulang Cuci (Rewash)"
  And selects reason "Noda Masih Ada (Stain Detected)" with notes "Noda saus di bagian dada belum bersih"
  Then the system transitions "production_status" back to "washing"
  And increments "rewash_count" to 1
  And logs the incident in "order_rewash_logs" with operator ID and timestamp
  And displays an orange badge "Rewash #1" on the production Kanban board

Scenario: Order fails quality check for the third time
  Given an order is in stage "ironing" with "rewash_count = 2"
  When the Operator attempts to trigger another rewash
  Then the system blocks the automatic loop
  And prompts: "Batas 2x Rewash tercapai. Laporkan ke Owner untuk konfirmasi noda membandel / risiko serat kain."
```

### US-06: Packing & Staging Rack Assignment
- **As a** Production Operator,
- **I want to** assign a specific physical rack coordinate when packing is finished,
- **So that** the front counter cashier can locate the bundle in under 10 seconds during customer pickup.

```gherkin
Scenario: Operator completes packing and stages bundle
  Given an order is in stage "ironing"
  When the Operator packs the garments, sprays the selected perfume "Sakura", and inputs rack code "R-03-A"
  And clicks "Selesai Packing & Masuk Rak"
  Then the system updates "rack_location" to "R-03-A"
  And transitions "production_status" to "ready_for_pickup"
  And queues an automated WhatsApp message to the customer stating the laundry is ready at rack "R-03-A"
```

### US-07: Cashier Shift Blind Count Close & Discrepancy Auditing
- **As a** Store Owner,
- **I want the** cashier to perform a blind cash count without seeing the system's expected balance when closing a shift,
- **So that** cashiers cannot cover up shortages or pocket unrecorded surpluses.

```gherkin
Scenario: Cashier closes shift with physical denomination count
  Given an active cashier shift has recorded Rp 850,000 in expected cash
  When the Cashier initiates "Tutup Shift"
  Then the POS screen conceals the expected cash total
  And presents input fields for each Indonesian currency denomination
  When the Cashier counts and enters:
    | Denomination | Quantity | Total       |
    | Rp 100,000   | 7        | Rp 700,000  |
    | Rp 50,000    | 2        | Rp 100,000  |
    | Rp 20,000    | 2        | Rp 40,000   |
    | Coins        | 10       | Rp 10,000   |
  And submits the blind count (Actual Cash = Rp 850,000)
  Then the system calculates "variance = 0"
  And flags the shift as "closed_balanced"
  And prints the closed shift Z-Report

Scenario: Cashier closes shift with a cash shortage
  Given an active cashier shift has recorded Rp 850,000 in expected cash
  When the Cashier submits a blind count totaling Rp 835,000
  Then the system calculates "variance = -15,000"
  And flags the shift as "closed_with_shortage"
  And prints the Z-Report highlighting "*SELISIH KAS: -Rp 15.000 (DEFISIT)*"
  And sends a high-priority alert to the Owner Dashboard
```

### US-08: Emergency Petty Cash Disbursement from Register
- **As a** Cashier,
- **I want to** disburse cash from the drawer for emergency store operational supplies and log the receipt immediately,
- **So that** my cash drawer reconciliation matches physical money at the end of the shift.

```gherkin
Scenario: Purchasing emergency LPG gas from drawer cash
  Given an active cashier shift with current drawer cash of Rp 450,000
  When the Cashier clicks "Kas Keluar (Petty Cash)"
  And enters amount Rp 22,000, category "Bahan Baku (Gas LPG)", and note "Beli tabung 3kg Toko Bu Joko"
  And uploads a photo of the purchase receipt
  Then the system creates a "shift_cash_movements" record of type "expense"
  And decrements the expected drawer cash from Rp 450,000 to Rp 428,000
  And logs the expense in the general operational expense ledger
```

### US-09: Automated WhatsApp Notification Dispatch
- **As a** Customer,
- **I want to** receive an automated WhatsApp notification when my laundry is ready for pickup,
- **So that** I know exactly when to collect it and how much balance I owe.

```gherkin
Scenario: Automated WhatsApp notification triggered on ready status
  Given an order with Order Number "LMS-20261113-0005" for customer phone "081234567890" with outstanding balance of Rp 25,000
  When the Production Operator marks the order "ready_for_pickup"
  Then the system enqueues a "SendWhatsAppNotificationJob" with idempotency key "wa:notify:order:5:ready_for_pickup"
  When the queue worker processes the job
  Then it normalizes the phone number to "6281234567890"
  And pushes the formatted message payload to WuzAPI
  And logs the dispatch status as "dispatched" in "whatsapp_message_logs"
```

### US-10: POS Manual WhatsApp Deep-Link Fallback
- **As a** Cashier,
- **I want a** one-click manual WhatsApp button in the POS when the automated gateway is down,
- **So that** customer communication is never interrupted during technical outages.

```gherkin
Scenario: Cashier uses manual WhatsApp fallback during gateway outage
  Given the WuzAPI gateway is disconnected and the circuit breaker is OPEN
  When the Cashier views order "LMS-20261113-0005" on the POS screen
  Then the POS displays a warning badge "⚠️ Gateway WA Offline"
  And displays a button "[ 📲 Kirim Manual via WhatsApp ]"
  When the Cashier clicks the manual button
  Then the browser opens a new tab with "https://wa.me/6281234567890?text={encoded_message}"
  And the system records a log entry in "whatsapp_message_logs" with "is_manual_fallback = true"
```

### US-11: Master Catalog Price Adjustment with Immutable History
- **As a** Store Owner,
- **I want to** adjust master catalog prices without altering the pricing of existing or completed orders,
- **So that** historical accounting reports and customer invoices remain 100% accurate.

```gherkin
Scenario: Owner updates service price from Rp 7,000 to Rp 8,000
  Given an existing order #101 was created yesterday with "Cuci Komplit" at Rp 7,000/kg
  When the Owner updates the master catalog price of "Cuci Komplit" to Rp 8,000/kg
  Then order #101 retains its "unit_price_snapshot = 7000.00"
  And historical financial reports for yesterday reflect the Rp 7,000 rate
  When a new order #102 is created today
  Then order #102 records "unit_price_snapshot = 8000.00"
```

### US-12: Dual-Basis Financial Reporting for Store Owner
- **As a** Store Owner,
- **I want to** view financial metrics on both Cash Flow and Accrual bases,
- **So that** I understand both my daily cash liquidity and true earned operating profit.

```gherkin
Scenario: Accrual revenue vs cash flow reconciliation across month-end
  Given order #200 (Rp 150,000) was marked "ready_for_pickup" on November 30 but picked up and paid on December 2
  When the Owner views the November P&L report (Accrual Basis)
  Then Service Revenue includes +Rp 150,000 and Accounts Receivable includes +Rp 150,000
  When the Owner views the November Cash Flow report
  Then Cash Collections shows Rp 0 from order #200
  When the Owner views the December Cash Flow report
  Then Cash Collections shows +Rp 150,000 from order #200 settlement
```

---

## 7. Non-Functional Requirements (NFRs)

### 7.1 Performance & Responsiveness
- **NFR-P1 (Sub-Second POS Intake):** Every checkout action, item addition, and weight calculation in the Vue 3 POS interface must render in under 100ms.
- **NFR-P2 (Server Response Time):** 95th percentile HTTP response times for API and Inertia page visits must remain under 300ms on a standard 2-vCPU / 4GB RAM host.
- **NFR-P3 (Queue Asynchrony):** All external I/O operations (WhatsApp API dispatches, thermal print spooling, PDF generation) must be offloaded to asynchronous background queue workers. The main web request must never block on external network latency.

### 7.2 Data Integrity & Precision
- **NFR-D1 (Monetary Decimal Standard):** All financial attributes (unit prices, subtotals, discounts, tax, payments, receivables, expenses) must be defined and stored as `DECIMAL(12, 2)` to eliminate floating-point rounding errors.
- **NFR-D2 (Weight Decimal Standard):** Laundry weights must be defined and stored as `DECIMAL(8, 2)` (e.g., `4.25` kg).
- **NFR-D3 (Atomic Financial Transactions):** All checkout, payment recording, and cashier drawer balance operations must execute inside strict database transactions (`DB::transaction()`). If any step fails, the entire transaction rolls back completely.
- **NFR-D4 (Referential Integrity):** All relational tables must enforce foreign key constraints with explicit `ON DELETE RESTRICT` for primary financial ledgers to prevent accidental data deletion.

### 7.3 Security & Role-Based Access Control
- **NFR-S1 (Authentication & Session Security):** State-preserving Sanctum session authentication with HTTP-only, SameSite, Secure cookies.
- **NFR-S2 (RBAC Policy Enforcement):** Strict authorization gates:
  - `Cashier`: Can create orders, take scale measurements, record payments, and manage active shift drawer. Cannot modify master catalog prices, delete historical orders, or view owner net profit reports.
  - `Production Operator`: Can update production stages, trigger rewash loops, and assign rack coordinates. Cannot access financial ledgers, payment screens, or cash drawers.
  - `Owner`: Unrestricted access across catalog, shifts, audit logs, expenses, and financial P&L statements.
- **NFR-S3 (Raw Body Webhook Verification):** All incoming webhooks from WuzAPI must be cryptographically verified using HMAC-SHA256 computed over the raw HTTP request stream before JSON parsing.

### 7.4 Availability & Fault Tolerance
- **NFR-A1 (Offline Fallback Continuity):** If internet connectivity to the WhatsApp gateway drops, the POS interface must remain 100% operational for intake, printing, and cash transactions, switching smoothly to manual `wa.me` links.
- **NFR-A2 (Automated Queue Retries):** Transient network errors during automated messaging must trigger exponential backoff retries (10s, 60s, 300s) before marking a job as failed.

### 7.5 Usability, Hardware & Accessibility
- **NFR-U1 (Touch-Screen Optimization):** POS interface designed for touchscreens with minimum interactive tap target sizes of 44x44 pixels.
- **NFR-U2 (High-Contrast Theme):** Adheres to WCAG 2.1 AA standards (minimum 4.5:1 text-to-background contrast ratio) to ensure legibility under bright counter lighting.
- **NFR-U3 (Keyboard Shortcuts):** Critical POS workflows (Search Customer, Submit Order, Pay, Open Drawer) must support single-key shortcuts (e.g. `F1` - `F8`, `Enter`, `Esc`) for high-velocity cashier operations.

---

## 8. Milestone Alignment & Traceability Matrix

| Requirement / User Story | Feature ID | Document Primary Source | Implementing Milestone |
|:---|:---|:---|:---:|
| Single-Store Monolith Scope | PRD §3 | `docs/PRD.md` | Milestone 1 |
| 11-Stage Operations Lifecycle | FEAT-201 | `docs/business-requirements.md` | Milestone 1 |
| Orthogonal State Machines | FEAT-301 | `docs/business-requirements.md` | Milestone 1 & 3 |
| User Personas & Flows | US-01 - 12 | `docs/user-flows.md` | Milestone 1 |
| Relational Schema & Indexes | NFR-D1-D4 | `docs/database-schema.md` & `erd.md` | Milestone 2 |
| System Architecture & Queues | NFR-P3 | `docs/architecture.md` | Milestone 2 |
| Immutable Price Snapshots | FEAT-302 | `docs/financial-logic.md` | Milestone 3 |
| Cashier Shift Blind Count | FEAT-404 | `docs/business-requirements.md` & `financial-logic.md` | Milestone 3 |
| WuzAPI & Fallback Architecture | FEAT-601-605| `docs/whatsapp-integration.md` | Milestone 3 |
| Touchscreen Design Tokens | NFR-U1-U3 | `docs/design-system.md` | Milestone 3 |
| RBAC Security & Webhook HMAC | NFR-S1-S3 | `docs/security.md` | Milestone 4 |
| Test Strategy & Coverage | All US | `docs/test-plan.md` | Milestone 4 |
| Phased Roadmap (0 to 7) | Strategic | `docs/roadmap.md` | Milestone 4 |
| ADR Architecture Decisions | Strategic | `docs/assumptions-and-decisions.md` | Milestone 4 |
| Single-Node Production Hosting| NFR-A1 | `docs/deployment.md` | Milestone 4 |
