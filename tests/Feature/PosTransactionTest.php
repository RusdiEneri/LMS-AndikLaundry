<?php

use App\Enums\PaymentStatus;
use App\Enums\ProductionStatus;
use App\Enums\ServiceUnit;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceTier;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\User;
use App\Services\InvoiceNumberGenerator;
use App\Services\TransactionService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Milestone 2: POS Transactions, Pricing Snapshots & Invoicing Tests
|--------------------------------------------------------------------------
| Specification Source: docs/PRD.md FEAT-102, docs/financial-logic.md §2,
|                       docs/database-schema.md Tables 8 & 9
*/

describe('Milestone 2: Invoice Number Generator', function () {

    it('T2-INV-01: Generates sequential daily invoice numbers in INV-YYYYMMDD-XXXX format', function () {
        $generator = app(InvoiceNumberGenerator::class);
        $today = now();
        $dateStr = $today->format('Ymd');

        $inv1 = $generator->generate($today);
        expect($inv1)->toBe("INV-{$dateStr}-0001");

        // Save a transaction with this invoice number
        Transaction::factory()->create(['invoice_number' => $inv1]);

        $inv2 = $generator->generate($today);
        expect($inv2)->toBe("INV-{$dateStr}-0002");

        Transaction::factory()->create(['invoice_number' => $inv2]);

        $inv3 = $generator->generate($today);
        expect($inv3)->toBe("INV-{$dateStr}-0003");
    });

    it('T2-INV-02: Enforces database unique constraint on invoice_number', function () {
        $invNumber = 'INV-20261114-9999';
        Transaction::factory()->create(['invoice_number' => $invNumber]);

        expect(function () use ($invNumber) {
            Transaction::factory()->create(['invoice_number' => $invNumber]);
        })->toThrow(QueryException::class);
    });
});

describe('Milestone 2: Kiloan Minimum Weight Threshold Invoicing Logic', function () {

    it('T2-PRIC-01: Applies minimum weight threshold on kiloan line item when actual weight is below threshold', function () {
        $category = ServiceCategory::factory()->create(['name' => 'Kiloan']);
        $service = Service::factory()->for($category, 'category')->create([
            'name' => 'Cuci Komplit Reguler',
            'code' => 'CK-REG',
            'unit' => ServiceUnit::KG,
            'base_price' => 7000.00,
            'min_weight' => 3.00,
        ]);
        $customer = Customer::factory()->create();
        $cashier = User::factory()->create(['role' => 'cashier']);

        $serviceTx = app(TransactionService::class);
        $transaction = $serviceTx->createTransaction([
            'customer_id' => $customer->id,
            'items' => [
                [
                    'service_id' => $service->id,
                    'actual_quantity' => 1.50, // 1.50 kg < 3.00 kg minimum
                ],
            ],
            'paid_amount' => 25000.00,
        ], $cashier);

        $item = $transaction->items()->first();

        // Actual weight is preserved for customer transparency
        expect((float) $item->actual_quantity)->toBe(1.50)
            // Billed quantity is elevated to minimum weight threshold
            ->and((float) $item->billed_quantity)->toBe(3.00)
            ->and((float) $item->unit_price_snapshot)->toBe(7000.00)
            // 3.00 kg * 7,000 = 21,000
            ->and((float) $item->subtotal_snapshot)->toBe(21000.00)
            ->and((float) $transaction->subtotal_amount)->toBe(21000.00)
            ->and((float) $transaction->total_amount)->toBe(21000.00)
            ->and((float) $transaction->paid_amount)->toBe(25000.00)
            // Change = 25,000 - 21,000 = 4,000
            ->and((float) $transaction->change_amount)->toBe(4000.00)
            ->and((float) $transaction->outstanding_balance)->toBe(0.00)
            ->and($transaction->payment_status)->toBe(PaymentStatus::OVERPAID);
    });

    it('T2-PRIC-02: Bills exact weight when actual weight exceeds minimum weight threshold', function () {
        $category = ServiceCategory::factory()->create(['name' => 'Kiloan']);
        $service = Service::factory()->for($category, 'category')->create([
            'name' => 'Cuci Komplit Reguler',
            'unit' => ServiceUnit::KG,
            'base_price' => 8000.00,
            'min_weight' => 3.00,
        ]);
        $customer = Customer::factory()->create();
        $cashier = User::factory()->create(['role' => 'cashier']);

        $serviceTx = app(TransactionService::class);
        $transaction = $serviceTx->createTransaction([
            'customer_id' => $customer->id,
            'items' => [
                [
                    'service_id' => $service->id,
                    'actual_quantity' => 4.25, // 4.25 kg > 3.00 kg
                ],
            ],
            'paid_amount' => 34000.00,
        ], $cashier);

        $item = $transaction->items()->first();

        expect((float) $item->actual_quantity)->toBe(4.25)
            ->and((float) $item->billed_quantity)->toBe(4.25)
            // 4.25 * 8000 = 34,000
            ->and((float) $item->subtotal_snapshot)->toBe(34000.00)
            ->and((float) $transaction->total_amount)->toBe(34000.00)
            ->and((float) $transaction->outstanding_balance)->toBe(0.00)
            ->and($transaction->payment_status)->toBe(PaymentStatus::PAID);
    });
});

