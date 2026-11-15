<?php

namespace Database\Seeders;

use App\Enums\ServiceUnit;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceTier;
use Illuminate\Database\Seeder;

class ServiceCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $kategoriKiloan = ServiceCategory::updateOrCreate(
            ['slug' => 'kiloan'],
            ['name' => 'Kiloan', 'description' => 'Layanan cuci harian berbasis timbangan kilogram dengan batas minimum cas 2 kg.', 'is_active' => true]
        );

        $kategoriMeteran = ServiceCategory::updateOrCreate(
            ['slug' => 'meteran'],
            ['name' => 'Meteran', 'description' => 'Layanan cuci karpet dan barang berbasis luas area (meter persegi).', 'is_active' => true]
        );

        $servicesData = [
            [
                'category' => $kategoriKiloan,
                'code' => 'CUCI-BASAH',
                'name' => 'Cuci Basah',
                'description' => 'Layanan cuci basah - pencucian dengan deterjen, pengeringan, tanpa setrika.',
                'unit' => ServiceUnit::KG,
                'base_price' => 5000.00,
                'min_weight' => 2.00,
                'estimated_duration_hours' => 48,
                'tiers' => [
                    ['tier_name' => 'Reguler', 'tier_code' => 'reguler', 'multiplier_factor' => 1.00, 'flat_surcharge' => 0.00, 'duration_hours' => 48],
                    ['tier_name' => 'Ekspres', 'tier_code' => 'ekspres', 'multiplier_factor' => 1.00, 'flat_surcharge' => 0.00, 'duration_hours' => 8, 'surcharge_per_kg' => 2000],
                ],
            ],
            [
                'category' => $kategoriKiloan,
                'code' => 'CUCI-KERING',
                'name' => 'Cuci Kering',
                'description' => 'Layanan cuci kering - pencucian dan pengeringan, tanpa setrika.',
                'unit' => ServiceUnit::KG,
                'base_price' => 6000.00,
                'min_weight' => 2.00,
                'estimated_duration_hours' => 48,
                'tiers' => [
                    ['tier_name' => 'Reguler', 'tier_code' => 'reguler', 'multiplier_factor' => 1.00, 'flat_surcharge' => 0.00, 'duration_hours' => 48],
                    ['tier_name' => 'Ekspres', 'tier_code' => 'ekspres', 'multiplier_factor' => 1.00, 'flat_surcharge' => 0.00, 'duration_hours' => 8, 'surcharge_per_kg' => 2000],
                ],
            ],
            [
                'category' => $kategoriKiloan,
                'code' => 'CUCI-SETRIKA',
                'name' => 'Cuci Setrika',
                'description' => 'Layanan cuci komplit - cuci, kering, dan setrika rapi.',
                'unit' => ServiceUnit::KG,
                'base_price' => 8000.00,
                'min_weight' => 2.00,
                'estimated_duration_hours' => 48,
                'tiers' => [
                    ['tier_name' => 'Reguler', 'tier_code' => 'reguler', 'multiplier_factor' => 1.00, 'flat_surcharge' => 0.00, 'duration_hours' => 48],
                    ['tier_name' => 'Ekspres', 'tier_code' => 'ekspres', 'multiplier_factor' => 1.00, 'flat_surcharge' => 0.00, 'duration_hours' => 8, 'surcharge_per_kg' => 2000],
                ],
            ],
            [
                'category' => $kategoriMeteran,
                'code' => 'CUCI-KARPET',
                'name' => 'Cuci Karpet',
                'description' => 'Layanan cuci karpet berbasis luas area (Panjang x Lebar).',
                'unit' => ServiceUnit::METER,
                'base_price' => 15000.00,
                'min_weight' => 1.00,
                'estimated_duration_hours' => 72,
                'tiers' => [
                    ['tier_name' => 'Reguler', 'tier_code' => 'reguler', 'multiplier_factor' => 1.00, 'flat_surcharge' => 0.00, 'duration_hours' => 72],
                    ['tier_name' => 'Ekspres', 'tier_code' => 'ekspres', 'multiplier_factor' => 1.00, 'flat_surcharge' => 0.00, 'duration_hours' => 24, 'surcharge_per_m2' => 5000],
                ],
            ],
        ];

        foreach ($servicesData as $svcData) {
            $service = Service::updateOrCreate(
                ['code' => $svcData['code']],
                [
                    'category_id' => $svcData['category']->id,
                    'name' => $svcData['name'],
                    'description' => $svcData['description'],
                    'unit' => $svcData['unit'],
                    'base_price' => $svcData['base_price'],
                    'min_weight' => $svcData['min_weight'],
                    'estimated_duration_hours' => $svcData['estimated_duration_hours'],
                    'is_active' => true,
                ]
            );

            foreach ($svcData['tiers'] as $tierData) {
                ServiceTier::updateOrCreate(
                    [
                        'service_id' => $service->id,
                        'tier_code' => $tierData['tier_code'],
                    ],
                    [
                        'tier_name' => $tierData['tier_name'],
                        'multiplier_factor' => $tierData['multiplier_factor'],
                        'flat_surcharge' => $tierData['flat_surcharge'],
                        'duration_hours' => $tierData['duration_hours'],
                        'is_active' => true,
                    ]
                );
            }
        }
    }
}
