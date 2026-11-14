<?php

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\ProductionStatus;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Transaction;
use App\Models\User;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Milestone 3: Payment Ledger, Split Tender & Receivables Unit Tests
|--------------------------------------------------------------------------
| Specification Source: docs/PRD.md FEAT-103, docs/financial-logic.md §3,
|                       docs/database-schema.md Table 10
*/

describe('Milestone 3: Payment Ledger - Cash Tender & Change Math', function () {

    it('T3-PAY-01: calculates exact change for excess cash tender', function () {
        $paymentService = app(PaymentService::class);
        $cashier = User::factory()->create(['role' => 'cashier']);
        $transaction = Transaction::factory()->create([
            'total_amount' => 45000.00,
            'paid_amount' => 0.00,
            'outstanding_balance' => 45000.00,
            'payment_status' => PaymentStatus::UNPAID,
        ]);

        $payment = $paymentService->recordPayment($transaction, [
            'payment_method' => PaymentMethod::CASH,
            'amount' => 45000.00,
            'tender_amount' => 50000.00,
        ], $cashier);

        expect((float) $payment->amount)->toBe(45000.00)
            ->and((float) $payment->tender_amount)->toBe(50000.00)
            ->and((float) $payment->change_amount)->toBe(5000.00);

        $transaction->refresh();
        expect((float) $transaction->change_amount)->toBe(5000.00)
            ->and((float) $transaction->paid_amount)->toBe(45000.00)
            ->and((float) $transaction->outstanding_balance)->toBe(0.00)
            ->and($transaction->payment_status)->toBe(PaymentStatus::PAID);
    });

    it('T3-PAY-02: calculates zero change when cash tender exactly matches payment amount', function () {
        $paymentService = app(PaymentService::class);
        $cashier = User::factory()->create(['role' => 'cashier']);
        $transaction = Transaction::factory()->create([
            'total_amount' => 75000.00,
            'paid_amount' => 0.00,
            'outstanding_balance' => 75000.00,
        ]);

        $payment = $paymentService->recordPayment($transaction, [
            'payment_method' => PaymentMethod::CASH,
            'amount' => 75000.00,
            'tender_amount' => 75000.00,
        ], $cashier);

        expect((float) $payment->change_amount)->toBe(0.00);
        $transaction->refresh();
        expect((float) $transaction->change_amount)->toBe(0.00)
            ->and($transaction->payment_status)->toBe(PaymentStatus::PAID);
    });

    it('T3-PAY-03: rejects cash tender when tender amount is less than payment amount', function () {
        $paymentService = app(PaymentService::class);
        $cashier = User::factory()->create(['role' => 'cashier']);
        $transaction = Transaction::factory()->create([
            'total_amount' => 60000.00,
            'paid_amount' => 0.00,
            'outstanding_balance' => 60000.00,
        ]);

        expect(function () use ($paymentService, $transaction, $cashier) {
            $paymentService->recordPayment($transaction, [
                'payment_method' => PaymentMethod::CASH,
                'amount' => 60000.00,
                'tender_amount' => 50000.00, // Insufficient cash tender
            ], $cashier);
        })->toThrow(InvalidArgumentException::class, 'Cash tender amount cannot be less than payment amount.');
    });

    it('T3-PAY-04: non-cash tenders enforce exact tender amount and zero change', function () {
        $paymentService = app(PaymentService::class);
        $cashier = User::factory()->create(['role' => 'cashier']);
        $transaction = Transaction::factory()->create([
            'total_amount' => 125000.00,
            'paid_amount' => 0.00,
            'outstanding_balance' => 125000.00,
        ]);

        $payment = $paymentService->recordPayment($transaction, [
            'payment_method' => PaymentMethod::QRIS,
            'amount' => 125000.00,
            'tender_amount' => 150000.00, // Should be ignored for non-cash
            'transaction_reference' => 'QRIS-REF-998877',
        ], $cashier);

        expect((float) $payment->tender_amount)->toBe(125000.00)
            ->and((float) $payment->change_amount)->toBe(0.00)
            ->and($payment->transaction_reference)->toBe('QRIS-REF-998877');
    });
});

describe('Milestone 3: Down Payment (DP) & Partial Payments', function () {

    it('T3-DP-01: 50% down payment (DP) correctly marks order as partially_paid and tracks outstanding balance', function () {
        $paymentService = app(PaymentService::class);
        $cashier = User::factory()->create(['role' => 'cashier']);
        $transaction = Transaction::factory()->create([
            'total_amount' => 100000.00,
            'paid_amount' => 0.00,
            'outstanding_balance' => 100000.00,
            'payment_status' => PaymentStatus::UNPAID,
        ]);

        $dpPayment = $paymentService->recordPayment($transaction, [
            'payment_method' => PaymentMethod::BANK_TRANSFER,
            'amount' => 50000.00,
            'notes' => 'DP 50% saat serah terima pakaian',
        ], $cashier);

        expect((float) $dpPayment->amount)->toBe(50000.00);

        $transaction->refresh();
        expect((float) $transaction->paid_amount)->toBe(50000.00)
            ->and((float) $transaction->outstanding_balance)->toBe(50000.00)
            ->and($transaction->payment_status)->toBe(PaymentStatus::PARTIALLY_PAID)
            ->and($transaction->isFullyPaid())->toBeFalse();
    });
});

