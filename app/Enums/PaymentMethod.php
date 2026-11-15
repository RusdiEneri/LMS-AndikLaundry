<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case CASH = 'cash';
    case QRIS = 'qris';
    case BANK_TRANSFER = 'bank_transfer';
    case E_WALLET = 'e_wallet';

    public const EWALLET = self::E_WALLET;

    public function label(): string
    {
        return match ($this) {
            self::CASH => 'Tunai (Cash)',
            self::QRIS => 'QRIS',
            self::BANK_TRANSFER => 'Transfer Bank (Nonaktif)',
            self::E_WALLET => 'E-Wallet (Nonaktif)',
        };
    }

    /**
     * Active payment methods in POS cashier (Cash & QRIS only).
     */
    public static function activeMethods(): array
    {
        return [self::CASH, self::QRIS];
    }
}
