<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case CASH = 'cash';
    case QRIS = 'qris';
    case BANK_TRANSFER = 'bank_transfer';
    case EWALLET = 'ewallet';
    case E_WALLET = 'e_wallet';

    /**
     * Get human-readable localized Indonesian label.
     */
    public function label(): string
    {
        return match ($this) {
            self::CASH => 'Tunai (Cash)',
            self::QRIS => 'QRIS',
            self::BANK_TRANSFER => 'Transfer Bank',
            self::EWALLET, self::E_WALLET => 'E-Wallet',
        };
    }
}
