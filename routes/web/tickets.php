<?php

use App\Http\Controllers\TicketsController;
use Illuminate\Support\Facades\Route;

Route::prefix('tickets')->name('tickets.')->group(function () {
    Route::get('/', [TicketsController::class, 'index'])->name('index');
    Route::get('create', [TicketsController::class, 'create'])->name('create');
    Route::post('/', [TicketsController::class, 'store'])->name('store')->middleware('throttle:20,1');
    Route::get('my-tickets', [TicketsController::class, 'myTickets'])->name('my-tickets');
});

Route::prefix('support')
    ->name('support.')
    ->middleware('role:support,admin')
    ->group(function () {
        Route::get('queue', [TicketsController::class, 'queue'])->name('queue');
        Route::get('all-tickets', [TicketsController::class, 'index'])->name('all-tickets');
        Route::get('my-tickets', [TicketsController::class, 'myTickets'])->name('my-tickets');
        Route::put('ticket/{ticket}/assign', [TicketsController::class, 'assign'])->name('assign');
        Route::put('ticket/{ticket}/unassign', [TicketsController::class, 'unassign'])->name('unassign');
        Route::put('ticket/{ticket}/status', [TicketsController::class, 'changeStatus'])->name('update-status');
        Route::put('ticket/{ticket}/priority', [TicketsController::class, 'changePriority'])->name('update-priority');
        Route::put('ticket/{ticket}/category', [TicketsController::class, 'changeCategory'])->name('update-category');
        Route::put('ticket/{ticket}/resolve', [TicketsController::class, 'resolve'])->name('resolve');
        Route::put('ticket/{ticket}/close', [TicketsController::class, 'close'])->name('close');
        Route::put('ticket/{ticket}/reopen', [TicketsController::class, 'reopen'])->name('reopen');
    });

Route::prefix('ticket')->name('tickets.')->group(function () {
    Route::get('{ticket}', [TicketsController::class, 'show'])->name('show');
    Route::post('{ticket}/reply', [TicketsController::class, 'reply'])->name('reply')->middleware('throttle:30,1');
    Route::post('{ticket}/internal-note', [TicketsController::class, 'internalNote'])->name('internal-note')->middleware('throttle:30,1');
    Route::get('{ticket}/attachment/{attachment}', [TicketsController::class, 'download'])->name('download');
});
