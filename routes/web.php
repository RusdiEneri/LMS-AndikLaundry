<?php

use App\Enums\ProductionStatus;
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
use App\Models\BusinessSetting;
use App\Models\Transaction;
use Illuminate\Foundation\Http\Middleware\TrimStrings;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

TrimStrings::except([
    'settings.*.value',
]);

Route::get('/', function () {
    if (Auth::check()) {
        $role = Auth::user()?->role;

        return match ($role) {
            'cashier' => redirect('/pos'),
            'operator' => redirect('/production/station'),
            default => redirect('/dashboard'),
        };
    }

    // Statistik publik landing page: hitungan riil dari database + basis dummy
    // agar toko yang baru beroperasi tetap tampil kredibel.
    $customerCount = 0;
    $orderCount = 0;
    $laundryKg = 0.0;

    try {
        $customerCount = (int) Transaction::where('production_status', '!=', ProductionStatus::CANCELLED->value)
            ->distinct('customer_id')
            ->count('customer_id');
        $orderCount = (int) Transaction::where('production_status', '!=', ProductionStatus::CANCELLED->value)->count();
        $laundryKg = (float) Transaction::join('transaction_items', 'transaction_items.transaction_id', '=', 'transactions.id')
            ->where('transaction_items.pricing_unit_snapshot', 'kg')
            ->where('transactions.production_status', '!=', ProductionStatus::CANCELLED->value)
            ->sum('transaction_items.actual_quantity');
    } catch (Throwable) {
        // Fallback anggun ke basis angka jika tabel belum termigrasi.
    }

    $storeName = 'Andik Laundry';
    $storePhone = '6281234567890';
    $storeAddress = 'Hendrosari, RT.06/RW.03, Hendrosari, Kec. Menganti, Kabupaten Gresik, Jawa Timur 61174';

    try {
        $storeName = BusinessSetting::get('store.name', $storeName);
        $storePhone = BusinessSetting::get('store.phone', $storePhone);
        $storeAddress = BusinessSetting::get('store.address', $storeAddress);
    } catch (Throwable) {
        // Fallback default jika tabel settings belum termigrasi.
    }

    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'stats' => [
            'customers' => 1250 + $customerCount,
            'orders' => 3400 + $orderCount,
            'laundry_kg' => 8200 + (int) floor($laundryKg),
        ],
        'store' => [
            'name' => $storeName,
            'phone' => $storePhone,
            'address' => $storeAddress,
        ],
    ]);
});

Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LogoutController::class, 'logout'])->name('logout');

Route::get('/nota/{invoice_number?}', [ReceiptController::class, 'show'])
    ->middleware('throttle:receipts')
    ->name('nota.show');

Route::get('/receipts/{invoice_number?}', [ReceiptController::class, 'show'])
    ->middleware('throttle:receipts')
    ->name('receipts.show');

Route::get('/receipt/{orderNumber?}', [ReceiptController::class, 'show'])
    ->middleware('throttle:receipts')
    ->name('receipt.show');

Route::middleware(['auth'])->group(function () {
    Route::middleware(['role:owner'])->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/owner/reports/export-csv', [DashboardController::class, 'exportCsv'])->name('owner.reports.export-csv');

        Route::get('/owner/users', [UserController::class, 'index'])->name('owner.users.index');
        Route::post('/owner/users', [UserController::class, 'store'])->name('owner.users.store');
        Route::get('/owner/users/{user}', fn () => abort(404));
        Route::put('/owner/users/{user}', [UserController::class, 'update'])->name('owner.users.update');
        Route::delete('/owner/users/{user}', [UserController::class, 'destroy'])->name('owner.users.destroy');
        Route::patch('/owner/users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('owner.users.toggle-status');

        Route::get('/owner/settings', [SettingController::class, 'index'])->name('owner.settings.index');
        Route::put('/owner/settings', [SettingController::class, 'update'])->name('owner.settings.update');
        Route::post('/owner/settings/whatsapp/test-connection', [SettingController::class, 'testWhatsAppConnection'])->name('owner.settings.whatsapp.test-connection');
        Route::post('/owner/settings/whatsapp/test-message', [SettingController::class, 'sendWhatsAppTestMessage'])->name('owner.settings.whatsapp.test-message');
        Route::post('/owner/settings/whatsapp/connect-session', [SettingController::class, 'connectWhatsAppSession'])->name('owner.settings.whatsapp.connect-session');
        Route::post('/owner/settings/whatsapp/disconnect-session', [SettingController::class, 'disconnectWhatsAppSession'])->name('owner.settings.whatsapp.disconnect-session');

        Route::get('/owner/expenses', [ExpenseController::class, 'index'])->name('owner.expenses.index');
        Route::get('/expenses', [ExpenseController::class, 'index'])->name('expenses.index');
        Route::post('/owner/expenses', [ExpenseController::class, 'store'])->name('owner.expenses.store');
        Route::post('/expenses', [ExpenseController::class, 'store']);
        Route::delete('/owner/expenses/{expense}', [ExpenseController::class, 'destroy'])->name('owner.expenses.destroy');

        Route::get('/owner/daily-recap', [DailyRecapController::class, 'index'])->name('owner.daily-recap.index');
        Route::get('/daily-recap', [DailyRecapController::class, 'index'])->name('daily-recap.index');
        Route::get('/api/daily-recap/print', [DailyRecapController::class, 'printData'])->name('api.daily-recap.print');

        Route::get('/owner/receivables', [ReceivableController::class, 'index'])->name('owner.receivables.index');
        Route::get('/receivables', [ReceivableController::class, 'index'])->name('receivables.index');
        Route::get('/api/receivables/{id}/reminder-url', [ReceivableController::class, 'getReminderUrl'])->name('api.receivables.reminder');
    });

    Route::get('/pos', [PosController::class, 'index'])->middleware('role:owner,cashier')->name('pos.index');

    Route::get('/api/pos/customers/search', [CustomerController::class, 'search'])->name('pos.customers.search');
    Route::get('/pos/customers/search', [CustomerController::class, 'search']);
    Route::post('/api/pos/customers', [CustomerController::class, 'store'])->name('pos.customers.store');
    Route::post('/pos/customers', [CustomerController::class, 'store']);

    Route::post('/api/pos/transactions', [TransactionController::class, 'store'])->name('pos.transactions.store');
    Route::get('/api/pos/transactions/{id}', [TransactionController::class, 'show'])->name('pos.transactions.show');
    Route::post('/api/pos/orders', [TransactionController::class, 'store'])->name('pos.orders.store');
    Route::post('/pos/orders', [TransactionController::class, 'store']);
    Route::get('/api/pos/orders/{id}', [TransactionController::class, 'show'])->name('pos.orders.show');
    Route::get('/pos/orders/{id}', [TransactionController::class, 'show']);

    Route::post('/pos/orders/{id}/payments', [PaymentController::class, 'store'])->name('pos.payments.store');
    Route::post('/api/pos/orders/{id}/payments', [PaymentController::class, 'store']);
    Route::post('/api/pos/transactions/{id}/payments', [PaymentController::class, 'store']);

    Route::get('/production/station', [StationController::class, 'index'])->middleware('role:owner,operator')->name('production.station');

    Route::patch('/production/orders/{id}/status', [ProductionStatusController::class, 'updateStatus'])->name('production.orders.status');
    Route::patch('/api/production/orders/{id}/status', [ProductionStatusController::class, 'updateStatus']);
});
