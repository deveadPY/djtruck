<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * AuditMiddleware — registra cada request a la API en el log de auditoría.
 *
 * Formato de entrada en storage/logs/laravel.log:
 *   [AUDIT] POST /api/v1/sales | user:12 | ip:192.168.1.1 | 201 | 45ms
 */
class AuditMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);

        $response = $next($request);

        $duration = (int) round((microtime(true) - $startTime) * 1000);

        Log::info('AUDIT', [
            'method'   => $request->method(),
            'path'     => $request->path(),
            'user_id'  => auth()->id(),
            'ip'       => $request->ip(),
            'status'   => $response->getStatusCode(),
            'duration' => "{$duration}ms",
            'agent'    => $request->userAgent(),
        ]);

        return $response;
    }
}
