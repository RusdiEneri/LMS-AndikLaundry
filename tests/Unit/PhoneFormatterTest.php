<?php

use App\Support\PhoneFormatter;

/*
|--------------------------------------------------------------------------
| Feature F5: Indonesian Phone Number Normalization Unit Tests
|--------------------------------------------------------------------------
| Specification Source: docs/security.md §9.2, docs/test-plan.md §2.3,
|                       docs/whatsapp-integration.md §3.3
| Canonical Format: E.164 without leading plus (e.g. 6281234567890)
*/

describe('Feature F5: Indonesian Phone Normalization - Tier 1 Happy Paths', function () {

    it('T1-PHONE-01: Formats standard 08... domestic format to canonical 628...', function () {
        expect(PhoneFormatter::normalizeToE164('081234567890'))->toBe('6281234567890');
        expect(PhoneFormatter::normalizeToE164('085711223344'))->toBe('6285711223344');
    });

    it('T1-PHONE-02: Formats plus prefix +62... to canonical 62...', function () {
        expect(PhoneFormatter::normalizeToE164('+6281234567890'))->toBe('6281234567890');
        expect(PhoneFormatter::normalizeToE164('+628991234567'))->toBe('628991234567');
    });

    it('T1-PHONE-03: Formats canonical input 62... without alteration', function () {
        expect(PhoneFormatter::normalizeToE164('6281234567890'))->toBe('6281234567890');
        expect(PhoneFormatter::normalizeToE164('6285200001111'))->toBe('6285200001111');
    });

    it('T1-PHONE-04: Formats hyphenated input 0812-3456-7890 to canonical 6281234567890', function () {
        expect(PhoneFormatter::normalizeToE164('0812-3456-7890'))->toBe('6281234567890');
        expect(PhoneFormatter::normalizeToE164('0856-789-0123-4'))->toBe('6285678901234');
    });

    it('T1-PHONE-05: Formats spaced input +62 812 3456 7890 to canonical 6281234567890', function () {
        expect(PhoneFormatter::normalizeToE164('+62 812 3456 7890'))->toBe('6281234567890');
        expect(PhoneFormatter::normalizeToE164('0812 3456 7890'))->toBe('6281234567890');
    });
});

describe('Feature F5: Indonesian Phone Normalization - Tier 2 Boundaries & Edge Cases', function () {

    it('T2-PHONE-01: Formats phone with leading 8 (missing 0 or 62) to canonical 628...', function () {
        expect(PhoneFormatter::normalizeToE164('8123456789'))->toBe('628123456789');
        expect(PhoneFormatter::normalizeToE164('85712345678'))->toBe('6285712345678');
    });

    it('T2-PHONE-02: Formats phone with leading, trailing, and multiple internal spaces cleanly', function () {
        expect(PhoneFormatter::normalizeToE164('   0857   1122   3344   '))->toBe('6285711223344');
        expect(PhoneFormatter::normalizeToE164(" \t 0812 3456 7890 \n "))->toBe('6281234567890');
    });

    it('T2-PHONE-03: Handles short phone numbers (< 9 digits) via validation or null', function () {
        // When using validation helper
        if (method_exists(PhoneFormatter::class, 'isValid')) {
            expect(PhoneFormatter::isValid('12345'))->toBeFalse()
                ->and(PhoneFormatter::isValid('0812'))->toBeFalse();
        }

        // When normalizing, either throws InvalidArgumentException or strips digits
        try {
            $normalized = PhoneFormatter::normalizeToE164('12345');
            // If it returns raw normalized string, assert length boundary
            expect(strlen($normalized))->toBeLessThan(9);
        } catch (InvalidArgumentException $e) {
            expect($e)->toBeInstanceOf(InvalidArgumentException::class);
        }
    });

    it('T2-PHONE-04: Strips non-numeric characters and symbols cleanly', function () {
        expect(PhoneFormatter::normalizeToE164('0812.3456.7890'))->toBe('6281234567890');
        expect(PhoneFormatter::normalizeToE164('(0812) 3456-7890'))->toBe('6281234567890');
        expect(PhoneFormatter::normalizeToE164('+62 (0812) 345-6789'))->toBe('628123456789');
    });

    it('T2-PHONE-05: Handles Indonesian regional landline formatting (021)', function () {
        expect(PhoneFormatter::normalizeToE164('021-7654321'))->toBe('62217654321');
        expect(PhoneFormatter::normalizeToE164('+62 21 8839201'))->toBe('62218839201');
    });

    it('T2-PHONE-06: Verifies idempotency - normalizing an already normalized number produces identical result', function () {
        $inputs = [
            '081234567890',
            '+6281234567890',
            '0857-1122-3344',
            '8123456789',
        ];

        foreach ($inputs as $input) {
            $firstPass = PhoneFormatter::normalizeToE164($input);
            $secondPass = PhoneFormatter::normalizeToE164($firstPass);
            expect($secondPass)->toBe($firstPass);
        }
    });

    test('T2-PHONE-07: Dataset test across major Indonesian cellular telco prefixes', function ($raw, $expected) {
        expect(PhoneFormatter::normalizeToE164($raw))->toBe($expected);
    })->with([
        ['0811-1234-5678', '6281112345678'], // Telkomsel Halo
        ['0812-3456-7890', '6281234567890'], // Telkomsel SimPATI
        ['0815-9876-5432', '6281598765432'], // Indosat Matrix
        ['0857-1234-5678', '6285712345678'], // Indosat IM3
        ['0817-0000-1111', '6281700001111'], // XL Axiata
        ['0878-8888-9999', '6287888889999'], // XL Prioritas
        ['0896-1234-5678', '6289612345678'], // Tri (3)
        ['0881-2222-3333', '6288122223333'], // Smartfren
    ]);
});
