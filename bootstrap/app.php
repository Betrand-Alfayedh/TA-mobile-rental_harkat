<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'auth' => \App\Http\Middleware\Authenticate::class,
            'role' => \App\Http\Middleware\RoleMiddleware::class,
            'api.role' => \App\Http\Middleware\ApiRole::class,
        ]);
        $middleware->statefulApi();
        // Cek peran sebelum pengikatan model: non-admin selalu 403, bukan 404 yang membocorkan keberadaan id.
        $middleware->prependToPriorityList(before: \Illuminate\Routing\Middleware\SubstituteBindings::class, prepend: \App\Http\Middleware\ApiRole::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Route /api selalu dijawab JSON (401/403/404/422/500), bukan redirect atau halaman HTML.
        $exceptions->shouldRenderJsonWhen(fn ($request, $e) => $request->is('api/*') || $request->expectsJson());
    })->create();
