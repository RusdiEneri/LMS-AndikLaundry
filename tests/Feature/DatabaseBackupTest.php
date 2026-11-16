<?php

use App\Http\Controllers\Owner\DashboardController;
use App\Http\Controllers\Owner\OwnerDashboardController;
use App\Models\BusinessSetting;
use App\Models\Customer;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\RateLimiter;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->backupDir = storage_path('app/backups');
    File::ensureDirectoryExists($this->backupDir);

    // Clean up any existing test backup archives before each test
    foreach (File::files($this->backupDir) as $file) {
        if ($file->getFilename() !== '.gitignore') {
            File::delete($file->getPathname());
        }
    }
});

afterEach(function () {
    // Clean up after tests run
    if (isset($this->backupDir) && File::exists($this->backupDir)) {
        foreach (File::files($this->backupDir) as $file) {
            if ($file->getFilename() !== '.gitignore') {
                File::delete($file->getPathname());
            }
        }
    }
});

describe('Phase 7: Database Backup Command & Retention', function () {

    it('T-BAK-01: creates a gzipped database dump in storage/app/backups with positive file size and exit code 0', function () {
        // Seed some data to ensure non-empty database dump
        BusinessSetting::set('company.name', 'Andik Laundry Express');
        Customer::factory()->create(['name' => 'Budi Santoso']);

        $exitCode = $this->artisan('db:backup')->run();
        expect($exitCode)->toBe(0);

        $backupFiles = array_filter(
            File::files($this->backupDir),
            fn ($file) => str_ends_with($file->getFilename(), '.sql.gz')
        );

        expect($backupFiles)->not->toBeEmpty();

        $latestBackup = array_values($backupFiles)[0];
        expect(File::size($latestBackup->getPathname()))->toBeGreaterThan(0);
        expect(File::exists($this->backupDir.'/.gitignore'))->toBeTrue();
    });

    it('T-BAK-02: uncompresses backup using gzdecode and asserts valid SQL schema and table text content', function () {
        BusinessSetting::set('company.name', 'Andik Laundry Express');
        User::factory()->create(['name' => 'Pak Andik Owner', 'email' => 'owner.test@andiklaundry.test']);

        $exitCode = $this->artisan('db:backup', ['--filename' => 'test-schema-verify.sql.gz'])->run();
        expect($exitCode)->toBe(0);

        $backupPath = $this->backupDir.'/test-schema-verify.sql.gz';
        expect(File::exists($backupPath))->toBeTrue();

        $compressedData = File::get($backupPath);
        $sqlContent = gzdecode($compressedData);

        expect($sqlContent)->toBeString();
        expect(strlen($sqlContent))->toBeGreaterThan(50);

        // Verify valid SQL schema keywords
        expect($sqlContent)->toMatch('/CREATE TABLE/i');
        expect($sqlContent)->toContain('users');
    });

    it('T-BAK-03: cleans up backups older than retention days while preserving recent ones', function () {
        $oldFile = $this->backupDir.'/backup-old-test.sql.gz';
        $recentFile = $this->backupDir.'/backup-recent-test.sql.gz';

        // Create dummy gzipped files
        File::put($oldFile, gzencode('-- old backup', 9));
        File::put($recentFile, gzencode('-- recent backup', 9));

        // Set modification time: old file 35 days ago, recent file 5 days ago
        touch($oldFile, Carbon::now()->subDays(35)->getTimestamp());
        touch($recentFile, Carbon::now()->subDays(5)->getTimestamp());

        expect(File::exists($oldFile))->toBeTrue();
        expect(File::exists($recentFile))->toBeTrue();

        // Run db:backup with default 30-day retention
        $exitCode = $this->artisan('db:backup', ['--retention' => 30])->run();
        expect($exitCode)->toBe(0);

        // Assert old backup is pruned and recent backup is retained
        expect(File::exists($oldFile))->toBeFalse();
        expect(File::exists($recentFile))->toBeTrue();
    });

    it('T-BAK-04: verifies db:backup is registered in Schedule with daily frequency at 02:00', function () {
        $schedule = app(Schedule::class);
        $events = collect($schedule->events());

        $backupEvent = $events->first(function ($event) {
            return str_contains($event->command ?? '', 'db:backup');
        });

        expect($backupEvent)->not->toBeNull();
        expect($backupEvent->expression)->toBe('0 2 * * *');
        expect($backupEvent->description)->toContain('Daily database backup');
    });
});

describe('Phase 7: Security Headers Middleware Hardening', function () {

    it('T-SEC-01: sets standard security headers on HTTP responses', function () {
        $response = $this->get('/login');

        $response->assertSuccessful();
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('X-XSS-Protection', '1; mode=block');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        $csp = $response->headers->get('Content-Security-Policy');
        expect($csp)->toContain("default-src 'self'");
        expect($csp)->toContain("frame-ancestors 'self'");
    });

    it('T-SEC-01b: sets Strict-Transport-Security header when request is HTTPS secure', function () {
        $response = $this->get('https://localhost/login');

        $response->assertSuccessful();
        $response->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
    });
});

describe('Phase 7: Public Digital Receipt Rate Limiting & Aliasing', function () {

    it('T-SEC-02: enforces rate limiting of 60 requests per minute by IP on receipts endpoint', function () {
        RateLimiter::clear('receipts');

        $transaction = Transaction::factory()->create([
            'invoice_number' => 'INV-RATE-TEST-001',
        ]);

        $ip = '192.168.1.100';

        // 60 requests should pass without 429
        for ($i = 1; $i <= 60; $i++) {
            $response = $this->withServerVariables(['REMOTE_ADDR' => $ip])
                ->get("/receipts/{$transaction->invoice_number}");

            expect($response->status())->not->toBe(429);
        }

        // 61st request from same IP must be throttled with 429 Too Many Requests
        $blockedResponse = $this->withServerVariables(['REMOTE_ADDR' => $ip])
            ->get("/receipts/{$transaction->invoice_number}");

        expect($blockedResponse->status())->toBe(429);
    });

    it('T-SEC-02b: supports /receipt/{orderNumber} route alias and shares rate limiting', function () {
        RateLimiter::clear('receipts');

        $transaction = Transaction::factory()->create([
            'invoice_number' => 'INV-ALIAS-TEST-002',
        ]);

        $aliasResponse = $this->get("/receipt/{$transaction->invoice_number}");
        $aliasResponse->assertSuccessful();

        $ip = '192.168.1.200';

        // Exhaust limit via alias route
        for ($i = 1; $i <= 60; $i++) {
            $response = $this->withServerVariables(['REMOTE_ADDR' => $ip])
                ->get("/receipt/{$transaction->invoice_number}");

            expect($response->status())->not->toBe(429);
        }

        // 61st request throttled
        $blockedResponse = $this->withServerVariables(['REMOTE_ADDR' => $ip])
            ->get("/receipt/{$transaction->invoice_number}");

        expect($blockedResponse->status())->toBe(429);
    });
});

describe('OwnerDashboardController compatibility alias', function () {

    it('instantiates OwnerDashboardController and extends DashboardController', function () {
        $controller = new OwnerDashboardController;
        expect($controller)->toBeInstanceOf(DashboardController::class);
    });
});
