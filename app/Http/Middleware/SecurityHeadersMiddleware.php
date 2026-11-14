<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeadersMiddleware
{
    /**
     * Handle an incoming request and apply security hardening headers.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        $isLocal = app()->environment('local', 'testing');
        $viteDev = $isLocal ? " http://localhost:5173 http://127.0.0.1:5173 http://[::1]:5173" : "";
        $viteWs = $isLocal ? " ws://localhost:5173 ws://127.0.0.1:5173 ws://[::1]:5173" : "";

        $response->headers->set(
            'Content-Security-Policy',
            "default-src 'self'; " .
            "script-src 'self' 'unsafe-inline' 'unsafe-eval'{$viteDev}; " .
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com{$viteDev}; " .
            "font-src 'self' https://fonts.gstatic.com data:{$viteDev}; " .
            "img-src 'self' data: blob:{$viteDev}; " .
            "connect-src 'self' ws: wss:{$viteDev}{$viteWs}; " .
            "frame-ancestors 'self';"
        );

        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
