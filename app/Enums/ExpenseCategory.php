<?php

namespace App\Enums;

enum ExpenseCategory: string
{
    case DETERGENT_CHEMICALS = 'detergent_chemicals';
    case GAS_LPG = 'gas_lpg';
    case PACKAGING_PLASTIC = 'packaging_plastic';
    case UTILITIES = 'utilities';
    case MAINTENANCE = 'maintenance';
    case OTHER = 'other';

    /**
     * Get Indonesian human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::DETERGENT_CHEMICALS => 'Deterjen & Pewangi',
            self::GAS_LPG => 'Gas LPG',
            self::PACKAGING_PLASTIC => 'Plastik & Hanger',
            self::UTILITIES => 'Listrik, Air & Wi-Fi',
            self::MAINTENANCE => 'Perawatan & Servis Mesin',
            self::OTHER => 'Lain-lain / Perlengkapan',
        };
    }
}

