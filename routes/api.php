<?php

use Illuminate\Support\Facades\Route;
use App\Infrastructure\Http\Controllers\Api\VehicleController;
use App\Infrastructure\Http\Controllers\Api\SaleController;
use App\Infrastructure\Http\Controllers\Api\InstallmentController;
use App\Infrastructure\Http\Controllers\Api\CurrencyController;
use App\Infrastructure\Http\Controllers\Api\SupplierController;
use App\Infrastructure\Http\Controllers\Api\CashRegisterController;
use App\Infrastructure\Http\Controllers\Api\ReportController;
use App\Infrastructure\Http\Controllers\Api\AuthController;
use App\Infrastructure\Http\Controllers\Api\BillingWebhookController;
use App\Infrastructure\Http\Controllers\Api\HealthController;
use App\Infrastructure\Http\Controllers\Api\PublicLeadController;

/*
|--------------------------------------------------------------------------
| API Routes — ERP Camiones & Repuestos
| Base: /api/v1/
|--------------------------------------------------------------------------
*/

// ── Autenticación (pública) ─────────────────────────────────────────────
Route::prefix('v1/auth')->group(function () {
    Route::post('login',   [AuthController::class, 'login'])->middleware('throttle:auth-login');
    Route::post('logout',  [AuthController::class, 'logout'])->middleware('auth:sanctum');
    Route::get('me',       [AuthController::class, 'me'])->middleware('auth:sanctum');
});

// ── Webhook de facturación electrónica (público, autenticado por firma HMAC) ─
Route::post('v1/billing/webhook', [BillingWebhookController::class, 'handle'])
    ->middleware('throttle:60,1')
    ->name('billing.webhook');

