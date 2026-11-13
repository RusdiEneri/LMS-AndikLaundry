# Business Requirements & Operational Rules Specification
## Laundry Management System (LMS-AndikLaundry)

**Document Version:** 1.0.0  
**Status:** Approved for Implementation (Phase 0 Planning Milestone)  
**Date:** 2026-11-13  
**Target Platform:** Single-Store Monolithic Web Application (Laravel 13, Inertia.js 3, Vue 3, Tailwind CSS 4, MySQL 8.4 LTS)  
**Scope:** Single-Store Retail Laundry Operations & Financial Controls  

---

## 1. Introduction & Domain Architecture

This document establishes the authoritative, binding business rules and operational specifications governing the LMS-AndikLaundry system. Every business entity, finite state machine transition, financial calculation, and physical workflow within the application must strictly adhere to the rules defined herein.

The system is architectured around three foundational principles:
1. **Physical Traceability:** Every garment bundle that enters the store is uniquely tracked across an 11-stage physical and digital operations lifecycle.
2. **State Orthogonality:** The physical state of laundry processing (`production_status`) is strictly decoupled from the financial state of invoice payment (`payment_status`).
3. **Financial Immutability & Auditability:** Historical transactions freeze their pricing snapshots, cash drawers enforce blind-count shift closing protocols, and order handovers are safeguarded by strict counter release guards.

---

## 2. The 11-Stage Laundry Operations Lifecycle

```
┌────────────────────────────────────────────────────────────────────────────────────────┐
│                        11-Stage Operational Lifecycle Pipeline                         │
└────────────────────────────────────────────────────────────────────────────────────────┘

  [ 1. Intake / Penerimaan ]
             │
             ▼
  [ 2. Tagging / Pelabelan ]
             │
             ▼
  [ 3. Sorting & Spotting ]
             │
             ▼
  [ 4. Washing / Pencucian ] ◄────────────────────────────────────────┐
             │                                                        │
             ▼                                                        │
  [ 5. Drying / Pengeringan ]                                         │
             │                                                        │
             ▼                                                        │
  [ 6. Ironing / Penyetrikaan ] ──(Residual Stain Detected: Rewash)───┘
             │ (Quality Pass)
             ▼
  [ 7. Packing & Perfuming ]
             │
             ▼
  [ 8. Ready for Pickup & Rack Staging ] ────(Auto WhatsApp Alert)
             │
             ├──────────────────────────────────────┐
             ▼ (Counter Pickup)                     ▼ (Home Delivery)
  [ 10. Completed & Handover ]            [ 9. Out for Delivery ]
             ▲                                      │
             └──────────────────────────────────────┘
             
  [ 11. Canceled & Claim Settlement ] ◄── (Exception path from Stages 1-4)
```

### Stage 1: Intake / Drop-Off (Penerimaan)
- **Actor:** Cashier (`role: cashier`)
- **Trigger:** Customer brings laundry to front counter.
- **Physical Actions:**
  1. Cashier identifies or registers customer using E.164 phone normalization.
  2. Cashier places laundry on calibrated digital scale, capturing gross weight in kilograms (`DECIMAL(8,2)`).
  3. For Satuan items (bedcover, suit, wedding dress, shoes), cashier counts pieces and inspects fabrics.
  4. Cashier confirms service tier (Regular, Kilat, Express), fragrance scent, and notes pre-existing damages.
- **System Validations & Invariants:**
  - `customer_id` must reference an active customer record with normalized phone number.
  - Weight for Kiloan items must be $> 0.00$ kg. Minimum billable weight threshold is applied dynamically.
  - For Satuan items, `item_condition_notes` is mandatory if garment exhibits pre-existing tears, stains, or missing buttons.
  - Generates immutable order tracking code: `ORD-YYYYMMDD-XXXX`.
- **Exit Condition:** Order created in database with `production_status = 'received'` and thermal intake slip printed.

