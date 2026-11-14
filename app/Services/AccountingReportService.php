<?php

namespace App\Services;

use App\Enums\ExpenseCategory;
use App\Enums\PaymentMethod;
use App\Enums\ProductionStatus;
use App\Models\Expense;
use App\Models\Payment;
use App\Models\Transaction;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Profit & loss reporting for the owner: cash basis (money that actually moved)
 * side by side with accrual basis (SAK EMKM revenue recognition on completed orders).
 */
class AccountingReportService
{
    public const PERIOD_TODAY = 'today';

    public const PERIOD_LAST_7_DAYS = 'last_7_days';

    public const PERIOD_THIS_MONTH = 'this_month';

    public const PERIOD_LAST_MONTH = 'last_month';

    public const PERIOD_CUSTOM = 'custom';

    public const PERIODS = [
        self::PERIOD_TODAY,
        self::PERIOD_LAST_7_DAYS,
        self::PERIOD_THIS_MONTH,
        self::PERIOD_LAST_MONTH,
        self::PERIOD_CUSTOM,
    ];

    public const MAX_RANGE_DAYS = 366;

    /** Above this many days the trend chart switches from daily to monthly buckets. */
    private const DAILY_TREND_MAX_DAYS = 62;

    /**
     * Resolve a period preset (or custom range) into inclusive start/end dates.
     *
     * @return array{preset: string, start: Carbon, end: Carbon, label: string}
     */
    public function resolvePeriod(string $preset, ?string $startDate = null, ?string $endDate = null): array
    {
        $today = Carbon::today();

        [$start, $end] = match ($preset) {
            self::PERIOD_TODAY => [$today->copy(), $today->copy()],
            self::PERIOD_LAST_7_DAYS => [$today->copy()->subDays(6), $today->copy()],
            self::PERIOD_THIS_MONTH => [$today->copy()->startOfMonth(), $today->copy()],
            self::PERIOD_LAST_MONTH => [
                $today->copy()->subMonthNoOverflow()->startOfMonth(),
                $today->copy()->subMonthNoOverflow()->endOfMonth()->startOfDay(),
            ],
            self::PERIOD_CUSTOM => $this->customRange($startDate, $endDate),
            default => throw new InvalidArgumentException("Periode laporan tidak dikenal: {$preset}"),
        };

        return [
            'preset' => $preset,
            'start' => $start,
            'end' => $end,
            'label' => $this->periodLabel($preset, $start, $end),
        ];
    }

    /**
     * Headline cash-vs-accrual figures for the owner dashboard, plus the detailed report.
     *
     * @return array<string, mixed>
     */
    public function getFinancialReport(Carbon $startDate, Carbon $endDate): array
    {
        $report = $this->generate($startDate, $endDate);
        $cash = $report['cash_basis'];
        $accrual = $report['accrual_basis'];

        $netCashFlow = $cash['net_cash_flow'];
        $netProfit = (float) $accrual['net_profit'];

        return [
            'period' => $report['period'],
            'cash_flow' => [
                'cash_in' => [
                    'tunai' => $cash['cash_in'],
                    'transfer' => $cash['bank_transfer_in'],
                    'qris' => $cash['qris_in'],
                    'ewallet' => $cash['ewallet_in'],
                    'total' => $cash['total_in'],
                ],
                'cash_out' => $cash['total_out'],
                'net_cash_flow' => $netCashFlow,
            ],
            'accrual' => [
                'recognized_revenue' => $accrual['recognized_revenue'],
                'operating_expenses' => $accrual['total_expenses'],
                'net_operating_profit' => $netProfit,
                'profit_margin_percent' => $accrual['profit_margin_percent'],
            ],
            'health' => [
                'total_unpaid_receivables' => $report['receivables']['total_outstanding'],
                // Positive: part of the profit is still sitting in customer receivables.
                'cash_to_accrual_gap' => $this->money($netProfit - $netCashFlow),
            ],
            'details' => $report,
        ];
    }

