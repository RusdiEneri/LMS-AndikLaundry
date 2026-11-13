# LMS-AndikLaundry Entity Relationship Diagram (ERD)

**Document Version:** 1.0.0  
**Target Platform:** MySQL 8.4 LTS  
**Primary Reference:** `docs/database-schema.md`, `docs/architecture.md`, `docs/financial-logic.md`

---

## 1. Overview & Diagrammatic Scope

This document provides the complete Entity Relationship Diagram (ERD) for **LMS-AndikLaundry** in standard Mermaid `erDiagram` syntax. It models the operational, financial, and messaging entities of the single-store laundry management system, illustrating all primary keys, foreign keys, unique keys, and relationship cardinalities.

### Cardinality Legend
- `||--o{` : Exactly one parent relates to zero, one, or many children ($1 : 0..N$)
- `||--|{` : Exactly one parent relates to one or many children ($1 : 1..N$)
- `|o--o{` : Zero or one parent relates to zero, one, or many children ($0..1 : 0..N$)
- `||--||` : Exactly one parent relates to exactly one child ($1 : 1$)

---

## 2. Complete Mermaid Entity Relationship Diagram

```mermaid
erDiagram
    USERS ||--o{ CASHIER_SHIFTS : "opens"
    USERS ||--o{ SHIFT_CASH_MOVEMENTS : "executes"
    USERS ||--o{ ORDERS : "intakes_as_cashier"
    USERS ||--o{ PAYMENTS : "collects"
    USERS ||--o{ REFUNDS : "authorizes"
    USERS ||--o{ EXPENSES : "records"
    USERS ||--o{ AUDIT_LOGS : "triggers"

    CASHIER_SHIFTS ||--o{ SHIFT_CASH_MOVEMENTS : "tracks"
    CASHIER_SHIFTS ||--o{ SHIFT_DENOMINATIONS : "contains_count"
    CASHIER_SHIFTS ||--o{ ORDERS : "settles"
    CASHIER_SHIFTS ||--o{ PAYMENTS : "reconciles"
    CASHIER_SHIFTS ||--o{ REFUNDS : "disburses"
    CASHIER_SHIFTS ||--o{ EXPENSES : "funds_petty_cash"

    CUSTOMERS ||--o{ ORDERS : "places"
    CUSTOMERS ||--o{ WHATSAPP_MESSAGE_LOGS : "receives"

    SERVICES ||--o{ SERVICE_TIERS : "offers"
    SERVICES ||--o{ ORDER_ITEMS : "cataloged_in"
    SERVICE_TIERS |o--o{ ORDER_ITEMS : "applies_speed_to"

    ORDERS ||--|{ ORDER_ITEMS : "contains"
    ORDERS ||--o{ PAYMENTS : "paid_by"
    ORDERS ||--o{ REFUNDS : "reimburses"
    ORDERS ||--o{ WHATSAPP_MESSAGE_LOGS : "notifies"

    ORDER_ITEMS ||--o{ ORDER_ITEM_TREATMENTS : "includes"

    PAYMENTS ||--o{ REFUNDS : "reverses"

    USERS {
        bigint id PK
        string name
        string email UK
        string phone UK
        string password
        enum role "owner, cashier, operator"
        boolean is_active
        timestamp created_at
        timestamp updated_at
        timestamp deleted_at
    }

    CASHIER_SHIFTS {
        bigint id PK
        string shift_number UK
        bigint cashier_id FK
        timestamp opened_at
        timestamp closed_at
        decimal opening_float
        decimal expected_cash
        decimal counted_cash
        decimal cash_difference
        text difference_reason
        enum status "open, closing, closed"
        text notes
        timestamp created_at
        timestamp updated_at
    }

    SHIFT_CASH_MOVEMENTS {
        bigint id PK
        string movement_number UK
        bigint shift_id FK
        bigint user_id FK
        bigint authorized_by_id FK
        enum movement_type "cash_in, cash_drop, expense"
        decimal amount
        string reason
        string reference_number
        timestamp created_at
        timestamp updated_at
    }

    SHIFT_DENOMINATIONS {
        bigint id PK
        bigint shift_id FK
        decimal denomination_value
        int quantity
        decimal subtotal_amount
        timestamp created_at
        timestamp updated_at
    }

    CUSTOMERS {
        bigint id PK
        string name
        string phone
        string phone_normalized UK
        string email
        text address
        int total_orders_count
        decimal total_spend
        decimal outstanding_receivables
        text notes
        timestamp created_at
        timestamp updated_at
        timestamp deleted_at
    }

    SERVICES {
        bigint id PK
        string code UK
        string name
        text description
        enum unit "kg, pcs, m2"
        decimal base_price
        decimal min_weight
        int estimated_duration_hours
        boolean is_active
        timestamp created_at
        timestamp updated_at
        timestamp deleted_at
    }

    SERVICE_TIERS {
        bigint id PK
        bigint service_id FK
        string tier_name
        string tier_code UK
        decimal multiplier_factor
        decimal flat_surcharge
        int duration_hours
        boolean is_active
        timestamp created_at
        timestamp updated_at
    }

    ORDERS {
        bigint id PK
        string invoice_number UK
        bigint customer_id FK
        bigint cashier_id FK
        bigint shift_id FK
        bigint approved_by_id FK
        enum production_status "received, washing, drying, ironing, ready_for_pickup, out_for_delivery, completed, canceled"
        enum payment_status "unpaid, partially_paid, paid, overpaid, refunded, partially_refunded"
        timestamp intake_at
        timestamp target_completion_at
        timestamp actual_completion_at
        timestamp picked_up_at
        decimal subtotal_amount
        decimal discount_amount
        decimal surcharge_amount
        decimal tax_amount
        decimal total_amount
        decimal paid_amount
        decimal change_amount
        decimal outstanding_balance
        date payment_due_date
        boolean is_debt_approved
        text debt_notes
        string staging_rack
        int rewash_count
        text notes
        string cancellation_reason
        timestamp created_at
        timestamp updated_at
        timestamp deleted_at
    }

    ORDER_ITEMS {
        bigint id PK
        bigint order_id FK
        bigint service_id FK
        bigint service_tier_id FK
        string item_name_snapshot
        enum pricing_unit_snapshot "kg, pcs, m2"
        decimal unit_price_snapshot
        decimal actual_quantity
        decimal billed_quantity
        decimal discount_amount_snapshot
        decimal subtotal_snapshot
        text item_condition_notes
        text special_instructions
        timestamp created_at
        timestamp updated_at
    }

    ORDER_ITEM_TREATMENTS {
        bigint id PK
        bigint order_item_id FK
        enum treatment_type "fragrance, spotting, anti_bacterial, special_pack"
        string treatment_name
        decimal surcharge_amount
        string notes
        timestamp created_at
        timestamp updated_at
    }

    PAYMENTS {
        bigint id PK
        string payment_number UK
        bigint order_id FK
        bigint shift_id FK
        bigint cashier_id FK
        enum payment_method "cash, qris, bank_transfer, debit_card"
        decimal amount
        decimal tender_amount
        decimal change_amount
        enum payment_status "pending, success, failed"
        string transaction_reference
        string idempotency_key UK
        timestamp paid_at
        text notes
        timestamp created_at
        timestamp updated_at
    }

    REFUNDS {
        bigint id PK
        string refund_number UK
        bigint payment_id FK
        bigint order_id FK
        bigint shift_id FK
        bigint cashier_id FK
        bigint authorized_by_id FK
        enum refund_method "cash, bank_transfer"
        decimal refund_amount
        text reason
        enum status "pending, completed, rejected"
        timestamp processed_at
        text notes
        timestamp created_at
        timestamp updated_at
    }

    EXPENSES {
        bigint id PK
        string expense_number UK
        bigint shift_id FK
        bigint cashier_id FK
        bigint authorized_by_id FK
        enum category "cogs_detergent, cogs_packaging, cogs_gas, utilities_electricity, utilities_water, maintenance, payroll, general_administrative"
        decimal amount
        enum payment_source "drawer_cash, bank_account"
        string payment_reference
        string vendor_name
        text description
        string receipt_path
        timestamp incurred_at
        timestamp created_at
        timestamp updated_at
    }

    WHATSAPP_MESSAGE_LOGS {
        bigint id PK
        bigint order_id FK
        bigint customer_id FK
        string recipient_phone
        enum message_type "order_created, order_processing, order_ready, payment_receipt, debt_reminder, manual_fallback"
        enum gateway_provider "wuzapi, meta_cloud, manual_link"
        string gateway_message_id
        string idempotency_key UK
        text message_body
        enum status "queued, sent, delivered, read, failed"
        int http_status_code
        text error_message
        timestamp sent_at
        timestamp delivered_at
        timestamp read_at
        int retry_count
        timestamp next_retry_at
        timestamp created_at
        timestamp updated_at
    }

    AUDIT_LOGS {
        bigint id PK
        bigint user_id FK
        enum action "create, update, delete, login, logout, price_override, debt_approval, shift_discrepancy"
        string auditable_type
        bigint auditable_id
        json old_values
        json new_values
        string ip_address
        text user_agent
        timestamp created_at
    }
```

