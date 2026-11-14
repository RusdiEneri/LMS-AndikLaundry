<?php

use App\Enums\PaymentStatus;
use App\Enums\ProductionStatus;
use App\Enums\ServiceUnit;
use App\Models\BusinessSetting;
use App\Models\Customer;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceTier;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Milestone 4: POS Cashier Frontend, Digital Receipt & Printing Tests (R6 & R7)
|--------------------------------------------------------------------------
| Covers:
| - Public digital receipt route GET /receipts/{invoice_number} (unauthenticated allowed)
| - Inertia component props: order, customer, items, payments, store_info, live status tracker
| - 404 for invalid/missing invoice number
| - JSON response format for receipt endpoint when requested
| - POS Controller data supply (categories, services, tiers, perfumes, default customer)
| - Fast customer search and creation endpoints
| - POS order submission with line items and intake payment
|--------------------------------------------------------------------------
*/

describe('Milestone 4: Public Digital Receipt Route & View', function () {

    it('T4-RCP-01: Public digital receipt GET /receipts/{invoice_number} is accessible unauthenticated', function () {
        $customer = Customer::factory()->create([
            'name' => 'Budi Santoso',
            'phone' => '081234567890',
        ]);

        $cashier = User::factory()->create(['role' => 'cashier']);

        $category = ServiceCategory::factory()->create(['name' => 'Kiloan']);
        $service = Service::factory()->for($category, 'category')->create([
            'name' => 'Cuci Komplit Reguler',
            'unit' => ServiceUnit::KG,
            'base_price' => 7000.00,
        ]);

        $transaction = Transaction::factory()->create([
            'invoice_number' => 'INV-20261114-1001',
            'customer_id' => $customer->id,
            'cashier_id' => $cashier->id,
            'production_status' => ProductionStatus::WASHING,
            'payment_status' => PaymentStatus::PAID,
            'subtotal_amount' => 21000.00,
            'total_amount' => 21000.00,
            'paid_amount' => 21000.00,
            'outstanding_balance' => 0.00,
            'staging_rack' => 'RAK-A1',
        ]);

        TransactionItem::factory()->create([
            'transaction_id' => $transaction->id,
            'service_id' => $service->id,
            'item_name_snapshot' => 'Cuci Komplit Reguler',
            'pricing_unit_snapshot' => 'kg',
            'unit_price_snapshot' => 7000.00,
            'actual_quantity' => 2.50,
            'billed_quantity' => 3.00,
            'subtotal_snapshot' => 21000.00,
            'perfume_variant' => 'Sakura',
            'item_condition_notes' => 'Kancing kedua copot',
        ]);

        // Unauthenticated request should return 200 OK
        $response = $this->get('/receipts/INV-20261114-1001');

        $response->assertStatus(200);

        // Verify Inertia component and prop payload
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Receipt/Show')
            ->has('order', fn (Assert $order) => $order
                ->where('invoice_number', 'INV-20261114-1001')
                ->where('production_status', 'washing')
                ->where('payment_status', 'paid')
                ->where('total_amount', 21000)
                ->where('paid_amount', 21000)
                ->where('outstanding_balance', 0)
                ->where('staging_rack', 'RAK-A1')
                ->where('customer.name', 'Budi Santoso')
                ->has('items', 1, fn (Assert $item) => $item
                    ->where('item_name', 'Cuci Komplit Reguler')
                    ->where('pricing_unit', 'kg')
                    ->where('actual_quantity', 2.5)
                    ->where('billed_quantity', 3)
                    ->where('perfume_variant', 'Sakura')
                    ->where('item_condition_notes', 'Kancing kedua copot')
                    ->etc()
                )
                ->etc()
            )
            ->has('store_info', fn (Assert $store) => $store
                ->has('name')
                ->has('phone')
                ->has('address')
                ->has('paper_width')
                ->has('receipt_footer')
                ->etc()
            )
        );
    });

    it('T4-RCP-02: Returns 404 when requested invoice number does not exist', function () {
        $response = $this->get('/receipts/INV-NON-EXISTENT-9999');
        $response->assertStatus(404);

        $jsonResponse = $this->getJson('/receipts/INV-NON-EXISTENT-9999');
        $jsonResponse->assertStatus(404)
            ->assertJsonPath('message', 'Nota transaksi tidak ditemukan.');
    });

    it('T4-RCP-03: Returns JSON response when requested with Accept: application/json', function () {
        $transaction = Transaction::factory()->create([
            'invoice_number' => 'INV-20261114-2002',
            'production_status' => ProductionStatus::READY_FOR_PICKUP,
            'payment_status' => PaymentStatus::PARTIALLY_PAID,
            'total_amount' => 50000.00,
            'paid_amount' => 25000.00,
            'outstanding_balance' => 25000.00,
        ]);

        $response = $this->getJson('/receipts/INV-20261114-2002');

        $response->assertStatus(200)
            ->assertJsonPath('order.invoice_number', 'INV-20261114-2002')
            ->assertJsonPath('order.production_status', 'ready_for_pickup')
            ->assertJsonPath('order.payment_status', 'partially_paid')
            ->assertJsonPath('order.outstanding_balance', 25000)
            ->assertJsonStructure([
                'order' => [
                    'id',
                    'invoice_number',
                    'production_status',
                    'payment_status',
                    'total_amount',
                    'paid_amount',
                    'customer',
                    'items',
                    'payments',
                ],
                'store_info',
            ]);
    });
});

