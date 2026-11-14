<?php

use App\Enums\ServiceUnit;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceTier;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Milestone 1: Service Pricing & Minimum Weight Unit Tests
|--------------------------------------------------------------------------
| Specification Source: docs/business-requirements.md §4.2, docs/financial-logic.md §2.2
| Formula: Q_billed = unit === 'kg' ? max(W_actual, W_min) : Q_actual
*/

describe('Milestone 1: Service Pricing Logic - Kiloan Minimum Weight Threshold', function () {

    it('T1-PRIC-01: Kiloan service bills minimum weight threshold when actual weight is below threshold', function () {
        $service = new Service([
            'name' => 'Cuci Komplit Reguler',
            'code' => 'CK-REG',
            'unit' => ServiceUnit::KG,
            'base_price' => 7000.00,
            'min_weight' => 3.00,
        ]);

        // Actual weight 1.25 kg < 3.00 kg minimum threshold
        $billedQty = $service->calculateBilledQuantity(1.25);
        expect($billedQty)->toBe(3.00);

        // Actual weight 0.50 kg < 3.00 kg
        $billedSmall = $service->calculateBilledQuantity(0.50);
        expect($billedSmall)->toBe(3.00);

        // Actual weight exactly at boundary 3.00 kg
        $billedBoundary = $service->calculateBilledQuantity(3.00);
        expect($billedBoundary)->toBe(3.00);
    });

    it('T1-PRIC-02: Kiloan service bills exact actual weight when actual weight exceeds threshold', function () {
        $service = new Service([
            'name' => 'Cuci Komplit Reguler',
            'code' => 'CK-REG',
            'unit' => ServiceUnit::KG,
            'base_price' => 7000.00,
            'min_weight' => 3.00,
        ]);

        // Actual weight 4.65 kg > 3.00 kg
        $billedQty = $service->calculateBilledQuantity(4.65);
        expect($billedQty)->toBe(4.65);

        // Heavy bundle 12.80 kg
        $billedHeavy = $service->calculateBilledQuantity(12.80);
        expect($billedHeavy)->toBe(12.80);
    });

    it('T1-PRIC-03: Kiloan service respects custom minimum weight threshold (e.g. 5.00 kg)', function () {
        $service = new Service([
            'name' => 'Cuci Kiloan Grosir/Asrama',
            'code' => 'CK-GROSIR',
            'unit' => ServiceUnit::KG,
            'base_price' => 5000.00,
            'min_weight' => 5.00,
        ]);

        expect($service->calculateBilledQuantity(2.50))->toBe(5.00)
            ->and($service->calculateBilledQuantity(4.99))->toBe(5.00)
            ->and($service->calculateBilledQuantity(5.01))->toBe(5.01);
    });

    it('T1-PRIC-04: Kiloan service with null min_weight defaults to 3.00 kg fallback', function () {
        $service = new Service([
            'name' => 'Cuci Kiloan Default',
            'code' => 'CK-DEF',
            'unit' => ServiceUnit::KG,
            'base_price' => 6000.00,
            'min_weight' => null,
        ]);

        expect($service->calculateBilledQuantity(1.40))->toBe(3.00)
            ->and($service->calculateBilledQuantity(3.50))->toBe(3.50);
    });

    it('T1-PRIC-05: Satuan service (pcs) bills exact item count without applying minimum weight', function () {
        $service = new Service([
            'name' => 'Kemeja Satuan',
            'code' => 'ST-KMJ',
            'unit' => ServiceUnit::PCS,
            'base_price' => 10000.00,
            'min_weight' => 1.00,
        ]);

        expect($service->calculateBilledQuantity(1.0))->toBe(1.0)
            ->and($service->calculateBilledQuantity(5.0))->toBe(5.0)
            ->and($service->calculateBilledQuantity(12.0))->toBe(12.0);
    });

    it('T1-PRIC-06: M2 service bills exact square meters without applying kiloan minimum weight', function () {
        $service = new Service([
            'name' => 'Cuci Karpet Tebal',
            'code' => 'KP-01',
            'unit' => ServiceUnit::M2,
            'base_price' => 15000.00,
            'min_weight' => 1.00,
        ]);

        expect($service->calculateBilledQuantity(2.4))->toBe(2.4)
            ->and($service->calculateBilledQuantity(6.5))->toBe(6.5);
    });
});

describe('Milestone 1: Service Speed Tier Surcharges', function () {

    it('T1-TIER-01: Reguler speed tier produces zero surcharge', function () {
        $tier = new ServiceTier([
            'tier_name' => 'Reguler',
            'tier_code' => 'reguler',
            'multiplier_factor' => 1.00,
            'flat_surcharge' => 0.00,
            'duration_hours' => 48,
        ]);

        $basePrice = 7000.00;
        $billedQty = 3.00;

        expect($tier->calculateSurcharge($basePrice, $billedQty))->toBe(0.00);
    });

    it('T1-TIER-02: Kilat speed tier with flat surcharge computes exact flat fee', function () {
        $tier = new ServiceTier([
            'tier_name' => 'Kilat',
            'tier_code' => 'kilat',
            'multiplier_factor' => 1.00,
            'flat_surcharge' => 10000.00,
            'duration_hours' => 24,
        ]);

        $basePrice = 7000.00;
        $billedQty = 4.00; // Base total = 28,000

        // Surcharge should be exactly Rp 10,000 flat
        expect($tier->calculateSurcharge($basePrice, $billedQty))->toBe(10000.00);
    });

    it('T1-TIER-03: Express speed tier with percentage multiplier factor computes surcharge correctly', function () {
        $tier = new ServiceTier([
            'tier_name' => 'Express',
            'tier_code' => 'express',
            'multiplier_factor' => 1.50, // +50% priority surcharge
            'flat_surcharge' => 0.00,
            'duration_hours' => 8,
        ]);

        $basePrice = 7000.00;
        $billedQty = 3.00; // Base total = 21,000
        // 50% surcharge on 21,000 = 10,500
        expect($tier->calculateSurcharge($basePrice, $billedQty))->toBe(10500.00);
    });

    it('T1-TIER-04: Speed tier with combined multiplier factor and flat surcharge calculates sum', function () {
        $tier = new ServiceTier([
            'tier_name' => 'Flash',
            'tier_code' => 'flash',
            'multiplier_factor' => 1.50, // +50%
            'flat_surcharge' => 5000.00, // +Rp 5,000
            'duration_hours' => 3,
        ]);

        $basePrice = 10000.00;
        $billedQty = 2.00; // Base total = 20,000
        // Multiplier surcharge: 20,000 * 0.50 = 10,000 + 5,000 flat = 15,000
        expect($tier->calculateSurcharge($basePrice, $billedQty))->toBe(15000.00);
    });

    it('T1-TIER-05: Database persistence and relationships between Category, Service, and Tiers', function () {
        $category = ServiceCategory::factory()->create(['name' => 'Kiloan Expres', 'slug' => 'kiloan-expres']);

        $service = Service::factory()->for($category, 'category')->create([
            'code' => 'CK-TEST-01',
            'name' => 'Cuci Uji Komplit',
            'unit' => ServiceUnit::KG,
            'base_price' => 8000.00,
            'min_weight' => 3.00,
        ]);

        $tierReguler = ServiceTier::factory()->for($service)->reguler()->create();
        $tierKilat = ServiceTier::factory()->for($service)->kilat(10000.00)->create();

        expect($service->category->id)->toBe($category->id)
            ->and($service->tiers)->toHaveCount(2)
            ->and($tierKilat->service->code)->toBe('CK-TEST-01')
            ->and($category->services)->toHaveCount(1);
    });
});
