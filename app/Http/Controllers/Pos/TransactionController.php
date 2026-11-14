<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pos\StoreTransactionRequest;
use App\Models\Transaction;
use App\Services\TransactionService;
use Illuminate\Http\JsonResponse;

class TransactionController extends Controller
{
    /**
     * Create a new POS transaction order with price snapshots.
     * POST /api/pos/transactions
     */
    public function store(StoreTransactionRequest $request, TransactionService $transactionService): JsonResponse
    {
        $transaction = $transactionService->createTransaction(
            $request->validated(),
            $request->user()
        );

        return response()->json([
            'message' => 'Transaksi berhasil dibuat.',
            'transaction' => $transaction->load(['items.service', 'items.serviceTier', 'customer', 'cashier']),
        ], 201);
    }

    /**
     * Retrieve a transaction by ID or invoice number.
     * GET /api/pos/transactions/{id}
     */
    public function show(string|int $id): JsonResponse
    {
        $query = Transaction::with(['items.service', 'items.serviceTier', 'customer', 'cashier']);

        $transaction = is_numeric($id)
            ? $query->find($id)
            : $query->where('invoice_number', $id)->first();

        if (!$transaction) {
            return response()->json([
                'message' => 'Transaksi tidak ditemukan.',
            ], 404);
        }

        return response()->json($transaction);
    }
}
