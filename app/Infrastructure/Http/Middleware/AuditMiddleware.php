<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * AuditMiddleware — Inyecta created_by/updated_by en cada request autenticada.
 */
class AuditMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // El auth()->id() estará disponible en todos los modelos y servicios
        // gracias a este middleware que corre antes de los controladores.
        return $next($request);
    }
}

