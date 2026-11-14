<?php

use App\Http\Controllers\SuperAdmin\ApprovalController;
use App\Http\Controllers\SuperAdmin\UserController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SuperAdmin\DashboardController;


// ✅ Route setelah login
Route::prefix('superadmin')->name('superadmin.')->middleware(['auth', 'role:SuperAdmin'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::resource('users', UserController::class)->except(['show']);
    Route::get('/approvals', [ApprovalController::class, 'index'])->name('approvals.index');
    Route::post('/approvals/{id}/approve', [ApprovalController::class, 'approve'])->name('approvals.approve');
    Route::post('/approvals/{id}/reject', [ApprovalController::class, 'reject'])->name('approvals.reject');
    Route::get('/superadmin/laporan', [DashboardController::class, 'listLaporan'])->name('listLaporan');
});
