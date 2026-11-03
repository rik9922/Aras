<?php

use App\Http\Middleware\JWTMiddleware;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'jwt.verify' => JWTMiddleware::class,
             'role' => \App\Http\Middleware\RoleMiddleware::class,
             'ensure.qb.customer' => \App\Http\Middleware\EnsureQuickBooksCustomer::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $apiJsonResponse = static function (string $message, string $errorType, int $statusCode, mixed $data = null) {
            return response()->json([
                'status' => 'failed',
                'message' => $message,
                'error_type' => $errorType,
                'data' => $data ?? new \stdClass(),
            ], $statusCode);
        };

        $exceptions->shouldRenderJsonWhen(function (Request $request) {
            return $request->is('api/*') || $request->expectsJson();
        });

        $exceptions->render(function (ValidationException $e, Request $request) use ($apiJsonResponse) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return $apiJsonResponse(
                    collect($e->errors())->flatten()->first() ?: 'Validation failed.',
                    'validation_error',
                    $e->status,
                    $e->errors()
                );
            }
        });

        $exceptions->render(function (AuthenticationException $e, Request $request) use ($apiJsonResponse) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return $apiJsonResponse('Unauthenticated.', 'unauthenticated', 401);
            }
        });

        $exceptions->render(function (ModelNotFoundException $e, Request $request) use ($apiJsonResponse) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return $apiJsonResponse('Resource not found.', 'not_found', 404);
            }
        });

        $exceptions->render(function (NotFoundHttpException $e, Request $request) use ($apiJsonResponse) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return $apiJsonResponse('Resource not found.', 'not_found', 404);
            }
        });
    })->create();
