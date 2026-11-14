<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pos\StorePaymentRequest;
use App\Models\Transaction;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;

class PaymentController extends Controller
{
    public function __construct(
        protected PaymentService $paymentService
    ) {}

    /**
     * Record a new payment for the given order/transaction.
     */
    public function store(StorePaymentRequest $request, string|int $id): JsonResponse
    {
        $transaction = is_numeric($id)
            ? Transaction::findOrFail((int) $id)
            : Transaction::where('invoice_number', $id)->firstOrFail();

        $payment = $this->paymentService->recordPayment(
            $transaction,
            $request->validated(),
            $request->user()
        );

        return response()->json([
            'message' => 'Pembayaran berhasil dicatat.',
            'data' => [
                'payment' => $payment,
                'transaction' => $transaction->fresh(['customer', 'payments']),
            ],
        ], 201);
    }
}
