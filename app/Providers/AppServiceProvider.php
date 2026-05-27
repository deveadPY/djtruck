<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use App\Infrastructure\Settings\EmpresaSettings;
use App\Infrastructure\Currency\CurrencyConverter;
use App\Domain\Sales\Services\InstallmentGenerator;
use App\Infrastructure\Mail\EmailSenderService;
use App\Domain\Finance\Services\CajaService;
use App\Domain\Sales\Events\SaleCompleted;
use App\Domain\Sales\Events\SaleCreated;
use App\Domain\Sales\Events\InstallmentOverdue;
use App\Domain\Sales\Events\Listeners\SendSaleCompletedEmail;
use App\Domain\Sales\Events\Listeners\SendSaleCreatedNotification;
use App\Domain\Sales\Events\Listeners\SendOverdueInstallmentNotification;
use App\Domain\Vehicle\Events\VehicleRegistered;
use App\Domain\Vehicle\Events\Listeners\LogVehicleRegistration;
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

        // Repositorios
        $this->app->bind(
            \App\Domain\Sales\Repositories\SaleRepositoryInterface::class,
            \App\Infrastructure\Persistence\Eloquent\Repositories\EloquentSaleRepository::class
        );
        $this->app->bind(
            \App\Domain\Vehicle\Repositories\VehicleRepositoryInterface::class,
            \App\Infrastructure\Persistence\Eloquent\Repositories\EloquentVehicleRepository::class
        );
        $this->app->bind(
            \App\Domain\Purchases\Repositories\PurchaseRepositoryInterface::class,
            \App\Infrastructure\Persistence\Eloquent\Repositories\EloquentPurchaseRepository::class
        );
        $this->app->bind(
            \App\Domain\Sales\Repositories\InstallmentRepositoryInterface::class,
            \App\Infrastructure\Persistence\Eloquent\Repositories\EloquentInstallmentRepository::class
        );
        $this->app->bind(
            \App\Domain\Customers\Repositories\CustomerRepositoryInterface::class,
            \App\Infrastructure\Persistence\Eloquent\Repositories\EloquentCustomerRepository::class
        );

        // Customer services
        $this->app->singleton(\App\Domain\Customers\Validators\UniqueRucValidator::class);
        $this->app->singleton(\App\Domain\Customers\Validators\UniqueEmailValidator::class);
        $this->app->singleton(\App\Domain\Customers\Services\CreditUpdateService::class);

        // Parts (Repuestos) — Domain & Application
        $this->app->bind(
            \App\Domain\Parts\Repositories\PartRepositoryInterface::class,
            \App\Infrastructure\Persistence\Eloquent\Repositories\EloquentPartRepository::class
        );
        $this->app->singleton(\App\Domain\Parts\Validators\UniquePartCodeValidator::class);
        $this->app->singleton(\App\Domain\Parts\Services\StockMovementService::class);
        $this->app->singleton(\App\Domain\Parts\Services\PriceCalculatorService::class);

        // Suppliers — Domain & Application
        $this->app->bind(
            \App\Domain\Suppliers\Repositories\SupplierRepositoryInterface::class,
            \App\Infrastructure\Persistence\Eloquent\Repositories\EloquentSupplierRepository::class
        );
        $this->app->singleton(\App\Domain\Suppliers\Validators\UniqueSupplierRucValidator::class);
        $this->app->singleton(\App\Domain\Suppliers\Services\SupplierScoreService::class);

        // Leads (CRM básico)
        $this->app->bind(
            \App\Domain\Leads\Repositories\LeadRepositoryInterface::class,
            \App\Infrastructure\Persistence\Eloquent\Repositories\EloquentLeadRepository::class
        );
        $this->app->singleton(\App\Domain\Leads\Services\LeadAssignmentService::class);

        // Quotes (Presupuestos)
        $this->app->bind(
            \App\Domain\Quotes\Repositories\QuoteRepositoryInterface::class,
            \App\Infrastructure\Persistence\Eloquent\Repositories\EloquentQuoteRepository::class
        );

        // Two-Factor Authentication
        $this->app->singleton(\App\Domain\Auth\TwoFactor\Services\TwoFactorService::class, function () {
            $issuer = config('app.name', 'DJ Trucks');
            return new \App\Domain\Auth\TwoFactor\Services\TwoFactorService($issuer);
        });

        // ── Billing Provider — Resuelve adapter según config/billing.php ──
        $this->app->singleton(\App\Domain\Billing\Contracts\BillingProviderInterface::class, function ($app) {
            $driver = config('billing.default', 'null');
            $cfg    = config("billing.drivers.{$driver}");

            if (!$cfg || !isset($cfg['adapter'])) {
                throw \App\Domain\Billing\Exceptions\BillingProviderException::notConfigured($driver);
            }

            $adapterClass = $cfg['adapter'];

            // Null adapter: sin parámetros
            if ($adapterClass === \App\Infrastructure\Billing\Adapters\NullBillingAdapter::class) {
                return new \App\Infrastructure\Billing\Adapters\NullBillingAdapter();
            }

            // Generic API adapter
            if ($adapterClass === \App\Infrastructure\Billing\Adapters\GenericApiBillingAdapter::class) {
                return new \App\Infrastructure\Billing\Adapters\GenericApiBillingAdapter(
                    baseUrl:      $cfg['base_url'] ?? '',
                    apiKey:       $cfg['api_key']  ?? '',
                    timeout:      (int) ($cfg['timeout'] ?? 30),
                    providerName: $cfg['provider_name'] ?? $driver,
                );
            }

            // Otros adapters: resolverlos via container
            return $app->make($adapterClass, $cfg);
        });

        // Validators de Sales
        $this->app->singleton(\App\Domain\Sales\Validators\SaleIntegrityValidator::class);
        $this->app->singleton(\App\Domain\Sales\Validators\CreditLimitValidator::class);
        $this->app->singleton(\App\Domain\Sales\Validators\InstallmentValidator::class);
        $this->app->singleton(\App\Domain\Sales\Validators\VehicleStateValidator::class);
        $this->app->singleton(\App\Domain\Sales\Validators\DuplicateVehicleSaleValidator::class);

        // Calculator y Processors de Sales
        $this->app->singleton(\App\Domain\Sales\Calculator\SaleCalculator::class);
        $this->app->singleton(\App\Domain\Sales\Services\ItemDescriptionResolver::class);
        $this->app->singleton(\App\Domain\Sales\Processors\SaleItemProcessor::class);
        $this->app->singleton(\App\Domain\Sales\Processors\PaymentProcessor::class);
        $this->app->singleton(\App\Domain\Sales\Processors\InstallmentProcessor::class);

        // Vehicle Domain Services
        $this->app->singleton(\App\Domain\Vehicle\Services\TradeInVehicleRegistrar::class);
        $this->app->singleton(\App\Domain\Vehicle\Validators\VehicleIntegrityValidator::class);
        $this->app->singleton(\App\Domain\Vehicle\Calculator\VehicleBookValueCalculator::class);
        $this->app->singleton(\App\Domain\Vehicle\Processors\VehicleImageProcessor::class);
        $this->app->bind(
            \App\Domain\Vehicle\Repositories\VehicleImageRepositoryInterface::class,
            \App\Infrastructure\Persistence\Eloquent\Repositories\EloquentVehicleImageRepository::class
        );

        // Purchases Domain Services
        $this->app->singleton(\App\Domain\Purchases\Validators\PurchaseValidator::class);
        $this->app->singleton(\App\Domain\Purchases\Calculator\PurchaseCalculator::class);
        $this->app->singleton(\App\Domain\Purchases\Processors\PurchaseItemProcessor::class);
        $this->app->singleton(\App\Domain\Purchases\Processors\PurchaseDocumentProcessor::class);
    }

    public function boot(): void
    {
        // Detectar lazy loading (N+1) en entornos no productivos
        Model::preventLazyLoading(! app()->isProduction());

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
        Event::listen(SaleCompleted::class, \App\Domain\Commissions\Events\Listeners\CalculateCommissionOnSaleCompleted::class);
        Event::listen(SaleCreated::class, SendSaleCreatedNotification::class);
        Event::listen(InstallmentOverdue::class, SendOverdueInstallmentNotification::class);
        Event::listen(VehicleRegistered::class, LogVehicleRegistration::class);

        // ── Observers — Auditoría automática ──────────────────────────────
        \App\Infrastructure\Persistence\Eloquent\Models\SaleModel::observe(
            \App\Infrastructure\Persistence\Observers\SaleObserver::class
        );
        \App\Infrastructure\Persistence\Eloquent\Models\VehicleModel::observe(
            \App\Infrastructure\Persistence\Observers\VehicleObserver::class
        );
        \App\Infrastructure\Persistence\Eloquent\Models\ClienteModel::observe(
            \App\Infrastructure\Persistence\Observers\ClienteObserver::class
        );
        \App\Infrastructure\Persistence\Eloquent\Models\PurchaseModel::observe(
            \App\Infrastructure\Persistence\Observers\PurchaseObserver::class
        );
        \App\Infrastructure\Persistence\Eloquent\Models\RepuestoModel::observe(
            \App\Infrastructure\Persistence\Observers\RepuestoObserver::class
        );
        \App\Infrastructure\Persistence\Eloquent\Models\SupplierModel::observe(
            \App\Infrastructure\Persistence\Observers\SupplierObserver::class
        );

        // ── Policies — Authorization ──────────────────────────────────────
        Gate::policy(
            \App\Infrastructure\Persistence\Eloquent\Models\SaleModel::class,
            \App\Infrastructure\Http\Policies\SalePolicy::class
        );
        Gate::policy(
            \App\Infrastructure\Persistence\Eloquent\Models\VehicleModel::class,
            \App\Infrastructure\Http\Policies\VehiclePolicy::class
        );
        Gate::policy(
            \App\Infrastructure\Persistence\Eloquent\Models\ClienteModel::class,
            \App\Infrastructure\Http\Policies\ClientePolicy::class
        );
        Gate::policy(
            \App\Infrastructure\Persistence\Eloquent\Models\PurchaseModel::class,
            \App\Infrastructure\Http\Policies\PurchasePolicy::class
        );
        Gate::policy(
            \App\Infrastructure\Persistence\Eloquent\Models\InstallmentModel::class,
            \App\Infrastructure\Http\Policies\InstallmentPolicy::class
        );

        // ── Super-admin override global ───────────────────────────────────
        Gate::before(function ($user, $ability) {
            if (method_exists($user, 'hasRole') && $user->hasRole('super-admin')) {
                return true;
            }
            return null;
        });

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
