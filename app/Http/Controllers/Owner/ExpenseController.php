<?php

namespace App\Http\Controllers\Owner;

use App\Enums\ExpenseCategory;
use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\StoreExpenseRequest;
use App\Models\Expense;
use App\Services\ExpenseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ExpenseController extends Controller
{
    public function __construct(
        protected ExpenseService $expenseService
    ) {}

    /**
     * Display a listing of operational expenses.
     */
    public function index(Request $request): Response
    {
        $search = $request->query('search');
        $category = $request->query('category');
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        $expenses = $this->expenseService->getExpenses(
            search: $search,
            category: $category,
            startDate: $startDate,
            endDate: $endDate,
            perPage: 15
        );

        $todaySummary = $this->expenseService->getExpenseSummaryForDate(now()->toDateString());

        $categories = collect(ExpenseCategory::cases())->map(fn ($cat) => [
            'value' => $cat->value,
            'label' => $cat->label(),
        ])->values()->all();

        return Inertia::render('Owner/Expenses/Index', [
            'expenses' => $expenses,
            'today_summary' => $todaySummary,
            'categories' => $categories,
            'filters' => [
                'search' => $search ?? '',
                'category' => $category ?? '',
                'start_date' => $startDate ?? '',
                'end_date' => $endDate ?? '',
            ],
        ]);
    }

    /**
     * Store a newly created expense in storage.
     */
    public function store(StoreExpenseRequest $request): RedirectResponse
    {
        $expense = $this->expenseService->createExpense(
            $request->validated(),
            $request->user()
        );

        return redirect()->back()->with('success', "Pengeluaran {$expense->expense_number} sebesar Rp ".number_format($expense->amount, 0, ',', '.').' berhasil dicatat.');
    }

    /**
     * Remove the specified expense from storage.
     */
    public function destroy(Expense $expense): RedirectResponse
    {
        $number = $expense->expense_number;
        $this->expenseService->deleteExpense($expense);

        return redirect()->back()->with('success', "Catatan pengeluaran {$number} berhasil dihapus.");
    }
}
