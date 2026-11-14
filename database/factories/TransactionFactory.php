<?php

namespace Database\Factories;

use App\Enums\PaymentStatus;
use App\Enums\ProductionStatus;
use App\Models\Customer;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Transaction>
 */
class TransactionFactory extends Factory
{
    protected $model = Transaction::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subtotal = 30000.00;
        $total = $subtotal;

        return [
            'invoice_number' => 'INV-' . date('Ymd') . '-' . fake()->unique()->numerify('####'),
            'customer_id' => Customer::factory(),
            'cashier_id' => User::factory()->state(['role' => 'cashier']),
            'shift_id' => null,
            'approved_by_id' => null,
            'production_status' => ProductionStatus::RECEIVED,
            'payment_status' => PaymentStatus::UNPAID,
            'intake_at' => now(),
            'target_completion_at' => now()->addHours(48),
            'actual_completion_at' => null,
            'picked_up_at' => null,
            'subtotal_amount' => $subtotal,
            'discount_amount' => 0.00,
            'surcharge_amount' => 0.00,
            'tax_amount' => 0.00,
            'total_amount' => $total,
            'paid_amount' => 0.00,
            'change_amount' => 0.00,
            'outstanding_balance' => $total,
            'staging_rack' => null,
            'rewash_count' => 0,
            'notes' => null,
            'is_debt_approved' => false,
            'debt_notes' => null,
        ];
    }

    /**
     * State for a fully paid transaction.
     */
    public function paid(): self
    {
        return $this->state(function (array $attributes) {
            $total = (float) ($attributes['total_amount'] ?? 30000.00);

            return [
                'payment_status' => PaymentStatus::PAID,
                'paid_amount' => $total,
                'change_amount' => 0.00,
                'outstanding_balance' => 0.00,
            ];
        });
    }

    /**
     * State for a partially paid (DP) transaction.
     */
    public function partiallyPaid(float $paid = 15000.00): self
    {
        return $this->state(function (array $attributes) use ($paid) {
            $total = (float) ($attributes['total_amount'] ?? 30000.00);

            return [
                'payment_status' => PaymentStatus::PARTIALLY_PAID,
                'paid_amount' => $paid,
                'change_amount' => 0.00,
                'outstanding_balance' => max(0.00, round($total - $paid, 2)),
            ];
        });
    }

    /**
     * Set production status state.
     */
    public function withProductionStatus(ProductionStatus $status): self
    {
        return $this->state(fn (array $attributes) => [
            'production_status' => $status,
        ]);
    }

    /**
     * Completed transaction state.
     */
    public function completed(): self
    {
        return $this->state(fn (array $attributes) => [
            'production_status' => ProductionStatus::COMPLETED,
            'actual_completion_at' => now(),
            'picked_up_at' => now(),
        ]);
    }
}
