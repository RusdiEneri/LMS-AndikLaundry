<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Support\PhoneFormatter;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $rawPhone = '08'.fake()->unique()->numerify('##########');

        return [
            'name' => fake()->name(),
            'phone' => $rawPhone,
            'phone_normalized' => PhoneFormatter::normalizeToE164($rawPhone),
            'email' => fake()->unique()->safeEmail(),
            'address' => fake()->address(),
            'total_orders_count' => fake()->numberBetween(0, 20),
            'total_spend' => fake()->randomFloat(2, 0, 500000),
            'outstanding_receivables' => 0.00,
            'notes' => fake()->optional()->sentence(),
            'whatsapp_consent' => true,
            'consent_at' => now(),
        ];
    }

    /**
     * Indicate that customer has outstanding receivables (debt).
     */
    public function withDebt(float $amount = 50000.00): self
    {
        return $this->state(fn (array $attributes) => [
            'outstanding_receivables' => $amount,
        ]);
    }

    /**
     * Indicate that customer has not consented to WhatsApp messages.
     */
    public function withoutConsent(): self
    {
        return $this->state(fn (array $attributes) => [
            'whatsapp_consent' => false,
            'consent_at' => null,
        ]);
    }
}
