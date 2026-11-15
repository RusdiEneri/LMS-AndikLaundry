<?php

namespace App\Services;

use App\Models\BusinessSetting;
use App\Models\Transaction;
use App\Support\PhoneFormatter;
use Illuminate\Support\Facades\Http;

class WhatsAppNotificationService
{
    /**
     * Get active WhatsApp gateway mode ('automatic' or 'manual').
     */
    public function getGatewayMode(): string
    {
        $mode = BusinessSetting::get('whatsapp.mode');
        if (! empty($mode)) {
            return $mode;
        }

        $provider = BusinessSetting::get('whatsapp.provider', 'wuzapi');

        return $provider === 'manual_link' ? 'manual' : 'automatic';
    }

    /**
     * Check if manual mode (wa.me link) is currently active.
     */
    public function isManualMode(): bool
    {
        return $this->getGatewayMode() === 'manual';
    }

    /**
     * Check if automatic gateway mode is currently active.
     */
    public function isAutomaticMode(): bool
    {
        return ! $this->isManualMode();
    }

    /**
     * Extract a human-readable string message from varied error structures.
     */
    public function extractErrorMessage(mixed $messageOrError, string $default): string
    {
        if (is_string($messageOrError) && trim($messageOrError) !== '') {
            return $messageOrError;
        }

        if (is_object($messageOrError)) {
            $messageOrError = (array) $messageOrError;
        }

        if (is_array($messageOrError)) {
            foreach (['message', 'error', 'detail', 'details', 'data'] as $key) {
                if (isset($messageOrError[$key])) {
                    $val = $messageOrError[$key];
                    if (is_string($val) && trim($val) !== '') {
                        return $val;
                    }
                    if (is_array($val) || is_object($val)) {
                        $nested = $this->extractErrorMessage($val, '');
                        if ($nested !== '') {
                            return $nested;
                        }
                    }
                }
            }

            $encoded = json_encode($messageOrError);

            return $encoded !== false ? $encoded : $default;
        }

        return $default;
    }

    /**
     * Get full WhatsApp configuration dictionary with fallbacks.
     *
     * @return array<string, mixed>
     */
    public function getGatewayConfig(): array
    {
        $serverUrl = BusinessSetting::get('whatsapp.server_url');
        $serverUrl = (! empty($serverUrl) && is_string($serverUrl) && trim($serverUrl) !== '')
            ? $serverUrl
            : 'http://localhost:8080';

        $provider = BusinessSetting::get('whatsapp.provider');
        $provider = (! empty($provider) && is_string($provider)) ? $provider : 'wuzapi';

        $apiKey = (string) (BusinessSetting::get('whatsapp.api_key') ?? '');

        $sessionId = BusinessSetting::get('whatsapp.session_id');
        if (empty($sessionId) || ! is_string($sessionId) || trim($sessionId) === '') {
            $sessionId = BusinessSetting::get('store.phone');
            $sessionId = (! empty($sessionId) && is_string($sessionId) && trim($sessionId) !== '')
                ? $sessionId
                : '6281234567890';
        }

        $rateLimit = BusinessSetting::get('whatsapp.anti_ban_rate_limit');
        $rateLimit = (! empty($rateLimit) && is_numeric($rateLimit)) ? (int) $rateLimit : 12;

        $intakeTemplate = BusinessSetting::get('whatsapp.template_intake');
        $intakeTemplate = (! empty($intakeTemplate) && is_string($intakeTemplate) && trim($intakeTemplate) !== '')
            ? $intakeTemplate
            : $this->getIntakeDefaultTemplate();

        $readyTemplate = BusinessSetting::get('whatsapp.template_ready');
        $readyTemplate = (! empty($readyTemplate) && is_string($readyTemplate) && trim($readyTemplate) !== '')
            ? $readyTemplate
            : $this->getReadyDefaultTemplate();

        $reminderTemplate = BusinessSetting::get('whatsapp.template_reminder');
        $reminderTemplate = (! empty($reminderTemplate) && is_string($reminderTemplate) && trim($reminderTemplate) !== '')
            ? $reminderTemplate
            : $this->getReminderDefaultTemplate();

        return [
            'mode' => $this->getGatewayMode(),
            'provider' => $provider,
            'server_url' => $serverUrl,
            'api_key' => $apiKey,
            'session_id' => $sessionId,
            'anti_ban_rate_limit' => $rateLimit,
            'template_intake' => $intakeTemplate,
            'template_ready' => $readyTemplate,
            'template_reminder' => $reminderTemplate,
        ];
    }

    /**
     * Default message template for Intake confirmation.
     * Sesuai Template 1: Pesan Masuk Cucian (Intake & Struk Digital)
     */
    public function getIntakeDefaultTemplate(): string
    {
        return "*{{store_name}}*\n"
            .'[{{store_address}}]'."\n"
            .'No. WA Toko : {{store_phone}}'."\n"
            .'===================='."\n"
            .'Tanggal : {{tanggal}}'."\n"
            .'No Nota : {{invoice_number}}'."\n"
            .'Pelanggan : {{customer_name}}'."\n"
            .'Layanan Antar : {{delivery_option}} (Gratis < 3 km)'."\n"
            .'===================='."\n"
            .'Halo {{customer_name}}, cucian Anda telah kami terima dengan rincian berikut:'."\n"
            .'{{items_detail}}'
            .'===================='."\n"
            .'*TOTAL BAYAR : {{total_amount}}*'."\n"
            .'Status Bayar : {{payment_status}}'."\n"
            .'Progres Saat Ini : {{production_status}}'."\n"
            .'Perkiraan Selesai : {{estimated_date}}'."\n"
            .'===================='."\n"
            .'*Ketentuan Layanan:*'."\n"
            .'1. Pakaian luntur bukan menjadi tanggung jawab laundry.'."\n"
            .'2. Komplain dilayani maksimal 1x24 jam setelah cucian diambil.'."\n"
            .'3. Cucian yang tidak diambil lebih dari 1 bulan di luar tanggung jawab toko.'."\n"
            .'===================='."\n"
            .'Cek progres cucian & nota digital Anda di sini:'."\n"
            .'{{receipt_url}}';
    }

