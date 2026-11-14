<?php

namespace App\Models;

use Database\Factories\ServiceTierFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceTier extends Model
{
    /** @use HasFactory<ServiceTierFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'service_id',
        'tier_name',
        'tier_code',
        'multiplier_factor',
        'flat_surcharge',
        'duration_hours',
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
            'multiplier_factor' => 'decimal:2',
            'flat_surcharge' => 'decimal:2',
            'duration_hours' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Calculate additional surcharge amount for this turnaround speed tier.
     * Combines multiplier factor surcharge over 1.00x and flat surcharge.
     */
    public function calculateSurcharge(float $basePrice, float $billedQty): float
    {
        $baseTotal = $basePrice * $billedQty;
        $multiplier = (float) ($this->multiplier_factor ?? 1.00);
        $multiplierSurcharge = $multiplier > 1.00 ? ($baseTotal * ($multiplier - 1.00)) : 0.00;
        $flat = (float) ($this->flat_surcharge ?? 0.00);

        return round($multiplierSurcharge + $flat, 2);
    }

    /**
     * Get the service that owns this speed tier.
     *
     * @return BelongsTo<Service, ServiceTier>
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'service_id');
    }
}
