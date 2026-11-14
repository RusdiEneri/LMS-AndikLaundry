<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Owner\DailyRecapController;
use App\Http\Controllers\Owner\DashboardController;
use App\Http\Controllers\Owner\ExpenseController;
use App\Http\Controllers\Owner\ReceivableController;
use App\Http\Controllers\Owner\SettingController;
use App\Http\Controllers\Owner\UserController;
use App\Http\Controllers\Pos\CustomerController;
use App\Http\Controllers\Pos\PaymentController;
use App\Http\Controllers\Pos\PosController;
use App\Http\Controllers\Pos\ReceiptController;
use App\Http\Controllers\Pos\TransactionController;
use App\Http\Controllers\Production\ProductionStatusController;
use App\Http\Controllers\Production\StationController;
use Illuminate\Foundation\Http\Middleware\TrimStrings;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

TrimStrings::except([
    'settings.*.value',
]);

/*
|--------------------------------------------------------------------------
| Public & Guest Authentication Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    if (Auth::check()) {
        $user = Auth::user();

        return match ($user->role) {
            'owner' => redirect('/dashboard'),
            'cashier' => redirect('/pos'),
            'operator' => redirect('/production/station'),
            default => redirect('/dashboard'),
        };
    }

    return redirect('/login');
});

Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LogoutController::class, 'logout'])->name('logout');

// Public Digital Receipt & Live Status Tracker
Route::get('/receipts/{invoice_number}', [ReceiptController::class, 'show'])
    ->middleware('throttle:receipts')
    ->name('receipts.show');

Route::get('/receipt/{orderNumber}', [ReceiptController::class, 'show'])
    ->middleware('throttle:receipts')
    ->name('receipt.show');

/*
|--------------------------------------------------------------------------
| Authenticated & Role-Protected Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth'])->group(function () {

    // Owner Only Routes
    Route::middleware(['role:owner'])->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/owner/reports/export-csv', [DashboardController::class, 'exportCsv'])->name('owner.reports.export-csv');

        // User Management Routes (Feature F4)
        Route::get('/owner/users', [UserController::class, 'index'])->name('owner.users.index');
        Route::post('/owner/users', [UserController::class, 'store'])->name('owner.users.store');
        Route::get('/owner/users/{user}', fn () => abort(404));
        Route::put('/owner/users/{user}', [UserController::class, 'update'])->name('owner.users.update');
        Route::delete('/owner/users/{user}', [UserController::class, 'destroy'])->name('owner.users.destroy');
        Route::patch('/owner/users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('owner.users.toggle-status');

        // Business Settings Routes (Feature F6)
        Route::get('/owner/settings', [SettingController::class, 'index'])->name('owner.settings.index');
        Route::put('/owner/settings', [SettingController::class, 'update'])->name('owner.settings.update');
        Route::post('/owner/settings/whatsapp/test-connection', [SettingController::class, 'testWhatsAppConnection'])->name('owner.settings.whatsapp.test-connection');
        Route::post('/owner/settings/whatsapp/test-message', [SettingController::class, 'sendWhatsAppTestMessage'])->name('owner.settings.whatsapp.test-message');

        // Operational Expenses (Phase 3)
        Route::get('/owner/expenses', [ExpenseController::class, 'index'])->name('owner.expenses.index');
        Route::get('/expenses', [ExpenseController::class, 'index'])->name('expenses.index');
        Route::post('/owner/expenses', [ExpenseController::class, 'store'])->name('owner.expenses.store');
        Route::post('/expenses', [ExpenseController::class, 'store']);
        Route::delete('/owner/expenses/{expense}', [ExpenseController::class, 'destroy'])->name('owner.expenses.destroy');

        // Daily Cash Recap (Rekap Kas Harian - Phase 3)
        Route::get('/owner/daily-recap', [DailyRecapController::class, 'index'])->name('owner.daily-recap.index');
        Route::get('/daily-recap', [DailyRecapController::class, 'index'])->name('daily-recap.index');
        Route::get('/api/daily-recap/print', [DailyRecapController::class, 'printData'])->name('api.daily-recap.print');

        // Customer Receivables (Buku Piutang - Phase 6)
        Route::get('/owner/receivables', [ReceivableController::class, 'index'])->name('owner.receivables.index');
        Route::get('/receivables', [ReceivableController::class, 'index'])->name('receivables.index');
        Route::get('/api/receivables/{id}/reminder-url', [ReceivableController::class, 'getReminderUrl'])->name('api.receivables.reminder');
    });

    // Cashier & Owner Routes
    Route::middleware(['role:cashier,owner'])->group(function () {
        Route::get('/pos', [PosController::class, 'index'])->name('pos.index');

        // Customer Management (POS API)
        Route::get('/api/pos/customers/search', [CustomerController::class, 'search'])->name('pos.customers.search');
        Route::get('/pos/customers/search', [CustomerController::class, 'search']);
        Route::post('/api/pos/customers', [CustomerController::class, 'store'])->name('pos.customers.store');
        Route::post('/pos/customers', [CustomerController::class, 'store']);

        // Transaction & Order Management (POS API)
        Route::post('/api/pos/transactions', [TransactionController::class, 'store'])->name('pos.transactions.store');
        Route::get('/api/pos/transactions/{id}', [TransactionController::class, 'show'])->name('pos.transactions.show');
        Route::post('/api/pos/orders', [TransactionController::class, 'store'])->name('pos.orders.store');
        Route::post('/pos/orders', [TransactionController::class, 'store']);
        Route::get('/api/pos/orders/{id}', [TransactionController::class, 'show'])->name('pos.orders.show');
        Route::get('/pos/orders/{id}', [TransactionController::class, 'show']);

        // Payment Processing (POS API)
        Route::post('/pos/orders/{id}/payments', [PaymentController::class, 'store'])->name('pos.payments.store');
        Route::post('/api/pos/orders/{id}/payments', [PaymentController::class, 'store']);
        Route::post('/api/pos/transactions/{id}/payments', [PaymentController::class, 'store']);
    });

    // Operator & Owner Routes
    Route::middleware(['role:operator,owner'])->group(function () {
        Route::get('/production/station', [StationController::class, 'index'])->name('production.station');
    });

    // Production Status Updates (Operator, Cashier & Owner)
    Route::middleware(['role:operator,cashier,owner'])->group(function () {
        Route::patch('/production/orders/{id}/status', [ProductionStatusController::class, 'updateStatus'])->name('production.orders.status');
        Route::patch('/api/production/orders/{id}/status', [ProductionStatusController::class, 'updateStatus']);
    });

});
