<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            if ($request->expectsJson() || $request->is('api/*')) {
                abort(401, 'Unauthenticated.');
            }

            return redirect('/login');
        }

        if (! $user->is_active) {
            abort(403, 'Akses Ditolak: Akun Anda tidak aktif.');
        }

        $validRoles = ['owner', 'cashier', 'operator'];
        if (! in_array($user->role, $validRoles, true)) {
            abort(403, 'Akses Ditolak: Peran akun tidak valid.');
        }

        if (! empty($roles) && ! in_array($user->role, $roles, true)) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki wewenang untuk mengakses halaman ini.');
        }

        return $next($request);
    }
}
