<?php

use App\Models\Customer;
use App\Models\Transaction;
use App\Services\CustomerReceivablesService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

describe('CustomerReceivablesService Unit Tests', function () {
    beforeEach(function () {
        $this->service = app(CustomerReceivablesService::class);
        $this->customer = Customer::factory()->create(['name' => 'Budi Santoso']);
    });

    it('classifies unpaid receivables into aging brackets (current, overdue, delinquent)', function () {
        $today = Carbon::today();

        // 1. Current (3 days old)
        Transaction::factory()->create([
            'customer_id' => $this->customer->id,
            'outstanding_balance' => 50000.00,
            'intake_at' => $today->copy()->subDays(3)->toDateTimeString(),
        ]);

        // 2. Overdue (10 days old)
        Transaction::factory()->create([
            'customer_id' => $this->customer->id,
            'outstanding_balance' => 75000.00,
            'intake_at' => $today->copy()->subDays(10)->toDateTimeString(),
        ]);

        // 3. Delinquent (20 days old)
        Transaction::factory()->create([
            'customer_id' => $this->customer->id,
            'outstanding_balance' => 100000.00,
            'intake_at' => $today->copy()->subDays(20)->toDateTimeString(),
        ]);

        $summary = $this->service->getReceivablesSummary();

        expect($summary['total_receivables'])->toBe(225000.0)
            ->and($summary['unpaid_orders_count'])->toBe(3)
            ->and($summary['aging_breakdown']['current'])->toBe(50000.0)
            ->and($summary['aging_breakdown']['overdue'])->toBe(75000.0)
            ->and($summary['aging_breakdown']['delinquent'])->toBe(100000.0);
    });
});

