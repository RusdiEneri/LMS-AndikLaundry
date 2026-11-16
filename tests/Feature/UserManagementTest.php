<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Feature F4: User Management CRUD & Protection Guards (Tier 1 & Tier 2)
|--------------------------------------------------------------------------
| Specification Source: ORIGINAL_REQUEST §R4, PROJECT.md §20-21, §74-80,
|                       docs/database-schema.md, docs/security.md §3
*/

describe('Feature F4: User Management - Tier 1 Happy Paths', function () {

    it('T1-USER-01: Owner lists paginated users with names, emails, phones, roles, and active statuses', function () {
        $owner = User::factory()->create(['role' => 'owner', 'is_active' => true]);
        User::factory()->create(['name' => 'Cashier Siti', 'role' => 'cashier', 'is_active' => true]);
        User::factory()->create(['name' => 'Operator Joko', 'role' => 'operator', 'is_active' => true]);

        $response = $this->actingAs($owner)->get('/owner/users');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Owner/Users/Index')
            ->has('users.data')
            ->has('users.data.0', fn (Assert $user) => $user
                ->has('id')
                ->has('name')
                ->has('email')
                ->has('phone')
                ->has('role')
                ->has('is_active')
                ->etc()
            )
        );
    });

    it('T1-USER-02: Owner creates new user with role and hashed password', function () {
        $owner = User::factory()->create(['role' => 'owner', 'is_active' => true]);

        $payload = [
            'name' => 'Siti Aminah',
            'email' => 'siti@andiklaundry.test',
            'phone' => '0812-9876-5432',
            'role' => 'cashier',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'is_active' => true,
        ];

        $response = $this->actingAs($owner)->post('/owner/users', $payload);

        $response->assertRedirect('/owner/users');

        $this->assertDatabaseHas('users', [
            'email' => 'siti@andiklaundry.test',
            'phone' => '6281298765432', // Normalized phone format
            'role' => 'cashier',
            'is_active' => true,
        ]);

        $createdUser = User::where('email', 'siti@andiklaundry.test')->first();
        expect(Hash::check('Password123!', $createdUser->password))->toBeTrue();

        // Verify audit log record
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $owner->id,
            'action' => 'create',
            'auditable_type' => User::class,
            'auditable_id' => $createdUser->id,
        ]);
    });

    it('T1-USER-03: Owner updates existing user profile (name, phone, role)', function () {
        $owner = User::factory()->create(['role' => 'owner', 'is_active' => true]);
        $cashier = User::factory()->create([
            'name' => 'Siti Aminah',
            'email' => 'siti@andiklaundry.test',
            'phone' => '6281234567890',
            'role' => 'cashier',
            'is_active' => true,
        ]);

        $updatePayload = [
            'name' => 'Siti Aminah S.E.',
            'email' => 'siti.new@andiklaundry.test',
            'phone' => '0857-1122-3344',
            'role' => 'operator', // Role change from cashier to operator
            'is_active' => true,
        ];

        $response = $this->actingAs($owner)->put("/owner/users/{$cashier->id}", $updatePayload);

        $response->assertRedirect('/owner/users');

        $this->assertDatabaseHas('users', [
            'id' => $cashier->id,
            'name' => 'Siti Aminah S.E.',
            'email' => 'siti.new@andiklaundry.test',
            'phone' => '6285711223344',
            'role' => 'operator',
        ]);

        // Verify audit log
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $owner->id,
            'action' => 'update',
            'auditable_type' => User::class,
            'auditable_id' => $cashier->id,
        ]);
    });

    it('T1-USER-04: Owner soft-deletes another staff member', function () {
        $owner = User::factory()->create(['role' => 'owner', 'is_active' => true]);
        $operator = User::factory()->create(['role' => 'operator', 'is_active' => true]);

        $response = $this->actingAs($owner)->delete("/owner/users/{$operator->id}");

        $response->assertRedirect('/owner/users');

        // Assert user is soft-deleted
        $this->assertSoftDeleted('users', ['id' => $operator->id]);

        // Verify audit log
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $owner->id,
            'action' => 'delete',
            'auditable_type' => User::class,
            'auditable_id' => $operator->id,
        ]);
    });

    it('T1-USER-05: Owner toggles is_active flag of another user to deactivate account', function () {
        $owner = User::factory()->create(['role' => 'owner', 'is_active' => true]);
        $cashier = User::factory()->create(['role' => 'cashier', 'is_active' => true]);

        $response = $this->actingAs($owner)->patch("/owner/users/{$cashier->id}/toggle-status");

        $response->assertRedirect('/owner/users');

        $cashier->refresh();
        expect((bool) $cashier->is_active)->toBeFalse();

        // Verify audit log
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $owner->id,
            'auditable_type' => User::class,
            'auditable_id' => $cashier->id,
        ]);

        // Second toggle reactivates account
        $secondResponse = $this->actingAs($owner)->patch("/owner/users/{$cashier->id}/toggle-status");
        $secondResponse->assertRedirect('/owner/users');

        $cashier->refresh();
        expect((bool) $cashier->is_active)->toBeTrue();
    });
});

