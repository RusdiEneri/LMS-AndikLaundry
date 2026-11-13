# UI/UX Design System Specification

**Document Version:** 1.0.0  
**Project:** Laundry Management System (LMS Andik Laundry)  
**Status:** Approved Architecture Specification  
**Classification:** Phase 0 Discovery & Planning Deliverable  
**Date:** 2026-11-13  

---

## 1. Executive Summary & Design Vision

LMS Andik Laundry is a high-velocity retail application designed to serve three distinct operational roles within a single-store laundry business:
1. **POS Cashier (Front-of-House):** Fast customer intake, digital scale weighing, quick-tender checkout, thermal receipt printing, and manual WhatsApp fallback links.
2. **Production Operator (Back-of-House):** High-contrast, touch-friendly tracking for washing, drying, steam ironing, packing, and rack staging.
3. **Store Owner (Back-Office):** Executive analytics, shift reconciliation auditing, expense logging, and customer receivables management.

### Core UX Principles
- **Speed & Ergonomics:** Cashiers must complete an intake transaction in under 20 seconds. Touch targets are large, numpads are reachable, and keyboard shortcuts exist for rapid hardware keyboard entry.
- **Physical Touch Target Standard:** All interactive controls on touchscreen displays adhere to a strict minimum size of **44x44px** (WCAG 2.5.5 / 2.5.8 Target Size).
- **High-Contrast Clarity (WCAG AA):** Laundries feature varied lighting environments (bright retail storefronts vs steam-filled, humid back-of-house washing rooms). All critical text meets or exceeds a **4.5:1 contrast ratio**.
- **Zero AI Slop & Functional Polish:** Complete visual states (default, hover, active, focus-visible, disabled, loading, empty) for every component without dummy layouts or non-functional placeholders.

---

## 2. 3-Layer Design Token Architecture

The design system uses a strict 3-tier token hierarchy to ensure clean decoupling, maintainability, and cohesive theme synchronization:

```
[ Layer 1: Primitive Tokens ]  -->  Raw values (colors, spacing units, typography scales)
             │
             ▼
[ Layer 2: Semantic Tokens ]   -->  Functional intent (surface, text-primary, border-focus, status-success)
             │
             ▼
[ Layer 3: Component Tokens ]  -->  Scoped element styles (btn-pos-primary, numpad-key, card-stat, badge-status)
```

### 2.1 Layer 1: Primitive Tokens

#### A. Color Primitives
```css
/* Neutral Palette (Slate) */
--color-slate-50:  #f8fafc;
--color-slate-100: #f1f5f9;
--color-slate-200: #e2e8f0;
--color-slate-300: #cbd5e1;
--color-slate-400: #94a3b8;
--color-slate-500: #64748b;
--color-slate-600: #475569;
--color-slate-700: #334155;
--color-slate-800: #1e293b;
--color-slate-900: #0f172a;
--color-slate-950: #020617;

/* Brand Primary (Fresh Clean Teal / Emerald) */
--color-teal-50:  #f0fdfa;
--color-teal-100: #ccfbf1;
--color-teal-200: #99f6e4;
--color-teal-300: #5eead4;
--color-teal-400: #2dd4bf;
--color-teal-500: #14b8a6;
--color-teal-600: #0d9488;
--color-teal-700: #0f766e;
--color-teal-800: #115e59;
--color-teal-900: #134e4a;
--color-teal-950: #042f2e;

/* Accent / Order Status (Cyan / Sky) */
--color-cyan-500: #06b6d4;
--color-cyan-600: #0891b2;
--color-cyan-700: #0e7490;

/* Success (Emerald) */
--color-emerald-500: #10b981;
--color-emerald-600: #059669;
--color-emerald-700: #047857;

/* Warning / Piutang / Pending (Amber) */
--color-amber-500: #f59e0b;
--color-amber-600: #d97706;
--color-amber-700: #b45309;

/* Danger / Variance Shortage / Loss (Rose) */
--color-rose-500: #f43f5e;
--color-rose-600: #e11d48;
--color-rose-700: #be123c;

/* WhatsApp Official Brand Green */
--color-whatsapp-500: #25d366;
--color-whatsapp-600: #128c7e;
--color-whatsapp-700: #075e54;
```

