<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\BusinessSetting;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReceiptController extends Controller
{
    /**
     * Display public digital receipt and live status tracking page.
     * GET /receipts/{invoice_number} or GET /receipt/{orderNumber}
     */
    public function show(?string $invoice_number = null, ?Request $request = null): Response|JsonResponse
    {
        $request ??= request();
        $targetInvoice = $invoice_number ?: (string) ($request->route('orderNumber') ?? $request->route('invoice_number') ?? $request->query('q') ?? '');

        $storeInfo = [
            'name' => BusinessSetting::get('store.name', 'Andik Laundry'),
            'phone' => BusinessSetting::get('store.phone', '6281234567890'),
            'address' => BusinessSetting::get('store.address', 'Hendrosari, RT.06/RW.03, Hendrosari, Kec. Menganti, Kabupaten Gresik, Jawa Timur 61174'),
            'default_min_weight' => (float) BusinessSetting::get('operational.default_min_weight', 2.0),
            'paper_width' => BusinessSetting::get('printer.paper_width', '58mm'),
            'receipt_footer' => BusinessSetting::get('store.receipt_footer', 'Terima kasih telah mencuci di Andik Laundry. Bersih, Wangi, Rapi!'),
        ];

        if (trim($targetInvoice) === '') {
            if ($request->wantsJson()) {
                return response()->json(['message' => 'Silakan masukkan nomor nota.'], 400);
            }

            return Inertia::render('Receipt/Show', [
                'order' => null,
                'store_info' => $storeInfo,
            ]);
        }

        // Search by invoice number or phone number
        $transaction = Transaction::where('invoice_number', $targetInvoice)
            ->orWhereHas('customer', function ($query) use ($targetInvoice) {
                $normalizedPhone = preg_replace('/[^0-9]/', '', $targetInvoice);
                if (strlen($normalizedPhone) >= 8) {
                    $query->where('phone_normalized', 'like', '%'.$normalizedPhone)
                        ->orWhere('phone', 'like', '%'.$targetInvoice);
                }
            })
            ->with([
                'items.service',
                'items.serviceTier',
                'customer',
                'cashier',
                'payments',
                'statusHistories.user',
            ])
            ->first();

        if (! $transaction) {
            if ($request->wantsJson()) {
                return response()->json(['message' => 'Nota transaksi tidak ditemukan.'], 404);
            }
            abort(404, 'Nota transaksi tidak ditemukan.');
        }

        $minWeight = (float) BusinessSetting::get('operational.default_min_weight', 2.0);

        $orderData = [
            'id' => $transaction->id,
            'invoice_number' => $transaction->invoice_number,
            'production_status' => is_string($transaction->production_status)
                ? $transaction->production_status
                : ($transaction->production_status?->value ?? 'received'),
            'production_status_label' => method_exists($transaction->production_status, 'label')
                ? $transaction->production_status->label()
                : (string) $transaction->production_status,
            'payment_status' => is_string($transaction->payment_status)
                ? $transaction->payment_status
                : ($transaction->payment_status?->value ?? 'unpaid'),
            'payment_status_label' => method_exists($transaction->payment_status, 'label')
                ? $transaction->payment_status->label()
                : (string) $transaction->payment_status,
            'intake_at' => $transaction->intake_at?->toISOString() ?? (string) $transaction->created_at,
            'target_completion_at' => $transaction->target_completion_at?->toISOString(),
            'actual_completion_at' => $transaction->actual_completion_at?->toISOString(),
            'picked_up_at' => $transaction->picked_up_at?->toISOString(),
            'subtotal_amount' => (float) $transaction->subtotal_amount,
            'discount_amount' => (float) $transaction->discount_amount,
            'surcharge_amount' => (float) $transaction->surcharge_amount,
            'tax_amount' => (float) $transaction->tax_amount,
            'total_amount' => (float) $transaction->total_amount,
            'paid_amount' => (float) $transaction->paid_amount,
            'change_amount' => (float) $transaction->change_amount,
            'outstanding_balance' => (float) $transaction->outstanding_balance,
            'staging_rack' => $transaction->staging_rack,
            'notes' => $transaction->notes,
            'delivery_option' => $transaction->delivery_option,
            'customer' => [
                'id' => $transaction->customer?->id,
                'name' => $transaction->customer?->name ?? 'Pelanggan Umum',
                'phone' => $transaction->customer?->phone ?? '',
                'phone_normalized' => $transaction->customer?->phone_normalized ?? '',
                'address' => $transaction->customer?->address ?? '',
            ],
            'cashier' => [
                'id' => $transaction->cashier?->id,
                'name' => $transaction->cashier?->name ?? 'Kasir',
            ],
            'items' => $transaction->items->map(fn ($item) => [
                'id' => $item->id,
                'item_name' => $item->item_name_snapshot,
                'pricing_unit' => $item->pricing_unit_snapshot,
                'unit_price' => (float) $item->unit_price_snapshot,
                'actual_quantity' => (float) $item->actual_quantity,
                'billed_quantity' => (float) $item->billed_quantity,
                'discount_amount' => (float) $item->discount_amount_snapshot,
                'subtotal' => (float) $item->subtotal_snapshot,
                'perfume_variant' => $item->perfume_variant,
                'item_condition_notes' => $item->item_condition_notes,
                'special_instructions' => $item->special_instructions,
            ])->values()->all(),
            'payments' => $transaction->payments->map(fn ($payment) => [
                'id' => $payment->id,
                'payment_number' => $payment->payment_number,
                'payment_method' => is_string($payment->payment_method)
                    ? $payment->payment_method
                    : ($payment->payment_method?->value ?? 'cash'),
                'amount' => (float) $payment->amount,
                'tender_amount' => (float) $payment->tender_amount,
                'change_amount' => (float) $payment->change_amount,
                'payment_status' => $payment->payment_status,
                'paid_at' => $payment->paid_at?->toISOString(),
            ])->values()->all(),
            'status_history' => $transaction->statusHistories->map(fn ($history) => [
                'id' => $history->id,
                'from_status' => is_string($history->from_status) ? $history->from_status : ($history->from_status?->value ?? null),
                'to_status' => is_string($history->to_status) ? $history->to_status : ($history->to_status?->value ?? $history->to_status),
                'transition_notes' => $history->transition_notes,
                'created_at' => $history->created_at?->toISOString(),
                'user_name' => $history->user?->name ?? 'Sistem',
            ])->values()->all(),
        ];

        $storeInfo = [
            'name' => BusinessSetting::get('store.name', 'Andik Laundry'),
            'phone' => BusinessSetting::get('store.phone', '6281234567890'),
            'address' => BusinessSetting::get('store.address', 'Hendrosari, RT.06/RW.03, Hendrosari, Kec. Menganti, Kabupaten Gresik, Jawa Timur 61174'),
            'default_min_weight' => $minWeight,
            'paper_width' => BusinessSetting::get('printer.paper_width', '58mm'),
            'receipt_footer' => BusinessSetting::get('store.receipt_footer', 'Terima kasih telah mencuci di Andik Laundry. Bersih, Wangi, Rapi!'),
        ];

        if ($request->wantsJson()) {
            return response()->json([
                'order' => $orderData,
                'store_info' => $storeInfo,
            ]);
        }

        return Inertia::render('Receipt/Show', [
            'order' => $orderData,
            'store_info' => $storeInfo,
        ]);
    }
}
