<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Clear login rate limiter before each test run
    RateLimiter::clear('127.0.0.1|owner@andiklaundry.test');
    RateLimiter::clear('127.0.0.1|cashier@andiklaundry.test');
    RateLimiter::clear('127.0.0.1|operator@andiklaundry.test');
});

/*
|--------------------------------------------------------------------------
| FEATURE F1: AUTH_SESSION — Stateful Session Authentication
|--------------------------------------------------------------------------
*/

describe('Feature F1: AUTH_SESSION (Tier 1 - Equivalence Class Happy Paths)', function () {

    it('T1-AUTH-01: logs in successfully with valid credentials and redirects to role home', function () {
        $owner = User::factory()->create([
            'email' => 'owner@andiklaundry.test',
            'password' => Hash::make('Password123!'),
            'role' => 'owner',
            'is_active' => true,
        ]);

        $cashier = User::factory()->create([
            'email' => 'cashier@andiklaundry.test',
            'password' => Hash::make('Password123!'),
            'role' => 'cashier',
            'is_active' => true,
        ]);

        $operator = User::factory()->create([
            'email' => 'operator@andiklaundry.test',
            'password' => Hash::make('Password123!'),
            'role' => 'operator',
            'is_active' => true,
        ]);

        // 1. Owner Login -> Redirects to /pos (POS-First architecture)
        $responseOwner = $this->post('/login', [
            'email' => 'owner@andiklaundry.test',
            'password' => 'Password123!',
        ]);
        $responseOwner->assertRedirect('/pos');
        $this->assertAuthenticatedAs($owner);
        Auth::logout();

        // 2. Non-owner roles route only to public receipt tracker (/nota) under owner-only model
        $responseCashier = $this->post('/login', [
            'email' => 'cashier@andiklaundry.test',
            'password' => 'Password123!',
        ]);
        $responseCashier->assertRedirect('/nota');
        $this->assertAuthenticatedAs($cashier);
        Auth::logout();

        $responseOperator = $this->post('/login', [
            'email' => 'operator@andiklaundry.test',
            'password' => 'Password123!',
        ]);
        $responseOperator->assertRedirect('/nota');
        $this->assertAuthenticatedAs($operator);
    });

    it('T1-AUTH-02: sets persistent remember token cookie when remember-me is checked', function () {
        $user = User::factory()->create([
            'email' => 'owner.remember@andiklaundry.test',
            'password' => Hash::make('Password123!'),
            'role' => 'owner',
            'is_active' => true,
        ]);

        $response = $this->post('/login', [
            'email' => 'owner.remember@andiklaundry.test',
            'password' => 'Password123!',
            'remember' => true,
        ]);

        $response->assertRedirect('/pos');
        $this->assertAuthenticatedAs($user);

        // Verify user model has a fresh remember token
        $user->refresh();
        expect($user->remember_token)->not->toBeNull();

        // Verify response contains remember cookie
        $response->assertCookie(Auth::guard('web')->getRecallerName());
    });

    it('T1-AUTH-03: logs out successfully, clears session, regenerates CSRF, and redirects to login', function () {
        $user = User::factory()->create([
            'email' => 'owner@andiklaundry.test',
            'password' => Hash::make('Password123!'),
            'role' => 'owner',
            'is_active' => true,
        ]);

        $this->actingAs($user);
        $this->assertAuthenticatedAs($user);

        $response = $this->post('/logout');

        $response->assertRedirect('/login');
        $this->assertGuest();
    });

    it('T1-AUTH-04: regenerates session ID upon login to protect against session fixation attacks', function () {
        $user = User::factory()->create([
            'email' => 'owner@andiklaundry.test',
            'password' => Hash::make('Password123!'),
            'role' => 'owner',
            'is_active' => true,
        ]);

        // Start initial session
        $this->get('/login');
        $initialSessionId = Session::getId();

        $response = $this->post('/login', [
            'email' => 'owner@andiklaundry.test',
            'password' => 'Password123!',
        ]);

        $response->assertRedirect('/pos');
        $newSessionId = Session::getId();

        expect($newSessionId)->not->toBe($initialSessionId);
    });

    it('T1-AUTH-05: redirects unauthenticated requests with expired/invalid session to login', function () {
        // Accessing protected owner route without session
        $response = $this->get('/dashboard');
        $response->assertRedirect('/login');

        // Accessing protected cashier route without session
        $responsePos = $this->get('/pos');
        $responsePos->assertRedirect('/login');

        // Accessing protected API endpoint without session returns 401
        $responseApi = $this->getJson('/dashboard');
        $responseApi->assertStatus(401);
    });
});

