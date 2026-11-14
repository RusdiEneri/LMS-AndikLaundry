<?php

namespace Database\Factories;

use App\Models\Service;
use App\Models\Transaction;
use App\Models\TransactionItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TransactionItem>
 */
class TransactionItemFactory extends Factory
{
    protected $model = TransactionItem::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $unitPrice = 8000.00;
        $actualQty = 3.00;
        $billedQty = 3.00;
        $subtotal = round($unitPrice * $billedQty, 2);

        return [
            'transaction_id' => Transaction::factory(),
            'service_id' => Service::factory(),
            'service_tier_id' => null,
            'item_name_snapshot' => 'Cuci Komplit Reguler',
            'pricing_unit_snapshot' => 'kg',
            'unit_price_snapshot' => $unitPrice,
            'actual_quantity' => $actualQty,
            'billed_quantity' => $billedQty,
            'discount_amount_snapshot' => 0.00,
            'subtotal_snapshot' => $subtotal,
            'perfume_variant' => 'Ocean Fresh',
            'item_condition_notes' => null,
            'special_instructions' => null,
        ];
    }
}
