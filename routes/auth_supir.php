<?php

use App\Http\Controllers\Auth\LoginController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Supir\SupirDashboardController;

Route::prefix('supir')->name('supir.')->group(function () {
    // Auth
    Route::middleware(['auth', 'role:Supir'])->group(function () {
        // Dashboard supir
        Route::get('/dashboard', [SupirDashboardController::class, 'index'])->name('dashboard');
        Route::post('/supir/status', [SupirDashboardController::class, 'updateStatus'])
            ->name('updateStatus');
        Route::post('/supir/job-accept', [SupirDashboardController::class, 'acceptJob'])
            ->name('acceptJob');
        // Tambah route supir lainnya di sini
    });
});
