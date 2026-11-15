<?php

namespace App\Enums;

enum ServiceUnit: string
{
    case KG = 'kg';
    case M2 = 'm2';
    case METER = 'meter';
    case PCS = 'pcs';

    public function label(): string
    {
        return match ($this) {
            self::KG => 'Kilogram (Kg)',
            self::M2, self::METER => 'Meter Persegi (m²)',
            self::PCS => 'Satuan (Pcs)',
        };
    }
}
