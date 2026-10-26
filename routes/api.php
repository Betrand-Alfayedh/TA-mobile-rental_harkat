<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BookingApiController;
use App\Http\Controllers\Api\MobilApiController;
use App\Http\Controllers\Api\PembayaranApiController;
use App\Http\Controllers\Api\GoogleApiController;
use App\Http\Controllers\Api\Admin\BookingApiController as AdminBookingApi;
use App\Http\Controllers\Api\Admin\DashboardApiController as AdminDashboardApi;
use App\Http\Controllers\Api\Admin\DetailApiController as AdminDetailApi;
use App\Http\Controllers\Api\Admin\PembayaranApiController as AdminPembayaranApi;
use App\Http\Controllers\Api\Admin\LaporanKondisiApiController as AdminLaporanKondisiApi;
use App\Http\Controllers\Api\Admin\MobilApiController as AdminMobilApi;
use App\Http\Controllers\Api\Admin\PenyewaApiController as AdminPenyewaApi;
use App\Http\Controllers\Api\Admin\RekapApiController as AdminRekapApi;
use App\Http\Controllers\Api\Admin\SupirApiController as AdminSupirApi;
use App\Http\Controllers\Api\Sopir\DashboardApiController as SopirDashboardApi;
use App\Http\Controllers\Api\Sopir\TawaranApiController as SopirTawaranApi;
use App\Http\Controllers\Api\Sopir\TugasApiController as SopirTugasApi;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — Harkat Car Rental Mobile API
|--------------------------------------------------------------------------
| All routes here are prefixed with /api automatically by Laravel.
| Public routes: no auth required.
| Protected routes: require Sanctum Bearer Token (auth:sanctum).
|--------------------------------------------------------------------------
*/

// ─────────────────────────────────────────────
//  PUBLIC ROUTES
// ─────────────────────────────────────────────

// Auth — login with email/password (existing accounts)
Route::post('/login',              [AuthController::class, 'login'])->middleware('throttle:login');
// Auth — daftar akun pelanggan baru
Route::post('/register',           [AuthController::class, 'register'])->middleware('throttle:10,1');
// Auth — login/register via Google ID Token (mobile)
Route::post('/auth/google/token',  [GoogleApiController::class, 'loginWithToken'])->middleware('throttle:google-login');

// Car Catalog (browsable without login)
Route::get('/mobils',           [MobilApiController::class, 'index']);
Route::get('/mobils/available', [MobilApiController::class, 'available']);
Route::get('/mobils/{id}',      [MobilApiController::class, 'show']);
Route::get('/tipe-mobils',      [MobilApiController::class, 'tipeIndex']);

// ─────────────────────────────────────────────
//  PROTECTED ROUTES (auth:sanctum)
// ─────────────────────────────────────────────

