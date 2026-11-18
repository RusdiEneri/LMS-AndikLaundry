<?php

use App\Enums\ProductionStatus;
use App\Models\Customer;
use App\Models\Transaction;
use App\Models\TransactionItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

it('reports zero statistics when there is no laundry data', function () {
    $this->get('/')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Welcome')
            ->where('stats.customers', 0)
            ->where('stats.orders', 0)
            ->where('stats.laundry_kg', 0)
        );
});

it('reports statistics from real transactions and ignores cancelled orders', function () {
    $customer = Customer::factory()->create();

    $activeTransaction = Transaction::factory()->for($customer)->create();

    TransactionItem::factory()->for($activeTransaction)->create([
        'pricing_unit_snapshot' => 'kg',
        'actual_quantity' => 4.50,
        'billed_quantity' => 4.50,
    ]);

    $cancelledTransaction = Transaction::factory()
        ->for($customer)
        ->withProductionStatus(ProductionStatus::CANCELLED)
        ->create();

    TransactionItem::factory()->for($cancelledTransaction)->create([
        'pricing_unit_snapshot' => 'kg',
        'actual_quantity' => 7.00,
        'billed_quantity' => 7.00,
    ]);

    $this->get('/')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('stats.customers', 1)
            ->where('stats.orders', 1)
            ->where('stats.laundry_kg', 4)
        );
});
