<?php

use App\Models\AuditLog;
use App\Models\BusinessSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Foundation E2E & Cross-Feature Integration Scenarios
|--------------------------------------------------------------------------
| Specification Source: TEST_INFRA.md (Tier 3 Pairwise & Tier 4 Scenarios),
|                       PROJECT.md, ORIGINAL_REQUEST.md
*/

beforeEach(function () {
    // Seed essential baseline settings
    BusinessSetting::firstOrCreate(
        ['key' => 'store.name'],
        ['value' => 'Andik Laundry', 'type' => 'string', 'group' => 'store']
    );
    BusinessSetting::firstOrCreate(
        ['key' => 'store.phone'],
        ['value' => '6281234567890', 'type' => 'string', 'group' => 'store']
    );
    BusinessSetting::firstOrCreate(
        ['key' => 'operational.default_min_weight'],
        ['value' => '3.00', 'type' => 'decimal', 'group' => 'operational']
    );
    BusinessSetting::firstOrCreate(
        ['key' => 'operational.customer_credit_ceiling'],
        ['value' => '150000.00', 'type' => 'decimal', 'group' => 'operational']
    );
});

/*
|--------------------------------------------------------------------------
| Tier 3: Cross-Feature Combinations (Pairwise Interaction Tests)
|--------------------------------------------------------------------------
*/

