<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controllers\Web;

use App\Application\Auth\TwoFactor\ConfirmTwoFactorUseCase;
use App\Application\Auth\TwoFactor\DisableTwoFactorUseCase;
use App\Application\Auth\TwoFactor\EnableTwoFactorUseCase;
use App\Application\Auth\TwoFactor\RegenerateRecoveryCodesUseCase;
use App\Application\Auth\TwoFactor\VerifyTwoFactorUseCase;
use App\Domain\Auth\TwoFactor\Services\TwoFactorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class TwoFactorController extends Controller
{
    public function __construct(
        private readonly TwoFactorService $service,
    ) {}

    /**
     * GET /2fa/setup — pantalla inicial donde se muestra QR + recovery codes.
     */
    public function setup(Request $request, EnableTwoFactorUseCase $useCase): View|RedirectResponse
    {
        $user = $request->user();

        if ($this->service->isEnabled($user)) {
            return redirect()->route('2fa.recovery.show')
                ->with('info', '2FA ya está activo. Mostrando recovery codes existentes.');
        }

        $setup = $useCase->execute($user);

        $qrSvg = QrCode::format('svg')
            ->size(280)
            ->margin(1)
            ->generate($setup['qr_uri']);

        // Guardar recovery_codes en session para mostrar tras confirmación
        session(['_2fa_pending_recovery' => $setup['recovery_codes']]);

        return view('2fa.setup', [
            'secret'         => $setup['secret'],
            'qr_svg'         => $qrSvg,
            'recovery_codes' => $setup['recovery_codes'],
        ]);
    }

    /**
     * POST /2fa/confirm — confirma activación con primer código.
     */
    public function confirm(Request $request, ConfirmTwoFactorUseCase $useCase): RedirectResponse
    {
        $data = $request->validate(['code' => 'required|string|size:6']);

        try {
            $useCase->execute($request->user(), $data['code']);

            // Marcar sesión actual como verificada (recién confirmó)
            session([VerifyTwoFactorUseCase::SESSION_KEY => now()->toIso8601String()]);

            return redirect()->route('2fa.recovery.show')
                ->with('success', '2FA activado correctamente. Guarda tus códigos de recuperación.');
        } catch (\Throwable $e) {
            return back()->withErrors(['code' => $e->getMessage()]);
        }
    }

    /**
     * GET /2fa/recovery-codes — vista que muestra los códigos para guardar.
     */
    public function showRecovery(Request $request): View
    {
        $user = $request->user();
        $codes = session('_2fa_pending_recovery')
              ?? $this->service->getRecoveryCodes($user);

        session()->forget('_2fa_pending_recovery');

        return view('2fa.recovery-codes', ['recovery_codes' => $codes]);
    }

    /**
     * GET /2fa/verify — pide código en cada nueva sesión.
     */
    public function verifyForm(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        if (!$this->service->isEnabled($user)) {
            return redirect()->route('2fa.setup');
        }
        return view('2fa.verify');
    }

    /**
     * POST /2fa/verify — procesa código TOTP o recovery code.
     */
    public function verify(Request $request, VerifyTwoFactorUseCase $useCase): RedirectResponse
    {
        $data = $request->validate(['code' => 'required|string|min:6|max:20']);

        try {
            $useCase->execute($request->user(), $data['code']);
            $next = session('url.intended', '/');
            session()->forget('url.intended');
            return redirect($next)->with('success', 'Sesión verificada.');
        } catch (\Throwable $e) {
            return back()->withErrors(['code' => $e->getMessage()])->withInput();
        }
    }

    /**
     * POST /2fa/disable — desactivar 2FA (requiere password).
     */
    public function disable(Request $request, DisableTwoFactorUseCase $useCase): RedirectResponse
    {
        $data = $request->validate(['password' => 'required|string']);

        try {
            $useCase->execute($request->user(), $data['password']);
            VerifyTwoFactorUseCase::forgetSession();
            return redirect()->route('configuracion.index')->with('success', '2FA desactivado.');
        } catch (\Throwable $e) {
            return back()->withErrors(['password' => $e->getMessage()]);
        }
    }

    /**
     * POST /2fa/regenerate-recovery — regenera recovery codes.
     */
    public function regenerate(Request $request, RegenerateRecoveryCodesUseCase $useCase): RedirectResponse
    {
        $data = $request->validate(['password' => 'required|string']);

        try {
            $codes = $useCase->execute($request->user(), $data['password']);
            session(['_2fa_pending_recovery' => $codes]);
            return redirect()->route('2fa.recovery.show')
                ->with('success', 'Recovery codes regenerados. Guárdalos de inmediato.');
        } catch (\Throwable $e) {
            return back()->withErrors(['password' => $e->getMessage()]);
        }
    }
}
