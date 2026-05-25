<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class GoogleApiController extends Controller
{
    /**
     * Verify a Google ID Token from the mobile app and return a Sanctum token.
     *
     * The Flutter app uses google_sign_in to get an idToken, then POSTs it here.
     * We verify it against Google's tokeninfo endpoint, then create/find the user
     * and return a Sanctum Bearer token.
     *
     * POST /api/auth/google/token
     * Body: { "id_token": "..." }
     */
    public function loginWithToken(Request $request)
    {
        $request->validate([
            'id_token' => 'required|string',
        ]);

        // Verify the Google ID token
        $response = Http::get('https://oauth2.googleapis.com/tokeninfo', [
            'id_token' => $request->id_token,
        ]);

        if (!$response->ok()) {
            return response()->json([
                'status'  => false,
                'message' => 'Token Google tidak valid.',
            ], 401);
        }

        $payload = $response->json();

        // Validate audience matches our app
        $allowedAudiences = [
            config('services.google.client_id'),
            config('services.google.mobile_client_id', ''),
        ];

        if (!in_array($payload['aud'] ?? '', array_filter($allowedAudiences))) {
            return response()->json([
                'status'  => false,
                'message' => 'Token Google tidak dikenal.',
            ], 401);
        }

        $email     = $payload['email']     ?? null;
        $name      = $payload['name']      ?? $email;
        $googleId  = $payload['sub']       ?? null;

        if (!$email) {
            return response()->json([
                'status'  => false,
                'message' => 'Email tidak ditemukan di token Google.',
            ], 422);
        }

        // Find or create customer
        $user = User::where('email', $email)->first();

        if (!$user) {
            // Auto-register as Customer
            $user = User::create([
                'name'      => $name,
                'email'     => $email,
                'password'  => bcrypt(str()->random(24)),
                'google_id' => $googleId,
                'role'      => 4, // Customer
            ]);
        } elseif ($user->role !== 4) {
            return response()->json([
                'status'  => false,
                'message' => 'Akun ini tidak memiliki akses ke aplikasi mobile.',
            ], 403);
        } else {
            // Update google_id if not set
            if (!$user->google_id && $googleId) {
                $user->update(['google_id' => $googleId]);
            }
        }

        // Revoke previous tokens (single session)
        $user->tokens()->delete();
        $token = $user->createToken('mobile-app')->plainTextToken;

        return response()->json([
            'status'  => true,
            'message' => 'Login dengan Google berhasil.',
            'data'    => [
                'user'  => [
                    'id'         => $user->id,
                    'name'       => $user->name,
                    'email'      => $user->email,
                    'no_hp'      => $user->no_hp,
                    'alamat'     => $user->alamat,
                    'asal_kota'  => $user->asal_kota,
                    'role'       => $user->role,
                    'role_name'  => $user->role_name,
                ],
                'token' => $token,
            ],
        ], 200);
    }
}
