<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\BreakTimeController;

Route::middleware(['auth'])->group(function () {
    Route::get('/', [AttendanceController::class, 'index'])->name('home');
    
    Route::prefix('attendance')->group(function () {
        Route::get('/', [AttendanceController::class, 'index'])->name('attendance.index');
        Route::post('/start', [AttendanceController::class, 'startWork'])->name('attendance.start');
        Route::post('/end', [AttendanceController::class, 'endWork'])->name('attendance.end');
        Route::get('/show', [AttendanceController::class, 'show'])->name('attendance.show');
    });

    Route::prefix('break')->group(function () {
        Route::post('/start', [BreakTimeController::class, 'startBreak'])->name('break.start');
        Route::post('/end', [BreakTimeController::class, 'endBreak'])->name('break.end');
    });
});