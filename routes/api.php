<?php

use Illuminate\Support\Facades\Route;
use App\Infrastructure\Http\Controllers\Api\VehicleController;
use App\Infrastructure\Http\Controllers\Api\SaleController;
use App\Infrastructure\Http\Controllers\Api\InstallmentController;
use App\Infrastructure\Http\Controllers\Api\CurrencyController;
use App\Infrastructure\Http\Controllers\Api\SupplierController;
use App\Infrastructure\Http\Controllers\Api\CashRegisterController;
use App\Infrastructure\Http\Controllers\Api\SifenController;
use App\Infrastructure\Http\Controllers\Api\ReportController;
use App\Infrastructure\Http\Controllers\Api\AuthController;

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

// ── Rutas protegidas ────────────────────────────────────────────────────
Route::prefix('v1')->middleware(['auth:sanctum', 'audit', 'throttle:api-general'])->group(function () {

    // ── VEHÍCULOS / ACTIVOS ─────────────────────────────────────────────
    Route::prefix('vehicles')->group(function () {
        Route::get('/',                    [VehicleController::class, 'index']);
        Route::post('/',                   [VehicleController::class, 'store']);
        Route::get('/{id}',                [VehicleController::class, 'show']);
        Route::put('/{id}',                [VehicleController::class, 'update']);
        Route::delete('/{id}',             [VehicleController::class, 'destroy']);

        // Costeo acumulado
        Route::get('/{id}/book-value',     [VehicleController::class, 'bookValue']);
        Route::get('/{id}/expenses',       [VehicleController::class, 'expenses']);
        Route::post('/{id}/expenses',      [VehicleController::class, 'addExpense']);
        Route::delete('/{id}/expenses/{expenseId}', [VehicleController::class, 'removeExpense']);

        // Stock disponible
        Route::get('/status/available',    [VehicleController::class, 'available']);
        Route::get('/status/trade-ins',    [VehicleController::class, 'tradeIns']);
    });

    // ── VENTAS ──────────────────────────────────────────────────────────
    Route::prefix('sales')->group(function () {
        Route::get('/',                    [SaleController::class, 'index']);
        Route::get('/{id}',                [SaleController::class, 'show']);
        Route::delete('/{id}',             [SaleController::class, 'destroy']);

        // ★ Venta híbrida con canje
        Route::post('/process-with-trade-in', [SaleController::class, 'procesarVentaConCanje']);

        // Rentabilidad
        Route::get('/{id}/profitability',  [SaleController::class, 'profitability']);

        // Re-emisión SIFEN
        Route::post('/{id}/emit-invoice',  [SaleController::class, 'emitInvoice']);
    });

    // ── CUOTAS ──────────────────────────────────────────────────────────
    Route::prefix('installments')->group(function () {
        Route::get('/',                        [InstallmentController::class, 'index']);
        Route::get('/overdue',                 [InstallmentController::class, 'overdue']);
        Route::get('/due-today',               [InstallmentController::class, 'dueToday']);
        Route::get('/{id}',                    [InstallmentController::class, 'show']);
        Route::post('/{id}/pay',               [InstallmentController::class, 'pay']);
        Route::post('/{id}/partial-pay',       [InstallmentController::class, 'partialPay']);
        Route::get('/client/{clientId}/statement', [InstallmentController::class, 'clientStatement']);
        Route::post('/simulate',               [InstallmentController::class, 'simulate']);
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

    // ── SIFEN ────────────────────────────────────────────────────────────
    Route::prefix('sifen')->group(function () {
        Route::get('/status',              [SifenController::class, 'status']);
        Route::post('/emit/{saleId}',      [SifenController::class, 'emit']);
        Route::get('/consult/{cdc}',       [SifenController::class, 'consult']);
        Route::post('/cancel/{cdc}',       [SifenController::class, 'cancel']);
        Route::get('/pending',             [SifenController::class, 'pending']);
        Route::post('/retry-pending',      [SifenController::class, 'retryPending']);
    });

    // ── REPORTES ────────────────────────────────────────────────────────
    Route::prefix('reports')->group(function () {
        Route::get('/sales-summary',       [ReportController::class, 'salesSummary']);
        Route::get('/profitability',       [ReportController::class, 'profitability']);
        Route::get('/stock-valuation',     [ReportController::class, 'stockValuation']);
        Route::get('/overdue-installments',[ReportController::class, 'overdueInstallments']);
        Route::get('/cash-flow',           [ReportController::class, 'cashFlow']);
        Route::get('/export/{type}',       [ReportController::class, 'export']);
    });
});
