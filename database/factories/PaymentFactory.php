<?php

namespace Database\Factories;

use App\Enums\PaymentMethod;
use App\Models\Payment;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $amount = 50000.00;

        return [
            'transaction_id' => Transaction::factory(),
            'cashier_id' => User::factory()->state(['role' => 'cashier']),
            'shift_id' => null,
            'payment_number' => 'PAY-'.date('Ymd').'-'.fake()->unique()->numerify('####'),
            'idempotency_key' => fake()->unique()->uuid(),
            'payment_method' => PaymentMethod::CASH,
            'amount' => $amount,
            'tender_amount' => $amount,
            'change_amount' => 0.00,
            'payment_status' => 'completed',
            'transaction_reference' => null,
            'paid_at' => now(),
            'notes' => null,
        ];
    }

    /**
     * Cash payment with excess tender.
     */
    public function cashWithExcess(float $amount, float $tender): self
    {
        return $this->state(fn () => [
            'payment_method' => PaymentMethod::CASH,
            'amount' => $amount,
            'tender_amount' => $tender,
            'change_amount' => max(0.00, round($tender - $amount, 2)),
        ]);
    }

    /**
     * QRIS payment.
     */
    public function qris(float $amount): self
    {
        return $this->state(fn () => [
            'payment_method' => PaymentMethod::QRIS,
            'amount' => $amount,
            'tender_amount' => $amount,
            'change_amount' => 0.00,
            'transaction_reference' => 'QRIS-'.fake()->numerify('##########'),
        ]);
    }
}
