<?php

use App\Http\Controllers\Admin\BookingController;
use App\Http\Controllers\Admin\LaporanController;
use App\Http\Controllers\Admin\MasterMobilController;
use App\Http\Controllers\Admin\PembayaranController;
use App\Http\Controllers\Admin\PengembalianController;
use App\Http\Controllers\Auth\LoginController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\MobilController;
use App\Http\Controllers\Admin\PelangganController;
use App\Http\Controllers\Admin\SupirController;
use App\Http\Controllers\Admin\TipeMobilController;


Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:Admin'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::resource('tipe-mobil', TipeMobilController::class);
    Route::resource('master-mobils', MasterMobilController::class)->except(['destroy']);
    Route::patch('/master-mobils/{id}/update-tipe', [MasterMobilController::class, 'updateTipe'])
        ->name('master-mobils.updateTipe');

    Route::resource('mobil', MobilController::class);
    Route::resource('supir', SupirController::class);
    Route::resource('pelanggan', PelangganController::class);
    Route::resource('booking', BookingController::class);
    Route::patch('/booking/{detail}/konfirmasi-jemput', [BookingController::class, 'konfirmasiJemput'])
        ->name('booking.konfirmasiJemput');
    Route::get('laporan/generate', [LaporanController::class, 'generateLaporanAdmin'])->name('laporan');
    Route::get('pembayaran', [PembayaranController::class, 'index'])->name('pembayaran.index');
    Route::put('pembayaran/{pembayaran}/verifikasi', [PembayaranController::class, 'verifikasi'])->name('pembayaran.verifikasi');
    Route::put('pembayaran/{pembayaran}/tolak', [PembayaranController::class, 'tolak'])->name('pembayaran.tolak');
    Route::resource('pengembalian', PengembalianController::class)
        ->only(['index', 'show', 'store', 'update']);
    Route::post('/mobil/update-status/{id}', [MobilController::class, 'updateStatus'])
        ->name('mobil.updateStatus');

    Route::post('/booking/{bookingDtl}/send', [BookingController::class, 'assignJobSupir'])
        ->name('assignJob');
    // Tambah route supir lainnya di sini

});
