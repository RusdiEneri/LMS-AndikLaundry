<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case UNPAID = 'unpaid';
    case PARTIALLY_PAID = 'partially_paid';
    case PAID = 'paid';
    case OVERPAID = 'overpaid';
    case REFUNDED = 'refunded';
    case PARTIALLY_REFUNDED = 'partially_refunded';

    /**
     * Get human-readable localized Indonesian label.
     */
    public function label(): string
    {
        return match ($this) {
            self::UNPAID => 'Belum Lunas',
            self::PARTIALLY_PAID => 'Bayar Sebagian (DP)',
            self::PAID => 'Lunas',
            self::OVERPAID => 'Lebih Bayar',
            self::REFUNDED => 'Dikembalikan (Refund)',
            self::PARTIALLY_REFUNDED => 'Refund Sebagian',
        };
    }

    /**
     * Check if payment is considered settled.
     */
    public function isSettled(): bool
    {
        return in_array($this, [self::PAID, self::OVERPAID, self::REFUNDED], true);
    }

    /**
     * Check if order is fully paid.
     */
    public function isPaid(): bool
    {
        return in_array($this, [self::PAID, self::OVERPAID], true);
    }

    /**
     * Get UI color badge token.
     */
    public function color(): string
    {
        return match ($this) {
            self::UNPAID => 'rose',
            self::PARTIALLY_PAID => 'amber',
            self::PAID => 'emerald',
            self::OVERPAID => 'blue',
            self::REFUNDED, self::PARTIALLY_REFUNDED => 'slate',
        };
    }
}