    /**
     * Stream every order taken in the range as CSV (one row per nota).
     */
    public function exportTransactionsCsv(Carbon $startDate, Carbon $endDate): StreamedResponse
    {
        $rangeStart = $startDate->copy()->startOfDay();
        $rangeEnd = $endDate->copy()->endOfDay();
        $filename = sprintf('laporan-transaksi_%s_%s.csv', $rangeStart->toDateString(), $rangeEnd->toDateString());

        return response()->streamDownload(function () use ($rangeStart, $rangeEnd) {
            $out = fopen('php://output', 'w');
            // UTF-8 BOM so Excel renders customer names with non-ASCII characters correctly.
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['No Nota', 'Tanggal', 'Pelanggan', 'Status Produksi', 'Total Tagihan', 'Terbayar', 'Sisa Piutang', 'Metode Pembayaran'], escape: '');

            Transaction::query()
                ->with([
                    'customer:id,name',
                    'payments' => fn ($q) => $q->where('payment_status', 'completed')->select('id', 'transaction_id', 'payment_method'),
                ])
                ->whereBetween('intake_at', [$rangeStart, $rangeEnd])
                ->orderBy('intake_at')
                ->orderBy('id')
                ->chunkById(500, function (Collection $chunk) use ($out) {
                    foreach ($chunk as $txn) {
                        $methods = $txn->payments
                            ->map(fn (Payment $p) => $p->payment_method instanceof PaymentMethod ? $p->payment_method->label() : (string) $p->payment_method)
                            ->unique()
                            ->implode(', ');

                        fputcsv($out, [
                            $this->csvSafe($txn->invoice_number),
                            $txn->intake_at?->format('Y-m-d H:i'),
                            $this->csvSafe($txn->customer?->name ?? '-'),
                            $txn->production_status instanceof ProductionStatus ? $txn->production_status->label() : (string) $txn->production_status,
                            number_format((float) $txn->total_amount, 2, '.', ''),
                            number_format((float) $txn->paid_amount, 2, '.', ''),
                            number_format((float) $txn->outstanding_balance, 2, '.', ''),
                            $methods !== '' ? $methods : 'Belum Bayar',
                        ], escape: '');
                    }
                });

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Neutralise spreadsheet formula injection in user-supplied text cells.
     */
    private function csvSafe(?string $value): string
    {
        $value = (string) $value;

        return $value !== '' && in_array($value[0], ['=', '+', '-', '@', "\t", "\r"], true) ? "'".$value : $value;
    }

    /**
     * Build the full profit & loss report for an inclusive date range.
     *
     * @return array<string, mixed>
     */
    public function generate(Carbon $start, Carbon $end): array
    {
        $rangeStart = $start->copy()->startOfDay();
        $rangeEnd = $end->copy()->endOfDay();

        $payments = Payment::query()
            ->where('payment_status', 'completed')
            ->whereBetween('paid_at', [$rangeStart, $rangeEnd])
            ->get(['id', 'transaction_id', 'payment_method', 'amount', 'paid_at']);

        $expenses = Expense::query()
            // whereDate: the date cast may persist a time part on sqlite, which breaks a string BETWEEN.
            ->whereDate('expense_date', '>=', $rangeStart->toDateString())
            ->whereDate('expense_date', '<=', $rangeEnd->toDateString())
            ->get(['id', 'category', 'amount', 'payment_method', 'expense_date']);

        $recognized = $this->recognizedTransactions($rangeStart, $rangeEnd);

        $cashBasis = $this->cashBasis($payments, $expenses);
        $accrualBasis = $this->accrualBasis($recognized, $expenses);

        return [
            'period' => [
                'start_date' => $rangeStart->toDateString(),
                'end_date' => $rangeEnd->toDateString(),
                'days' => (int) $rangeStart->diffInDays($rangeEnd->copy()->startOfDay()) + 1,
            ],
            'cash_basis' => $cashBasis,
            'accrual_basis' => $accrualBasis,
            'reconciliation' => $this->reconciliation($payments, $recognized, $cashBasis, $accrualBasis),
            'expense_breakdown' => $this->expenseBreakdown($expenses),
            'service_mix' => $this->serviceMix($recognized),
            'trend' => $this->trend($rangeStart, $rangeEnd, $payments, $expenses, $recognized),
            'receivables' => $this->outstandingReceivables(),
        ];
    }

    /**
     * Outstanding (unpaid) receivables across all non-cancelled orders, regardless of period.
     *
     * @return array{total_outstanding: float, orders_count: int, customers_count: int}
     */
    public function outstandingReceivables(): array
    {
        $rows = Transaction::query()
            ->where('outstanding_balance', '>', 0)
            ->where('production_status', '!=', ProductionStatus::CANCELLED->value)
            ->get(['id', 'customer_id', 'outstanding_balance']);

        return [
            'total_outstanding' => $this->money($rows->sum(fn ($t) => (float) $t->outstanding_balance)),
            'orders_count' => $rows->count(),
            'customers_count' => $rows->pluck('customer_id')->filter()->unique()->count(),
        ];
    }

    /**
     * Completed orders whose completion falls inside the range. Completion time is
     * actual_completion_at, falling back to picked_up_at, then updated_at for legacy rows.
     *
     * @return Collection<int, Transaction>
     */
    private function recognizedTransactions(Carbon $rangeStart, Carbon $rangeEnd): Collection
    {
        return Transaction::query()
            ->with('items:id,transaction_id,pricing_unit_snapshot,actual_quantity,subtotal_snapshot')
            ->where('production_status', ProductionStatus::COMPLETED->value)
            ->whereRaw(
                'COALESCE(actual_completion_at, picked_up_at, updated_at) BETWEEN ? AND ?',
                [$rangeStart->toDateTimeString(), $rangeEnd->toDateTimeString()]
            )
            ->get();
    }

    /**
     * @param  Collection<int, Payment>  $payments
     * @param  Collection<int, Expense>  $expenses
     * @return array<string, float>
     */
    private function cashBasis(Collection $payments, Collection $expenses): array
    {
        $byMethod = fn (string ...$methods) => $this->money(
            $payments->filter(fn (Payment $p) => in_array($this->methodOf($p), $methods, true))
                ->sum(fn (Payment $p) => (float) $p->amount)
        );

        $cashIn = $byMethod(PaymentMethod::CASH->value);
        $qrisIn = $byMethod(PaymentMethod::QRIS->value);
        $transferIn = $byMethod(PaymentMethod::BANK_TRANSFER->value);
        $ewalletIn = $byMethod(PaymentMethod::EWALLET->value, PaymentMethod::E_WALLET->value);
        $digitalIn = $this->money($qrisIn + $transferIn + $ewalletIn);
        $totalIn = $this->money($cashIn + $digitalIn);

        $cashOut = $this->money($expenses->where('payment_method', 'cash')->sum(fn ($e) => (float) $e->amount));
        $totalOut = $this->money($expenses->sum(fn ($e) => (float) $e->amount));
        $transferOut = $this->money($totalOut - $cashOut);

        return [
            'cash_in' => $cashIn,
            'qris_in' => $qrisIn,
            'bank_transfer_in' => $transferIn,
            'ewallet_in' => $ewalletIn,
            'digital_in' => $digitalIn,
            'total_in' => $totalIn,
            'cash_out' => $cashOut,
            'transfer_out' => $transferOut,
            'total_out' => $totalOut,
            'net_cash_on_hand' => $this->money($cashIn - $cashOut),
            'net_cash_flow' => $this->money($totalIn - $totalOut),
        ];
    }

    /**
     * @param  Collection<int, Transaction>  $recognized
     * @param  Collection<int, Expense>  $expenses
     * @return array<string, float|int>
     */
    private function accrualBasis(Collection $recognized, Collection $expenses): array
    {
        $revenue = $this->money($recognized->sum(fn (Transaction $t) => (float) $t->total_amount));
        $unpaidPortion = $this->money($recognized->sum(fn (Transaction $t) => (float) $t->outstanding_balance));
        $totalExpenses = $this->money($expenses->sum(fn ($e) => (float) $e->amount));
        $netProfit = $this->money($revenue - $totalExpenses);

        return [
            'recognized_revenue' => $revenue,
            'recognized_orders_count' => $recognized->count(),
            'recognized_unpaid_portion' => $unpaidPortion,
            'total_expenses' => $totalExpenses,
            'net_profit' => $netProfit,
            'profit_margin_percent' => $revenue > 0 ? round(($netProfit / $revenue) * 100, 1) : 0.0,
        ];
    }

    /**
     * Explain exactly why net cash flow differs from accrual profit:
     * difference = money received for orders not recognized this period
     *            - recognized revenue not collected within this period.
     *
     * @param  Collection<int, Payment>  $payments
     * @param  Collection<int, Transaction>  $recognized
     * @param  array<string, float>  $cash
     * @param  array<string, float|int>  $accrual
     * @return array<string, float>
     */
    private function reconciliation(Collection $payments, Collection $recognized, array $cash, array $accrual): array
    {
        $recognizedIds = $recognized->pluck('id')->flip();

        $collectedOnRecognized = $this->money(
            $payments->filter(fn (Payment $p) => $recognizedIds->has($p->transaction_id))
                ->sum(fn (Payment $p) => (float) $p->amount)
        );

        $receivedForOtherOrders = $this->money($cash['total_in'] - $collectedOnRecognized);
        $recognizedNotCollected = $this->money((float) $accrual['recognized_revenue'] - $collectedOnRecognized);

        return [
            'received_for_unrecognized_orders' => $receivedForOtherOrders,
            'recognized_not_collected_in_period' => $recognizedNotCollected,
            'difference' => $this->money($cash['net_cash_flow'] - (float) $accrual['net_profit']),
        ];
    }

    /**
     * @param  Collection<int, Expense>  $expenses
     * @return list<array{category: string, label: string, amount: float, percentage: float}>
     */
    private function expenseBreakdown(Collection $expenses): array
    {
        $total = (float) $expenses->sum(fn ($e) => (float) $e->amount);

        return $expenses
            ->groupBy(fn ($e) => (string) $e->category)
            ->map(function (Collection $group, string $category) use ($total) {
                $amount = $this->money($group->sum(fn ($e) => (float) $e->amount));

                return [
                    'category' => $category,
                    'label' => ExpenseCategory::tryFrom($category)?->label() ?? $category,
                    'amount' => $amount,
                    'percentage' => $total > 0 ? round(($amount / $total) * 100, 1) : 0.0,
                ];
            })
            ->sortByDesc('amount')
            ->values()
            ->all();
    }

    /**
     * Revenue composition of recognized orders by pricing unit.
     *
     * @param  Collection<int, Transaction>  $recognized
     * @return list<array{unit: string, label: string, quantity: float, revenue: float, percentage: float}>
     */
    private function serviceMix(Collection $recognized): array
    {
        $items = $recognized->flatMap(fn (Transaction $t) => $t->items);
        $total = (float) $items->sum(fn ($i) => (float) $i->subtotal_snapshot);

        $labels = ['kg' => 'Kiloan', 'pcs' => 'Satuan', 'm2' => 'Karpet / Meter'];

        return $items
            ->groupBy(fn ($i) => (string) $i->pricing_unit_snapshot)
            ->map(function (Collection $group, string $unit) use ($total, $labels) {
                $revenue = $this->money($group->sum(fn ($i) => (float) $i->subtotal_snapshot));

                return [
                    'unit' => $unit,
                    'label' => $labels[$unit] ?? strtoupper($unit),
                    'quantity' => round((float) $group->sum(fn ($i) => (float) $i->actual_quantity), 2),
                    'revenue' => $revenue,
                    'percentage' => $total > 0 ? round(($revenue / $total) * 100, 1) : 0.0,
                ];
            })
            ->sortByDesc('revenue')
            ->values()
            ->all();
    }

    /**
     * Daily buckets for short ranges, monthly buckets for long ones.
     *
     * @param  Collection<int, Payment>  $payments
     * @param  Collection<int, Expense>  $expenses
     * @param  Collection<int, Transaction>  $recognized
     * @return array{granularity: string, points: list<array<string, mixed>>}
     */
    private function trend(Carbon $rangeStart, Carbon $rangeEnd, Collection $payments, Collection $expenses, Collection $recognized): array
    {
        $days = (int) $rangeStart->diffInDays($rangeEnd->copy()->startOfDay()) + 1;
        $monthly = $days > self::DAILY_TREND_MAX_DAYS;
        $keyFormat = $monthly ? 'Y-m' : 'Y-m-d';

        $buckets = [];
        $cursor = $rangeStart->copy()->startOfDay();
        while ($cursor->lte($rangeEnd)) {
            $key = $cursor->format($keyFormat);
            $buckets[$key] ??= [
                'key' => $key,
                'label' => $monthly
                    ? $cursor->copy()->locale('id')->translatedFormat('M Y')
                    : $cursor->copy()->locale('id')->translatedFormat('d M'),
                // revenue = accrual (completed orders); received = all payment methods;
                // cash_received / cash_expenses = physical cash only (drawer movement).
                'revenue' => 0.0,
                'received' => 0.0,
                'cash_received' => 0.0,
                'expenses' => 0.0,
                'cash_expenses' => 0.0,
            ];
            $monthly ? $cursor->addMonthNoOverflow()->startOfMonth() : $cursor->addDay();
        }

        foreach ($recognized as $txn) {
            $at = $txn->actual_completion_at ?? $txn->picked_up_at ?? $txn->updated_at;
            $key = Carbon::parse($at)->format($keyFormat);
            if (isset($buckets[$key])) {
                $buckets[$key]['revenue'] += (float) $txn->total_amount;
            }
        }

        foreach ($payments as $payment) {
            $key = Carbon::parse($payment->paid_at)->format($keyFormat);
            if (isset($buckets[$key])) {
                $buckets[$key]['received'] += (float) $payment->amount;
                if ($this->methodOf($payment) === PaymentMethod::CASH->value) {
                    $buckets[$key]['cash_received'] += (float) $payment->amount;
                }
            }
        }

        foreach ($expenses as $expense) {
            $key = Carbon::parse($expense->expense_date)->format($keyFormat);
            if (isset($buckets[$key])) {
                $buckets[$key]['expenses'] += (float) $expense->amount;
                if ($expense->payment_method === 'cash') {
                    $buckets[$key]['cash_expenses'] += (float) $expense->amount;
                }
            }
        }

        $points = array_map(fn (array $b) => [
            ...$b,
            'revenue' => $this->money($b['revenue']),
            'received' => $this->money($b['received']),
            'cash_received' => $this->money($b['cash_received']),
            'expenses' => $this->money($b['expenses']),
            'cash_expenses' => $this->money($b['cash_expenses']),
            'net_cash_on_hand' => $this->money($b['cash_received'] - $b['cash_expenses']),
        ], array_values($buckets));

        return [
            'granularity' => $monthly ? 'monthly' : 'daily',
            'points' => $points,
        ];
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function customRange(?string $startDate, ?string $endDate): array
    {
        if (! $startDate || ! $endDate) {
            throw new InvalidArgumentException('Rentang tanggal kustom wajib memiliki tanggal awal dan akhir.');
        }

        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->startOfDay();

        if ($end->lt($start)) {
            throw new InvalidArgumentException('Tanggal akhir tidak boleh sebelum tanggal awal.');
        }

        if ($start->diffInDays($end) + 1 > self::MAX_RANGE_DAYS) {
            throw new InvalidArgumentException('Rentang laporan maksimal '.self::MAX_RANGE_DAYS.' hari.');
        }

        return [$start, $end];
    }

    private function periodLabel(string $preset, Carbon $start, Carbon $end): string
    {
        $fmt = fn (Carbon $d) => $d->copy()->locale('id')->translatedFormat('d M Y');

        return match ($preset) {
            self::PERIOD_TODAY => 'Hari Ini ('.$fmt($start).')',
            self::PERIOD_LAST_7_DAYS => '7 Hari Terakhir ('.$fmt($start).' – '.$fmt($end).')',
            self::PERIOD_THIS_MONTH => 'Bulan Ini ('.$start->copy()->locale('id')->translatedFormat('F Y').')',
            self::PERIOD_LAST_MONTH => 'Bulan Lalu ('.$start->copy()->locale('id')->translatedFormat('F Y').')',
            default => $start->isSameDay($end) ? $fmt($start) : $fmt($start).' – '.$fmt($end),
        };
    }

    private function methodOf(Payment $payment): string
    {
        $method = $payment->payment_method;

        return $method instanceof PaymentMethod ? $method->value : (string) $method;
    }

    private function money(float|int|string $value): float
    {
        return round((float) $value, 2);
    }
}
