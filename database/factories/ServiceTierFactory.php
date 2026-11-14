<?php

namespace Database\Factories;

use App\Models\Service;
use App\Models\ServiceTier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ServiceTier>
 */
class ServiceTierFactory extends Factory
{
    protected $model = ServiceTier::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'service_id' => Service::factory(),
            'tier_name' => 'Reguler',
            'tier_code' => 'reguler',
            'multiplier_factor' => 1.00,
            'flat_surcharge' => 0.00,
            'duration_hours' => 48,
            'is_active' => true,
        ];
    }

    /**
     * State for Reguler tier.
     */
    public function reguler(): self
    {
        return $this->state(fn (array $attributes) => [
            'tier_name' => 'Reguler',
            'tier_code' => 'reguler',
            'multiplier_factor' => 1.00,
            'flat_surcharge' => 0.00,
            'duration_hours' => 48,
            'is_active' => true,
        ]);
    }

    /**
     * State for Kilat tier (24h).
     */
    public function kilat(float $flatSurcharge = 10000.00): self
    {
        return $this->state(fn (array $attributes) => [
            'tier_name' => 'Kilat',
            'tier_code' => 'kilat',
            'multiplier_factor' => 1.00,
            'flat_surcharge' => $flatSurcharge,
            'duration_hours' => 24,
            'is_active' => true,
        ]);
    }

    /**
     * State for Express tier (6-8h).
     */
    public function express(float $flatSurcharge = 20000.00, float $multiplier = 1.00): self
    {
        return $this->state(fn (array $attributes) => [
            'tier_name' => 'Express',
            'tier_code' => 'express',
            'multiplier_factor' => $multiplier,
            'flat_surcharge' => $flatSurcharge,
            'duration_hours' => 8,
            'is_active' => true,
        ]);
    }

    /**
     * State for Flash tier (2-3h).
     */
    public function flash(float $flatSurcharge = 35000.00, float $multiplier = 1.00): self
    {
        return $this->state(fn (array $attributes) => [
            'tier_name' => 'Flash',
            'tier_code' => 'flash',
            'multiplier_factor' => $multiplier,
            'flat_surcharge' => $flatSurcharge,
            'duration_hours' => 3,
            'is_active' => true,
        ]);
    }
}
