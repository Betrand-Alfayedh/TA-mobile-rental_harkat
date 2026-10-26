<?php

namespace App\Http\Controllers\Api\Concerns;

use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;

/** Format respons seragam API: { status, message, data }. */
trait RespondsJson
{
    protected function ok(mixed $data = null, string $message = 'OK', int $code = 200): JsonResponse
    {
        return response()->json(['status' => true, 'message' => $message, 'data' => $data], $code);
    }

    protected function fail(string $message, int $code = 422): JsonResponse
    {
        return response()->json(['status' => false, 'message' => $message], $code);
    }

    /** Daftar dipaginasi: data + meta halaman. */
    protected function page(LengthAwarePaginator $p, string $resource): JsonResponse
    {
        return response()->json([
            'status'  => true,
            'message' => 'OK',
            'data'    => $resource::collection($p->getCollection())->resolve(),
            'meta'    => ['page' => $p->currentPage(), 'per_page' => $p->perPage(), 'total' => $p->total(), 'last_page' => $p->lastPage()],
        ]);
    }
}
