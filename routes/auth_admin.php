<?php

use App\Http\Controllers\Admin\BookingController;
use App\Http\Controllers\Admin\LaporanController;
use App\Http\Controllers\Admin\LaporanKondisiController;
use App\Http\Controllers\Admin\RekapController;
use App\Http\Controllers\Admin\MasterMobilController;
use App\Http\Controllers\Admin\PembayaranController;
use App\Http\Controllers\Admin\PengembalianController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Customer\BookingController as CekMobilController;
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

    Route::resource('mobil', MobilController::class)->except(['destroy']);
    Route::resource('supir', SupirController::class)->except(['destroy']);
    Route::resource('pelanggan', PelangganController::class)->except(['destroy']);
    // Cek ketersediaan memakai logika yang sama dengan form pelanggan.
    Route::post('booking/cek-mobil', [CekMobilController::class, 'checkMobils'])->name('booking.cekMobil');
    Route::resource('booking', BookingController::class)->only(['index', 'create', 'store']);
    Route::post('/booking/{booking}/cancel', [BookingController::class, 'cancel'])->name('booking.cancel');
    Route::patch('/booking/{detail}/konfirmasi-jemput', [BookingController::class, 'konfirmasiJemput'])
        ->name('booking.konfirmasiJemput');
    Route::get('detail/{detail}/laporan-kondisi', [LaporanKondisiController::class, 'show'])->name('laporan-kondisi.show');
    Route::post('detail/{detail}/laporan-kondisi', [LaporanKondisiController::class, 'store'])->name('laporan-kondisi.store');
    Route::get('rekap', [RekapController::class, 'index'])->name('rekap');
    Route::get('laporan/generate', [LaporanController::class, 'generateLaporanAdmin'])->name('laporan');
    Route::get('pembayaran', [PembayaranController::class, 'index'])->name('pembayaran.index');
    Route::put('pembayaran/{pembayaran}/verifikasi', [PembayaranController::class, 'verifikasi'])->name('pembayaran.verifikasi');
    Route::put('pembayaran/{pembayaran}/lunas', [PembayaranController::class, 'tandaiLunas'])->name('pembayaran.lunas');
    Route::put('pembayaran/{pembayaran}/tolak', [PembayaranController::class, 'tolak'])->name('pembayaran.tolak');
    Route::resource('pengembalian', PengembalianController::class)
        ->only(['index', 'show', 'store']);
    Route::post('/mobil/update-status/{id}', [MobilController::class, 'updateStatus'])
        ->name('mobil.updateStatus');

    Route::post('/booking/{bookingDtl}/send', [BookingController::class, 'assignJobSupir'])
        ->name('assignJob');
    // Tambah route supir lainnya di sini

});