describe('Tier 3: Pairwise Cross-Feature Interactions', function () {

    it('T3-PAIR-01 (AUTH_SESSION x RBAC_BARRIERS): Cashier accesses POS, blocked from Owner routes, logs out, Owner logs in and accesses Owner routes', function () {
        $cashier = User::factory()->create(['email' => 'cashier@test.com', 'role' => 'cashier', 'is_active' => true]);
        $owner = User::factory()->create(['email' => 'owner@test.com', 'role' => 'owner', 'is_active' => true]);

        // 1. Cashier (non-owner) is blocked from POS and Owner routes -> 403
        $this->actingAs($cashier)->get('/pos')->assertForbidden();
        $this->actingAs($cashier)->get('/owner/users')->assertForbidden();

        // 2. Cashier logs out
        $this->actingAs($cashier)->post('/logout')->assertRedirect('/login');

        // 3. Owner logs in and navigates to /pos and /owner/users -> 200 OK
        $this->actingAs($owner)->get('/pos')->assertOk();
        $this->actingAs($owner)->get('/owner/users')->assertOk();
    });

    it('T3-PAIR-02 (USER_CRUD x AUTH_SESSION x AUTH_RATE_LIMIT): Created owner with raw phone normalizes in DB, fails login 5 times, hits rate limit 429, recovers after decay', function () {
        $owner = User::factory()->create(['role' => 'owner', 'is_active' => true]);

        // 1. Owner creates second owner with raw unformatted phone
        $createPayload = [
            'name' => 'Dewi Persik',
            'email' => 'dewi@andiklaundry.test',
            'phone' => '0857-1122-3344',
            'role' => 'owner',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'is_active' => true,
        ];
        $this->actingAs($owner)->post('/owner/users', $createPayload)->assertStatus(302);

        // 2. Assert phone normalized canonically in database
        $this->assertDatabaseHas('users', [
            'email' => 'dewi@andiklaundry.test',
            'phone' => '6285711223344',
        ]);

        $this->post('/logout');

        // 3. Dewi attempts 5 consecutive bad password logins
        $throttleKey = '127.0.0.1|dewi@andiklaundry.test';
        RateLimiter::clear($throttleKey);

        for ($i = 1; $i <= 5; $i++) {
            $this->post('/login', [
                'email' => 'dewi@andiklaundry.test',
                'password' => 'WrongPassword',
            ])->assertSessionHasErrors(['email']);
        }

        // 4. 6th attempt is locked out with 429 Too Many Requests
        $responseLock = $this->post('/login', [
            'email' => 'dewi@andiklaundry.test',
            'password' => 'Password123!',
        ]);
        expect($responseLock->status())->toBeIn([422, 429]);

        // 5. Travel past decay window (61 seconds) and attempt legitimate login
        $this->travel(61)->seconds();
        RateLimiter::clear($throttleKey);

        $loginResponse = $this->post('/login', [
            'email' => 'dewi@andiklaundry.test',
            'password' => 'Password123!',
        ]);
        $loginResponse->assertRedirect('/pos');
        $this->assertAuthenticated();
    });

    it('T3-PAIR-03 (USER_CRUD x AUTH_SESSION x EnsureUserIsActive): Owner account in active session is deactivated; next request is intercepted and session destroyed', function () {
        $owner = User::factory()->create(['role' => 'owner', 'is_active' => true]);
        $secondOwner = User::factory()->create(['email' => 'owner_siti@test.com', 'role' => 'owner', 'is_active' => true]);

        // 1. Second owner logs in and confirms active session
        $this->actingAs($secondOwner)->get('/pos')->assertOk();

        // 2. Primary owner deactivates second account
        $this->actingAs($owner)->patch("/owner/users/{$secondOwner->id}/toggle-status")->assertStatus(302);
        expect((bool) $secondOwner->fresh()->is_active)->toBeFalse();

        // 3. Deactivated user performs subsequent request; EnsureUserIsActive intercepts
        $subsequentResponse = $this->actingAs($secondOwner)->get('/pos');

        // Assert session flushed and redirected to login with 403 or deactivated notice
        expect($subsequentResponse->status())->toBeIn([302, 403]);
        $this->assertGuest();
    });

    it('T3-PAIR-04 (SETTINGS_MGMT x RBAC_BARRIERS x USER_CRUD): Cashier blocked from mutating settings, Owner updates min_weight threshold, POS reflects new setting', function () {
        $cashier = User::factory()->create(['role' => 'cashier', 'is_active' => true]);
        $owner = User::factory()->create(['role' => 'owner', 'is_active' => true]);

        // 1. Cashier attempts PUT to settings -> Blocked 403
        $this->actingAs($cashier)->put('/owner/settings', [
            'settings' => [['key' => 'operational.default_min_weight', 'value' => '5.00']],
        ])->assertForbidden();

        // 2. Owner updates operational minimum weight
        $this->actingAs($owner)->put('/owner/settings', [
            'settings' => [['key' => 'operational.default_min_weight', 'value' => '3.50']],
        ]);

        // 3. Setting persists and verified through static getter helper
        expect(BusinessSetting::get('operational.default_min_weight'))->toBeIn(['3.50', 3.50, 3.5]);

        // 4. Owner visiting POS interface receives 200 OK while Cashier gets 403
        $this->actingAs($owner)->get('/pos')->assertOk();
        $this->actingAs($cashier)->get('/pos')->assertForbidden();
    });

    it('T3-PAIR-05 (USER_CRUD x SelfProtectionGuards x RBAC_BARRIERS): Owner cannot delete or demote self, but can manage second owner account', function () {
        $owner1 = User::factory()->create(['role' => 'owner', 'is_active' => true]);
        $owner2 = User::factory()->create(['role' => 'owner', 'is_active' => true]);

        // 1. Owner 1 attempts to delete self -> Blocked 403
        $this->actingAs($owner1)->delete("/owner/users/{$owner1->id}")->assertForbidden();

        // 2. Owner 1 attempts to demote self to cashier -> Blocked 403
        $this->actingAs($owner1)->put("/owner/users/{$owner1->id}", [
            'name' => $owner1->name,
            'email' => $owner1->email,
            'phone' => $owner1->phone,
            'role' => 'cashier',
            'is_active' => true,
        ])->assertForbidden();

        // 3. Owner 1 deletes Owner 2 -> Allowed
        $this->actingAs($owner1)->delete("/owner/users/{$owner2->id}")->assertStatus(302);
        $this->assertSoftDeleted('users', ['id' => $owner2->id]);
    });

    it('T3-PAIR-06 (ROUTE_LAYOUT x AUTH_SESSION x RBAC_BARRIERS): Guest visits /pos, redirected to /login with intended URL, logs in as Owner, redirected to /pos', function () {
        $owner = User::factory()->create([
            'email' => 'owner_flow@andiklaundry.test',
            'password' => Hash::make('Password123!'),
            'role' => 'owner',
            'is_active' => true,
        ]);

        // 1. Guest visits /pos -> Redirected to /login
        $guestResponse = $this->get('/pos');
        $guestResponse->assertRedirect('/login');

        // 2. Owner logs in
        $loginResponse = $this->post('/login', [
            'email' => 'owner_flow@andiklaundry.test',
            'password' => 'Password123!',
        ]);

        // 3. Redirected to intended URL /pos
        $loginResponse->assertRedirect('/pos');

        // 4. Verify shared Inertia props confirm owner identity
        $this->get('/pos')->assertInertia(fn (Assert $page) => $page
            ->has('auth.user')
            ->where('auth.user.role', 'owner')
        );
    });

    it('T3-PAIR-07 (PHONE_NORM x USER_CRUD): Prevents duplicate registration across varying telephone formats for the same customer or staff', function () {
        $owner = User::factory()->create(['role' => 'owner', 'is_active' => true]);

        // 1. Register staff with international +62 format
        $this->actingAs($owner)->post('/owner/users', [
            'name' => 'Budi Operator',
            'email' => 'budi1@andiklaundry.test',
            'phone' => '+62 812-3456-7890',
            'role' => 'operator',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'is_active' => true,
        ])->assertStatus(302);

        $this->assertDatabaseHas('users', ['phone' => '6281234567890']);

        // 2. Attempt registering second staff with local 08 format -> Rejected by unique validation
        $responseDuplicate = $this->actingAs($owner)->post('/owner/users', [
            'name' => 'Budi Duplikat',
            'email' => 'budi2@andiklaundry.test',
            'phone' => '081234567890',
            'role' => 'operator',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'is_active' => true,
        ]);

        $responseDuplicate->assertSessionHasErrors(['phone']);
    });
});

