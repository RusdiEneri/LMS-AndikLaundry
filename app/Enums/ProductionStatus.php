<?php

namespace App\Enums;

enum ProductionStatus: string
{
    // Simplified 3-stage flow (Final spec)
    case DIPROSES = 'diproses';
    case SELESAI = 'selesai';
    case SUDAH_DIAMBIL = 'sudah_diambil';

    // Canonical / granular states
    case RECEIVED = 'received';
    case WAITING = 'waiting';
    case SORTING = 'sorting';
    case WASHING = 'washing';
    case DRYING = 'drying';
    case IRONING = 'ironing';
    case QC = 'qc';
    case PACKING = 'packing';
    case READY_FOR_PICKUP = 'ready_for_pickup';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';

    public static function coerce(string|self $value): self
    {
        if ($value instanceof self) {
            return $value;
        }

        return match (strtolower($value)) {
            'diproses' => self::DIPROSES,
            'selesai' => self::SELESAI,
            'sudah_diambil', 'diambil' => self::SUDAH_DIAMBIL,
            default => self::tryFrom($value) ?? self::DIPROSES,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::DIPROSES => 'Diproses',
            self::SELESAI => 'Selesai',
            self::SUDAH_DIAMBIL => 'Sudah Diambil',
            self::RECEIVED => 'Diterima',
            self::WAITING => 'Menunggu',
            self::SORTING => 'Pemilahan',
            self::WASHING => 'Pencucian',
            self::DRYING => 'Pengeringan',
            self::IRONING => 'Penyetrikaan',
            self::QC => 'Pemeriksaan (QC)',
            self::PACKING => 'Pengemasan',
            self::READY_FOR_PICKUP => 'Siap Ambil',
            self::COMPLETED => 'Selesai Diambil',
            self::CANCELLED => 'Dibatalkan',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::DIPROSES, self::WASHING, self::DRYING, self::IRONING, self::QC, self::PACKING => 'blue',
            self::SELESAI, self::READY_FOR_PICKUP => 'emerald',
            self::SUDAH_DIAMBIL, self::COMPLETED => 'gray',
            self::CANCELLED => 'rose',
            default => 'slate',
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::SUDAH_DIAMBIL, self::COMPLETED, self::CANCELLED], true);
    }

    public function canTransitionTo(ProductionStatus $target): bool
    {
        if ($this === $target) {
            return true;
        }

        if ($this->isTerminal()) {
            return false;
        }

        // Cancellation is allowed from any non-terminal state
        if ($target === self::CANCELLED) {
            return true;
        }

        // 3-Stage simplified transitions
        if ($this === self::DIPROSES) {
            return in_array($target, [self::SELESAI, self::SUDAH_DIAMBIL, self::READY_FOR_PICKUP, self::COMPLETED], true);
        }
        if ($this === self::SELESAI) {
            return in_array($target, [self::SUDAH_DIAMBIL, self::COMPLETED, self::DIPROSES], true);
        }

        // Granular workflow transitions
        return match ($this) {
            self::RECEIVED => in_array($target, [self::WAITING, self::WASHING, self::DIPROSES, self::SELESAI, self::SUDAH_DIAMBIL], true),
            self::WAITING => in_array($target, [self::SORTING, self::WASHING, self::DIPROSES, self::SELESAI], true),
            self::SORTING => in_array($target, [self::WASHING, self::DIPROSES], true),
            self::WASHING => in_array($target, [self::DRYING, self::DIPROSES], true),
            self::DRYING => in_array($target, [self::IRONING, self::QC, self::DIPROSES], true),
            self::IRONING => in_array($target, [self::QC, self::PACKING, self::WASHING, self::DIPROSES], true),
            self::QC => in_array($target, [self::READY_FOR_PICKUP, self::WASHING, self::PACKING, self::SELESAI], true),
            self::PACKING => in_array($target, [self::READY_FOR_PICKUP, self::SELESAI], true),
            self::READY_FOR_PICKUP => in_array($target, [self::COMPLETED, self::SUDAH_DIAMBIL], true),
            default => false,
        };
    }
}
