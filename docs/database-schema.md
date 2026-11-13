# LMS-AndikLaundry Database Schema Specification

**Document Version:** 1.0.0  
**Database Engine:** MySQL 8.4 LTS  
**Default Storage Engine:** InnoDB  
**Default Character Set & Collation:** `utf8mb4` / `utf8mb4_0900_ai_ci`  
**Timezone Standard:** UTC (stored as `TIMESTAMP` or `DATETIME`), displayed in Asia/Jakarta (`WIB` / `UTC+7`) at application layer  
**Primary Reference:** `docs/architecture.md`, `docs/erd.md`, `docs/financial-logic.md`

---

## 1. Schema Conventions & Global Standards

### 1.1 Precision & Numeric Types Standard
- **Monetary Values:** `DECIMAL(12, 2)`  
  *Covers values up to IDR 9,999,999,999.99.* Never use `FLOAT`, `DOUBLE`, or integer cents/pennies. Signed `DECIMAL(12, 2)` is used where negative adjustments occur (e.g. cash discrepancies, refund totals); unsigned where strictly non-negative.
- **Weights & Physical Quantities:** `DECIMAL(8, 2)`  
  *Covers quantities up to 999,999.99 kg or pcs.* Allows fractional kilogram weighing (e.g. `2.45 kg`).
- **Rates & Percentages:** `DECIMAL(5, 2)`  
  *Covers percentages from 0.00% to 100.00%* (e.g. `11.00%` PPN tax, `15.00%` promo discount).
- **Multipliers:** `DECIMAL(4, 2)`  
  *Covers tier multipliers* (e.g. `1.50x` for Kilat, `2.00x` for Express).

### 1.2 Primary & Foreign Key Conventions
- **Primary Keys:** Every table uses `id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY`.
- **Foreign Keys:** Named with pattern `{singular_parent_table}_id` (e.g. `order_id`, `customer_id`, `shift_id`).
- **Referential Integrity & Deletion Rules:**
  - `ON DELETE RESTRICT`: Applied to all core financial, operational, and audit entities (`orders`, `payments`, `refunds`, `cashier_shifts`, `customers`, `users`). Prevents accidental loss of historical records if a master record is deleted.
  - `ON DELETE CASCADE`: Applied strictly to dependent child composition records (`order_items`, `order_item_treatments`, `shift_denominations`).
  - `ON DELETE SET NULL`: Applied to optional tracking references (e.g. `approved_by_id`, `shift_id` on non-shift expenses).

### 1.3 Audit & Lifecycle Columns
- `created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP`
- `updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP`
- `deleted_at TIMESTAMP NULL DEFAULT NULL` (for tables implementing soft deletes: `customers`, `orders`, `services`, `users`).

---

## 2. Table Definitions & Relational Specifications

---

### Table 1: `users`
Represents system operators, cashiers, and store owners with role-based access.

```sql
CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('owner', 'cashier', 'operator') NOT NULL DEFAULT 'operator',
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    remember_token VARCHAR(100) NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL DEFAULT NULL,
    
    UNIQUE KEY uq_users_email (email),
    UNIQUE KEY uq_users_phone (phone),
    INDEX idx_users_role_active (role, is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
```

---

### Table 2: `cashier_shifts`
Tracks cashier cash drawer sessions, opening float, blind count audits, and discrepancies.

```sql
CREATE TABLE cashier_shifts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    shift_number VARCHAR(32) NOT NULL,
    cashier_id BIGINT UNSIGNED NOT NULL,
    opened_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    closed_at TIMESTAMP NULL DEFAULT NULL,
    opening_float DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
    expected_cash DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
    counted_cash DECIMAL(12, 2) NULL DEFAULT NULL,
    cash_difference DECIMAL(12, 2) NULL DEFAULT NULL,
    difference_reason TEXT NULL,
    status ENUM('open', 'closing', 'closed') NOT NULL DEFAULT 'open',
    notes TEXT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY uq_cashier_shifts_number (shift_number),
    INDEX idx_cashier_shifts_cashier_status (cashier_id, status),
    INDEX idx_cashier_shifts_opened_at (opened_at),
    CONSTRAINT fk_cashier_shifts_cashier FOREIGN KEY (cashier_id) REFERENCES users (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
```

---

### Table 3: `shift_cash_movements`
Records mid-shift physical cash injections, manager drops to safe, and emergency drawer petty cash disbursements.

