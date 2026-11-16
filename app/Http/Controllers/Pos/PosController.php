<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\BusinessSetting;
use App\Models\Customer;
use App\Models\Service;
use App\Models\ServiceCategory;
use Database\Seeders\ServiceCatalogSeeder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PosController extends Controller
{
    /**
     * Display the POS Cashier split-screen layout.
     */
    public function index(Request $request): Response
    {
        $minWeight = (float) BusinessSetting::get('operational.default_min_weight', 3.0);
        $creditCeiling = (float) BusinessSetting::get('operational.customer_credit_ceiling', 150000.0);

        // Auto-seed catalog if database is fresh/empty to ensure real persisted service IDs
        if (ServiceCategory::where('is_active', true)->count() === 0 || Service::where('is_active', true)->count() === 0) {
            (new ServiceCatalogSeeder)->run();
        }

        // Fetch categories with services and tiers
        $categoryModels = ServiceCategory::where('is_active', true)
            ->with(['services' => function ($query) {
                $query->where('is_active', true)->with('tiers');
            }])
            ->orderBy('id')
            ->get();

        $categories = array_merge(['Semua'], $categoryModels->pluck('name')->toArray());
        $services = [];
        foreach ($categoryModels as $cat) {
            foreach ($cat->services as $svc) {
                $services[] = [
                    'id' => $svc->id,
                    'name' => $svc->name,
                    'category' => $cat->name,
                    'category_slug' => $cat->slug,
                    'price' => (float) $svc->base_price,
                    'unit' => is_string($svc->unit) ? $svc->unit : ($svc->unit?->value ?? 'kg'),
                    'min_weight' => $svc->min_weight !== null ? (float) $svc->min_weight : null,
                    'estimated_hours' => $svc->estimated_duration_hours ?? 48,
                    'code' => $svc->code,
                    'tiers' => $svc->tiers->map(fn ($tier) => [
                        'id' => $tier->id,
                        'tier_name' => $tier->tier_name,
                        'tier_code' => $tier->tier_code,
                        'multiplier_factor' => (float) $tier->multiplier_factor,
                        'flat_surcharge' => (float) $tier->flat_surcharge,
                        'duration_hours' => (int) $tier->duration_hours,
                    ])->values()->all(),
                ];
            }
        }

        $perfumeVariants = BusinessSetting::get('catalog.perfume_variants');
        if (is_string($perfumeVariants)) {
            $decoded = json_decode($perfumeVariants, true);
            if (is_array($decoded)) {
                $perfumeVariants = $decoded;
            }
        }
        if (! is_array($perfumeVariants) || empty($perfumeVariants)) {
            $perfumeVariants = ['Lavender', 'Sakura', 'Ocean Fresh', 'Akasia', 'Cherry Blossom', 'Snappy', 'Downy', 'Non-Parfum'];
        }

        $defaultCustomer = Customer::firstOrCreate(
            ['name' => 'Pelanggan Umum'],
            [
                'phone' => '080000000000',
                'phone_normalized' => '+6280000000000',
                'address' => 'Pelanggan Walk-In',
                'whatsapp_consent' => false,
            ]
        );

        return Inertia::render('Pos/Index', [
            'default_customer' => [
                'id' => $defaultCustomer->id,
                'name' => $defaultCustomer->name,
                'phone' => $defaultCustomer->phone,
                'phone_normalized' => $defaultCustomer->phone_normalized,
                'outstanding_receivables' => (float) $defaultCustomer->outstanding_receivables,
            ],
            'active_shift' => [
                'id' => 'SHF-OPEN',
                'cashier_name' => $request->user()?->name ?? 'Kasir',
                'is_open' => true,
                'opened_at' => now()->format('H:i'),
                'opening_cash' => 200000,
                'drawer_cash' => 200000,
            ],
            'store_info' => [
                'name' => BusinessSetting::get('store.name', 'Andik Laundry'),
                'phone' => BusinessSetting::get('store.phone', '6281234567890'),
                'address' => BusinessSetting::get('store.address', 'Jl. Raya Laundry No. 12, Jakarta'),
                'default_min_weight' => $minWeight,
                'customer_credit_ceiling' => $creditCeiling,
                'paper_width' => BusinessSetting::get('printer.paper_width', '58mm'),
                'receipt_footer' => BusinessSetting::get('store.receipt_footer', 'Terima kasih telah mencuci di Andik Laundry. Bersih, Wangi, Rapi!'),
            ],
            'categories' => $categories,
            'services' => $services,
            'quick_tenders' => [10000, 20000, 50000, 100000],
            'turnaround_tiers' => [
                ['code' => 'reguler', 'label' => 'Reguler (48 jam)', 'surcharge' => 0],
                ['code' => 'kilat', 'label' => 'Kilat (24 jam)', 'surcharge' => 10000],
                ['code' => 'express', 'label' => 'Express (8 jam)', 'surcharge' => 20000],
                ['code' => 'flash', 'label' => 'Flash (3 jam)', 'surcharge' => 35000],
            ],
            'perfume_variants' => $perfumeVariants,
        ]);
    }
}