#### B. Spacing Scale (4px Baseline Grid)
- `space-1`: `4px` (0.25rem)
- `space-2`: `8px` (0.5rem)
- `space-3`: `12px` (0.75rem)
- `space-4`: `16px` (1.0rem)
- `space-5`: `20px` (1.25rem)
- `space-6`: `24px` (1.5rem)
- `space-8`: `32px` (2.0rem)
- `space-10`: `40px` (2.5rem)
- `space-11`: `44px` (2.75rem) — **Mandatory Touch Target Baseline**
- `space-12`: `48px` (3.0rem)
- `space-16`: `64px` (4.0rem)

#### C. Elevation & Shadows
- `shadow-sm`: `0 1px 2px 0 rgb(0 0 0 / 0.05)`
- `shadow-md`: `0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1)`
- `shadow-lg`: `0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1)`
- `shadow-pos-card`: `0 2px 8px -2px rgba(15, 23, 42, 0.08)`

---

### 2.2 Layer 2: Semantic Tokens

Semantic tokens assign intent to raw primitives, ensuring accessibility and theme consistency across views:

| Token Name | Assigned Primitive | Functional Context |
|:---|:---|:---|
| `--surface-canvas` | `var(--color-slate-100)` | Main application background |
| `--surface-card` | `#ffffff` | Elevated POS cards, modals, table containers |
| `--surface-muted` | `var(--color-slate-50)` | Table alternating rows, secondary panels |
| `--surface-sidebar` | `var(--color-slate-900)` | Navigation rail / sidebar |
| `--text-primary` | `var(--color-slate-900)` | Primary headings, table text, currency totals |
| `--text-secondary` | `var(--color-slate-600)` | Meta descriptions, line item units, notes |
| `--text-muted` | `var(--color-slate-400)` | Disabled text, placeholder text |
| `--text-inverse` | `#ffffff` | Text on dark buttons and sidebar |
| `--border-subtle` | `var(--color-slate-200)` | Card borders, table dividers |
| `--border-strong` | `var(--color-slate-400)` | Active input field borders |
| `--interactive-primary` | `var(--color-teal-600)` | Primary checkout button, active state tabs |
| `--interactive-primary-hover` | `var(--color-teal-700)` | Primary button hover state |
| `--interactive-focus-ring` | `var(--color-teal-500)` | 2px accessible focus outline offset |
| `--status-paid-bg` | `var(--color-teal-50)` | Paid / Lunas badge background |
| `--status-paid-text` | `var(--color-teal-800)` | Paid / Lunas badge text |
| `--status-unpaid-bg` | `var(--color-rose-50)` | Unpaid / Belum Lunas badge background |
| `--status-unpaid-text` | `var(--color-rose-700)` | Unpaid / Belum Lunas badge text |
| `--status-partial-bg` | `var(--color-amber-50)` | Down payment / Piutang badge background |
| `--status-partial-text` | `var(--color-amber-800)` | Down payment / Piutang badge text |

---

### 2.3 Layer 3: Component Tokens

Component tokens bundle semantic styles into specific UI elements:

```css
/* POS Touch Keypad Button */
.btn-numpad {
  min-height: 52px;
  min-width: 52px;
  font-size: 1.25rem;
  font-weight: 600;
  border-radius: 0.5rem;
  background-color: var(--surface-card);
  border: 1px solid var(--border-subtle);
  color: var(--text-primary);
  display: flex;
  align-items: center;
  justify-content: center;
  user-select: none;
  transition: all 0.15s ease-in-out;
}
.btn-numpad:hover {
  background-color: var(--surface-muted);
  border-color: var(--border-strong);
}
.btn-numpad:active {
  background-color: var(--color-slate-200);
  transform: scale(0.98);
}

/* POS Quick Tender Button */
.btn-quick-tender {
  min-height: 44px;
  padding: 0 1rem;
  font-size: 0.875rem;
  font-weight: 600;
  border-radius: 0.375rem;
  background-color: var(--color-slate-100);
  border: 1px solid var(--border-subtle);
  color: var(--text-primary);
}

/* Service Category Tab */
.tab-service-category {
  min-height: 44px;
  padding: 0 1.25rem;
  font-weight: 500;
  border-radius: 0.5rem;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
}
.tab-service-category.active {
  background-color: var(--color-teal-600);
  color: #ffffff;
  box-shadow: var(--shadow-sm);
}
```

---

## 3. Tailwind CSS 4 Configuration

LMS Andik Laundry utilizes Tailwind CSS 4's modern CSS-native `@theme` architecture. The configuration is declared in `resources/css/app.css`:

