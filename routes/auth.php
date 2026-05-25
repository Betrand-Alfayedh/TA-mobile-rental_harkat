<?php

use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\Customer\BookingController;
use App\Http\Controllers\Customer\PembayaranController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:Customer', 'verified'])->group(function () {

    Route::get('/home', [LandingController::class, 'index'])->name('home');

    Route::get('/autocomplete-cities', [ProfileController::class, 'autocompleteCities']);
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.index');
    Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('/check-profile', function () {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['status' => 'unauthenticated']);
        }

        if (!$user->alamat || !$user->no_hp) {
            return response()->json(['status' => 'incomplete']);
        }

        return response()->json([
            'status' => 'complete',
            'redirect' => route('booking.create')
        ]);
    })->name('check.profile');

    Route::get('verify-email', EmailVerificationPromptController::class)
        ->name('verification.notice');
    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');
    Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])
        ->name('password.confirm');
    Route::post('confirm-password', [ConfirmablePasswordController::class, 'store']);
    Route::put('password', [PasswordController::class, 'update'])->name('password.update');

    Route::resource('booking', BookingController::class)->only(['index', 'create', 'store']);
    Route::get('/pembayaran/{booking}', [PembayaranController::class, 'show'])->name('pembayaran.show');
    Route::post('/pembayaran/{booking}', [PembayaranController::class, 'uploadBukti'])->name('pembayaran.upload');
    Route::post('/booking/check-mobils', [BookingController::class, 'checkMobils'])->name('booking.checkMobils');
    Route::get('riwayat', [BookingController::class, 'riwayat'])->name('riwayat.index');
});
