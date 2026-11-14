<?php

namespace Database\Seeders;

use App\Enums\ServiceUnit;
use App\Models\BusinessSetting;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceTier;
use Illuminate\Database\Seeder;

class ServiceCatalogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Seed Perfume Scents into BusinessSettings
        BusinessSetting::updateOrCreate(
            ['key' => 'catalog.perfume_variants'],
            [
                'value' => json_encode(['Lavender', 'Sakura', 'Snappy', 'Ocean Fresh', 'Non-Parfum']),
                'type' => 'json',
                'group' => 'catalog',
                'description' => 'Varian aroma parfum standar dan pilihan pelanggan',
            ]
        );

        // 2. Seed Categories
        $categories = [
            'kiloan' => ServiceCategory::updateOrCreate(
                ['slug' => 'kiloan'],
                ['name' => 'Kiloan', 'description' => 'Layanan cuci harian berbasis timbangan kilogram dengan batas minimum cas.', 'is_active' => true]
            ),
            'satuan' => ServiceCategory::updateOrCreate(
                ['slug' => 'satuan'],
                ['name' => 'Satuan', 'description' => 'Layanan perawatan per potong pakaian dengan inspeksi kondisi.', 'is_active' => true]
            ),
            'dry-clean' => ServiceCategory::updateOrCreate(
                ['slug' => 'dry-clean'],
                ['name' => 'Dry Clean', 'description' => 'Pembersihan kimia khusus untuk pakaian formal, jas, dan gaun.', 'is_active' => true]
            ),
            'sepatu-karpet' => ServiceCategory::updateOrCreate(
                ['slug' => 'sepatu-karpet'],
                ['name' => 'Sepatu & Karpet', 'description' => 'Deep cleaning untuk sepatu, tas, dan karpet.', 'is_active' => true]
            ),
        ];

        // 3. Canonical Service Catalog Definitions
        $servicesData = [
            [
                'category' => 'kiloan',
                'code' => 'CK-REG',
                'name' => 'Cuci Komplit Reguler',
                'description' => 'Cuci, pengeringan, setrika uap, parfum, dan packing rapi.',
                'unit' => ServiceUnit::KG,
                'base_price' => 7000.00,
                'min_weight' => 3.00,
                'estimated_duration_hours' => 48,
                'tiers' => [
                    ['tier_name' => 'Reguler', 'tier_code' => 'reguler', 'multiplier_factor' => 1.00, 'flat_surcharge' => 0.00, 'duration_hours' => 48],
                    ['tier_name' => 'Kilat', 'tier_code' => 'kilat', 'multiplier_factor' => 1.00, 'flat_surcharge' => 10000.00, 'duration_hours' => 24],
                    ['tier_name' => 'Express', 'tier_code' => 'express', 'multiplier_factor' => 1.00, 'flat_surcharge' => 20000.00, 'duration_hours' => 8],
                    ['tier_name' => 'Flash', 'tier_code' => 'flash', 'multiplier_factor' => 1.00, 'flat_surcharge' => 35000.00, 'duration_hours' => 3],
                ],
            ],
            [
                'category' => 'kiloan',
                'code' => 'CKL-REG',
                'name' => 'Cuci Kering Lipat',
                'description' => 'Cuci bersih, dikeringkan mesin tumbler gas, dilipat rapi tanpa setrika.',
                'unit' => ServiceUnit::KG,
                'base_price' => 5000.00,
                'min_weight' => 3.00,
                'estimated_duration_hours' => 48,
                'tiers' => [
                    ['tier_name' => 'Reguler', 'tier_code' => 'reguler', 'multiplier_factor' => 1.00, 'flat_surcharge' => 0.00, 'duration_hours' => 48],
                    ['tier_name' => 'Kilat', 'tier_code' => 'kilat', 'multiplier_factor' => 1.00, 'flat_surcharge' => 10000.00, 'duration_hours' => 24],
                    ['tier_name' => 'Express', 'tier_code' => 'express', 'multiplier_factor' => 1.00, 'flat_surcharge' => 20000.00, 'duration_hours' => 8],
                ],
            ],
            [
                'category' => 'kiloan',
                'code' => 'SU-REG',
                'name' => 'Setrika Uap Reguler',
                'description' => 'Setrika uap boiler profesional, parfum, dan packing.',
                'unit' => ServiceUnit::KG,
                'base_price' => 4000.00,
                'min_weight' => 3.00,
                'estimated_duration_hours' => 24,
                'tiers' => [
                    ['tier_name' => 'Reguler', 'tier_code' => 'reguler', 'multiplier_factor' => 1.00, 'flat_surcharge' => 0.00, 'duration_hours' => 24],
                    ['tier_name' => 'Kilat', 'tier_code' => 'kilat', 'multiplier_factor' => 1.00, 'flat_surcharge' => 10000.00, 'duration_hours' => 12],
                ],
            ],
            [
                'category' => 'satuan',
                'code' => 'ST-KMJ',
                'name' => 'Satuan Kemeja / Blus',
                'description' => 'Pencucian detail kancing dan kerah, setrika steam gantung.',
                'unit' => ServiceUnit::PCS,
                'base_price' => 10000.00,
                'min_weight' => 1.00,
                'estimated_duration_hours' => 48,
                'tiers' => [
                    ['tier_name' => 'Reguler', 'tier_code' => 'reguler', 'multiplier_factor' => 1.00, 'flat_surcharge' => 0.00, 'duration_hours' => 48],
                    ['tier_name' => 'Kilat', 'tier_code' => 'kilat', 'multiplier_factor' => 1.00, 'flat_surcharge' => 5000.00, 'duration_hours' => 24],
                ],
            ],
            [
                'category' => 'satuan',
                'code' => 'ST-BCK',
                'name' => 'Bedcover King',
                'description' => 'Pencucian tabung besar higienis, pengeringan menyeluruh.',
                'unit' => ServiceUnit::PCS,
                'base_price' => 25000.00,
                'min_weight' => 1.00,
                'estimated_duration_hours' => 48,
                'tiers' => [
                    ['tier_name' => 'Reguler', 'tier_code' => 'reguler', 'multiplier_factor' => 1.00, 'flat_surcharge' => 0.00, 'duration_hours' => 48],
                    ['tier_name' => 'Kilat', 'tier_code' => 'kilat', 'multiplier_factor' => 1.00, 'flat_surcharge' => 10000.00, 'duration_hours' => 24],
                ],
            ],
            [
                'category' => 'dry-clean',
                'code' => 'DC-JAS',
                'name' => 'Jas Pria (Dry Clean)',
                'description' => 'Pembersihan solven non-air profesional untuk jas dan blazer.',
                'unit' => ServiceUnit::PCS,
                'base_price' => 35000.00,
                'min_weight' => 1.00,
                'estimated_duration_hours' => 72,
                'tiers' => [
                    ['tier_name' => 'Reguler', 'tier_code' => 'reguler', 'multiplier_factor' => 1.00, 'flat_surcharge' => 0.00, 'duration_hours' => 72],
                ],
            ],
            [
                'category' => 'sepatu-karpet',
                'code' => 'SP-CNV',
                'name' => 'Sepatu Canvas',
                'description' => 'Deep wash sole, upper, insole, dan deodorizing.',
                'unit' => ServiceUnit::PCS,
                'base_price' => 30000.00,
                'min_weight' => 1.00,
                'estimated_duration_hours' => 72,
                'tiers' => [
                    ['tier_name' => 'Reguler', 'tier_code' => 'reguler', 'multiplier_factor' => 1.00, 'flat_surcharge' => 0.00, 'duration_hours' => 72],
                ],
            ],
        ];

        foreach ($servicesData as $svcData) {
            $cat = $categories[$svcData['category']] ?? null;

            $service = Service::updateOrCreate(
                ['code' => $svcData['code']],
                [
                    'category_id' => $cat?->id,
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
