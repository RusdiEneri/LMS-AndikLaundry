<?php

use App\Models\AuditLog;
use App\Models\BusinessSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Feature F6: Business Settings Management (Tier 1 & Tier 2)
|--------------------------------------------------------------------------
| Specification Source: ORIGINAL_REQUEST §R4, PROJECT.md §22-23, §81-85,
|                       docs/database-schema.md Table 14, docs/security.md §3
*/

beforeEach(function () {
    // Seed standard initial settings for isolated feature testing
    BusinessSetting::firstOrCreate(
        ['key' => 'store.name'],
        ['value' => 'Andik Laundry', 'type' => 'string', 'group' => 'store']
    );
    BusinessSetting::firstOrCreate(
        ['key' => 'store.phone'],
        ['value' => '6281234567890', 'type' => 'string', 'group' => 'store']
    );
    BusinessSetting::firstOrCreate(
        ['key' => 'store.address'],
        ['value' => 'Jl. Raya Laundry No. 12, Jakarta', 'type' => 'string', 'group' => 'store']
    );
    BusinessSetting::firstOrCreate(
        ['key' => 'store.receipt_footer'],
        ['value' => 'Terima kasih telah mencuci di Andik Laundry.', 'type' => 'string', 'group' => 'store']
    );

    BusinessSetting::firstOrCreate(
        ['key' => 'operational.default_min_weight'],
        ['value' => '3.00', 'type' => 'decimal', 'group' => 'operational']
    );
    BusinessSetting::firstOrCreate(
        ['key' => 'operational.customer_credit_ceiling'],
        ['value' => '150000.00', 'type' => 'decimal', 'group' => 'operational']
    );
    BusinessSetting::firstOrCreate(
        ['key' => 'operational.default_debt_due_days'],
        ['value' => '7', 'type' => 'integer', 'group' => 'operational']
    );
    BusinessSetting::firstOrCreate(
        ['key' => 'operational.cashier_petty_cash_limit'],
        ['value' => '100000.00', 'type' => 'decimal', 'group' => 'operational']
    );
    BusinessSetting::firstOrCreate(
        ['key' => 'operational.max_rewash_limit'],
        ['value' => '2', 'type' => 'integer', 'group' => 'operational']
    );

    BusinessSetting::firstOrCreate(
        ['key' => 'financial.tax_rate_percent'],
        ['value' => '0.00', 'type' => 'decimal', 'group' => 'financial']
    );
    BusinessSetting::firstOrCreate(
        ['key' => 'financial.currency_code'],
        ['value' => 'IDR', 'type' => 'string', 'group' => 'financial']
    );

    BusinessSetting::firstOrCreate(
        ['key' => 'printer.paper_width'],
        ['value' => '58mm', 'type' => 'string', 'group' => 'printer']
    );
    BusinessSetting::firstOrCreate(
        ['key' => 'printer.auto_print'],
        ['value' => '0', 'type' => 'boolean', 'group' => 'printer']
    );

    BusinessSetting::firstOrCreate(
        ['key' => 'whatsapp.provider'],
        ['value' => 'wuzapi', 'type' => 'string', 'group' => 'whatsapp']
    );
    BusinessSetting::firstOrCreate(
        ['key' => 'whatsapp.anti_ban_rate_limit'],
        ['value' => '12', 'type' => 'integer', 'group' => 'whatsapp']
    );
});

describe('Feature F6: Business Settings - Tier 1 Happy Paths', function () {

    it('T1-SET-01: Owner retrieves settings dictionary grouped by domain', function () {
        $owner = User::factory()->create(['role' => 'owner', 'is_active' => true]);

        $response = $this->actingAs($owner)->get('/owner/settings');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Owner/Settings/Index')
            ->has('settings')
            ->has('settings.store')
            ->has('settings.operational')
            ->has('settings.financial')
            ->has('settings.printer')
            ->has('settings.whatsapp')
        );
    });

    it('T1-SET-02: Owner bulk updates multiple settings in a single request', function () {
        $owner = User::factory()->create(['role' => 'owner', 'is_active' => true]);

        $payload = [
            'settings' => [
                ['key' => 'store.name', 'value' => 'Andik Laundry Syariah'],
                ['key' => 'operational.default_min_weight', 'value' => '3.50'],
                ['key' => 'printer.auto_print', 'value' => true],
            ],
        ];

        $response = $this->actingAs($owner)->put('/owner/settings', $payload);

        expect($response->status())->toBeIn([200, 302]);
        
        $this->assertDatabaseHas('business_settings', [
            'key' => 'store.name',
            'value' => 'Andik Laundry Syariah',
        ]);
        $this->assertDatabaseHas('business_settings', [
            'key' => 'operational.default_min_weight',
            'value' => '3.50',
        ]);

        // Verify audit log captured the update
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $owner->id,
            'action' => 'update',
            'auditable_type' => BusinessSetting::class,
        ]);
    });

    it('T1-SET-03: Setting types (decimal, integer, boolean, string, json) cast correctly on retrieval', function () {
        BusinessSetting::updateOrCreate(
            ['key' => 'store.operating_hours'],
            ['value' => json_encode(['open' => '08:00', 'close' => '21:00']), 'type' => 'json', 'group' => 'store']
        );

        expect(BusinessSetting::get('operational.default_min_weight'))->toBeIn(['3.00', 3.00, 3.0])
            ->and(BusinessSetting::get('operational.default_debt_due_days'))->toBe(7)
            ->and(BusinessSetting::get('printer.auto_print'))->toBeFalse()
            ->and(BusinessSetting::get('store.name'))->toBe('Andik Laundry')
            ->and(BusinessSetting::get('store.operating_hours'))->toBeArray()
            ->and(BusinessSetting::get('store.operating_hours')['open'])->toBe('08:00');
    });

    it('T1-SET-04: BusinessSetting::get returns stored value when key exists', function () {
        $name = BusinessSetting::get('store.name', 'Fallback Name');
        $minWeight = BusinessSetting::get('operational.default_min_weight', '1.00');

        expect($name)->toBe('Andik Laundry')
            ->and($minWeight)->toBeIn(['3.00', 3.00, 3.0]);
    });

    it('T1-SET-05: BusinessSetting::get returns default fallback when key is absent', function () {
        $missingWithDefault = BusinessSetting::get('non.existent.key', 'Default Value');
        $missingWithoutDefault = BusinessSetting::get('another.missing.key');

        expect($missingWithDefault)->toBe('Default Value')
            ->and($missingWithoutDefault)->toBeNull();
    });
});

