<?php

use App\Enums\ExpenseCategory;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\Payment;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\User;
use App\Services\DailyCashRecapService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

describe('DailyCashRecapService Unit Tests', function () {
    beforeEach(function () {
        $this->service = app(DailyCashRecapService::class);
        $this->user = User::factory()->create(['role' => 'owner']);
        $this->customer = Customer::factory()->create();
    });

    it('calculates net cash on hand as cash payments minus cash expenses', function () {
        $today = Carbon::today()->toDateString();

        $txn = Transaction::factory()->create([
            'customer_id' => $this->customer->id,
            'cashier_id' => $this->user->id,
            'intake_at' => $today . ' 09:00:00',
            'total_amount' => 150000,
            'paid_amount' => 150000,
        ]);

        // Cash payment in: 100,000
        Payment::factory()->create([
            'transaction_id' => $txn->id,
            'cashier_id' => $this->user->id,
            'payment_method' => 'cash',
            'amount' => 100000,
            'payment_status' => 'completed',
            'paid_at' => $today . ' 09:15:00',
        ]);

        // QRIS payment in: 50,000
        Payment::factory()->create([
            'transaction_id' => $txn->id,
            'cashier_id' => $this->user->id,
            'payment_method' => 'qris',
            'amount' => 50000,
            'payment_status' => 'completed',
            'paid_at' => $today . ' 09:30:00',
        ]);

        // Cash expense out: 40,000 (LPG)
        Expense::factory()->create([
            'user_id' => $this->user->id,
            'amount' => 40000,
            'category' => ExpenseCategory::GAS_LPG->value,
            'payment_method' => 'cash',
            'expense_date' => $today,
        ]);

        // Transfer expense out: 20,000 (Non-cash)
        Expense::factory()->create([
            'user_id' => $this->user->id,
            'amount' => 20000,
            'category' => ExpenseCategory::UTILITIES->value,
            'payment_method' => 'bank_transfer',
            'expense_date' => $today,
        ]);

        $recap = $this->service->getRecapForDate($today);

        // Cash in = 100,000, Cash out = 40,000 => Net cash on hand = 60,000
        expect($recap['cash_summary']['cash_in'])->toBe(100000.0)
            ->and($recap['cash_summary']['cash_out'])->toBe(40000.0)
            ->and($recap['cash_summary']['net_cash_on_hand'])->toBe(60000.0);

        // Digital summary: QRIS = 50,000
        expect($recap['digital_summary']['qris'])->toBe(50000.0)
            ->and($recap['digital_summary']['total_digital'])->toBe(50000.0);

        // Total payments received = 150,000
        expect($recap['revenue_summary']['total_payments_received'])->toBe(150000.0);
    });

    it('aggregates daily order volume by kilograms and pieces', function () {
        $today = Carbon::today()->toDateString();

        $txn = Transaction::factory()->create([
            'customer_id' => $this->customer->id,
            'cashier_id' => $this->user->id,
            'intake_at' => $today . ' 10:00:00',
        ]);

        // Kiloan item 4.5 kg
        TransactionItem::factory()->create([
            'transaction_id' => $txn->id,
            'actual_quantity' => 4.50,
            'pricing_unit_snapshot' => 'kg',
        ]);

        // Satuan item 2 pcs
        TransactionItem::factory()->create([
            'transaction_id' => $txn->id,
            'actual_quantity' => 2.00,
            'pricing_unit_snapshot' => 'pcs',
        ]);

        $recap = $this->service->getRecapForDate($today);

        expect($recap['operational_volume']['total_orders'])->toBe(1)
            ->and($recap['operational_volume']['total_weight_kg'])->toBe(4.5)
            ->and($recap['operational_volume']['total_pieces'])->toBe(2);
    });
});

