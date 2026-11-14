<?php

namespace Database\Factories;

use App\Enums\ServiceUnit;
use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Service>
 */
class ServiceFactory extends Factory
{
    protected $model = Service::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'category_id' => ServiceCategory::factory(),
            'code' => fake()->unique()->bothify('SVC-####'),
            'name' => fake()->words(3, true),
            'description' => fake()->optional()->sentence(),
            'unit' => ServiceUnit::KG,
            'base_price' => 7000.00,
            'min_weight' => 3.00,
            'estimated_duration_hours' => 48,
            'is_active' => true,
        ];
    }

    /**
     * State for Kiloan services.
     */
    public function kiloan(float $basePrice = 7000.00, float $minWeight = 3.00): self
    {
        return $this->state(fn (array $attributes) => [
            'unit' => ServiceUnit::KG,
            'base_price' => $basePrice,
            'min_weight' => $minWeight,
            'estimated_duration_hours' => 48,
        ]);
    }

    /**
     * State for Satuan services.
     */
    public function satuan(float $basePrice = 10000.00): self
    {
        return $this->state(fn (array $attributes) => [
            'unit' => ServiceUnit::PCS,
            'base_price' => $basePrice,
            'min_weight' => 1.00,
            'estimated_duration_hours' => 48,
        ]);
    }

    /**
     * State for Dry Clean services.
     */
    public function dryClean(float $basePrice = 35000.00): self
    {
        return $this->state(fn (array $attributes) => [
            'unit' => ServiceUnit::PCS,
            'base_price' => $basePrice,
            'min_weight' => 1.00,
            'estimated_duration_hours' => 72,
        ]);
    }
}
