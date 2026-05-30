<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
        'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
    ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
    $exceptions->render(function (\Spatie\Permission\Exceptions\UnauthorizedException $e, $request) {
        return response()->json([
            'status' => 403,
            'message' => 'عذراً، لا تملك الصلاحيات (الرتبة) اللازمة للوصول لهذا المسار.',
            'error' => 'Unauthorized_Role'
        ], 403);
    });
    //rate limiting for login route
    $exceptions->render(function (ThrottleRequestsException $e, Request $request) {
            // نتحقق أن الطلب قادم للـ API لتخصيص الرد له
            if ($request->is('api/*')) {
                $headers = $e->getHeaders();
                $retryAfter = $headers['Retry-After'] ?? 60; // عدد الثواني المتبقية

                return response()->json([
                    'status' => 429,
                    'message' => "لقد تجاوزت الحد المسموح به من المحاولات. يرجى الانتظار والمحاولة مجدداً بعد {$retryAfter} ثانية.",
                    'error' => 'Too_Many_Attempts',
                    'retry_after_seconds' => (int) $retryAfter
                ], 429);
            }
        });
    })->create();
