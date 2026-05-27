<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Middleware;

use App\Application\Auth\TwoFactor\VerifyTwoFactorUseCase;
use App\Domain\Auth\TwoFactor\Services\TwoFactorService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Una vez que el usuario tiene 2FA activado, este middleware exige verificación
 * del código en cada nueva sesión (después del login con contraseña).
 *
 * El flag `two_factor_verified_at` se guarda en session por VerifyTwoFactorUseCase.
 */
class VerifyTwoFactorMiddleware
{
    private const ROUTES_BYPASS = [
        '2fa.verify', '2fa.verify.submit',
        'logout', 'auth.logout',
    ];

    public function __construct(private readonly TwoFactorService $service) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (!$user) {
            return $next($request);
        }

        // Si el usuario aún no activó 2FA, no aplica (eso es responsabilidad de EnforceTwoFactor)
        if (!$this->service->isEnabled($user)) {
            return $next($request);
        }

        if (VerifyTwoFactorUseCase::isSessionVerified()) {
            return $next($request);
        }

        $routeName = $request->route()?->getName();
        if ($routeName && in_array($routeName, self::ROUTES_BYPASS, true)) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success'      => false,
                'message'      => 'Se requiere verificación 2FA en esta sesión.',
                'redirect_url' => route('2fa.verify'),
            ], 403);
        }

        // Guardar URL pretendida para volver después de verificar
        session(['url.intended' => $request->fullUrl()]);
        return redirect()->route('2fa.verify');
    }
}