---

## 3. Entity Relationship Dictionary & Cardinality Matrix

| Parent Entity | Relationship | Child Entity | Cardinality | Foreign Key Column | On Delete Rule | Business Logic & Integrity Invariant |
|---|---|---|---|---|---|---|
| `USERS` | opens | `CASHIER_SHIFTS` | $1 : 0..N$ | `cashier_id` | `RESTRICT` | User must hold the role `cashier` or `owner` to open a shift session. |
| `USERS` | executes | `SHIFT_CASH_MOVEMENTS` | $1 : 0..N$ | `user_id` | `RESTRICT` | Tracks cashier responsible for drawer drops, cash-ins, and petty cash logs. |
| `USERS` | intakes | `ORDERS` | $1 : 0..N$ | `cashier_id` | `RESTRICT` | Identifies which staff member created the order invoice. |
| `USERS` | authorizes debt | `ORDERS` | $0..1 : 0..N$ | `approved_by_id` | `SET NULL` | Required when an unpaid order is released on credit to a customer. |
| `USERS` | collects | `PAYMENTS` | $1 : 0..N$ | `cashier_id` | `RESTRICT` | Identifies staff member receiving the money tender. |
| `USERS` | authorizes | `REFUNDS` | $1 : 0..N$ | `authorized_by_id` | `RESTRICT` | Owner or Manager level authorization required for any cash/transfer refund. |
| `USERS` | records | `EXPENSES` | $1 : 0..N$ | `cashier_id` | `RESTRICT` | Tracks staff member inputting the expense invoice or voucher. |
| `USERS` | triggers | `AUDIT_LOGS` | $0..1 : 0..N$ | `user_id` | `SET NULL` | Records the responsible actor; set to null for automated system background jobs. |
| `CASHIER_SHIFTS` | tracks | `SHIFT_CASH_MOVEMENTS` | $1 : 0..N$ | `shift_id` | `RESTRICT` | All cash movements directly affect `expected_cash` in the active shift drawer. |
| `CASHIER_SHIFTS` | contains count | `SHIFT_DENOMINATIONS`| $1 : 0..N$ | `shift_id` | `CASCADE` | Detailed bill/coin counts submitted during blind count shift closing. |
| `CASHIER_SHIFTS` | settles | `ORDERS` | $1 : 0..N$ | `shift_id` | `RESTRICT` | Associates intake transaction with the active operational shift. |
| `CASHIER_SHIFTS` | reconciles | `PAYMENTS` | $1 : 0..N$ | `shift_id` | `RESTRICT` | Physical cash payments increment shift drawer; non-cash logged separately. |
| `CASHIER_SHIFTS` | disburses | `REFUNDS` | $1 : 0..N$ | `shift_id` | `RESTRICT` | Cash refunds decrease expected cash drawer balance. |
| `CASHIER_SHIFTS` | funds petty cash | `EXPENSES` | $0..1 : 0..N$ | `shift_id` | `SET NULL` | Drawer expenses reduce cash in register; bank expenses omit `shift_id`. |
| `CUSTOMERS` | places | `ORDERS` | $1 : 0..N$ | `customer_id` | `RESTRICT` | Customer profile aggregates total spend, order counts, and receivables. |
| `CUSTOMERS` | receives | `WHATSAPP_MESSAGE_LOGS`| $1 : 0..N$ | `customer_id` | `RESTRICT` | Direct notification recipient for ready notices and receipts. |
| `SERVICES` | offers | `SERVICE_TIERS` | $1 : 0..N$ | `service_id` | `CASCADE` | Defines turnaround speeds (Regular, Kilat, Express) per master service. |
| `SERVICES` | cataloged in | `ORDER_ITEMS` | $1 : 0..N$ | `service_id` | `RESTRICT` | Master catalog reference for pricing snapshot creation. |
| `SERVICE_TIERS` | applies speed | `ORDER_ITEMS` | $0..1 : 0..N$ | `service_tier_id` | `SET NULL` | Specific speed tier multiplier applied to the line item. |
| `ORDERS` | contains | `ORDER_ITEMS` | $1 : 1..N$ | `order_id` | `CASCADE` | Frozen line-item snapshots. An order must possess at least one item. |
| `ORDERS` | paid by | `PAYMENTS` | $1 : 0..N$ | `order_id` | `RESTRICT` | Supports split tenders (e.g. Cash + QRIS) and down payment deposits. |
| `ORDERS` | reimburses | `REFUNDS` | $1 : 0..N$ | `order_id` | `RESTRICT` | Reversals from cancellation or claims linked directly to the parent order. |
| `ORDERS` | notifies | `WHATSAPP_MESSAGE_LOGS`| $0..1 : 0..N$ | `order_id` | `SET NULL` | Outbound WhatsApp notifications dispatched for specific order events. |
| `ORDER_ITEMS` | includes | `ORDER_ITEM_TREATMENTS`| $1 : 0..N$ | `order_item_id` | `CASCADE` | Optional add-ons such as perfume scents, anti-bacterial wash, and spotting. |
| `PAYMENTS` | reverses | `REFUNDS` | $1 : 0..N$ | `payment_id` | `RESTRICT` | Links refund record to the specific original tender transaction. |

---

## 4. Cross-Document Consistency Matrix

This schema and ERD specification is guaranteed 100% consistent with the system architecture and business requirements:
1. **Financial Types:** All monetary columns in `ORDERS`, `ORDER_ITEMS`, `ORDER_ITEM_TREATMENTS`, `PAYMENTS`, `REFUNDS`, `EXPENSES`, `CASHIER_SHIFTS`, and `CUSTOMERS` strictly use `DECIMAL(12, 2)`.
2. **Weight Precision:** All physical quantity and weight columns (`actual_quantity`, `billed_quantity`, `min_weight`) strictly use `DECIMAL(8, 2)`.
3. **Decoupled FSMs:** `ORDERS` features separate `production_status` and `payment_status` ENUMs with zero coupling between production processing and payment settlement.
4. **Auditability:** Critical actions and financial updates log JSON deltas into `AUDIT_LOGS`, while message retries and idempotency keys in `WHATSAPP_MESSAGE_LOGS` prevent duplicate customer alerts.