    /**
     * Default message template for Ready for Pickup.
     * Sesuai Template 2: Notifikasi Cucian Selesai
     */
    public function getReadyDefaultTemplate(): string
    {
        return "*{{store_name}} — CUCIAN SELESAI*\n\n"
            .'Halo Kak *{{customer_name}}*, cucian Anda dengan nomor nota *{{invoice_number}}* telah *SELESAI* diproses dan siap diambil/diantar.'."\n\n"
            .'• Lokasi Rak : {{rack_location}}'."\n"
            .'• Total Tagihan : {{total_amount}}'."\n"
            .'• Status Bayar : {{payment_status}}'."\n\n"
            .'Lihat detail nota & pembayaran QRIS di sini:'."\n"
            .'{{receipt_url}}'."\n\n"
            .'Terima kasih telah mempercayakan cucian Anda kepada {{store_name}}!';
    }

    /**
     * Default message template for Completed/Picked Up.
     * Sesuai Template 3: Konfirmasi Cucian Sudah Diambil
     */
    public function getCompletedDefaultTemplate(): string
    {
        return "*{{store_name}} — TRANSAKSI SELESAI*\n\n"
            .'Terima kasih Kak *{{customer_name}}*, cucian Anda dengan nomor nota *{{invoice_number}}* telah berstatus *SUDAH DIAMBIL* pada {{completed_date}}.'."\n\n"
            .'Simpan tautan berikut sebagai arsip nota digital Anda:'."\n"
            .'{{receipt_url}}'."\n\n"
            .'Sampai jumpa kembali di {{store_name}}!';
    }

    /**
     * Default message template for Receivable Payment Reminder.
     */
    public function getReminderDefaultTemplate(): string
    {
        return "*{{store_name}} — PENGINGAT TAGIHAN*\n\n"
            .'Halo Kak *{{customer_name}}*, nota *{{invoice_number}}* memiliki sisa tagihan sebesar *{{debt_amount}}*.'."\n\n"
            .'{{paid_info}}'
            .'Lokasi Rak: {{rack_location}}'."\n\n"
            .'Lihat detail nota digital & QRIS di sini:'."\n"
            .'{{receipt_url}}'."\n\n"
            .'Terima kasih!';
    }

    /**
     * Interpolate placeholder variables in template text.
     * Supports both {{key}}, {{ key }}, and {key} forms.
     *
     * @param  array<string, mixed>  $variables
     */
    public function interpolateTemplate(string $template, array $variables): string
    {
        return (string) preg_replace_callback(
            '/\{\{\s*([a-zA-Z0-9_]+)\s*\}\}|\{([a-zA-Z0-9_]+)\}/',
            function (array $matches) use ($variables): string {
                $key = ! empty($matches[1]) ? $matches[1] : $matches[2];

                return array_key_exists($key, $variables)
                    ? (string) $variables[$key]
                    : $matches[0];
            },
            $template
        );
    }

    /**
     * Render a sample message for a template type using sample transaction data.
     */
    public function renderSampleMessage(string $templateType, ?string $customTemplate = null): string
    {
        $storeName = BusinessSetting::get('store.name', 'Andik Laundry');
        $invoice = 'INV-TEST-001';
        $receiptUrl = route('receipts.show', ['invoice_number' => $invoice]);

        $sampleVariables = [
            'store_name' => $storeName,
            'store_address' => 'Jl. Pandegiling No. 12, Surabaya',
            'store_phone' => '081234567890',
            'customer_name' => 'Budi Santoso',
            'invoice_number' => $invoice,
            'tanggal' => date('d/m/Y - H:i'),
            'delivery_option' => 'Antar Jemput',
            'items_detail' => "1. Cuci Komplit Reguler\n   Berat : 3.00 kg (Min. 2 kg)\n   Harga : Rp 8.000/kg\n   Subtotal : Rp 24.000\n",
            'items_summary' => "• Cuci Komplit Reguler (3,00 kg)\n• Bed Cover Sedang (1,00 pcs)\n",
            'total_amount' => 'Rp 50.000',
            'paid_amount' => 'Rp 30.000',
            'paid_info' => "💵 *Telah Dibayar:* Rp 30.000\n",
            'debt_amount' => 'Rp 20.000',
            'outstanding_balance' => 'Rp 20.000',
            'payment_status' => 'BELUM LUNAS',
            'production_status' => 'DIPROSES',
            'estimated_date' => date('d/m/Y', strtotime('+3 days')),
            'status_text' => '⚠️ Sisa Tagihan: Rp 20.000',
            'rack_location' => 'Rak R-01-A',
            'staging_rack' => 'Rak R-01-A',
            'receipt_url' => $receiptUrl,
        ];

        $template = $customTemplate;

        if (empty($template) || ! is_string($template) || trim($template) === '') {
            $dbTemplate = match ($templateType) {
                'intake' => BusinessSetting::get('whatsapp.template_intake'),
                'ready' => BusinessSetting::get('whatsapp.template_ready'),
                'reminder' => BusinessSetting::get('whatsapp.template_reminder'),
                default => null,
            };

            if (! empty($dbTemplate) && is_string($dbTemplate) && trim($dbTemplate) !== '') {
                $template = $dbTemplate;
            } else {
                $template = match ($templateType) {
                    'intake' => $this->getIntakeDefaultTemplate(),
                    'ready' => $this->getReadyDefaultTemplate(),
                    'reminder' => $this->getReminderDefaultTemplate(),
                    default => null,
                };
            }
        }

        if (empty($template)) {
            return "[Uji Coba WhatsApp Gateway]\nHalo Budi Santoso, ini pesan uji coba dari {$storeName}.";
        }

        return $this->interpolateTemplate($template, $sampleVariables);
    }

