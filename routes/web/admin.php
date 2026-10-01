<?php

use App\Http\Controllers\Admin\CategoriesController;
use App\Http\Controllers\Admin\DepartmentsController;
use App\Http\Controllers\Admin\PrioritiesController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\TicketStatusesController;
use App\Http\Controllers\Admin\UsersController;
use App\Http\Controllers\Admin\VerificationRequestsController;
use Illuminate\Support\Facades\Route;

Route::middleware(['role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::resource('users', UsersController::class)->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy']);
    Route::patch('users/{user}/toggle-suspend', [UsersController::class, 'toggleSuspend'])->name('users.toggle-suspend');
    Route::patch('users/{user}/reset-password', [UsersController::class, 'resetPassword'])->name('users.reset-password');
    Route::patch('users/{user}/approve', [UsersController::class, 'approve'])->name('users.approve');
    Route::patch('users/{user}/reject', [UsersController::class, 'reject'])->name('users.reject');

    Route::resource('departments', DepartmentsController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::patch('departments/{department}/toggle-active', [DepartmentsController::class, 'toggleActive'])->name('departments.toggle-active');

    Route::resource('categories', CategoriesController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::patch('categories/{category}/toggle-active', [CategoriesController::class, 'toggleActive'])->name('categories.toggle-active');

    Route::resource('priorities', PrioritiesController::class)->only(['index', 'store', 'update']);
    Route::patch('priorities/{priority}/toggle-active', [PrioritiesController::class, 'toggleActive'])->name('priorities.toggle-active');

    Route::resource('statuses', TicketStatusesController::class)->only(['index', 'store', 'update']);
    Route::patch('statuses/{status}/toggle-active', [TicketStatusesController::class, 'toggleActive'])->name('statuses.toggle-active');

    Route::get('settings', [SettingsController::class, 'edit'])->name('settings.index');
    Route::put('settings', [SettingsController::class, 'update'])->name('settings.update');
    Route::post('settings/test-mail', [SettingsController::class, 'sendTestEmail'])->name('settings.test-mail');

    Route::resource('verifications', VerificationRequestsController::class)
        ->only(['index', 'show'])
        ->parameters(['verifications' => 'verification_request']);
    Route::get('verifications/{verification_request}/image', [VerificationRequestsController::class, 'image'])->name('verifications.image');
    Route::patch('verifications/{verification_request}/approve', [VerificationRequestsController::class, 'approve'])->name('verifications.approve');
    Route::patch('verifications/{verification_request}/reject', [VerificationRequestsController::class, 'reject'])->name('verifications.reject');
    Route::patch('verifications/{verification_request}/resubmit', [VerificationRequestsController::class, 'requestResubmission'])->name('verifications.resubmit');
});

Route::middleware(['role:admin'])->prefix('admin')->name('admin.')->group(function () {
    require __DIR__.'/audit.php';
});
