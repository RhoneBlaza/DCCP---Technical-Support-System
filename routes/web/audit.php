<?php

use App\Http\Controllers\Admin\AuditLogsController;
use Illuminate\Support\Facades\Route;

Route::prefix('audit-logs')->name('audit-logs.')->group(function () {
    Route::get('/', [AuditLogsController::class, 'index'])->name('index');
    Route::get('/feed', [AuditLogsController::class, 'feed'])->name('feed');
    Route::get('/export', [AuditLogsController::class, 'export'])->name('export');
});
