<?php

namespace App\Http\Controllers\Production;

use App\Enums\PaymentStatus;
use App\Enums\ProductionStatus;
use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Services\ProductionStatusService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StationController extends Controller
{
    public function __construct(
        protected ProductionStatusService $productionStatusService
    ) {}

    /**
     * Display simplified laundry production progress tracking page.
     */
    public function index(Request $request): Response
    {
        $ordersQuery = Transaction::with(['customer', 'items'])
            ->where(function ($query): void {
                $query->whereNotIn('production_status', [
                    ProductionStatus::COMPLETED->value,
                    ProductionStatus::SUDAH_DIAMBIL->value,
                    ProductionStatus::CANCELLED->value,
                ])->orWhere(function ($sub): void {
                    $sub->whereIn('production_status', [
                        ProductionStatus::COMPLETED->value,
                        ProductionStatus::SUDAH_DIAMBIL->value,
                    ])->whereDate('updated_at', today());
                });
            })
            ->orderByDesc('intake_at')
            ->get();

        $orders = $ordersQuery->map(function (Transaction $order): array {
            $status = $order->production_status instanceof ProductionStatus
                ? $order->production_status
                : ProductionStatus::coerce((string) $order->production_status);

            $stage = match ($status) {
                ProductionStatus::SELESAI, ProductionStatus::READY_FOR_PICKUP => 'selesai',
                ProductionStatus::SUDAH_DIAMBIL, ProductionStatus::COMPLETED => 'sudah_diambil',
                default => 'diproses',
            };

            $firstItem = $order->items->first();
            $extraCount = max(0, $order->items->count() - 1);
            $qty = (float) ($firstItem?->actual_quantity ?? 0);
            $unit = $firstItem?->pricing_unit_snapshot ?? 'kg';
            $qtyText = rtrim(rtrim(number_format($qty, 2, ',', '.'), '0'), ',').' '.$unit;
            $baseName = $firstItem?->item_name_snapshot ?? 'Layanan Laundry';
            $itemsSummary = "{$baseName} ({$qtyText})".($extraCount > 0 ? " + {$extraCount} item" : '');

            $payStatus = $order->payment_status instanceof PaymentStatus
                ? $order->payment_status->value
                : (string) $order->payment_status;

            return [
                'id' => $order->id,
                'invoice_number' => $order->invoice_number,
                'customer_name' => $order->customer?->name ?? 'Pelanggan Walk-In',
                'customer_phone' => $order->customer?->phone,
                'items_summary' => $itemsSummary,
                'stage' => $stage,
                'status_label' => match ($stage) {
                    'selesai' => 'Siap Ambil',
                    'sudah_diambil' => 'Sudah Diambil',
                    default => 'Diproses',
                },
                'delivery_option' => $order->delivery_option ?? 'pickup',
                'payment_status' => $payStatus,
                'outstanding_balance' => (float) $order->outstanding_balance,
                'can_release' => $this->productionStatusService->canRelease($order),
                'intake_label' => $order->intake_at?->format('d M · H:i') ?? '-',
                'target_label' => $order->target_completion_at?->format('d M · H:i'),
                'notes' => $order->notes,
            ];
        })->values();

        return Inertia::render('Production/Station', [
            'orders' => $orders,
            'summary' => [
                'processing_count' => $orders->where('stage', 'diproses')->count(),
                'ready_count' => $orders->where('stage', 'selesai')->count(),
                'picked_up_today_count' => $orders->where('stage', 'sudah_diambil')->count(),
            ],
        ]);
    }
}
