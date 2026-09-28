<?php

use App\Http\Controllers\ReportsController;
use Illuminate\Support\Facades\Route;

Route::prefix('reports')
    ->name('reports.')
    ->middleware('role:support,admin')
    ->group(function () {
        Route::get('/', [ReportsController::class, 'index'])->name('index');
        Route::get('export', [ReportsController::class, 'export'])->name('export');
    });
