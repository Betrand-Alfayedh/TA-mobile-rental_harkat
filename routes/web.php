<?php

use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\LandingController;
use Illuminate\Broadcasting\BroadcastController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Customer\MobilController;

// Fallback GET /login → default redirect ke login customer
Route::get('/login', function () {
    return redirect()->route('login.form', ['role' => 'customer']);
})->name('login');
Route::get('/', [LandingController::class, 'index'])->name('landing-page');
Route::resource('mobil', MobilController::class);
// Login universal untuk semua role
Route::middleware('guest:web')->group(function () {
    Route::get('login/google', [GoogleController::class, 'redirect'])->name('google.login');
    Route::get('auth/google/callback', [GoogleController::class, 'callback']);

    Route::get('login/{role}', [LoginController::class, 'showLoginForm'])->name('login.form');
    Route::post('login', [LoginController::class, 'login'])->name('login');

    // REGISTER untuk customer aja
    Route::get('register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('register', [RegisteredUserController::class, 'store']);

    // forgot password dll tetap
    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])->name('password.email');
    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('reset-password', [NewPasswordController::class, 'store'])->name('password.store');
});
Route::post('/logout', [LoginController::class, 'logout'])->middleware('auth')->name('logout');

Route::post('/broadcasting/auth', [BroadcastController::class, 'authenticate'])
    ->middleware('auth');

require __DIR__ . '/auth.php';
require __DIR__ . '/auth_superadmin.php';
require __DIR__ . '/auth_admin.php';
require __DIR__ . '/auth_supir.php';
