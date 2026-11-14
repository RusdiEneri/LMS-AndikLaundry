<?php

namespace App\Services;

use DateTimeInterface;
use Illuminate\Support\Facades\DB;

class InvoiceNumberGenerator
{
    /**
     * Generate a unique, concurrency-safe, sequential daily invoice number.
     * Format: INV-YYYYMMDD-XXXX (e.g. INV-20261114-0001).
     */
    public function generate(?DateTimeInterface $date = null): string
    {
        $targetDate = $date ?? now();
        $prefix = 'INV-' . $targetDate->format('Ymd') . '-';

        // Query the latest invoice number for the date prefix
        $latest = DB::table('transactions')
            ->where('invoice_number', 'like', $prefix . '%')
            ->lockForUpdate()
            ->orderByDesc('invoice_number')
            ->value('invoice_number');

        $nextSequence = 1;
        if ($latest && preg_match('/' . preg_quote($prefix, '/') . '(\d+)$/', $latest, $matches)) {
            $nextSequence = ((int) $matches[1]) + 1;
        }

        $candidate = $prefix . str_pad((string) $nextSequence, 4, '0', STR_PAD_LEFT);

        // Safeguard to guarantee absolute uniqueness even under high concurrency
        while (DB::table('transactions')->where('invoice_number', $candidate)->exists()) {
            $nextSequence++;
            $candidate = $prefix . str_pad((string) $nextSequence, 4, '0', STR_PAD_LEFT);
        }

        return $candidate;
    }
}