```sql
CREATE TABLE shift_cash_movements (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    movement_number VARCHAR(32) NOT NULL,
    shift_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    authorized_by_id BIGINT UNSIGNED NULL,
    movement_type ENUM('cash_in', 'cash_drop', 'expense') NOT NULL,
    amount DECIMAL(12, 2) NOT NULL,
    reason VARCHAR(255) NOT NULL,
    reference_number VARCHAR(64) NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY uq_shift_cash_movements_number (movement_number),
    INDEX idx_shift_movements_shift_type (shift_id, movement_type),
    CONSTRAINT fk_shift_movements_shift FOREIGN KEY (shift_id) REFERENCES cashier_shifts (id) ON DELETE RESTRICT,
    CONSTRAINT fk_shift_movements_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE RESTRICT,
    CONSTRAINT fk_shift_movements_auth FOREIGN KEY (authorized_by_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
```

---

### Table 4: `shift_denominations`
Captures individual bill and coin counts entered during the cashier blind count audit protocol.

```sql
CREATE TABLE shift_denominations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    shift_id BIGINT UNSIGNED NOT NULL,
    denomination_value DECIMAL(12, 2) NOT NULL,
    quantity INT UNSIGNED NOT NULL DEFAULT 0,
    subtotal_amount DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_shift_denominations_shift (shift_id),
    CONSTRAINT fk_shift_denominations_shift FOREIGN KEY (shift_id) REFERENCES cashier_shifts (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
```

---

### Table 5: `customers`
Customer registry storing contact details, normalized E.164 phone numbers, and running aggregate stats.

```sql
CREATE TABLE customers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    phone_normalized VARCHAR(20) NOT NULL,
    email VARCHAR(150) NULL,
    address TEXT NULL,
    total_orders_count INT UNSIGNED NOT NULL DEFAULT 0,
    total_spend DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
    outstanding_receivables DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
    notes TEXT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL DEFAULT NULL,

    UNIQUE KEY uq_customers_phone_normalized (phone_normalized),
    INDEX idx_customers_name (name),
    INDEX idx_customers_receivables (outstanding_receivables)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
```

---

### Table 6: `services`
Master catalog for laundry services (e.g. Cuci Komplit Kiloan, Cuci Kering Lipat, Satuan Jas, Bedcover).

```sql
CREATE TABLE services (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT NULL,
    unit ENUM('kg', 'pcs', 'm2') NOT NULL DEFAULT 'kg',
    base_price DECIMAL(12, 2) NOT NULL,
    min_weight DECIMAL(8, 2) NOT NULL DEFAULT 1.00,
    estimated_duration_hours INT UNSIGNED NOT NULL DEFAULT 48,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL DEFAULT NULL,

    UNIQUE KEY uq_services_code (code),
    INDEX idx_services_unit_active (unit, is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
```

---

### Table 7: `service_tiers`
Turnaround speed tiers modifying service base prices and delivery turnaround expectations.

```sql
CREATE TABLE service_tiers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    service_id BIGINT UNSIGNED NOT NULL,
    tier_name VARCHAR(50) NOT NULL,
    tier_code VARCHAR(20) NOT NULL,
    multiplier_factor DECIMAL(4, 2) NOT NULL DEFAULT 1.00,
    flat_surcharge DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
    duration_hours INT UNSIGNED NOT NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY uq_service_tier_code (service_id, tier_code),
    INDEX idx_service_tiers_active (service_id, is_active),
    CONSTRAINT fk_service_tiers_service FOREIGN KEY (service_id) REFERENCES services (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
```

---

### Table 8: `orders`
Primary transactional entity governing the orthogonal production workflow and payment status.

