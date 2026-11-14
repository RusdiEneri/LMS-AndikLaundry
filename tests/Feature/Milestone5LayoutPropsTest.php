<?php

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
        $cashier = User::factory()->create(['role' => 'cashier', 'is_active' => true]);

        $response = $this->actingAs($cashier)->get('/pos');

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

    it('Production Station receives workstation queues, machines, and filters', function () {
        $operator = User::factory()->create(['role' => 'operator', 'is_active' => true]);

        $response = $this->actingAs($operator)->get('/production/station');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Production/Station')
            ->has('workstation_queues')
            ->has('workstation_queues.washing_queue')
            ->has('workstation_queues.drying_queue')
            ->has('workstation_queues.ironing_packing_queue')
            ->has('machines')
            ->has('machines.washers')
            ->has('machines.dryers')
            ->has('filters')
        );
    });
});
