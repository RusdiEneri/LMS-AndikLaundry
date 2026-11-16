<?php

namespace App\Models;

use App\Enums\ServiceUnit;
use Database\Factories\ServiceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Service extends Model
{
    /** @use HasFactory<ServiceFactory> */
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'category_id',
        'code',
        'name',
        'description',
        'unit',
        'base_price',
        'min_weight',
        'estimated_duration_hours',
        'is_active',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'unit' => ServiceUnit::class,
            'base_price' => 'decimal:2',
            'min_weight' => 'decimal:2',
            'estimated_duration_hours' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Calculate billed quantity based on service unit and minimum weight rules.
     * For Kiloan ('kg'): returns max($actualWeight, min_weight ?? 3.00).
     * For Satuan/other: returns $actualWeight directly.
     */
    public function calculateBilledQuantity(float $actualWeight): float
    {
        $isKg = ($this->unit === ServiceUnit::KG)
            || ($this->unit instanceof ServiceUnit && $this->unit->value === 'kg')
            || $this->unit === 'kg';

        if ($isKg) {
            $threshold = (float) ($this->min_weight ?? 3.00);

            return max($actualWeight, $threshold);
        }

        return $actualWeight;
    }

    /**
     * Get the category that this service belongs to.
     *
     * @return BelongsTo<ServiceCategory, Service>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ServiceCategory::class, 'category_id');
    }

    /**
     * Get the turnaround speed tiers for this service.
     *
     * @return HasMany<ServiceTier>
     */
    public function tiers(): HasMany
    {
        return $this->hasMany(ServiceTier::class, 'service_id');
    }
}
