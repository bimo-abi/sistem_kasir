<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\TransactionController;
use Illuminate\Support\Facades\Route;

// Redirect root URL to Login or Dashboard
Route::get('/', function () {
    return redirect()->route('login');
});

// Guest Routes (Autentikasi)
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);

    Route::get('/register', [RegisterController::class, 'showRegisterForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register']);
});

// Authenticated Routes (Harus Login)
Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Route Khusus Admin (CRUD Produk)
    Route::middleware('role:admin')->prefix('admin')->as('admin.')->group(function () {
        Route::resource('products', ProductController::class);
    });

    // Route Kasir & Admin (POS & Transaksi)
    Route::middleware('role:admin,kasir')->prefix('kasir')->as('kasir.')->group(function () {
        Route::get('/', [PosController::class, 'index'])->name('pos');
        Route::get('/api/products/search', [ProductController::class, 'apiSearch'])->name('products.search');

        // Checkout & Transaksi Ditahan (Tahap 7 & 8)
        Route::post('/checkout', [TransactionController::class, 'store'])->name('checkout.store');
        Route::post('/hold', [TransactionController::class, 'hold'])->name('checkout.hold');
        Route::get('/held', [TransactionController::class, 'heldIndex'])->name('held.index');
        Route::get('/held/resume/{id}', [TransactionController::class, 'resumeHeld'])->name('held.resume');
        Route::delete('/held/{id}', [TransactionController::class, 'cancelHeld'])->name('held.cancel');

        // Struk & Riwayat Transaksi (Tahap 9 & 10)
        Route::get('/receipt/{id}', [TransactionController::class, 'receipt'])->name('receipt');
        Route::get('/history', [TransactionController::class, 'history'])->name('history');
    });
});
