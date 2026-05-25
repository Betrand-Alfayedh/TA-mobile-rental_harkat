<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * Render an exception into an HTTP response.
     */
    public function render($request, Throwable $e)
    {
        // Ambil kode error HTTP (kalau ada), default ke 500
        $code = ($e instanceof HttpExceptionInterface)
            ? $e->getStatusCode()
            : 500;

        // Pesan custom per kode error
        $messages = [
            404 => 'Halaman tidak ditemukan 😢',
            500 => 'Ups, server lagi error 😭',
            403 => 'Kamu gak punya akses ke halaman ini 🚫',
            419 => 'Session kamu udah habis ⏳',
        ];

        // Return ke view custom
        return response()->view('errors.page', [
            'code' => $code,
            'message' => $messages[$code] ?? 'Terjadi kesalahan yang tidak diketahui 😅',
        ], $code);
    }
}