    /**
     * Build customer intake notification message.
     * With detailed item breakdown and delivery option.
     */
    public function buildIntakeMessage(Transaction $transaction): string
    {
        $storeName = BusinessSetting::get('store.name', 'Andik Laundry');
        $storeAddress = BusinessSetting::get('store.address', 'Alamat Toko');
        $storePhone = BusinessSetting::get('store.phone', '08xxxxxxxxxx');
        $customerName = $transaction->customer?->name ?? 'Pelanggan';
        $invoice = $transaction->invoice_number;
        $totalFormatted = 'Rp '.number_format($transaction->total_amount, 0, ',', '.');
        $debtFormatted = 'Rp '.number_format($transaction->outstanding_balance, 0, ',', '.');
        $rackLocation = ! empty($transaction->staging_rack) ? $transaction->staging_rack : 'Rak Penerimaan';

        $paymentStatus = $transaction->outstanding_balance <= 0 ? 'LUNAS' : 'BELUM LUNAS (Sisa: '.$debtFormatted.')';

        $itemsDetail = '';
        foreach ($transaction->items as $idx => $item) {
            $idxNum = $idx + 1;
            $itemName = $item->item_name_snapshot ?? 'Layanan';
            $qty = number_format($item->actual_quantity, 2, ',', '.');
            $unit = $item->pricing_unit_snapshot ?? 'kg';
            $unitPrice = number_format((float) $item->unit_price_snapshot, 0, ',', '.');
            $subtotal = number_format((float) $item->subtotal_snapshot, 0, ',', '.');

            $itemsDetail .= "{$idxNum}. {$itemName}\n";
            if ($unit === 'kg') {
                $itemsDetail .= "   Berat : {$qty} kg (Min. 2 kg)\n";
                $itemsDetail .= "   Harga : Rp {$unitPrice}/kg\n";
            } else {
                $itemsDetail .= "   Ukuran : {$qty} {$unit}\n";
                $itemsDetail .= "   Harga : Rp {$unitPrice}/{$unit}\n";
            }
            $itemsDetail .= "   Subtotal : Rp {$subtotal}\n";
        }
        if (empty($itemsDetail)) {
            $itemsDetail = "1. Layanan Laundry\n   Berat : 0 kg\n   Harga : Rp 0/kg\n   Subtotal : Rp 0\n";
        }

        $deliveryOption = $transaction->delivery_option === 'delivery' ? 'Antar Jemput' : 'Ambil Sendiri';
        $estimatedDate = $transaction->target_completion_at?->format('d/m/Y') ?? date('d/m/Y', strtotime('+3 days'));
        $intakeDate = $transaction->intake_at?->format('d/m/Y - H:i') ?? date('d/m/Y - H:i');

        $receiptUrl = route('receipts.show', ['invoice_number' => $invoice]);

        $template = BusinessSetting::get('whatsapp.template_intake');
        if (empty($template) || ! is_string($template) || trim($template) === '') {
            $template = $this->getIntakeDefaultTemplate();
        }

        return $this->interpolateTemplate($template, [
            'store_name' => $storeName,
            'store_address' => $storeAddress,
            'store_phone' => $storePhone,
            'customer_name' => $customerName,
            'invoice_number' => $invoice,
            'delivery_option' => $deliveryOption,
            'tanggal' => $intakeDate,
            'items_detail' => $itemsDetail,
            'total_amount' => $totalFormatted,
            'payment_status' => $paymentStatus,
            'production_status' => 'DIPROSES',
            'estimated_date' => $estimatedDate,
            'rack_location' => $rackLocation,
            'receipt_url' => $receiptUrl,
        ]);
    }

    /**
     * Build notification message when laundry is ready for pickup.
     * Sesuai Template 2.
     */
    public function buildReadyForPickupMessage(Transaction $transaction): string
    {
        $storeName = BusinessSetting::get('store.name', 'Andik Laundry');
        $customerName = $transaction->customer?->name ?? 'Pelanggan';
        $invoice = $transaction->invoice_number;
        $totalFormatted = 'Rp '.number_format($transaction->total_amount, 0, ',', '.');
        $rackLocation = ! empty($transaction->staging_rack) ? $transaction->staging_rack : 'Rak Pengambilan';

        $debtFormatted = 'Rp '.number_format($transaction->outstanding_balance, 0, ',', '.');
        $paidFormatted = 'Rp '.number_format($transaction->paid_amount, 0, ',', '.');
        $paymentStatus = $transaction->outstanding_balance <= 0 ? 'LUNAS' : 'BELUM LUNAS';
        $paidInfo = $transaction->paid_amount > 0
            ? "💵 *Telah Dibayar:* {$paidFormatted}\n"
            : '';

        $itemsSummary = '';
        if ($transaction->relationLoaded('items') || $transaction->items()->exists()) {
            foreach ($transaction->items as $item) {
                $qty = number_format($item->actual_quantity, 2, ',', '.');
                $unit = $item->pricing_unit_snapshot ?? 'kg';
                $itemsSummary .= "• {$item->item_name_snapshot} ({$qty} {$unit})\n";
            }
        }
        if (empty($itemsSummary)) {
            $itemsSummary = "• Layanan Laundry Pakaian\n";
        }

        $receiptUrl = route('receipts.show', ['invoice_number' => $invoice]);

        $template = BusinessSetting::get('whatsapp.template_ready');
        if (empty($template) || ! is_string($template) || trim($template) === '') {
            $template = $this->getReadyDefaultTemplate();
        }

        return $this->interpolateTemplate($template, [
            'store_name' => $storeName,
            'customer_name' => $customerName,
            'invoice_number' => $invoice,
            'rack_location' => $rackLocation,
            'staging_rack' => $rackLocation,
            'total_amount' => $totalFormatted,
            'paid_amount' => $paidFormatted,
            'paid_info' => $paidInfo,
            'debt_amount' => $debtFormatted,
            'items_summary' => $itemsSummary,
            'payment_status' => $paymentStatus,
            'receipt_url' => $receiptUrl,
        ]);
    }

    /**
     * Build notification message when laundry is picked up.
     * Sesuai Template 3.
     */
    public function buildCompletedMessage(Transaction $transaction): string
    {
        $storeName = BusinessSetting::get('store.name', 'Andik Laundry');
        $customerName = $transaction->customer?->name ?? 'Pelanggan';
        $invoice = $transaction->invoice_number;
        $completedDate = $transaction->picked_up_at?->format('d/m/Y - H:i') ?? date('d/m/Y - H:i');

        $receiptUrl = route('receipts.show', ['invoice_number' => $invoice]);

        $template = BusinessSetting::get('whatsapp.template_completed');
        if (empty($template) || ! is_string($template) || trim($template) === '') {
            $template = $this->getCompletedDefaultTemplate();
        }

        return $this->interpolateTemplate($template, [
            'store_name' => $storeName,
            'customer_name' => $customerName,
            'invoice_number' => $invoice,
            'completed_date' => $completedDate,
            'receipt_url' => $receiptUrl,
        ]);
    }

