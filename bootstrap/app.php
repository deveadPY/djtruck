<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

$app = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->api(prepend: [
            \App\Infrastructure\Http\Middleware\ApiNegotiationMiddleware::class,
        ]);

        $middleware->web(append: [
            \App\Infrastructure\Http\Middleware\EnsureUserIsActiveMiddleware::class,
        ]);

        $middleware->alias([
            'audit'             => \App\Infrastructure\Http\Middleware\AuditMiddleware::class,
            'api.auth'           => \App\Infrastructure\Http\Middleware\ApiAuthMiddleware::class,
            'role'               => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission'         => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
            '2fa.enforce'        => \App\Infrastructure\Http\Middleware\EnforceTwoFactorMiddleware::class,
            '2fa.verify'         => \App\Infrastructure\Http\Middleware\VerifyTwoFactorMiddleware::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            'api/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Sesión vencida o formulario abierto demasiado tiempo (error 419): en vez de la página
        // "Page Expired", volver al formulario con lo tipeado (sin contraseña) y un aviso claro.
        // Laravel convierte TokenMismatchException en HttpException 419 antes de los render callbacks.
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\HttpException $e, Request $request) {
            if ($e->getStatusCode() !== 419 || $request->expectsJson()) {
                return null;
            }
            if ($request->routeIs('logout')) {
                return redirect()->route('login');
            }

            return redirect()->back()
                ->withInput($request->except(['password', 'password_confirmation', '_token']))
                ->with('error', 'La página había expirado. Volvé a intentarlo.');
        });

        // Respuestas JSON para errores en rutas API
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*') || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Recurso no encontrado.',
                    'error'   => $e->getMessage(),
                ], 404);
            }
        });

        $exceptions->render(function (
            \App\Domain\Shared\Exceptions\SaleAmountMismatchException $e,
            Request $request
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Error en montos de la venta.',
                'error'   => $e->getMessage(),
            ], 422);
        });

        $exceptions->render(function (
            \App\Domain\Shared\Exceptions\InsufficientStockException $e,
            Request $request
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Stock insuficiente.',
                'error'   => $e->getMessage(),
            ], 409);
        });

        $exceptions->render(function (
            \App\Domain\Shared\Exceptions\CurrencyConversionException $e,
            Request $request
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Error de conversión de moneda.',
                'error'   => $e->getMessage(),
            ], 422);
        });
    })
    ->create();

$app->usePublicPath(dirname(__DIR__).'/public');

return $app;
