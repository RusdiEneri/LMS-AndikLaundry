<?php

namespace App\Services;

use App\Models\BusinessSetting;
use App\Models\Expense;
use App\Models\Payment;
use App\Models\Transaction;
use Illuminate\Support\Carbon;

class DailyCashRecapService
{
    /**
     * Compute comprehensive daily cash and operational recap for a given date.
     *
     * @return array<string, mixed>
     */
    public function getRecapForDate(string|Carbon $date): array
    {
        $targetDate = is_string($date) ? Carbon::parse($date)->toDateString() : $date->toDateString();

        // 1. Inward Payments
        $payments = Payment::whereDate('paid_at', $targetDate)
            ->where('payment_status', 'completed')
            ->get();

        $cashPayments = (float) $payments->where('payment_method', 'cash')->sum('amount');
        $qrisPayments = (float) $payments->where('payment_method', 'qris')->sum('amount');
        $transferPayments = (float) $payments->where('payment_method', 'bank_transfer')->sum('amount');
        $ewalletPayments = (float) $payments->filter(fn ($p) => in_array($p->payment_method, ['ewallet', 'e_wallet']))->sum('amount');
        $digitalPayments = $qrisPayments + $transferPayments + $ewalletPayments;
        $totalPaymentsReceived = $cashPayments + $digitalPayments;

        // 2. Outward Operational Expenses
        $expenses = Expense::whereDate('expense_date', $targetDate)->get();
        $cashExpenses = (float) $expenses->where('payment_method', 'cash')->sum('amount');
        $transferExpenses = (float) $expenses->where('payment_method', 'bank_transfer')->sum('amount');
        $totalExpensesDisbursed = $cashExpenses + $transferExpenses;

        // 3. Physical Cash Position in Drawer/Pocket
        $netCashOnHand = $cashPayments - $cashExpenses;

        // 4. Daily Intake Transactions
        $transactions = Transaction::with('items')
            ->whereDate('intake_at', $targetDate)
            ->get();

        $totalOrdersCount = $transactions->count();
        $totalInvoicedTurnover = (float) $transactions->sum('total_amount');
        $newReceivablesCreated = (float) $transactions->sum('outstanding_balance');

        $totalKg = 0.0;
        $totalPcs = 0;
        foreach ($transactions as $txn) {
            foreach ($txn->items as $item) {
                if ($item->pricing_unit_snapshot === 'kg') {
                    $totalKg += (float) $item->actual_quantity;
                } else {
                    $totalPcs += (int) $item->actual_quantity;
                }
            }
        }

        // 5. Receivables (Piutang) Settled Today
        $debtSettledToday = (float) $payments->filter(function ($payment) use ($targetDate) {
            $txn = $payment->transaction;

            return $txn && Carbon::parse($txn->intake_at)->toDateString() < $targetDate;
        })->sum('amount');

        // Store branding
        $storeName = BusinessSetting::get('store.name', 'Andik Laundry');
        $storePhone = BusinessSetting::get('store.phone', '6281234567890');
        $storeAddress = BusinessSetting::get('store.address', 'Hendrosari, RT.06/RW.03, Hendrosari, Kec. Menganti, Kabupaten Gresik, Jawa Timur 61174');

        return [
            'date' => $targetDate,
            'date_formatted' => Carbon::parse($targetDate)->translatedFormat('l, d F Y'),
            'store_info' => [
                'name' => $storeName,
                'phone' => $storePhone,
                'address' => $storeAddress,
            ],
            'cash_summary' => [
                'cash_in' => $cashPayments,
                'cash_out' => $cashExpenses,
                'net_cash_on_hand' => $netCashOnHand,
            ],
            'digital_summary' => [
                'qris' => $qrisPayments,
                'bank_transfer' => $transferPayments,
                'ewallet' => $ewalletPayments,
                'total_digital' => $digitalPayments,
            ],
            'revenue_summary' => [
                'total_payments_received' => $totalPaymentsReceived,
                'total_orders_invoiced' => $totalInvoicedTurnover,
                'new_receivables' => $newReceivablesCreated,
                'debt_settled_today' => $debtSettledToday,
            ],
            'operational_volume' => [
                'total_orders' => $totalOrdersCount,
                'total_weight_kg' => round($totalKg, 2),
                'total_pieces' => $totalPcs,
            ],
            'expenses_list' => $expenses->map(fn ($exp) => [
                'id' => $exp->id,
                'expense_number' => $exp->expense_number,
                'category' => $exp->category,
                'category_label' => $exp->category,
                'description' => $exp->description,
                'amount' => (float) $exp->amount,
                'payment_method' => $exp->payment_method,
            ])->values()->all(),
        ];
    }
}
