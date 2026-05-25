<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BookingApiController;
use App\Http\Controllers\Api\MobilApiController;
use App\Http\Controllers\Api\PembayaranApiController;
use App\Http\Controllers\Api\GoogleApiController;
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
Route::post('/login',              [AuthController::class, 'login']);
// Auth — login/register via Google ID Token (mobile)
Route::post('/auth/google/token',  [GoogleApiController::class, 'loginWithToken']);

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

    // Bookings
    Route::get('/bookings',             [BookingApiController::class, 'index']);
    Route::post('/bookings',            [BookingApiController::class, 'store']);
    Route::get('/bookings/riwayat',     [BookingApiController::class, 'riwayat']);
    Route::post('/bookings/estimate',   [BookingApiController::class, 'estimate']);
    Route::get('/bookings/{id}',        [BookingApiController::class, 'show']);

    // Payments
    Route::get('/bookings/{id}/pembayaran',          [PembayaranApiController::class, 'show']);
    Route::post('/bookings/{id}/pembayaran/upload',  [PembayaranApiController::class, 'uploadBukti']);
    Route::get('/bookings/{id}/semua-pembayaran',    [PembayaranApiController::class, 'allPayments']);
});
