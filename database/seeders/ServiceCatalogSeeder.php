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
    public function run(): void
    {
        $minWeight = (float) BusinessSetting::get('operational.default_min_weight', 2.0);

        // 1. Service Categories (Real-world: Kiloan, Satuan, Karpet)
        $kategoriKiloan = ServiceCategory::updateOrCreate(
            ['slug' => 'kiloan'],
            [
                'name' => 'Kiloan',
                'description' => 'Layanan cuci harian berbasis timbangan kilogram (Min 2 kg).',
                'is_active' => true,
            ]
        );

        $kategoriSatuan = ServiceCategory::updateOrCreate(
            ['slug' => 'satuan'],
            [
                'name' => 'Satuan',
                'description' => 'Layanan cuci barang satuan seperti bedcover, selimut, sprei, dan boneka.',
                'is_active' => true,
            ]
        );

        $kategoriKarpet = ServiceCategory::updateOrCreate(
            ['slug' => 'karpet'],
            [
                'name' => 'Karpet',
                'description' => 'Layanan cuci karpet meteran (Panjang x Lebar).',
                'is_active' => true,
            ]
        );

        // Deactivate unused categories
        ServiceCategory::whereIn('slug', ['dry-clean', 'sepatu-karpet'])->update(['is_active' => false]);

        // 2. Services Master Data - Actual Andik Laundry Pricing
        $servicesData = [
            // KILOAN (Min 2kg, Reguler 2-3 Hari, Ekspres +2.000/kg pagi ambil sore)
            [
                'category' => $kategoriKiloan,
                'code' => 'CUCI-SETRIKA',
                'name' => 'Cuci Setrika',
                'description' => 'Cuci bersih wangi, pengeringan, dan setrika rapi.',
                'unit' => ServiceUnit::KG,
                'base_price' => 8000.00,
                'min_weight' => $minWeight,
                'estimated_duration_hours' => 48,
                'is_active' => true,
                'tiers' => [
                    ['tier_name' => 'Reguler (2-3 Hari)', 'tier_code' => 'reguler', 'multiplier_factor' => 1.00, 'flat_surcharge' => 0.00, 'duration_hours' => 48],
                    ['tier_name' => 'Ekspres (Pagi Ambil Sore)', 'tier_code' => 'ekspres', 'multiplier_factor' => 1.00, 'flat_surcharge' => 2000.00, 'duration_hours' => 8],
                ],
            ],
            [
                'category' => $kategoriKiloan,
                'code' => 'CUCI-KERING',
                'name' => 'Cuci Kering',
                'description' => 'Pencucian dan pengeringan higienis, dilipat rapi tanpa setrika.',
                'unit' => ServiceUnit::KG,
                'base_price' => 6000.00,
                'min_weight' => $minWeight,
                'estimated_duration_hours' => 48,
                'is_active' => true,
                'tiers' => [
                    ['tier_name' => 'Reguler (2-3 Hari)', 'tier_code' => 'reguler', 'multiplier_factor' => 1.00, 'flat_surcharge' => 0.00, 'duration_hours' => 48],
                    ['tier_name' => 'Ekspres (Pagi Ambil Sore)', 'tier_code' => 'ekspres', 'multiplier_factor' => 1.00, 'flat_surcharge' => 2000.00, 'duration_hours' => 8],
                ],
            ],
            [
                'category' => $kategoriKiloan,
                'code' => 'CUCI-BASAH',
                'name' => 'Cuci Basah',
                'description' => 'Pencucian mesin dengan sabun wangi, diperas tanpa pengeringan tuntas.',
                'unit' => ServiceUnit::KG,
                'base_price' => 5000.00,
                'min_weight' => $minWeight,
                'estimated_duration_hours' => 24,
                'is_active' => true,
                'tiers' => [
                    ['tier_name' => 'Reguler (1 Hari)', 'tier_code' => 'reguler', 'multiplier_factor' => 1.00, 'flat_surcharge' => 0.00, 'duration_hours' => 24],
                ],
            ],

            // KARPET (Meteran Rp 15.000 / meter)
            [
                'category' => $kategoriKarpet,
                'code' => 'CUCI-KARPET',
                'name' => 'Cuci Karpet',
                'description' => 'Pencucian karpet meteran bersih menyeluruh dan anti apek.',
                'unit' => ServiceUnit::METER,
                'base_price' => 15000.00,
                'min_weight' => 1.00,
                'estimated_duration_hours' => 72,
                'is_active' => true,
                'tiers' => [
                    ['tier_name' => 'Reguler (2-3 Hari)', 'tier_code' => 'reguler', 'multiplier_factor' => 1.00, 'flat_surcharge' => 0.00, 'duration_hours' => 72],
                ],
            ],

            // SATUAN (Bedcover, Selimut, Sprei, Boneka)
            [
                'category' => $kategoriSatuan,
                'code' => 'BEDCOVER-KING',
                'name' => 'Bedcover King (Besar)',
                'description' => 'Cuci bedcover ukuran besar / king size, bersih dan harum.',
                'unit' => ServiceUnit::PCS,
                'base_price' => 25000.00,
                'min_weight' => 1.00,
                'estimated_duration_hours' => 48,
                'is_active' => true,
                'tiers' => [
                    ['tier_name' => 'Reguler (2 Hari)', 'tier_code' => 'reguler', 'multiplier_factor' => 1.00, 'flat_surcharge' => 0.00, 'duration_hours' => 48],
                ],
            ],
            [
                'category' => $kategoriSatuan,
                'code' => 'BEDCOVER-SINGLE',
                'name' => 'Bedcover Single (Kecil)',
                'description' => 'Cuci bedcover ukuran single / kecil.',
                'unit' => ServiceUnit::PCS,
                'base_price' => 20000.00,
                'min_weight' => 1.00,
                'estimated_duration_hours' => 48,
                'is_active' => true,
                'tiers' => [
                    ['tier_name' => 'Reguler (2 Hari)', 'tier_code' => 'reguler', 'multiplier_factor' => 1.00, 'flat_surcharge' => 0.00, 'duration_hours' => 48],
                ],
            ],
            [
                'category' => $kategoriSatuan,
                'code' => 'SELIMUT-SPREI',
                'name' => 'Selimut / Sprei',
                'description' => 'Cuci selimut tebal atau sprei set kasur.',
                'unit' => ServiceUnit::PCS,
                'base_price' => 15000.00,
                'min_weight' => 1.00,
                'estimated_duration_hours' => 48,
                'is_active' => true,
                'tiers' => [
                    ['tier_name' => 'Reguler (2 Hari)', 'tier_code' => 'reguler', 'multiplier_factor' => 1.00, 'flat_surcharge' => 0.00, 'duration_hours' => 48],
                ],
            ],
            [
                'category' => $kategoriSatuan,
                'code' => 'BONEKA-BANTAL',
                'name' => 'Boneka / Bantal',
                'description' => 'Cuci boneka, bantal tidur, atau guling.',
                'unit' => ServiceUnit::PCS,
                'base_price' => 10000.00,
                'min_weight' => 1.00,
                'estimated_duration_hours' => 48,
                'is_active' => true,
                'tiers' => [
                    ['tier_name' => 'Reguler (2 Hari)', 'tier_code' => 'reguler', 'multiplier_factor' => 1.00, 'flat_surcharge' => 0.00, 'duration_hours' => 48],
                ],
            ],
        ];

        // Deactivate obsolete services
        Service::whereIn('code', [
            'CUCI-KOMPLIT',
            'CUCI-KERING-LIPAT',
            'SETRIKA-UAP',
            'SELIMUT-TEBAL',
            'JAS-PRIA',
            'GAUN-KEBAYA',
            'SEPATU-CANVAS',
        ])->update(['is_active' => false]);

        foreach ($servicesData as $svcData) {
            $service = Service::updateOrCreate(
                ['code' => $svcData['code']],
                [
                    'category_id' => $svcData['category']->id,
                    'name' => $svcData['name'],
                    'description' => $svcData['description'],
                    'unit' => $svcData['unit'],
                    'base_price' => $svcData['base_price'],
                    'min_weight' => $svcData['min_weight'] ?? 1.00,
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
