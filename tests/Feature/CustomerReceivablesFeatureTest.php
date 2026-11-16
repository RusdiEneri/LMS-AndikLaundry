<?php

use App\Models\Customer;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

describe('Customer Receivables Feature Tests', function () {
    beforeEach(function () {
        $this->owner = User::factory()->create(['role' => 'owner', 'is_active' => true]);
        $this->customer = Customer::factory()->create(['name' => 'Dewi Lestari', 'phone' => '081234567890']);
    });

    it('requires authentication to view receivables', function () {
        $response = $this->get('/owner/receivables');
        $response->assertRedirect('/login');
    });

    it('renders receivables page with summary and debtor list for authenticated owner', function () {
        Transaction::factory()->create([
            'customer_id' => $this->customer->id,
            'outstanding_balance' => 60000.00,
        ]);

        $response = $this->actingAs($this->owner)->get('/owner/receivables');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Owner/Receivables/Index')
            ->has('summary')
            ->has('summary.total_receivables')
            ->has('summary.aging_breakdown')
            ->has('summary.debtors')
        );
    });

    it('provides whatsapp reminder url via GET /api/receivables/{id}/reminder-url', function () {
        $txn = Transaction::factory()->create([
            'customer_id' => $this->customer->id,
            'invoice_number' => 'INV-20261114-9999',
            'outstanding_balance' => 45000.00,
        ]);

        $response = $this->actingAs($this->owner)->getJson("/api/receivables/{$txn->id}/reminder-url");

        $response->assertOk();
        $response->assertJsonStructure([
            'status',
            'data' => [
                'invoice_number',
                'customer_name',
                'customer_phone',
                'message',
                'wa_url',
            ],
        ]);
    });
});
