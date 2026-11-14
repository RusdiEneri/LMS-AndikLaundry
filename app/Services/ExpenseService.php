<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ExpenseService
{
    /**
     * Record a new operational expense.
     *
     * @param  array<string, mixed>  $data
     */
    public function createExpense(array $data, ?User $user = null): Expense
    {
        return DB::transaction(function () use ($data, $user) {
            $expenseDate = isset($data['expense_date']) && !empty($data['expense_date'])
                ? Carbon::parse($data['expense_date'])
                : Carbon::today();

            $receiptPath = null;
            if (isset($data['receipt_image']) && $data['receipt_image'] instanceof UploadedFile) {
                $receiptPath = $data['receipt_image']->store('expenses', 'public');
            }

            return Expense::create([
                'expense_number' => Expense::generateExpenseNumber($expenseDate),
                'user_id' => $user?->id ?? ($data['user_id'] ?? null),
                'amount' => $data['amount'],
                'category' => $data['category'],
                'description' => $data['description'],
                'payment_method' => $data['payment_method'] ?? 'cash',
                'expense_date' => $expenseDate->toDateString(),
                'receipt_image_path' => $receiptPath,
            ]);
        });
    }

    /**
     * Retrieve paginated and filtered expenses.
     */
    public function getExpenses(
        ?string $search = null,
        ?string $category = null,
        ?string $startDate = null,
        ?string $endDate = null,
        int $perPage = 15
    ): LengthAwarePaginator {
        $query = Expense::with('user')->orderByDesc('expense_date')->orderByDesc('id');

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('expense_number', 'LIKE', "%{$search}%")
                    ->orWhere('description', 'LIKE', "%{$search}%");
            });
        }

        if (!empty($category)) {
            $query->where('category', $category);
        }

        if (!empty($startDate) && !empty($endDate)) {
            $query->whereBetween('expense_date', [$startDate, $endDate]);
        } elseif (!empty($startDate)) {
            $query->whereDate('expense_date', '>=', $startDate);
        } elseif (!empty($endDate)) {
            $query->whereDate('expense_date', '<=', $endDate);
        }

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Delete an expense and its receipt photo.
     */
    public function deleteExpense(Expense $expense): bool
    {
        if ($expense->receipt_image_path && Storage::disk('public')->exists($expense->receipt_image_path)) {
            Storage::disk('public')->delete($expense->receipt_image_path);
        }

        return (bool) $expense->delete();
    }

    /**
     * Get aggregate expense summary for a specific date.
     *
     * @return array<string, mixed>
     */
    public function getExpenseSummaryForDate(string $date): array
    {
        $expenses = Expense::whereDate('expense_date', $date)->get();

        $totalAmount = $expenses->sum('amount');
        $cashAmount = $expenses->where('payment_method', 'cash')->sum('amount');
        $transferAmount = $expenses->where('payment_method', 'bank_transfer')->sum('amount');

        $byCategory = $expenses->groupBy('category')->map(function ($items, $cat) {
            return [
                'category' => $cat,
                'count' => $items->count(),
                'total' => (float) $items->sum('amount'),
            ];
        })->values()->all();

        return [
            'date' => $date,
            'total_expenses' => (float) $totalAmount,
            'cash_expenses' => (float) $cashAmount,
            'transfer_expenses' => (float) $transferAmount,
            'expense_count' => $expenses->count(),
            'by_category' => $byCategory,
        ];
    }
}

