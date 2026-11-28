<?php

use App\Enums\PaymentStatus;
use App\Enums\ProductionStatus;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Milestone 3: Payment & Production Workflow HTTP API Tests
|--------------------------------------------------------------------------
*/

describe('Milestone 3: Payment Controller API', function () {

    it('T3-API-PAY-01: Cashier can record cash payment with change via POST /pos/orders/{id}/payments', function () {
        $cashier = User::factory()->create(['role' => 'owner']);
        $transaction = Transaction::factory()->create([
            'total_amount' => 50000.00,
            'paid_amount' => 0.00,
            'outstanding_balance' => 50000.00,
            'payment_status' => PaymentStatus::UNPAID,
        ]);

        $response = $this->actingAs($cashier)->postJson("/pos/orders/{$transaction->id}/payments", [
            'payment_method' => 'cash',
            'amount' => 50000.00,
            'tender_amount' => 100000.00,
            'notes' => 'Pembayaran tunai kasir',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.payment.amount', '50000.00')
            ->assertJsonPath('data.payment.tender_amount', '100000.00')
            ->assertJsonPath('data.payment.change_amount', '50000.00')
            ->assertJsonPath('data.transaction.payment_status', 'paid')
            ->assertJsonPath('data.transaction.outstanding_balance', '0.00');

        $this->assertDatabaseHas('payments', [
            'transaction_id' => $transaction->id,
            'amount' => 50000.00,
            'change_amount' => 50000.00,
        ]);
    });

    it('T3-API-PAY-02: Also accessible via /api/pos/orders/{id}/payments and invoice number', function () {
        $cashier = User::factory()->create(['role' => 'owner']);
        $transaction = Transaction::factory()->create([
            'invoice_number' => 'INV-20261114-8888',
            'total_amount' => 35000.00,
            'paid_amount' => 0.00,
            'outstanding_balance' => 35000.00,
        ]);

        $response = $this->actingAs($cashier)->postJson('/api/pos/orders/INV-20261114-8888/payments', [
            'payment_method' => 'qris',
            'amount' => 35000.00,
            'transaction_reference' => 'QRIS-API-TEST',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.payment.payment_method', 'qris')
            ->assertJsonPath('data.payment.transaction_reference', 'QRIS-API-TEST');
    });

    it('T3-API-PAY-03: Validates required payment fields and rejects invalid method', function () {
        $cashier = User::factory()->create(['role' => 'owner']);
        $transaction = Transaction::factory()->create();

        $response = $this->actingAs($cashier)->postJson("/pos/orders/{$transaction->id}/payments", [
            'payment_method' => 'cryptocurrency', // invalid
            'amount' => -100, // invalid
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['payment_method', 'amount']);
    });
});

describe('Milestone 3: Production Status Controller API', function () {

    it('T3-API-FSM-01: Operator can transition production status via PATCH /production/orders/{id}/status', function () {
        $operator = User::factory()->create(['role' => 'owner']);
        $transaction = Transaction::factory()->create([
            'production_status' => ProductionStatus::RECEIVED,
        ]);

        $response = $this->actingAs($operator)->patchJson("/production/orders/{$transaction->id}/status", [
            'to_status' => 'waiting',
            'notes' => 'Pakaian telah disortir dan masuk antrean',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.transaction.production_status', 'waiting');

        $this->assertDatabaseHas('transaction_status_histories', [
            'transaction_id' => $transaction->id,
            'from_status' => 'received',
            'to_status' => 'waiting',
        ]);
    });

    it('T3-API-FSM-02: Returns 422 with meaningful error message on invalid transition attempt', function () {
        $operator = User::factory()->create(['role' => 'owner']);
        $transaction = Transaction::factory()->create([
            'production_status' => ProductionStatus::RECEIVED,
        ]);

        $response = $this->actingAs($operator)->patchJson("/production/orders/{$transaction->id}/status", [
            'to_status' => 'ironing', // illegal jump
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('message', "Invalid production status transition from 'received' to 'ironing'.");
    });

    it('T3-API-FSM-03: Rejects whitespace-only staging rack and rewash notes with 422 Unprocessable Entity', function () {
        $operator = User::factory()->create(['role' => 'owner']);

        // 1. Whitespace staging rack when marking ready_for_pickup
        $txQc = Transaction::factory()->create([
            'production_status' => ProductionStatus::QC,
            'staging_rack' => null,
        ]);

        $responseRack = $this->actingAs($operator)->patchJson("/production/orders/{$txQc->id}/status", [
            'to_status' => 'ready_for_pickup',
            'staging_rack' => '   ',
        ]);

        $responseRack->assertStatus(422)
            ->assertJsonPath('message', 'A valid staging rack coordinate is required when marking order ready for pickup.');

        // 2. Whitespace notes when routing rewash from QC
        $responseRewash = $this->actingAs($operator)->patchJson("/production/orders/{$txQc->id}/status", [
            'to_status' => 'washing',
            'notes' => '   ',
        ]);

        $responseRewash->assertStatus(422)
            ->assertJsonPath('message', 'Rewash requires a reason code or notes.');
    });
});
