<?php

namespace App\Enums;

enum ProductionStatus: string
{
    case RECEIVED = 'received';
    case WAITING = 'waiting';
    case WASHING = 'washing';
    case DRYING = 'drying';
    case IRONING = 'ironing';
    case QC = 'qc';
    case READY_FOR_PICKUP = 'ready_for_pickup';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';

    /**
     * Get human-readable localized Indonesian label.
     */
    public function label(): string
    {
        return match ($this) {
            self::RECEIVED => 'Diterima',
            self::WAITING => 'Menunggu Antrean',
            self::WASHING => 'Proses Cuci',
            self::DRYING => 'Proses Pengeringan',
            self::IRONING => 'Proses Setrika',
            self::QC => 'Quality Control',
            self::READY_FOR_PICKUP => 'Siap Diambil',
            self::COMPLETED => 'Selesai / Diambil',
            self::CANCELLED => 'Dibatalkan',
        };
    }

    /**
     * Determine if status is terminal (cannot transition further).
     */
    public function isTerminal(): bool
    {
        return in_array($this, [self::COMPLETED, self::CANCELLED], true);
    }

    /**
     * Validate whether transitioning to target status is permitted.
     */
    public function canTransitionTo(ProductionStatus $target): bool
    {
        if ($this === $target) {
            return false;
        }

        if ($this->isTerminal()) {
            return false;
        }

        // Any non-terminal state can be cancelled
        if ($target === self::CANCELLED) {
            return true;
        }

        return match ($this) {
            self::RECEIVED => in_array($target, [self::WAITING, self::WASHING], true),
            self::WAITING => $target === self::WASHING,
            self::WASHING => $target === self::DRYING,
            self::DRYING => in_array($target, [self::IRONING, self::QC], true),
            self::IRONING => in_array($target, [self::QC, self::READY_FOR_PICKUP, self::WASHING], true),
            // QC allows proceeding to ready_for_pickup or rewash loop back to washing
            self::QC => in_array($target, [self::READY_FOR_PICKUP, self::WASHING], true),
            self::READY_FOR_PICKUP => $target === self::COMPLETED,
            default => false,
        };
    }

    /**
     * Get UI color badge design token.
     */
    public function color(): string
    {
        return match ($this) {
            self::RECEIVED => 'gray',
            self::WAITING => 'amber',
            self::WASHING => 'blue',
            self::DRYING => 'cyan',
            self::IRONING => 'indigo',
            self::QC => 'purple',
            self::READY_FOR_PICKUP => 'emerald',
            self::COMPLETED => 'green',
            self::CANCELLED => 'rose',
        };
    }
}
