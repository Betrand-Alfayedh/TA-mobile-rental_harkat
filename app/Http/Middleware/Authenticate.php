<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;

class Authenticate extends Middleware
{
    protected function redirectTo($request)
    {
        if (! $request->expectsJson()) {

            // Superadmin area
            if ($request->is('superadmin*')) {
                return route('login.form', ['role' => 'superadmin']);
            }

            // Admin area
            if ($request->is('admin*')) {
                return route('login.form', ['role' => 'admin']);
            }

            // Supir area
            if ($request->is('supir*')) {
                return route('login.form', ['role' => 'supir']);
            }

            // Customer area atau Google callback
            if ($request->is('customer*') || $request->is('auth/google/callback')) {
                return route('login.form', ['role' => 'customer']);
            }

            // Default fallback (misalnya landing page umum)
            return '/landing-page';
        }
    }
}