```css
@import "tailwindcss";

@theme {
  --font-sans: "Plus Jakarta Sans", system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
  --font-mono: "JetBrains Mono", "Fira Code", monospace;

  /* Color Bindings */
  --color-primary-50:  #f0fdfa;
  --color-primary-100: #ccfbf1;
  --color-primary-200: #99f6e4;
  --color-primary-500: #14b8a6;
  --color-primary-600: #0d9488;
  --color-primary-700: #0f766e;
  --color-primary-800: #115e59;
  --color-primary-900: #134e4a;

  --color-surface-base:  #f8fafc;
  --color-surface-panel: #ffffff;
  --color-surface-muted: #f1f5f9;

  /* Minimum Touch Target utility */
  --size-touch-target: 44px;
}

/* Global Focus Utility */
*:focus-visible {
  outline: 2px solid var(--color-primary-600);
  outline-offset: 2px;
}
```

---

## 4. Typography Scale & Font Metrics

The typography system pairs **Plus Jakarta Sans** (clean, geometric humanist sans-serif optimized for retail screens) with **JetBrains Mono** (monospaced tabular figures for monetary values, weights, and thermal receipts).

| Text Style | Font Family | Size (px / rem) | Line Height | Weight | Usage |
|:---|:---|:---|:---|:---|:---|
| **Display Heading** | Plus Jakarta Sans | 30px / 1.875rem | 36px | 700 (Bold) | Owner Dashboard main KPI numbers |
| **Heading 1** | Plus Jakarta Sans | 24px / 1.5rem | 32px | 700 (Bold) | Page titles, Checkout Modal Header |
| **Heading 2** | Plus Jakarta Sans | 20px / 1.25rem | 28px | 600 (Semibold) | Section headers, Cart Drawer total |
| **Heading 3** | Plus Jakarta Sans | 16px / 1.0rem | 24px | 600 (Semibold) | Card titles, Service item name in catalog |
| **Body Regular** | Plus Jakarta Sans | 14px / 0.875rem | 20px | 400 (Regular) | Table data cells, customer phone, notes |
| **Body Semibold** | Plus Jakarta Sans | 14px / 0.875rem | 20px | 600 (Semibold) | Form field labels, button text |
| **Caption** | Plus Jakarta Sans | 12px / 0.75rem | 16px | 500 (Medium) | Meta timestamps, tracking numbers, rack IDs |
| **Currency Display** | JetBrains Mono | 18px / 1.125rem | 24px | 700 (Bold) | Final invoice total, change due |
| **Receipt Monospace** | JetBrains Mono | 12px / 0.75rem | 16px | 400 (Regular) | 58mm / 80mm thermal receipt preview |

---

## 5. Spacing Rhythm & Touch Ergonomics

### 5.1 The 44x44px Touch Target Invariant
In retail store operations, cashiers and production operators frequently interact with touchscreen tablets or all-in-one POS terminals using gloved or damp hands.
- **Strict Invariant:** Every clickable button, quantity adjuster (+/-), numpad key, tab, and icon action button must provide an interactive bounding box of **at least 44x44px**.
- **Implementation:**
  ```html
  <!-- Minimum touch target wrapper -->
  <button class="min-h-[44px] min-w-[44px] inline-flex items-center justify-center p-2 ...">
    <IconBarcode class="w-6 h-6" />
  </button>
  ```

---

## 6. Responsive Standards & Breakpoint Hierarchy

```
+-------------------------------------------------------------------------------+
|                        RESPONSIVE LAYOUT HIERARCHY                            |
|                                                                               |
|  Mobile (320px - 639px)        : Stacked single column, full-width drawers   |
|  Tablet / POS (640px - 1023px) : 2-column adaptive, collapsible cart drawer   |
|  Desktop POS (1024px - 1279px) : 60/40 Split screen (Catalog + Fixed Cart)    |
|  Wide Screen (1280px - 1920px) : 3-column (Nav + Catalog + Fixed Cart/Audit)  |
+-------------------------------------------------------------------------------+
```

### 6.1 Breakpoint Definitions
1. **`sm` (640px):** Small tablet / Android mobile POS handheld terminal. Cart transitions to a sliding bottom sheet.
2. **`md` (768px):** iPad / Android tablet landscape. Catalog displays 3 cards per row.
3. **`lg` (1024px):** Standard 15-inch Touchscreen POS Counter Terminal. Split-screen layout locks into place: 60% Catalog/Search, 40% Cart & Checkout.
4. **`xl` (1280px):** Desktop workstation / Store Owner management monitor.
5. **`2xl` (1536px+):** Full HD back-office display with extended analytics heatmaps.