### Stage 2: Tagging & Barcoding (Pelabelan)
- **Actor:** Cashier or Production Operator (`role: cashier` or `role: operator`)
- **Trigger:** Order confirmation at intake.
- **Physical Actions:**
  1. Cashier prints waterproof heat-seal or pin tags on 58mm thermal ribbon.
  2. Tags are securely attached to the bundle bag or individual luxury garments.
  3. Tag data displays: Order Number (`ORD-...`), Customer Initials, Piece Count, Service Tier, and Scent Code.
- **System Invariants:**
  - Prevents garment bundle mixing during subsequent batch sorting or multi-drum washing.
  - Each tag contains a scannable Code128 barcode or QR code encoding the Order ID.
- **Exit Condition:** Garments tagged; bundle placed in the sorting staging basket.

### Stage 3: Sorting & Pre-Treatment Spotting (Penyortiran & Spotting)
- **Actor:** Production Operator (`role: operator`)
- **Trigger:** Tagged bundle moves into the workshop processing floor.
- **Physical Actions:**
  1. Operator separates garments into fabric compatibility categories:
     - Category A: Whites and light cottons.
     - Category B: Dark and color-bleed-risk textiles.
     - Category C: Heavy materials (denim, towels, jackets).
     - Category D: Delicates (silk, lace, wool, satin).
  2. Spotting: Operator inspects collars, cuffs, and stubborn stains (oil, blood, ink, tea), applying specialized chemical pre-wash spotting agents (alkali, sour, degreaser).
- **System Invariants:**
  - If a garment is discovered with severe defects not recorded at intake (e.g. torn pocket lining), operator flags order as `intake_discrepancy_alert`, prompting cashier to notify customer via WhatsApp before washing.
- **Exit Condition:** Pre-treatment complete; sorted garments staged at washer station.

