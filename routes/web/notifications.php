<?php

use App\Http\Controllers\NotificationsController;
use Illuminate\Support\Facades\Route;

Route::prefix('notifications')->name('notifications.')->group(function () {
    Route::get('/', [NotificationsController::class, 'index'])->name('index');
    Route::get('recent', [NotificationsController::class, 'recent'])->name('recent');
    Route::post('read-all', [NotificationsController::class, 'markAllRead'])->name('read-all');
    Route::post('{notification}/read', [NotificationsController::class, 'markRead'])->name('read');
});
