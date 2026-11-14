<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\BusinessSetting;
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
     * Display the Owner Executive Dashboard with KPI cards, charts, and shift audits.
     */
    public function index(Request $request, AccountingReportService $reports): Response
    {
        [$startDate, $endDate] = $this->reportRange($request);
        $financialReport = $reports->getFinancialReport($startDate, $endDate);

        $minWeight = (float) BusinessSetting::get('operational.default_min_weight', 3.0);
        $creditCeiling = (float) BusinessSetting::get('operational.customer_credit_ceiling', 150000.0);

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