describe('Milestone 2: Price Snapshotting Immutability', function () {

    it('T2-SNAP-01: Line item snapshots remain permanently immutable when master catalog service prices change or get soft-deleted', function () {
        $category = ServiceCategory::factory()->create(['name' => 'Satuan Exclusive']);
        $service = Service::factory()->for($category, 'category')->create([
            'name' => 'Jas Pria Formal',
            'code' => 'ST-JAS',
            'unit' => ServiceUnit::PCS,
            'base_price' => 35000.00,
        ]);
        $customer = Customer::factory()->create();
        $cashier = User::factory()->create(['role' => 'cashier']);

        $serviceTx = app(TransactionService::class);
        $transaction = $serviceTx->createTransaction([
            'customer_id' => $customer->id,
            'items' => [
                [
                    'service_id' => $service->id,
                    'actual_quantity' => 2.00,
                    'perfume_variant' => 'Lavender Luxury',
                    'item_condition_notes' => 'Kancing jas lengkap, ada noda kopi di kerah',
                ],
            ],
            'paid_amount' => 70000.00,
        ], $cashier);

        $item = $transaction->items()->first();

        expect((float) $item->unit_price_snapshot)->toBe(35000.00)
            ->and((float) $item->subtotal_snapshot)->toBe(70000.00)
            ->and($item->item_name_snapshot)->toBe('Jas Pria Formal')
            ->and($item->pricing_unit_snapshot)->toBe('pcs')
            ->and($item->perfume_variant)->toBe('Lavender Luxury')
            ->and($item->item_condition_notes)->toBe('Kancing jas lengkap, ada noda kopi di kerah');

        // Alter catalog base price by +50% (store inflation or price policy change)
        $service->update(['base_price' => 55000.00, 'name' => 'Jas Pria Formal (New Price)']);

        // Refresh transaction and items from DB
        $reloadedItem = TransactionItem::find($item->id);
        $reloadedTx = Transaction::find($transaction->id);

        expect((float) $reloadedItem->unit_price_snapshot)->toBe(35000.00)
            ->and((float) $reloadedItem->subtotal_snapshot)->toBe(70000.00)
            ->and($reloadedItem->item_name_snapshot)->toBe('Jas Pria Formal')
            ->and((float) $reloadedTx->subtotal_amount)->toBe(70000.00)
            ->and((float) $reloadedTx->total_amount)->toBe(70000.00);

        // Soft delete the service entirely
        $service->delete();

        $itemAfterDelete = TransactionItem::find($item->id);
        expect((float) $itemAfterDelete->unit_price_snapshot)->toBe(35000.00)
            ->and((float) $itemAfterDelete->subtotal_snapshot)->toBe(70000.00)
            ->and($itemAfterDelete->item_name_snapshot)->toBe('Jas Pria Formal');
    });

    it('T2-SNAP-02: Speed tier multiplier and flat surcharges freeze properly into snapshots', function () {
        $service = Service::factory()->create([
            'name' => 'Cuci Setrika Kemeja',
            'unit' => ServiceUnit::PCS,
            'base_price' => 10000.00,
        ]);
        $tierExpress = ServiceTier::factory()->for($service)->create([
            'tier_name' => 'Express 6 Jam',
            'multiplier_factor' => 1.50, // +50%
            'flat_surcharge' => 5000.00,  // Rp 5,000 flat
            'duration_hours' => 6,
        ]);
        $customer = Customer::factory()->create();
        $cashier = User::factory()->create(['role' => 'cashier']);

        $serviceTx = app(TransactionService::class);
        $transaction = $serviceTx->createTransaction([
            'customer_id' => $customer->id,
            'items' => [
                [
                    'service_id' => $service->id,
                    'service_tier_id' => $tierExpress->id,
                    'actual_quantity' => 2.00,
                ],
            ],
            'paid_amount' => 0.00,
        ], $cashier);

        $item = $transaction->items()->first();

        // 10,000 * 1.50 multiplier = 15,000 unit price snapshot
        expect((float) $item->unit_price_snapshot)->toBe(15000.00)
            // 2 pcs * 15,000 = 30,000 subtotal snapshot
            ->and((float) $item->subtotal_snapshot)->toBe(30000.00)
            ->and($item->item_name_snapshot)->toBe('Cuci Setrika Kemeja (Express 6 Jam)')
            // Flat surcharge = 5,000 on order
            ->and((float) $transaction->surcharge_amount)->toBe(5000.00)
            // Total = 30,000 + 5,000 = 35,000
            ->and((float) $transaction->total_amount)->toBe(35000.00)
            ->and((float) $transaction->outstanding_balance)->toBe(35000.00)
            ->and($transaction->payment_status)->toBe(PaymentStatus::UNPAID);
    });
});

