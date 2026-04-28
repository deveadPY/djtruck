<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Database\Eloquent\Relations\Relation;
use App\Infrastructure\Settings\EmpresaSettings;
use App\Infrastructure\Currency\CurrencyConverter;
use App\Domain\Sales\Services\InstallmentGenerator;
use App\Infrastructure\Mail\EmailSenderService;
use App\Domain\Finance\Services\CajaService;
use App\Domain\Sales\Events\SaleCompleted;
use App\Domain\Sales\Events\InstallmentOverdue;
use App\Domain\Sales\Events\Listeners\SendSaleCompletedEmail;
use App\Domain\Sales\Events\Listeners\SendOverdueInstallmentNotification;
use App\Infrastructure\Persistence\Eloquent\Models\VehicleModel;
use App\Infrastructure\Persistence\Eloquent\Models\RepuestoModel;
use App\Infrastructure\Services\ClienteCreditService;
use Illuminate\Support\Facades\Event;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Singletons de servicios core
        $this->app->singleton(CurrencyConverter::class, fn() => new CurrencyConverter());
        $this->app->singleton(InstallmentGenerator::class, fn() => new InstallmentGenerator());

        // Singleton del servicio de envío de emails (inyectado en controllers y listeners)
        $this->app->singleton(EmailSenderService::class);

        // Singleton de Caja — gestión de Caja Chica y Caja Capital
        $this->app->singleton(CajaService::class);

        // Cálculo de crédito/deuda activa de clientes
        $this->app->singleton(ClienteCreditService::class);
    }

    public function boot(): void
    {
        // ── Morph map — aliases para relaciones polimórficas ──────────────
        // Permite que morphTo() resuelva al modelo correcto aunque el DB
        // almacene strings cortos en lugar de FQCNs.
        Relation::morphMap([
            'App\Models\Vehicle'       => VehicleModel::class,
            'App\Models\StockRepuesto' => RepuestoModel::class,
        ]);

        // ── Configuración de empresa — compartida cuando la app ya está lista ──
        // booted() garantiza que la BD y todos los providers están listos
        $this->app->booted(function () {
            try {
                View::share('empresa', EmpresaSettings::get());
            } catch (\Throwable $e) {
                View::share('empresa', null);
            }
        });

        // ── Event Listeners — Email automático ────────────────────────────
        Event::listen(SaleCompleted::class, SendSaleCompletedEmail::class);
        Event::listen(InstallmentOverdue::class, SendOverdueInstallmentNotification::class);

        // ── Rate Limiters ─────────────────────────────────────────────────

        // Login: máximo 5 intentos por minuto por IP
        RateLimiter::for('auth-login', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip())->response(function () {
                return response()->json([
                    'success' => false,
                    'message' => 'Demasiados intentos de autenticación. Intente nuevamente en un minuto.',
                ], 429);
            });
        });

        // Escritura de tasas de cambio: máximo 10 por minuto por usuario
        RateLimiter::for('currency-write', function (Request $request) {
            return Limit::perMinute(10)->by($request->user()?->id ?? $request->ip());
        });

        // API general: 60 requests por minuto por usuario autenticado
        RateLimiter::for('api-general', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?? $request->ip());
        });
    }
}