describe('Milestone 3: Split Tender Payments', function () {

    it('T3-SPLIT-01: multi-tender payments across Cash and QRIS correctly reach fully paid status', function () {
        $paymentService = app(PaymentService::class);
        $cashier = User::factory()->create(['role' => 'cashier']);
        $transaction = Transaction::factory()->create([
            'total_amount' => 90000.00,
            'paid_amount' => 0.00,
            'outstanding_balance' => 90000.00,
            'payment_status' => PaymentStatus::UNPAID,
        ]);

        // First installment: Cash 40,000 with 50,000 tender (10,000 change)
        $paymentService->recordPayment($transaction, [
            'payment_method' => PaymentMethod::CASH,
            'amount' => 40000.00,
            'tender_amount' => 50000.00,
        ], $cashier);

        $transaction->refresh();
        expect((float) $transaction->paid_amount)->toBe(40000.00)
            ->and((float) $transaction->outstanding_balance)->toBe(50000.00)
            ->and($transaction->payment_status)->toBe(PaymentStatus::PARTIALLY_PAID);

        // Second installment: QRIS remaining 50,000
        $paymentService->recordPayment($transaction, [
            'payment_method' => PaymentMethod::QRIS,
            'amount' => 50000.00,
            'transaction_reference' => 'QRIS-SPLIT-001',
        ], $cashier);

        $transaction->refresh();
        expect((float) $transaction->paid_amount)->toBe(90000.00)
            ->and((float) $transaction->outstanding_balance)->toBe(0.00)
            ->and($transaction->payment_status)->toBe(PaymentStatus::PAID)
            ->and($transaction->isFullyPaid())->toBeTrue()
            ->and($transaction->payments()->count())->toBe(2);
    });
});

describe('Milestone 3: Customer Outstanding Receivables Ledger', function () {

    it('T3-RCV-01: customer outstanding_receivables reflects aggregated unpaid balance across transactions', function () {
        $paymentService = app(PaymentService::class);
        $cashier = User::factory()->create(['role' => 'cashier']);
        $customer = Customer::factory()->create(['outstanding_receivables' => 0.00]);

        // Order 1: 100,000 unpaid
        $order1 = Transaction::factory()->create([
            'customer_id' => $customer->id,
            'total_amount' => 100000.00,
            'paid_amount' => 0.00,
            'outstanding_balance' => 100000.00,
            'payment_status' => PaymentStatus::UNPAID,
        ]);

        // Order 2: 50,000 unpaid
        $order2 = Transaction::factory()->create([
            'customer_id' => $customer->id,
            'total_amount' => 50000.00,
            'paid_amount' => 0.00,
            'outstanding_balance' => 50000.00,
            'payment_status' => PaymentStatus::UNPAID,
        ]);

        $customer->updateReceivables();
        expect((float) $customer->fresh()->outstanding_receivables)->toBe(150000.00);

        // Pay 60,000 on Order 1
        $paymentService->recordPayment($order1, [
            'payment_method' => PaymentMethod::CASH,
            'amount' => 60000.00,
            'tender_amount' => 60000.00,
        ], $cashier);

        expect((float) $customer->fresh()->outstanding_receivables)->toBe(90000.00);

        // Pay remaining 40,000 on Order 1
        $paymentService->recordPayment($order1, [
            'payment_method' => PaymentMethod::QRIS,
            'amount' => 40000.00,
        ], $cashier);

        expect((float) $customer->fresh()->outstanding_receivables)->toBe(50000.00);

        // Pay Order 2 in full
        $paymentService->recordPayment($order2, [
            'payment_method' => PaymentMethod::BANK_TRANSFER,
            'amount' => 50000.00,
        ], $cashier);

        expect((float) $customer->fresh()->outstanding_receivables)->toBe(0.00);
    });
});

describe('Milestone 3: Idempotency Key Guard', function () {

    it('T3-IDEMP-01: prevents duplicate payment processing when identical idempotency_key is submitted', function () {
        $paymentService = app(PaymentService::class);
        $cashier = User::factory()->create(['role' => 'cashier']);
        $transaction = Transaction::factory()->create([
            'total_amount' => 80000.00,
            'paid_amount' => 0.00,
            'outstanding_balance' => 80000.00,
        ]);

        $idempotencyKey = 'IDEMP-TEST-KEY-ABC-123';

        // First attempt
        $payment1 = $paymentService->recordPayment($transaction, [
            'payment_method' => PaymentMethod::CASH,
            'amount' => 40000.00,
            'tender_amount' => 40000.00,
            'idempotency_key' => $idempotencyKey,
        ], $cashier);

        // Duplicate second attempt (e.g. network retry)
        $payment2 = $paymentService->recordPayment($transaction, [
            'payment_method' => PaymentMethod::CASH,
            'amount' => 40000.00,
            'tender_amount' => 40000.00,
            'idempotency_key' => $idempotencyKey,
        ], $cashier);

        expect($payment1->id)->toBe($payment2->id);
        expect(Payment::where('idempotency_key', $idempotencyKey)->count())->toBe(1);

        $transaction->refresh();
        // Paid amount must not be double counted
        expect((float) $transaction->paid_amount)->toBe(40000.00)
            ->and((float) $transaction->outstanding_balance)->toBe(40000.00);
    });
});

describe('Milestone 3: Orthogonal Separation', function () {

    it('T3-ORTHO-01: payment settlement operates independently and never alters production status', function () {
        $paymentService = app(PaymentService::class);
        $cashier = User::factory()->create(['role' => 'cashier']);
        $transaction = Transaction::factory()->create([
            'total_amount' => 50000.00,
            'paid_amount' => 0.00,
            'outstanding_balance' => 50000.00,
            'payment_status' => PaymentStatus::UNPAID,
            'production_status' => ProductionStatus::WASHING,
        ]);

        $paymentService->recordPayment($transaction, [
            'payment_method' => PaymentMethod::QRIS,
            'amount' => 50000.00,
        ], $cashier);

        $transaction->refresh();
        expect($transaction->payment_status)->toBe(PaymentStatus::PAID)
            ->and($transaction->production_status)->toBe(ProductionStatus::WASHING);
    });
});