describe('Milestone 2: Multi-Item Order Intake & Receivables Tracking', function () {

    it('T2-ORD-01: Creates multi-item order with kiloan and satuan items, discounts, and partial down payment (DP)', function () {
        $kiloan = Service::factory()->create([
            'name' => 'Cuci Komplit Reguler',
            'unit' => ServiceUnit::KG,
            'base_price' => 7000.00,
            'min_weight' => 3.00,
        ]);
        $satuan = Service::factory()->create([
            'name' => 'Bedcover Besar',
            'unit' => ServiceUnit::PCS,
            'base_price' => 30000.00,
        ]);
        $customer = Customer::factory()->create([
            'total_orders_count' => 0,
            'total_spend' => 0.00,
            'outstanding_receivables' => 0.00,
        ]);
        $cashier = User::factory()->create(['role' => 'cashier']);

        $serviceTx = app(TransactionService::class);
        $transaction = $serviceTx->createTransaction([
            'customer_id' => $customer->id,
            'items' => [
                [
                    'service_id' => $kiloan->id,
                    'actual_quantity' => 4.00, // 4 * 7,000 = 28,000
                    'perfume_variant' => 'Ocean Fresh',
                ],
                [
                    'service_id' => $satuan->id,
                    'actual_quantity' => 1.00, // 1 * 30,000 = 30,000
                    'item_condition_notes' => 'Ada robek kecil di sudut kanan bawah',
                ],
            ],
            'discount_amount' => 5000.00, // Promo voucher Rp 5,000
            'paid_amount' => 20000.00,    // Down payment (DP) Rp 20,000
            'notes' => 'Pelanggan minta selesai hari Sabtu',
        ], $cashier);

        // Subtotal = 28,000 + 30,000 = 58,000
        // Total = 58,000 - 5,000 = 53,000
        // Outstanding balance = 53,000 - 20,000 = 33,000
        expect((float) $transaction->subtotal_amount)->toBe(58000.00)
            ->and((float) $transaction->discount_amount)->toBe(5000.00)
            ->and((float) $transaction->total_amount)->toBe(53000.00)
            ->and((float) $transaction->paid_amount)->toBe(20000.00)
            ->and((float) $transaction->outstanding_balance)->toBe(33000.00)
            ->and((float) $transaction->change_amount)->toBe(0.00)
            ->and($transaction->payment_status)->toBe(PaymentStatus::PARTIALLY_PAID)
            ->and($transaction->production_status)->toBe(ProductionStatus::RECEIVED)
            ->and($transaction->notes)->toBe('Pelanggan minta selesai hari Sabtu');

        // Verify customer receivables and statistics updated
        $customer->refresh();
        expect((float) $customer->outstanding_receivables)->toBe(33000.00)
            ->and((float) $customer->total_spend)->toBe(53000.00)
            ->and($customer->total_orders_count)->toBe(1);

        // Verify Order alias model works interchangeably
        $order = Order::find($transaction->id);
        expect($order)->not->toBeNull()
            ->and($order->invoice_number)->toBe($transaction->invoice_number)
            ->and($order->items)->toHaveCount(2)
            ->and($order->customer->id)->toBe($customer->id);
    });

    it('T2-ORD-02: Recalculate financial totals updates transaction and payment status', function () {
        $transaction = Transaction::factory()->create([
            'subtotal_amount' => 50000.00,
            'discount_amount' => 0.00,
            'surcharge_amount' => 0.00,
            'tax_amount' => 0.00,
            'total_amount' => 50000.00,
            'paid_amount' => 0.00,
            'outstanding_balance' => 50000.00,
            'payment_status' => PaymentStatus::UNPAID,
        ]);

        TransactionItem::factory()->create([
            'transaction_id' => $transaction->id,
            'subtotal_snapshot' => 50000.00,
        ]);

        // Simulating customer making full payment
        $transaction->paid_amount = 50000.00;
        $transaction->recalculateTotals();
        $transaction->save();

        expect($transaction->payment_status)->toBe(PaymentStatus::PAID)
            ->and((float) $transaction->outstanding_balance)->toBe(0.00)
            ->and($transaction->isFullyPaid())->toBeTrue();
    });
});