    /**
     * Build payment reminder message for unpaid orders (Piutang).
     */
    public function buildReceivableReminderMessage(Transaction $transaction): string
    {
        $storeName = BusinessSetting::get('store.name', 'Andik Laundry');
        $customerName = $transaction->customer?->name ?? 'Pelanggan';
        $invoice = $transaction->invoice_number;
        $totalFormatted = 'Rp '.number_format($transaction->total_amount, 0, ',', '.');
        $paidFormatted = 'Rp '.number_format($transaction->paid_amount, 0, ',', '.');
        $debtFormatted = 'Rp '.number_format($transaction->outstanding_balance, 0, ',', '.');
        $rackLocation = ! empty($transaction->staging_rack) ? $transaction->staging_rack : 'Rak Pengambilan';
        $statusText = $transaction->outstanding_balance <= 0
            ? '✅ LUNAS'
            : "⚠️ Sisa Tagihan: {$debtFormatted}";

        $paidInfo = $transaction->paid_amount > 0
            ? "💵 *Telah Dibayar:* {$paidFormatted}\n"
            : '';

        $itemsSummary = '';
        if ($transaction->relationLoaded('items') || $transaction->items()->exists()) {
            foreach ($transaction->items as $item) {
                $qty = number_format($item->actual_quantity, 2, ',', '.');
                $unit = $item->pricing_unit_snapshot ?? 'kg';
                $itemsSummary .= "• {$item->item_name_snapshot} ({$qty} {$unit})\n";
            }
        }
        if (empty($itemsSummary)) {
            $itemsSummary = "• Layanan Laundry Pakaian\n";
        }

        $receiptUrl = route('receipts.show', ['invoice_number' => $invoice]);

        $template = BusinessSetting::get('whatsapp.template_reminder');
        if (empty($template) || ! is_string($template) || trim($template) === '') {
            $template = $this->getReminderDefaultTemplate();
        }

        return $this->interpolateTemplate($template, [
            'store_name' => $storeName,
            'customer_name' => $customerName,
            'invoice_number' => $invoice,
            'items_summary' => $itemsSummary,
            'rack_location' => $rackLocation,
            'staging_rack' => $rackLocation,
            'total_amount' => $totalFormatted,
            'paid_amount' => $paidFormatted,
            'paid_info' => $paidInfo,
            'debt_amount' => $debtFormatted,
            'outstanding_balance' => $debtFormatted,
            'status_text' => $statusText,
            'receipt_url' => $receiptUrl,
        ]);
    }

    /**
     * Build a manual wa.me deep-link URL.
     */
    public function buildManualWhatsAppUrl(?string $phone, string $message): string
    {
        $normalizedPhone = PhoneFormatter::normalize($phone ?? '');
        $digitsOnly = ltrim($normalizedPhone, '+');

        return 'https://wa.me/'.$digitsOnly.'?text='.urlencode($message);
    }