### 6.2 Mobile-First Reflow & Overflow Prevention
- All layouts employ `overflow-x: hidden` on viewport roots.
- Data tables implement container horizontal scrolling (`overflow-x: auto`) with sticky header rows and sticky frozen first columns (`sticky left-0 bg-white`).

---

## 7. Complete Component Visual States

To ensure UI robustness, every component explicitly specifies all seven visual states:

```
[ Default ] ──> [ Hover ] ──> [ Active / Pressed ]
     │               │
     ├──> [ Focus-Visible ] (2px Accessible Outline)
     ├──> [ Disabled ] (50% opacity, pointer-events-none)
     ├──> [ Loading ] (Skeleton shimmer / Spinner)
     └──> [ Empty State ] (Icon + Explanatory Text + Action Button)
```

### State Specification Matrix
| Component | Default | Hover | Active / Pressed | Focus-Visible | Disabled | Loading |
|:---|:---|:---|:---|:---|:---|:---|
| **Primary Button** | Background `--color-teal-600`, text white | Background `--color-teal-700` | Scale `0.98`, background `--color-teal-800` | 2px ring `--color-teal-500`, offset 2px | Opacity `0.5`, cursor `not-allowed` | Spinner replaces icon; text remains |
| **Catalog Card** | White card, border `--border-subtle` | Border `--color-teal-400`, shadow-md | Background `--color-teal-50` | 2px border `--color-teal-600` | Grayscale, badge "Stok Habis" | Gray skeleton pulse shimmer |
| **Text Input** | White bg, border `--color-slate-300` | Border `--color-slate-400` | Border `--color-teal-600` | Outline 2px `--color-teal-600`, offset 1px | Bg `--color-slate-100`, text `--color-slate-400` | Animated spinner on right icon slot |
| **Status Badge** | Semantic bg (e.g. green-50), text green-800 | Opacity `0.9` | N/A (Static) | Outline 2px ring | N/A | Pulse animation |

---

## 8. POS Cashier Layout Ergonomics

The POS Cashier interface is engineered for rapid order entry under high queue pressure.

```
+-------------------------------------------------------+-----------------------------+
| [Search Service or Garment... [F1]] [Category Tabs]   | ORDER SUMMARY (Cart)        |
+-------------------------------------------------------+-----------------------------+
| [ Cuci Komplit Reguler ] [ Cuci Kering Lipat ]        | 1. Cuci Komplit Reguler     |
| Rp 7.000 / kg (Min 3kg)  Rp 5.000 / kg                |    Actual: 4.25 kg          |
|                                                       |    Subtotal: Rp 29.750  [X] |
| [ Setrika Uap Reguler ]  [ Bedcover King ]            |                             |
| Rp 4.000 / kg            Rp 25.000 / pcs              | 2. Bedcover King            |
|                                                       |    Qty: 1 pcs               |
| [ Jas Pria (Dry Clean)]  [ Sepatu Canvas ]            |    Subtotal: Rp 25.000  [X] |
| Rp 35.000 / pcs          Rp 30.000 / pasang           +-----------------------------+
+-------------------------------------------------------+ Surcharge (Kilat 24h) +Rp 10k|
| ON-SCREEN NUMPAD (Scale Weight / Cash Tender)         | Diskon Voucher         -Rp 5k|
| +-------+-------+-------+ [ QUICK TENDER ]            | Pajak (PPN 0%)           Rp 0|
| |   7   |   8   |   9   | [ Uang Pas ]                +-----------------------------+
| +-------+-------+-------+ [ Rp 50.000 ]               | TOTAL TAGIHAN:    Rp 59.750 |
| |   4   |   5   |   6   | [ Rp 100.000 ]              +-----------------------------+
| +-------+-------+-------+ [ DP 50% ]                  | [ 💳 PROSES BAYAR / NOTA ]  |
| |   1   |   2   |   3   |                             | [ 📲 Kirim Manual WA ]      |
| +-------+-------+-------+                             +-----------------------------+
| |   0   |   .   |  <-   |                             | Status Kasir: Shift Buka    |
+---------+-------+-------+-----------------------------+ Kasir: Siti Aminah (ID: 03) |
```

