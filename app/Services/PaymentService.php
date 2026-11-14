<?php

namespace App\Services;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PaymentService
{
    /**
     * Record a payment against a transaction within a database transaction.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws InvalidArgumentException
     */
    public function recordPayment(Transaction $transaction, array $data, ?User $cashier = null): Payment
    {
        return DB::transaction(function () use ($transaction, $data, $cashier): Payment {
            // Guard idempotency: return existing payment if idempotency_key already exists
            if (! empty($data['idempotency_key'])) {
                $existingPayment = Payment::where('idempotency_key', $data['idempotency_key'])->first();
                if ($existingPayment) {
                    return $existingPayment;
                }
            }

            $amount = round((float) ($data['amount'] ?? 0), 2);
            if ($amount <= 0.00) {
                throw new InvalidArgumentException('Payment amount must be greater than zero.');
            }

            // Resolve payment method enum
            $rawMethod = $data['payment_method'] ?? 'cash';
            if ($rawMethod instanceof PaymentMethod) {
                $paymentMethod = $rawMethod;
            } else {
                $normalizedMethod = strtolower(trim((string) $rawMethod));
                $paymentMethod = PaymentMethod::tryFrom($normalizedMethod)
                    ?? match ($normalizedMethod) {
                        'e_wallet', 'ewallet' => PaymentMethod::EWALLET,
                        'bank_transfer', 'transfer' => PaymentMethod::BANK_TRANSFER,
                        'qris' => PaymentMethod::QRIS,
                        default => PaymentMethod::CASH,
                    };
            }

            // Calculate tender and change amount
            if ($paymentMethod === PaymentMethod::CASH) {
                $tenderAmount = isset($data['tender_amount'])
                    ? round((float) $data['tender_amount'], 2)
                    : $amount;

                if ($tenderAmount < $amount) {
                    throw new InvalidArgumentException('Cash tender amount cannot be less than payment amount.');
                }

                $changeAmount = max(0.00, round($tenderAmount - $amount, 2));
            } else {
                // Non-cash tenders (QRIS, Bank Transfer, E-Wallet) must match exact amount
                $tenderAmount = $amount;
                $changeAmount = 0.00;
            }

            // Resolve cashier
            $cashierId = $cashier?->id ?? $data['cashier_id'] ?? auth()->id() ?? $transaction->cashier_id;
            if (! $cashierId) {
                $cashierId = User::whereIn('role', ['cashier', 'owner'])->value('id') ?? 1;
            }

            // Generate unique sequential payment number (e.g. PAY-YYYYMMDD-XXXX)
            $paymentNumber = $this->generatePaymentNumber();

            $payment = Payment::create([
                'transaction_id' => $transaction->id,
                'cashier_id' => $cashierId,
                'shift_id' => $data['shift_id'] ?? $transaction->shift_id,
                'payment_number' => $paymentNumber,
                'idempotency_key' => $data['idempotency_key'] ?? null,
                'payment_method' => $paymentMethod,
                'amount' => $amount,
                'tender_amount' => $tenderAmount,
                'change_amount' => $changeAmount,
                'payment_status' => $data['payment_status'] ?? 'completed',
                'transaction_reference' => $data['transaction_reference'] ?? null,
                'paid_at' => ! empty($data['paid_at']) ? Carbon::parse($data['paid_at']) : now(),
                'notes' => $data['notes'] ?? null,
            ]);

            // Recalculate transaction financials based on completed payments
            $this->recalculateTransactionFinancials($transaction);

            // Update customer outstanding receivables ledger
            if ($transaction->customer) {
                $transaction->customer->updateReceivables();
            }

            return $payment;
        });
    }

    /**
     * Recalculate and persist transaction financial totals from completed payments.
     */
    public function recalculateTransactionFinancials(Transaction $transaction): Transaction
    {
        $completedPayments = $transaction->payments()->where('payment_status', 'completed')->get();

        $totalPaid = round((float) $completedPayments->sum('amount'), 2);
        $totalCashChange = round((float) $completedPayments
            ->where('payment_method', PaymentMethod::CASH)
            ->sum('change_amount'), 2);

        $totalAmount = (float) $transaction->total_amount;
        $outstanding = max(0.00, round($totalAmount - $totalPaid, 2));

        $transaction->paid_amount = $totalPaid;
        $transaction->change_amount = $totalCashChange;
        $transaction->outstanding_balance = $outstanding;

        if ($totalAmount == 0.00) {
            $transaction->payment_status = PaymentStatus::PAID;
        } elseif ($totalPaid >= $totalAmount) {
            $transaction->payment_status = $totalPaid > $totalAmount
                ? PaymentStatus::OVERPAID
                : PaymentStatus::PAID;
        } elseif ($totalPaid > 0.00) {
            $transaction->payment_status = PaymentStatus::PARTIALLY_PAID;
        } else {
            $transaction->payment_status = PaymentStatus::UNPAID;
        }

        // Strictly orthogonal separation: payment status change NEVER alters production status
        $transaction->save();

        return $transaction;
    }

    /**
     * Generate an atomic sequential daily payment number (PAY-YYYYMMDD-XXXX).
     */
    protected function generatePaymentNumber(): string
    {
        $dateStr = now()->format('Ymd');
        $prefix = "PAY-{$dateStr}-";

        $lastPayment = Payment::where('payment_number', 'like', "{$prefix}%")
            ->orderBy('payment_number', 'desc')
            ->lockForUpdate()
            ->first();

        if ($lastPayment) {
            $lastSeq = (int) substr($lastPayment->payment_number, -4);
            $nextSeq = $lastSeq + 1;
        } else {
            $nextSeq = 1;
        }

        return $prefix.str_pad((string) $nextSeq, 4, '0', STR_PAD_LEFT);
    }
}
