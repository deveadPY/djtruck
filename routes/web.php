<?php

use Illuminate\Support\Facades\Route;
use App\Infrastructure\Http\Controllers\Web\AuthController;
use App\Infrastructure\Http\Controllers\Web\DashboardController;
use App\Infrastructure\Http\Controllers\Web\VehicleWebController;
use App\Infrastructure\Http\Controllers\Web\RepuestoWebController;
use App\Infrastructure\Http\Controllers\Web\GastoWebController;
use App\Infrastructure\Http\Controllers\Web\VentaWebController;
use App\Infrastructure\Http\Controllers\Web\PlanCuotasWebController;
use App\Infrastructure\Http\Controllers\Web\FinanceWebController;
use App\Infrastructure\Http\Controllers\Web\ProveedorWebController;
use App\Infrastructure\Http\Controllers\Web\FacturaWebController;
use App\Infrastructure\Http\Controllers\Web\CotizacionWebController;
use App\Infrastructure\Http\Controllers\Web\ClienteWebController;
use App\Infrastructure\Http\Controllers\Web\DocumentoWebController;
use App\Infrastructure\Http\Controllers\Web\ConfiguracionController;
use App\Infrastructure\Http\Controllers\Web\RolController;
use App\Infrastructure\Http\Controllers\Web\UserController;
use App\Infrastructure\Http\Controllers\Web\NotificacionesWebController;
use App\Infrastructure\Http\Controllers\Web\EmailConfiguracionController;
use App\Infrastructure\Http\Controllers\Web\ReportWebController;
use App\Infrastructure\Http\Controllers\Web\CompraWebController;

Route::get('/', fn() => redirect('/dashboard'));

// ── Auth (sólo invitados) ────────────────────────────────────────────────
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
});

