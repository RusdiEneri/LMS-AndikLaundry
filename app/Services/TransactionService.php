<?php

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Enums\ProductionStatus;
use App\Models\Customer;
use App\Models\Service;
use App\Models\ServiceTier;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class TransactionService
{
    public function __construct(
        protected InvoiceNumberGenerator $invoiceNumberGenerator
    ) {}

    /**
     * Create a new transaction intake with frozen price snapshots in a database transaction.
     *
     * @param  array<string, mixed>  $data
     * @throws InvalidArgumentException
     */
    public function createTransaction(array $data, ?User $cashier = null): Transaction
    {
        if (empty($data['items']) || !is_array($data['items'])) {
            throw new InvalidArgumentException('Transaction must contain at least one line item.');
        }

        return DB::transaction(function () use ($data, $cashier): Transaction {
            $customer = Customer::findOrFail($data['customer_id']);
            $cashierId = $cashier?->id ?? $data['cashier_id'] ?? auth()->id();

            if (!$cashierId) {
                // If no authenticated cashier, resolve to the first available cashier/owner
                $cashierId = User::whereIn('role', ['cashier', 'owner'])->value('id') ?? 1;
            }

            $invoiceNumber = $this->invoiceNumberGenerator->generate();

            $preparedItems = [];
            $subtotalSum = 0.00;
            $tierFlatSurchargesTotal = 0.00;
            $maxDurationHours = 48;

            foreach ($data['items'] as $itemData) {
                $service = Service::findOrFail($itemData['service_id']);
                $tier = !empty($itemData['service_tier_id'])
                    ? ServiceTier::find($itemData['service_tier_id'])
                    : null;

                $actualQty = (float) $itemData['actual_quantity'];
                $billedQty = $service->calculateBilledQuantity($actualQty);

                // Multiplier factor surcharge modifies unit price snapshot
                $tierMultiplier = $tier ? (float) ($tier->multiplier_factor ?? 1.00) : 1.00;
                $unitPriceSnapshot = round((float) $service->base_price * $tierMultiplier, 2);

                $itemDiscount = isset($itemData['discount_amount']) ? (float) $itemData['discount_amount'] : 0.00;
                $itemSubtotal = max(0.00, round(($billedQty * $unitPriceSnapshot) - $itemDiscount, 2));

                $unitValue = is_string($service->unit) ? $service->unit : ($service->unit?->value ?? 'kg');
                $itemName = $service->name . ($tier ? " ({$tier->tier_name})" : '');

                $preparedItems[] = [
                    'service_id' => $service->id,
                    'service_tier_id' => $tier?->id,
                    'item_name_snapshot' => $itemName,
                    'pricing_unit_snapshot' => $unitValue,
                    'unit_price_snapshot' => $unitPriceSnapshot,
                    'actual_quantity' => $actualQty,
                    'billed_quantity' => $billedQty,
                    'discount_amount_snapshot' => $itemDiscount,
                    'subtotal_snapshot' => $itemSubtotal,
                    'perfume_variant' => $itemData['perfume_variant'] ?? null,
                    'item_condition_notes' => $itemData['item_condition_notes'] ?? null,
                    'special_instructions' => $itemData['special_instructions'] ?? null,
                ];

                $subtotalSum += $itemSubtotal;

                // Any flat surcharge defined on the speed tier
                if ($tier && (float) ($tier->flat_surcharge ?? 0) > 0) {
                    $tierFlatSurchargesTotal += (float) $tier->flat_surcharge;
                }

                // Determine duration for target completion calculation
                $itemDuration = $tier?->duration_hours ?? $service->estimated_duration_hours ?? 48;
                if ($itemDuration > $maxDurationHours) {
                    $maxDurationHours = $itemDuration;
                }
            }

            $orderDiscount = max(0.00, (float) ($data['discount_amount'] ?? 0.00));
            // Cap discount at subtotal
            $appliedDiscount = min($subtotalSum, $orderDiscount);

            // Surcharge calculation: if caller explicitly provides order-level surcharge_amount,
            // treat it as authoritative (prevents multi-item speed tier surcharge accumulation defect).
            // Otherwise, fall back to the accumulated tier flat surcharges.
            if (array_key_exists('surcharge_amount', $data) && $data['surcharge_amount'] !== null) {
                $totalSurcharge = max(0.00, round((float) $data['surcharge_amount'], 2));
            } else {
                $totalSurcharge = round($tierFlatSurchargesTotal, 2);
            }

            $taxAmount = max(0.00, (float) ($data['tax_amount'] ?? 0.00));

            $totalAmount = max(0.00, round(($subtotalSum - $appliedDiscount) + $totalSurcharge + $taxAmount, 2));
            $paidAmount = max(0.00, (float) ($data['paid_amount'] ?? 0.00));

            $changeAmount = max(0.00, round($paidAmount - $totalAmount, 2));
            $outstandingBalance = max(0.00, round($totalAmount - $paidAmount, 2));

            // Determine payment status
            if ($totalAmount == 0.00) {
                $paymentStatus = PaymentStatus::PAID;
            } elseif ($paidAmount >= $totalAmount) {
                $paymentStatus = $paidAmount > $totalAmount ? PaymentStatus::OVERPAID : PaymentStatus::PAID;
            } elseif ($paidAmount > 0.00) {
                $paymentStatus = PaymentStatus::PARTIALLY_PAID;
            } else {
                $paymentStatus = PaymentStatus::UNPAID;
            }

            $targetCompletion = !empty($data['target_completion_at'])
                ? Carbon::parse($data['target_completion_at'])
                : now()->addHours($maxDurationHours);

            $transaction = Transaction::create([
                'invoice_number' => $invoiceNumber,
                'customer_id' => $customer->id,
                'cashier_id' => $cashierId,
                'shift_id' => $data['shift_id'] ?? null,
                'approved_by_id' => $data['approved_by_id'] ?? null,
                'production_status' => ProductionStatus::RECEIVED,
                'payment_status' => $paymentStatus,
                'intake_at' => now(),
                'target_completion_at' => $targetCompletion,
                'subtotal_amount' => $subtotalSum,
                'discount_amount' => $appliedDiscount,
                'surcharge_amount' => $totalSurcharge,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount,
                'paid_amount' => $paidAmount,
                'change_amount' => $changeAmount,
                'outstanding_balance' => $outstandingBalance,
                'staging_rack' => $data['staging_rack'] ?? null,
                'rewash_count' => 0,
                'notes' => $data['notes'] ?? null,
                'is_debt_approved' => (bool) ($data['is_debt_approved'] ?? false),
                'debt_notes' => $data['debt_notes'] ?? null,
            ]);

            foreach ($preparedItems as $itemAttributes) {
                $transaction->items()->create($itemAttributes);
            }

            if ($paidAmount > 0.00) {
                $rawMethod = $data['payment_method'] ?? 'cash';
                $transaction->payments()->create([
                    'cashier_id' => $cashierId,
                    'shift_id' => $data['shift_id'] ?? null,
                    'payment_number' => 'PAY-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6)),
                    'payment_method' => $rawMethod,
                    'amount' => min($paidAmount, $totalAmount),
                    'tender_amount' => $paidAmount,
                    'change_amount' => $changeAmount,
                    'payment_status' => 'completed',
                    'paid_at' => now(),
                    'notes' => $data['notes'] ?? 'Pembayaran saat intake kasir',
                ]);
            }

            // Update customer aggregate statistics
            $customer->increment('total_orders_count');
            $customer->total_spend = round((float) $customer->total_spend + $totalAmount, 2);
            $customer->outstanding_receivables = round((float) $customer->outstanding_receivables + $outstandingBalance, 2);
            $customer->save();

            return $transaction;
        });
    }
}
