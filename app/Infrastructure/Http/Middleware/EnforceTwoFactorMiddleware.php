<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Middleware;

use App\Domain\Auth\TwoFactor\Services\TwoFactorService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Obliga a usuarios con roles privilegiados a tener 2FA activado.
 * Si no lo tienen, redirige a /2fa/setup.
 *
 * Roles que requieren 2FA: super-admin, admin, gerente.
 */
class EnforceTwoFactorMiddleware
{
    /** @var array<string> Roles que obligatoriamente deben tener 2FA. */
    private const ROLES_REQUIRE_2FA = ['super-admin', 'admin', 'gerente'];

    /** @var array<string> Rutas que se permiten sin tener 2FA setup completo. */
    private const ROUTES_BYPASS = [
        '2fa.setup', '2fa.confirm', '2fa.show.recovery',
        'logout', 'auth.logout',
    ];

    public function __construct(private readonly TwoFactorService $service) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (!$user) {
            return $next($request);
        }

        if (!method_exists($user, 'hasAnyRole') || !$user->hasAnyRole(self::ROLES_REQUIRE_2FA)) {
            return $next($request);
        }

        if ($this->service->isEnabled($user)) {
            return $next($request);
        }

        // No tiene 2FA setup completo
        $routeName = $request->route()?->getName();
        if ($routeName && in_array($routeName, self::ROUTES_BYPASS, true)) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success'      => false,
                'message'      => 'Su rol requiere activar Two-Factor Authentication.',
                'redirect_url' => route('2fa.setup'),
            ], 403);
        }

        return redirect()->route('2fa.setup')
            ->with('warning', 'Su rol requiere activar 2FA antes de continuar.');
    }
}
