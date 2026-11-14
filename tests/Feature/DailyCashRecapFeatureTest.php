<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

describe('Daily Cash Recap Feature Tests', function () {
    beforeEach(function () {
        $this->owner = User::factory()->create(['role' => 'owner', 'is_active' => true]);
    });

    it('requires authentication to access daily recap', function () {
        $response = $this->get('/owner/daily-recap');
        $response->assertRedirect('/login');
    });

    it('renders daily recap page with complete financial summary for owner', function () {
        $response = $this->actingAs($this->owner)->get('/owner/daily-recap');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Owner/DailyRecap')
            ->has('recap')
            ->has('recap.date')
            ->has('recap.cash_summary')
            ->has('recap.cash_summary.cash_in')
            ->has('recap.cash_summary.cash_out')
            ->has('recap.cash_summary.net_cash_on_hand')
            ->has('recap.digital_summary')
            ->has('recap.revenue_summary')
            ->has('recap.operational_volume')
            ->has('recap.expenses_list')
            ->has('selected_date')
        );
    });

    it('returns daily recap JSON data via print endpoint /api/daily-recap/print', function () {
        $response = $this->actingAs($this->owner)->getJson('/api/daily-recap/print');

        $response->assertOk();
        $response->assertJsonStructure([
            'status',
            'data' => [
                'date',
                'store_info' => ['name', 'phone', 'address'],
                'cash_summary' => ['cash_in', 'cash_out', 'net_cash_on_hand'],
                'digital_summary' => ['qris', 'bank_transfer', 'total_digital'],
                'revenue_summary',
                'operational_volume',
                'expenses_list',
            ],
        ]);
    });
});