describe('Feature F4: User Management - Tier 2 Boundaries & Protection Guards', function () {

    it('T2-USER-01: Logged-in Owner self-deletion attempt is blocked with HTTP 403', function () {
        $owner = User::factory()->create(['role' => 'owner', 'is_active' => true]);

        $response = $this->actingAs($owner)->delete("/owner/users/{$owner->id}");

        $response->assertForbidden();

        // Account must remain NOT deleted
        $this->assertNotSoftDeleted('users', ['id' => $owner->id]);
    });

    it('T2-USER-02: Logged-in Owner self-demotion is blocked with HTTP 403', function () {
        $owner = User::factory()->create([
            'role' => 'owner',
            'name' => 'Pak Andik',
            'email' => 'owner@andiklaundry.test',
            'phone' => '628111222333',
            'is_active' => true,
        ]);

        $demotePayload = [
            'name' => 'Pak Andik',
            'email' => 'owner@andiklaundry.test',
            'phone' => '628111222333',
            'role' => 'cashier', // Attempting to demote self
            'is_active' => true,
        ];

        $response = $this->actingAs($owner)->put("/owner/users/{$owner->id}", $demotePayload);

        $response->assertForbidden();

        $owner->refresh();
        expect($owner->role)->toBe('owner');
    });

    it('T2-USER-03: Logged-in Owner self-deactivation is blocked with HTTP 403', function () {
        $owner = User::factory()->create(['role' => 'owner', 'is_active' => true]);

        // Attempt via toggle-status endpoint
        $toggleResponse = $this->actingAs($owner)->patch("/owner/users/{$owner->id}/toggle-status");
        $toggleResponse->assertForbidden();

        $owner->refresh();
        expect((bool) $owner->is_active)->toBeTrue();

        // Attempt via PUT update endpoint
        $updatePayload = [
            'name' => $owner->name,
            'email' => $owner->email,
            'phone' => $owner->phone,
            'role' => 'owner',
            'is_active' => false,
        ];

        $putResponse = $this->actingAs($owner)->put("/owner/users/{$owner->id}", $updatePayload);
        $putResponse->assertForbidden();

        $owner->refresh();
        expect((bool) $owner->is_active)->toBeTrue();
    });

    it('T2-USER-04: Creating user with duplicate email returns validation error 422', function () {
        $owner = User::factory()->create(['role' => 'owner', 'is_active' => true]);
        User::factory()->create(['email' => 'existing@andiklaundry.test']);

        $payload = [
            'name' => 'Duplicate Email Staff',
            'email' => 'existing@andiklaundry.test',
            'phone' => '081234567899',
            'role' => 'cashier',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ];

        $response = $this->actingAs($owner)->postJson('/owner/users', $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    });

    it('T2-USER-05: Creating user with invalid role returns validation error 422', function () {
        $owner = User::factory()->create(['role' => 'owner', 'is_active' => true]);

        $payload = [
            'name' => 'Invalid Role Staff',
            'email' => 'invalidrole@andiklaundry.test',
            'phone' => '081234567899',
            'role' => 'superadmin', // Invalid role
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ];

        $response = $this->actingAs($owner)->postJson('/owner/users', $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['role']);
    });

    it('T2-USER-06: Non-owner roles (cashier/operator) cannot access User Management endpoints', function () {
        $cashier = User::factory()->create(['role' => 'cashier', 'is_active' => true]);
        $operator = User::factory()->create(['role' => 'operator', 'is_active' => true]);
        $targetUser = User::factory()->create(['role' => 'operator', 'is_active' => true]);

        // Cashier attempts
        $this->actingAs($cashier)->get('/owner/users')->assertForbidden();
        $this->actingAs($cashier)->post('/owner/users', [])->assertForbidden();
        $this->actingAs($cashier)->put("/owner/users/{$targetUser->id}", [])->assertForbidden();
        $this->actingAs($cashier)->delete("/owner/users/{$targetUser->id}")->assertForbidden();
        $this->actingAs($cashier)->patch("/owner/users/{$targetUser->id}/toggle-status")->assertForbidden();

        // Operator attempts
        $this->actingAs($operator)->get('/owner/users')->assertForbidden();
        $this->actingAs($operator)->post('/owner/users', [])->assertForbidden();
        $this->actingAs($operator)->delete("/owner/users/{$targetUser->id}")->assertForbidden();
    });

    it('T2-USER-07: Creating user with duplicate phone returns validation error 422', function () {
        $owner = User::factory()->create(['role' => 'owner', 'is_active' => true]);
        User::factory()->create(['phone' => '6281234567890']);

        // Attempt registration with same phone in domestic format
        $payload = [
            'name' => 'Duplicate Phone Staff',
            'email' => 'newstaff@andiklaundry.test',
            'phone' => '0812-3456-7890', // Normalizes to 6281234567890
            'role' => 'cashier',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ];

        $response = $this->actingAs($owner)->postJson('/owner/users', $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['phone']);
    });

    it('T2-USER-08: Multi-owner deletion allows Owner 1 to soft-delete Owner 2', function () {
        $owner1 = User::factory()->create(['role' => 'owner', 'is_active' => true]);
        $owner2 = User::factory()->create(['role' => 'owner', 'is_active' => true]);

        $response = $this->actingAs($owner1)->delete("/owner/users/{$owner2->id}");

        $response->assertRedirect('/owner/users');
        $this->assertSoftDeleted('users', ['id' => $owner2->id]);
    });

    it('T2-USER-09: Filtering users by role and search query returns matching results', function () {
        $owner = User::factory()->create(['role' => 'owner', 'name' => 'Owner Alpha', 'is_active' => true]);
        User::factory()->create(['role' => 'cashier', 'name' => 'Siti Cashier', 'email' => 'siti@test.com', 'is_active' => true]);
        User::factory()->create(['role' => 'operator', 'name' => 'Joko Operator', 'email' => 'joko@test.com', 'is_active' => true]);

        // Search by query
        $searchResponse = $this->actingAs($owner)->get('/owner/users?search=Siti');
        $searchResponse->assertOk();
        $searchResponse->assertInertia(fn (Assert $page) => $page
            ->component('Owner/Users/Index')
            ->has('users.data', 1)
            ->where('users.data.0.name', 'Siti Cashier')
        );

        // Filter by role
        $roleResponse = $this->actingAs($owner)->get('/owner/users?role=operator');
        $roleResponse->assertOk();
        $roleResponse->assertInertia(fn (Assert $page) => $page
            ->component('Owner/Users/Index')
            ->has('users.data', 1)
            ->where('users.data.0.name', 'Joko Operator')
        );
    });

    it('T2-USER-10: Password can be updated with minimum 8 characters or kept if empty', function () {
        $owner = User::factory()->create(['role' => 'owner', 'is_active' => true]);
        $cashier = User::factory()->create([
            'role' => 'cashier',
            'password' => Hash::make('OldPassword123!'),
            'is_active' => true,
        ]);

        // Update with short password should fail
        $shortPassPayload = [
            'name' => $cashier->name,
            'email' => $cashier->email,
            'phone' => $cashier->phone,
            'role' => 'cashier',
            'password' => 'short',
            'password_confirmation' => 'short',
        ];
        $this->actingAs($owner)->putJson("/owner/users/{$cashier->id}", $shortPassPayload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['password']);

        // Update without password keeps existing password
        $noPassPayload = [
            'name' => 'Updated Name',
            'email' => $cashier->email,
            'phone' => $cashier->phone,
            'role' => 'cashier',
            'password' => null,
        ];
        $this->actingAs($owner)->put("/owner/users/{$cashier->id}", $noPassPayload)
            ->assertRedirect('/owner/users');

        $cashier->refresh();
        expect(Hash::check('OldPassword123!', $cashier->password))->toBeTrue();
    });
});