describe('Milestone 2: POS API Endpoints', function () {

    it('T2-API-01: Cashier can submit transaction intake via POST /api/pos/transactions', function () {
        $cashier = User::factory()->create(['role' => 'cashier']);
        $customer = Customer::factory()->create();
        $service = Service::factory()->create([
            'base_price' => 10000.00,
            'unit' => ServiceUnit::PCS,
        ]);

        $payload = [
            'customer_id' => $customer->id,
            'items' => [
                [
                    'service_id' => $service->id,
                    'actual_quantity' => 3.00,
                    'perfume_variant' => 'Floral Blossom',
                ],
            ],
            'paid_amount' => 30000.00,
            'notes' => 'Diambil sore hari',
        ];

        $response = $this->actingAs($cashier)->postJson('/api/pos/transactions', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('message', 'Transaksi berhasil dibuat.')
            ->assertJsonStructure([
                'message',
                'transaction' => [
                    'id',
                    'invoice_number',
                    'customer_id',
                    'cashier_id',
                    'production_status',
                    'payment_status',
                    'total_amount',
                    'items',
                    'customer',
                ],
            ]);

        $this->assertDatabaseHas('transactions', [
            'customer_id' => $customer->id,
            'cashier_id' => $cashier->id,
            'total_amount' => 30000.00,
            'payment_status' => PaymentStatus::PAID->value,
            'production_status' => ProductionStatus::RECEIVED->value,
        ]);
    });

    it('T2-API-02: Returns 422 Unprocessable Entity when items array is empty or invalid', function () {
        $cashier = User::factory()->create(['role' => 'cashier']);
        $customer = Customer::factory()->create();

        $response = $this->actingAs($cashier)->postJson('/api/pos/transactions', [
            'customer_id' => $customer->id,
            'items' => [],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['items']);
    });

    it('T2-API-03: Cashier can retrieve transaction details by ID or invoice number via GET /api/pos/transactions/{id}', function () {
        $cashier = User::factory()->create(['role' => 'cashier']);
        $transaction = Transaction::factory()->create([
            'invoice_number' => 'INV-20261114-1234',
        ]);
        TransactionItem::factory()->create(['transaction_id' => $transaction->id]);

        // Access by ID
        $responseById = $this->actingAs($cashier)->getJson("/api/pos/transactions/{$transaction->id}");
        $responseById->assertStatus(200)
            ->assertJsonPath('invoice_number', 'INV-20261114-1234');

        // Access by invoice number
        $responseByInv = $this->actingAs($cashier)->getJson('/api/pos/transactions/INV-20261114-1234');
        $responseByInv->assertStatus(200)
            ->assertJsonPath('id', $transaction->id);

        // Access via alias route /api/pos/orders/{id}
        $responseByAlias = $this->actingAs($cashier)->getJson("/api/pos/orders/{$transaction->id}");
        $responseByAlias->assertStatus(200)
            ->assertJsonPath('invoice_number', 'INV-20261114-1234');
    });

    it('T2-API-04: Operator is forbidden (403) from accessing cashier POS transaction intake', function () {
        $operator = User::factory()->create(['role' => 'operator']);

        $response = $this->actingAs($operator)->postJson('/api/pos/transactions', [
            'customer_id' => 1,
            'items' => [],
        ]);

        $response->assertStatus(403);
    });

    it('T2-API-05: Supports tax, line-item discounts, and debt authorization notes', function () {
        $cashier = User::factory()->create(['role' => 'cashier']);
        $customer = Customer::factory()->create([
            'total_orders_count' => 0,
            'total_spend' => 0.00,
            'outstanding_receivables' => 0.00,
        ]);
        $service = Service::factory()->create([
            'base_price' => 20000.00,
            'unit' => ServiceUnit::PCS,
        ]);

        $payload = [
            'customer_id' => $customer->id,
            'items' => [
                [
                    'service_id' => $service->id,
                    'actual_quantity' => 2.00, // 2 * 20,000 = 40,000
                    'discount_amount' => 5000.00, // Line discount: 40,000 - 5,000 = 35,000
                ],
            ],
            'discount_amount' => 5000.00, // Order discount: 35,000 - 5,000 = 30,000
            'tax_amount' => 3300.00,      // 11% PPN: 30,000 + 3,300 = 33,300
            'paid_amount' => 0.00,
            'is_debt_approved' => true,
            'debt_notes' => 'Diizinkan bawa pulang, bayar via transfer nanti malam',
        ];

        $response = $this->actingAs($cashier)->postJson('/api/pos/transactions', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('transaction.subtotal_amount', '35000.00')
            ->assertJsonPath('transaction.discount_amount', '5000.00')
            ->assertJsonPath('transaction.tax_amount', '3300.00')
            ->assertJsonPath('transaction.total_amount', '33300.00')
            ->assertJsonPath('transaction.outstanding_balance', '33300.00')
            ->assertJsonPath('transaction.is_debt_approved', true)
            ->assertJsonPath('transaction.debt_notes', 'Diizinkan bawa pulang, bayar via transfer nanti malam')
            ->assertJsonPath('transaction.payment_status', PaymentStatus::UNPAID->value);
    });
});

describe('Milestone 2: ProductionStatus and PaymentStatus Enums', function () {

    it('T2-ENUM-01: ProductionStatus enforces 8-state orthogonal workflow transitions and rewash loop', function () {
        $received = ProductionStatus::RECEIVED;
        expect($received->canTransitionTo(ProductionStatus::WASHING))->toBeTrue()
            ->and($received->canTransitionTo(ProductionStatus::WAITING))->toBeTrue()
            ->and($received->canTransitionTo(ProductionStatus::CANCELLED))->toBeTrue()
            ->and($received->canTransitionTo(ProductionStatus::COMPLETED))->toBeFalse();

        // QC state allows proceeding to ready_for_pickup OR rewash loop back to washing
        $qc = ProductionStatus::QC;
        expect($qc->canTransitionTo(ProductionStatus::READY_FOR_PICKUP))->toBeTrue()
            ->and($qc->canTransitionTo(ProductionStatus::WASHING))->toBeTrue() // Rewash loop!
            ->and($qc->canTransitionTo(ProductionStatus::COMPLETED))->toBeFalse();

        // Terminal states cannot transition further
        $completed = ProductionStatus::COMPLETED;
        expect($completed->isTerminal())->toBeTrue()
            ->and($completed->canTransitionTo(ProductionStatus::WASHING))->toBeFalse()
            ->and($completed->canTransitionTo(ProductionStatus::CANCELLED))->toBeFalse();

        $cancelled = ProductionStatus::CANCELLED;
        expect($cancelled->isTerminal())->toBeTrue()
            ->and($cancelled->canTransitionTo(ProductionStatus::RECEIVED))->toBeFalse();
    });

    it('T2-ENUM-02: PaymentStatus provides helper methods for settlement and localized labels', function () {
        expect(PaymentStatus::PAID->isSettled())->toBeTrue()
            ->and(PaymentStatus::PAID->isPaid())->toBeTrue()
            ->and(PaymentStatus::OVERPAID->isSettled())->toBeTrue()
            ->and(PaymentStatus::UNPAID->isSettled())->toBeFalse()
            ->and(PaymentStatus::PARTIALLY_PAID->isSettled())->toBeFalse()
            ->and(PaymentStatus::PAID->label())->toBe('Lunas')
            ->and(PaymentStatus::PARTIALLY_PAID->label())->toBe('Bayar Sebagian (DP)')
            ->and(PaymentStatus::UNPAID->label())->toBe('Belum Lunas');
    });
});
