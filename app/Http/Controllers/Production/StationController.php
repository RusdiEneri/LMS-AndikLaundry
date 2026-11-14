<?php

namespace App\Http\Controllers\Production;

use App\Enums\ProductionStatus;
use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StationController extends Controller
{
    /**
     * Display the Production Operator workstation staging layout.
     */
    public function index(Request $request): Response
    {
        // Query active orders from database
        $activeOrders = Transaction::with(['customer', 'items.service'])
            ->whereNotIn('production_status', [
                ProductionStatus::COMPLETED->value,
                ProductionStatus::CANCELLED->value,
            ])
            ->orderBy('intake_at')
            ->get();

        $washingQueue = [];
        $dryingQueue = [];
        $ironingPackingQueue = [];

        foreach ($activeOrders as $order) {
            $customerName = $order->customer?->name ?? 'Pelanggan Walk-In';
            $firstItem = $order->items->first();
            $serviceName = $firstItem?->item_name_snapshot ?? 'Cuci Reguler';

            $totalQty = $order->items->sum('actual_quantity');
            $unit = $firstItem?->pricing_unit_snapshot ?? 'kg';
            $weightOrQty = number_format($totalQty, 2, ',', '.') . ' ' . $unit;

            $perfume = 'Standar';
            if (! empty($order->notes) && str_contains(strtolower($order->notes), 'parfum:')) {
                preg_match('/parfum:\s*([^,;]+)/i', $order->notes, $matches);
                if (isset($matches[1])) {
                    $perfume = trim($matches[1]);
                }
            }

            $tier = str_contains(strtolower($serviceName), 'kilat') ? 'Kilat' : (str_contains(strtolower($serviceName), 'express') ? 'Express' : 'Reguler');

            $orderData = [
                'order_code' => $order->invoice_number,
                'customer_name' => $customerName,
                'service' => $serviceName,
                'weight_or_qty' => $weightOrQty,
                'perfume' => $perfume,
                'tier' => $tier,
                'notes' => $order->notes,
                'staging_rack' => $order->staging_rack,
            ];

            $statusVal = $order->production_status instanceof ProductionStatus
                ? $order->production_status->value
                : (string) $order->production_status;

            if (in_array($statusVal, ['received', 'waiting', 'sorting', 'washing'], true)) {
                $orderData['next_action'] = $statusVal === 'washing' ? 'PINDAH PENGERING' : 'MULAI CUCI';
                $washingQueue[] = $orderData;
            } elseif ($statusVal === 'drying') {
                $orderData['machine'] = 'Mesin Dryer #01';
                $orderData['remaining_minutes'] = 15;
                $orderData['next_action'] = 'PINDAH SETRIKA';
                $dryingQueue[] = $orderData;
            } elseif (in_array($statusVal, ['ironing', 'qc', 'packing', 'ready_for_pickup'], true)) {
                $orderData['rack_target'] = $order->staging_rack ?? 'Rak Siap Ambil';
                $orderData['next_action'] = $statusVal === 'ready_for_pickup' ? 'SIAP DISERAHKAN' : 'SIAP AMBIL / RAK';
                $ironingPackingQueue[] = $orderData;
            }
        }

        // If no active orders exist yet in database, provide fallback sample items
        if (empty($washingQueue) && empty($dryingQueue) && empty($ironingPackingQueue)) {
            $washingQueue = [
                [
                    'order_code' => 'ORD-1042',
                    'customer_name' => 'Budi S.',
                    'service' => 'Cuci Komplit',
                    'weight_or_qty' => '4.50 kg',
                    'perfume' => 'Lavender',
                    'tier' => 'Reguler',
                    'notes' => null,
                    'next_action' => 'MULAI CUCI',
                ],
                [
                    'order_code' => 'ORD-1043',
                    'customer_name' => 'Maya K.',
                    'service' => 'Satuan (Bedcover King)',
                    'weight_or_qty' => '1 pcs',
                    'perfume' => 'Sakura',
                    'tier' => 'Kilat',
                    'notes' => 'Noda Kerah (Spotting)',
                    'next_action' => 'MULAI CUCI',
                ],
            ];
            $dryingQueue = [
                [
                    'order_code' => 'ORD-1040',
                    'customer_name' => 'Dewi R.',
                    'service' => 'Cuci Kering',
                    'weight_or_qty' => '6.20 kg',
                    'machine' => 'Mesin Dryer #02',
                    'remaining_minutes' => 24,
                    'next_action' => 'PINDAH SETRIKA',
                ],
            ];
            $ironingPackingQueue = [
                [
                    'order_code' => 'ORD-1038',
                    'customer_name' => 'Ahmad F.',
                    'service' => 'Setrika Uap',
                    'weight_or_qty' => '3.80 kg',
                    'perfume' => 'Sakura',
                    'rack_target' => 'Rak A-04',
                    'next_action' => 'SIAP AMBIL / RAK',
                ],
            ];
        }

        return Inertia::render('Production/Station', [
            'workstation_queues' => [
                'washing_queue' => $washingQueue,
                'drying_queue' => $dryingQueue,
                'ironing_packing_queue' => $ironingPackingQueue,
            ],
            'machines' => [
                'washers' => [
                    ['id' => 'W-01', 'name' => 'Mesin Cuci #01', 'status' => 'running', 'status_label' => 'Berjalan', 'remaining_minutes' => 18, 'load_kg' => 7.0],
                    ['id' => 'W-02', 'name' => 'Mesin Cuci #02', 'status' => 'idle', 'status_label' => 'Standby / Kosong', 'remaining_minutes' => 0, 'load_kg' => 0.0],
                    ['id' => 'W-03', 'name' => 'Mesin Cuci #03', 'status' => 'running', 'status_label' => 'Berjalan', 'remaining_minutes' => 35, 'load_kg' => 10.0],
                ],
                'dryers' => [
                    ['id' => 'D-01', 'name' => 'Mesin Dryer #01', 'status' => 'running', 'status_label' => 'Berjalan', 'remaining_minutes' => 12, 'load_kg' => 8.0],
                    ['id' => 'D-02', 'name' => 'Mesin Dryer #02', 'status' => 'running', 'status_label' => 'Berjalan', 'remaining_minutes' => 24, 'load_kg' => 8.0],
                ],
            ],
            'filters' => [
                'active' => 'semua',
                'options' => ['semua', 'kiloan', 'satuan', 'kilat'],
            ],
            'perfume_variants' => ['Lavender', 'Sakura', 'Snappy', 'Ocean Fresh', 'Non-Parfum'],
        ]);
    }
}