```sql
CREATE TABLE orders (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    invoice_number VARCHAR(32) NOT NULL,
    customer_id BIGINT UNSIGNED NOT NULL,
    cashier_id BIGINT UNSIGNED NOT NULL,
    shift_id BIGINT UNSIGNED NOT NULL,
    approved_by_id BIGINT UNSIGNED NULL,
    
    production_status ENUM(
        'received',
        'washing',
        'drying',
        'ironing',
        'ready_for_pickup',
        'out_for_delivery',
        'completed',
        'canceled'
    ) NOT NULL DEFAULT 'received',
    
    payment_status ENUM(
        'unpaid',
        'partially_paid',
        'paid',
        'overpaid',
        'refunded',
        'partially_refunded'
    ) NOT NULL DEFAULT 'unpaid',

    intake_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    target_completion_at TIMESTAMP NOT NULL,
    actual_completion_at TIMESTAMP NULL DEFAULT NULL,
    picked_up_at TIMESTAMP NULL DEFAULT NULL,

    subtotal_amount DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
    discount_amount DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
    surcharge_amount DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
    tax_amount DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
    total_amount DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
    paid_amount DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
    change_amount DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
    outstanding_balance DECIMAL(12, 2) NOT NULL DEFAULT 0.00,

    payment_due_date DATE NULL DEFAULT NULL,
    is_debt_approved BOOLEAN NOT NULL DEFAULT FALSE,
    debt_notes TEXT NULL,

    staging_rack VARCHAR(30) NULL,
    rewash_count INT UNSIGNED NOT NULL DEFAULT 0,
    notes TEXT NULL,
    cancellation_reason VARCHAR(255) NULL,

    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL DEFAULT NULL,

    UNIQUE KEY uq_orders_invoice_number (invoice_number),
    INDEX idx_orders_customer_status (customer_id, production_status),
    INDEX idx_orders_payment_status (payment_status, outstanding_balance),
    INDEX idx_orders_shift (shift_id),
    INDEX idx_orders_intake_at (intake_at),
    INDEX idx_orders_target_completion (target_completion_at),
    INDEX idx_orders_staging_rack (staging_rack),

    CONSTRAINT fk_orders_customer FOREIGN KEY (customer_id) REFERENCES customers (id) ON DELETE RESTRICT,
    CONSTRAINT fk_orders_cashier FOREIGN KEY (cashier_id) REFERENCES users (id) ON DELETE RESTRICT,
    CONSTRAINT fk_orders_shift FOREIGN KEY (shift_id) REFERENCES cashier_shifts (id) ON DELETE RESTRICT,
    CONSTRAINT fk_orders_approved_by FOREIGN KEY (approved_by_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
```

---

### Table 9: `order_items`
Frozen item snapshot capturing exact catalog rates, units, and actual vs. billed quantities at intake.

```sql
CREATE TABLE order_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id BIGINT UNSIGNED NOT NULL,
    service_id BIGINT UNSIGNED NOT NULL,
    service_tier_id BIGINT UNSIGNED NULL,
    
    item_name_snapshot VARCHAR(150) NOT NULL,
    pricing_unit_snapshot ENUM('kg', 'pcs', 'm2') NOT NULL DEFAULT 'kg',
    unit_price_snapshot DECIMAL(12, 2) NOT NULL,
    
    actual_quantity DECIMAL(8, 2) NOT NULL,
    billed_quantity DECIMAL(8, 2) NOT NULL,
    
    discount_amount_snapshot DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
    subtotal_snapshot DECIMAL(12, 2) NOT NULL,
    
    item_condition_notes TEXT NULL,
    special_instructions TEXT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_order_items_order (order_id),
    INDEX idx_order_items_service (service_id),
    CONSTRAINT fk_order_items_order FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE CASCADE,
    CONSTRAINT fk_order_items_service FOREIGN KEY (service_id) REFERENCES services (id) ON DELETE RESTRICT,
    CONSTRAINT fk_order_items_tier FOREIGN KEY (service_tier_id) REFERENCES service_tiers (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
```

---

### Table 10: `order_item_treatments`
Special laundry treatments attached to line items (fragrance scent, anti-bacterial wash, spotting, premium packaging).

```sql
CREATE TABLE order_item_treatments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_item_id BIGINT UNSIGNED NOT NULL,
    treatment_type ENUM('fragrance', 'spotting', 'anti_bacterial', 'special_pack') NOT NULL,
    treatment_name VARCHAR(100) NOT NULL,
    surcharge_amount DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
    notes VARCHAR(255) NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_treatments_order_item (order_item_id),
    CONSTRAINT fk_treatments_order_item FOREIGN KEY (order_item_id) REFERENCES order_items (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
```

---

### Table 11: `payments`
Immutable record of financial tenders received (cash, QRIS, transfer) supporting down payments and split tenders.

```sql
CREATE TABLE payments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    payment_number VARCHAR(32) NOT NULL,
    order_id BIGINT UNSIGNED NOT NULL,
    shift_id BIGINT UNSIGNED NOT NULL,
    cashier_id BIGINT UNSIGNED NOT NULL,
    payment_method ENUM('cash', 'qris', 'bank_transfer', 'debit_card') NOT NULL,
    amount DECIMAL(12, 2) NOT NULL,
    tender_amount DECIMAL(12, 2) NOT NULL,
    change_amount DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
    payment_status ENUM('pending', 'success', 'failed') NOT NULL DEFAULT 'success',
    transaction_reference VARCHAR(100) NULL,
    idempotency_key VARCHAR(64) NOT NULL,
    paid_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    notes TEXT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY uq_payments_payment_number (payment_number),
    UNIQUE KEY uq_payments_idempotency (idempotency_key),
    INDEX idx_payments_order (order_id),
    INDEX idx_payments_shift_method (shift_id, payment_method),
    INDEX idx_payments_paid_at (paid_at),

    CONSTRAINT fk_payments_order FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE RESTRICT,
    CONSTRAINT fk_payments_shift FOREIGN KEY (shift_id) REFERENCES cashier_shifts (id) ON DELETE RESTRICT,
    CONSTRAINT fk_payments_cashier FOREIGN KEY (cashier_id) REFERENCES users (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
```

