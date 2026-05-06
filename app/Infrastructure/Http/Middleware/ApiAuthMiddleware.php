<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * ApiAuthMiddleware — Verifica que la request tenga un usuario autenticado vía Sanctum.
 * Retorna 401 JSON si no hay token válido.
 */
class ApiAuthMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user()) {
            return response()->json([
                'success' => false,
                'message' => 'No autenticado. Incluya el token Bearer en el header Authorization.',
            ], 401);
        }

        return $next($request);
    }
}