Route::middleware('auth:sanctum')->group(function () {

    // Auth/Profile
    Route::post('/logout',          [AuthController::class, 'logout']);
    Route::get('/me',               [AuthController::class, 'me']);
    Route::put('/me',               [AuthController::class, 'updateProfile']);
    Route::put('/me/password',      [AuthController::class, 'changePassword']);

    // Pelanggan saja: pesanan dan pembayaran (admin/sopir memakai /api/admin dan /api/sopir)
    Route::middleware('api.role:customer')->group(function () {
        // Bookings
        Route::get('/bookings',             [BookingApiController::class, 'index']);
        Route::post('/bookings',            [BookingApiController::class, 'store']);
        Route::get('/bookings/riwayat',     [BookingApiController::class, 'riwayat']);
        Route::post('/bookings/estimate',   [BookingApiController::class, 'estimate']);
        Route::post('/bookings/{id}/cancel', [BookingApiController::class, 'cancel']);
        Route::get('/bookings/{id}',        [BookingApiController::class, 'show']);

        // Payments
        Route::get('/bookings/{id}/pembayaran',          [PembayaranApiController::class, 'show']);
        Route::post('/bookings/{id}/pembayaran/upload',  [PembayaranApiController::class, 'uploadBukti']);
        Route::get('/bookings/{id}/semua-pembayaran',    [PembayaranApiController::class, 'allPayments']);
    });
    // ── Admin (peran 2): alur inti pesanan ──────────────────────────────────
    Route::middleware('api.role:admin')->prefix('admin')->group(function () {
        Route::get('/dashboard', [AdminDashboardApi::class, 'index']);

        Route::get('/bookings',               [AdminBookingApi::class, 'index']);
        Route::get('/bookings/{id}',          [AdminBookingApi::class, 'show']);
        Route::post('/bookings/{id}/cancel',  [AdminBookingApi::class, 'cancel']);

        Route::get('/pembayaran',                    [AdminPembayaranApi::class, 'index']);
        Route::post('/pembayaran/{id}/verifikasi',   [AdminPembayaranApi::class, 'verifikasi']);
        Route::post('/pembayaran/{id}/tolak',        [AdminPembayaranApi::class, 'tolak']);
        Route::post('/pembayaran/{id}/lunas',        [AdminPembayaranApi::class, 'lunas']);

        Route::post('/detail/{detail}/penugasan',   [AdminDetailApi::class, 'penugasan']);
        Route::patch('/detail/{detail}/jemput',     [AdminDetailApi::class, 'jemput']);
        Route::post('/detail/{detail}/pengembalian', [AdminDetailApi::class, 'pengembalian']);

        Route::get('/detail/{detail}/laporan-kondisi',  [AdminLaporanKondisiApi::class, 'index']);
        Route::post('/detail/{detail}/laporan-kondisi', [AdminLaporanKondisiApi::class, 'store']);

        Route::get('/referensi', [AdminMobilApi::class, 'referensi']);
        Route::get('/mobils',               [AdminMobilApi::class, 'index']);
        Route::post('/mobils',              [AdminMobilApi::class, 'store']);
        Route::get('/mobils/{id}',          [AdminMobilApi::class, 'show']);
        Route::put('/mobils/{id}',          [AdminMobilApi::class, 'update']);
        Route::delete('/mobils/{id}',       [AdminMobilApi::class, 'destroy']);
        Route::patch('/mobils/{id}/status', [AdminMobilApi::class, 'status']);
        Route::post('/mobils/{id}/gambar',  [AdminMobilApi::class, 'gambar']);

        Route::get('/supirs',       [AdminSupirApi::class, 'index']);
        Route::post('/supirs',      [AdminSupirApi::class, 'store']);
        Route::get('/supirs/{id}',  [AdminSupirApi::class, 'show']);
        Route::put('/supirs/{id}',  [AdminSupirApi::class, 'update']);

        Route::get('/penyewa',       [AdminPenyewaApi::class, 'index']);
        Route::post('/penyewa',      [AdminPenyewaApi::class, 'store']);
        Route::get('/penyewa/{id}',  [AdminPenyewaApi::class, 'show']);
        Route::put('/penyewa/{id}',  [AdminPenyewaApi::class, 'update']);

        Route::get('/rekap', [AdminRekapApi::class, 'bulanan']);
    });

    // ── Sopir (peran 3, akun disetujui) ─────────────────────────────────────
    Route::middleware('api.role:sopir')->prefix('sopir')->group(function () {
        Route::get('/dashboard', [SopirDashboardApi::class, 'index']);
        Route::patch('/status',  [SopirDashboardApi::class, 'status']);

        Route::get('/tawaran',               [SopirTawaranApi::class, 'index']);
        Route::post('/tawaran/{id}/terima',  [SopirTawaranApi::class, 'terima']);
        Route::post('/tawaran/{id}/tolak',   [SopirTawaranApi::class, 'tolak']);

        Route::get('/tugas',                         [SopirTugasApi::class, 'index']);
        Route::get('/riwayat',                       [SopirTugasApi::class, 'riwayat']);
        Route::post('/tugas/{id}/laporan-kondisi',   [SopirTugasApi::class, 'laporan']);
    });
});
