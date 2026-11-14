<?php

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\ProductionStatus;
use App\Enums\ServiceUnit;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceTier;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\TransactionStatusHistory;
use App\Models\User;
use App\Services\PaymentService;
use App\Services\ProductionStatusService;
use App\Services\TransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Phase 2 (Core POS): End-to-End Scenario & Pairwise Integration Test Suite
|--------------------------------------------------------------------------
| Covers:
| - Tier 3: Pairwise Cross-Feature Interactions (T3-POS-01 to T3-POS-10)
| - Tier 4: Real-World Application Scenarios (T4-SCENARIO-01 to T4-SCENARIO-05)
|
| Authoritative Sources:
| - docs/PRD.md (FEAT-101 to FEAT-605, User Stories US-01 to US-06)
| - docs/database-schema.md (Tables 5 to 11)
| - docs/financial-logic.md (§2 & §3)
| - TEST_INFRA.md (Coverage matrix & scenario definitions)
|--------------------------------------------------------------------------
*/

describe('Tier 3: Pairwise Cross-Feature Interactions', function () {

    it('T3-POS-01: Customer Intake x Price Snapshotting (F1, F2, F10)', function () {
        $cashier = User::factory()->cashier()->create();

        // 1. Create customer with non-E.164 phone
        $customer = Customer::create([
            'name' => 'Agus Pratama',
            'phone' => '081234567890',
            'whatsapp_consent' => true,
        ]);

        expect($customer->phone)->toBe('081234567890')
            ->and($customer->phone_normalized)->toBe('6281234567890');

        // 2. Master service with base_price Rp 10,000
        $category = ServiceCategory::factory()->create(['name' => 'Satuan']);
        $service = Service::factory()->for($category, 'category')->create([
            'name' => 'Kemeja Katun',
            'unit' => ServiceUnit::PCS,
            'base_price' => 10000.00,
        ]);

        // 3. Create transaction item with price snapshot
        $transactionService = app(TransactionService::class);
        $transaction = $transactionService->createTransaction([
            'customer_id' => $customer->id,
            'items' => [
                [
                    'service_id' => $service->id,
                    'actual_quantity' => 2.00,
                ],
            ],
            'paid_amount' => 20000.00,
            'payment_method' => 'cash',
        ], $cashier);

        $item = $transaction->items()->first();
        expect((float) $item->unit_price_snapshot)->toBe(10000.00)
            ->and((float) $item->subtotal_snapshot)->toBe(20000.00)
            ->and((float) $transaction->total_amount)->toBe(20000.00);

        // 4. Update catalog service base_price to Rp 20,000 (100% price hike)
        $service->update(['base_price' => 20000.00, 'name' => 'Kemeja Katun Premium']);

        // 5. Verify transaction item preserves snapshotted price 10,000 and total matches historical price
        $reloadedItem = TransactionItem::findOrFail($item->id);
        $reloadedTx = Transaction::findOrFail($transaction->id);

        expect((float) $reloadedItem->unit_price_snapshot)->toBe(10000.00)
            ->and((float) $reloadedItem->subtotal_snapshot)->toBe(20000.00)
            ->and($reloadedItem->item_name_snapshot)->toBe('Kemeja Katun')
            ->and((float) $reloadedTx->total_amount)->toBe(20000.00);
    });

    it('T3-POS-02: Kiloan Min-Weight x Turnaround Surcharge x Perfume Variant (F6, F8, F9, F12)', function () {
        $cashier = User::factory()->cashier()->create();
        $customer = Customer::factory()->create();

        // Kiloan service with min_weight 3.0 kg, base_price Rp 7,000
        $category = ServiceCategory::factory()->create(['name' => 'Kiloan']);
        $service = Service::factory()->for($category, 'category')->create([
            'name' => 'Cuci Kering Setrika',
            'unit' => ServiceUnit::KG,
            'base_price' => 7000.00,
            'min_weight' => 3.00,
        ]);

        // Turnaround tier 'express' with flat surcharge Rp 20,000
        $tier = ServiceTier::factory()->create([
            'service_id' => $service->id,
            'tier_name' => 'Express',
            'tier_code' => 'express',
            'multiplier_factor' => 1.00,
            'flat_surcharge' => 20000.00,
            'duration_hours' => 12,
        ]);

        // Actual weight 1.5 kg (< 3.0 kg min), Perfume variant 'Lavender'
        $transactionService = app(TransactionService::class);
        $transaction = $transactionService->createTransaction([
            'customer_id' => $customer->id,
            'items' => [
                [
                    'service_id' => $service->id,
                    'service_tier_id' => $tier->id,
                    'actual_quantity' => 1.50,
                    'perfume_variant' => 'Lavender',
                ],
            ],
            'paid_amount' => 41000.00,
        ], $cashier);

        $item = $transaction->items()->first();

        // Verify billed_quantity = 3.0 kg, item subtotal = 21,000, surcharge = 20,000, grand total = 41,000, perfume = 'Lavender'
        expect((float) $item->actual_quantity)->toBe(1.50)
            ->and((float) $item->billed_quantity)->toBe(3.00)
            ->and((float) $item->unit_price_snapshot)->toBe(7000.00)
            ->and((float) $item->subtotal_snapshot)->toBe(21000.00)
            ->and($item->perfume_variant)->toBe('Lavender')
            ->and((float) $transaction->subtotal_amount)->toBe(21000.00)
            ->and((float) $transaction->surcharge_amount)->toBe(20000.00)
            ->and((float) $transaction->total_amount)->toBe(41000.00);
    });

    it('T3-POS-03: Satuan Intake with Defect Notes x Down Payment x Piutang (F7, F14, F15)', function () {
        $cashier = User::factory()->cashier()->create();
        $customer = Customer::factory()->create([
            'outstanding_receivables' => 0.00,
        ]);

        // Satuan item (Jas Pria, 35,000) with condition notes
        $category = ServiceCategory::factory()->create(['name' => 'Satuan']);
        $service = Service::factory()->for($category, 'category')->create([
            'name' => 'Jas Pria',
            'unit' => ServiceUnit::PCS,
            'base_price' => 35000.00,
        ]);

        $defectNote = 'Kancing lepas 1, noda minyak di kerah';

        // Down payment recorded: 15,000 against 35,000 total
        $transactionService = app(TransactionService::class);
        $transaction = $transactionService->createTransaction([
            'customer_id' => $customer->id,
            'items' => [
                [
                    'service_id' => $service->id,
                    'actual_quantity' => 1.00,
                    'item_condition_notes' => $defectNote,
                ],
            ],
            'paid_amount' => 15000.00,
            'payment_method' => 'cash',
        ], $cashier);

        $item = $transaction->items()->first();

        // Verify outstanding_balance = 20,000, payment_status = partial, defect notes preserved in snapshot
        expect((float) $transaction->total_amount)->toBe(35000.00)
            ->and((float) $transaction->paid_amount)->toBe(15000.00)
            ->and((float) $transaction->outstanding_balance)->toBe(20000.00)
            ->and($transaction->payment_status)->toBe(PaymentStatus::PARTIALLY_PAID)
            ->and($item->item_condition_notes)->toBe($defectNote);

        // Verify customer outstanding_receivables incremented by 20,000
        $customer->refresh();
        expect((float) $customer->outstanding_receivables)->toBe(20000.00);
    });

    it('T3-POS-04: Multi-Tender Split Payment x Balance Settlement (F13, F15)', function () {
        $cashier = User::factory()->cashier()->create();
        $customer = Customer::factory()->create();

        $category = ServiceCategory::factory()->create(['name' => 'Satuan']);
        $service = Service::factory()->for($category, 'category')->create([
            'name' => 'Bed Cover King',
            'unit' => ServiceUnit::PCS,
            'base_price' => 50000.00,
        ]);

        // Transaction total 100,000 (2 pcs @ 50,000)
        $transactionService = app(TransactionService::class);
        $transaction = $transactionService->createTransaction([
            'customer_id' => $customer->id,
            'items' => [
                [
                    'service_id' => $service->id,
                    'actual_quantity' => 2.00,
                ],
            ],
            'paid_amount' => 0.00,
        ], $cashier);

        expect((float) $transaction->total_amount)->toBe(100000.00)
            ->and((float) $transaction->outstanding_balance)->toBe(100000.00)
            ->and($transaction->payment_status)->toBe(PaymentStatus::UNPAID);

        $paymentService = app(PaymentService::class);

        // Payment 1: Cash 40,000. Balance = 60,000, status = partial
        $payment1 = $paymentService->recordPayment($transaction, [
            'payment_method' => PaymentMethod::CASH,
            'amount' => 40000.00,
            'tender_amount' => 50000.00, // Cash tender 50k -> change 10k
        ], $cashier);

        $transaction->refresh();
        expect((float) $payment1->amount)->toBe(40000.00)
            ->and((float) $payment1->tender_amount)->toBe(50000.00)
            ->and((float) $payment1->change_amount)->toBe(10000.00)
            ->and((float) $transaction->paid_amount)->toBe(40000.00)
            ->and((float) $transaction->change_amount)->toBe(10000.00)
            ->and((float) $transaction->outstanding_balance)->toBe(60000.00)
            ->and($transaction->payment_status)->toBe(PaymentStatus::PARTIALLY_PAID);

        // Payment 2: QRIS 60,000. Balance = 0, status = paid
        $payment2 = $paymentService->recordPayment($transaction, [
            'payment_method' => PaymentMethod::QRIS,
            'amount' => 60000.00,
        ], $cashier);

        $transaction->refresh();
        expect((float) $payment2->amount)->toBe(60000.00)
            ->and((float) $payment2->change_amount)->toBe(0.00)
            ->and((float) $transaction->paid_amount)->toBe(100000.00)
            ->and((float) $transaction->outstanding_balance)->toBe(0.00)
            ->and($transaction->payment_status)->toBe(PaymentStatus::PAID)
            ->and($transaction->payments()->count())->toBe(2);
    });

    it('T3-POS-05: Counter Release Guard x 8-State Production Workflow (F16, F17)', function () {
        $operator = User::factory()->operator()->create();
        $fsmService = app(ProductionStatusService::class);

        // Transaction with payment_status = unpaid, is_debt_approved = false
        $transaction = Transaction::factory()->create([
            'production_status' => ProductionStatus::RECEIVED,
            'payment_status' => PaymentStatus::UNPAID,
            'total_amount' => 60000.00,
            'paid_amount' => 0.00,
            'outstanding_balance' => 60000.00,
            'is_debt_approved' => false,
        ]);

        // Transition production FSM up to ready_for_pickup
        $fsmService->transition($transaction, ProductionStatus::WAITING, $operator);
        $fsmService->transition($transaction, ProductionStatus::WASHING, $operator);
        $fsmService->transition($transaction, ProductionStatus::DRYING, $operator);
        $fsmService->transition($transaction, ProductionStatus::IRONING, $operator);
        $fsmService->transition($transaction, ProductionStatus::QC, $operator);
        $fsmService->transition($transaction, ProductionStatus::READY_FOR_PICKUP, $operator, 'Selesai setrika', [
            'staging_rack' => 'RACK-SEC-01',
        ]);

        // Verify ProductionStatusService::canRelease($tx) returns false
        expect($transaction->production_status)->toBe(ProductionStatus::READY_FOR_PICKUP)
            ->and($fsmService->canRelease($transaction))->toBeFalse();

        // Attempt transition to completed throws exception
        expect(function () use ($fsmService, $transaction, $operator) {
            $fsmService->transition($transaction, ProductionStatus::COMPLETED, $operator);
        })->toThrow(InvalidArgumentException::class, 'Cannot release order: payment has not been settled and credit/debt is not approved.');

        expect($transaction->fresh()->production_status)->toBe(ProductionStatus::READY_FOR_PICKUP);
    });

    it('T3-POS-06: Production FSM Audit Trail x QC Rewash Loop (F17, F18, F19)', function () {
        $operator = User::factory()->operator()->create();
        $fsmService = app(ProductionStatusService::class);

        $transaction = Transaction::factory()->create([
            'production_status' => ProductionStatus::QC,
            'payment_status' => PaymentStatus::PAID,
            'rewash_count' => 0,
        ]);

        // QC inspection fails: transition with target washing and notes "Noda kerah belum bersih"
        $fsmService->transition($transaction, ProductionStatus::WASHING, $operator, 'Noda kerah belum bersih');

        // Verify rewash_count increments to 1
        expect($transaction->production_status)->toBe(ProductionStatus::WASHING)
            ->and($transaction->rewash_count)->toBe(1);

        // FSM moves washing -> drying -> ironing -> qc
        $fsmService->transition($transaction, ProductionStatus::DRYING, $operator, 'Pengeringan rewash cycle');
        $fsmService->transition($transaction, ProductionStatus::IRONING, $operator, 'Setrika ulang');
        $fsmService->transition($transaction, ProductionStatus::QC, $operator, 'QC inspeksi kedua');

        // Second pass moves to ready_for_pickup with rack coordinate
        $fsmService->transition($transaction, ProductionStatus::READY_FOR_PICKUP, $operator, 'Inspeksi kedua lolos', [
            'staging_rack' => 'RACK-REWASH-02',
        ]);

        expect($transaction->production_status)->toBe(ProductionStatus::READY_FOR_PICKUP)
            ->and($transaction->staging_rack)->toBe('RACK-REWASH-02');

        // Verify all transitions logged in TransactionStatusHistory
        $histories = $transaction->statusHistories()->orderBy('id')->get();
        expect($histories->count())->toBe(5)
            ->and($histories[0]->from_status)->toBe(ProductionStatus::QC)
            ->and($histories[0]->to_status)->toBe(ProductionStatus::WASHING)
            ->and($histories[0]->notes)->toBe('Noda kerah belum bersih')
            ->and($histories[0]->user_id)->toBe($operator->id)
            ->and($histories[4]->to_status)->toBe(ProductionStatus::READY_FOR_PICKUP)
            ->and($histories[4]->metadata['staging_rack'])->toBe('RACK-REWASH-02');
    });

    it('T3-POS-07: Finished Laundry Staging x Coordinate Assignment (F17, F20)', function () {
        $operator = User::factory()->operator()->create();
        $fsmService = app(ProductionStatusService::class);

        $transaction = Transaction::factory()->create([
            'production_status' => ProductionStatus::QC,
            'staging_rack' => null,
        ]);

        // Transition to ready_for_pickup requires non-empty coordinate (empty rejected)
        expect(function () use ($fsmService, $transaction, $operator) {
            $fsmService->transition($transaction, ProductionStatus::READY_FOR_PICKUP, $operator, 'Packing selesai');
        })->toThrow(InvalidArgumentException::class, 'A valid staging rack coordinate is required when marking order ready for pickup.');

        // Whitespace string is rejected
        expect(function () use ($fsmService, $transaction, $operator) {
            $fsmService->transition($transaction, ProductionStatus::READY_FOR_PICKUP, $operator, 'Packing selesai', [
                'staging_rack' => "   \t  \n",
            ]);
        })->toThrow(InvalidArgumentException::class, 'A valid staging rack coordinate is required when marking order ready for pickup.');

        expect($transaction->fresh()->production_status)->toBe(ProductionStatus::QC)
            ->and($transaction->fresh()->staging_rack)->toBeNull();

        // Valid coordinate persists to $transaction->staging_rack (and trims whitespace)
        $fsmService->transition($transaction, ProductionStatus::READY_FOR_PICKUP, $operator, 'Disimpan di rak B', [
            'staging_rack' => '  RACK-B-04  ',
        ]);

        expect($transaction->production_status)->toBe(ProductionStatus::READY_FOR_PICKUP)
            ->and($transaction->staging_rack)->toBe('RACK-B-04');
    });

    it('T3-POS-08: Invoice Number Formatting x Thermal Printing x WhatsApp Link (F11, F21, F23)', function () {
        $cashier = User::factory()->cashier()->create();
        $customer = Customer::create([
            'name' => 'Dewi Lestari',
            'phone' => '081233445566',
            'whatsapp_consent' => true,
        ]);

        $category = ServiceCategory::factory()->create(['name' => 'Kiloan']);
        $service = Service::factory()->for($category, 'category')->create([
            'name' => 'Cuci Kering Lipat',
            'unit' => ServiceUnit::KG,
            'base_price' => 6000.00,
            'min_weight' => 3.00,
        ]);

        $transactionService = app(TransactionService::class);
        $transaction = $transactionService->createTransaction([
            'customer_id' => $customer->id,
            'items' => [
                [
                    'service_id' => $service->id,
                    'actual_quantity' => 3.00,
                ],
            ],
            'paid_amount' => 18000.00,
            'payment_method' => 'cash',
        ], $cashier);

        // 1. Verify invoice number matches regex /^INV-\d{8}-\d{4}$/
        expect($transaction->invoice_number)->toMatch('/^INV-\d{8}-\d{4}$/');

        // 2. Access GET /receipts/{invoice_number} without auth: returns 200 with order and store info props
        $response = $this->get("/receipts/{$transaction->invoice_number}");
        $response->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Receipt/Show')
                ->where('order.invoice_number', $transaction->invoice_number)
                ->where('order.customer.name', 'Dewi Lestari')
                ->where('order.customer.phone_normalized', '6281233445566')
                ->has('store_info.paper_width')
                ->has('store_info.name')
                ->has('store_info.address')
            );

        // 3. Verify wa.me URL format
        $normalizedPhone = $customer->fresh()->phone_normalized;
        $encodedInvoice = urlencode($transaction->invoice_number);
        $waLink = "https://wa.me/{$normalizedPhone}?text=No.+Nota%3A+{$encodedInvoice}";

        expect($waLink)->toStartWith("https://wa.me/{$normalizedPhone}?text=")
            ->and(urldecode($waLink))->toContain($transaction->invoice_number);
    });

    it('T3-POS-09: Owner Debt Override for Unpaid Release (F15, F16, F17)', function () {
        $owner = User::factory()->owner()->create();
        $fsmService = app(ProductionStatusService::class);

        // Unpaid transaction with balance > 0
        $transaction = Transaction::factory()->create([
            'production_status' => ProductionStatus::READY_FOR_PICKUP,
            'payment_status' => PaymentStatus::UNPAID,
            'total_amount' => 85000.00,
            'paid_amount' => 0.00,
            'outstanding_balance' => 85000.00,
            'is_debt_approved' => false,
            'staging_rack' => 'RACK-VIP-01',
        ]);

        expect($fsmService->canRelease($transaction))->toBeFalse();

        // Authorized debt: set is_debt_approved = true, debt_notes = "Disetujui Owner - Bayar akhir bulan"
        $transaction->update([
            'is_debt_approved' => true,
            'debt_notes' => 'Disetujui Owner - Bayar akhir bulan',
            'approved_by_id' => $owner->id,
        ]);

        // Verify ProductionStatusService::canRelease($tx) returns true. Transition to completed succeeds.
        expect($fsmService->canRelease($transaction))->toBeTrue();

        $fsmService->transition($transaction, ProductionStatus::COMPLETED, $owner, 'Serah terima diotorisasi owner');

        expect($transaction->production_status)->toBe(ProductionStatus::COMPLETED)
            ->and($transaction->actual_completion_at)->not->toBeNull()
            ->and($transaction->picked_up_at)->not->toBeNull();
    });

    it('T3-POS-10: UU PDP Consent Revocation x Public Digital Receipt (F4, F22)', function () {
        $cashier = User::factory()->cashier()->create();

        // Customer with whatsapp_consent = false
        $customer = Customer::create([
            'name' => 'Maya Anggraini',
            'phone' => '087811223344',
            'whatsapp_consent' => false,
        ]);

        // Verify customer model correctly stores false consent
        expect($customer->whatsapp_consent)->toBeFalse()
            ->and($customer->consent_at)->toBeNull();

        $category = ServiceCategory::factory()->create(['name' => 'Kiloan']);
        $service = Service::factory()->for($category, 'category')->create([
            'name' => 'Cuci Kering Lipat',
            'unit' => ServiceUnit::KG,
            'base_price' => 7000.00,
            'min_weight' => 3.00,
        ]);

        $transactionService = app(TransactionService::class);
        $transaction = $transactionService->createTransaction([
            'customer_id' => $customer->id,
            'items' => [
                [
                    'service_id' => $service->id,
                    'actual_quantity' => 3.00,
                ],
            ],
            'paid_amount' => 21000.00,
        ], $cashier);

        // Verify GET /receipts/{invoice_number} still accessible (public web receipt independent of WA consent)
        $response = $this->get("/receipts/{$transaction->invoice_number}");
        $response->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Receipt/Show')
                ->where('order.invoice_number', $transaction->invoice_number)
                ->where('order.customer.name', 'Maya Anggraini')
            );

        $jsonResponse = $this->getJson("/receipts/{$transaction->invoice_number}");
        $jsonResponse->assertStatus(200)
            ->assertJsonPath('order.invoice_number', $transaction->invoice_number);
    });
});

