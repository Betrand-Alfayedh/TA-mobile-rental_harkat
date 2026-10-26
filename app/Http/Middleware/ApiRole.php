<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Pembatas peran untuk route API (JSON, tanpa redirect).
 * Pemakaian: ->middleware('api.role:admin') atau 'api.role:admin,sopir'.
 * Peran dibaca dari database (bukan dari token) sehingga perubahan peran / penonaktifan langsung berlaku.
 */
class ApiRole
{
    public const PERAN = ['admin' => 2, 'sopir' => 3, 'customer' => 4];

    public function handle(Request $request, Closure $next, string ...$peran): Response
    {
        $user = $request->user();
        $diizinkan = array_map(fn ($p) => self::PERAN[$p] ?? null, $peran);

        if (! $user || ! $user->aktif || ! in_array((int) $user->role, $diizinkan, true)) {
            return response()->json([
                'status'  => false,
                'message' => 'Anda tidak berhak mengakses fitur ini.',
            ], 403);
        }

        // Sopir hanya boleh bekerja bila datanya ada dan disetujui (berlaku juga untuk token yang sudah terbit).
        if ((int) $user->role === 3 && (int) $user->supir?->status_approval !== 1) {
            return response()->json(['status' => false, 'message' => 'Akun sopir belum disetujui.'], 403);
        }

        return $next($request);
    }
}
