<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Services\ProductionStatusService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class ProductionStatusController extends Controller
{
    public function __construct(
        protected ProductionStatusService $productionStatusService
    ) {}

    /**
     * Update the production status of an order/transaction.
     */
    public function updateStatus(Request $request, string|int $id): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['nullable', 'string'],
            'to_status' => ['nullable', 'string'],
            'notes' => ['nullable', 'string', 'max:500'],
            'staging_rack' => ['nullable', 'string', 'max:50'],
            'metadata' => ['nullable', 'array'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $targetStatus = $validated['status'] ?? $validated['to_status'] ?? null;
        if (! $targetStatus) {
            return response()->json([
                'message' => 'Target status is required.',
            ], 422);
        }

        $transaction = is_numeric($id)
            ? Transaction::findOrFail((int) $id)
            : Transaction::where('invoice_number', $id)->firstOrFail();

        $metadata = $validated['metadata'] ?? [];
        if (isset($metadata['staging_rack']) && is_string($metadata['staging_rack'])) {
            $metadata['staging_rack'] = trim($metadata['staging_rack']);
        }
        if (isset($metadata['reason']) && is_string($metadata['reason'])) {
            $metadata['reason'] = trim($metadata['reason']);
        }

        if ($request->has('staging_rack')) {
            $stagingRack = $request->input('staging_rack');
            $metadata['staging_rack'] = is_string($stagingRack) ? trim($stagingRack) : $stagingRack;
        }
        if ($request->has('reason')) {
            $reason = $request->input('reason');
            $metadata['reason'] = is_string($reason) ? trim($reason) : $reason;
        }

        $notes = $validated['notes'] ?? null;
        if (is_string($notes)) {
            $trimmedNotes = trim($notes);
            $notes = $trimmedNotes !== '' ? $trimmedNotes : null;
        }

        try {
            $updated = $this->productionStatusService->transition(
                $transaction,
                $targetStatus,
                $request->user(),
                $notes,
                $metadata
            );

            return response()->json([
                'message' => 'Status produksi berhasil diperbarui.',
                'data' => [
                    'transaction' => $updated->fresh(['statusHistories.user']),
                ],
            ]);
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
