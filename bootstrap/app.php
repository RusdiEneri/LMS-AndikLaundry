<?php

use App\Http\Middleware\CheckRole;
use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\SecurityHeadersMiddleware;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpKernel\Exception\HttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(SecurityHeadersMiddleware::class);

        $middleware->web(append: [
            HandleInertiaRequests::class,
            EnsureUserIsActive::class,
        ]);

        $middleware->alias([
            'role' => CheckRole::class,
            'active' => EnsureUserIsActive::class,
        ]);

        $middleware->redirectTo(
            guests: '/login',
            users: fn (Request $request) => match ($request->user()?->role) {
                'owner' => '/dashboard',
                'cashier' => '/pos',
                'operator' => '/production/station',
                default => '/dashboard',
            },
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->render(function (Throwable $e, Request $request) {
            $is403 = ($e instanceof HttpException && $e->getStatusCode() === 403)
                || $e instanceof AuthorizationException;

            if ($is403) {
                if (($request->expectsJson() || $request->is('api/*')) && ! $request->header('X-Inertia')) {
                    return response()->json([
                        'message' => 'This action is unauthorized.',
                    ], 403);
                }

                $message = $e->getMessage() ?: 'Akses Ditolak: Anda tidak memiliki wewenang untuk mengakses halaman ini.';

                return Inertia::render('Error/403', [
                    'status' => 403,
                    'message' => $message,
                ])->toResponse($request)->setStatusCode(403);
            }
        });
    })->create();
