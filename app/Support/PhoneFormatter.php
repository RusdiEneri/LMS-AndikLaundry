<?php

namespace App\Support;

class PhoneFormatter
{
    /**
     * Normalize an Indonesian phone number to canonical E.164 without leading plus.
     */
    public static function normalize(string $phone): string
    {
        return self::normalizeToE164($phone);
    }

    /**
     * Formats phone input to canonical E.164 (e.g. 6281234567890, 62217654321).
     */
    public static function normalizeToE164(string $phone): string
    {
        $digits = preg_replace('/[^\d]/', '', $phone);

        if (str_starts_with($digits, '620')) {
            $digits = '62'.substr($digits, 3);
        } elseif (str_starts_with($digits, '0')) {
            $digits = '62'.substr($digits, 1);
        } elseif (str_starts_with($digits, '8')) {
            $digits = '62'.$digits;
        }

        return $digits;
    }

    /**
     * Check if the phone number satisfies canonical Indonesian cellular or landline format.
     */
    public static function isValid(string $phone): bool
    {
        $normalized = self::normalizeToE164($phone);

        return (bool) preg_match('/^62[2-9]\d{7,13}$/', $normalized);
    }
}
