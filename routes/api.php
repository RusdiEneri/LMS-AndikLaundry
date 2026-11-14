<?php

use App\Http\Controllers\Owner\SettingController;
use App\Http\Controllers\Pos\PaymentController;
use App\Http\Controllers\Production\ProductionStatusController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::post('/pos/orders/{id}/payments', [PaymentController::class, 'store'])->name('api.pos.payments.store');
    Route::patch('/production/orders/{id}/status', [ProductionStatusController::class, 'updateStatus'])->name('api.production.orders.status');

    Route::middleware(['role:owner'])->group(function () {
        Route::post('/whatsapp/test-connection', [SettingController::class, 'testWhatsAppConnection'])->name('api.whatsapp.test-connection');
        Route::post('/whatsapp/test-message', [SettingController::class, 'sendWhatsAppTestMessage'])->name('api.whatsapp.test-message');
        Route::post('/owner/settings/whatsapp/test-connection', [SettingController::class, 'testWhatsAppConnection']);
        Route::post('/owner/settings/whatsapp/test-message', [SettingController::class, 'sendWhatsAppTestMessage']);
    });
});
