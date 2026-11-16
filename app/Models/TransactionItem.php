<?php

namespace App\Models;

use Database\Factories\TransactionItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransactionItem extends Model
{
    /** @use HasFactory<TransactionItemFactory> */
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'transaction_items';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'transaction_id',
        'service_id',
        'service_tier_id',
        'item_name_snapshot',
        'pricing_unit_snapshot',
        'unit_price_snapshot',
        'actual_quantity',
        'billed_quantity',
        'discount_amount_snapshot',
        'subtotal_snapshot',
        'perfume_variant',
        'item_condition_notes',
        'special_instructions',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'unit_price_snapshot' => 'decimal:2',
            'actual_quantity' => 'decimal:2',
            'billed_quantity' => 'decimal:2',
            'discount_amount_snapshot' => 'decimal:2',
            'subtotal_snapshot' => 'decimal:2',
        ];
    }

    /**
     * Create snapshot array from catalog master record and options.
     * Freezes all current pricing details against future catalog price changes.
     *
     * @return array<string, mixed>
     */
    public static function snapshotFrom(
        Service $service,
        float $actualQty,
        ?ServiceTier $tier = null,
        ?string $perfume = null,
        ?string $conditionNotes = null,
        ?string $instructions = null,
        float $discount = 0.00
    ): array {
        $billedQty = $service->calculateBilledQuantity($actualQty);
        $tierMultiplier = $tier ? (float) ($tier->multiplier_factor ?? 1.00) : 1.00;
        $unitPrice = round((float) $service->base_price * $tierMultiplier, 2);
        $discountAmount = max(0.00, $discount);
        $subtotal = max(0.00, round(($billedQty * $unitPrice) - $discountAmount, 2));

        $unitValue = is_string($service->unit) ? $service->unit : ($service->unit?->value ?? 'kg');
        $itemName = $service->name.($tier ? " ({$tier->tier_name})" : '');

        return [
            'service_id' => $service->id,
            'service_tier_id' => $tier?->id,
            'item_name_snapshot' => $itemName,
            'pricing_unit_snapshot' => $unitValue,
            'unit_price_snapshot' => $unitPrice,
            'actual_quantity' => $actualQty,
            'billed_quantity' => $billedQty,
            'discount_amount_snapshot' => $discountAmount,
            'subtotal_snapshot' => $subtotal,
            'perfume_variant' => $perfume,
            'item_condition_notes' => $conditionNotes,
            'special_instructions' => $instructions,
        ];
    }

    /**
     * Get the transaction that owns this line item.
     *
     * @return BelongsTo<Transaction, TransactionItem>
     */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'transaction_id');
    }

    /**
     * Alias relation for Order context.
     *
     * @return BelongsTo<Order, TransactionItem>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'transaction_id');
    }

    /**
     * Get the service catalog item reference (may be null if service deleted).
     *
     * @return BelongsTo<Service, TransactionItem>
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'service_id');
    }

    /**
     * Get the turnaround speed tier reference.
     *
     * @return BelongsTo<ServiceTier, TransactionItem>
     */
    public function serviceTier(): BelongsTo
    {
        return $this->belongsTo(ServiceTier::class, 'service_tier_id');
    }
}
