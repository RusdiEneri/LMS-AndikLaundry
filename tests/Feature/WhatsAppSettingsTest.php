<?php

use App\Models\BusinessSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| WhatsApp Gateway Settings Feature Tests
|--------------------------------------------------------------------------
| Requirements: R1 (Interface), R2 (Persistence), R3 (Connection & Message Testing)
*/

beforeEach(function () {
    // Seed essential store settings
    BusinessSetting::firstOrCreate(
        ['key' => 'store.name'],
        ['value' => 'Andik Laundry', 'type' => 'string', 'group' => 'store']
    );
    BusinessSetting::firstOrCreate(
        ['key' => 'store.phone'],
        ['value' => '6281234567890', 'type' => 'string', 'group' => 'store']
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

describe('WhatsApp Settings Interface (R1)', function () {

    it('allows owner to view WhatsApp gateway settings in /owner/settings with default fallbacks', function () {
        $owner = User::factory()->create(['role' => 'owner', 'is_active' => true]);

        $response = $this->actingAs($owner)->get('/owner/settings');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Owner/Settings/Index')
            ->has('settings.whatsapp')
            ->where('settings.whatsapp', function ($wa) {
                expect($wa)->toHaveKey('whatsapp.mode')
                    ->and($wa)->toHaveKey('whatsapp.server_url')
                    ->and($wa)->toHaveKey('whatsapp.api_key')
                    ->and($wa)->toHaveKey('whatsapp.session_id')
                    ->and($wa)->toHaveKey('whatsapp.template_intake')
                    ->and($wa)->toHaveKey('whatsapp.template_ready')
                    ->and($wa)->toHaveKey('whatsapp.template_reminder')
                    ->and($wa['whatsapp.mode'])->toBe('automatic');

                return true;
            })
        );
    });

    it('blocks cashier and operator from viewing settings interface', function () {
        $cashier = User::factory()->create(['role' => 'cashier', 'is_active' => true]);
        $operator = User::factory()->create(['role' => 'operator', 'is_active' => true]);

        $this->actingAs($cashier)->get('/owner/settings')->assertForbidden();
        $this->actingAs($operator)->get('/owner/settings')->assertForbidden();
    });
});

describe('WhatsApp Settings Persistence (R2)', function () {

    it('persists WhatsApp gateway configuration and custom templates in business_settings', function () {
        $owner = User::factory()->create(['role' => 'owner', 'is_active' => true]);

        $payload = [
            'settings' => [
                ['key' => 'whatsapp.mode', 'value' => 'manual'],
                ['key' => 'whatsapp.provider', 'value' => 'manual_link'],
                ['key' => 'whatsapp.server_url', 'value' => 'http://wuzapi-host:8080'],
                ['key' => 'whatsapp.api_key', 'value' => 'my-secret-wuzapi-token-2026'],
                ['key' => 'whatsapp.session_id', 'value' => '6289876543210'],
                ['key' => 'whatsapp.anti_ban_rate_limit', 'value' => 15],
                ['key' => 'whatsapp.template_intake', 'value' => 'Nota: {{invoice_number}}, Total: {{total_amount}}'],
                ['key' => 'whatsapp.template_ready', 'value' => 'Cucian {{invoice_number}} siap di {{rack_location}}'],
                ['key' => 'whatsapp.template_reminder', 'value' => 'Tagihan {{invoice_number}} sisa {{debt_amount}}'],
            ],
        ];

        $response = $this->actingAs($owner)->put('/owner/settings', $payload);

        expect($response->status())->toBeIn([200, 302]);

        $this->assertDatabaseHas('business_settings', [
            'key' => 'whatsapp.mode',
            'value' => 'manual',
            'group' => 'whatsapp',
        ]);
        $this->assertDatabaseHas('business_settings', [
            'key' => 'whatsapp.server_url',
            'value' => 'http://wuzapi-host:8080',
            'group' => 'whatsapp',
        ]);
        $this->assertDatabaseHas('business_settings', [
            'key' => 'whatsapp.api_key',
            'value' => 'my-secret-wuzapi-token-2026',
            'group' => 'whatsapp',
        ]);
        $this->assertDatabaseHas('business_settings', [
            'key' => 'whatsapp.session_id',
            'value' => '6289876543210',
            'group' => 'whatsapp',
        ]);
        $this->assertDatabaseHas('business_settings', [
            'key' => 'whatsapp.template_intake',
            'value' => 'Nota: {{invoice_number}}, Total: {{total_amount}}',
            'group' => 'whatsapp',
        ]);
        $this->assertDatabaseHas('business_settings', [
            'key' => 'whatsapp.template_ready',
            'value' => 'Cucian {{invoice_number}} siap di {{rack_location}}',
            'group' => 'whatsapp',
        ]);
        $this->assertDatabaseHas('business_settings', [
            'key' => 'whatsapp.template_reminder',
            'value' => 'Tagihan {{invoice_number}} sisa {{debt_amount}}',
            'group' => 'whatsapp',
        ]);

        // Verifies audit trail
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $owner->id,
            'action' => 'update',
            'auditable_type' => BusinessSetting::class,
        ]);
    });

    it('rejects invalid gateway mode values', function () {
        $owner = User::factory()->create(['role' => 'owner', 'is_active' => true]);

        $payload = [
            'settings' => [
                ['key' => 'whatsapp.mode', 'value' => 'unsupported_mode'],
            ],
        ];

        $response = $this->actingAs($owner)->put('/owner/settings', $payload);

        if ($response->status() === 302) {
            $response->assertSessionHasErrors();
        } else {
            $response->assertStatus(422);
        }
    });

    it('rejects anti_ban_rate_limit below 1', function () {
        $owner = User::factory()->create(['role' => 'owner', 'is_active' => true]);

        $payload = [
            'settings' => [
                ['key' => 'whatsapp.anti_ban_rate_limit', 'value' => 0],
            ],
        ];

        $response = $this->actingAs($owner)->put('/owner/settings', $payload);

        if ($response->status() === 302) {
            $response->assertSessionHasErrors();
        } else {
            $response->assertStatus(422);
        }
    });
});