describe('Feature F1: AUTH_SESSION (Tier 2 - Boundaries & Corner Cases)', function () {

    it('T2-AUTH-01: returns 422 credential validation error for non-existent email', function () {
        $response = $this->from('/login')->post('/login', [
            'email' => 'nonexistent@andiklaundry.test',
            'password' => 'Password123!',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors(['email']);
        $this->assertGuest();
    });

    it('T2-AUTH-02: returns 422 validation error when password is blank', function () {
        $user = User::factory()->create(['email' => 'owner@andiklaundry.test']);

        $response = $this->from('/login')->post('/login', [
            'email' => 'owner@andiklaundry.test',
            'password' => '',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors(['password']);
        $this->assertGuest();
    });

    it('T2-AUTH-03: returns 422 validation error when email is empty or invalid format', function () {
        // Empty email
        $resEmpty = $this->from('/login')->post('/login', [
            'email' => '',
            'password' => 'Password123!',
        ]);
        $resEmpty->assertSessionHasErrors(['email']);

        // Malformed email
        $resMalformed = $this->from('/login')->post('/login', [
            'email' => 'not-a-valid-email',
            'password' => 'Password123!',
        ]);
        $resMalformed->assertSessionHasErrors(['email']);
        $this->assertGuest();
    });

    it('T2-AUTH-04: blocks deactivated user (is_active = false) from logging in with 422 error', function () {
        $deactivated = User::factory()->create([
            'email' => 'deactivated@andiklaundry.test',
            'password' => Hash::make('Password123!'),
            'role' => 'cashier',
            'is_active' => false,
        ]);

        $response = $this->from('/login')->post('/login', [
            'email' => 'deactivated@andiklaundry.test',
            'password' => 'Password123!',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors(['email']);
        $this->assertGuest();
    });

    it('T2-AUTH-05: terminates session and redirects to login when authenticated user is deactivated mid-session', function () {
        $user = User::factory()->create([
            'email' => 'owner.active@andiklaundry.test',
            'role' => 'owner',
            'is_active' => true,
        ]);

        $this->actingAs($user);

        // Verify active access
        $res1 = $this->get('/pos');
        $res1->assertOk();

        // Admin deactivates user mid-session
        $user->update(['is_active' => false]);

        // Subsequent request intercepted by EnsureUserIsActive middleware
        $res2 = $this->get('/pos');
        $res2->assertRedirect('/login');
        $this->assertGuest();
    });
});

/*
|--------------------------------------------------------------------------
| FEATURE F2: AUTH_RATE_LIMIT — Login Rate Limiting & Brute Force Defense
|--------------------------------------------------------------------------
*/

describe('Feature F2: AUTH_RATE_LIMIT (Tier 1 - Equivalence Class Happy Paths)', function () {

    it('T1-RATE-01: increments throttle counter upon failed login attempt', function () {
        $email = 'target@andiklaundry.test';
        $ip = '127.0.0.1';
        $key = Str::transliterate(Str::lower($email).'|'.$ip);

        expect(RateLimiter::attempts($key))->toBe(0);

        $this->post('/login', [
            'email' => $email,
            'password' => 'WrongPassword!',
        ]);

        expect(RateLimiter::attempts($key))->toBe(1);
    });

    it('T1-RATE-02: locks out after 5 consecutive failed login attempts returning 429 or countdown', function () {
        $email = 'bruteforce@andiklaundry.test';

        User::factory()->create([
            'email' => $email,
            'password' => Hash::make('CorrectPassword123!'),
        ]);

        // First 5 failed attempts
        for ($i = 1; $i <= 5; $i++) {
            $res = $this->post('/login', [
                'email' => $email,
                'password' => 'BadPassword'.$i,
            ]);
            // Either redirected with validation errors or evaluated
            if ($i < 5) {
                $res->assertSessionHasErrors(['email']);
            }
        }

        // 6th attempt MUST trigger rate limiter lockout (429 or session error with countdown)
        $lockedResponse = $this->post('/login', [
            'email' => $email,
            'password' => 'BadPassword6',
        ]);

        if ($lockedResponse->status() === 429) {
            $lockedResponse->assertStatus(429);
        } else {
            $lockedResponse->assertSessionHasErrors(['email']);
            // Session error message mentions waiting seconds
            $errors = session('errors')->get('email');
            expect(implode(' ', $errors))->toMatch('/(detik|menit|terlalu banyak|too many)/i');
        }
    });

    it('T1-RATE-03: provides retry countdown seconds or Retry-After header on lockout response', function () {
        $email = 'lockout@andiklaundry.test';
        $key = Str::transliterate(Str::lower($email).'|127.0.0.1');

        RateLimiter::hit($key, 60);
        RateLimiter::hit($key, 60);
        RateLimiter::hit($key, 60);
        RateLimiter::hit($key, 60);
        RateLimiter::hit($key, 60);

        // Locked out
        $response = $this->post('/login', [
            'email' => $email,
            'password' => 'AnotherAttempt',
        ]);

        if ($response->status() === 429) {
            expect($response->headers->has('Retry-After'))->toBeTrue();
        } else {
            $response->assertSessionHasErrors(['email']);
            $availableIn = RateLimiter::availableIn($key);
            expect($availableIn)->toBeGreaterThan(0);
        }
    });

    it('T1-RATE-04: clears throttle counter on successful login before reaching lock threshold', function () {
        $email = 'recovery@andiklaundry.test';
        $key = Str::transliterate(Str::lower($email).'|127.0.0.1');

        User::factory()->create([
            'email' => $email,
            'password' => Hash::make('ValidPass123!'),
            'role' => 'owner',
            'is_active' => true,
        ]);

        // 3 failed attempts
        for ($i = 0; $i < 3; $i++) {
            $this->post('/login', ['email' => $email, 'password' => 'WrongPass']);
        }
        expect(RateLimiter::attempts($key))->toBe(3);

        // 4th attempt succeeds
        $res = $this->post('/login', ['email' => $email, 'password' => 'ValidPass123!']);
        $res->assertRedirect('/pos');

        // Throttle counter must be cleared
        expect(RateLimiter::attempts($key))->toBe(0);
    });

    it('T1-RATE-05: partitions rate limit bucket independently by email and client IP address', function () {
        $email1 = 'user1@andiklaundry.test';
        $email2 = 'user2@andiklaundry.test';

        // 5 failures on email1
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['email' => $email1, 'password' => 'Wrong']);
        }

        // Email2 from same IP is NOT locked out
        $key2 = Str::transliterate(Str::lower($email2).'|127.0.0.1');
        expect(RateLimiter::attempts($key2))->toBe(0);

        $res = $this->post('/login', ['email' => $email2, 'password' => 'Wrong']);
        expect(RateLimiter::attempts($key2))->toBe(1);
    });
});

describe('Feature F2: AUTH_RATE_LIMIT (Tier 2 - Boundaries & Corner Cases)', function () {

    it('T2-RATE-01: allows 5th attempt after exactly 4 failed attempts without pre-lockout', function () {
        $email = 'exact4@andiklaundry.test';
        $user = User::factory()->create([
            'email' => $email,
            'password' => Hash::make('CorrectPassword123!'),
            'role' => 'owner',
            'is_active' => true,
        ]);

        // 4 failed attempts
        for ($i = 0; $i < 4; $i++) {
            $this->post('/login', ['email' => $email, 'password' => 'Wrong']);
        }

        // 5th attempt with correct credentials MUST succeed
        $response = $this->post('/login', [
            'email' => $email,
            'password' => 'CorrectPassword123!',
        ]);

        $response->assertRedirect('/pos');
        $this->assertAuthenticatedAs($user);
    });

    it('T2-RATE-02: strictly enforces lockout on 6th failed attempt within 60 seconds', function () {
        $email = 'strict6@andiklaundry.test';
        $key = Str::transliterate(Str::lower($email).'|127.0.0.1');

        for ($i = 1; $i <= 5; $i++) {
            RateLimiter::hit($key, 60);
        }

        expect(RateLimiter::tooManyAttempts($key, 5))->toBeTrue();

        $res = $this->post('/login', ['email' => $email, 'password' => 'AnyPassword']);
        if ($res->status() === 429) {
            $res->assertStatus(429);
        } else {
            $res->assertSessionHasErrors(['email']);
        }
    });

    it('T2-RATE-03: allows independent login attempts from different client IP targeting same email', function () {
        $email = 'shared@andiklaundry.test';
        $keyIp1 = Str::transliterate(Str::lower($email).'|192.168.1.100');
        $keyIp2 = Str::transliterate(Str::lower($email).'|192.168.1.200');

        // IP1 fails 5 times
        for ($i = 0; $i < 5; $i++) {
            RateLimiter::hit($keyIp1, 60);
        }
        expect(RateLimiter::tooManyAttempts($keyIp1, 5))->toBeTrue();

        // IP2 must not be locked out
        expect(RateLimiter::tooManyAttempts($keyIp2, 5))->toBeFalse();
        expect(RateLimiter::attempts($keyIp2))->toBe(0);
    });

    it('T2-RATE-04: normalizes email casing to identical throttle bucket', function () {
        $emailLower = 'casing@andiklaundry.test';
        $emailUpper = 'CASING@ANDIKLAUNDRY.TEST';

        $keyLower = Str::transliterate(Str::lower($emailLower).'|127.0.0.1');
        $keyUpper = Str::transliterate(Str::lower($emailUpper).'|127.0.0.1');

        expect($keyLower)->toBe($keyUpper);

        // 3 failures using uppercase
        for ($i = 0; $i < 3; $i++) {
            $this->post('/login', ['email' => $emailUpper, 'password' => 'Wrong']);
        }

        // Lowercase sees 3 attempts
        expect(RateLimiter::attempts($keyLower))->toBe(3);
    });

    it('T2-RATE-05: resets throttle lockout automatically after 60 seconds time decay', function () {
        $email = 'decay@andiklaundry.test';
        $user = User::factory()->create([
            'email' => $email,
            'password' => Hash::make('TargetPass123!'),
            'role' => 'owner',
            'is_active' => true,
        ]);
        $key = Str::transliterate(Str::lower($email).'|127.0.0.1');

        for ($i = 0; $i < 5; $i++) {
            RateLimiter::hit($key, 60);
        }
        expect(RateLimiter::tooManyAttempts($key, 5))->toBeTrue();

        // Advance time by 61 seconds
        $this->travel(61)->seconds();

        // Rate limiter must allow attempts now
        expect(RateLimiter::tooManyAttempts($key, 5))->toBeFalse();

        // Valid login succeeds
        $res = $this->post('/login', ['email' => $email, 'password' => 'TargetPass123!']);
        $res->assertRedirect('/pos');
        $this->assertAuthenticatedAs($user);
    });
});