    /**
     * Test gateway connectivity (session/health check).
     *
     * @param  array<string, mixed>|null  $params
     * @return array<string, mixed>
     */
    public function testConnection(?array $params = null): array
    {
        $mode = $params['mode'] ?? $this->getGatewayMode();

        if ($mode === 'manual') {
            return [
                'success' => true,
                'mode' => 'manual',
                'status' => 200,
                'message' => 'Mode manual (wa.me fallback) aktif. Gateway server tidak diperlukan.',
                'data' => [
                    'mode' => 'manual',
                    'connected' => true,
                ],
            ];
        }

        if (isset($params['server_url']) && trim((string) $params['server_url']) !== '') {
            $rawUrl = trim((string) $params['server_url']);
        } elseif (array_key_exists('server_url', $params ?? []) && trim((string) ($params['server_url'] ?? '')) === '') {
            $rawUrl = '';
        } else {
            $dbUrl = BusinessSetting::get('whatsapp.server_url');
            $rawUrl = (! empty($dbUrl) && is_string($dbUrl) && trim($dbUrl) !== '')
                ? trim($dbUrl)
                : 'http://localhost:8080';
        }

        if (empty($rawUrl)) {
            return [
                'success' => false,
                'mode' => 'automatic',
                'status' => 422,
                'message' => 'URL Server WhatsApp gateway belum diatur.',
            ];
        }

        if (in_array($rawUrl, ['http://', 'https://', 'http:', 'https:'], true)) {
            return [
                'success' => false,
                'mode' => 'automatic',
                'status' => 422,
                'message' => 'URL Server WhatsApp gateway tidak valid.',
            ];
        }

        if (str_contains($rawUrl, '://') && ! str_starts_with($rawUrl, 'http://') && ! str_starts_with($rawUrl, 'https://')) {
            return [
                'success' => false,
                'mode' => 'automatic',
                'status' => 422,
                'message' => 'URL Server WhatsApp gateway tidak valid.',
            ];
        }

        $serverUrl = $rawUrl;
        if (! str_starts_with($serverUrl, 'http://') && ! str_starts_with($serverUrl, 'https://')) {
            $serverUrl = 'http://'.$serverUrl;
        }
        $serverUrl = rtrim($serverUrl, '/');

        $parsedHost = parse_url($serverUrl, PHP_URL_HOST);
        if (empty($parsedHost) || ! filter_var($serverUrl, FILTER_VALIDATE_URL)) {
            return [
                'success' => false,
                'mode' => 'automatic',
                'status' => 422,
                'message' => 'URL Server WhatsApp gateway tidak valid.',
            ];
        }

        if (isset($params['session_id']) && trim((string) $params['session_id']) !== '') {
            $sessionId = trim((string) $params['session_id']);
        } else {
            $dbSession = BusinessSetting::get('whatsapp.session_id');
            if (empty($dbSession) || ! is_string($dbSession) || trim($dbSession) === '') {
                $dbSession = BusinessSetting::get('store.phone', '6281234567890');
            }
            $sessionId = trim((string) $dbSession);
        }

        $apiKey = trim((string) ($params['api_key'] ?? BusinessSetting::get('whatsapp.api_key', '')));

        try {
            $headers = ['Accept' => 'application/json'];
            if (! empty($apiKey)) {
                $headers['Token'] = $apiKey;
                $headers['Authorization'] = 'Bearer '.$apiKey;
            }
            if (! empty($sessionId)) {
                $headers['Session'] = $sessionId;
            }

            $request = Http::timeout(5)->withHeaders($headers);

            // Attempt WuzAPI standard session status check
            $response = $request->get("{$serverUrl}/session/status");

            // If 404, fallback to root or health check
            if ($response->status() === 404) {
                $response = $request->get("{$serverUrl}/");
            }

            if ($response->successful()) {
                $rawJson = $response->json();
                if ($rawJson === null || ! is_array($rawJson)) {
                    return [
                        'success' => false,
                        'mode' => 'automatic',
                        'status' => 422,
                        'message' => 'Server merespons tetapi bukan gateway WhatsApp yang valid (respons bukan format JSON).',
                        'data' => $response->body(),
                    ];
                }

                $json = $rawJson;
                $data = isset($json['data']) && is_array($json['data'])
                    ? array_merge($json, $json['data'])
                    : $json;

                if (empty($data)) {
                    return [
                        'success' => false,
                        'mode' => 'automatic',
                        'status' => 422,
                        'message' => 'Gateway WhatsApp tidak mengembalikan data status sesi yang valid.',
                        'data' => $json,
                    ];
                }

                // Validate that response indicates an active logged-in session
                if (isset($data['success']) && ($data['success'] === false || $data['success'] === 'false' || $data['success'] === 0 || $data['success'] === '0')) {
                    return [
                        'success' => false,
                        'mode' => 'automatic',
                        'status' => 422,
                        'message' => $this->extractErrorMessage($data['message'] ?? $data['error'] ?? $data['details'] ?? $data['detail'] ?? $data['data'] ?? null, 'Gateway merespons dengan status gagal.'),
                        'data' => $data,
                    ];
                }

                if (! empty($data['error'])) {
                    return [
                        'success' => false,
                        'mode' => 'automatic',
                        'status' => 422,
                        'message' => $this->extractErrorMessage($data['error'], 'Gateway merespons dengan status error.'),
                        'data' => $data,
                    ];
                }

                if (isset($data['status']) && ($data['status'] === false || $data['status'] === 'false' || $data['status'] === 0 || $data['status'] === '0')) {
                    return [
                        'success' => false,
                        'mode' => 'automatic',
                        'status' => 422,
                        'message' => $this->extractErrorMessage($data['message'] ?? $data['error'] ?? $data['details'] ?? $data['detail'] ?? $data['data'] ?? null, 'Gateway merespons dengan status error.'),
                        'data' => $data,
                    ];
                }

                $statusVal = strtolower((string) ($data['status'] ?? $data['state'] ?? ''));
                if (in_array($statusVal, ['error', 'failed', 'fail', 'failure', 'unauthorized', 'rejected', 'invalid'], true)) {
                    return [
                        'success' => false,
                        'mode' => 'automatic',
                        'status' => 422,
                        'message' => $this->extractErrorMessage($data['message'] ?? $data['error'] ?? $data['details'] ?? $data['detail'] ?? $data['data'] ?? null, 'Gateway merespons dengan status error.'),
                        'data' => $data,
                    ];
                }

                if (array_key_exists('jid', $data) && trim((string) $data['jid']) === '') {
                    return [
                        'success' => false,
                        'mode' => 'automatic',
                        'status' => 422,
                        'message' => 'Gateway server terhubung, namun sesi WhatsApp belum aktif (disconnected/unpaired). Silakan periksa atau scan ulang QR.',
                        'data' => $data,
                    ];
                }

                if (! empty($data['qr']) && empty($data['connected'])) {
                    return [
                        'success' => false,
                        'mode' => 'automatic',
                        'status' => 422,
                        'message' => 'Gateway server terhubung, namun sesi WhatsApp belum aktif (disconnected/unpaired). Silakan periksa atau scan ulang QR.',
                        'data' => $data,
                    ];
                }

                if (in_array($statusVal, ['disconnected', 'disconnect', 'unpaired', 'unauthenticated', 'unauthorized', 'logged_out', 'logout', 'scan_qr_code', 'close', 'closed', 'offline', 'inactive', 'stopped', 'pairing', 'qrcode', 'qr'], true)) {
                    return [
                        'success' => false,
                        'mode' => 'automatic',
                        'status' => 422,
                        'message' => 'Gateway server terhubung, namun sesi WhatsApp belum aktif (disconnected/unpaired). Silakan periksa atau scan ulang QR.',
                        'data' => $data,
                    ];
                }

                $authKeys = ['connected', 'loggedIn', 'authenticated', 'is_authenticated', 'isConnected', 'is_connected', 'isLoggedIn', 'is_logged_in'];
                foreach ($authKeys as $key) {
                    if (array_key_exists($key, $data) && ! filter_var($data[$key], FILTER_VALIDATE_BOOLEAN)) {
                        return [
                            'success' => false,
                            'mode' => 'automatic',
                            'status' => 422,
                            'message' => 'Gateway server terhubung, namun sesi WhatsApp belum aktif (disconnected/unpaired). Silakan periksa atau scan ulang QR.',
                            'data' => $data,
                        ];
                    }
                }

                foreach (['code', 'status', 'statusCode'] as $codeKey) {
                    if (isset($data[$codeKey]) && is_numeric($data[$codeKey]) && (int) $data[$codeKey] >= 400) {
                        $errorCode = (int) $data[$codeKey];

                        return [
                            'success' => false,
                            'mode' => 'automatic',
                            'status' => 422,
                            'message' => $this->extractErrorMessage($data['message'] ?? $data['error'] ?? $data['details'] ?? $data['detail'] ?? $data['data'] ?? null, 'Gateway merespons dengan kode error: '.$errorCode),
                            'data' => $data,
                        ];
                    }
                }

                return [
                    'success' => true,
                    'mode' => 'automatic',
                    'status' => $response->status(),
                    'message' => 'Koneksi ke gateway WhatsApp berhasil terhubung.',
                    'data' => $data,
                ];
            }

            $rawJson = $response->json();
            $detail = null;
            if (is_array($rawJson)) {
                $detailData = isset($rawJson['data']) && is_array($rawJson['data'])
                    ? array_merge($rawJson, $rawJson['data'])
                    : $rawJson;
                $detail = $this->extractErrorMessage($detailData['message'] ?? $detailData['error'] ?? $detailData['details'] ?? $detailData['detail'] ?? $detailData['data'] ?? null, '');
            }

            $errorMessage = ! empty($detail)
                ? "Gateway WhatsApp merespons dengan status error: {$response->status()} ({$detail})"
                : "Gateway WhatsApp merespons dengan status error: {$response->status()}";

            return [
                'success' => false,
                'mode' => 'automatic',
                'status' => $response->status(),
                'message' => $errorMessage,
                'data' => $rawJson ?? $response->body(),
            ];
        } catch (\Throwable $e) {
            $msg = $e->getMessage();
            if (str_contains($msg, 'cURL error 7') || str_contains($msg, 'Failed to connect') || str_contains($msg, 'Connection refused')) {
                $friendly = "Gagal terhubung ke WhatsApp gateway ({$serverUrl}): Server belum dijalankan. Jalankan gateway dengan mengeksekusi `whatsapp-gateway.exe` di folder `gateway/`, atau gunakan Mode Manual (wa.me).";
            } else {
                $friendly = 'Gagal menghubungi server WhatsApp gateway: '.$msg;
            }

            return [
                'success' => false,
                'mode' => 'automatic',
                'status' => 500,
                'message' => $friendly,
            ];
        }
    }

