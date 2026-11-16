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
        $minWeight = (float) BusinessSetting::get('operational.default_min_weight', 3.0);

        // 1. Service Categories
        $kategoriKiloan = ServiceCategory::updateOrCreate(
            ['slug' => 'kiloan'],
            ['name' => 'Kiloan', 'description' => 'Layanan cuci harian berbasis timbangan kilogram dengan batas minimum cas.', 'is_active' => true]
        );

        $kategoriSatuan = ServiceCategory::updateOrCreate(
            ['slug' => 'satuan'],
            ['name' => 'Satuan', 'description' => 'Layanan cuci barang satuan seperti bedcover, selimut, sprei, dan boneka.', 'is_active' => true]
        );

        $kategoriDryClean = ServiceCategory::updateOrCreate(
            ['slug' => 'dry-clean'],
            ['name' => 'Dry Clean', 'description' => 'Layanan pencucian khusus jas, gaun, kebaya, dan pakaian formal.', 'is_active' => true]
        );

        $kategoriSepatuKarpet = ServiceCategory::updateOrCreate(
            ['slug' => 'sepatu-karpet'],
            ['name' => 'Sepatu & Karpet', 'description' => 'Layanan cuci sepatu kanvas/kulit dan cuci karpet meteran.', 'is_active' => true]
        );

        // 2. Services Master Data
        $servicesData = [
            // KILOAN
            [
                'category' => $kategoriKiloan,
                'code' => 'CUCI-KOMPLIT',
                'name' => 'Cuci Komplit Reguler',
                'description' => 'Layanan cuci komplit: cuci deterjen premium, pengeringan, setrika uap rapi, dan kemas wangi.',
                'unit' => ServiceUnit::KG,
                'base_price' => 7000.00,
                'min_weight' => $minWeight,
                'estimated_duration_hours' => 48,
                'tiers' => [
                    ['tier_name' => 'Reguler (48 Jam)', 'tier_code' => 'reguler', 'multiplier_factor' => 1.00, 'flat_surcharge' => 0.00, 'duration_hours' => 48],
                    ['tier_name' => 'Kilat (24 Jam)', 'tier_code' => 'kilat', 'multiplier_factor' => 1.25, 'flat_surcharge' => 0.00, 'duration_hours' => 24],
                    ['tier_name' => 'Ekspres (8 Jam)', 'tier_code' => 'ekspres', 'multiplier_factor' => 1.50, 'flat_surcharge' => 0.00, 'duration_hours' => 8],
                ],
            ],
            [
                'category' => $kategoriKiloan,
                'code' => 'CUCI-KERING-LIPAT',
                'name' => 'Cuci Kering Lipat',
                'description' => 'Pencucian dan pengeringan higienis, dilipat rapi tanpa setrika uap.',
                'unit' => ServiceUnit::KG,
                'base_price' => 5000.00,
                'min_weight' => $minWeight,
                'estimated_duration_hours' => 48,
                'tiers' => [
                    ['tier_name' => 'Reguler (48 Jam)', 'tier_code' => 'reguler', 'multiplier_factor' => 1.00, 'flat_surcharge' => 0.00, 'duration_hours' => 48],
                    ['tier_name' => 'Kilat (24 Jam)', 'tier_code' => 'kilat', 'multiplier_factor' => 1.25, 'flat_surcharge' => 0.00, 'duration_hours' => 24],
                    ['tier_name' => 'Ekspres (8 Jam)', 'tier_code' => 'ekspres', 'multiplier_factor' => 1.50, 'flat_surcharge' => 0.00, 'duration_hours' => 8],
                ],
            ],
            [
                'category' => $kategoriKiloan,
                'code' => 'SETRIKA-UAP',
                'name' => 'Setrika Uap Reguler',
                'description' => 'Jasa setrika uap presisi boiler untuk pakaian yang sudah dicuci sendiri, rapi dan wangi.',
                'unit' => ServiceUnit::KG,
                'base_price' => 4000.00,
                'min_weight' => $minWeight,
                'estimated_duration_hours' => 24,
                'tiers' => [
                    ['tier_name' => 'Reguler (24 Jam)', 'tier_code' => 'reguler', 'multiplier_factor' => 1.00, 'flat_surcharge' => 0.00, 'duration_hours' => 24],
                    ['tier_name' => 'Ekspres (6 Jam)', 'tier_code' => 'ekspres', 'multiplier_factor' => 1.50, 'flat_surcharge' => 0.00, 'duration_hours' => 6],
                ],
            ],
            [
                'category' => $kategoriKiloan,
                'code' => 'CUCI-BASAH',
                'name' => 'Cuci Basah',
                'description' => 'Pencucian mesin dengan deterjen dan pelembut, diperas tanpa pengeringan tuntas.',
                'unit' => ServiceUnit::KG,
                'base_price' => 4000.00,
                'min_weight' => $minWeight,
                'estimated_duration_hours' => 24,
                'tiers' => [
                    ['tier_name' => 'Reguler (24 Jam)', 'tier_code' => 'reguler', 'multiplier_factor' => 1.00, 'flat_surcharge' => 0.00, 'duration_hours' => 24],
                ],
            ],

            // SATUAN
            [
                'category' => $kategoriSatuan,
                'code' => 'BEDCOVER-KING',
                'name' => 'Bedcover King',
                'description' => 'Cuci satuan bedcover ukuran besar / king size, bersih menyeluruh dan anti apek.',
                'unit' => ServiceUnit::PCS,
                'base_price' => 25000.00,
                'min_weight' => 1.00,
                'estimated_duration_hours' => 48,
                'tiers' => [
                    ['tier_name' => 'Reguler (48 Jam)', 'tier_code' => 'reguler', 'multiplier_factor' => 1.00, 'flat_surcharge' => 0.00, 'duration_hours' => 48],
                    ['tier_name' => 'Ekspres (24 Jam)', 'tier_code' => 'ekspres', 'multiplier_factor' => 1.40, 'flat_surcharge' => 0.00, 'duration_hours' => 24],
                ],
            ],
            [
                'category' => $kategoriSatuan,
                'code' => 'BEDCOVER-SINGLE',
                'name' => 'Bedcover Single',
                'description' => 'Cuci satuan bedcover ukuran single, bersih dan harum.',
                'unit' => ServiceUnit::PCS,
                'base_price' => 20000.00,
                'min_weight' => 1.00,
                'estimated_duration_hours' => 48,
                'tiers' => [
                    ['tier_name' => 'Reguler (48 Jam)', 'tier_code' => 'reguler', 'multiplier_factor' => 1.00, 'flat_surcharge' => 0.00, 'duration_hours' => 48],
                ],
            ],
            [
                'category' => $kategoriSatuan,
                'code' => 'SELIMUT-TEBAL',
                'name' => 'Selimut Tebal',
                'description' => 'Cuci selimut tebal / fleece / wol lembut tanpa merusak serat kain.',
                'unit' => ServiceUnit::PCS,
                'base_price' => 18000.00,
                'min_weight' => 1.00,
                'estimated_duration_hours' => 48,
                'tiers' => [
                    ['tier_name' => 'Reguler (48 Jam)', 'tier_code' => 'reguler', 'multiplier_factor' => 1.00, 'flat_surcharge' => 0.00, 'duration_hours' => 48],
                ],
            ],

            // DRY CLEAN
            [
                'category' => $kategoriDryClean,
                'code' => 'JAS-PRIA',
                'name' => 'Jas Pria (Dry Clean)',
                'description' => 'Perawatan dry clean khusus jas pria, menjaga bentuk bahu dan kain tetap prima.',
                'unit' => ServiceUnit::PCS,
                'base_price' => 35000.00,
                'min_weight' => 1.00,
                'estimated_duration_hours' => 72,
                'tiers' => [
                    ['tier_name' => 'Reguler (72 Jam)', 'tier_code' => 'reguler', 'multiplier_factor' => 1.00, 'flat_surcharge' => 0.00, 'duration_hours' => 72],
                    ['tier_name' => 'Ekspres (24 Jam)', 'tier_code' => 'ekspres', 'multiplier_factor' => 1.50, 'flat_surcharge' => 0.00, 'duration_hours' => 24],
                ],
            ],
            [
                'category' => $kategoriDryClean,
                'code' => 'GAUN-KEBAYA',
                'name' => 'Gaun / Kebaya',
                'description' => 'Perawatan dry clean khusus gaun pesta, brokat, dan kebaya berpayet.',
                'unit' => ServiceUnit::PCS,
                'base_price' => 40000.00,
                'min_weight' => 1.00,
                'estimated_duration_hours' => 72,
                'tiers' => [
                    ['tier_name' => 'Reguler (72 Jam)', 'tier_code' => 'reguler', 'multiplier_factor' => 1.00, 'flat_surcharge' => 0.00, 'duration_hours' => 72],
                ],
            ],

            // SEPATU & KARPET
            [
                'category' => $kategoriSepatuKarpet,
                'code' => 'SEPATU-CANVAS',
                'name' => 'Sepatu Canvas',
                'description' => 'Deep cleaning sepatu sneakers / canvas luar dalam dengan formula khusus.',
                'unit' => ServiceUnit::PCS,
                'base_price' => 30000.00,
                'min_weight' => 1.00,
                'estimated_duration_hours' => 72,
                'tiers' => [
                    ['tier_name' => 'Reguler (72 Jam)', 'tier_code' => 'reguler', 'multiplier_factor' => 1.00, 'flat_surcharge' => 0.00, 'duration_hours' => 72],
                ],
            ],
            [
                'category' => $kategoriSepatuKarpet,
                'code' => 'CUCI-KARPET',
                'name' => 'Cuci Karpet',
                'description' => 'Layanan cuci karpet berbasis luas area (Panjang x Lebar) dengan mesin ekstraktor.',
                'unit' => ServiceUnit::METER,
                'base_price' => 15000.00,
                'min_weight' => 1.00,
                'estimated_duration_hours' => 72,
                'tiers' => [
                    ['tier_name' => 'Reguler (72 Jam)', 'tier_code' => 'reguler', 'multiplier_factor' => 1.00, 'flat_surcharge' => 0.00, 'duration_hours' => 72],
                    ['tier_name' => 'Ekspres (24 Jam)', 'tier_code' => 'ekspres', 'multiplier_factor' => 1.50, 'flat_surcharge' => 0.00, 'duration_hours' => 24],
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
