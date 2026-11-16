<?php

use App\Models\BusinessSetting;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    BusinessSetting::firstOrCreate(
        ['key' => 'store.name'],
        ['value' => 'Andik Laundry', 'type' => 'string', 'group' => 'store']
    );

    $this->categoryKiloan = ServiceCategory::firstOrCreate(
        ['slug' => 'kiloan'],
        ['name' => 'Kiloan', 'description' => 'Layanan Kiloan', 'is_active' => true]
    );

    $this->categorySatuan = ServiceCategory::firstOrCreate(
        ['slug' => 'satuan'],
        ['name' => 'Satuan', 'description' => 'Layanan Satuan', 'is_active' => true]
    );
});

describe('Owner Service Management CRUD', function () {
    it('allows owner to view settings including services and categories', function () {
        $owner = User::factory()->create(['role' => 'owner', 'is_active' => true]);

        $service = Service::create([
            'category_id' => $this->categoryKiloan->id,
            'code' => 'CK-TEST',
            'name' => 'Cuci Kering Test',
            'unit' => 'kg',
            'base_price' => 6000.00,
            'min_weight' => 2.00,
            'estimated_duration_hours' => 48,
            'is_active' => true,
        ]);

        $response = $this->actingAs($owner)->get('/owner/settings');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Owner/Settings/Index')
            ->has('services')
            ->has('categories')
        );
    });

    it('allows owner to create a new service with reguler and ekspres tiers', function () {
        $owner = User::factory()->create(['role' => 'owner', 'is_active' => true]);

        $payload = [
            'name' => 'Cuci Setrika Spesial',
            'category_id' => $this->categoryKiloan->id,
            'unit' => 'kg',
            'base_price' => 8500,
            'min_weight' => 2,
            'estimated_duration_hours' => 48,
            'description' => 'Cuci bersih, wangi parfum akasia, dan setrika uap.',
            'is_active' => true,
            'has_express' => true,
            'express_surcharge' => 2000,
            'express_duration_hours' => 12,
        ];

        $response = $this->actingAs($owner)->post('/owner/services', $payload);

        $response->assertSessionHas('success');

        $this->assertDatabaseHas('services', [
            'name' => 'Cuci Setrika Spesial',
            'category_id' => $this->categoryKiloan->id,
            'unit' => 'kg',
            'base_price' => 8500.00,
            'min_weight' => 2.00,
            'is_active' => true,
        ]);

        $createdService = Service::where('name', 'Cuci Setrika Spesial')->first();
        expect($createdService)->not->toBeNull();
        expect($createdService->tiers)->toHaveCount(2);

        $this->assertDatabaseHas('service_tiers', [
            'service_id' => $createdService->id,
            'tier_code' => 'reguler',
            'flat_surcharge' => 0.00,
            'duration_hours' => 48,
        ]);

        $this->assertDatabaseHas('service_tiers', [
            'service_id' => $createdService->id,
            'tier_code' => 'ekspres',
            'flat_surcharge' => 2000.00,
            'duration_hours' => 12,
        ]);
    });

    it('allows owner to update service details and prices', function () {
        $owner = User::factory()->create(['role' => 'owner', 'is_active' => true]);

        $service = Service::create([
            'category_id' => $this->categoryKiloan->id,
            'code' => 'CS-UPDATE',
            'name' => 'Cuci Setrika Lama',
            'unit' => 'kg',
            'base_price' => 7000.00,
            'min_weight' => 3.00,
            'estimated_duration_hours' => 48,
            'is_active' => true,
        ]);

        $updatePayload = [
            'name' => 'Cuci Setrika Baru',
            'category_id' => $this->categoryKiloan->id,
            'unit' => 'kg',
            'base_price' => 8000.00,
            'min_weight' => 2.00,
            'estimated_duration_hours' => 48,
            'description' => 'Harga baru disesuaikan lapangan.',
            'is_active' => true,
            'has_express' => true,
            'express_surcharge' => 2500,
            'express_duration_hours' => 10,
        ];

        $response = $this->actingAs($owner)->put("/owner/services/{$service->id}", $updatePayload);

        $response->assertSessionHas('success');

        $this->assertDatabaseHas('services', [
            'id' => $service->id,
            'name' => 'Cuci Setrika Baru',
            'base_price' => 8000.00,
            'min_weight' => 2.00,
        ]);

        $this->assertDatabaseHas('service_tiers', [
            'service_id' => $service->id,
            'tier_code' => 'ekspres',
            'flat_surcharge' => 2500.00,
            'duration_hours' => 10,
        ]);
    });

    it('allows owner to toggle service active status', function () {
        $owner = User::factory()->create(['role' => 'owner', 'is_active' => true]);

        $service = Service::create([
            'category_id' => $this->categoryKiloan->id,
            'code' => 'TOGGLE-SVC',
            'name' => 'Layanan Toggle',
            'unit' => 'kg',
            'base_price' => 5000.00,
            'min_weight' => 2.00,
            'estimated_duration_hours' => 24,
            'is_active' => true,
        ]);

        $response = $this->actingAs($owner)->patch("/owner/services/{$service->id}/toggle-status");
        $response->assertSessionHas('success');

        expect($service->fresh()->is_active)->toBeFalse();

        // Toggle back
        $response = $this->actingAs($owner)->patch("/owner/services/{$service->id}/toggle-status");
        $response->assertSessionHas('success');

        expect($service->fresh()->is_active)->toBeTrue();
    });

    it('allows owner to soft delete a service', function () {
        $owner = User::factory()->create(['role' => 'owner', 'is_active' => true]);

        $service = Service::create([
            'category_id' => $this->categoryKiloan->id,
            'code' => 'DEL-SVC',
            'name' => 'Layanan Hapus',
            'unit' => 'kg',
            'base_price' => 5000.00,
            'min_weight' => 2.00,
            'estimated_duration_hours' => 24,
            'is_active' => true,
        ]);

        $response = $this->actingAs($owner)->delete("/owner/services/{$service->id}");
        $response->assertSessionHas('success');

        $this->assertSoftDeleted('services', ['id' => $service->id]);
    });

    it('forbids cashier from modifying services', function () {
        $cashier = User::factory()->create(['role' => 'cashier', 'is_active' => true]);

        $service = Service::create([
            'category_id' => $this->categoryKiloan->id,
            'code' => 'PROTECT-SVC',
            'name' => 'Layanan Terlindungi',
            'unit' => 'kg',
            'base_price' => 5000.00,
            'min_weight' => 2.00,
            'estimated_duration_hours' => 24,
            'is_active' => true,
        ]);

        $response = $this->actingAs($cashier)->post('/owner/services', [
            'name' => 'Hacker Service',
            'category_id' => $this->categoryKiloan->id,
            'unit' => 'kg',
            'base_price' => 1000,
            'estimated_duration_hours' => 24,
        ]);

        $response->assertForbidden();

        $response = $this->actingAs($cashier)->delete("/owner/services/{$service->id}");
        $response->assertForbidden();
    });
});
