<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * AuditMiddleware — Inyecta X-Request-Id único para trazabilidad en logs.
 *
 * Cada request recibe un UUID v4 que:
 *   - Se agrega al header response (cliente puede reportar el ID en bugs)
 *   - Se inyecta como context global en Log (todos los logs del request lo incluyen)
 *   - Se inyecta como atributo del request (controllers/services pueden leerlo)
 */
class AuditMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $requestId = $request->header('X-Request-Id') ?: (string) Str::uuid();
        $request->attributes->set('request_id', $requestId);

        Log::shareContext([
            'request_id' => $requestId,
            'user_id'    => $request->user()?->id,
            'ip'         => $request->ip(),
            'route'      => $request->route()?->getName(),
        ]);

        $response = $next($request);
        $response->headers->set('X-Request-Id', $requestId);

        return $response;
    }
}
