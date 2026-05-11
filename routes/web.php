<?php

use App\Http\Controllers\Admin\ImportController as AdminImportController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ListingController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProfitLossController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('dashboard'));

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::resource('listings', ListingController::class)->except('show');
    Route::resource('orders', OrderController::class)->except('show');
    Route::get('/profit-loss', [ProfitLossController::class, 'index'])->name('profit-loss.index');
    Route::post('/orders/import', [OrderController::class, 'import'])->name('orders.import');
});

Route::middleware(['auth', 'verified', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::resource('users', AdminUserController::class);
    Route::post('users/{user}/impersonate', [AdminUserController::class, 'impersonate'])->name('users.impersonate');
    Route::get('import', [AdminImportController::class, 'create'])->name('import.create');
    Route::post('import', [AdminImportController::class, 'store'])->name('import.store');
    Route::post('listings/{listing}/copy', [AdminImportController::class, 'copyToUsers'])->name('listings.copy');
});

// Stop impersonation must be outside the admin middleware so a client-impersonated session can reach it
Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
    Route::post('impersonate/stop', [AdminUserController::class, 'stopImpersonating'])->name('impersonate.stop');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::patch('/profile/currency', [ProfileController::class, 'updateCurrency'])->name('profile.currency');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