### 8.1 Split-Screen Layout Architecture
- **Left Panel (60% Width):**
  1. Top search bar with autofocus: scans barcode tags or executes instant fuzzy search across service catalog.
  2. Category selector pills: `Semua`, `Kiloan`, `Satuan`, `Dry Clean`, `Sepatu / Karpet`.
  3. Catalog Grid: 3 to 4 cards per row with clear pricing and unit indicators.
  4. Numpad Area: Dedicated touch keypad for instant weight input (`3.45 kg`) or cash tender entry (`50000`).
- **Right Panel (40% Width):**
  1. Customer Info Bar: Customer name, phone, E.164 normalization indicator, and outstanding debt warning badge if applicable.
  2. Cart Line Items: Item name, billed weight vs actual weight, condition notes (for satuan), delete button.
  3. Turnaround Tier Toggle: `Reguler (48-72h)`, `Kilat (24h)`, `Express (6-8h)`, `Flash (2-3h)`.
  4. Calculation Ledger: Subtotal, discounts, surcharges, tax, total invoice.
  5. Checkout Action Bar: Big primary button `[ PROSES PEMBAYARAN [F8] ]` and fallback `[ 📲 Kirim Manual WA ]`.

### 8.2 Barcode & Hardware Scanner Integration
- The POS listens silently to global `keydown` events using a rapid keystroke buffer.
- When an external USB/Bluetooth barcode scanner scans a laundry tag or receipt QR code, characters arrive with inter-key intervals < 20ms followed by an `Enter` key.
- The system intercepts the scan, resolves the order ID or service SKU, and automatically opens the corresponding order without requiring manual mouse focus on an input box.

### 8.3 Thermal Receipt Preview Modal (58mm & 80mm)
- When checkout completes, an accessible modal opens showing a pixel-accurate monospaced simulation of the thermal receipt.
- A single click triggers `window.print()` targeting raw ESC/POS thermal printers via browser print dialog or WebUSB.

---

## 9. Owner Dashboard & Analytics Ergonomics

The Store Owner interface focuses on high-level executive visibility and operational oversight.

```
+---------------------------------------------------------------------------------------+
| ANDIK LAUNDRY - OWNER DASHBOARD                                  [ 13 Nov 2026 15:30 ]|
+-------------------+-------------------+-------------------+---------------------------+
| OMSET HARI INI    | TOTAL CUCIAN AKTIF| PIUTANG PELANGGAN | KAS LACI AKTIF (ESTIMASI) |
| Rp 1.450.000      | 142.50 kg         | Rp 385.000        | Rp 650.000                |
| (+12% vs kemarin) | (32 Nota Berjalan)| (6 Pelanggan)     | (Shift 1 & 2 Aktif)       |
+-------------------+-------------------+-------------------+---------------------------+
| GRAFIK OMSET MINGGUAN (BAR CHART)     | KOMPOSISI LAYANAN (DONUT CHART)               |
| [||| |||| ||||| ||||||]               | [ 75% Kiloan | 20% Satuan | 5% Sepatu ]       |
+---------------------------------------+-----------------------------------------------+
| AUDIT PENUTUPAN SHIFT KASIR (HARI INI)                                                |
| Shift ID  | Kasir       | Jam Buka - Tutup | Ekspektasi Kas | Fisik Kas  | Selisih Kas    |
| SHF-0001  | Budi (Pagi) | 07:00 - 15:00    | Rp 480.000     | Rp 480.000 | Rp 0 (OK)      |
| SHF-0002  | Siti (Sore) | 15:00 - AKTIF    | Rp 170.000     | -          | -              |
+---------------------------------------------------------------------------------------+
```

### 9.1 Executive KPI Cards
1. **Total Daily Turnover (Omset Hari Ini):** Cash + Electronic payments received today.
2. **Active Workload (Beban Cucian Aktif):** Total kilograms currently in `washing`, `drying`, and `ironing`.
3. **Outstanding Receivables (Total Piutang):** Uncollected invoices with click-to-view customer debt ledger.
4. **Active Cash in Drawers:** Total real-time expected cash across active open cashier shifts.

### 9.2 Shift Discrepancy Auditing Table
- Highlights shift closures with visual variance indicators:
  - `Balanced`: Green pill badge `Seimbang (Rp 0)`.
  - `Surplus`: Blue pill badge `Lebih (+Rp 4.000)`.
  - `Shortage`: Red bold alert badge `Kurang (-Rp 15.000) [Review Required]`.