    /**
     * Send test message to verify credentials and template delivery.
     *
     * @param  array<string, mixed>|null  $params
     * @return array<string, mixed>
     */
    public function sendTestMessage(string $phone, ?string $message = null, ?array $params = null): array
    {
        $normalizedPhone = PhoneFormatter::normalize($phone);
        $digitsOnly = ltrim($normalizedPhone, '+');

        $mode = $params['mode'] ?? $this->getGatewayMode();

        if (empty($digitsOnly) || ! ctype_digit($digitsOnly) || strlen($digitsOnly) < 8) {
            return [
                'success' => false,
                'mode' => $mode,
                'status' => 422,
                'message' => 'Nomor WhatsApp tujuan tidak valid. Masukkan minimal 8 digit angka.',
            ];
        }

        $storeName = BusinessSetting::get('store.name', 'Andik Laundry');
        $messageText = $message ?: "[Uji Coba WhatsApp Gateway]\n"
            ."Halo! Ini adalah pesan uji coba dari sistem {$storeName}.\n"
            .'Jika Anda menerima pesan ini, integrasi WhatsApp Gateway telah berfungsi dengan baik.';

        if ($mode === 'manual') {
            $waUrl = $this->buildManualWhatsAppUrl($normalizedPhone, $messageText);

            return [
                'success' => true,
                'mode' => 'manual',
                'status' => 200,
                'phone' => $normalizedPhone,
                'wa_url' => $waUrl,
                'message' => 'Tautan WhatsApp manual (wa.me) berhasil dibuat.',
                'data' => [
                    'phone' => $normalizedPhone,
                    'message' => $messageText,
                    'wa_url' => $waUrl,
                ],
            ];
        }

        if (isset($params['server_url']) && trim((string) $params['server_url']) !== '') {
            $rawUrl = trim((string) $params['server_url']);
        } elseif (array_key_exists('server_url', $params ?? []) && trim((string) ($params['server_url'] ?? '')) === '') {
            $rawUrl = '';
        } else {
            $dbUrl = BusinessSetting::get('whatsapp.server_url');
            $rawUrl = (! empty($dbUrl) && is_string($dbUrl) && trim($dbUrl) !== '')
                ? trim($dbUrl)
                : 'http://localhost:8080';
        }

        if (empty($rawUrl)) {
            return [
                'success' => false,
                'mode' => 'automatic',
                'status' => 422,
                'message' => 'URL Server WhatsApp gateway belum diatur.',
            ];
        }

        if (in_array($rawUrl, ['http://', 'https://', 'http:', 'https:'], true)) {
            return [
                'success' => false,
                'mode' => 'automatic',
                'status' => 422,
                'message' => 'URL Server WhatsApp gateway tidak valid.',
            ];
        }

        if (str_contains($rawUrl, '://') && ! str_starts_with($rawUrl, 'http://') && ! str_starts_with($rawUrl, 'https://')) {
            return [
                'success' => false,
                'mode' => 'automatic',
                'status' => 422,
                'message' => 'URL Server WhatsApp gateway tidak valid.',
            ];
        }

        $serverUrl = $rawUrl;
        if (! str_starts_with($serverUrl, 'http://') && ! str_starts_with($serverUrl, 'https://')) {
            $serverUrl = 'http://'.$serverUrl;
        }
        $serverUrl = rtrim($serverUrl, '/');

        $parsedHost = parse_url($serverUrl, PHP_URL_HOST);
        if (empty($parsedHost) || ! filter_var($serverUrl, FILTER_VALIDATE_URL)) {
            return [
                'success' => false,
                'mode' => 'automatic',
                'status' => 422,
                'message' => 'URL Server WhatsApp gateway tidak valid.',
            ];
        }

        if (isset($params['session_id']) && trim((string) $params['session_id']) !== '') {
            $sessionId = trim((string) $params['session_id']);
        } else {
            $dbSession = BusinessSetting::get('whatsapp.session_id');
            if (empty($dbSession) || ! is_string($dbSession) || trim($dbSession) === '') {
                $dbSession = BusinessSetting::get('store.phone', '6281234567890');
            }
            $sessionId = trim((string) $dbSession);
        }

        $apiKey = trim((string) ($params['api_key'] ?? BusinessSetting::get('whatsapp.api_key', '')));

        try {
            $headers = ['Accept' => 'application/json'];
            if (! empty($apiKey)) {
                $headers['Token'] = $apiKey;
                $headers['Authorization'] = 'Bearer '.$apiKey;
            }
            if (! empty($sessionId)) {
                $headers['Session'] = $sessionId;
            }

            $response = Http::timeout(10)->withHeaders($headers)->post("{$serverUrl}/chat/send/text", [
                'Phone' => $digitsOnly,
                'Body' => $messageText,
            ]);

            if ($response->successful()) {
                $rawJson = $response->json();

                if ($rawJson === null || ! is_array($rawJson)) {
                    return [
                        'success' => false,
                        'mode' => 'automatic',
                        'status' => 422,
                        'message' => 'Gateway merespons tetapi bukan format JSON yang valid.',
                        'data' => $response->body(),
                    ];
                }

                $json = $rawJson;
                $data = isset($json['data']) && is_array($json['data'])
                    ? array_merge($json, $json['data'])
                    : $json;

                if (empty($data)) {
                    return [
                        'success' => false,
                        'mode' => 'automatic',
                        'status' => 422,
                        'message' => 'Gateway WhatsApp tidak mengembalikan konfirmasi pengiriman pesan yang valid.',
                        'data' => $json,
                    ];
                }

                if (isset($data['success']) && ($data['success'] === false || $data['success'] === 'false' || $data['success'] === 0 || $data['success'] === '0')) {
                    return [
                        'success' => false,
                        'mode' => 'automatic',
                        'status' => 422,
                        'message' => $this->extractErrorMessage($data['message'] ?? $data['error'] ?? $data['details'] ?? $data['detail'] ?? $data['data'] ?? null, 'Gateway merespons dengan status gagal saat mengirim pesan.'),
                        'data' => $data,
                    ];
                }

                if (! empty($data['error'])) {
                    return [
                        'success' => false,
                        'mode' => 'automatic',
                        'status' => 422,
                        'message' => $this->extractErrorMessage($data['error'], 'Gateway merespons dengan status gagal saat mengirim pesan.'),
                        'data' => $data,
                    ];
                }

                if (isset($data['status']) && ($data['status'] === false || $data['status'] === 'false' || $data['status'] === 0 || $data['status'] === '0')) {
                    return [
                        'success' => false,
                        'mode' => 'automatic',
                        'status' => 422,
                        'message' => $this->extractErrorMessage($data['message'] ?? $data['error'] ?? $data['details'] ?? $data['detail'] ?? $data['data'] ?? null, 'Gateway merespons dengan status error saat mengirim pesan.'),
                        'data' => $data,
                    ];
                }

                $sendKeys = ['sent', 'delivered'];
                foreach ($sendKeys as $sKey) {
                    if (array_key_exists($sKey, $data) && ! filter_var($data[$sKey], FILTER_VALIDATE_BOOLEAN)) {
                        return [
                            'success' => false,
                            'mode' => 'automatic',
                            'status' => 422,
                            'message' => $this->extractErrorMessage($data['message'] ?? $data['error'] ?? $data['details'] ?? $data['detail'] ?? $data['data'] ?? null, 'Gateway merespons dengan status gagal saat mengirim pesan.'),
                            'data' => $data,
                        ];
                    }
                }

                $statusVal = strtolower((string) ($data['status'] ?? $data['state'] ?? ''));
                if (in_array($statusVal, ['error', 'failed', 'fail', 'failure', 'unauthorized', 'rejected', 'invalid'], true)) {
                    return [
                        'success' => false,
                        'mode' => 'automatic',
                        'status' => 422,
                        'message' => $this->extractErrorMessage($data['message'] ?? $data['error'] ?? $data['details'] ?? $data['detail'] ?? $data['data'] ?? null, 'Gateway merespons dengan status error saat mengirim pesan.'),
                        'data' => $data,
                    ];
                }

                if (array_key_exists('jid', $data) && trim((string) $data['jid']) === '') {
                    return [
                        'success' => false,
                        'mode' => 'automatic',
                        'status' => 422,
                        'message' => 'Gagal mengirim pesan: sesi WhatsApp belum aktif atau terputus (disconnected).',
                        'data' => $data,
                    ];
                }

                if (! empty($data['qr']) && empty($data['connected'])) {
                    return [
                        'success' => false,
                        'mode' => 'automatic',
                        'status' => 422,
                        'message' => 'Gagal mengirim pesan: sesi WhatsApp belum aktif atau terputus (disconnected).',
                        'data' => $data,
                    ];
                }

                if (in_array($statusVal, ['disconnected', 'disconnect', 'unpaired', 'unauthenticated', 'unauthorized', 'logged_out', 'logout', 'scan_qr_code', 'close', 'closed', 'offline', 'inactive', 'stopped', 'pairing', 'qrcode', 'qr'], true)) {
                    return [
                        'success' => false,
                        'mode' => 'automatic',
                        'status' => 422,
                        'message' => 'Gagal mengirim pesan: sesi WhatsApp belum aktif atau terputus (disconnected).',
                        'data' => $data,
                    ];
                }

                $authKeys = ['connected', 'loggedIn', 'authenticated', 'is_authenticated', 'isConnected', 'is_connected', 'isLoggedIn', 'is_logged_in'];
                foreach ($authKeys as $key) {
                    if (array_key_exists($key, $data) && ! filter_var($data[$key], FILTER_VALIDATE_BOOLEAN)) {
                        return [
                            'success' => false,
                            'mode' => 'automatic',
                            'status' => 422,
                            'message' => 'Gagal mengirim pesan: sesi WhatsApp belum aktif atau terputus (disconnected).',
                            'data' => $data,
                        ];
                    }
                }

                foreach (['code', 'status', 'statusCode'] as $codeKey) {
                    if (isset($data[$codeKey]) && is_numeric($data[$codeKey]) && (int) $data[$codeKey] >= 400) {
                        $errorCode = (int) $data[$codeKey];

                        return [
                            'success' => false,
                            'mode' => 'automatic',
                            'status' => 422,
                            'message' => $this->extractErrorMessage($data['message'] ?? $data['error'] ?? $data['details'] ?? $data['detail'] ?? $data['data'] ?? null, 'Gagal mengirim pesan WhatsApp (kode error gateway: '.$errorCode.').'),
                            'data' => $data,
                        ];
                    }
                }

                return [
                    'success' => true,
                    'mode' => 'automatic',
                    'status' => $response->status(),
                    'message' => 'Pesan uji coba WhatsApp berhasil dikirim.',
                    'data' => $data,
                ];
            }

            $rawJson = $response->json();
            $detail = null;
            if (is_array($rawJson)) {
                $detailData = isset($rawJson['data']) && is_array($rawJson['data'])
                    ? array_merge($rawJson, $rawJson['data'])
                    : $rawJson;
                $detail = $this->extractErrorMessage($detailData['message'] ?? $detailData['error'] ?? $detailData['details'] ?? $detailData['detail'] ?? $detailData['data'] ?? null, '');
            }

            $errorMessage = ! empty($detail)
                ? "Gagal mengirim pesan uji coba WhatsApp (HTTP {$response->status()}): {$detail}"
                : "Gagal mengirim pesan uji coba WhatsApp (HTTP {$response->status()}).";

            return [
                'success' => false,
                'mode' => 'automatic',
                'status' => $response->status(),
                'message' => $errorMessage,
                'data' => $rawJson ?? $response->body(),
            ];
        } catch (\Throwable $e) {
            $msg = $e->getMessage();
            if (str_contains($msg, 'cURL error 7') || str_contains($msg, 'Failed to connect') || str_contains($msg, 'Connection refused')) {
                $friendly = "Gagal terhubung ke WhatsApp gateway ({$serverUrl}): Server belum dijalankan. Jalankan gateway di folder `gateway/`, atau gunakan Mode Manual (wa.me).";
            } else {
                $friendly = 'Terjadi kesalahan saat mengirim pesan uji coba: '.$msg;
            }

            return [
                'success' => false,
                'mode' => 'automatic',
                'status' => 500,
                'message' => $friendly,
            ];
        }
    }

