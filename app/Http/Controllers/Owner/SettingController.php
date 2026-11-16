<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\UpdateSettingsRequest;
use App\Models\AuditLog;
use App\Models\BusinessSetting;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Services\WhatsAppNotificationService;
use App\Support\PhoneFormatter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SettingController extends Controller
{
    public function __construct(
        protected WhatsAppNotificationService $waService
    ) {}

    /**
     * Display the business settings index page.
     */
    public function index(Request $request): Response
    {
        $allSettings = BusinessSetting::all();

        // Ensure all canonical groups are initialized
        $groups = ['store', 'operational', 'financial', 'printer', 'whatsapp'];

        $groupedSettings = [];
        foreach ($groups as $group) {
            $groupedSettings[$group] = [];
        }

        foreach ($allSettings as $setting) {
            $group = $setting->group ?? 'general';
            if (! isset($groupedSettings[$group])) {
                $groupedSettings[$group] = [];
            }

            $groupedSettings[$group][$setting->key] = BusinessSetting::castValue(
                $setting->value,
                $setting->type
            );
        }

        // WhatsApp defaults guarantee fallback values in UI if not yet persisted in database
        $defaultWhatsApp = [
            'whatsapp.mode' => 'automatic',
            'whatsapp.provider' => 'wuzapi',
            'whatsapp.server_url' => 'http://localhost:8080',
            'whatsapp.api_key' => '',
            'whatsapp.session_id' => BusinessSetting::get('store.phone', '6281234567890'),
            'whatsapp.anti_ban_rate_limit' => 12,
            'whatsapp.template_intake' => $this->waService->getIntakeDefaultTemplate(),
            'whatsapp.template_ready' => $this->waService->getReadyDefaultTemplate(),
            'whatsapp.template_reminder' => $this->waService->getReminderDefaultTemplate(),
        ];

        foreach ($defaultWhatsApp as $key => $defaultVal) {
            if (! array_key_exists($key, $groupedSettings['whatsapp'])) {
                $groupedSettings['whatsapp'][$key] = $defaultVal;
            }
        }

        return Inertia::render('Owner/Settings/Index', [
            'settings' => $groupedSettings,
            'services' => Service::with(['category', 'tiers'])->orderBy('category_id')->orderBy('id')->get(),
            'categories' => ServiceCategory::where('is_active', true)->orderBy('id')->get(),
        ]);
    }

    /**
     * Update business settings in bulk and record immutable audit logs.
     */
    public function update(UpdateSettingsRequest $request): RedirectResponse
    {
        $items = $request->validated('settings');

        foreach ($items as $item) {
            $key = $item['key'];
            $value = $item['value'] ?? null;

            // Optional normalization for store phone
            if ($key === 'store.phone' && $value !== null && class_exists(PhoneFormatter::class)) {
                $value = PhoneFormatter::normalizeToE164((string) $value);
            }

            $existing = BusinessSetting::where('key', $key)->first();
            $oldValue = $existing?->value;

            // Resolve group dynamically if new setting is submitted
            $group = $existing?->group;
            if (! $group) {
                $prefix = str_contains($key, '.') ? explode('.', $key)[0] : 'general';
                $group = in_array($prefix, ['store', 'operational', 'financial', 'printer', 'whatsapp'])
                    ? $prefix
                    : 'general';
            }

            $type = $existing?->type ?? (is_bool($value) ? 'boolean' : (is_numeric($value) ? 'decimal' : 'string'));

            $updatedSetting = BusinessSetting::set($key, $value, $type, $group);

            // Immutable compliance audit trail
            AuditLog::record(
                action: 'update',
                auditable: $updatedSetting,
                oldValues: ['value' => $oldValue],
                newValues: ['value' => $updatedSetting->value],
                userId: $request->user()?->id
            );
        }

        return redirect()->back()->with('success', 'Pengaturan bisnis berhasil diperbarui.');
    }

    /**
     * Test gateway connectivity (session/health check).
     */
    public function testWhatsAppConnection(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'server_url' => ['nullable', 'string', 'max:500'],
            'api_key' => ['nullable', 'string', 'max:500'],
            'session_id' => ['nullable', 'string', 'max:100'],
            'mode' => ['nullable', 'string', 'in:automatic,manual,gateway'],
        ]);

        if ($request->has('server_url') && ($validated['server_url'] === null || trim((string) $validated['server_url']) === '')) {
            $validated['server_url'] = '';
        }

        $result = $this->waService->testConnection($validated);

        $httpCode = $result['success'] ? 200 : 422;

        return response()->json($result, $httpCode);
    }

    /**
     * Send test message to verify credentials and template delivery.
     */
    public function sendWhatsAppTestMessage(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'phone' => ['required', 'string'],
            'message' => ['nullable', 'string', 'max:4000'],
            'template' => ['nullable', 'string', 'max:4000'],
            'server_url' => ['nullable', 'string'],
            'api_key' => ['nullable', 'string'],
            'session_id' => ['nullable', 'string'],
            'mode' => ['nullable', 'string', 'in:automatic,manual,gateway'],
            'template_type' => ['nullable', 'string', 'in:intake,ready,reminder,custom'],
        ]);

        $message = $validated['message'] ?? null;
        if (! $message && ! empty($validated['template_type']) && $validated['template_type'] !== 'custom') {
            $message = $this->waService->renderSampleMessage(
                $validated['template_type'],
                $request->input('template')
            );
        }

        $params = $request->only(['server_url', 'api_key', 'session_id', 'mode']);
        if ($request->has('server_url') && ($params['server_url'] === null || trim((string) $params['server_url']) === '')) {
            $params['server_url'] = '';
        }

        $result = $this->waService->sendTestMessage(
            $validated['phone'],
            $message,
            $params
        );

        $httpCode = $result['success'] ? 200 : 422;

        return response()->json($result, $httpCode);
    }

    /**
     * Connect or pair WhatsApp session via QR code or Pairing OTP code.
     */
    public function connectWhatsAppSession(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'method' => ['required', 'string', 'in:qr,otp,pairing_code'],
            'phone' => ['nullable', 'string', 'max:50'],
            'server_url' => ['nullable', 'string', 'max:500'],
            'api_key' => ['nullable', 'string', 'max:500'],
        ]);

        $params = [
            'server_url' => $validated['server_url'] ?? null,
            'api_key' => $validated['api_key'] ?? null,
        ];

        if ($validated['method'] === 'otp' || $validated['method'] === 'pairing_code') {
            $phone = $validated['phone'] ?? BusinessSetting::get('store.phone', '6281234567890');
            $result = $this->waService->requestPairingCode($phone, $params);
        } else {
            $result = $this->waService->connectSession($params);
        }

        $httpCode = $result['success'] ? 200 : ($result['status'] ?? 422);

        return response()->json($result, $httpCode);
    }

    /**
     * Disconnect and logout WhatsApp session.
     */
    public function disconnectWhatsAppSession(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'server_url' => ['nullable', 'string', 'max:500'],
            'api_key' => ['nullable', 'string', 'max:500'],
        ]);

        $result = $this->waService->disconnectSession($validated);
        $httpCode = $result['success'] ? 200 : ($result['status'] ?? 422);

        return response()->json($result, $httpCode);
    }
}
