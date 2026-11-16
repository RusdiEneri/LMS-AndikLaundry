<?php

use App\Enums\ExpenseCategory;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

describe('Expense Model Unit Tests', function () {
    it('generates sequential expense numbers with EXP-YYYYMMDD-XXXX format', function () {
        $today = Carbon::today();
        $expectedPrefix = 'EXP-'.$today->format('Ymd').'-';

        $number1 = Expense::generateExpenseNumber($today);
        expect($number1)->toBe($expectedPrefix.'0001');

        Expense::factory()->create([
            'expense_number' => $number1,
            'expense_date' => $today->toDateString(),
        ]);

        $number2 = Expense::generateExpenseNumber($today);
        expect($number2)->toBe($expectedPrefix.'0002');
    });

    it('correctly casts amount to decimal and date to carbon date', function () {
        $user = User::factory()->create();
        $expense = Expense::create([
            'expense_number' => 'EXP-20261114-0001',
            'user_id' => $user->id,
            'amount' => '44000.50',
            'category' => ExpenseCategory::GAS_LPG->value,
            'description' => 'Beli 2 tabung gas LPG 3kg',
            'payment_method' => 'cash',
            'expense_date' => '2026-11-14',
        ]);

        expect((float) $expense->amount)->toBe(44000.50)
            ->and($expense->expense_date->toDateString())->toBe('2026-11-14')
            ->and($expense->user->id)->toBe($user->id);
    });

    it('filters expenses using forDate and dateBetween scopes', function () {
        Expense::factory()->create(['expense_date' => '2026-11-10']);
        Expense::factory()->create(['expense_date' => '2026-11-12']);
        Expense::factory()->create(['expense_date' => '2026-11-14']);

        expect(Expense::forDate('2026-11-12')->count())->toBe(1)
            ->and(Expense::dateBetween('2026-11-11', '2026-11-15')->count())->toBe(2);
    });
});
