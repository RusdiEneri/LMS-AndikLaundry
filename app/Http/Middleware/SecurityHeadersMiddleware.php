<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeadersMiddleware
{
    /**
     * Handle an incoming request and apply security hardening headers.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $isLocal = app()->environment('local', 'testing');
        $nonce = $isLocal ? null : Vite::useCspNonce();

        /** @var Response $response */
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        $csp = $isLocal
            ? "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' http: https:; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://api.fontshare.com http: https:; font-src 'self' https://fonts.gstatic.com https://cdn.fontshare.com data: http: https:; img-src 'self' data: blob: http: https:; connect-src 'self' ws: wss: http: https:; frame-src 'self' https://maps.google.com https://www.google.com; frame-ancestors 'self';"
            : "default-src 'self'; script-src 'self' 'nonce-{$nonce}' https://static.cloudflareinsights.com; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://api.fontshare.com; font-src 'self' https://fonts.gstatic.com https://cdn.fontshare.com data:; img-src 'self' data: blob:; connect-src 'self' ws: wss: https://cloudflareinsights.com; frame-src 'self' https://maps.google.com https://www.google.com; frame-ancestors 'self';";

        $response->headers->set('Content-Security-Policy', $csp);

        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