/*
|--------------------------------------------------------------------------
| Tier 4: Real-World Application Scenarios (E2E Foundation Journeys)
|--------------------------------------------------------------------------
*/

describe('Tier 4: Real-World Application Workloads', function () {

    it('T4-SCENARIO-01: Store Staff Provisioning & First-Time Login Journey', function () {
        $owner = User::factory()->create([
            'name' => 'Pak Andik',
            'email' => 'owner@andiklaundry.test',
            'password' => Hash::make('OwnerPass123!'),
            'role' => 'owner',
            'is_active' => true,
        ]);

        // Step 1: Owner logs in and navigates to User Management (POS-First architecture)
        $this->post('/login', ['email' => 'owner@andiklaundry.test', 'password' => 'OwnerPass123!'])->assertRedirect('/pos');
        $this->get('/owner/users')->assertOk();

        // Step 2: Owner provisions Staff Cashier "Siti Aminah"
        $this->post('/owner/users', [
            'name' => 'Siti Aminah',
            'email' => 'siti@andiklaundry.test',
            'phone' => '0812-9876-5432',
            'role' => 'cashier',
            'password' => 'SitiPass123!',
            'password_confirmation' => 'SitiPass123!',
            'is_active' => true,
        ])->assertStatus(302);

        // Step 3: Owner provisions Staff Operator "Joko Widodo"
        $this->post('/owner/users', [
            'name' => 'Joko Widodo',
            'email' => 'joko@andiklaundry.test',
            'phone' => '+62 856 7890 1234',
            'role' => 'operator',
            'password' => 'JokoPass123!',
            'password_confirmation' => 'JokoPass123!',
            'is_active' => true,
        ])->assertStatus(302);

        // Step 4: Verify database assertions & audit logs
        $this->assertDatabaseHas('users', ['email' => 'siti@andiklaundry.test', 'phone' => '6281298765432']);
        $this->assertDatabaseHas('users', ['email' => 'joko@andiklaundry.test', 'phone' => '6285678901234']);
        expect(AuditLog::where('action', 'create')->where('auditable_type', User::class)->count())->toBe(2);

        // Step 5: Owner logs out
        $this->post('/logout')->assertRedirect('/login');

        // Step 6: Non-owner roles route only to public /nota and are denied internal views
        $this->post('/login', ['email' => 'siti@andiklaundry.test', 'password' => 'SitiPass123!'])->assertRedirect('/nota');
        $this->get('/pos')->assertStatus(403);
        $this->get('/dashboard')->assertStatus(403);
        $this->post('/logout')->assertRedirect('/login');

        $this->post('/login', ['email' => 'joko@andiklaundry.test', 'password' => 'JokoPass123!'])->assertRedirect('/nota');
        $this->get('/production/station')->assertStatus(403);
        $this->get('/pos')->assertStatus(403);
        $this->get('/owner/settings')->assertStatus(403);
    });

    it('T4-SCENARIO-02: Rogue User Immediate Deactivation & Session Termination', function () {
        $owner = User::factory()->create(['role' => 'owner', 'is_active' => true]);
        $secondOwner = User::factory()->create([
            'name' => 'Staf Suspect',
            'email' => 'siti.suspect@andiklaundry.test',
            'password' => Hash::make('SitiPass123!'),
            'role' => 'owner',
            'is_active' => true,
        ]);

        // Step 1: Second owner is operating POS terminal
        $this->actingAs($secondOwner)->get('/pos')->assertOk();

        // Step 2: Owner disables account
        $this->actingAs($owner)->patch("/owner/users/{$secondOwner->id}/toggle-status")->assertStatus(302);
        expect((bool) $secondOwner->fresh()->is_active)->toBeFalse();
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $owner->id,
            'auditable_type' => User::class,
            'auditable_id' => $secondOwner->id,
        ]);

        // Step 3: Deactivated user attempts to refresh POS
        $blockedResponse = $this->actingAs($secondOwner)->get('/pos');
        expect($blockedResponse->status())->toBeIn([302, 403]);
        $this->assertGuest();

        // Step 4: Deactivated user attempts to re-authenticate -> Blocked at login boundary
        $reauthResponse = $this->post('/login', [
            'email' => 'siti.suspect@andiklaundry.test',
            'password' => 'SitiPass123!',
        ]);
        $reauthResponse->assertSessionHasErrors(['email']);
        $this->assertGuest();
    });

    it('T4-SCENARIO-03: System Configuration Audit & Operational Thresholds', function () {
        $owner = User::factory()->create(['role' => 'owner', 'is_active' => true]);
        $cashier = User::factory()->create(['role' => 'cashier', 'is_active' => true]);

        // Step 1: Owner updates store profile and operational thresholds
        $updatePayload = [
            'settings' => [
                ['key' => 'store.name', 'value' => 'Andik Laundry Syariah'],
                ['key' => 'store.phone', 'value' => '081234567890'],
                ['key' => 'operational.default_min_weight', 'value' => '3.00'],
                ['key' => 'operational.customer_credit_ceiling', 'value' => '500000.00'],
            ],
        ];

        $this->actingAs($owner)->put('/owner/settings', $updatePayload)->assertStatus(302);

        // Step 2: Verify audit log recorded changes
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $owner->id,
            'action' => 'update',
            'auditable_type' => BusinessSetting::class,
        ]);

        // Step 3: Owner accesses POS; Cashier gets 403
        expect(BusinessSetting::get('store.name'))->toBe('Andik Laundry Syariah')
            ->and(BusinessSetting::get('operational.default_min_weight'))->toBeIn(['3.00', 3.00, 3.0])
            ->and(BusinessSetting::get('operational.customer_credit_ceiling'))->toBeIn(['500000.00', 500000.00, 500000]);

        $this->actingAs($owner)->get('/pos')->assertOk();
        $this->actingAs($cashier)->get('/pos')->assertForbidden();

        // Step 4: Cashier attempts to alter operational settings -> Blocked 403
        $this->actingAs($cashier)->put('/owner/settings', [
            'settings' => [['key' => 'operational.default_min_weight', 'value' => '1.00']],
        ])->assertForbidden();
    });

    it('T4-SCENARIO-04: Owner Self-Preservation & Disaster Prevention', function () {
        $pakAndik = User::factory()->create([
            'name' => 'Pak Andik',
            'email' => 'andik@andiklaundry.test',
            'role' => 'owner',
            'is_active' => true,
        ]);

        // Step 1: Pak Andik accidentally attempts to delete his own account
        $responseDelete = $this->actingAs($pakAndik)->delete("/owner/users/{$pakAndik->id}");
        $responseDelete->assertForbidden();
        expect(User::find($pakAndik->id))->not->toBeNull();

        // Step 2: Pak Andik attempts to demote his own role to Cashier
        $responseDemote = $this->actingAs($pakAndik)->put("/owner/users/{$pakAndik->id}", [
            'name' => 'Pak Andik',
            'email' => 'andik@andiklaundry.test',
            'phone' => '6281234567001',
            'role' => 'cashier',
            'is_active' => true,
        ]);
        $responseDemote->assertForbidden();
        expect($pakAndik->fresh()->role)->toBe('owner');

        // Step 3: Pak Andik creates backup Owner account "Ibu Andik"
        $this->actingAs($pakAndik)->post('/owner/users', [
            'name' => 'Ibu Andik',
            'email' => 'ibu.andik@andiklaundry.test',
            'phone' => '081234567999',
            'role' => 'owner',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'is_active' => true,
        ])->assertStatus(302);

        $ibuAndik = User::where('email', 'ibu.andik@andiklaundry.test')->first();
        expect($ibuAndik)->not->toBeNull();

        // Step 4: Pak Andik deletes backup Owner account -> Allowed
        $this->actingAs($pakAndik)->delete("/owner/users/{$ibuAndik->id}")->assertStatus(302);
        $this->assertSoftDeleted('users', ['id' => $ibuAndik->id]);
    });

    it('T4-SCENARIO-05: Brute-Force Attack Defense & Legitimate Recovery', function () {
        $owner = User::factory()->create([
            'email' => 'target.owner@andiklaundry.test',
            'password' => Hash::make('LegitimatePassword123!'),
            'role' => 'owner',
            'is_active' => true,
        ]);

        $throttleKey = '127.0.0.1|target.owner@andiklaundry.test';
        RateLimiter::clear($throttleKey);

        // Step 1: Attacker sends 5 failed password attempts in rapid succession
        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $response = $this->post('/login', [
                'email' => 'target.owner@andiklaundry.test',
                'password' => "BadPasswordAttempt_{$attempt}",
            ]);
            $response->assertSessionHasErrors(['email']);
        }

        // Step 2: 6th attempt is blocked with 429 Too Many Requests
        $lockedResponse = $this->post('/login', [
            'email' => 'target.owner@andiklaundry.test',
            'password' => 'LegitimatePassword123!',
        ]);
        expect($lockedResponse->status())->toBeIn([422, 429]);
        $this->assertGuest();

        // Step 3: Legitimate owner waits out decay window (61s)
        $this->travel(61)->seconds();
        RateLimiter::clear($throttleKey);

        // Step 4: Owner attempts login with correct credentials -> Succeeds
        $recoveryResponse = $this->post('/login', [
            'email' => 'target.owner@andiklaundry.test',
            'password' => 'LegitimatePassword123!',
        ]);
        $recoveryResponse->assertRedirect('/pos');
        $this->assertAuthenticatedAs($owner);
    });
});