describe('Gateway Connection Testing (R3)', function () {

    it('returns success for manual wa.me mode without invoking external server', function () {
        $owner = User::factory()->create(['role' => 'owner', 'is_active' => true]);

        Http::fake();

        $response = $this->actingAs($owner)->postJson('/owner/settings/whatsapp/test-connection', [
            'mode' => 'manual',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('mode', 'manual');

        Http::assertNothingSent();
    });

    it('tests automatic gateway connectivity and returns success on healthy 200 response', function () {
        $owner = User::factory()->create(['role' => 'owner', 'is_active' => true]);

        Http::fake([
            'http://localhost:8080/session/status' => Http::response([
                'code' => 200,
                'success' => true,
                'data' => [
                    'connected' => true,
                    'loggedIn' => true,
                ],
            ], 200),
        ]);

        $response = $this->actingAs($owner)->postJson('/owner/settings/whatsapp/test-connection', [
            'mode' => 'automatic',
            'server_url' => 'http://localhost:8080',
            'api_key' => 'test-token',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('mode', 'automatic')
            ->assertJsonPath('data.connected', true);

        Http::assertSent(fn ($req) => $req->url() === 'http://localhost:8080/session/status' && $req->hasHeader('Token', 'test-token'));
    });

    it('handles gateway server error and returns 422 with diagnostic message', function () {
        $owner = User::factory()->create(['role' => 'owner', 'is_active' => true]);

        Http::fake([
            'http://localhost:8080/session/status' => Http::response(['error' => 'Unauthorized'], 401),
        ]);

        $response = $this->actingAs($owner)->postJson('/owner/settings/whatsapp/test-connection', [
            'mode' => 'automatic',
            'server_url' => 'http://localhost:8080',
            'api_key' => 'invalid-token',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('mode', 'automatic');
    });

    it('detects gateway returning 200 with disconnected or unpaired session as failure', function () {
        $owner = User::factory()->create(['role' => 'owner', 'is_active' => true]);

        Http::fake([
            'http://localhost:8080/session/status' => Http::response([
                'code' => 200,
                'success' => true,
                'data' => [
                    'connected' => false,
                    'loggedIn' => false,
                ],
            ], 200),
        ]);

        $response = $this->actingAs($owner)->postJson('/owner/settings/whatsapp/test-connection', [
            'mode' => 'automatic',
            'server_url' => 'http://localhost:8080',
            'api_key' => 'valid-token',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('data.connected', false);

        expect($response->json('message'))->toContain('belum aktif');
    });

    it('detects gateway returning 200 with success=false in JSON as failure', function () {
        $owner = User::factory()->create(['role' => 'owner', 'is_active' => true]);

        Http::fake([
            'http://localhost:8080/session/status' => Http::response([
                'code' => 200,
                'success' => false,
                'error' => 'Device unlinked from WhatsApp mobile client',
            ], 200),
        ]);

        $response = $this->actingAs($owner)->postJson('/owner/settings/whatsapp/test-connection', [
            'mode' => 'automatic',
            'server_url' => 'http://localhost:8080',
            'api_key' => 'valid-token',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);

        expect($response->json('message'))->toContain('Device unlinked');
    });

    it('detects gateway returning non-JSON response as failure', function () {
        $owner = User::factory()->create(['role' => 'owner', 'is_active' => true]);

        Http::fake([
            'http://localhost:8080/session/status' => Http::response('<html>Welcome to Nginx</html>', 200, ['Content-Type' => 'text/html']),
        ]);

        $response = $this->actingAs($owner)->postJson('/owner/settings/whatsapp/test-connection', [
            'mode' => 'automatic',
            'server_url' => 'http://localhost:8080',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);

        expect($response->json('message'))->toContain('bukan format JSON');
    });

    it('rejects invalid mode when testing connection', function () {
        $owner = User::factory()->create(['role' => 'owner', 'is_active' => true]);

        $response = $this->actingAs($owner)->postJson('/owner/settings/whatsapp/test-connection', [
            'mode' => 'invalid_mode',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['mode']);
    });

    it('blocks non-owner from testing WhatsApp connection', function () {
        $cashier = User::factory()->create(['role' => 'cashier', 'is_active' => true]);

        $response = $this->actingAs($cashier)->postJson('/owner/settings/whatsapp/test-connection', [
            'mode' => 'manual',
        ]);

        $response->assertForbidden();
    });
});

describe('WhatsApp Test Message Delivery (R3)', function () {

    it('generates manual wa.me url when in manual mode', function () {
        $owner = User::factory()->create(['role' => 'owner', 'is_active' => true]);

        $response = $this->actingAs($owner)->postJson('/owner/settings/whatsapp/test-message', [
            'phone' => '081234567890',
            'mode' => 'manual',
            'message' => 'Halo pesan uji coba manual',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('mode', 'manual')
            ->assertJsonPath('phone', '6281234567890');

        expect($response->json('wa_url'))->toContain('https://wa.me/6281234567890');
    });

    it('dispatches test message to gateway in automatic mode', function () {
        $owner = User::factory()->create(['role' => 'owner', 'is_active' => true]);

        Http::fake([
            'http://localhost:8080/chat/send/text' => Http::response([
                'code' => 200,
                'success' => true,
                'data' => [
                    'id' => 'MSG-TEST-001',
                    'details' => 'Message sent',
                ],
            ], 200),
        ]);

        $response = $this->actingAs($owner)->postJson('/owner/settings/whatsapp/test-message', [
            'phone' => '081234567890',
            'mode' => 'automatic',
            'server_url' => 'http://localhost:8080',
            'api_key' => 'my-token',
            'message' => 'Pesan uji coba otomatis',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('mode', 'automatic');

        Http::assertSent(function ($request) {
            return $request->url() === 'http://localhost:8080/chat/send/text'
                && $request['Phone'] === '6281234567890'
                && $request['Body'] === 'Pesan uji coba otomatis'
                && $request->hasHeader('Token', 'my-token');
        });
    });

    it('supports template_type preview generation for test message', function () {
        $owner = User::factory()->create(['role' => 'owner', 'is_active' => true]);

        Http::fake([
            'http://localhost:8080/chat/send/text' => Http::response(['success' => true], 200),
        ]);

        $response = $this->actingAs($owner)->postJson('/owner/settings/whatsapp/test-message', [
            'phone' => '081234567890',
            'mode' => 'automatic',
            'template_type' => 'intake',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true);

        Http::assertSent(fn ($req) => str_contains($req['Body'], 'cucian Anda telah kami terima'));
    });

    it('interpolates configured custom template when sending template test message', function () {
        $owner = User::factory()->create(['role' => 'owner', 'is_active' => true]);

        BusinessSetting::set(
            'whatsapp.template_intake',
            'Kustom: Halo {{customer_name}}, nota {{invoice_number}} total {{total_amount}}',
            'string',
            'whatsapp'
        );

        Http::fake([
            'http://localhost:8080/chat/send/text' => Http::response(['success' => true], 200),
        ]);

        $response = $this->actingAs($owner)->postJson('/owner/settings/whatsapp/test-message', [
            'phone' => '081234567890',
            'mode' => 'automatic',
            'template_type' => 'intake',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true);

        Http::assertSent(function ($req) {
            return str_contains($req['Body'], 'Kustom: Halo Budi Santoso')
                && str_contains($req['Body'], 'INV-TEST-001')
                && str_contains($req['Body'], 'Rp 50.000');
        });
    });

    it('rejects invalid recipient phone number with 422', function () {
        $owner = User::factory()->create(['role' => 'owner', 'is_active' => true]);

        $response = $this->actingAs($owner)->postJson('/owner/settings/whatsapp/test-message', [
            'phone' => 'invalid-abc',
            'mode' => 'manual',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);

        expect($response->json('message'))->toContain('tidak valid');
    });

    it('detects gateway returning 200 with success=false during test message sending as failure', function () {
        $owner = User::factory()->create(['role' => 'owner', 'is_active' => true]);

        Http::fake([
            'http://localhost:8080/chat/send/text' => Http::response([
                'code' => 200,
                'success' => false,
                'error' => 'WhatsApp socket closed or phone disconnected',
            ], 200),
        ]);

        $response = $this->actingAs($owner)->postJson('/owner/settings/whatsapp/test-message', [
            'phone' => '081234567890',
            'mode' => 'automatic',
            'server_url' => 'http://localhost:8080',
            'message' => 'Pesan uji coba',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);

        expect($response->json('message'))->toContain('WhatsApp socket closed');
    });

    it('detects gateway returning non-JSON response during test message sending as failure', function () {
        $owner = User::factory()->create(['role' => 'owner', 'is_active' => true]);

        Http::fake([
            'http://localhost:8080/chat/send/text' => Http::response('<html>Error 502 Bad Gateway</html>', 200),
        ]);

        $response = $this->actingAs($owner)->postJson('/owner/settings/whatsapp/test-message', [
            'phone' => '081234567890',
            'mode' => 'automatic',
            'server_url' => 'http://localhost:8080',
            'message' => 'Pesan uji coba',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);

        expect($response->json('message'))->toContain('bukan format JSON');
    });

    it('allows owner to test connection and test message via api routes', function () {
        $owner = User::factory()->create(['role' => 'owner', 'is_active' => true]);

        $connResponse = $this->actingAs($owner)->postJson('/api/whatsapp/test-connection', [
            'mode' => 'manual',
        ]);
        $connResponse->assertOk()->assertJsonPath('success', true);

        $msgResponse = $this->actingAs($owner)->postJson('/api/whatsapp/test-message', [
            'phone' => '081234567890',
            'mode' => 'manual',
            'message' => 'Tes rute API',
        ]);
        $msgResponse->assertOk()->assertJsonPath('success', true);
    });

    it('returns 422 if recipient phone is missing', function () {
        $owner = User::factory()->create(['role' => 'owner', 'is_active' => true]);

        $response = $this->actingAs($owner)->postJson('/owner/settings/whatsapp/test-message', [
            'phone' => '',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['phone']);
    });

    it('detects gateway returning 200 with error payload in test-connection', function () {
        $owner = User::factory()->create(['role' => 'owner', 'is_active' => true]);

        Http::fake([
            'http://localhost:8080/session/status' => Http::response([
                'code' => 200,
                'error' => 'Device session unlinked from WhatsApp mobile client',
            ], 200),
        ]);

        $response = $this->actingAs($owner)->postJson('/owner/settings/whatsapp/test-connection', [
            'mode' => 'automatic',
            'server_url' => 'http://localhost:8080',
            'api_key' => 'valid-token',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);

        expect($response->json('message'))->toContain('Device session unlinked');
    });

    it('detects gateway returning 200 with primitive JSON in test-connection', function () {
        $owner = User::factory()->create(['role' => 'owner', 'is_active' => true]);

        Http::fake([
            'http://localhost:8080/session/status' => Http::response('"OK"', 200, ['Content-Type' => 'application/json']),
        ]);

        $response = $this->actingAs($owner)->postJson('/owner/settings/whatsapp/test-connection', [
            'mode' => 'automatic',
            'server_url' => 'http://localhost:8080',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);

        expect($response->json('message'))->toContain('bukan gateway WhatsApp yang valid');
    });

    it('detects gateway returning 200 with error payload in test-message', function () {
        $owner = User::factory()->create(['role' => 'owner', 'is_active' => true]);

        Http::fake([
            'http://localhost:8080/chat/send/text' => Http::response([
                'error' => 'Destination phone number is not on WhatsApp',
            ], 200),
        ]);

        $response = $this->actingAs($owner)->postJson('/owner/settings/whatsapp/test-message', [
            'phone' => '081234567890',
            'mode' => 'automatic',
            'server_url' => 'http://localhost:8080',
            'message' => 'Pesan uji coba',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);

        expect($response->json('message'))->toContain('Destination phone number is not on WhatsApp');
    });

    it('detects gateway returning 200 with primitive JSON in test-message', function () {
        $owner = User::factory()->create(['role' => 'owner', 'is_active' => true]);

        Http::fake([
            'http://localhost:8080/chat/send/text' => Http::response('123', 200, ['Content-Type' => 'application/json']),
        ]);

        $response = $this->actingAs($owner)->postJson('/owner/settings/whatsapp/test-message', [
            'phone' => '081234567890',
            'mode' => 'automatic',
            'server_url' => 'http://localhost:8080',
            'message' => 'Pesan uji coba',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);

        expect($response->json('message'))->toContain('bukan format JSON yang valid');
    });

    it('handles nested array error objects without Array to string conversion in test-connection', function () {
        $owner = User::factory()->create(['role' => 'owner', 'is_active' => true]);

        Http::fake([
            'http://localhost:8080/session/status' => Http::response([
                'error' => ['message' => 'Session auth token expired', 'code' => 401],
            ], 401),
        ]);

        $response = $this->actingAs($owner)->postJson('/owner/settings/whatsapp/test-connection', [
            'mode' => 'automatic',
            'server_url' => 'http://localhost:8080',
            'api_key' => 'expired-token',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);

        expect($response->json('message'))->toContain('Session auth token expired')
            ->and($response->json('message'))->not->toContain('Array to string conversion');
    });

    it('handles nested array error objects without Array to string conversion in test-message', function () {
        $owner = User::factory()->create(['role' => 'owner', 'is_active' => true]);

        Http::fake([
            'http://localhost:8080/chat/send/text' => Http::response([
                'error' => ['message' => 'Destination WhatsApp server timed out', 'code' => 504],
            ], 504),
        ]);

        $response = $this->actingAs($owner)->postJson('/owner/settings/whatsapp/test-message', [
            'phone' => '081234567890',
            'mode' => 'automatic',
            'server_url' => 'http://localhost:8080',
            'message' => 'Pesan uji coba',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);

        expect($response->json('message'))->toContain('Destination WhatsApp server timed out')
            ->and($response->json('message'))->not->toContain('Array to string conversion');
    });

    it('detects gateway returning 200 with null connected value as disconnected in test-connection', function () {
        $owner = User::factory()->create(['role' => 'owner', 'is_active' => true]);

        Http::fake([
            'http://localhost:8080/session/status' => Http::response([
                'code' => 200,
                'data' => [
                    'connected' => null,
                ],
            ], 200),
        ]);

        $response = $this->actingAs($owner)->postJson('/owner/settings/whatsapp/test-connection', [
            'mode' => 'automatic',
            'server_url' => 'http://localhost:8080',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);

        expect($response->json('message'))->toContain('belum aktif');
    });

    it('blocks non-owner from sending WhatsApp test messages', function () {
        $cashier = User::factory()->create(['role' => 'cashier', 'is_active' => true]);

        $response = $this->actingAs($cashier)->postJson('/owner/settings/whatsapp/test-message', [
            'phone' => '081234567890',
        ]);

        $response->assertForbidden();
    });

    it('detects gateway returning 200 with status=error in test-connection endpoint', function () {
        $owner = User::factory()->create(['role' => 'owner', 'is_active' => true]);

        Http::fake([
            'http://localhost:8080/session/status' => Http::response([
                'status' => 'error',
                'message' => 'WhatsApp gateway session died',
            ], 200),
        ]);

        $response = $this->actingAs($owner)->postJson('/owner/settings/whatsapp/test-connection', [
            'mode' => 'automatic',
            'server_url' => 'http://localhost:8080',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);

        expect($response->json('message'))->toContain('WhatsApp gateway session died');
    });

    it('detects gateway returning 200 with status=error in test-message endpoint', function () {
        $owner = User::factory()->create(['role' => 'owner', 'is_active' => true]);

        Http::fake([
            'http://localhost:8080/chat/send/text' => Http::response([
                'status' => 'error',
                'message' => 'Target phone is blocked or disconnected',
            ], 200),
        ]);

        $response = $this->actingAs($owner)->postJson('/owner/settings/whatsapp/test-message', [
            'phone' => '081234567890',
            'mode' => 'automatic',
            'server_url' => 'http://localhost:8080',
            'message' => 'Uji coba status error',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);

        expect($response->json('message'))->toContain('Target phone is blocked or disconnected');
    });

    it('returns 422 for invalid server url host in test-connection endpoint', function () {
        $owner = User::factory()->create(['role' => 'owner', 'is_active' => true]);

        $response = $this->actingAs($owner)->postJson('/owner/settings/whatsapp/test-connection', [
            'mode' => 'automatic',
            'server_url' => 'http://',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);

        expect($response->json('message'))->toContain('tidak valid');
    });

    it('returns 422 for invalid server url host in test-message endpoint', function () {
        $owner = User::factory()->create(['role' => 'owner', 'is_active' => true]);

        $response = $this->actingAs($owner)->postJson('/owner/settings/whatsapp/test-message', [
            'phone' => '081234567890',
            'mode' => 'automatic',
            'server_url' => 'http://',
            'message' => 'Uji coba URL invalid',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);

        expect($response->json('message'))->toContain('tidak valid');
    });

    it('detects gateway returning 200 with status=401 in test-connection endpoint', function () {
        $owner = User::factory()->create(['role' => 'owner', 'is_active' => true]);

        Http::fake([
            'http://localhost:8080/session/status' => Http::response([
                'status' => 401,
                'message' => 'Unauthorized device credentials',
            ], 200),
        ]);

        $response = $this->actingAs($owner)->postJson('/owner/settings/whatsapp/test-connection', [
            'mode' => 'automatic',
            'server_url' => 'http://localhost:8080',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);

        expect($response->json('message'))->toContain('Unauthorized device credentials');
    });

    it('detects gateway returning 200 with sent=false in test-message endpoint', function () {
        $owner = User::factory()->create(['role' => 'owner', 'is_active' => true]);

        Http::fake([
            'http://localhost:8080/chat/send/text' => Http::response([
                'sent' => false,
                'message' => 'Outbound message dispatch failed',
            ], 200),
        ]);

        $response = $this->actingAs($owner)->postJson('/owner/settings/whatsapp/test-message', [
            'phone' => '081234567890',
            'mode' => 'automatic',
            'server_url' => 'http://localhost:8080',
            'message' => 'Uji coba sent false',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);

        expect($response->json('message'))->toContain('Outbound message dispatch failed');
    });

    it('rejects non-http scheme in test-connection endpoint', function () {
        $owner = User::factory()->create(['role' => 'owner', 'is_active' => true]);

        $response = $this->actingAs($owner)->postJson('/owner/settings/whatsapp/test-connection', [
            'mode' => 'automatic',
            'server_url' => 'ftp://localhost:8080',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);

        expect($response->json('message'))->toContain('tidak valid');
    });

    it('detects unlinked session with empty jid in test-connection endpoint', function () {
        $owner = User::factory()->create(['role' => 'owner', 'is_active' => true]);

        Http::fake([
            'http://localhost:8080/session/status' => Http::response([
                'code' => 200,
                'success' => true,
                'data' => [
                    'details' => 'Connected!',
                    'jid' => '',
                ],
            ], 200),
        ]);

        $response = $this->actingAs($owner)->postJson('/owner/settings/whatsapp/test-connection', [
            'mode' => 'automatic',
            'server_url' => 'http://localhost:8080',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);

        expect($response->json('message'))->toContain('belum aktif (disconnected/unpaired)');
    });

    it('detects masked status 401 behind envelope code 200 in test-connection endpoint', function () {
        $owner = User::factory()->create(['role' => 'owner', 'is_active' => true]);

        Http::fake([
            'http://localhost:8080/session/status' => Http::response([
                'code' => 200,
                'status' => 401,
                'message' => 'Token has expired',
            ], 200),
        ]);

        $response = $this->actingAs($owner)->postJson('/owner/settings/whatsapp/test-connection', [
            'mode' => 'automatic',
            'server_url' => 'http://localhost:8080',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);

        expect($response->json('message'))->toContain('Token has expired');
    });

    it('detects masked statusCode 500 behind envelope code 200 in test-message endpoint', function () {
        $owner = User::factory()->create(['role' => 'owner', 'is_active' => true]);

        Http::fake([
            'http://localhost:8080/chat/send/text' => Http::response([
                'code' => 200,
                'statusCode' => 500,
                'message' => 'Downstream failure in gateway',
            ], 200),
        ]);

        $response = $this->actingAs($owner)->postJson('/owner/settings/whatsapp/test-message', [
            'phone' => '081234567890',
            'mode' => 'automatic',
            'server_url' => 'http://localhost:8080',
            'message' => 'Uji coba',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);

        expect($response->json('message'))->toContain('Downstream failure in gateway');
    });

    it('detects whitespace server url as unset in test-connection endpoint', function () {
        $owner = User::factory()->create(['role' => 'owner', 'is_active' => true]);

        $response = $this->actingAs($owner)->postJson('/owner/settings/whatsapp/test-connection', [
            'mode' => 'automatic',
            'server_url' => '   ',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);

        expect($response->json('message'))->toContain('belum diatur');
    });

    it('detects whitespace server url as unset in test-message endpoint', function () {
        $owner = User::factory()->create(['role' => 'owner', 'is_active' => true]);

        $response = $this->actingAs($owner)->postJson('/owner/settings/whatsapp/test-message', [
            'phone' => '081234567890',
            'mode' => 'automatic',
            'server_url' => '   ',
            'message' => 'Test whitespace',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);

        expect($response->json('message'))->toContain('belum diatur');
    });

    it('detects empty JSON response as failure in test-connection endpoint', function () {
        $owner = User::factory()->create(['role' => 'owner', 'is_active' => true]);

        Http::fake([
            'http://localhost:8080/session/status' => Http::response([], 200),
        ]);

        $response = $this->actingAs($owner)->postJson('/owner/settings/whatsapp/test-connection', [
            'mode' => 'automatic',
            'server_url' => 'http://localhost:8080',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);

        expect($response->json('message'))->toContain('tidak mengembalikan data status sesi');
    });

    it('detects empty JSON response as failure in test-message endpoint', function () {
        $owner = User::factory()->create(['role' => 'owner', 'is_active' => true]);

        Http::fake([
            'http://localhost:8080/chat/send/text' => Http::response([], 200),
        ]);

        $response = $this->actingAs($owner)->postJson('/owner/settings/whatsapp/test-message', [
            'phone' => '081234567890',
            'mode' => 'automatic',
            'server_url' => 'http://localhost:8080',
            'message' => 'Testing empty JSON',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);

        expect($response->json('message'))->toContain('tidak mengembalikan konfirmasi pengiriman');
    });

    it('detects status failure in test-connection endpoint', function () {
        $owner = User::factory()->create(['role' => 'owner', 'is_active' => true]);

        Http::fake([
            'http://localhost:8080/session/status' => Http::response([
                'status' => 'failure',
                'message' => 'Remote gateway denied authentication',
            ], 200),
        ]);

        $response = $this->actingAs($owner)->postJson('/owner/settings/whatsapp/test-connection', [
            'mode' => 'automatic',
            'server_url' => 'http://localhost:8080',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);

        expect($response->json('message'))->toContain('Remote gateway denied authentication');
    });
});

describe('WhatsApp Session Pairing & Disconnection (R4)', function () {

    it('returns QR data when connect-session is requested with qr method', function () {
        $owner = User::factory()->create(['role' => 'owner', 'is_active' => true]);

        Http::fake([
            'http://localhost:8080/session/connect' => Http::response([
                'code' => 200,
                'success' => true,
                'message' => 'Silakan scan QR code',
                'data' => [
                    'connected' => true,
                    'loggedIn' => false,
                    'qr' => 'https://wa.me/settings/linked_devices#mock-qr',
                    'qr_image' => 'data:image/png;base64,mockPngBase64',
                ],
            ], 200),
        ]);

        $response = $this->actingAs($owner)->postJson('/owner/settings/whatsapp/connect-session', [
            'method' => 'qr',
            'server_url' => 'http://localhost:8080',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.qr', 'https://wa.me/settings/linked_devices#mock-qr')
            ->assertJsonPath('data.qr_image', 'data:image/png;base64,mockPngBase64');
    });

    it('returns 8-character pairing code when connect-session is requested with otp method', function () {
        $owner = User::factory()->create(['role' => 'owner', 'is_active' => true]);

        Http::fake([
            'http://localhost:8080/session/pair-code' => Http::response([
                'code' => 200,
                'success' => true,
                'message' => 'Kode pairing WhatsApp berhasil dibuat.',
                'data' => [
                    'pairing_code' => 'ABCD1234',
                    'formatted_code' => 'ABCD-1234',
                    'phone' => '6281234567890',
                    'instructions' => ['Buka WhatsApp', 'Masukkan kode'],
                ],
            ], 200),
        ]);

        $response = $this->actingAs($owner)->postJson('/owner/settings/whatsapp/connect-session', [
            'method' => 'otp',
            'phone' => '081234567890',
            'server_url' => 'http://localhost:8080',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.pairing_code', 'ABCD1234')
            ->assertJsonPath('data.formatted_code', 'ABCD-1234');
    });

    it('disconnects and logs out active WhatsApp session', function () {
        $owner = User::factory()->create(['role' => 'owner', 'is_active' => true]);

        Http::fake([
            'http://localhost:8080/session/logout' => Http::response([
                'code' => 200,
                'success' => true,
                'message' => 'Sesi WhatsApp berhasil diputuskan (logout).',
            ], 200),
        ]);

        $response = $this->actingAs($owner)->postJson('/owner/settings/whatsapp/disconnect-session', [
            'server_url' => 'http://localhost:8080',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Sesi WhatsApp berhasil diputuskan (logout).');
    });
});
