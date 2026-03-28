<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\TradingController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PushSubscriptionController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('dashboard'));

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Companies / watchlist
    Route::get('/companies', [CompanyController::class, 'index'])->name('companies.index');
    Route::post('/companies', [CompanyController::class, 'store'])->name('companies.store');
    Route::delete('/companies/{symbol}', [CompanyController::class, 'destroy'])->name('companies.destroy');
    Route::patch('/companies/{symbol}/favorite', [CompanyController::class, 'toggleFavorite'])->name('companies.favorite');

    // Trading
    Route::post('/orders', [TradingController::class, 'place'])->name('orders.place');
    Route::get('/orders', [TradingController::class, 'history'])->name('orders.history');

    // API endpoints
    Route::prefix('api')->group(function () {
        Route::get('/symbols/search', [CompanyController::class, 'search'])->name('api.symbols.search');
        Route::get('/quotes/{symbol}', [CompanyController::class, 'quote'])->name('api.quotes');
        Route::get('/account', [TradingController::class, 'account'])->name('api.account');

        // Push notifications
        Route::get('/push/vapid-key', [PushSubscriptionController::class, 'vapidKey'])->name('api.push.vapid-key');
        Route::post('/push/subscribe', [PushSubscriptionController::class, 'store'])->name('api.push.subscribe');
        Route::post('/push/unsubscribe', [PushSubscriptionController::class, 'destroy'])->name('api.push.unsubscribe');
        Route::post('/push/response', [PushSubscriptionController::class, 'recordResponse'])->name('api.push.response');
    });
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
