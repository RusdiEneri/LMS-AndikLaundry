<?php

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Enums\ProductionStatus;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ProductionStatusService
{
    /**
     * Transition a transaction to a new production status.
     *
     * @param  array<string, mixed>  $metadata
     *
     * @throws InvalidArgumentException
     */
    public function transition(
        Transaction $transaction,
        ProductionStatus|string $targetStatus,
        ?User $user = null,
        ?string $notes = null,
        array $metadata = []
    ): Transaction {
        return DB::transaction(function () use ($transaction, $targetStatus, $user, $notes, $metadata): Transaction {
            $fromStatus = $transaction->production_status instanceof ProductionStatus
                ? $transaction->production_status
                : ProductionStatus::from($transaction->production_status);

            $toStatus = $targetStatus instanceof ProductionStatus
                ? $targetStatus
                : ProductionStatus::from((string) $targetStatus);

            // Verify basic state transition validity via enum
            if (! $fromStatus->canTransitionTo($toStatus)) {
                throw new InvalidArgumentException(
                    "Invalid production status transition from '{$fromStatus->value}' to '{$toStatus->value}'."
                );
            }

            // QC / Ironing Rewash Loop Validation
            if ($toStatus === ProductionStatus::WASHING && in_array($fromStatus, [ProductionStatus::QC, ProductionStatus::IRONING], true)) {
                $reason = $notes ?? $metadata['reason'] ?? null;
                if (is_null($reason) || trim((string) $reason) === '') {
                    throw new InvalidArgumentException('Rewash requires a reason code or notes.');
                }

                if ($transaction->rewash_count >= 2) {
                    throw new InvalidArgumentException('Maximum rewash limit reached (2 cycles). Order cannot undergo further rewash cycles.');
                }

                $transaction->rewash_count = ($transaction->rewash_count ?? 0) + 1;
            }

            // Physical Rack Staging Coordinate Requirement
            if ($toStatus === ProductionStatus::READY_FOR_PICKUP || $toStatus === ProductionStatus::SELESAI) {
                $rackCoordinate = $metadata['staging_rack'] ?? $transaction->staging_rack;
                if ($toStatus === ProductionStatus::READY_FOR_PICKUP && (is_null($rackCoordinate) || trim((string) $rackCoordinate) === '')) {
                    throw new InvalidArgumentException('A valid staging rack coordinate is required when marking order ready for pickup.');
                }
                if (! is_null($rackCoordinate) && trim((string) $rackCoordinate) !== '') {
                    $transaction->staging_rack = trim((string) $rackCoordinate);
                }
            }

            // Counter Release Guard (blocking release to completed if unpaid unless debt approved)
            if ($toStatus === ProductionStatus::COMPLETED || $toStatus === ProductionStatus::SUDAH_DIAMBIL) {
                if (! $this->canRelease($transaction)) {
                    throw new InvalidArgumentException('Cannot release order: payment has not been settled and credit/debt is not approved.');
                }

                $transaction->actual_completion_at = now();
                $transaction->picked_up_at = now();
            }

            // Cancellation handling
            if ($toStatus === ProductionStatus::CANCELLED) {
                $cancellationReason = $notes ?? $metadata['reason'] ?? $metadata['cancellation_reason'] ?? 'Order cancelled.';
                $transaction->cancellation_reason = $cancellationReason;
            }

            // Record immutable status transition audit log
            $transaction->statusHistories()->create([
                'user_id' => $user?->id ?? auth()->id(),
                'from_status' => $fromStatus,
                'to_status' => $toStatus,
                'notes' => $notes,
                'metadata' => ! empty($metadata) ? $metadata : null,
            ]);

            $transaction->production_status = $toStatus;
            $transaction->save();

            return $transaction;
        });
    }

    /**
     * Check if an order is authorized to be released to completed.
     */
    public function canRelease(Transaction $transaction): bool
    {
        if ($transaction->is_debt_approved) {
            return true;
        }

        $paymentStatus = $transaction->payment_status instanceof PaymentStatus
            ? $transaction->payment_status
            : PaymentStatus::tryFrom((string) $transaction->payment_status);

        return in_array($paymentStatus, [PaymentStatus::PAID, PaymentStatus::OVERPAID], true)
            || (float) $transaction->outstanding_balance <= 0.00;
    }
}
