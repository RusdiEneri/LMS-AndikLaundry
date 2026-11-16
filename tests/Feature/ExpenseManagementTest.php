<?php

use App\Enums\ExpenseCategory;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

describe('Expense Management Feature Tests', function () {
    beforeEach(function () {
        $this->owner = User::factory()->create(['role' => 'owner', 'is_active' => true]);
    });

    it('requires authentication to access expenses', function () {
        $response = $this->get('/owner/expenses');
        $response->assertRedirect('/login');
    });

    it('renders expenses index page with pagination, today summary, and categories', function () {
        Expense::factory()->count(3)->create(['expense_date' => now()->toDateString()]);

        $response = $this->actingAs($this->owner)->get('/owner/expenses');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Owner/Expenses/Index')
            ->has('expenses')
            ->has('today_summary')
            ->has('categories')
            ->has('filters')
        );
    });

    it('allows owner to record an expense via POST /owner/expenses', function () {
        $payload = [
            'amount' => 45000,
            'category' => ExpenseCategory::GAS_LPG->value,
            'description' => 'Beli tabung 3kg Toko Barokah',
            'payment_method' => 'cash',
            'expense_date' => now()->toDateString(),
        ];

        $response = $this->actingAs($this->owner)->post('/owner/expenses', $payload);

        $response->assertRedirect();
        $this->assertDatabaseHas('expenses', [
            'amount' => 45000.00,
            'category' => ExpenseCategory::GAS_LPG->value,
            'description' => 'Beli tabung 3kg Toko Barokah',
            'payment_method' => 'cash',
        ]);
    });

    it('validates required fields when creating an expense', function () {
        $response = $this->actingAs($this->owner)->post('/owner/expenses', []);

        $response->assertSessionHasErrors(['amount', 'category', 'description', 'payment_method']);
    });

    it('allows owner to delete an expense record', function () {
        $expense = Expense::factory()->create(['user_id' => $this->owner->id]);

        $response = $this->actingAs($this->owner)->delete("/owner/expenses/{$expense->id}");

        $response->assertRedirect();
        $this->assertDatabaseMissing('expenses', ['id' => $expense->id]);
    });
});
