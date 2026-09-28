<?php

use App\Http\Controllers\AccountStatusController;
use App\Http\Controllers\Auth\ChangePasswordController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SearchController;
use Illuminate\Support\Facades\Route;

// ---------------------------------------------------------------------------
// Guests
// ---------------------------------------------------------------------------
Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('login', [LoginController::class, 'login'])->name('login.attempt');

    Route::get('register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('register', [RegisterController::class, 'register'])->name('register.attempt');

    // Signed, expiring URLs only: never reveal account status by guessing URLs.
    Route::get('account-status/{user}', [AccountStatusController::class, 'show'])
        ->middleware('signed')
        ->name('account.status');

    Route::get('forgot-password', [ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
    Route::post('forgot-password', [ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email')->middleware('throttle:5,1');

    Route::get('reset-password/{token?}', [ResetPasswordController::class, 'showResetForm'])
        ->where('token', '.*')
        ->name('password.reset');
    Route::post('reset-password', [ResetPasswordController::class, 'reset'])->name('password.store')->middleware('throttle:5,1');
});

// ---------------------------------------------------------------------------
// Authenticated users
// ---------------------------------------------------------------------------
Route::middleware(['auth', 'active', 'must.change.password'])->group(function () {
    Route::post('logout', [LoginController::class, 'logout'])->name('logout');

    Route::get('/', fn () => redirect()->route('dashboard'));
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/', [ProfileController::class, 'show'])->name('show');
        Route::put('/', [ProfileController::class, 'update'])->name('update');
    });

    Route::prefix('account')->name('profile.change-password')->group(function () {
        Route::get('/', [ChangePasswordController::class, 'showChangeForm']);
        Route::post('/', [ChangePasswordController::class, 'update'])->name('.store');
    });

    Route::get('search', [SearchController::class, 'index'])->name('search.index');
});

// ---------------------------------------------------------------------------
// Role-scoped feature routes (filled in later phases)
// ---------------------------------------------------------------------------
Route::middleware(['auth', 'active', 'must.change.password'])->group(function () {
    require __DIR__.'/web/tickets.php';
    require __DIR__.'/web/notifications.php';
    require __DIR__.'/web/admin.php';
    require __DIR__.'/web/reports.php';
});
