<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;

uses(RefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| FEATURE F3: RBAC_BARRIERS — Role Authorization & Access Barriers
|--------------------------------------------------------------------------
*/

describe('Feature F3: RBAC_BARRIERS (Tier 1 - Equivalence Class Happy Paths)', function () {

    it('T1-RBAC-01: allows Owner to access Dashboard, User Management, and Settings', function () {
        $owner = User::factory()->create([
            'email' => 'owner@andiklaundry.test',
            'role' => 'owner',
            'is_active' => true,
        ]);

        $this->actingAs($owner);

        // Owner Dashboard
        $resDash = $this->get('/dashboard');
        $resDash->assertOk();

        // User Management
        $resUsers = $this->get('/owner/users');
        $resUsers->assertOk();

        // Business Settings
        $resSettings = $this->get('/owner/settings');
        $resSettings->assertOk();
    });

    it('T1-RBAC-02: allows Cashier to access POS interface and shifts', function () {
        $cashier = User::factory()->create([
            'email' => 'cashier@andiklaundry.test',
            'role' => 'cashier',
            'is_active' => true,
        ]);

        $this->actingAs($cashier);

        $response = $this->get('/pos');
        $response->assertOk();
    });

    it('T1-RBAC-03: allows Operator to access Production Station', function () {
        $operator = User::factory()->create([
            'email' => 'operator@andiklaundry.test',
            'role' => 'operator',
            'is_active' => true,
        ]);

        $this->actingAs($operator);

        $response = $this->get('/production/station');
        $response->assertOk();
    });

    it('T1-RBAC-04: blocks Cashier from accessing Owner User Management with HTTP 403 Forbidden', function () {
        $cashier = User::factory()->create([
            'email' => 'cashier@andiklaundry.test',
            'role' => 'cashier',
            'is_active' => true,
        ]);

        $this->actingAs($cashier);

        $response = $this->get('/owner/users');
        $response->assertForbidden();
    });

    it('T1-RBAC-05: blocks Operator from accessing POS interface with HTTP 403 Forbidden', function () {
        $operator = User::factory()->create([
            'email' => 'operator@andiklaundry.test',
            'role' => 'operator',
            'is_active' => true,
        ]);

        $this->actingAs($operator);

        $response = $this->get('/pos');
        $response->assertForbidden();
    });
});

describe('Feature F3: RBAC_BARRIERS (Tier 2 - Boundaries & Corner Cases)', function () {

    it('T2-RBAC-01: blocks Cashier from accessing Operator production station with HTTP 403 (strict segregation)', function () {
        $cashier = User::factory()->create([
            'email' => 'cashier@andiklaundry.test',
            'role' => 'cashier',
            'is_active' => true,
        ]);

        $this->actingAs($cashier);

        // Strict role segregation: Cashier cannot access production station
        $response = $this->get('/production/station');
        $response->assertForbidden();
    });

    it('T2-RBAC-02: blocks Operator from accessing Business Settings with HTTP 403 Forbidden', function () {
        $operator = User::factory()->create([
            'email' => 'operator@andiklaundry.test',
            'role' => 'operator',
            'is_active' => true,
        ]);

        $this->actingAs($operator);

        $response = $this->get('/owner/settings');
        $response->assertForbidden();
    });

    it('T2-RBAC-03: grants Owner super-admin bypass across all granular permission gates via Gate::before', function () {
        $owner = User::factory()->create([
            'role' => 'owner',
            'is_active' => true,
        ]);

        $cashier = User::factory()->create([
            'role' => 'cashier',
            'is_active' => true,
        ]);

        // Define arbitrary granular gate not assigned in DB
        Gate::define('arbitrary.admin.action', fn () => false);

        // Cashier is denied by gate
        expect(Gate::forUser($cashier)->allows('arbitrary.admin.action'))->toBeFalse();

        // Owner super-admin automatically passes via Gate::before
        expect(Gate::forUser($owner)->allows('arbitrary.admin.action'))->toBeTrue();
    });

    it('T2-RBAC-04: blocks user with unrecognized or corrupted role string in database with HTTP 403', function () {
        $mutatedUser = User::factory()->create([
            'email' => 'corrupted@andiklaundry.test',
            'role' => 'unrecognized_intruder',
            'is_active' => true,
        ]);

        $this->actingAs($mutatedUser);

        // Blocked from dashboard
        $this->get('/dashboard')->assertForbidden();

        // Blocked from POS
        $this->get('/pos')->assertForbidden();

        // Blocked from owner settings
        $this->get('/owner/settings')->assertForbidden();
    });

    it('T2-RBAC-05: returns JSON 403 response for unauthorized API / AJAX requests', function () {
        $cashier = User::factory()->create([
            'role' => 'cashier',
            'is_active' => true,
        ]);

        $this->actingAs($cashier);

        // Request with Accept: application/json
        $response = $this->getJson('/owner/users');

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'This action is unauthorized.',
            ]);
    });

    it('T2-RBAC-06: renders custom Inertia 403 Error Page for unauthorized web requests', function () {
        $operator = User::factory()->create([
            'role' => 'operator',
            'is_active' => true,
        ]);

        $this->actingAs($operator);

        $response = $this->get('/dashboard', [
            'X-Inertia' => 'true',
        ]);

        $response->assertStatus(403);
    });

    it('T2-RBAC-07: EnsureUserIsActive middleware intercepts deactivated user mid-session, flushes session, and returns 403 / redirect to login', function () {
        $user = User::factory()->create([
            'role' => 'cashier',
            'is_active' => true,
        ]);

        $this->actingAs($user);
        $this->get('/pos')->assertOk();

        // Deactivate account
        $user->update(['is_active' => false]);

        // Next web request must be ejected to /login
        $response = $this->get('/pos');
        $response->assertRedirect('/login');
        $this->assertGuest();

        // Next JSON request must return 403
        $this->actingAs($user);
        $resJson = $this->getJson('/pos');
        $resJson->assertStatus(403);
    });
});
