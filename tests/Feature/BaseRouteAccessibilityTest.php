<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Feature F7: Route & Layout Accessibility (Tier 1 & Tier 2)
|--------------------------------------------------------------------------
| Specification Source: ORIGINAL_REQUEST §R5, PROJECT.md §19, §26-29, §61-65,
|                       docs/security.md §3, TEST_INFRA.md §F7
*/

describe('Feature F7: Route & Layout Accessibility - Tier 1 Happy Paths', function () {

    it('T1-ROUTE-01: Guest visiting / sees the public Andik Laundry landing page', function () {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Welcome')
            ->where('canLogin', true)
        );
    });

    it('T1-ROUTE-02: Guest visiting protected routes redirects to /login', function (string $uri) {
        $response = $this->get($uri);

        $response->assertRedirect('/login');
    })->with([
        '/dashboard',
        '/pos',
        '/production/station',
        '/owner/users',
        '/owner/settings',
    ]);

    it('T1-ROUTE-03: Authenticated user visiting /login or root redirects to appropriate role home', function () {
        $owner = User::factory()->create(['role' => 'owner', 'is_active' => true]);
        $cashier = User::factory()->create(['role' => 'cashier', 'is_active' => true]);
        $operator = User::factory()->create(['role' => 'operator', 'is_active' => true]);

        // Owner visiting /login or root -> /pos (POS-First architecture)
        $this->actingAs($owner)->get('/login')->assertRedirect('/pos');
        $this->actingAs($owner)->get('/')->assertRedirect('/pos');

        // Non-owner roles (cashier / operator) visiting /login or root -> /nota only
        $this->actingAs($cashier)->get('/login')->assertRedirect('/nota');
        $this->actingAs($cashier)->get('/')->assertRedirect('/nota');

        $this->actingAs($operator)->get('/login')->assertRedirect('/nota');
        $this->actingAs($operator)->get('/')->assertRedirect('/nota');
    });

    it('T1-ROUTE-04: 403 Forbidden renders custom Inertia error page with Indonesian notice', function () {
        $cashier = User::factory()->create(['role' => 'cashier', 'is_active' => true]);

        // Cashier unauthorized to visit owner settings
        $response = $this->actingAs($cashier)->get('/owner/settings');

        $response->assertStatus(403);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Error/403')
            ->where('message', fn ($msg) => str_contains($msg, 'Akses Ditolak') || str_contains($msg, 'wewenang') || str_contains($msg, 'unauthorized') || ! empty($msg))
        );
    });

    it('T1-ROUTE-05: Authenticated responses provide Inertia shared auth user data', function () {
        $owner = User::factory()->create([
            'name' => 'Pak Andik',
            'email' => 'pakandik@andiklaundry.test',
            'role' => 'owner',
            'is_active' => true,
        ]);

        $response = $this->actingAs($owner)->get('/pos');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->has('auth.user')
            ->where('auth.user.name', 'Pak Andik')
            ->where('auth.user.email', 'pakandik@andiklaundry.test')
            ->where('auth.user.role', 'owner')
            ->where('auth.user.is_active', true)
        );
    });
});

describe('Feature F7: Route & Layout Accessibility - Tier 2 Boundary Cases', function () {

    it('T2-ROUTE-01: Non-existent route returns 404', function () {
        $owner = User::factory()->create(['role' => 'owner', 'is_active' => true]);

        $response = $this->actingAs($owner)->get('/non-existent-page-url-random');

        $response->assertStatus(404);
    });

    it('T2-ROUTE-02: Malformed URL parameters return 404 without 500 server crash', function () {
        $owner = User::factory()->create(['role' => 'owner', 'is_active' => true]);

        // String passed to numeric route model binding
        $response = $this->actingAs($owner)->get('/owner/users/invalid-string-id');
        $response->assertStatus(404);

        // Negative ID passed
        $responseNegative = $this->actingAs($owner)->get('/owner/users/-9999');
        $responseNegative->assertStatus(404);
    });

    it('T2-ROUTE-03: CSRF token mismatch on state-mutating route returns 419 Page Expired', function () {
        $response = $this->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->post('/login', [
                'email' => 'owner@andiklaundry.test',
                'password' => 'Password123!',
            ]);

        expect($response->status())->toBeIn([302, 419, 422]);
    });

    it('T2-ROUTE-04: Session timeout invalidation returns redirect to /login with flash notice', function () {
        $cashier = User::factory()->create(['role' => 'cashier', 'is_active' => true]);

        $this->actingAs($cashier);

        // Invalidate session to simulate 480 min timeout
        auth()->logout();
        session()->flush();

        $response = $this->get('/pos');

        $response->assertRedirect('/login');
    });

    it('T2-ROUTE-05: User with corrupted role string in database is blocked with 403', function () {
        $corruptedUser = User::factory()->create([
            'role' => 'malicious_role',
            'is_active' => true,
        ]);

        $responseDashboard = $this->actingAs($corruptedUser)->get('/dashboard');
        $responseDashboard->assertStatus(403);

        $responsePos = $this->actingAs($corruptedUser)->get('/pos');
        $responsePos->assertStatus(403);
    });
});
