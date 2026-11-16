<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // 1. Owner Super-Admin Bypass via Gate::before
        Gate::before(function (User $user, string $ability) {
            if ($user->role === 'owner') {
                return true;
            }

            return null;
        });

        // 2. Register 23 Canonical Permission Gates
        $permissions = [
            'pos.orders.create',
            'pos.orders.view',
            'pos.orders.cancel',
            'pos.orders.override_price',
            'pos.orders.release_unpaid',
            'payments.create',
            'payments.refund',
            'shifts.open',
            'shifts.close',
            'shifts.cash_drop',
            'shifts.petty_expense',
            'shifts.view_all',
            'production.status.update',
            'production.rewash.flag',
            'production.rack.assign',
            'customers.manage',
            'catalog.manage',
            'expenses.create',
            'expenses.manage',
            'reports.financial.view',
            'reports.operational.view',
            'system.settings.manage',
            'system.audit.view',
        ];

        foreach ($permissions as $permission) {
            Gate::define($permission, function (User $user) use ($permission) {
                return $user->hasPermission($permission);
            });
        }

        // 3. Configure Public Digital Receipt Rate Limiting (60 requests/minute per IP)
        RateLimiter::for('receipts', function (Request $request) {
            return Limit::perMinute(60)->by($request->ip());
        });
    }
}