    /**
     * Start/refresh gateway WhatsApp connection and retrieve active pairing state or QR code.
     *
     * @param  array<string, mixed>|null  $params
     * @return array<string, mixed>
     */
    public function connectSession(?array $params = null): array
    {
        $serverUrl = $this->resolveServerUrl($params);
        $apiKey = trim((string) ($params['api_key'] ?? BusinessSetting::get('whatsapp.api_key', '')));

        try {
            $headers = ['Accept' => 'application/json'];
            if (! empty($apiKey)) {
                $headers['Token'] = $apiKey;
                $headers['Authorization'] = 'Bearer '.$apiKey;
            }

            $client = Http::timeout(10)->withHeaders($headers);
            $response = $client->post("{$serverUrl}/session/connect");

            if (! $response->successful()) {
                $response = $client->get("{$serverUrl}/session/status");
            }

            if ($response->successful()) {
                $rawJson = $response->json();
                $data = is_array($rawJson) && isset($rawJson['data']) && is_array($rawJson['data'])
                    ? $rawJson['data']
                    : (is_array($rawJson) ? $rawJson : []);

                return [
                    'success' => true,
                    'status' => $response->status(),
                    'message' => $rawJson['message'] ?? 'Sesi WhatsApp aktif.',
                    'data' => $data,
                ];
            }

            return [
                'success' => false,
                'status' => $response->status(),
                'message' => 'Gagal menghubungkan sesi ke gateway WhatsApp (HTTP '.$response->status().').',
                'data' => $response->json() ?? $response->body(),
            ];
        } catch (\Throwable $e) {
            $msg = $e->getMessage();
            if (str_contains($msg, 'cURL error 7') || str_contains($msg, 'Failed to connect') || str_contains($msg, 'Connection refused')) {
                $friendly = "Gagal terhubung ke WhatsApp gateway ({$serverUrl}): Server belum dijalankan. Jalankan gateway di folder `gateway/`.";
            } else {
                $friendly = 'Gagal menghubungi server WhatsApp gateway: '.$msg;
            }

            return [
                'success' => false,
                'status' => 500,
                'message' => $friendly,
            ];
        }
    }

