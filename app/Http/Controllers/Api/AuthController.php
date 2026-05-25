<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Register a new customer account.
     *
     * POST /api/register
     */
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'no_hp'    => 'nullable|string|max:20',
            'alamat'   => 'nullable|string',
            'asal_kota' => 'nullable|string',
        ]);

        $user = User::create([
            'name'      => $validated['name'],
            'email'     => $validated['email'],
            'password'  => Hash::make($validated['password']),
            'role'      => 4, // Customer
            'no_hp'     => $validated['no_hp'] ?? null,
            'alamat'    => $validated['alamat'] ?? null,
            'asal_kota' => $validated['asal_kota'] ?? null,
        ]);

        $token = $user->createToken('mobile-app')->plainTextToken;

        return response()->json([
            'status'  => true,
            'message' => 'Registrasi berhasil.',
            'data'    => [
                'user'  => [
                    'id'        => $user->id,
                    'name'      => $user->name,
                    'email'     => $user->email,
                    'no_hp'     => $user->no_hp,
                    'alamat'    => $user->alamat,
                    'asal_kota' => $user->asal_kota,
                    'role'      => $user->role,
                    'role_name' => $user->role_name,
                ],
                'token' => $token,
            ],
        ], 201);
    }

    /**
     * Login and get a Sanctum token.
     *
     * POST /api/login
     */
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Email atau password salah.'],
            ]);
        }

        // Only allow Customer (role=4) to login via mobile
        if ($user->role !== 4) {
            return response()->json([
                'status'  => false,
                'message' => 'Akun ini tidak memiliki akses ke aplikasi mobile.',
            ], 403);
        }

        // Revoke previous tokens to enforce single-session
        $user->tokens()->delete();

        $token = $user->createToken('mobile-app')->plainTextToken;

        return response()->json([
            'status'  => true,
            'message' => 'Login berhasil.',
            'data'    => [
                'user'  => [
                    'id'        => $user->id,
                    'name'      => $user->name,
                    'email'     => $user->email,
                    'no_hp'     => $user->no_hp,
                    'alamat'    => $user->alamat,
                    'asal_kota' => $user->asal_kota,
                    'role'      => $user->role,
                    'role_name' => $user->role_name,
                ],
                'token' => $token,
            ],
        ]);
    }

    /**
     * Logout and revoke the current token.
     *
     * POST /api/logout
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status'  => true,
            'message' => 'Logout berhasil.',
        ]);
    }

    /**
     * Get authenticated user profile.
     *
     * GET /api/me
     */
    public function me(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'status'  => true,
            'message' => 'OK',
            'data'    => [
                'id'        => $user->id,
                'name'      => $user->name,
                'email'     => $user->email,
                'no_hp'     => $user->no_hp,
                'alamat'    => $user->alamat,
                'asal_kota' => $user->asal_kota,
                'role'      => $user->role,
                'role_name' => $user->role_name,
            ],
        ]);
    }

    /**
     * Update authenticated user profile.
     *
     * PUT /api/me
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name'      => 'sometimes|string|max:255',
            'no_hp'     => 'sometimes|string|max:20',
            'alamat'    => 'sometimes|string',
            'asal_kota' => 'sometimes|string',
        ]);

        $user->update($validated);

        return response()->json([
            'status'  => true,
            'message' => 'Profil berhasil diperbarui.',
            'data'    => [
                'id'        => $user->id,
                'name'      => $user->name,
                'email'     => $user->email,
                'no_hp'     => $user->no_hp,
                'alamat'    => $user->alamat,
                'asal_kota' => $user->asal_kota,
            ],
        ]);
    }

    /**
     * Change password.
     *
     * PUT /api/me/password
     */
    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'password'         => 'required|string|min:8|confirmed',
        ]);

        $user = $request->user();

        if (! Hash::check($request->current_password, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['Password lama tidak sesuai.'],
            ]);
        }

        $user->update(['password' => Hash::make($request->password)]);

        return response()->json([
            'status'  => true,
            'message' => 'Password berhasil diubah.',
        ]);
    }
}
