<?php

namespace Database\Factories;

use App\Enums\ExpenseCategory;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<Expense>
 */
class ExpenseFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Expense::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $date = Carbon::today();

        return [
            'expense_number' => Expense::generateExpenseNumber($date) . '-' . fake()->unique()->numerify('####'),
            'user_id' => User::factory(),
            'amount' => fake()->randomElement([22000, 44000, 75000, 120000, 250000]),
            'category' => fake()->randomElement([
                ExpenseCategory::GAS_LPG->value,
                ExpenseCategory::DETERGENT_CHEMICALS->value,
                ExpenseCategory::PACKAGING_PLASTIC->value,
                ExpenseCategory::UTILITIES->value,
                ExpenseCategory::OTHER->value,
            ]),
            'description' => fake()->sentence(4),
            'payment_method' => 'cash',
            'expense_date' => $date->toDateString(),
            'receipt_image_path' => null,
        ];
    }
}