describe('Feature F6: Business Settings - Tier 2 Boundary Cases', function () {

    it('T2-SET-01: Updating unknown setting key handles gracefully or creates if permissible', function () {
        $owner = User::factory()->create(['role' => 'owner', 'is_active' => true]);

        $payload = [
            'settings' => [
                ['key' => 'custom.notice_board', 'value' => 'Promo Ramadhan Diskon 20%'],
            ],
        ];

        $response = $this->actingAs($owner)->put('/owner/settings', $payload);

        // Should either succeed by creating setting or fail 422 if strict whitelist
        if (in_array($response->status(), [200, 302])) {
            $this->assertDatabaseHas('business_settings', [
                'key' => 'custom.notice_board',
                'value' => 'Promo Ramadhan Diskon 20%',
            ]);
        } else {
            $response->assertStatus(422);
        }
    });

    it('T2-SET-02: Negative value for min_weight threshold is rejected by validation', function () {
        $owner = User::factory()->create(['role' => 'owner', 'is_active' => true]);

        $payload = [
            'settings' => [
                ['key' => 'operational.default_min_weight', 'value' => -1.50],
            ],
        ];

        $response = $this->actingAs($owner)->put('/owner/settings', $payload);

        if ($response->status() === 302) {
            $response->assertSessionHasErrors();
        } else {
            $response->assertStatus(422);
        }
        
        // Assert original value remained intact
        expect(BusinessSetting::get('operational.default_min_weight'))->toBeIn(['3.00', 3.00, 3.0]);
    });

    it('T2-SET-03: Bulk update payload with corrupted array structure returns 422', function () {
        $owner = User::factory()->create(['role' => 'owner', 'is_active' => true]);

        // Malformed: settings is a string instead of array
        $response = $this->actingAs($owner)->putJson('/owner/settings', [
            'settings' => 'corrupted_string',
        ]);

        $response->assertStatus(422);

        // Malformed: missing key attribute in array item
        $responseItem = $this->actingAs($owner)->putJson('/owner/settings', [
            'settings' => [
                ['value' => 'No Key Specified'],
            ],
        ]);

        $responseItem->assertStatus(422);
    });

    it('T2-SET-04: Updating settings as Cashier or Operator is strictly blocked with 403', function () {
        $cashier = User::factory()->create(['role' => 'cashier', 'is_active' => true]);
        $operator = User::factory()->create(['role' => 'operator', 'is_active' => true]);

        $payload = [
            'settings' => [
                ['key' => 'operational.default_min_weight', 'value' => '1.00'],
            ],
        ];

        $responseCashier = $this->actingAs($cashier)->put('/owner/settings', $payload);
        $responseCashier->assertForbidden();

        $responseOperator = $this->actingAs($operator)->put('/owner/settings', $payload);
        $responseOperator->assertForbidden();

        // Database value untouched
        expect(BusinessSetting::get('operational.default_min_weight'))->toBeIn(['3.00', 3.00, 3.0]);
    });

    it('T2-SET-05: Very long string boundary value in setting persists without truncation', function () {
        $owner = User::factory()->create(['role' => 'owner', 'is_active' => true]);

        $longText = str_repeat('Syarat dan ketentuan garansi cuci bersih Andik Laundry: ', 20); // ~1100 chars

        $payload = [
            'settings' => [
                ['key' => 'store.receipt_footer', 'value' => $longText],
            ],
        ];

        $response = $this->actingAs($owner)->put('/owner/settings', $payload);
        expect($response->status())->toBeIn([200, 302]);

        $saved = BusinessSetting::get('store.receipt_footer');
        expect($saved)->toBe($longText)
            ->and(strlen($saved))->toBeGreaterThan(1000);
    });
});
