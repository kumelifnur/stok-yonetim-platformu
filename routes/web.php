<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\RegistrationController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\SessionController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'home')->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/kayit', [RegistrationController::class, 'create'])->name('register');
    Route::post('/kayit', [RegistrationController::class, 'store'])->name('register.store');
    Route::get('/giris', [SessionController::class, 'create'])->name('login');
    Route::post('/giris', [SessionController::class, 'store'])->middleware('throttle:6,1')->name('login.store');
});

Route::post('/cikis', [SessionController::class, 'destroy'])->middleware('auth')->name('logout');

Route::middleware(['auth', 'business.owner'])->prefix('panel')->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::post('/urunler', [ProductController::class, 'store'])->name('products.store');
    Route::put('/urunler/{product}', [ProductController::class, 'update'])->name('products.update');
    Route::post('/urunler/{product}/stok', [ProductController::class, 'adjustStock'])->name('products.stock');
    Route::get('/siparis/yeni', [SaleController::class, 'create'])->name('sales.create');
    Route::post('/siparis', [SaleController::class, 'store'])->name('sales.store');
    Route::patch('/siparis/{sale}/durum', [SaleController::class, 'updateStatus'])->name('sales.status');
});
