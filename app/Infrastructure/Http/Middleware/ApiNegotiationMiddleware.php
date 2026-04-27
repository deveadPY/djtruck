<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * ApiNegotiationMiddleware — Detecta si la request espera JSON o HTML.
 * Rutas /api/* siempre retornan JSON.
 * Rutas web retornan View si el header Accept no es application/json.
 */
class ApiNegotiationMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // Forzar JSON en todas las rutas /api/*
        if ($request->is('api/*')) {
            $request->headers->set('Accept', 'application/json');
        }

        return $next($request);
    }
}
