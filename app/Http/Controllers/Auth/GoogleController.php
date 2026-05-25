<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use App\Models\User;

class GoogleController extends Controller
{
    public function redirect()
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();

            // Cek apakah user sudah ada berdasarkan email
            $user = User::where('email', $googleUser->getEmail())->first();

            if (!$user) {
                // Kalau belum ada → daftar user baru otomatis sebagai Customer
                $user = User::create([
                    'name'      => $googleUser->getName(),
                    'email'     => $googleUser->getEmail(),
                    'password'  => bcrypt(str()->random(16)),
                    'google_id' => $googleUser->getId(),
                    'role'      => 4, // Customer
                ]);
            } elseif ($user->role !== 4) {
                // User ada tapi bukan customer — tolak
                return redirect('/login')->with('error', 'Akun ini tidak dapat login sebagai customer.');
            }

            // Login user (lama / baru)
            Auth::login($user);

            return redirect('home');
        } catch (\Exception $e) {
            \Log::error('Google Login Error: ' . $e->getMessage());
            return redirect('/login')->with('error', 'Login dengan Google gagal! (' . $e->getMessage() . ')');
        }
    }
}
