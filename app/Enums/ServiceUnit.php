<?php

namespace App\Enums;

enum ServiceUnit: string
{
    case KG = 'kg';
    case PCS = 'pcs';
    case M2 = 'm2';

    /**
     * Get human-readable localized label.
     */
    public function label(): string
    {
        return match ($this) {
            self::KG => 'Kilogram (Kg)',
            self::PCS => 'Satuan (Pcs)',
            self::M2 => 'Meter Persegi (M²)',
        };
    }
}
