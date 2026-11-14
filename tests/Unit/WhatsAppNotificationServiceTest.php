<?php

use App\Models\BusinessSetting;
use App\Models\Customer;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Services\WhatsAppNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

describe('WhatsAppNotificationService Unit Tests', function () {
    beforeEach(function () {
        $this->service = app(WhatsAppNotificationService::class);
        $this->customer = Customer::factory()->create([
            'name' => 'Siti Nurhaliza',
            'phone' => '081234567890',
            'phone_normalized' => '+6281234567890',
        ]);
        $this->transaction = Transaction::factory()->create([
            'customer_id' => $this->customer->id,
            'invoice_number' => 'INV-20261114-1234',
            'total_amount' => 75000.00,
            'paid_amount' => 50000.00,
            'outstanding_balance' => 25000.00,
            'staging_rack' => 'R-02-B',
        ]);

        TransactionItem::factory()->create([
            'transaction_id' => $this->transaction->id,
            'item_name_snapshot' => 'Cuci Komplit Reguler',
            'actual_quantity' => 5.00,
            'pricing_unit_snapshot' => 'kg',
        ]);
    });

    it('builds clear intake confirmation message with invoice, items, and receipt URL', function () {
        $msg = $this->service->buildIntakeMessage($this->transaction);

        expect($msg)->toContain('Siti Nurhaliza')
            ->and($msg)->toContain('INV-20261114-1234')
            ->and($msg)->toContain('Cuci Komplit Reguler')
            ->and($msg)->toContain('75.000')
            ->and($msg)->toContain('25.000');
    });

    it('builds ready for pickup message highlighting staging rack coordinates', function () {
        $msg = $this->service->buildReadyForPickupMessage($this->transaction);

        expect($msg)->toContain('Siti Nurhaliza')
            ->and($msg)->toContain('R-02-B')
            ->and($msg)->toContain('SELESAI');
    });

    it('builds receivable reminder message showing outstanding balance', function () {
        $msg = $this->service->buildReceivableReminderMessage($this->transaction);

        expect($msg)->toContain('Siti Nurhaliza')
            ->and($msg)->toContain('25.000')
            ->and($msg)->toContain('INV-20261114-1234');
    });

    it('formats wa.me manual fallback URL correctly', function () {
        $url = $this->service->buildManualWhatsAppUrl('+6281234567890', 'Halo apa kabar');

        expect($url)->toBe('https://wa.me/6281234567890?text=Halo+apa+kabar');
    });

    it('renders custom intake template persisted in business_settings with variables', function () {
        BusinessSetting::set(
            'whatsapp.template_intake',
            'Hai {customer_name}, pesanan {invoice_number} siap dicuci! Total: {total_amount}',
            'string',
            'whatsapp'
        );

        $msg = $this->service->buildIntakeMessage($this->transaction);

        expect($msg)->toBe('Hai Siti Nurhaliza, pesanan INV-20261114-1234 siap dicuci! Total: Rp 75.000');
    });

    it('renders custom ready for pickup template with rack coordinates', function () {
        BusinessSetting::set(
            'whatsapp.template_ready',
            'Halo {{customer_name}}, cucian {{invoice_number}} selesai di {{rack_location}}',
            'string',
            'whatsapp'
        );

        $msg = $this->service->buildReadyForPickupMessage($this->transaction);

        expect($msg)->toBe('Halo Siti Nurhaliza, cucian INV-20261114-1234 selesai di R-02-B');
    });

    it('renders custom receivable reminder template with debt amount', function () {
        BusinessSetting::set(
            'whatsapp.template_reminder',
            'Kak {{ customer_name }}, tagihan {{invoice_number}} tersisa {{debt_amount}}',
            'string',
            'whatsapp'
        );

        $msg = $this->service->buildReceivableReminderMessage($this->transaction);

        expect($msg)->toBe('Kak Siti Nurhaliza, tagihan INV-20261114-1234 tersisa Rp 25.000');
    });

    it('interpolates both single-bracket and double-bracket template placeholders', function () {
        $template = 'A: {name}, B: {{name}}, C: {{ name }}';
        $result = $this->service->interpolateTemplate($template, ['name' => 'Laundry']);

        expect($result)->toBe('A: Laundry, B: Laundry, C: Laundry');
    });

    it('detects gateway mode correctly from business_settings', function () {
        BusinessSetting::set('whatsapp.mode', 'manual', 'string', 'whatsapp');
        expect($this->service->getGatewayMode())->toBe('manual')
            ->and($this->service->isManualMode())->toBeTrue()
            ->and($this->service->isAutomaticMode())->toBeFalse();

        BusinessSetting::set('whatsapp.mode', 'automatic', 'string', 'whatsapp');
        expect($this->service->getGatewayMode())->toBe('automatic')
            ->and($this->service->isManualMode())->toBeFalse()
            ->and($this->service->isAutomaticMode())->toBeTrue();
    });

    it('performs gateway connection health test via Http facade', function () {
        Http::fake([
            'http://localhost:8080/session/status' => Http::response([
                'code' => 200,
                'data' => ['connected' => true, 'loggedIn' => true],
            ], 200),
        ]);

        $res = $this->service->testConnection([
            'mode' => 'automatic',
            'server_url' => 'http://localhost:8080',
            'api_key' => 'tok-123',
        ]);

        expect($res['success'])->toBeTrue()
            ->and($res['mode'])->toBe('automatic')
            ->and($res['data']['connected'])->toBeTrue();
    });

    it('sends test message to gateway via POST /chat/send/text', function () {
        Http::fake([
            'http://localhost:8080/chat/send/text' => Http::response([
                'code' => 200,
                'data' => ['id' => 'MSG-001'],
            ], 200),
        ]);

        $res = $this->service->sendTestMessage('081234567890', 'Test notification', [
            'mode' => 'automatic',
            'server_url' => 'http://localhost:8080',
            'api_key' => 'tok-123',
        ]);

        expect($res['success'])->toBeTrue()
            ->and($res['mode'])->toBe('automatic');

        Http::assertSent(fn ($req) => $req['Phone'] === '6281234567890' && $req['Body'] === 'Test notification');
    });

    it('detects disconnected session in testConnection even with 200 status', function () {
        Http::fake([
            'http://localhost:8080/session/status' => Http::response([
                'code' => 200,
                'data' => ['connected' => false, 'loggedIn' => false],
            ], 200),
        ]);

        $res = $this->service->testConnection([
            'mode' => 'automatic',
            'server_url' => 'http://localhost:8080',
        ]);

        expect($res['success'])->toBeFalse()
            ->and($res['status'])->toBe(422)
            ->and($res['message'])->toContain('belum aktif');
    });

    it('rejects invalid phone numbers in sendTestMessage', function () {
        $res = $this->service->sendTestMessage('abc', 'Pesan');

        expect($res['success'])->toBeFalse()
            ->and($res['status'])->toBe(422)
            ->and($res['message'])->toContain('tidak valid');
    });

    it('renders sample template message with realistic transaction placeholders', function () {
        $msg = $this->service->renderSampleMessage('intake');

        expect($msg)->toContain('Budi Santoso')
            ->and($msg)->toContain('INV-TEST-001')
            ->and($msg)->toContain('Cuci Komplit Reguler')
            ->and($msg)->toContain('Rp 50.000');
    });

    it('detects non-JSON response in testConnection', function () {
        Http::fake([
            'http://localhost:8080/session/status' => Http::response('<html>Welcome</html>', 200),
        ]);

        $res = $this->service->testConnection([
            'mode' => 'automatic',
            'server_url' => 'http://localhost:8080',
        ]);

        expect($res['success'])->toBeFalse()
            ->and($res['status'])->toBe(422)
            ->and($res['message'])->toContain('bukan format JSON');
    });

    it('detects gateway returning 200 with success=false in sendTestMessage', function () {
        Http::fake([
            'http://localhost:8080/chat/send/text' => Http::response([
                'success' => false,
                'error' => 'Device logged out',
            ], 200),
        ]);

        $res = $this->service->sendTestMessage('081234567890', 'Test', [
            'mode' => 'automatic',
            'server_url' => 'http://localhost:8080',
        ]);

        expect($res['success'])->toBeFalse()
            ->and($res['status'])->toBe(422)
            ->and($res['message'])->toContain('Device logged out');
    });

    it('detects non-JSON response in sendTestMessage', function () {
        Http::fake([
            'http://localhost:8080/chat/send/text' => Http::response('<html>Gateway Error</html>', 200),
        ]);

        $res = $this->service->sendTestMessage('081234567890', 'Test', [
            'mode' => 'automatic',
            'server_url' => 'http://localhost:8080',
        ]);

        expect($res['success'])->toBeFalse()
            ->and($res['status'])->toBe(422)
            ->and($res['message'])->toContain('bukan format JSON');
    });

    it('interpolates total_amount and status_text in ready and reminder messages', function () {
        BusinessSetting::set(
            'whatsapp.template_reminder',
            'Halo {{customer_name}}, total: {{total_amount}}, sisa: {{debt_amount}}, status: {{status_text}}',
            'string',
            'whatsapp'
        );

        $msg = $this->service->buildReceivableReminderMessage($this->transaction);

        expect($msg)->toContain('total: Rp 75.000')
            ->and($msg)->toContain('sisa: Rp 25.000')
            ->and($msg)->toContain('Sisa Tagihan: Rp 25.000');
    });

    it('interpolates items_summary, paid_info, and rack_location across ready and reminder messages', function () {
        BusinessSetting::set(
            'whatsapp.template_ready',
            'Halo {{customer_name}}, nota {{invoice_number}}:\n{{items_summary}}{{paid_info}}Lokasi: {{rack_location}}',
            'string',
            'whatsapp'
        );

        $readyMsg = $this->service->buildReadyForPickupMessage($this->transaction);

        expect($readyMsg)->toContain('Cuci Komplit Reguler')
            ->and($readyMsg)->toContain('50.000')
            ->and($readyMsg)->toContain('Lokasi: R-02-B');

        BusinessSetting::set(
            'whatsapp.template_reminder',
            'Pengingat: {{items_summary}}{{paid_info}}Sisa: {{debt_amount}} di {{rack_location}}',
            'string',
            'whatsapp'
        );

        $reminderMsg = $this->service->buildReceivableReminderMessage($this->transaction);

        expect($reminderMsg)->toContain('Cuci Komplit Reguler')
            ->and($reminderMsg)->toContain('50.000')
            ->and($reminderMsg)->toContain('R-02-B');
    });

    it('detects gateway returning 200 with error key in payload as failure in testConnection', function () {
        Http::fake([
            'http://localhost:8080/session/status' => Http::response([
                'error' => 'Device session unlinked',
            ], 200),
        ]);

        $res = $this->service->testConnection([
            'mode' => 'automatic',
            'server_url' => 'http://localhost:8080',
        ]);

        expect($res['success'])->toBeFalse()
            ->and($res['status'])->toBe(422)
            ->and($res['message'])->toContain('Device session unlinked');
    });

    it('detects gateway returning 200 with string booleans for connected and loggedIn as failure', function () {
        Http::fake([
            'http://localhost:8080/session/status' => Http::response([
                'data' => [
                    'connected' => 'false',
                    'loggedIn' => 'false',
                ],
            ], 200),
        ]);

        $res = $this->service->testConnection([
            'mode' => 'automatic',
            'server_url' => 'http://localhost:8080',
        ]);

        expect($res['success'])->toBeFalse()
            ->and($res['status'])->toBe(422)
            ->and($res['message'])->toContain('belum aktif');
    });

    it('detects gateway returning 200 with primitive JSON scalars as failure in testConnection', function () {
        Http::fake([
            'http://localhost:8080/session/status' => Http::response('"OK"', 200, ['Content-Type' => 'application/json']),
        ]);

        $res = $this->service->testConnection([
            'mode' => 'automatic',
            'server_url' => 'http://localhost:8080',
        ]);

        expect($res['success'])->toBeFalse()
            ->and($res['status'])->toBe(422)
            ->and($res['message'])->toContain('bukan gateway WhatsApp yang valid');
    });

    it('detects gateway returning 200 with error key in payload as failure in sendTestMessage', function () {
        Http::fake([
            'http://localhost:8080/chat/send/text' => Http::response([
                'error' => 'Recipient phone is not registered on WhatsApp',
            ], 200),
        ]);

        $res = $this->service->sendTestMessage('081234567890', 'Test', [
            'mode' => 'automatic',
            'server_url' => 'http://localhost:8080',
        ]);

        expect($res['success'])->toBeFalse()
            ->and($res['status'])->toBe(422)
            ->and($res['message'])->toContain('Recipient phone is not registered');
    });

    it('detects gateway returning 200 with primitive JSON scalars as failure in sendTestMessage', function () {
        Http::fake([
            'http://localhost:8080/chat/send/text' => Http::response('123', 200, ['Content-Type' => 'application/json']),
        ]);

        $res = $this->service->sendTestMessage('081234567890', 'Test', [
            'mode' => 'automatic',
            'server_url' => 'http://localhost:8080',
        ]);

        expect($res['success'])->toBeFalse()
            ->and($res['status'])->toBe(422)
            ->and($res['message'])->toContain('bukan format JSON yang valid');
    });

    it('extracts nested array error objects safely without Array to string conversion in testConnection', function () {
        Http::fake([
            'http://localhost:8080/session/status' => Http::response([
                'error' => ['message' => 'Token has expired and requires refresh', 'code' => 401],
            ], 401),
        ]);

        $res = $this->service->testConnection([
            'mode' => 'automatic',
            'server_url' => 'http://localhost:8080',
        ]);

        expect($res['success'])->toBeFalse()
            ->and($res['status'])->toBe(401)
            ->and($res['message'])->toContain('Token has expired and requires refresh')
            ->and($res['message'])->not->toContain('Array to string conversion');
    });

    it('extracts nested array error objects in 200 response safely in testConnection', function () {
        Http::fake([
            'http://localhost:8080/session/status' => Http::response([
                'success' => false,
                'error' => ['message' => 'WhatsApp companion device unlinked', 'code' => 'ERR_UNLINKED'],
            ], 200),
        ]);

        $res = $this->service->testConnection([
            'mode' => 'automatic',
            'server_url' => 'http://localhost:8080',
        ]);

        expect($res['success'])->toBeFalse()
            ->and($res['status'])->toBe(422)
            ->and($res['message'])->toContain('WhatsApp companion device unlinked');
    });

    it('extracts nested array error objects safely without Array to string conversion in sendTestMessage', function () {
        Http::fake([
            'http://localhost:8080/chat/send/text' => Http::response([
                'error' => ['message' => 'Destination device unreachable', 'code' => 500],
            ], 500),
        ]);

        $res = $this->service->sendTestMessage('081234567890', 'Test', [
            'mode' => 'automatic',
            'server_url' => 'http://localhost:8080',
        ]);

        expect($res['success'])->toBeFalse()
            ->and($res['status'])->toBe(500)
            ->and($res['message'])->toContain('Destination device unreachable')
            ->and($res['message'])->not->toContain('Array to string conversion');
    });

    it('detects disconnected session when connected or loggedIn value is null', function () {
        Http::fake([
            'http://localhost:8080/session/status' => Http::response([
                'data' => [
                    'connected' => null,
                    'loggedIn' => null,
                ],
            ], 200),
        ]);

        $res = $this->service->testConnection([
            'mode' => 'automatic',
            'server_url' => 'http://localhost:8080',
        ]);

        expect($res['success'])->toBeFalse()
            ->and($res['status'])->toBe(422)
            ->and($res['message'])->toContain('belum aktif');
    });

    it('detects disconnected session when status or state string indicates disconnected', function () {
        Http::fake([
            'http://localhost:8080/session/status' => Http::response([
                'data' => [
                    'status' => 'DISCONNECTED',
                ],
            ], 200),
        ]);

        $res = $this->service->testConnection([
            'mode' => 'automatic',
            'server_url' => 'http://localhost:8080',
        ]);

        expect($res['success'])->toBeFalse()
            ->and($res['status'])->toBe(422)
            ->and($res['message'])->toContain('belum aktif');
    });

    it('falls back to default configuration and templates when empty strings exist in business_settings', function () {
        BusinessSetting::set('whatsapp.server_url', '', 'string', 'whatsapp');
        BusinessSetting::set('whatsapp.session_id', '', 'string', 'whatsapp');
        BusinessSetting::set('whatsapp.template_intake', '', 'string', 'whatsapp');
        BusinessSetting::set('whatsapp.template_ready', '', 'string', 'whatsapp');
        BusinessSetting::set('whatsapp.template_reminder', '', 'string', 'whatsapp');

        $config = $this->service->getGatewayConfig();

        expect($config['server_url'])->toBe('http://localhost:8080')
            ->and($config['session_id'])->toBe('6281234567890')
            ->and($config['template_intake'])->toBe($this->service->getIntakeDefaultTemplate())
            ->and($config['template_ready'])->toBe($this->service->getReadyDefaultTemplate())
            ->and($config['template_reminder'])->toBe($this->service->getReminderDefaultTemplate());

        $sampleIntake = $this->service->renderSampleMessage('intake');
        expect($sampleIntake)->toContain('cucian Anda telah kami terima');
    });

    it('supports rack_location and staging_rack variables in buildIntakeMessage', function () {
        BusinessSetting::set(
            'whatsapp.template_intake',
            'Halo {{customer_name}}, pesanan {{invoice_number}} diterima di {{rack_location}}',
            'string',
            'whatsapp'
        );

        $msg = $this->service->buildIntakeMessage($this->transaction);

        expect($msg)->toBe('Halo Siti Nurhaliza, pesanan INV-20261114-1234 diterima di R-02-B');
    });

    it('detects gateway returning 200 with status=error as failure in testConnection', function () {
        Http::fake([
            'http://localhost:8080/session/status' => Http::response([
                'status' => 'error',
                'message' => 'WhatsApp gateway session died',
            ], 200),
        ]);

        $res = $this->service->testConnection([
            'mode' => 'automatic',
            'server_url' => 'http://localhost:8080',
        ]);

        expect($res['success'])->toBeFalse()
            ->and($res['status'])->toBe(422)
            ->and($res['message'])->toContain('WhatsApp gateway session died');
    });

    it('detects gateway returning 200 with boolean status=false as failure in testConnection', function () {
        Http::fake([
            'http://localhost:8080/session/status' => Http::response([
                'status' => false,
                'message' => 'Session auth failed',
            ], 200),
        ]);

        $res = $this->service->testConnection([
            'mode' => 'automatic',
            'server_url' => 'http://localhost:8080',
        ]);

        expect($res['success'])->toBeFalse()
            ->and($res['status'])->toBe(422)
            ->and($res['message'])->toContain('Session auth failed');
    });

    it('detects gateway returning 200 with data.error as failure in testConnection', function () {
        Http::fake([
            'http://localhost:8080/session/status' => Http::response([
                'code' => 200,
                'data' => [
                    'error' => 'Device session revoked by companion app',
                ],
            ], 200),
        ]);

        $res = $this->service->testConnection([
            'mode' => 'automatic',
            'server_url' => 'http://localhost:8080',
        ]);

        expect($res['success'])->toBeFalse()
            ->and($res['status'])->toBe(422)
            ->and($res['message'])->toContain('Device session revoked by companion app');
    });

    it('detects gateway returning 200 with status=offline as failure in testConnection', function () {
        Http::fake([
            'http://localhost:8080/session/status' => Http::response([
                'code' => 200,
                'data' => [
                    'status' => 'offline',
                ],
            ], 200),
        ]);

        $res = $this->service->testConnection([
            'mode' => 'automatic',
            'server_url' => 'http://localhost:8080',
        ]);

        expect($res['success'])->toBeFalse()
            ->and($res['status'])->toBe(422)
            ->and($res['message'])->toContain('belum aktif');
    });

    it('detects invalid server url host in testConnection', function () {
        $res = $this->service->testConnection([
            'mode' => 'automatic',
            'server_url' => 'http://',
        ]);

        expect($res['success'])->toBeFalse()
            ->and($res['status'])->toBe(422)
            ->and($res['message'])->toContain('tidak valid');
    });

    it('detects gateway returning 200 with status=error as failure in sendTestMessage', function () {
        Http::fake([
            'http://localhost:8080/chat/send/text' => Http::response([
                'status' => 'error',
                'message' => 'Device not connected to WhatsApp',
            ], 200),
        ]);

        $res = $this->service->sendTestMessage('081234567890', 'Hello', [
            'mode' => 'automatic',
            'server_url' => 'http://localhost:8080',
        ]);

        expect($res['success'])->toBeFalse()
            ->and($res['status'])->toBe(422)
            ->and($res['message'])->toContain('Device not connected to WhatsApp');
    });

    it('detects gateway returning 200 with boolean status=false as failure in sendTestMessage', function () {
        Http::fake([
            'http://localhost:8080/chat/send/text' => Http::response([
                'status' => false,
                'message' => 'Send queue full',
            ], 200),
        ]);

        $res = $this->service->sendTestMessage('081234567890', 'Hello', [
            'mode' => 'automatic',
            'server_url' => 'http://localhost:8080',
        ]);

        expect($res['success'])->toBeFalse()
            ->and($res['status'])->toBe(422)
            ->and($res['message'])->toContain('Send queue full');
    });

    it('detects gateway returning 200 with data.error as failure in sendTestMessage', function () {
        Http::fake([
            'http://localhost:8080/chat/send/text' => Http::response([
                'code' => 200,
                'data' => [
                    'error' => 'Anti-ban limit triggered on gateway',
                ],
            ], 200),
        ]);

        $res = $this->service->sendTestMessage('081234567890', 'Hello', [
            'mode' => 'automatic',
            'server_url' => 'http://localhost:8080',
        ]);

        expect($res['success'])->toBeFalse()
            ->and($res['status'])->toBe(422)
            ->and($res['message'])->toContain('Anti-ban limit triggered on gateway');
    });

    it('detects gateway returning 200 with connected=false as failure in sendTestMessage', function () {
        Http::fake([
            'http://localhost:8080/chat/send/text' => Http::response([
                'connected' => false,
                'message' => 'Gateway client disconnected',
            ], 200),
        ]);

        $res = $this->service->sendTestMessage('081234567890', 'Hello', [
            'mode' => 'automatic',
            'server_url' => 'http://localhost:8080',
        ]);

        expect($res['success'])->toBeFalse()
            ->and($res['status'])->toBe(422)
            ->and($res['message'])->toContain('belum aktif atau terputus');
    });

    it('detects invalid server url host in sendTestMessage', function () {
        $res = $this->service->sendTestMessage('081234567890', 'Hello', [
            'mode' => 'automatic',
            'server_url' => 'http://',
        ]);

        expect($res['success'])->toBeFalse()
            ->and($res['status'])->toBe(422)
            ->and($res['message'])->toContain('tidak valid');
    });

    it('detects gateway returning 200 with numeric status code in testConnection', function () {
        Http::fake([
            'http://localhost:8080/session/status' => Http::response([
                'status' => 401,
                'message' => 'Invalid session API key',
            ], 200),
        ]);

        $res = $this->service->testConnection([
            'mode' => 'automatic',
            'server_url' => 'http://localhost:8080',
        ]);

        expect($res['success'])->toBeFalse()
            ->and($res['status'])->toBe(422)
            ->and($res['message'])->toContain('Invalid session API key');
    });

    it('detects gateway returning 200 with statusCode in testConnection', function () {
        Http::fake([
            'http://localhost:8080/session/status' => Http::response([
                'statusCode' => 500,
                'message' => 'Internal gateway server error',
            ], 200),
        ]);

        $res = $this->service->testConnection([
            'mode' => 'automatic',
            'server_url' => 'http://localhost:8080',
        ]);

        expect($res['success'])->toBeFalse()
            ->and($res['status'])->toBe(422)
            ->and($res['message'])->toContain('Internal gateway server error');
    });

    it('detects state=disconnect and status=logout as disconnected in testConnection', function () {
        Http::fake([
            'http://localhost:8080/session/status' => Http::response([
                'state' => 'disconnect',
            ], 200),
        ]);

        $res = $this->service->testConnection([
            'mode' => 'automatic',
            'server_url' => 'http://localhost:8080',
        ]);

        expect($res['success'])->toBeFalse()
            ->and($res['status'])->toBe(422)
            ->and($res['message'])->toContain('belum aktif');
    });

    it('detects authenticated=false and isConnected=false in testConnection', function () {
        Http::fake([
            'http://localhost:8080/session/status' => Http::response([
                'authenticated' => false,
            ], 200),
        ]);

        $res = $this->service->testConnection([
            'mode' => 'automatic',
            'server_url' => 'http://localhost:8080',
        ]);

        expect($res['success'])->toBeFalse()
            ->and($res['status'])->toBe(422)
            ->and($res['message'])->toContain('belum aktif');
    });

    it('rejects non-http URI schemes in testConnection', function () {
        $res = $this->service->testConnection([
            'mode' => 'automatic',
            'server_url' => 'ftp://localhost:8080',
        ]);

        expect($res['success'])->toBeFalse()
            ->and($res['status'])->toBe(422)
            ->and($res['message'])->toContain('tidak valid');
    });

    it('detects gateway returning 200 with numeric status in sendTestMessage', function () {
        Http::fake([
            'http://localhost:8080/chat/send/text' => Http::response([
                'status' => 401,
                'message' => 'Unauthorized device token',
            ], 200),
        ]);

        $res = $this->service->sendTestMessage('081234567890', 'Hello', [
            'mode' => 'automatic',
            'server_url' => 'http://localhost:8080',
        ]);

        expect($res['success'])->toBeFalse()
            ->and($res['status'])->toBe(422)
            ->and($res['message'])->toContain('Unauthorized device token');
    });

    it('detects gateway returning 200 with sent=false in sendTestMessage', function () {
        Http::fake([
            'http://localhost:8080/chat/send/text' => Http::response([
                'sent' => false,
                'message' => 'Failed to deliver message via socket',
            ], 200),
        ]);

        $res = $this->service->sendTestMessage('081234567890', 'Hello', [
            'mode' => 'automatic',
            'server_url' => 'http://localhost:8080',
        ]);

        expect($res['success'])->toBeFalse()
            ->and($res['status'])->toBe(422)
            ->and($res['message'])->toContain('Failed to deliver message via socket');
    });

    it('rejects non-http URI schemes in sendTestMessage', function () {
        $res = $this->service->sendTestMessage('081234567890', 'Hello', [
            'mode' => 'automatic',
            'server_url' => 'ws://localhost:8080',
        ]);

        expect($res['success'])->toBeFalse()
            ->and($res['status'])->toBe(422)
            ->and($res['message'])->toContain('tidak valid');
    });

    it('detects status 401 even when envelope code 200 is present in testConnection', function () {
        Http::fake([
            'http://localhost:8080/session/status' => Http::response([
                'code' => 200,
                'status' => 401,
                'message' => 'Unauthorized token inside envelope',
            ], 200),
        ]);

        $res = $this->service->testConnection([
            'mode' => 'automatic',
            'server_url' => 'http://localhost:8080',
        ]);

        expect($res['success'])->toBeFalse()
            ->and($res['status'])->toBe(422)
            ->and($res['message'])->toContain('Unauthorized token inside envelope');
    });

    it('detects statusCode 500 even when envelope code 200 is present in sendTestMessage', function () {
        Http::fake([
            'http://localhost:8080/chat/send/text' => Http::response([
                'code' => 200,
                'statusCode' => 500,
                'message' => 'Internal failure masked by code 200',
            ], 200),
        ]);

        $res = $this->service->sendTestMessage('081234567890', 'Hello', [
            'mode' => 'automatic',
            'server_url' => 'http://localhost:8080',
        ]);

        expect($res['success'])->toBeFalse()
            ->and($res['status'])->toBe(422)
            ->and($res['message'])->toContain('Internal failure masked by code 200');
    });

    it('detects unlinked session when jid is empty string in testConnection', function () {
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

        $res = $this->service->testConnection([
            'mode' => 'automatic',
            'server_url' => 'http://localhost:8080',
        ]);

        expect($res['success'])->toBeFalse()
            ->and($res['status'])->toBe(422)
            ->and($res['message'])->toContain('belum aktif (disconnected/unpaired)');
    });

    it('detects unlinked session when jid is empty string in sendTestMessage', function () {
        Http::fake([
            'http://localhost:8080/chat/send/text' => Http::response([
                'code' => 200,
                'success' => true,
                'data' => [
                    'details' => 'Connected!',
                    'jid' => '',
                ],
            ], 200),
        ]);

        $res = $this->service->sendTestMessage('081234567890', 'Hello', [
            'mode' => 'automatic',
            'server_url' => 'http://localhost:8080',
        ]);

        expect($res['success'])->toBeFalse()
            ->and($res['status'])->toBe(422)
            ->and($res['message'])->toContain('belum aktif atau terputus');
    });

    it('detects pairing and qrcode status tokens as unlinked in testConnection', function () {
        Http::fake([
            'http://localhost:8080/session/status' => Http::response([
                'state' => 'pairing',
            ], 200),
        ]);

        $res = $this->service->testConnection([
            'mode' => 'automatic',
            'server_url' => 'http://localhost:8080',
        ]);

        expect($res['success'])->toBeFalse()
            ->and($res['status'])->toBe(422)
            ->and($res['message'])->toContain('belum aktif');
    });

    it('detects qr payload without connected flag as unlinked in testConnection', function () {
        Http::fake([
            'http://localhost:8080/session/status' => Http::response([
                'code' => 200,
                'data' => [
                    'qr' => 'data:image/png;base64,iVBORw0KGgo...',
                ],
            ], 200),
        ]);

        $res = $this->service->testConnection([
            'mode' => 'automatic',
            'server_url' => 'http://localhost:8080',
        ]);

        expect($res['success'])->toBeFalse()
            ->and($res['status'])->toBe(422)
            ->and($res['message'])->toContain('belum aktif');
    });

    it('detects whitespace server url as unset in testConnection and sendTestMessage', function () {
        $resConn = $this->service->testConnection([
            'mode' => 'automatic',
            'server_url' => '   ',
        ]);
        expect($resConn['success'])->toBeFalse()
            ->and($resConn['status'])->toBe(422)
            ->and($resConn['message'])->toContain('belum diatur');

        $resMsg = $this->service->sendTestMessage('081234567890', 'Hello', [
            'mode' => 'automatic',
            'server_url' => '   ',
        ]);
        expect($resMsg['success'])->toBeFalse()
            ->and($resMsg['status'])->toBe(422)
            ->and($resMsg['message'])->toContain('belum diatur');
    });

    it('extracts error message from stdClass object in extractErrorMessage', function () {
        $obj = (object) ['message' => 'Token has expired on remote server'];
        $extracted = $this->service->extractErrorMessage($obj, 'Default fallback');

        expect($extracted)->toBe('Token has expired on remote server');
    });

    it('detects empty JSON response as invalid session status in testConnection', function () {
        Http::fake([
            'http://localhost:8080/session/status' => Http::response([], 200),
        ]);

        $res = $this->service->testConnection([
            'mode' => 'automatic',
            'server_url' => 'http://localhost:8080',
        ]);

        expect($res['success'])->toBeFalse()
            ->and($res['status'])->toBe(422)
            ->and($res['message'])->toContain('tidak mengembalikan data status sesi');
    });

    it('detects empty JSON response as invalid delivery in sendTestMessage', function () {
        Http::fake([
            'http://localhost:8080/chat/send/text' => Http::response([], 200),
        ]);

        $res = $this->service->sendTestMessage('081234567890', 'Hello', [
            'mode' => 'automatic',
            'server_url' => 'http://localhost:8080',
        ]);

        expect($res['success'])->toBeFalse()
            ->and($res['status'])->toBe(422)
            ->and($res['message'])->toContain('tidak mengembalikan konfirmasi pengiriman');
    });

    it('detects status failure and unauthorized as failure in testConnection', function () {
        Http::fake([
            'http://localhost:8080/session/status' => Http::response([
                'status' => 'failure',
                'message' => 'Device rejected credentials',
            ], 200),
        ]);

        $res = $this->service->testConnection([
            'mode' => 'automatic',
            'server_url' => 'http://localhost:8080',
        ]);

        expect($res['success'])->toBeFalse()
            ->and($res['status'])->toBe(422)
            ->and($res['message'])->toContain('Device rejected credentials');
    });

    it('detects status failure and unauthorized as failure in sendTestMessage', function () {
        Http::fake([
            'http://localhost:8080/chat/send/text' => Http::response([
                'status' => 'unauthorized',
                'message' => 'Token has expired on remote server',
            ], 200),
        ]);

        $res = $this->service->sendTestMessage('081234567890', 'Hello', [
            'mode' => 'automatic',
            'server_url' => 'http://localhost:8080',
        ]);

        expect($res['success'])->toBeFalse()
            ->and($res['status'])->toBe(422)
            ->and($res['message'])->toContain('Token has expired on remote server');
    });

    it('extracts human-readable message from details key and nested error structures', function () {
        $extracted1 = $this->service->extractErrorMessage(['details' => 'Invalid session key'], 'Default');
        expect($extracted1)->toBe('Invalid session key');

        $extracted2 = $this->service->extractErrorMessage(['error' => ['details' => 'Subsystem disconnected']], 'Default');
        expect($extracted2)->toBe('Subsystem disconnected');
    });
});