// ── Rutas autenticadas ───────────────────────────────────────────────────
Route::middleware('auth')->group(function () {

    Route::get('/logout', [AuthController::class, 'logout'])->name('logout');

    // Dashboard (accesible a todos los roles)
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // ══════════════════════════════════════════════════════════════════════
    // VEHÍCULOS
    // ══════════════════════════════════════════════════════════════════════
    Route::middleware('permission:vehiculos.crear')->group(function () {
        Route::get('/vehicles/create', [VehicleWebController::class, 'create'])->name('vehicles.create');
        Route::post('/vehicles', [VehicleWebController::class, 'store'])->name('vehicles.store');
    });
    Route::middleware('permission:vehiculos.ver')->group(function () {
        Route::get('/vehicles', [VehicleWebController::class, 'index'])->name('vehicles.index');
        Route::get('/vehicles/{id}', [VehicleWebController::class, 'show'])->name('vehicles.show');
    });
    Route::middleware('permission:vehiculos.editar')->group(function () {
        Route::get('/vehicles/{id}/edit', [VehicleWebController::class, 'edit'])->name('vehicles.edit');
        Route::put('/vehicles/{id}', [VehicleWebController::class, 'update'])->name('vehicles.update');
        Route::delete('/vehicles/{vehiculoId}/imagenes/{imagenId}', [VehicleWebController::class, 'destroyImagen'])->name('vehicles.imagenes.destroy');
        Route::post('/vehicles/{vehiculoId}/imagenes/{imagenId}/portada', [VehicleWebController::class, 'setPortada'])->name('vehicles.imagenes.portada');
        // Gastos de vehículo
        Route::get('/vehicles/{id}/gastos/create', [GastoWebController::class, 'create'])->name('gastos.create');
        Route::post('/vehicles/{id}/gastos', [GastoWebController::class, 'store'])->name('gastos.store');
        Route::delete('/vehicles/{vehiculoId}/gastos/{gastoId}', [GastoWebController::class, 'destroy'])->name('gastos.destroy');
    });
    Route::middleware('permission:vehiculos.eliminar')->group(function () {
        Route::delete('/vehicles/{id}', [VehicleWebController::class, 'destroy'])->name('vehicles.destroy');
    });

    // ══════════════════════════════════════════════════════════════════════
    // REPUESTOS
    // ══════════════════════════════════════════════════════════════════════
    Route::middleware('permission:repuestos.crear')->group(function () {
        Route::get('/repuestos/create', [RepuestoWebController::class, 'create'])->name('repuestos.create');
        Route::post('/repuestos', [RepuestoWebController::class, 'store'])->name('repuestos.store');
        Route::post('/repuestos/import', [RepuestoWebController::class, 'importExcel'])->name('repuestos.import');
    });
    Route::middleware('permission:repuestos.ver')->group(function () {
        Route::get('/repuestos', [RepuestoWebController::class, 'index'])->name('repuestos.index');
        Route::get('/repuestos/export', [RepuestoWebController::class, 'exportExcel'])->name('repuestos.export');
    });
    Route::middleware('permission:repuestos.editar')->group(function () {
        Route::get('/repuestos/{id}/edit', [RepuestoWebController::class, 'edit'])->name('repuestos.edit');
        Route::put('/repuestos/{id}', [RepuestoWebController::class, 'update'])->name('repuestos.update');
    });
    Route::middleware('permission:repuestos.eliminar')->group(function () {
        Route::delete('/repuestos/{id}', [RepuestoWebController::class, 'destroy'])->name('repuestos.destroy');
    });

    // ── Compras (Reponer Stock) ──
    Route::middleware('permission:repuestos.crear')->group(function () {
        Route::get('/compras/create', [CompraWebController::class, 'create'])->name('compras.create');
        Route::post('/compras', [CompraWebController::class, 'store'])->name('compras.store');
    });
    Route::middleware('permission:repuestos.ver')->group(function () {
        Route::get('/compras', [CompraWebController::class, 'index'])->name('compras.index');
        Route::get('/compras/{id}', [CompraWebController::class, 'show'])->name('compras.show');
    });
    Route::middleware('permission:repuestos.eliminar')->group(function () {
        Route::delete('/compras/{id}', [CompraWebController::class, 'destroy'])->name('compras.destroy');
    });

    // ══════════════════════════════════════════════════════════════════════
    // VENTAS
    // ══════════════════════════════════════════════════════════════════════
    Route::middleware('permission:ventas.crear')->group(function () {
        Route::get('/ventas/create', [VentaWebController::class, 'create'])->name('ventas.create');
        Route::post('/ventas', [VentaWebController::class, 'store'])->name('ventas.store');
    });
    Route::middleware('permission:ventas.ver')->group(function () {
        Route::get('/ventas', [VentaWebController::class, 'index'])->name('ventas.index');
        Route::get('/ventas/{id}', [VentaWebController::class, 'show'])->name('ventas.show');
        Route::get('/ventas/{id}/imprimir', [VentaWebController::class, 'imprimirNotaVenta'])->name('ventas.imprimir');
    });

    // ══════════════════════════════════════════════════════════════════════
    // CUOTAS / PLANES
    // ══════════════════════════════════════════════════════════════════════
    Route::middleware('permission:cuotas.ver')->group(function () {
        Route::get('/ventas/{ventaId}/plan-cuotas/create', [PlanCuotasWebController::class, 'create'])->name('planes_cuotas.create');
        Route::post('/ventas/{ventaId}/plan-cuotas', [PlanCuotasWebController::class, 'store'])->name('planes_cuotas.store');
        Route::get('/planes-cuotas/{planId}', [PlanCuotasWebController::class, 'show'])->name('planes_cuotas.show');
        Route::get('/cuotas/{cuotaId}/recibo-pdf', [PlanCuotasWebController::class, 'downloadRecibo'])->name('cuotas.recibo-pdf');
    });
    Route::middleware('permission:cuotas.pagar')->group(function () {
        Route::post('/cuotas/{cuotaId}/pagar', [PlanCuotasWebController::class, 'pagarCuota'])->name('cuotas.pagar');
    });

    // ══════════════════════════════════════════════════════════════════════
    // CLIENTES
    // ══════════════════════════════════════════════════════════════════════
    Route::middleware('permission:clientes.crear')->group(function () {
        Route::get('/clientes/create', [ClienteWebController::class, 'create'])->name('clientes.create');
        Route::post('/clientes', [ClienteWebController::class, 'store'])->name('clientes.store');
    });
    Route::middleware('permission:clientes.ver')->group(function () {
        Route::get('/clientes', [ClienteWebController::class, 'index'])->name('clientes.index');
        Route::get('/clientes/{id}', [ClienteWebController::class, 'show'])->name('clientes.show');
        Route::get('/clientes/{id}/estado-cuenta-pdf', [ClienteWebController::class, 'downloadEstadoCuenta'])->name('clientes.estado-cuenta-pdf');
    });
    Route::middleware('permission:clientes.editar')->group(function () {
        Route::get('/clientes/{id}/edit', [ClienteWebController::class, 'edit'])->name('clientes.edit');
        Route::put('/clientes/{id}', [ClienteWebController::class, 'update'])->name('clientes.update');
    });
    Route::middleware('permission:clientes.eliminar')->group(function () {
        Route::delete('/clientes/{id}', [ClienteWebController::class, 'destroy'])->name('clientes.destroy');
    });
    Route::middleware('permission:clientes.editar')->group(function () {
        Route::post('/clientes/{clienteId}/referencias', [ClienteWebController::class, 'storeReferencia'])->name('clientes.referencias.store');
        Route::delete('/clientes/{clienteId}/referencias/{refId}', [ClienteWebController::class, 'destroyReferencia'])->name('clientes.referencias.destroy');
    });

    // ══════════════════════════════════════════════════════════════════════
    // PROVEEDORES
    // ══════════════════════════════════════════════════════════════════════
    Route::middleware('permission:proveedores.crear')->group(function () {
        Route::get('/proveedores/create', [ProveedorWebController::class, 'create'])->name('proveedores.create');
        Route::post('/proveedores', [ProveedorWebController::class, 'store'])->name('proveedores.store');
    });
    Route::middleware('permission:proveedores.ver')->group(function () {
        Route::get('/proveedores', [ProveedorWebController::class, 'index'])->name('proveedores.index');
        Route::get('/proveedores/{id}', [ProveedorWebController::class, 'show'])->name('proveedores.show');
    });
    Route::middleware('permission:proveedores.editar')->group(function () {
        Route::get('/proveedores/{id}/edit', [ProveedorWebController::class, 'edit'])->name('proveedores.edit');
        Route::put('/proveedores/{id}', [ProveedorWebController::class, 'update'])->name('proveedores.update');
    });
    Route::middleware('permission:proveedores.eliminar')->group(function () {
        Route::delete('/proveedores/{id}', [ProveedorWebController::class, 'destroy'])->name('proveedores.destroy');
    });

    // ══════════════════════════════════════════════════════════════════════
    // FACTURAS
    // ══════════════════════════════════════════════════════════════════════
    Route::middleware('permission:facturas.crear')->group(function () {
        Route::get('/facturas/create', [FacturaWebController::class, 'create'])->name('facturas.create');
        Route::post('/facturas', [FacturaWebController::class, 'store'])->name('facturas.store');
        Route::delete('/facturas/{id}', [FacturaWebController::class, 'destroy'])->name('facturas.destroy');
    });
    Route::middleware('permission:facturas.ver')->group(function () {
        Route::get('/facturas', [FacturaWebController::class, 'index'])->name('facturas.index');
        Route::get('/facturas/{id}', [FacturaWebController::class, 'show'])->name('facturas.show');
    });

    // ══════════════════════════════════════════════════════════════════════
    // FINANZAS / CAJAS
    // ══════════════════════════════════════════════════════════════════════
    Route::middleware('permission:finanzas.ver')->group(function () {
        Route::get('/finance',                          [FinanceWebController::class, 'index'])->name('finance.index');
        Route::get('/finance/caja/{codigo}',            [FinanceWebController::class, 'show'])->name('finance.caja.show');
        Route::post('/finance/caja/{codigo}/movimiento',[FinanceWebController::class, 'storeMovimiento'])->name('finance.caja.movimiento');
        Route::get('/finance/caja/{codigo}/movimiento/{id}/recibo', [FinanceWebController::class, 'reciboMovimiento'])->name('finance.caja.recibo');
        Route::get('/finance/caja/{codigo}/reporte-pdf', [FinanceWebController::class, 'reportePdf'])->name('finance.caja.reporte');
        Route::post('/finance/transferir',              [FinanceWebController::class, 'transferir'])->name('finance.transferir');
    });

    // ══════════════════════════════════════════════════════════════════════
    // COTIZACIONES (visible a todos los autenticados)
    // ══════════════════════════════════════════════════════════════════════
    Route::get('/cotizaciones', [CotizacionWebController::class, 'index'])->name('cotizaciones.index');
    Route::get('/cotizaciones/create', [CotizacionWebController::class, 'create'])->name('cotizaciones.create');
    Route::post('/cotizaciones', [CotizacionWebController::class, 'store'])->name('cotizaciones.store');
    Route::get('/api/cotizaciones/today', [CotizacionWebController::class, 'getTodayRates'])->name('api.cotizaciones.today');

    // ══════════════════════════════════════════════════════════════════════
    // DOCUMENTOS
    // ══════════════════════════════════════════════════════════════════════
    Route::middleware('permission:documentos.crear')->group(function () {
        Route::post('/documentos', [DocumentoWebController::class, 'upload'])->name('documentos.upload');
    });
    Route::middleware('permission:documentos.ver')->group(function () {
        Route::get('/documentos/{id}/download', [DocumentoWebController::class, 'download'])->name('documentos.download');
    });
    Route::middleware('permission:documentos.eliminar')->group(function () {
        Route::delete('/documentos/{id}', [DocumentoWebController::class, 'destroy'])->name('documentos.destroy');
    });

    // ══════════════════════════════════════════════════════════════════════
    // CONFIGURACIÓN DE EMPRESA
    // ══════════════════════════════════════════════════════════════════════
    Route::middleware('permission:configuracion.ver')->group(function () {
        Route::get('/configuracion', [ConfiguracionController::class, 'index'])->name('config.index');
    });
    Route::middleware('permission:configuracion.editar')->group(function () {
        Route::post('/configuracion', [ConfiguracionController::class, 'update'])->name('config.update');
        Route::delete('/configuracion/logo', [ConfiguracionController::class, 'destroyLogo'])->name('config.logo.destroy');
    });

    // ══════════════════════════════════════════════════════════════════════
    // GESTIÓN DE ROLES
    // ══════════════════════════════════════════════════════════════════════
    Route::middleware('permission:roles.crear')->group(function () {
        Route::get('/roles/create', [RolController::class, 'create'])->name('roles.create');
        Route::post('/roles', [RolController::class, 'store'])->name('roles.store');
    });
    Route::middleware('permission:roles.ver')->group(function () {
        Route::get('/roles', [RolController::class, 'index'])->name('roles.index');
    });
    Route::middleware('permission:roles.editar')->group(function () {
        Route::get('/roles/{role}/edit', [RolController::class, 'edit'])->name('roles.edit');
        Route::put('/roles/{role}', [RolController::class, 'update'])->name('roles.update');
    });
    Route::middleware('permission:roles.eliminar')->group(function () {
        Route::delete('/roles/{role}', [RolController::class, 'destroy'])->name('roles.destroy');
    });

    // ══════════════════════════════════════════════════════════════════════
    // GESTIÓN DE USUARIOS
    // ══════════════════════════════════════════════════════════════════════
    Route::middleware('permission:usuarios.crear')->group(function () {
        Route::get('/usuarios/create', [UserController::class, 'create'])->name('usuarios.create');
        Route::post('/usuarios', [UserController::class, 'store'])->name('usuarios.store');
    });
    Route::middleware('permission:usuarios.ver')->group(function () {
        Route::get('/usuarios', [UserController::class, 'index'])->name('usuarios.index');
    });
    Route::middleware('permission:usuarios.editar')->group(function () {
        Route::get('/usuarios/{usuario}/edit', [UserController::class, 'edit'])->name('usuarios.edit');
        Route::put('/usuarios/{usuario}', [UserController::class, 'update'])->name('usuarios.update');
        Route::post('/usuarios/{usuario}/toggle', [UserController::class, 'toggleActivo'])->name('usuarios.toggle');
    });
    Route::middleware('permission:usuarios.eliminar')->group(function () {
        Route::delete('/usuarios/{usuario}', [UserController::class, 'destroy'])->name('usuarios.destroy');
    });

    // ══════════════════════════════════════════════════════════════════════
    // NOTIFICACIONES (campana + página completa)
    // ══════════════════════════════════════════════════════════════════════
    Route::middleware('permission:cuotas.ver')->group(function () {
        Route::get('/api/notificaciones', [NotificacionesWebController::class, 'apiIndex'])->name('api.notificaciones');
        Route::get('/notificaciones', [NotificacionesWebController::class, 'index'])->name('notificaciones.index');
    });

    // ══════════════════════════════════════════════════════════════════════
    // EMAIL — CONFIGURACIÓN SMTP Y PLANTILLAS
    // ══════════════════════════════════════════════════════════════════════
    Route::middleware('permission:configuracion.ver')->group(function () {
        Route::get('/configuracion/email', [EmailConfiguracionController::class, 'index'])->name('config.email');
    });
    Route::middleware('permission:configuracion.editar')->group(function () {
        Route::post('/configuracion/email/smtp', [EmailConfiguracionController::class, 'updateSmtp'])->name('config.email.smtp.update');
        Route::post('/configuracion/email/smtp/test', [EmailConfiguracionController::class, 'testSmtp'])->name('config.email.smtp.test');
        Route::get('/configuracion/email/plantillas/create', [EmailConfiguracionController::class, 'createPlantilla'])->name('config.email.plantilla.create');
        Route::post('/configuracion/email/plantillas', [EmailConfiguracionController::class, 'storePlantilla'])->name('config.email.plantilla.store');
        Route::get('/configuracion/email/plantillas/{id}/edit', [EmailConfiguracionController::class, 'editPlantilla'])->name('config.email.plantilla.edit');
        Route::put('/configuracion/email/plantillas/{id}', [EmailConfiguracionController::class, 'updatePlantilla'])->name('config.email.plantilla.update');
        Route::delete('/configuracion/email/plantillas/{id}', [EmailConfiguracionController::class, 'destroyPlantilla'])->name('config.email.plantilla.destroy');
    });

    // ── Envío manual de email desde detalle de plan ───────────────────────
    Route::middleware('permission:cuotas.pagar')->group(function () {
        Route::post('/planes-cuotas/{planId}/enviar-email', [EmailConfiguracionController::class, 'enviarEmailPlan'])->name('planes_cuotas.enviar-email');
    });

    // ══════════════════════════════════════════════════════════════════════
    // REPORTES WEB
    // ══════════════════════════════════════════════════════════════════════
    Route::middleware('permission:ventas.ver')->group(function () {
        Route::get('/reportes', [ReportWebController::class, 'index'])->name('reportes.index');
        Route::get('/reportes/export/{tipo}', [ReportWebController::class, 'export'])->name('reportes.export');
    });

});
