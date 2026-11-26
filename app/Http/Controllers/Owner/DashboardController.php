<?php

namespace App\Http\Controllers\Owner;

use App\Enums\ExpenseCategory;
use App\Enums\ProductionStatus;
use App\Http\Controllers\Controller;
use App\Models\BusinessSetting;
use App\Models\Expense;
use App\Models\Transaction;
use App\Services\AccountingReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DashboardController extends Controller
{
    /**
     * Display the Owner Executive Dashboard with financial metrics, operational counts, and recent activity.
     */
    public function index(Request $request, AccountingReportService $reports): Response
    {
        [$startDate, $endDate] = $this->reportRange($request);
        $financialReport = $reports->getFinancialReport($startDate, $endDate);

        $minWeight = (float) BusinessSetting::get('operational.default_min_weight', 3.0);
        $creditCeiling = (float) BusinessSetting::get('operational.customer_credit_ceiling', 150000.0);

        $processingStatuses = [
            ProductionStatus::DIPROSES->value,
            ProductionStatus::RECEIVED->value,
            ProductionStatus::WAITING->value,
            ProductionStatus::SORTING->value,
            ProductionStatus::WASHING->value,
            ProductionStatus::DRYING->value,
            ProductionStatus::IRONING->value,
            ProductionStatus::QC->value,
            ProductionStatus::PACKING->value,
        ];
        $readyStatuses = [
            ProductionStatus::SELESAI->value,
            ProductionStatus::READY_FOR_PICKUP->value,
        ];

        $processingCount = Transaction::query()
            ->whereIn('production_status', $processingStatuses)
            ->count();
        $readyCount = Transaction::query()
            ->whereIn('production_status', $readyStatuses)
            ->count();

        $recentTransactions = Transaction::query()
            ->with('customer:id,name')
            ->orderByDesc('id')
            ->limit(5)
            ->get(['id', 'invoice_number', 'customer_id', 'production_status', 'total_amount', 'outstanding_balance', 'intake_at', 'created_at'])
            ->map(function (Transaction $txn): array {
                $statusEnum = $txn->production_status instanceof ProductionStatus
                    ? $txn->production_status
                    : ProductionStatus::coerce((string) $txn->production_status);
                $timestamp = $txn->intake_at ?? $txn->created_at;

                return [
                    'id' => $txn->id,
                    'invoice_number' => $txn->invoice_number,
                    'customer_name' => $txn->customer?->name ?? 'Pelanggan Umum',
                    'status' => $statusEnum->value,
                    'status_label' => $statusEnum->label(),
                    'total_amount' => (float) $txn->total_amount,
                    'outstanding_balance' => (float) $txn->outstanding_balance,
                    'date_label' => $timestamp?->copy()->locale('id')->translatedFormat('d M Y, H:i') ?? '-',
                ];
            })
            ->values()
            ->all();

        $recentExpenses = Expense::query()
            ->orderByDesc('expense_date')
            ->orderByDesc('id')
            ->limit(5)
            ->get(['id', 'expense_number', 'category', 'description', 'amount', 'expense_date'])
            ->map(function (Expense $expense): array {
                $category = (string) $expense->category;
                $categoryLabel = ExpenseCategory::tryFrom($category)?->label() ?? $category;

                return [
                    'id' => $expense->id,
                    'expense_number' => $expense->expense_number,
                    'category_label' => $categoryLabel,
                    'description' => $expense->description ?: $categoryLabel,
                    'amount' => (float) $expense->amount,
                    'date_label' => $expense->expense_date?->copy()->locale('id')->translatedFormat('d M Y') ?? '-',
                ];
            })
            ->values()
            ->all();

        $kpis = [
            'today_revenue' => [
                'amount' => 1450000,
                'formatted' => 'Rp 1.450.000',
                'trend' => '+12% vs kemarin',
                'is_positive' => true,
                'cash_amount' => 950000,
                'electronic_amount' => 500000,
            ],
            'active_workload' => [
                'total_kg' => 142.50,
                'formatted' => '142.50 kg',
                'active_orders_count' => 32,
                'breakdown' => [
                    'washing' => 12,
                    'drying' => 10,
                    'ironing' => 10,
                ],
            ],
            'customer_receivables' => [
                'total_unpaid' => 385000,
                'formatted' => 'Rp 385.000',
                'customer_count' => 6,
                'credit_ceiling' => $creditCeiling,
            ],
            'active_cash_drawer' => [
                'estimated_amount' => 650000,
                'formatted' => 'Rp 650.000',
                'active_shifts_count' => 1,
                'shifts_label' => 'Kas Tunai Toko Hari Ini',
            ],
        ];

        return Inertia::render('Owner/Dashboard', [
            'financialReport' => $financialReport,
            'operations_summary' => [
                'processing_count' => $processingCount,
                'ready_count' => $readyCount,
            ],
            'recent_transactions' => $recentTransactions,
            'recent_expenses' => $recentExpenses,
            'kpis' => $kpis,
            'today_revenue' => $kpis['today_revenue'],
            'active_workload' => $kpis['active_workload'],
            'customer_receivables' => $kpis['customer_receivables'],
            'active_cash_drawer' => $kpis['active_cash_drawer'],
            'weekly_revenue' => [
                ['day' => 'Sen', 'date' => '2026-11-08', 'revenue' => 1100000, 'formatted' => 'Rp 1.100.000'],
                ['day' => 'Sel', 'date' => '2026-11-09', 'revenue' => 1350000, 'formatted' => 'Rp 1.350.000'],
                ['day' => 'Rab', 'date' => '2026-11-10', 'revenue' => 1200000, 'formatted' => 'Rp 1.200.000'],
                ['day' => 'Kam', 'date' => '2026-11-11', 'revenue' => 1500000, 'formatted' => 'Rp 1.500.000'],
                ['day' => 'Jum', 'date' => '2026-11-12', 'revenue' => 1650000, 'formatted' => 'Rp 1.650.000'],
                ['day' => 'Sab', 'date' => '2026-11-13', 'revenue' => 1800000, 'formatted' => 'Rp 1.800.000'],
                ['day' => 'Min', 'date' => '2026-11-14', 'revenue' => 1450000, 'formatted' => 'Rp 1.450.000'],
            ],
            'service_mix' => [
                ['category' => 'Kiloan', 'percentage' => 75, 'weight_kg' => 106.88, 'revenue' => 1087500],
                ['category' => 'Satuan', 'percentage' => 20, 'pcs' => 28, 'revenue' => 290000],
                ['category' => 'Sepatu & Karpet', 'percentage' => 5, 'pcs' => 7, 'revenue' => 72500],
            ],
            'recent_shifts' => [
                [
                    'shift_id' => 'REKAP-HARI-INI',
                    'cashier_name' => 'Pemilik & Keluarga',
                    'time_range' => '07:00 - Sekarang (Toko Buka)',
                    'expected_cash' => 950000,
                    'actual_cash' => 650000,
                    'variance' => 0,
                    'status' => 'balanced',
                    'status_label' => 'Kas Terkendali (Seimbang)',
                ],
            ],
            'store_info' => [
                'name' => BusinessSetting::get('store.name', 'Andik Laundry'),
                'phone' => BusinessSetting::get('store.phone', '6281234567890'),
                'address' => BusinessSetting::get('store.address', 'Jl. Raya Laundry No. 12, Jakarta'),
                'default_min_weight' => $minWeight,
                'customer_credit_ceiling' => $creditCeiling,
            ],
        ]);
    }

    /**
     * Download the per-order transaction report for the selected range as CSV.
     */
    public function exportCsv(Request $request, AccountingReportService $reports): StreamedResponse
    {
        [$startDate, $endDate] = $this->reportRange($request);

        return $reports->exportTransactionsCsv($startDate, $endDate);
    }

    /**
     * Validate start_date / end_date; defaults to the first of this month through today.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    private function reportRange(Request $request): array
    {
        $validated = $request->validate([
            'start_date' => ['nullable', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:start_date'],
        ], [], [
            'start_date' => 'Tanggal Awal',
            'end_date' => 'Tanggal Akhir',
        ]);

        $startDate = isset($validated['start_date'])
            ? Carbon::parse($validated['start_date'])->startOfDay()
            : Carbon::today()->startOfMonth();
        $endDate = isset($validated['end_date'])
            ? Carbon::parse($validated['end_date'])->startOfDay()
            : Carbon::today();

        if ($endDate->lt($startDate)) {
            throw ValidationException::withMessages(['end_date' => 'Tanggal Akhir tidak boleh sebelum Tanggal Awal.']);
        }

        // Caps report/export cost; one year of a home laundry fits comfortably.
        if ($startDate->diffInDays($endDate) + 1 > AccountingReportService::MAX_RANGE_DAYS) {
            throw ValidationException::withMessages([
                'end_date' => 'Rentang laporan maksimal '.AccountingReportService::MAX_RANGE_DAYS.' hari.',
            ]);
        }

        return [$startDate, $endDate];
    }
}
