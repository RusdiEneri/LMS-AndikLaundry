<?php

use App\Enums\PaymentStatus;
use App\Enums\ProductionStatus;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

describe('Milestone 5: Layout & Controller Inertia Props Verification', function () {

    it('Owner Dashboard receives KPI cards, weekly revenue, service mix, recent shifts, and store info', function () {
        $owner = User::factory()->create(['role' => 'owner', 'is_active' => true]);

        $response = $this->actingAs($owner)->get('/dashboard');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Owner/Dashboard')
            ->has('kpis')
            ->has('kpis.today_revenue')
            ->has('kpis.active_workload')
            ->has('kpis.customer_receivables')
            ->has('kpis.active_cash_drawer')
            ->has('weekly_revenue')
            ->has('service_mix')
            ->has('recent_shifts')
            ->has('store_info')
            ->where('store_info.name', 'Andik Laundry')
        );
    });

    it('POS Cashier interface receives active shift, store info, categories, services, quick tenders, and turnaround tiers', function () {
        $owner = User::factory()->create(['role' => 'owner', 'is_active' => true]);

        $response = $this->actingAs($owner)->get('/pos');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Pos/Index')
            ->has('active_shift')
            ->has('store_info')
            ->has('categories')
            ->has('services')
            ->has('quick_tenders')
            ->has('turnaround_tiers')
            ->has('perfume_variants')
            ->where('active_shift.is_open', true)
        );
    });

    it('Production Station renders empty real queue without hardcoded dummy data and supports simplified transitions', function () {
        $owner = User::factory()->create(['role' => 'owner', 'is_active' => true]);

        $emptyResponse = $this->actingAs($owner)->get('/production/station');

        $emptyResponse->assertOk();
        $emptyResponse->assertInertia(fn (Assert $page) => $page
            ->component('Production/Station')
            ->has('orders', 0)
            ->where('summary.processing_count', 0)
            ->where('summary.ready_count', 0)
            ->where('summary.picked_up_today_count', 0)
        );

        $order = Transaction::factory()->create([
            'production_status' => ProductionStatus::RECEIVED,
            'payment_status' => PaymentStatus::PAID,
            'total_amount' => 16000.00,
            'paid_amount' => 16000.00,
            'outstanding_balance' => 0.00,
        ]);

        $this->actingAs($owner)->get('/production/station')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Production/Station')
                ->has('orders', 1)
                ->where('orders.0.invoice_number', $order->invoice_number)
                ->where('orders.0.stage', 'diproses')
                ->where('summary.processing_count', 1)
            );

        $this->actingAs($owner)
            ->patchJson("/production/orders/{$order->id}/status", ['to_status' => 'selesai'])
            ->assertOk()
            ->assertJsonPath('data.transaction.production_status', 'selesai');

        $this->actingAs($owner)
            ->patchJson("/production/orders/{$order->id}/status", ['to_status' => 'sudah_diambil'])
            ->assertOk()
            ->assertJsonPath('data.transaction.production_status', 'sudah_diambil');
    });
});
