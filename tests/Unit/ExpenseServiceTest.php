<?php

use App\Enums\ExpenseCategory;
use App\Models\Expense;
use App\Models\User;
use App\Services\ExpenseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

describe('ExpenseService Unit Tests', function () {
    beforeEach(function () {
        $this->service = app(ExpenseService::class);
        $this->user = User::factory()->create(['role' => 'owner']);
    });

    it('creates expense with auto-generated sequential number', function () {
        $expense = $this->service->createExpense([
            'amount' => 75000,
            'category' => ExpenseCategory::DETERGENT_CHEMICALS->value,
            'description' => 'Beli deterjen cair 5 liter',
            'payment_method' => 'cash',
            'expense_date' => '2026-11-14',
        ], $this->user);

        expect($expense)->toBeInstanceOf(Expense::class)
            ->and($expense->expense_number)->toStartWith('EXP-20261114-')
            ->and((float) $expense->amount)->toBe(75000.0)
            ->and($expense->category)->toBe(ExpenseCategory::DETERGENT_CHEMICALS->value)
            ->and($expense->user_id)->toBe($this->user->id);
    });

    it('handles optional receipt image upload during creation', function () {
        Storage::fake('public');
        $image = UploadedFile::fake()->create('receipt.jpg', 150, 'image/jpeg');

        $expense = $this->service->createExpense([
            'amount' => 44000,
            'category' => ExpenseCategory::GAS_LPG->value,
            'description' => 'Beli tabung gas LPG',
            'payment_method' => 'cash',
            'receipt_image' => $image,
        ], $this->user);

        expect($expense->receipt_image_path)->not->toBeNull();
        Storage::disk('public')->assertExists($expense->receipt_image_path);
    });

    it('filters expenses by search keyword, category, and date', function () {
        Expense::factory()->create([
            'description' => 'Beli plastik kresek besar',
            'category' => ExpenseCategory::PACKAGING_PLASTIC->value,
            'expense_date' => '2026-11-10',
        ]);
        Expense::factory()->create([
            'description' => 'Beli deterjen bubuk',
            'category' => ExpenseCategory::DETERGENT_CHEMICALS->value,
            'expense_date' => '2026-11-14',
        ]);

        $searchResult = $this->service->getExpenses(search: 'plastik');
        expect($searchResult->total())->toBe(1);

        $categoryResult = $this->service->getExpenses(category: ExpenseCategory::DETERGENT_CHEMICALS->value);
        expect($categoryResult->total())->toBe(1);

        $dateResult = $this->service->getExpenses(startDate: '2026-11-13', endDate: '2026-11-15');
        expect($dateResult->total())->toBe(1);
    });

    it('computes aggregated expense summary for a specific date', function () {
        Expense::factory()->create([
            'amount' => 50000,
            'payment_method' => 'cash',
            'category' => ExpenseCategory::GAS_LPG->value,
            'expense_date' => '2026-11-14',
        ]);
        Expense::factory()->create([
            'amount' => 30000,
            'payment_method' => 'cash',
            'category' => ExpenseCategory::PACKAGING_PLASTIC->value,
            'expense_date' => '2026-11-14',
        ]);
        Expense::factory()->create([
            'amount' => 100000,
            'payment_method' => 'bank_transfer',
            'category' => ExpenseCategory::UTILITIES->value,
            'expense_date' => '2026-11-14',
        ]);

        $summary = $this->service->getExpenseSummaryForDate('2026-11-14');

        expect($summary['total_expenses'])->toBe(180000.0)
            ->and($summary['cash_expenses'])->toBe(80000.0)
            ->and($summary['transfer_expenses'])->toBe(100000.0)
            ->and($summary['expense_count'])->toBe(3);
    });
});