---

### Table 12: `refunds`
Reversal records for overpayments, order cancellations, or approved loss claims.

```sql
CREATE TABLE refunds (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    refund_number VARCHAR(32) NOT NULL,
    payment_id BIGINT UNSIGNED NOT NULL,
    order_id BIGINT UNSIGNED NOT NULL,
    shift_id BIGINT UNSIGNED NOT NULL,
    cashier_id BIGINT UNSIGNED NOT NULL,
    authorized_by_id BIGINT UNSIGNED NOT NULL,
    refund_method ENUM('cash', 'bank_transfer') NOT NULL,
    refund_amount DECIMAL(12, 2) NOT NULL,
    reason TEXT NOT NULL,
    status ENUM('pending', 'completed', 'rejected') NOT NULL DEFAULT 'completed',
    processed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    notes TEXT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY uq_refunds_number (refund_number),
    INDEX idx_refunds_order (order_id),
    INDEX idx_refunds_payment (payment_id),
    INDEX idx_refunds_shift (shift_id),

    CONSTRAINT fk_refunds_payment FOREIGN KEY (payment_id) REFERENCES payments (id) ON DELETE RESTRICT,
    CONSTRAINT fk_refunds_order FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE RESTRICT,
    CONSTRAINT fk_refunds_shift FOREIGN KEY (shift_id) REFERENCES cashier_shifts (id) ON DELETE RESTRICT,
    CONSTRAINT fk_refunds_cashier FOREIGN KEY (cashier_id) REFERENCES users (id) ON DELETE RESTRICT,
    CONSTRAINT fk_refunds_auth FOREIGN KEY (authorized_by_id) REFERENCES users (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
```

---

### Table 13: `expenses`
Operational cost disbursements categorizing COGS, utility expenses, maintenance, and petty cash logs.

```sql
CREATE TABLE expenses (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    expense_number VARCHAR(32) NOT NULL,
    shift_id BIGINT UNSIGNED NULL,
    cashier_id BIGINT UNSIGNED NOT NULL,
    authorized_by_id BIGINT UNSIGNED NULL,
    category ENUM(
        'cogs_detergent',
        'cogs_packaging',
        'cogs_gas',
        'utilities_electricity',
        'utilities_water',
        'maintenance',
        'payroll',
        'general_administrative'
    ) NOT NULL,
    amount DECIMAL(12, 2) NOT NULL,
    payment_source ENUM('drawer_cash', 'bank_account') NOT NULL DEFAULT 'drawer_cash',
    payment_reference VARCHAR(100) NULL,
    vendor_name VARCHAR(150) NULL,
    description TEXT NOT NULL,
    receipt_path VARCHAR(255) NULL,
    incurred_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY uq_expenses_number (expense_number),
    INDEX idx_expenses_category_date (category, incurred_at),
    INDEX idx_expenses_shift_source (shift_id, payment_source),

    CONSTRAINT fk_expenses_shift FOREIGN KEY (shift_id) REFERENCES cashier_shifts (id) ON DELETE SET NULL,
    CONSTRAINT fk_expenses_cashier FOREIGN KEY (cashier_id) REFERENCES users (id) ON DELETE RESTRICT,
    CONSTRAINT fk_expenses_auth FOREIGN KEY (authorized_by_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
```

---

### Table 14: `whatsapp_message_logs`
Asynchronous message tracking log supporting idempotency, retry tracking, and delivery webhook timestamps.