describe('Tier 4: Real-World Application Scenarios', function () {

    it('T4-SCENARIO-01: Standard Kiloan Intake with Below-Min Weight (F1, F2, F6, F8, F9, F10, F11, F13, F21)', function () {
        $cashier = User::factory()->cashier()->create();

        // 1. Customer registered (081299990001 -> 6281299990001)
        $customer = Customer::create([
            'name' => 'Rina Marlina',
            'phone' => '081299990001',
            'whatsapp_consent' => true,
        ]);
        expect($customer->phone_normalized)->toBe('6281299990001');

        // 2. Kiloan laundry: min 3.0 kg, Rp 7.000/kg
        $category = ServiceCategory::factory()->create(['name' => 'Kiloan']);
        $service = Service::factory()->for($category, 'category')->create([
            'name' => 'Cuci Komplit Reguler',
            'unit' => ServiceUnit::KG,
            'base_price' => 7000.00,
            'min_weight' => 3.00,
        ]);

        // Turnaround Kilat (+Rp 10.000 flat surcharge)
        $tierKilat = ServiceTier::factory()->create([
            'service_id' => $service->id,
            'tier_name' => 'Kilat 24 Jam',
            'tier_code' => 'kilat',
            'multiplier_factor' => 1.00,
            'flat_surcharge' => 10000.00,
            'duration_hours' => 24,
        ]);

        // Intake: 1.8 kg actual weight, Parfum Ocean Fresh
        // Expected Math:
        // - Billed Qty: max(1.8, 3.0) = 3.0 kg
        // - Subtotal: 3.0 * 7,000 = Rp 21,000
        // - Surcharge: Rp 10,000
        // - Grand Total: 21,000 + 10,000 = Rp 31,000
        // - Cash payment Rp 35,000 -> change Rp 4,000, balance Rp 0, status paid
        $transactionService = app(TransactionService::class);
        $transaction = $transactionService->createTransaction([
            'customer_id' => $customer->id,
            'items' => [
                [
                    'service_id' => $service->id,
                    'service_tier_id' => $tierKilat->id,
                    'actual_quantity' => 1.80,
                    'perfume_variant' => 'Ocean Fresh',
                ],
            ],
            'paid_amount' => 35000.00,
            'payment_method' => 'cash',
        ], $cashier);

        $item = $transaction->items()->first();

        expect((float) $item->actual_quantity)->toBe(1.80)
            ->and((float) $item->billed_quantity)->toBe(3.00)
            ->and((float) $item->unit_price_snapshot)->toBe(7000.00)
            ->and((float) $item->subtotal_snapshot)->toBe(21000.00)
            ->and($item->perfume_variant)->toBe('Ocean Fresh')
            ->and((float) $transaction->subtotal_amount)->toBe(21000.00)
            ->and((float) $transaction->surcharge_amount)->toBe(10000.00)
            ->and((float) $transaction->total_amount)->toBe(31000.00)
            ->and((float) $transaction->paid_amount)->toBe(35000.00)
            ->and((float) $transaction->change_amount)->toBe(4000.00)
            ->and((float) $transaction->outstanding_balance)->toBe(0.00)
            ->and($transaction->payment_status->isPaid())->toBeTrue();

        // Verify invoice generated
        expect($transaction->invoice_number)->toMatch('/^INV-\d{8}-\d{4}$/');

        // Receipt route loads order correctly
        $receiptResponse = $this->get("/receipts/{$transaction->invoice_number}");
        $receiptResponse->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Receipt/Show')
                ->where('order.invoice_number', $transaction->invoice_number)
                ->where('order.total_amount', 31000)
                ->where('order.paid_amount', 35000)
                ->where('order.change_amount', 4000)
                ->where('order.outstanding_balance', 0)
                ->where('order.items.0.actual_quantity', 1.8)
                ->where('order.items.0.billed_quantity', 3)
                ->where('order.items.0.perfume_variant', 'Ocean Fresh')
            );
    });

    it('T4-SCENARIO-02: High-Value Satuan Laundry with Defect Notes & DP (F1, F3, F7, F10, F11, F14, F15, F23)', function () {
        $cashier = User::factory()->cashier()->create();

        // 1. Customer registration & lookup
        $customer = Customer::create([
            'name' => 'Haji Sulaiman',
            'phone' => '085211223344',
            'outstanding_receivables' => 0.00,
            'whatsapp_consent' => true,
        ]);

        $searchResult = Customer::search('Sulaiman')->get();
        expect($searchResult->contains('id', $customer->id))->toBeTrue();

        // 2. Master Satuan services:
        // - Jas Pria @ Rp 35,000
        // - Gaun Sutra @ Rp 50,000
        $category = ServiceCategory::factory()->create(['name' => 'Satuan Premium']);
        $serviceJas = Service::factory()->for($category, 'category')->create([
            'name' => 'Jas Pria Formal',
            'unit' => ServiceUnit::PCS,
            'base_price' => 35000.00,
        ]);
        $serviceGaun = Service::factory()->for($category, 'category')->create([
            'name' => 'Gaun Sutra Pesta',
            'unit' => ServiceUnit::PCS,
            'base_price' => 50000.00,
        ]);

        // Order Intake:
        // Item 1: 2 pcs Jas Pria @ 35,000 = Rp 70,000 with note "Kancing saku lepas"
        // Item 2: 1 pc Gaun Sutra @ 50,000 = Rp 50,000 with note "Bahan sensitif"
        // Total = 70,000 + 50,000 = Rp 120,000
        // Down Payment via Bank Transfer: Rp 50,000
        // Piutang = Rp 70,000
        $transactionService = app(TransactionService::class);
        $transaction = $transactionService->createTransaction([
            'customer_id' => $customer->id,
            'items' => [
                [
                    'service_id' => $serviceJas->id,
                    'actual_quantity' => 2.00,
                    'item_condition_notes' => 'Kancing saku lepas',
                ],
                [
                    'service_id' => $serviceGaun->id,
                    'actual_quantity' => 1.00,
                    'item_condition_notes' => 'Bahan sensitif',
                ],
            ],
            'paid_amount' => 50000.00,
            'payment_method' => 'bank_transfer',
        ], $cashier);

        expect((float) $transaction->total_amount)->toBe(120000.00)
            ->and((float) $transaction->paid_amount)->toBe(50000.00)
            ->and((float) $transaction->outstanding_balance)->toBe(70000.00)
            ->and($transaction->payment_status)->toBe(PaymentStatus::PARTIALLY_PAID)
            ->and($transaction->items()->count())->toBe(2);

        $items = $transaction->items()->get();
        expect($items[0]->item_condition_notes)->toBe('Kancing saku lepas')
            ->and((float) $items[0]->subtotal_snapshot)->toBe(70000.00)
            ->and($items[1]->item_condition_notes)->toBe('Bahan sensitif')
            ->and((float) $items[1]->subtotal_snapshot)->toBe(50000.00);

        // Customer outstanding_receivables increases by Rp 70,000
        $customer->refresh();
        expect((float) $customer->outstanding_receivables)->toBe(70000.00);

        // Invoice formatting & WhatsApp link validation
        expect($transaction->invoice_number)->toMatch('/^INV-\d{8}-\d{4}$/');
        $waLink = "https://wa.me/{$customer->phone_normalized}?text=".urlencode("Nota: {$transaction->invoice_number}, Sisa: Rp 70.000");
        expect($waLink)->toContain('https://wa.me/6285211223344?text=')
            ->and(urldecode($waLink))->toContain($transaction->invoice_number);
    });

    it('T4-SCENARIO-03: Split Payment (Cash + QRIS) & Pickup Handover (F11, F13, F15, F16, F17, F20)', function () {
        $cashier = User::factory()->cashier()->create();
        $operator = User::factory()->operator()->create();
        $customer = Customer::factory()->create();

        $category = ServiceCategory::factory()->create(['name' => 'Satuan']);
        $service = Service::factory()->for($category, 'category')->create([
            'name' => 'Selimut Bulu',
            'unit' => ServiceUnit::PCS,
            'base_price' => 40000.00,
        ]);

        // Intake total Rp 80.000 (2 pcs @ 40,000), initial Cash payment Rp 30.000 (status partial)
        $transactionService = app(TransactionService::class);
        $transaction = $transactionService->createTransaction([
            'customer_id' => $customer->id,
            'items' => [
                [
                    'service_id' => $service->id,
                    'actual_quantity' => 2.00,
                ],
            ],
            'paid_amount' => 30000.00,
            'payment_method' => 'cash',
        ], $cashier);

        expect((float) $transaction->total_amount)->toBe(80000.00)
            ->and((float) $transaction->paid_amount)->toBe(30000.00)
            ->and((float) $transaction->outstanding_balance)->toBe(50000.00)
            ->and($transaction->payment_status)->toBe(PaymentStatus::PARTIALLY_PAID)
            ->and($transaction->production_status)->toBe(ProductionStatus::RECEIVED);

        // Production progresses: received -> waiting -> washing -> drying -> ironing -> qc -> ready_for_pickup
        $fsmService = app(ProductionStatusService::class);
        $fsmService->transition($transaction, ProductionStatus::WAITING, $operator);
        $fsmService->transition($transaction, ProductionStatus::WASHING, $operator);
        $fsmService->transition($transaction, ProductionStatus::DRYING, $operator);
        $fsmService->transition($transaction, ProductionStatus::IRONING, $operator);
        $fsmService->transition($transaction, ProductionStatus::QC, $operator);
        $fsmService->transition($transaction, ProductionStatus::READY_FOR_PICKUP, $operator, 'Selesai disetrika dan dipacking', [
            'staging_rack' => 'RACK-C-02',
        ]);

        expect($transaction->production_status)->toBe(ProductionStatus::READY_FOR_PICKUP)
            ->and($transaction->staging_rack)->toBe('RACK-C-02');

        // Handover is BLOCKED before payment settlement
        expect($fsmService->canRelease($transaction))->toBeFalse();
        expect(function () use ($fsmService, $transaction, $cashier) {
            $fsmService->transition($transaction, ProductionStatus::COMPLETED, $cashier);
        })->toThrow(InvalidArgumentException::class, 'Cannot release order: payment has not been settled and credit/debt is not approved.');

        // Customer arrives at pickup counter and settles remaining Rp 50.000 via QRIS
        $paymentService = app(PaymentService::class);
        $payment2 = $paymentService->recordPayment($transaction, [
            'payment_method' => PaymentMethod::QRIS,
            'amount' => 50000.00,
        ], $cashier);

        $transaction->refresh();
        expect((float) $payment2->amount)->toBe(50000.00)
            ->and((float) $transaction->paid_amount)->toBe(80000.00)
            ->and((float) $transaction->outstanding_balance)->toBe(0.00)
            ->and($transaction->payment_status)->toBe(PaymentStatus::PAID)
            ->and($fsmService->canRelease($transaction))->toBeTrue();

        // Handover to completed now succeeds
        $fsmService->transition($transaction, ProductionStatus::COMPLETED, $cashier, 'Pengambilan lunas via QRIS');

        expect($transaction->production_status)->toBe(ProductionStatus::COMPLETED)
            ->and($transaction->actual_completion_at)->not->toBeNull()
            ->and($transaction->picked_up_at)->not->toBeNull();
    });

    it('T4-SCENARIO-04: QC Rewash Cycle Followed by Final Staging (F17, F18, F19, F20)', function () {
        $operator = User::factory()->operator()->create();
        $fsmService = app(ProductionStatusService::class);

        $transaction = Transaction::factory()->create([
            'production_status' => ProductionStatus::RECEIVED,
            'payment_status' => PaymentStatus::PAID,
            'total_amount' => 45000.00,
            'paid_amount' => 45000.00,
            'outstanding_balance' => 0.00,
            'rewash_count' => 0,
        ]);

        // Order moves received -> washing -> drying -> ironing -> qc
        $fsmService->transition($transaction, ProductionStatus::WASHING, $operator, 'Cuci awal');
        $fsmService->transition($transaction, ProductionStatus::DRYING, $operator, 'Kering awal');
        $fsmService->transition($transaction, ProductionStatus::IRONING, $operator, 'Setrika awal');
        $fsmService->transition($transaction, ProductionStatus::QC, $operator, 'Pemeriksaan QC 1');

        expect($transaction->production_status)->toBe(ProductionStatus::QC)
            ->and($transaction->rewash_count)->toBe(0);

        // QC fails with note "Bercak noda minyak belum tuntas" -> routes back to washing (rewash_count = 1)
        $fsmService->transition($transaction, ProductionStatus::WASHING, $operator, 'Bercak noda minyak belum tuntas');

        expect($transaction->production_status)->toBe(ProductionStatus::WASHING)
            ->and($transaction->rewash_count)->toBe(1);

        // Cycles through washing -> drying -> ironing -> qc
        $fsmService->transition($transaction, ProductionStatus::DRYING, $operator, 'Pengeringan rewash');
        $fsmService->transition($transaction, ProductionStatus::IRONING, $operator, 'Setrika uap rewash');
        $fsmService->transition($transaction, ProductionStatus::QC, $operator, 'Pemeriksaan QC 2');

        // Second inspection passed -> ready_for_pickup ("RACK-A-01")
        $fsmService->transition($transaction, ProductionStatus::READY_FOR_PICKUP, $operator, 'Inspeksi QC passed', [
            'staging_rack' => 'RACK-A-01',
        ]);

        expect($transaction->production_status)->toBe(ProductionStatus::READY_FOR_PICKUP)
            ->and($transaction->staging_rack)->toBe('RACK-A-01')
            ->and($transaction->rewash_count)->toBe(1);

        // Complete audit trail verified in TransactionStatusHistory
        $histories = $transaction->statusHistories()->orderBy('id')->get();
        expect($histories->count())->toBe(9);

        // Verify rewash transition in audit trail
        $rewashHistory = $histories->firstWhere('notes', 'Bercak noda minyak belum tuntas');
        expect($rewashHistory)->not->toBeNull()
            ->and($rewashHistory->from_status)->toBe(ProductionStatus::QC)
            ->and($rewashHistory->to_status)->toBe(ProductionStatus::WASHING)
            ->and($rewashHistory->user_id)->toBe($operator->id);

        // Verify final staging transition in audit trail
        $stagingHistory = $histories->last();
        expect($stagingHistory->to_status)->toBe(ProductionStatus::READY_FOR_PICKUP)
            ->and($stagingHistory->metadata['staging_rack'])->toBe('RACK-A-01');
    });

    it('T4-SCENARIO-05: Blocked Release for Unpaid Order Without Approval (F16, F17)', function () {
        $operator = User::factory()->operator()->create();
        $fsmService = app(ProductionStatusService::class);

        // Order at ready_for_pickup with balance Rp 45.000, is_debt_approved = false
        $transaction = Transaction::factory()->create([
            'production_status' => ProductionStatus::READY_FOR_PICKUP,
            'payment_status' => PaymentStatus::PARTIALLY_PAID,
            'total_amount' => 75000.00,
            'paid_amount' => 30000.00,
            'outstanding_balance' => 45000.00,
            'is_debt_approved' => false,
            'staging_rack' => 'RACK-E-05',
        ]);

        // Release attempted without payment or debt approval -> fails with exception at domain service layer
        expect($fsmService->canRelease($transaction))->toBeFalse();
        expect(function () use ($fsmService, $transaction, $operator) {
            $fsmService->transition($transaction, ProductionStatus::COMPLETED, $operator);
        })->toThrow(InvalidArgumentException::class, 'Cannot release order: payment has not been settled and credit/debt is not approved.');

        // Order remains in ready_for_pickup
        expect($transaction->fresh()->production_status)->toBe(ProductionStatus::READY_FOR_PICKUP);

        // Release attempted via HTTP Controller API -> returns 422 with domain error message
        $response = $this->actingAs($operator)->patchJson("/production/orders/{$transaction->id}/status", [
            'status' => 'completed',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('message', 'Cannot release order: payment has not been settled and credit/debt is not approved.');

        // Verify order state unchanged in database
        expect($transaction->fresh()->production_status)->toBe(ProductionStatus::READY_FOR_PICKUP)
            ->and($transaction->fresh()->actual_completion_at)->toBeNull()
            ->and($transaction->fresh()->picked_up_at)->toBeNull();
    });
});