describe('Milestone 4: POS Controller Data Feed & Cashier Operations', function () {

    it('T4-POS-01: PosController provides real catalog, tiers, perfumes, and default customer', function () {
        $cashier = User::factory()->create(['role' => 'cashier']);

        $category = ServiceCategory::factory()->create(['name' => 'Kiloan', 'is_active' => true]);
        Service::factory()->for($category, 'category')->create([
            'name' => 'Cuci Kering Lipat',
            'unit' => ServiceUnit::KG,
            'base_price' => 5000.00,
            'is_active' => true,
        ]);

        $response = $this->actingAs($cashier)->get('/pos');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Pos/Index')
            ->has('categories')
            ->has('services')
            ->has('quick_tenders')
            ->has('turnaround_tiers')
            ->has('perfume_variants')
            ->has('store_info')
            ->has('active_shift')
            ->has('default_customer', fn (Assert $cust) => $cust
                ->where('name', 'Pelanggan Umum')
                ->has('id')
                ->etc()
            )
        );
    });

    it('T4-POS-02: Fast customer creation and lookup via /pos/customers', function () {
        $cashier = User::factory()->create(['role' => 'cashier']);

        // Create new customer via POS
        $createRes = $this->actingAs($cashier)->postJson('/pos/customers', [
            'name' => 'Ratna Sari',
            'phone' => '081399887766',
            'address' => 'Jl. Kenanga No. 5',
            'whatsapp_consent' => true,
        ]);

        $createRes->assertStatus(201)
            ->assertJsonPath('name', 'Ratna Sari')
            ->assertJsonPath('phone_normalized', '6281399887766');

        $customerId = $createRes->json('id');

        // Search created customer
        $searchRes = $this->actingAs($cashier)->getJson('/pos/customers/search?q=Ratna');
        $searchRes->assertStatus(200)
            ->assertJsonFragment(['name' => 'Ratna Sari', 'id' => $customerId]);
    });

    it('T4-POS-03: POS order intake creates transaction with price snapshots and records payment', function () {
        $cashier = User::factory()->create(['role' => 'cashier']);
        $customer = Customer::factory()->create([
            'name' => 'Hendra Setiawan',
            'phone' => '081234567890',
            'outstanding_receivables' => 0.00,
        ]);

        $category = ServiceCategory::factory()->create(['name' => 'Kiloan']);
        $service = Service::factory()->for($category, 'category')->create([
            'name' => 'Cuci Komplit Reguler',
            'unit' => ServiceUnit::KG,
            'base_price' => 7000.00,
            'min_weight' => 3.00,
        ]);

        $tier = ServiceTier::factory()->create([
            'service_id' => $service->id,
            'tier_name' => 'Kilat',
            'tier_code' => 'kilat',
            'multiplier_factor' => 1.00,
            'flat_surcharge' => 10000.00,
            'duration_hours' => 24,
        ]);

        $orderPayload = [
            'customer_id' => $customer->id,
            'items' => [
                [
                    'service_id' => $service->id,
                    'service_tier_id' => $tier->id,
                    'actual_quantity' => 4.00,
                    'perfume_variant' => 'Lavender',
                    'item_condition_notes' => 'Ada kancing longgar',
                ],
            ],
            'paid_amount' => 50000.00,
            'payment_method' => 'cash',
            'notes' => 'Harap selesai besok sore',
        ];

        // 4.00 kg * 7000 = 28,000 + 10,000 tier surcharge = 38,000 total
        // Tender = 50,000, Change = 12,000, Outstanding = 0
        $response = $this->actingAs($cashier)->postJson('/pos/orders', $orderPayload);

        $response->assertStatus(201)
            ->assertJsonPath('message', 'Transaksi berhasil dibuat.')
            ->assertJsonPath('transaction.total_amount', '38000.00')
            ->assertJsonPath('transaction.paid_amount', '50000.00')
            ->assertJsonPath('transaction.change_amount', '12000.00')
            ->assertJsonPath('transaction.outstanding_balance', '0.00')
            ->assertJsonPath('transaction.payment_status', 'overpaid');

        $invoiceNumber = $response->json('transaction.invoice_number');
        expect($invoiceNumber)->toMatch('/^INV-\d{8}-\d{4}$/');

        // Verify public receipt is immediately available for customer
        $receiptRes = $this->getJson("/receipts/{$invoiceNumber}");
        $receiptRes->assertStatus(200)
            ->assertJsonPath('order.invoice_number', $invoiceNumber)
            ->assertJsonPath('order.customer.name', 'Hendra Setiawan')
            ->assertJsonPath('order.items.0.perfume_variant', 'Lavender')
            ->assertJsonPath('order.items.0.item_condition_notes', 'Ada kancing longgar');
    });

    it('T4-REM-01: POS order with turnaround tier and explicit surcharge_amount avoids double surcharge', function () {
        $cashier = User::factory()->cashier()->create();
        $customer = Customer::factory()->create([
            'name' => 'Budi Santoso',
            'phone' => '081234567891',
            'phone_normalized' => '+6281234567891',
            'outstanding_receivables' => 0.00,
        ]);

        $category = ServiceCategory::factory()->create(['name' => 'Kiloan']);
        $service = Service::factory()->for($category, 'category')->create([
            'name' => 'Cuci Komplit Kilat',
            'unit' => ServiceUnit::KG,
            'base_price' => 7000.00,
            'min_weight' => 3.00,
        ]);

        $tier = ServiceTier::factory()->create([
            'service_id' => $service->id,
            'tier_name' => 'Kilat',
            'tier_code' => 'kilat',
            'multiplier_factor' => 1.00,
            'flat_surcharge' => 10000.00,
            'duration_hours' => 24,
        ]);

        // When frontend passes both service_tier_id AND root surcharge_amount: 10000
        $orderPayload = [
            'customer_id' => $customer->id,
            'items' => [
                [
                    'service_id' => $service->id,
                    'service_tier_id' => $tier->id,
                    'actual_quantity' => 4.00,
                    'perfume_variant' => 'Lavender',
                ],
            ],
            'surcharge_amount' => 10000.00,
            'paid_amount' => 38000.00, // 4.00 * 7000 = 28000 + 10000 surcharge = 38000 total
            'payment_method' => 'cash',
        ];

        $response = $this->actingAs($cashier)->postJson('/pos/orders', $orderPayload);

        $response->assertStatus(201)
            ->assertJsonPath('message', 'Transaksi berhasil dibuat.')
            ->assertJsonPath('transaction.surcharge_amount', '10000.00')
            ->assertJsonPath('transaction.total_amount', '38000.00')
            ->assertJsonPath('transaction.paid_amount', '38000.00')
            ->assertJsonPath('transaction.change_amount', '0.00')
            ->assertJsonPath('transaction.outstanding_balance', '0.00')
            ->assertJsonPath('transaction.payment_status', 'paid');
    });

    it('T4-REM-02: POS order with default walk-in customer (Pelanggan Umum) completes checkout smoothly', function () {
        $cashier = User::factory()->cashier()->create();
        $walkInCustomer = Customer::firstOrCreate(
            ['name' => 'Pelanggan Umum'],
            [
                'phone' => '080000000000',
                'phone_normalized' => '+6280000000000',
                'address' => 'Pelanggan Walk-In',
                'whatsapp_consent' => false,
            ]
        );

        $category = ServiceCategory::factory()->create(['name' => 'Kiloan']);
        $service = Service::factory()->for($category, 'category')->create([
            'name' => 'Cuci Kiloan Reguler',
            'unit' => ServiceUnit::KG,
            'base_price' => 6000.00,
            'min_weight' => 3.00,
        ]);

        // (1) Test with explicit walk-in customer id passed from POS default_customer prop
        $orderPayload1 = [
            'customer_id' => $walkInCustomer->id,
            'items' => [
                [
                    'service_id' => $service->id,
                    'actual_quantity' => 3.00,
                    'perfume_variant' => 'Ocean Fresh',
                ],
            ],
            'paid_amount' => 18000.00, // 3 * 6000 = 18000
            'payment_method' => 'cash',
        ];

        $res1 = $this->actingAs($cashier)->postJson('/pos/orders', $orderPayload1);
        $res1->assertStatus(201)
            ->assertJsonPath('transaction.customer.id', $walkInCustomer->id)
            ->assertJsonPath('transaction.customer.name', 'Pelanggan Umum')
            ->assertJsonPath('transaction.total_amount', '18000.00')
            ->assertJsonPath('transaction.outstanding_balance', '0.00')
            ->assertJsonPath('transaction.payment_status', 'paid');

        // (2) Test with null/omitted customer_id (fallback to Pelanggan Umum)
        $orderPayload2 = [
            'customer_id' => null,
            'items' => [
                [
                    'service_id' => $service->id,
                    'actual_quantity' => 3.00,
                    'perfume_variant' => 'Lavender',
                ],
            ],
            'paid_amount' => 18000.00,
            'payment_method' => 'qris',
        ];

        $res2 = $this->actingAs($cashier)->postJson('/pos/orders', $orderPayload2);
        $res2->assertStatus(201)
            ->assertJsonPath('transaction.customer.id', $walkInCustomer->id)
            ->assertJsonPath('transaction.customer.name', 'Pelanggan Umum')
            ->assertJsonPath('transaction.total_amount', '18000.00')
            ->assertJsonPath('transaction.outstanding_balance', '0.00')
            ->assertJsonPath('transaction.payment_status', 'paid');
    });

    it('T4-REM-03: Multi-item POS order with turnaround tier avoids flat surcharge accumulation defect', function () {
        $cashier = User::factory()->cashier()->create();
        $customer = Customer::factory()->create([
            'name' => 'Ahmad Dahlan',
            'phone' => '081234567899',
            'phone_normalized' => '+6281234567899',
            'outstanding_receivables' => 0.00,
        ]);

        $category = ServiceCategory::factory()->create(['name' => 'Kiloan']);

        $service1 = Service::factory()->for($category, 'category')->create([
            'name' => 'Cuci Kering Setrika',
            'unit' => ServiceUnit::KG,
            'base_price' => 7000.00,
            'min_weight' => 3.00,
        ]);

        $service2 = Service::factory()->for($category, 'category')->create([
            'name' => 'Cuci Kering Saja',
            'unit' => ServiceUnit::KG,
            'base_price' => 5000.00,
            'min_weight' => 3.00,
        ]);

        $tier1 = ServiceTier::factory()->create([
            'service_id' => $service1->id,
            'tier_name' => 'Kilat 24 Jam',
            'tier_code' => 'kilat',
            'multiplier_factor' => 1.00,
            'flat_surcharge' => 10000.00,
            'duration_hours' => 24,
        ]);

        $tier2 = ServiceTier::factory()->create([
            'service_id' => $service2->id,
            'tier_name' => 'Kilat 24 Jam',
            'tier_code' => 'kilat',
            'multiplier_factor' => 1.00,
            'flat_surcharge' => 10000.00,
            'duration_hours' => 24,
        ]);

        // 2 items: 3kg @ 7,000 = 21,000 and 3kg @ 5,000 = 15,000 -> subtotal = 36,000
        // Both items have Kilat tier (flat surcharge 10,000 each)
        // Order-level surcharge_amount passed is 10000.00
        // Total should be 36,000 + 10,000 = 46,000.00 (NOT 56,000.00 from 20,000 surcharge!)
        $orderPayload = [
            'customer_id' => $customer->id,
            'items' => [
                [
                    'service_id' => $service1->id,
                    'service_tier_id' => $tier1->id,
                    'actual_quantity' => 3.00,
                    'perfume_variant' => 'Lavender',
                ],
                [
                    'service_id' => $service2->id,
                    'service_tier_id' => $tier2->id,
                    'actual_quantity' => 3.00,
                    'perfume_variant' => 'Ocean Fresh',
                ],
            ],
            'surcharge_amount' => 10000.00,
            'paid_amount' => 46000.00,
            'payment_method' => 'cash',
        ];

        $response = $this->actingAs($cashier)->postJson('/pos/orders', $orderPayload);

        $response->assertStatus(201)
            ->assertJsonPath('message', 'Transaksi berhasil dibuat.')
            ->assertJsonPath('transaction.surcharge_amount', '10000.00')
            ->assertJsonPath('transaction.total_amount', '46000.00')
            ->assertJsonPath('transaction.paid_amount', '46000.00')
            ->assertJsonPath('transaction.outstanding_balance', '0.00')
            ->assertJsonPath('transaction.payment_status', 'paid');
    });
});
