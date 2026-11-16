<?php

namespace App\Services;

use App\Models\Transaction;
use Illuminate\Support\Carbon;

class CustomerReceivablesService
{
    /**
     * Get aggregate receivables summary and aging brackets.
     *
     * @return array<string, mixed>
     */
    public function getReceivablesSummary(): array
    {
        $today = Carbon::today();

        $unpaidTransactions = Transaction::with('customer')
            ->where('outstanding_balance', '>', 0)
            ->whereNotIn('production_status', ['cancelled'])
            ->orderByDesc('intake_at')
            ->get();

        $totalReceivables = (float) $unpaidTransactions->sum('outstanding_balance');

        $currentBucket = 0.0;     // 0-7 days
        $overdueBucket = 0.0;     // 8-14 days
        $delinquentBucket = 0.0;  // > 14 days

        $debtors = [];

        foreach ($unpaidTransactions as $txn) {
            $daysOld = $txn->intake_at ? Carbon::parse($txn->intake_at)->diffInDays($today) : 0;
            $balance = (float) $txn->outstanding_balance;

            if ($daysOld <= 7) {
                $currentBucket += $balance;
                $agingLabel = '0-7 Hari (Baru)';
                $agingBadge = 'teal';
            } elseif ($daysOld <= 14) {
                $overdueBucket += $balance;
                $agingLabel = '8-14 Hari (Jatuh Tempo)';
                $agingBadge = 'amber';
            } else {
                $delinquentBucket += $balance;
                $agingLabel = '> 14 Hari (Menunggak)';
                $agingBadge = 'destructive';
            }

            $customerName = $txn->customer?->name ?? 'Pelanggan Walk-In';
            $customerPhone = $txn->customer?->phone ?? '';

            $debtors[] = [
                'transaction_id' => $txn->id,
                'invoice_number' => $txn->invoice_number,
                'customer_id' => $txn->customer_id,
                'customer_name' => $customerName,
                'customer_phone' => $customerPhone,
                'intake_date' => $txn->intake_at ? Carbon::parse($txn->intake_at)->toDateString() : '-',
                'days_old' => $daysOld,
                'total_amount' => (float) $txn->total_amount,
                'paid_amount' => (float) $txn->paid_amount,
                'outstanding_balance' => $balance,
                'aging_label' => $agingLabel,
                'aging_badge' => $agingBadge,
            ];
        }

        $uniqueDebtorsCount = collect($debtors)->pluck('customer_id')->filter()->unique()->count();

        return [
            'total_receivables' => $totalReceivables,
            'debtors_count' => $uniqueDebtorsCount,
            'unpaid_orders_count' => count($debtors),
            'aging_breakdown' => [
                'current' => $currentBucket,
                'overdue' => $overdueBucket,
                'delinquent' => $delinquentBucket,
            ],
            'debtors' => $debtors,
        ];
    }
}