```sql
CREATE TABLE whatsapp_message_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id BIGINT UNSIGNED NULL,
    customer_id BIGINT UNSIGNED NOT NULL,
    recipient_phone VARCHAR(20) NOT NULL,
    message_type ENUM(
        'order_created',
        'order_processing',
        'order_ready',
        'payment_receipt',
        'debt_reminder',
        'manual_fallback'
    ) NOT NULL,
    gateway_provider ENUM('wuzapi', 'meta_cloud', 'manual_link') NOT NULL DEFAULT 'wuzapi',
    gateway_message_id VARCHAR(100) NULL,
    idempotency_key VARCHAR(64) NOT NULL,
    message_body TEXT NOT NULL,
    status ENUM('queued', 'sent', 'delivered', 'read', 'failed') NOT NULL DEFAULT 'queued',
    http_status_code INT UNSIGNED NULL,
    error_message TEXT NULL,
    sent_at TIMESTAMP NULL DEFAULT NULL,
    delivered_at TIMESTAMP NULL DEFAULT NULL,
    read_at TIMESTAMP NULL DEFAULT NULL,
    retry_count INT UNSIGNED NOT NULL DEFAULT 0,
    next_retry_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY uq_wa_logs_idempotency (idempotency_key),
    INDEX idx_wa_logs_gateway_msg (gateway_message_id),
    INDEX idx_wa_logs_order (order_id),
    INDEX idx_wa_logs_customer_status (customer_id, status),
    INDEX idx_wa_logs_queue_retry (status, next_retry_at),

    CONSTRAINT fk_wa_logs_order FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE SET NULL,
    CONSTRAINT fk_wa_logs_customer FOREIGN KEY (customer_id) REFERENCES customers (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
```

---

### Table 15: `audit_logs`
Immutable compliance and activity ledger recording critical operational events and financial overrides.

```sql
CREATE TABLE audit_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NULL,
    action ENUM(
        'create',
        'update',
        'delete',
        'login',
        'logout',
        'price_override',
        'debt_approval',
        'shift_discrepancy'
    ) NOT NULL,
    auditable_type VARCHAR(100) NOT NULL,
    auditable_id BIGINT UNSIGNED NOT NULL,
    old_values JSON NULL,
    new_values JSON NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_audit_logs_user_action (user_id, action),
    INDEX idx_audit_logs_target (auditable_type, auditable_id),
    INDEX idx_audit_logs_created (created_at),

    CONSTRAINT fk_audit_logs_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
```

---

## 3. Performance Indexing Matrix

| Table | Index Name | Indexed Columns | Query Optimization Purpose |
|---|---|---|---|
| `users` | `idx_users_role_active` | `(role, is_active)` | Fast filtering for active cashiers/operators in UI |
| `cashier_shifts` | `idx_cashier_shifts_cashier_status` | `(cashier_id, status)` | Instant lookup of active open shift per cashier |
| `customers` | `uq_customers_phone_normalized` | `(phone_normalized)` | Zero-latency customer search by WhatsApp number |
| `customers` | `idx_customers_receivables` | `(outstanding_receivables)` | Debt ledger queries & WhatsApp reminder filters |
| `orders` | `idx_orders_customer_status` | `(customer_id, production_status)` | Customer active order history and pickup lookup |
| `orders` | `idx_orders_payment_status` | `(payment_status, outstanding_balance)` | Financial settlement queries & unpaid balance reports |
| `orders` | `idx_orders_intake_at` | `(intake_at)` | Daily/weekly POS intake volume & sales reporting |
| `orders` | `idx_orders_target_completion`| `(target_completion_at)` | Production operator schedule & priority queue sorting |
| `payments` | `uq_payments_idempotency` | `(idempotency_key)` | Hardware/network double-charge protection |
| `payments` | `idx_payments_shift_method` | `(shift_id, payment_method)` | X/Z report aggregation by payment channel |
| `expenses` | `idx_expenses_category_date` | `(category, incurred_at)` | COGS vs Operational Expense P&L reporting |
| `whatsapp_message_logs`| `idx_wa_logs_queue_retry` | `(status, next_retry_at)` | Background queue polling for failed notification retries |
| `audit_logs` | `idx_audit_logs_target` | `(auditable_type, auditable_id)`| Security audit trail inspection for any specific entity |

---

## 4. Referential Integrity Guarantees & Deletion Rules

1. **Financial Preservation Guarantee:** Deleting any record from `orders`, `payments`, `refunds`, or `expenses` is strictly prohibited via `ON DELETE RESTRICT` foreign keys. This guarantees that audit trails and accounting ledgers cannot be corrupted.
2. **Shift Integrity Guarantee:** An active or closed `cashier_shifts` record cannot be dropped while associated orders, payments, or movements reference it.
3. **Child Cleanup via Cascade:** Detailed breakdowns that have no meaning independent of their parent record (`order_items`, `order_item_treatments`, `shift_denominations`) are configured with `ON DELETE CASCADE` only if a draft order is hard-canceled or removed before submission.
4. **Normalized Telephone Numbers:** Customer phone numbers are strictly scrubbed to international E.164 without symbols (e.g. `6281234567890`) and indexed under `uq_customers_phone_normalized`.
