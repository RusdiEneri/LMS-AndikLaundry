<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Services\CustomerReceivablesService;
use App\Services\WhatsAppNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReceivableController extends Controller
{
    public function __construct(
        protected CustomerReceivablesService $receivablesService,
        protected WhatsAppNotificationService $waService
    ) {}

    /**
     * Display the Customer Receivables (Buku Piutang) page.
     */
    public function index(Request $request): Response
    {
        $summary = $this->receivablesService->getReceivablesSummary();

        return Inertia::render('Owner/Receivables/Index', [
            'summary' => $summary,
        ]);
    }

    /**
     * Get pre-composed WhatsApp reminder URL for a specific unpaid transaction.
     */
    public function getReminderUrl(Request $request, string|int $id): JsonResponse
    {
        $transaction = is_numeric($id)
            ? Transaction::with('customer')->findOrFail((int) $id)
            : Transaction::with('customer')->where('invoice_number', $id)->firstOrFail();

        $message = $this->waService->buildReceivableReminderMessage($transaction);
        $waUrl = $this->waService->buildManualWhatsAppUrl($transaction->customer?->phone, $message);

        return response()->json([
            'status' => 'success',
            'data' => [
                'invoice_number' => $transaction->invoice_number,
                'customer_name' => $transaction->customer?->name,
                'customer_phone' => $transaction->customer?->phone,
                'message' => $message,
                'wa_url' => $waUrl,
            ],
        ]);
    }
}