    /**
     * Request WhatsApp phone pairing code (OTP) for code-based device linking.
     *
     * @param  array<string, mixed>|null  $params
     * @return array<string, mixed>
     */
    public function requestPairingCode(string $phone, ?array $params = null): array
    {
        $normalizedPhone = PhoneFormatter::normalize($phone);
        $cleanPhone = ltrim($normalizedPhone, '+');

        if (empty($cleanPhone) || ! ctype_digit($cleanPhone) || strlen($cleanPhone) < 8) {
            return [
                'success' => false,
                'status' => 422,
                'message' => 'Nomor WhatsApp tidak valid. Masukkan nomor HP aktif (contoh: 081234567890 / 6281234567890).',
            ];
        }

        $serverUrl = $this->resolveServerUrl($params);
        $apiKey = trim((string) ($params['api_key'] ?? BusinessSetting::get('whatsapp.api_key', '')));

        try {
            $headers = ['Accept' => 'application/json'];
            if (! empty($apiKey)) {
                $headers['Token'] = $apiKey;
                $headers['Authorization'] = 'Bearer '.$apiKey;
            }

            $client = Http::timeout(15)->withHeaders($headers);
            $response = $client->post("{$serverUrl}/session/pair-code", [
                'phone' => $cleanPhone,
            ]);

            $rawJson = $response->json();
            if ($response->successful()) {
                $data = is_array($rawJson) && isset($rawJson['data']) && is_array($rawJson['data'])
                    ? $rawJson['data']
                    : (is_array($rawJson) ? $rawJson : []);

                return [
                    'success' => true,
                    'status' => 200,
                    'message' => $rawJson['message'] ?? 'Kode pairing WhatsApp berhasil dibuat.',
                    'data' => $data,
                ];
            }

            $errorMsg = is_array($rawJson) && ! empty($rawJson['message'])
                ? $rawJson['message']
                : 'Gagal membuat kode pairing dari gateway (HTTP '.$response->status().').';

            return [
                'success' => false,
                'status' => $response->status(),
                'message' => $errorMsg,
                'data' => $rawJson,
            ];
        } catch (\Throwable $e) {
            $msg = $e->getMessage();
            if (str_contains($msg, 'cURL error 7') || str_contains($msg, 'Failed to connect') || str_contains($msg, 'Connection refused')) {
                $friendly = "Gagal terhubung ke WhatsApp gateway ({$serverUrl}): Server belum dijalankan. Jalankan gateway di folder `gateway/`.";
            } else {
                $friendly = 'Gagal menghubungi server WhatsApp gateway: '.$msg;
            }

            return [
                'success' => false,
                'status' => 500,
                'message' => $friendly,
            ];
        }
    }

    /**
     * Disconnect and logout WhatsApp session.
     *
     * @param  array<string, mixed>|null  $params
     * @return array<string, mixed>
     */
    public function disconnectSession(?array $params = null): array
    {
        $serverUrl = $this->resolveServerUrl($params);
        $apiKey = trim((string) ($params['api_key'] ?? BusinessSetting::get('whatsapp.api_key', '')));

        try {
            $headers = ['Accept' => 'application/json'];
            if (! empty($apiKey)) {
                $headers['Token'] = $apiKey;
                $headers['Authorization'] = 'Bearer '.$apiKey;
            }

            $client = Http::timeout(10)->withHeaders($headers);
            $response = $client->post("{$serverUrl}/session/logout");

            if ($response->successful()) {
                $rawJson = $response->json();

                return [
                    'success' => true,
                    'status' => 200,
                    'message' => $rawJson['message'] ?? 'Sesi WhatsApp berhasil diputuskan (logout).',
                ];
            }

            return [
                'success' => false,
                'status' => $response->status(),
                'message' => 'Gagal memutuskan sesi WhatsApp di gateway.',
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'status' => 500,
                'message' => 'Gagal menghubungi server WhatsApp gateway: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Resolve server URL with fallback.
     *
     * @param  array<string, mixed>|null  $params
     */
    protected function resolveServerUrl(?array $params = null): string
    {
        if (isset($params['server_url']) && trim((string) $params['server_url']) !== '') {
            $rawUrl = trim((string) $params['server_url']);
        } else {
            $dbUrl = BusinessSetting::get('whatsapp.server_url');
            if (empty($dbUrl) || ! is_string($dbUrl) || trim($dbUrl) === '') {
                $dbUrl = 'http://localhost:8080';
            }
            $rawUrl = trim((string) $dbUrl);
        }

        if (! str_starts_with($rawUrl, 'http://') && ! str_starts_with($rawUrl, 'https://')) {
            $rawUrl = 'http://'.$rawUrl;
        }

        return rtrim($rawUrl, '/');
    }
}
