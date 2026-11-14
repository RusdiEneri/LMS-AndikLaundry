<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user) {
            $freshUser = $user->fresh();

            if (!$freshUser || !$freshUser->is_active) {
                Auth::guard('web')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                if ($request->expectsJson() || $request->is('api/*')) {
                    abort(403, 'Akun Anda telah dinonaktifkan.');
                }

                return redirect('/login')->withErrors([
                    'email' => 'Akun Anda telah dinonaktifkan. Silakan hubungi pemilik.',
                ]);
            }
        }

        return $next($request);
    }
}