### Stage 4: Washing (Pencucian)
- **Actor:** Production Operator (`role: operator`)
- **Trigger:** Operator loads sorted batch into designated washing machine.
- **Physical Actions:**
  1. Operator assigns laundry to physical Washing Machine ID (Washer #1 to #4).
  2. Chemical dosing pump or manual dispenser injects measured detergent and oxygen bleach based on billed weight.
  3. Operator selects wash cycle program (Normal 45m, Delicates 30m, Heavy 60m).
- **System Invariants:**
  - Operator logs machine assignment in system.
  - System updates `production_status` to `'washing'`.
  - Timestamp `washing_started_at` is permanently recorded for machine cycle auditing.
- **Exit Condition:** Wash cycle completes; garments transferred to extraction cart.

### Stage 5: Drying / Tumbler (Pengeringan)
- **Actor:** Production Operator (`role: operator`)
- **Trigger:** Unloading spin-extracted garments from washer drum.
- **Physical Actions:**
  1. Operator transfers wet garments into Gas Tumbler Dryer (Dryer #1 to #4).
  2. Thermal setting selection: Low Heat (50°C) for synthetics/delicates, High Heat (70°C) for cottons/linens.
  3. Cycle timer set to 35–45 minutes until 100% dry.
- **System Invariants:**
  - System updates `production_status` to `'drying'`.
  - Machine assignment and start timestamp logged in `production_logs`.
- **Exit Condition:** Dryer cycle completes; dry laundry placed into ironing queue basket.
  *(Exception: If service tier is "Cuci Kering Lipat" / Wash-Dry-Fold without ironing, order skips directly from Stage 5 to Stage 7 Packing).*

### Stage 6: Ironing & Steam Pressing (Penyetrikaan Uap)
- **Actor:** Production Operator (`role: operator`)
- **Trigger:** Dry laundry bundle reaches the steam boiler ironing table.
- **Physical Actions:**
  1. Operator presses garments using high-pressure steam boiler irons (Setrika Uap Boiler).
  2. Critical Quality Control (QC) Gate: Operator inspects each piece under high-intensity workbench illumination.
  3. Residual Stain Check: If residual dirt, oil, or food stain is discovered, operator triggers the **Production Rewash Loop** (see §6).
- **System Invariants:**
  - System updates `production_status` to `'ironing'`.
  - Garments passing quality check proceed immediately to packing.
- **Exit Condition:** All items in order ironed to crisp finish and pass visual inspection.

### Stage 7: Packing & Perfuming (Pengemasan & Semprot Parfum)
- **Actor:** Production Operator (`role: operator`)
- **Trigger:** Ironing completed.
- **Physical Actions:**
  1. Operator applies fine-mist spray of customer's selected fragrance (Lavender, Sakura, Ocean Fresh, Snappy, Non-Parfum) using pneumatic atomizer.
  2. Garments are folded uniformly using folding boards or placed on hangers.
  3. Operator encloses bundle in high-clarity branded poly bag (plastik jinjing) and heat-seals edges to protect against dust and humidity.
  4. Piece count verification: Operator counts folded pieces against intake slip count.
- **System Invariants:**
  - System verifies total packaged piece count matches intake piece count. Discrepancies prompt operator confirmation.
- **Exit Condition:** Bundle sealed; ready for staging rack assignment.

### Stage 8: Ready for Pickup & Rack Staging (Siap Ambil & Penempatan Rak)
- **Actor:** Production Operator (`role: operator`)
- **Trigger:** Sealed bundle placed onto storage shelves.
- **Physical Actions:**
  1. Operator places packaged bundle into physical shelf/bin coordinate (e.g. `Rack 02, Shelf B` -> `R-02-B`).
  2. Operator inputs rack coordinate `R-02-B` into system and clicks "Masuk Rak & Siap Ambil".
- **System Invariants:**
  - System updates `production_status` to `'ready_for_pickup'`.
  - `rack_location` attribute stored as `VARCHAR(20)`.
  - System triggers asynchronous `SendWhatsAppNotificationJob` pushing automated "Cucian Selesai" message with rack code, invoice total, and remaining balance.
- **Exit Condition:** Order stored safely on shelf; customer notified.

### Stage 9: Out for Delivery (Pengantaran Kurir - Optional)
- **Actor:** Courier / Delivery Operator (`role: operator` or `role: cashier`)
- **Trigger:** Customer requested pickup & delivery service tier.
- **Physical Actions:**
  1. Courier picks up bundle from assigned rack coordinate.
  2. Courier scans order barcode into delivery dispatch manifest.
  3. Order dispatched to customer address.
- **System Invariants:**
  - System updates `production_status` to `'out_for_delivery'`.
  - If `payment_status` is unpaid, manifest marks order as "Cash on Delivery (COD)".
- **Exit Condition:** Courier arrives at customer delivery destination.

### Stage 10: Completed & Handover (Selesai & Serah Terima)
- **Actor:** Cashier (`role: cashier`)
- **Trigger:** Customer arrives at store counter or courier arrives at customer doorstep.
- **Physical Actions:**
  1. Customer presents digital invoice, WhatsApp message, or order number.
  2. Cashier verifies payment status in POS:
     - If `paid`: Cashier retrieves bundle from rack location (`R-02-B`) and hands it over immediately.
     - If `unpaid` or `partially_paid`: Cashier collects remaining balance (Cash, QRIS, Transfer) before release, OR executes authorized debt approval (see §5).
- **System Invariants:**
  - **Counter Release Guard Enforced:** System strictly blocks transition to `completed` if `payment_status != 'paid'`, unless `is_debt_approved = true`.
  - Once requirements are satisfied, system updates `production_status` to `'completed'`.
  - Timestamp `completed_at` is recorded. Lifecycle is closed.
- **Exit Condition:** Garments handed over; financial settlement finalized.

### Stage 11: Canceled & Claim Settlement (Pembatalan & Klaim Kerugian)
- **Actor:** Store Owner or Cashier with Owner PIN (`role: owner`)
- **Trigger:** Customer cancels order prior to washing, or severe fabric damage occurs during processing.
- **Business Rules:**
  - Orders may only be canceled prior to entering `ironing`. Once ironed and packed, cancellation is prohibited.
  - If customer deposited advance payment (Down Payment), system generates a mandatory `refund_records` entry.
  - In cases of garment damage or loss: Owner files "Loss Incident Claim". System creates compensation voucher or cash settlement recorded under `Beban Kerugian Operasional`.
  - System updates `production_status` to `'canceled'`.
- **Exit Condition:** Refund issued; claim signed; order archived.

---

## 3. Orthogonal Production & Payment State Machines

The foundational architectural invariant of LMS-AndikLaundry is that laundry operational progression and customer financial settlement are **completely decoupled orthogonal finite state machines**.

### 3.1 Rationale for State Decoupling
In traditional retail POS systems, an order is either "Open" or "Paid". In service laundries, this coupling fails completely:
- A customer may pay 100% upfront (Lunas Depan) while garments remain unwashed in the intake basket.
- A trusted customer may take their finished laundry home and pay via bank transfer tomorrow (Piutang).
- A customer may pay a 50% deposit at drop-off and settle the remaining 50% upon pickup.

Coupling these states results in data corruption, inaccurate cash reconciliation, and inability to report true operating profit.

### 3.2 Finite State Machine Definitions

```
FSM 1: Production State Machine (production_status)
===================================================
[received] ──> [washing] ──> [drying] ──> [ironing] ──> [ready_for_pickup] ──> [completed]
      │             ▲                        │                │                     ▲
      │             │                        │                ▼                     │
      │             └──(Rewash Loop: Max 2)──┘        [out_for_delivery] ───────────┘
      │
      └──> [canceled] (Manager PIN Required)


FSM 2: Payment State Machine (payment_status)
=============================================
Calculated Deterministically from Immutable Payment Records:
       ┌───────────────────────────────┐
       ▼                               │
   [unpaid] ──> [partially_paid] ──> [paid] ──> [overpaid]
       │               │               │
       ▼               ▼               ▼
 [partially_refunded] ─────────────────┴──────> [refunded]
```

### 3.3 Dynamic Deterministic Derivation of Payment Status
Payment status is **never** manually updated via an arbitrary form dropdown. It is deterministically derived from the sum of immutable ledger entries:

$$\text{Net Paid} = \sum \text{payment\_records(amount)} - \sum \text{refund\_records(amount)}$$

$$\text{Final Invoice} = \text{Subtotal} - \text{Discount} + \text{Surcharges} + \text{Tax}$$

- If $\text{Net Paid} = 0$ and $\sum \text{Refund} = 0$: `payment_status = 'unpaid'`
- If $0 < \text{Net Paid} < \text{Final Invoice}$: `payment_status = 'partially_paid'`
- If $\text{Net Paid} = \text{Final Invoice}$: `payment_status = 'paid'`
- If $\text{Net Paid} > \text{Final Invoice}$: `payment_status = 'overpaid'`
- If $\text{Net Paid} = 0$ and $\sum \text{Refund} > 0$: `payment_status = 'refunded'`
- If $0 < \text{Net Paid} < \text{Final Invoice}$ due to partial refund: `payment_status = 'partially_refunded'`

### 3.4 Complete Orthogonal State Matrix

| Production State | `unpaid` | `partially_paid` | `paid` | `refunded` |
|:---|:---:|:---:|:---:|:---:|
| `received` | ✅ Common (Bayar Nanti) | ✅ Common (Down Payment) | ✅ Common (Lunas Depan) | ❌ Invalid |
| `washing` | ✅ Permitted | ✅ Permitted | ✅ Permitted | ❌ Invalid |
| `drying` | ✅ Permitted | ✅ Permitted | ✅ Permitted | ❌ Invalid |
| `ironing` | ✅ Permitted | ✅ Permitted | ✅ Permitted | ❌ Invalid |
| `ready_for_pickup` | ✅ Awaiting counter pay | ✅ Awaiting balance pay | ✅ Fast customer handoff | ❌ Invalid |
| `out_for_delivery` | ✅ COD payment collection| ✅ COD balance collection| ✅ Pre-paid delivery handoff| ❌ Invalid |
| `completed` | ⚠️ Controlled (Piutang) | ⚠️ Controlled (Piutang) | ✅ Standard closed order | ⚠️ Claim settlement |
| `canceled` | ✅ Voided before pay | ⚠️ Requires DP refund | ⚠️ Requires full refund | ✅ Settled refund |

---

## 4. Measurement & Minimum Billable Weight Rules

### 4.1 Unit of Measurement Standards
1. **Kilogram (`kg`):** Used for Kiloan wash bundles. Captured with 2 decimal precision (`DECIMAL(8,2)`). Minimum display step: 0.05 kg.
2. **Piece (`pcs`):** Used for discrete satuan garments (blazers, shirts, dresses, shoes, bags). Represented as `DECIMAL(8,2)` with integer values (e.g. `1.00`, `2.00`).
3. **Square Meter (`m2`):** Used for large surface area textiles (carpets, thick curtains). Calculated as $\text{Length (m)} \times \text{Width (m)}$. Minimum billable area threshold applies (e.g., minimum 2.00 $m^2$).

### 4.2 Minimum Billable Weight Policy (Aturan Minimal Cas Kiloan)
To cover the fixed utility and labor costs of running washing machines, single-store laundry economics mandate a minimum billable weight threshold:
- **Default Store Threshold:** 3.00 kg (configurable per service tier in master catalog).
- **Core Formula:**
  $$\text{billed\_quantity} = \max(\text{actual\_quantity}, \text{service.min\_weight})$$

### 4.3 Detailed Calculation Scenarios:
- **Scenario A (Under Minimum Weight):**
  - Customer brings a light bundle: `actual_quantity = 1.45 kg`.
  - Master service: "Cuci Komplit Reguler" at Rp 7,000/kg, `min_weight = 3.00 kg`.
  - System calculation:
    - `actual_quantity = 1.45`
    - `billed_quantity = 3.00`
    - `item_subtotal = 3.00 * 7,000 = Rp 21,000`
  - *Customer Invoice Printout:* Clearly presents both actual weight (1.45 kg) and minimum billable weight note ("Min. Cas 3.00 kg") to prevent customer disputes.
- **Scenario B (Over Minimum Weight):**
  - Customer brings a family bundle: `actual_quantity = 5.60 kg`.
  - Master service: "Cuci Komplit Reguler" at Rp 7,000/kg, `min_weight = 3.00 kg`.
  - System calculation:
    - `actual_quantity = 5.60`
    - `billed_quantity = 5.60`
    - `item_subtotal = 5.60 * 7,000 = Rp 39,200`

---

## 5. Counter Release Guard & Customer Credit (Piutang) Policy

### 5.1 The Counter Release Guard Rule
To eliminate uncollected revenue leakage, the POS interface enforces an automated architectural guard:

> **Counter Release Guard:**  
> An order CANNOT transition to `completed` if `payment_status != 'paid'`, **UNLESS** an authorized user explicitly flags `is_debt_approved = true` with customer identity verification and a promised payment due date.

### 5.2 Attempted Release Flow:
```
[ Cashier clicks "Serahkan Cucian" ]
                 │
                 ▼
       Is payment_status == 'paid'?
        /                       \
      YES                        NO
      /                            \
[ Handover Approved ]     Does Cashier have permission 'allow_credit_release'?
[ Status: completed ]               /                                \
                                  YES                                 NO
                                  /                                    \
               [ Cashier toggles 'Izinkan Piutang' ]       [ Block Transition ]
               [ System records debtor & due date   ]       [ Prompt: Tagihan Wajib Lunas ]
               [ Status: completed, payment: unpaid ]
```

### 5.3 Customer Receivables (Piutang) Rules:
1. **Credit Ceiling:** Standard retail customers have a maximum credit ceiling of Rp 150,000. If an unpaid order exceeds this ceiling or if the customer has existing overdue debts, credit release is blocked until the Store Owner enters their PIN.
2. **Promised Due Date (`due_date`):** Mandatory field when approving debt. Defaults to 7 calendar days from order handover.
3. **Aging Brackets & WhatsApp Dunning:**
   - **Current (0 to 7 days):** Polite reminder template sent 1 day before due date.
   - **Overdue (8 to 14 days):** Follow-up reminder template with direct QRIS payment link.
   - **Delinquent (> 14 days):** Customer account flagged in POS; cashier alerted to block new order intakes until prior debt is settled.

---

## 6. Production Rewash Loop Quality Specifications

Quality control occurs at Stage 6 (Steam Ironing). The following rules govern re-wash cycles:

```
[ Ironing Table Inspection ]
            │
  Stain or Odor Detected?
   /                   \
 NO                    YES
 /                       \
[ Stage 7: Packing ]   Check current rewash_count:
                         ├── If rewash_count < 2:
                         │     • Log Reason Code & Operator ID
                         │     • Increment rewash_count by 1
                         │     • Route order back to Stage 4: [washing]
                         │
                         └── If rewash_count >= 2:
                               • Halt automatic re-wash loop
                               • Trigger Manager Inspection Alert
                               • Contact customer regarding persistent stain
```

### 6.1 Permitted Reason Codes (`rewash_reason_code`):
1. `stain_detected`: Residual grease, food stain, ink, or collar grime discovered during pressing.
2. `odor_remaining`: Damp/sour smell (bau apek) caused by incomplete drying or insufficient detergent.
3. `wrinkle_quality_fail`: Severe fabric wrinkling requiring re-soaking with fabric softener.
4. `lint_contamination`: Excessive fiber/lint transfer from dark towels to light garments.

### 6.2 Financial & Operational Invariants:
- **Zero Customer Surcharge:** Re-washing is an internal operational correction. Under no circumstances may an additional charge be added to the customer's invoice.
- **Rewash Cap:** Maximum 2 re-wash cycles per order. If a stubborn stain resists 2 chemical wash cycles, further washing risks fabric fiber destruction. The order is escalated to the Store Owner, who contacts the customer to explain the fabric limitation.
- **Cost Allocation:** Water, detergent, and gas consumed during re-wash cycles are captured under internal shrinkage/loss (`Biaya Kualitas Operasional`) for monthly productivity analysis.

---

## 7. Physical Staging & Rack Allocation System

To ensure that the front counter cashier can locate and hand over completed laundry in under 10 seconds during rush hours, a standardized physical coordinate system is enforced.

### 7.1 Coordinate Syntax: `Rack-Shelf-Bin`
- Format: `R{Rack_Number}-{Shelf_Letter}[-{Bin_Number}]`
- Examples:
  - `R-01-A`: Rack 1, Top Shelf A (Large items, Bedcovers).
  - `R-02-B`: Rack 2, Middle Shelf B (Standard Kiloan bundles).
  - `R-03-C`: Rack 3, Lower Shelf C (Shoes and accessories).
  - `HANG-01`: Hanging Garment Rail 1 (Suits, Gowns, Ironed Shirts).

### 7.2 Staging Business Invariants:
1. **Mandatory Input:** Transitioning an order from `ironing` to `ready_for_pickup` **requires** an active rack coordinate input. The system rejects transitions with empty rack values.
2. **Search Integration:** When a cashier looks up an order in the POS counter search (by customer name, phone, or QR scan), the rack location is highlighted in large, high-contrast bold typography (e.g., **RAK: R-02-B**).
3. **Notification Inclusion:** The rack location is automatically injected into the automated customer WhatsApp message ("Cucian Anda tersimpan rapi di Rak R-02-B"), enabling instant counter retrieval upon arrival.
4. **De-allocation on Handover:** When the order transitions to `completed`, the rack coordinate is cleared and released for new staging bundles.

---

## 8. Pricing Tiers, Surcharges & Immutable Price Snapshots

### 8.1 Turnaround Speed Tiers

| Tier Name | Turnaround Time | Price Multiplier / Surcharge | Cut-Off Rules |
|:---|:---:|:---|:---|
| **Regular (Reguler)** | 48 – 72 Hours | Base Catalog Rate | Accepted anytime during store operating hours. |
| **Kilat (24 Jam)** | 24 Hours | + Rp 3,000 / kg (or +40% base) | Accepted until 16:00 WIB for next-day 16:00 pickup. |
| **Express (6-8 Jam)** | 6 – 8 Hours | + Rp 6,000 / kg (or +80% base) | Accepted until 12:00 WIB for same-day evening pickup. |
| **Super Express / Flash** | 2 – 3 Hours | + Rp 10,000 / kg (or +100% base)| Requires operator verification of vacant washer/dryer. |

### 8.2 Add-On Surcharges:
1. **Fragrance Variants:**
   - Standard Fragrance (Lavender, Sakura, Ocean Fresh, Snappy, Non-Parfum): Included in base rate (+ Rp 0).
   - Premium Imported Fragrance (e.g. Downy Passion, Molto Gold): + Rp 2,000 / kg.
2. **Logistics (Pickup & Delivery):**
   - Zone 1 (0 – 2 km): Rp 5,000 flat.
   - Zone 2 (2 – 5 km): Rp 10,000 flat.
   - Zone 3 (> 5 km): Rp 15,000 flat.

### 8.3 Immutable Line Item Price Snapshots
In retail laundry, owners periodically adjust prices (e.g., inflating Cuci Komplit from Rp 7,000 to Rp 8,000 due to detergent price hikes).

> **Architectural Invariant:**  
> Master catalog modifications must **NEVER** alter the pricing of existing, active, or historical orders. Every transaction item permanently freezes its financial attributes at the instant of order creation.

#### Frozen Snapshot Schema (`order_items`):
- `item_name_snapshot`: `VARCHAR(150)` — Freezes service name (e.g. "Cuci Komplit Reguler").
- `pricing_unit_snapshot`: `ENUM('kg', 'pcs', 'm2')` — Freezes billing unit.
- `unit_price_snapshot`: `DECIMAL(12, 2)` — Freezes the exact catalog unit rate applied.
- `actual_quantity`: `DECIMAL(8, 2)` — Freezes actual physical scale weight.
- `billed_quantity`: `DECIMAL(8, 2)` — Freezes billable weight after minimum threshold.
- `discount_amount_snapshot`: `DECIMAL(12, 2)` — Freezes item-level promo discount.
- `subtotal_snapshot`: `DECIMAL(12, 2)` — Freezes calculated line total: $(\text{billed\_quantity} \times \text{unit\_price}) - \text{discount}$.

---

## 9. Cashier Shift Management & Cash Drawer Reconciliation

To completely eliminate register skimming, unaccounted emergency purchases, and employee disputes, LMS-AndikLaundry implements the **Blind Count Shift Protocol**.

### 9.1 Shift Lifecycle States

```
[ Shift Closed ] ──(Open Shift: Enter Opening Float)──> [ Shift Open / Active ]
                                                                │
     ┌──────────────────────────────────────────────────────────┴──────────────────────────────┐
     │ Operations: POS Cash Intake, QRIS Sales, Piutang Settlements, Mid-Shift Drops/Expenses   │
     └──────────────────────────────────────────────────────────┬──────────────────────────────┘
                                                                │
[ Shift Closed & Audited ] <──(Blind Cash Count & Audit)─── [ Shift Closing ]
```

### 9.2 Cash Drawer Movements (`shift_cash_movements`)
Every movement of physical cash in or out of the register drawer during an active shift is recorded in an immutable ledger:
1. `opening_float` (Modal Awal): Starting cash change in register (e.g. Rp 200,000 in small bills).
2. `cash_sale` (Penjualan Tunai): Cash received from order intake or pickup settlement.
3. `cash_refund` (Pengembalian Tunai): Cash paid out for canceled orders or overpayments.
4. `cash_drop` (Setoran Kas ke Brankas): Excess cash transferred to store safe during shift.
5. `expense` (Kas Kecil Kasir / Petty Cash Out): Small emergency operational disbursements (e.g. buying 3kg LPG cylinder for Rp 22,000). Requires category, description, and receipt photo.
6. `cash_in` (Penambahan Modal Kasir): Emergency change cash added by Store Owner.

### 9.3 Cash Drawer Reconciliation Formula
Physical cash reconciliation computes the exact expected cash balance:

$$\text{Expected Cash} = \text{Opening Float} + \text{Cash Sales} - \text{Cash Refunds} + \text{Cash In} - \text{Cash Drops} - \text{Cash Expenses}$$

*(Note: Non-cash payments—such as QRIS, EDC, and Bank Transfers—are verified in financial audit reports but are strictly **excluded** from the physical cash drawer reconciliation formula!)*

### 9.4 The Blind Count Audit Protocol (Audit Hitung Buta)
1. **Concealment:** When the cashier initiates "Tutup Shift", the POS interface **deliberately hides** the computed `Expected Cash` balance.
2. **Physical Denomination Input:** The cashier physically counts the drawer contents and inputs counts per Indonesian currency denomination:

| Denomination | Form Field | Multiplier |
|:---|:---:|:---|
| Rp 100,000 | `bill_100k` | $\times 100,000$ |
| Rp 50,000 | `bill_50k` | $\times 50,000$ |
| Rp 20,000 | `bill_20k` | $\times 20,000$ |
| Rp 10,000 | `bill_10k` | $\times 10,000$ |
| Rp 5,000 | `bill_5k` | $\times 5,000$ |
| Rp 2,000 | `bill_2k` | $\times 2,000$ |
| Rp 1,000 | `bill_1k` | $\times 1,000$ |
| Coins (Rp 1000, 500, 200, 100) | `coins_total`| Exact value |

3. **Variance Calculation:**
   $$\text{Variance} = \text{Actual Cash Counted} - \text{Expected Cash}$$
   - If $\text{Variance} = 0$: Flagged as `closed_balanced` (Kas Imbang).
   - If $\text{Variance} > 0$: Flagged as `closed_with_surplus` (Kas Lebih). Surplus is credited to `Pendapatan Kas Lebih (Other Operating Income)`.
   - If $\text{Variance} < 0$: Flagged as `closed_with_shortage` (Kas Kurang). Discrepancy logged for payroll deduction or owner forgiveness.
4. **Shift Reports:**
   - **X-Report:** Mid-shift informational audit slip printed without closing the shift.
   - **Z-Report:** Official end-of-shift closing audit slip freezing shift records permanently.

---

## 10. Operational Summary & Verification Checklist

To verify that any implemented feature complies with these business rules, developers and QA auditors must check:

- [ ] Does every kiloan intake enforce minimum billable weight via $\max(\text{actual}, \text{min})$?
- [ ] Are `production_status` and `payment_status` fully independent finite state machines?
- [ ] Is `payment_status` derived exclusively from the immutable payment records ledger?
- [ ] Does the Counter Release Guard prevent completing an unpaid order without authorized debt flag?
- [ ] Does the re-wash loop enforce a strict maximum cap of 2 cycles with mandatory reason logging?
- [ ] Does every packed order assign a valid rack coordinate before triggering WhatsApp dispatch?
- [ ] Are line-item prices, discounts, and names permanently snapshotted in `order_items`?
- [ ] Does shift closing enforce the blind count protocol without displaying the expected cash balance?
- [ ] Are non-cash payments (QRIS/Transfer) excluded from the physical cash drawer reconciliation formula?
- [ ] Are all monetary calculations stored and manipulated using `DECIMAL(12, 2)` precision?