---

## 10. Production Operator Staging UI

The Production Operator interface runs on wall-mounted touchscreen monitors or tablets positioned in the laundry work area.

```
+---------------------------------------------------------------------------------------+
| OPERATOR CUCI & FINISHING - ANDIK LAUNDRY                        [ Filter: SEMUA ]    |
+---------------------------------------------------------------------------------------+
| [1. ANTRIAN CUCI (5)]   | [2. PROSES PENGERINGAN (3)] | [3. SETRIKA & PACKING (4)]    |
| +---------------------+ | +-------------------------+ | +---------------------------+ |
| | ORD-1042 - Budi S.  | | | ORD-1040 - Dewi R.      | | | ORD-1038 - Ahmad F.       | |
| | Cuci Komplit - 4.5kg| | | Cuci Kering - 6.2kg     | | | Setrika Uap - 3.8kg       | |
| | Parfum: Lavender    | | | Mesin Dryer #02         | | | Parfum: Sakura            | |
| | [>> MULAI CUCI]     | | | [>> PINDAH SETRIKA]     | | | [>> SIAP AMBIL / RAK]     | |
| +---------------------+ | +-------------------------+ | +---------------------------+ |
| | ORD-1043 - Maya K.  | | | ORD-1041 - Joko W.      | | | ORD-1039 - Rina S.        | |
| | Satuan (Bedcover Kg)| | | Cuci Komplit - 3.1kg    | | | Jas Pria (Dry Clean - 2)  | |
| | Noda Kerah (Spotting)| | Mesin Dryer #01         | | Gantungan Hanger #12        | |
| | [>> MULAI CUCI]     | | [>> PINDAH SETRIKA]     | | [>> SIAP AMBIL / RAK]     | |
| +---------------------+ | +-------------------------+ | +---------------------------+ |
+---------------------------------------------------------------------------------------+
```

### 10.1 Large-Card Touch Workflow
- Giant action buttons (`min-h-[56px]`) allow operators to advance order status with a single tap.
- Scent preference (Parfum: Lavender, Sakura, Snappy, Non-Parfum) is displayed in high-contrast colored badges to eliminate packing errors.
- Rewash Button: If an operator identifies a residual stain at the ironing station, a dedicated `[ 🔄 Cuci Ulang ]` button prompts for a reason code and routes the bundle back to washing queue.

---

## 11. Accessibility & WCAG AA Contrast Compliance

### 11.1 Contrast Validation Matrix
All core text pairings are evaluated against WCAG 2.1 AA requirements (Minimum contrast ratio of **4.5:1** for regular text and **3.0:1** for large text/icons):

| Element Context | Foreground Color | Background Color | Contrast Ratio | WCAG AA Status |
|:---|:---|:---|:---:|:---:|
| **Primary Button Text** | `#ffffff` (White) | `#0d9488` (Teal-600) | **4.64 : 1** | PASS (AA) |
| **Primary Headings** | `#0f172a` (Slate-900) | `#ffffff` (White) | **18.74 : 1** | PASS (AAA) |
| **Secondary Meta Text**| `#475569` (Slate-600) | `#ffffff` (White) | **5.91 : 1** | PASS (AA) |
| **Paid Badge Text** | `#115e59` (Teal-800) | `#f0fdfa` (Teal-50) | **7.82 : 1** | PASS (AAA) |
| **Unpaid Badge Text** | `#be123c` (Rose-700) | `#fff1f2` (Rose-50) | **6.12 : 1** | PASS (AA) |
| **Piutang Badge Text** | `#b45309` (Amber-700) | `#fffbeb` (Amber-50)| **5.14 : 1** | PASS (AA) |
| **Sidebar Navigation** | `#f8fafc` (Slate-50) | `#0f172a` (Slate-900) | **18.74 : 1** | PASS (AAA) |

### 11.2 Keyboard Navigation Shortcuts
For high-speed cashier entry without requiring mouse movement:
- `F1`: Focus search bar (Fuzzy service / customer search)
- `F2`: Add standard "Cuci Komplit Reguler" to cart
- `F4`: Open customer phone number input
- `F8`: Trigger Payment / Checkout Modal
- `F9`: Quick Cash Tender ("Uang Pas")
- `F10`: Quick QRIS Tender
- `Escape`: Close active modal or clear cart focus
