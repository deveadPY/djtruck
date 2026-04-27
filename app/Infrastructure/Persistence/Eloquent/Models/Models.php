<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Models — Refactorizado
|--------------------------------------------------------------------------
| Cada modelo fue separado a su propio archivo para cumplir PSR-4.
| Este archivo se mantiene vacío como referencia histórica.
|
| Modelos disponibles en este namespace:
|   - VehicleModel         (vehiculos)
|   - VehicleExpenseModel  (gastos_vehiculo)
|   - VehicleImageModel    (vehiculo_imagenes)
|   - SaleModel            (ventas)
|   - PaymentDetailModel   (detalles_pago)
|   - InstallmentModel     (cuotas)
|   - PlanCuotasModel      (planes_cuotas)
|   - SupplierModel        (proveedores)
|   - SupplierInvoiceModel (facturas_proveedores)
|   - ClienteModel         (clientes)
|   - CajaModel            (cajas)
|   - MovimientoCajaModel  (movimientos_caja)
|   - RepuestoModel        (stock_repuestos)
|   - DocumentoModel       (documentos)
*/

namespace App\Infrastructure\Persistence\Eloquent\Models;
