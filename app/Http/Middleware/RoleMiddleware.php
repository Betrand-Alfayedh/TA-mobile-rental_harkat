<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $roles  Comma separated roles, misal: "Admin,SuperAdmin"
     */
    public function handle(Request $request, Closure $next, $roles): Response
    {
        if (!Auth::check()) {
            // redirect login form sesuai prefix
            $prefix = explode('/', $request->path())[0] ?? '';
            return redirect("login/$prefix");
        }

        $userRole = Auth::user()->roleName;
        $rolesArray = array_map('trim', explode(',', $roles));

        if (!in_array($userRole, $rolesArray)) {
            Auth::logout(); // tambahan biar user langsung dipaksa logout
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect()->route('landing-page');
        }

        return $next($request);
    }
}