// ── Captura de leads desde catálogo web público (sin auth, con throttle) ─────
Route::post('v1/public/leads', [PublicLeadController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('public.leads.store');

// ── Health checks (público — para monitoreo externo) ─────────────────────────
Route::get('v1/health',      [HealthController::class, 'basic'])->name('health.basic');
Route::get('v1/health/deep', [HealthController::class, 'deep'])->middleware('throttle:30,1')->name('health.deep');

// ── Rutas protegidas ────────────────────────────────────────────────────
Route::prefix('v1')->middleware(['auth:sanctum', 'audit', 'throttle:api-general'])->group(function () {

    // ── VEHÍCULOS / ACTIVOS ─────────────────────────────────────────────
    Route::prefix('vehicles')->group(function () {
        Route::get('/',                    [VehicleController::class, 'index'])->middleware('permission:vehicles.view');
        Route::post('/',                   [VehicleController::class, 'store'])->middleware('permission:vehicles.create');
        Route::get('/{id}',                [VehicleController::class, 'show'])->middleware('permission:vehicles.view');
        Route::put('/{id}',                [VehicleController::class, 'update'])->middleware('permission:vehicles.update');
        Route::delete('/{id}',             [VehicleController::class, 'destroy'])->middleware('permission:vehicles.delete');

        // Costeo acumulado
        Route::get('/{id}/book-value',     [VehicleController::class, 'bookValue'])->middleware('permission:vehicles.view');
        Route::get('/{id}/expenses',       [VehicleController::class, 'expenses'])->middleware('permission:vehicles.view');
        Route::post('/{id}/expenses',      [VehicleController::class, 'addExpense'])->middleware('permission:vehicles.expenses.create');
        Route::delete('/{id}/expenses/{expenseId}', [VehicleController::class, 'removeExpense'])->middleware('permission:vehicles.expenses.create');

        // Stock disponible
        Route::get('/status/available',    [VehicleController::class, 'available'])->middleware('permission:vehicles.view');
        Route::get('/status/trade-ins',    [VehicleController::class, 'tradeIns'])->middleware('permission:vehicles.view');
    });

    // ── VENTAS ──────────────────────────────────────────────────────────
    Route::prefix('sales')->group(function () {
        Route::get('/',                    [SaleController::class, 'index'])->middleware('permission:sales.view');
        Route::get('/{id}',                [SaleController::class, 'show'])->middleware('permission:sales.view');
        Route::delete('/{id}',             [SaleController::class, 'destroy'])->middleware('permission:sales.delete');

        // ★ Venta híbrida con canje
        Route::post('/process-with-trade-in', [SaleController::class, 'procesarVentaConCanje'])->middleware('permission:sales.create');

        // Rentabilidad
        Route::get('/{id}/profitability',  [SaleController::class, 'profitability'])->middleware('permission:sales.view');
    });

    // ── CUOTAS ──────────────────────────────────────────────────────────
    Route::prefix('installments')->group(function () {
        Route::get('/',                        [InstallmentController::class, 'index'])->middleware('permission:installments.view');
        Route::get('/overdue',                 [InstallmentController::class, 'overdue'])->middleware('permission:installments.view');
        Route::get('/due-today',               [InstallmentController::class, 'dueToday'])->middleware('permission:installments.view');
        Route::get('/{id}',                    [InstallmentController::class, 'show'])->middleware('permission:installments.view');
        Route::post('/{id}/pay',               [InstallmentController::class, 'pay'])->middleware('permission:installments.pay');
        Route::post('/{id}/partial-pay',       [InstallmentController::class, 'partialPay'])->middleware('permission:installments.pay');
        Route::post('/liquidate',              [InstallmentController::class, 'liquidate'])->middleware('permission:installments.liquidate');
        Route::get('/client/{clientId}/statement', [InstallmentController::class, 'clientStatement'])->middleware('permission:installments.view');
        Route::post('/simulate',               [InstallmentController::class, 'simulate'])->middleware('permission:installments.view');
    });

    // ── MONEDAS / TASAS ─────────────────────────────────────────────────
    Route::prefix('currency')->group(function () {
        Route::get('/rates',               [CurrencyController::class, 'currentRates']);
        Route::post('/rates',              [CurrencyController::class, 'setManualRate'])->middleware('throttle:currency-write');
        Route::post('/convert',            [CurrencyController::class, 'convert']);
        Route::get('/history',             [CurrencyController::class, 'history']);
        Route::post('/fetch-bcn',          [CurrencyController::class, 'fetchFromBcn'])->middleware('throttle:currency-write');
    });

    // ── PROVEEDORES ─────────────────────────────────────────────────────
    Route::prefix('suppliers')->group(function () {
        Route::get('/',                    [SupplierController::class, 'index']);
        Route::post('/',                   [SupplierController::class, 'store']);
        Route::get('/{id}',                [SupplierController::class, 'show']);
        Route::put('/{id}',                [SupplierController::class, 'update']);
        Route::delete('/{id}',             [SupplierController::class, 'destroy']);

        // Facturas
        Route::get('/{id}/invoices',       [SupplierController::class, 'invoices']);
        Route::post('/{id}/invoices',      [SupplierController::class, 'addInvoice']);
        Route::put('/{id}/invoices/{invId}', [SupplierController::class, 'updateInvoice']);
    });

    // ── CAJA Y TESORERÍA ────────────────────────────────────────────────
    Route::prefix('cash-registers')->group(function () {
        Route::get('/',                    [CashRegisterController::class, 'index']);
        Route::get('/{id}',                [CashRegisterController::class, 'show']);
        Route::get('/{id}/balance',        [CashRegisterController::class, 'balance']);
        Route::get('/{id}/transactions',   [CashRegisterController::class, 'transactions']);
        Route::post('/transfer',           [CashRegisterController::class, 'transfer']);
        Route::post('/{id}/reconcile',     [CashRegisterController::class, 'reconcile']);
        Route::get('/{id}/reconciliations',[CashRegisterController::class, 'reconciliations']);
    });

    // ── REPORTES ────────────────────────────────────────────────────────
    Route::prefix('reports')->middleware('permission:reports.view')->group(function () {
        Route::get('/sales-summary',       [ReportController::class, 'salesSummary']);
        Route::get('/profitability',       [ReportController::class, 'profitability']);
        Route::get('/stock-valuation',     [ReportController::class, 'stockValuation']);
        Route::get('/overdue-installments',[ReportController::class, 'overdueInstallments']);
        Route::get('/cash-flow',           [ReportController::class, 'cashFlow']);
        Route::get('/collections-forecast',[ReportController::class, 'collectionsForecast']);
        Route::get('/inventory-age',       [ReportController::class, 'inventoryAge']);
        Route::get('/sales-by-vendor',     [ReportController::class, 'salesByVendor']);
        Route::get('/dashboard-kpis',      [ReportController::class, 'dashboardKpis']);
        Route::get('/export/{type}',       [ReportController::class, 'export'])->middleware('permission:reports.export');
    });

});
