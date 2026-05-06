<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // ── monedas_historial ───────────────────────────────────────────────
        Schema::create('monedas_historial', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('transaction_id');
            $table->string('transaction_type', 50);
            $table->char('moneda_origen',  3);
            $table->char('moneda_destino', 3);
            $table->decimal('tasa_usada',       20, 8);
            $table->string('fuente_tasa',       50);
            $table->decimal('monto_original',   20, 4);
            $table->decimal('monto_convertido', 20, 4);
            $table->dateTime('tasa_fecha');
            $table->timestamp('created_at')->useCurrent();
            $table->unsignedBigInteger('created_by')->default(0);

            $table->index(['transaction_type', 'transaction_id']);
            $table->index(['moneda_origen', 'moneda_destino']);
        });

        // ── clientes ────────────────────────────────────────────────────────
        Schema::create('clientes', function (Blueprint $table) {
            $table->id();
            $table->string('ruc', 30)->nullable();
            $table->string('razon_social', 200);
            $table->string('nombre_fantasia', 200)->nullable();
            $table->char('pais', 2)->default('PY');
            $table->string('email', 150)->nullable();
            $table->string('telefono', 50)->nullable();
            $table->string('direccion', 300)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->unsignedBigInteger('created_by')->default(0);
        });

        // ── proveedores ─────────────────────────────────────────────────────
        Schema::create('proveedores', function (Blueprint $table) {
            $table->id();
            $table->string('ruc_rut_nit', 30)->nullable();
            $table->string('razon_social', 200);
            $table->string('nombre_fantasia', 200)->nullable();
            $table->char('pais', 2)->default('PY');
            $table->enum('tipo', ['FABRICANTE','DISTRIBUIDOR','IMPORTADOR','SERVICIO','OTRO'])->default('DISTRIBUIDOR');
            $table->char('moneda_principal', 3)->default('USD');
            $table->string('email', 150)->nullable();
            $table->string('telefono', 50)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->unsignedBigInteger('created_by')->default(0);
        });

        // ── stock_repuestos ─────────────────────────────────────────────────
        Schema::create('stock_repuestos', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->unique();
            $table->string('descripcion', 300);
            $table->string('marca_compatible', 80)->nullable();
            $table->string('unidad_medida', 20)->default('UND');
            $table->decimal('stock_actual',   12, 3)->default(0);
            $table->decimal('stock_minimo',   12, 3)->default(0);
            $table->decimal('costo_promedio_usd', 20, 4)->default(0);
            $table->decimal('precio_venta_usd',   20, 4)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->unsignedBigInteger('created_by')->default(0);
        });

        // ── vehiculos ───────────────────────────────────────────────────────
        Schema::create('vehiculos', function (Blueprint $table) {
            $table->id();
            $table->string('numero_chasis', 17);
            $table->string('numero_motor', 20)->nullable();
            $table->string('numero_serie', 50)->nullable();
            $table->string('marca', 80);
            $table->string('modelo', 80);
            $table->smallInteger('anio');
            $table->string('color', 50)->nullable();
            $table->enum('tipo_vehiculo', ['CAMION_RIGIDO','CAMION_TRACTO','SEMI_REMOLQUE','FURGON','VOLQUETE','CISTERNA','OTRO'])->default('CAMION_RIGIDO');
            $table->decimal('capacidad_toneladas', 8, 2)->nullable();
            $table->smallInteger('anio_fabricacion')->nullable();
            $table->char('pais_origen', 2)->nullable();
            $table->unsignedInteger('kilometraje')->default(0);
            $table->enum('estado', ['EN_TRANSITO','EN_ADUANA','EN_PREPARACION','DISPONIBLE','RESERVADO','VENDIDO','TOMA','BAJA'])->default('EN_TRANSITO');
            $table->string('ubicacion', 100)->nullable();
            $table->char('moneda_costo', 3)->default('USD');
            $table->decimal('costo_origen_usd',    20, 4)->default(0);
            $table->decimal('costo_origen_moneda',  20, 4)->default(0);
            $table->decimal('tasa_cambio_compra',   20, 8)->nullable();
            $table->decimal('total_gastos_usd',     20, 4)->default(0);
            // valor_libro_usd = costo_origen_usd + total_gastos_usd (se computa en PHP)
            $table->decimal('precio_venta_sugerido_usd', 20, 4)->nullable();
            $table->decimal('margen_objetivo_pct',  5, 2)->nullable();
            $table->decimal('valor_toma_usd',       20, 4)->nullable();
            $table->unsignedBigInteger('proveedor_id')->nullable();
            $table->string('factura_compra_numero', 50)->nullable();
            $table->date('factura_compra_fecha')->nullable();
            $table->unsignedBigInteger('venta_canje_origen_id')->nullable();
            $table->unsignedBigInteger('vehiculo_canje_ref_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unsignedBigInteger('created_by')->default(0);
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();

            $table->unique(['numero_chasis', 'deleted_at']);
            $table->index('estado');
            $table->index(['marca', 'modelo']);
            $table->index('proveedor_id');

            $table->foreign('proveedor_id')->references('id')->on('proveedores')->nullOnDelete();
        });

        // ── facturas_proveedores ────────────────────────────────────────────
        Schema::create('facturas_proveedores', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('proveedor_id');
            $table->string('numero_factura', 50);
            $table->date('fecha_factura');
            $table->enum('destino', ['VEHICULO','GASTO_OPERATIVO','MIXTO']);
            $table->unsignedBigInteger('vehiculo_id')->nullable();
            $table->string('cuenta_gasto', 100)->nullable();
            $table->char('moneda', 3)->default('USD');
            $table->decimal('subtotal',   20, 4);
            $table->decimal('impuestos',  20, 4)->default(0);
            $table->decimal('total_usd',  20, 4);
            $table->enum('estado', ['PENDIENTE','APROBADA','PAGADA','ANULADA'])->default('PENDIENTE');
            $table->text('descripcion')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unsignedBigInteger('created_by')->default(0);

            $table->foreign('proveedor_id')->references('id')->on('proveedores');
            $table->foreign('vehiculo_id')->references('id')->on('vehiculos')->nullOnDelete();
        });

        // ── gastos_vehiculo ─────────────────────────────────────────────────
        Schema::create('gastos_vehiculo', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vehiculo_id');
            $table->enum('origen_tipo', ['FACTURA_PROVEEDOR','STOCK_REPUESTO','MANO_OBRA','ADUANA','FLETE','SEGURO','PATENTE_MATRICULA','OTRO'])->default('FACTURA_PROVEEDOR');
            $table->unsignedBigInteger('factura_proveedor_id')->nullable();
            $table->unsignedBigInteger('repuesto_id')->nullable();
            $table->decimal('repuesto_cantidad', 10, 3)->nullable();
            $table->string('concepto', 255);
            $table->text('descripcion')->nullable();
            $table->enum('categoria', ['REPARACION_MECANICA','CHAPERIA_PINTURA','ELECTRICIDAD','NEUMATICOS','DERECHOS_ADUANA','IMPUESTO_IMPORTACION','LOGISTICA','DOCUMENTACION','OTROS_PREPARACION'])->default('OTROS_PREPARACION');
            $table->char('moneda', 3)->default('USD');
            $table->decimal('monto_moneda', 20, 4);
            $table->decimal('tasa_cambio',  20, 8)->nullable();
            $table->decimal('monto_usd',    20, 4);
            $table->boolean('aplicado_al_costo')->default(false);
            $table->dateTime('aplicado_en')->nullable();
            $table->date('fecha_gasto');
            $table->string('numero_remision', 50)->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unsignedBigInteger('created_by')->default(0);
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();

            $table->index('vehiculo_id');
            $table->index('categoria');
            $table->index('aplicado_al_costo');
            $table->foreign('vehiculo_id')->references('id')->on('vehiculos');
            $table->foreign('factura_proveedor_id')->references('id')->on('facturas_proveedores')->nullOnDelete();
            $table->foreign('repuesto_id')->references('id')->on('stock_repuestos')->nullOnDelete();
        });

        // ── ventas ──────────────────────────────────────────────────────────
        Schema::create('ventas', function (Blueprint $table) {
            $table->id();
            $table->string('numero_venta', 20)->unique();
            $table->unsignedBigInteger('cliente_id');
            $table->unsignedBigInteger('vehiculo_id');
            $table->unsignedBigInteger('vendedor_id');
            $table->enum('estado', ['PRESUPUESTO','RESERVADO','EN_PROCESO','COMPLETADO','CANCELADO'])->default('PRESUPUESTO');
            $table->char('moneda_venta', 3)->default('USD');
            $table->decimal('precio_venta_moneda', 20, 4);
            $table->decimal('precio_venta_usd',    20, 4);
            $table->decimal('tasa_cambio_venta',   20, 8)->nullable();
            $table->decimal('valor_libro_snapshot', 20, 4);
            $table->boolean('tiene_factura_electronica')->default(false);
            $table->string('cdc_sifen', 44)->nullable();
            $table->string('numero_timbrado', 20)->nullable();
            $table->dateTime('fecha_emision_fe')->nullable();
            $table->text('observaciones')->nullable();
            $table->date('fecha_venta');
            $table->timestamps();
            $table->softDeletes();
            $table->unsignedBigInteger('created_by')->default(0);
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->index('cliente_id');
            $table->index('vehiculo_id');
            $table->index('estado');
            $table->index('fecha_venta');
            $table->foreign('vehiculo_id')->references('id')->on('vehiculos');
            $table->foreign('cliente_id')->references('id')->on('clientes');
        });

        // ── planes_cuotas ───────────────────────────────────────────────────
        Schema::create('planes_cuotas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('venta_id');
            $table->unsignedBigInteger('cliente_id');
            $table->enum('tipo_plan', ['FRANCESA','ALEMANA','MANUAL'])->default('FRANCESA');
            $table->char('moneda', 3)->default('USD');
            $table->decimal('capital_total',     20, 4);
            $table->decimal('capital_total_usd', 20, 4);
            $table->smallInteger('numero_cuotas');
            $table->decimal('tasa_interes_mensual', 8, 4)->default(0);
            $table->date('fecha_primera_cuota');
            $table->enum('estado', ['ACTIVO','CANCELADO','COMPLETADO'])->default('ACTIVO');
            $table->timestamps();
            $table->unsignedBigInteger('created_by')->default(0);

            $table->foreign('venta_id')->references('id')->on('ventas');
        });

        // ── detalles_pago ───────────────────────────────────────────────────
        Schema::create('detalles_pago', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('venta_id');
            $table->enum('tipo_pago', ['EFECTIVO','TRANSFERENCIA','CHEQUE','VEHICULO_CANJE','PLAN_CUOTAS','TARJETA']);
            $table->char('moneda', 3)->default('USD');
            $table->decimal('monto_moneda', 20, 4);
            $table->decimal('monto_usd',    20, 4);
            $table->decimal('tasa_cambio',  20, 8)->nullable();
            $table->unsignedBigInteger('vehiculo_canje_id')->nullable();
            $table->unsignedBigInteger('plan_cuotas_id')->nullable();
            $table->string('referencia_bancaria', 100)->nullable();
            $table->string('banco', 80)->nullable();
            $table->unsignedBigInteger('caja_id')->nullable();
            $table->string('observaciones', 500)->nullable();
            $table->date('fecha_pago');
            $table->timestamps();
            $table->softDeletes();
            $table->unsignedBigInteger('created_by')->default(0);

            $table->index('venta_id');
            $table->index('tipo_pago');
            $table->foreign('venta_id')->references('id')->on('ventas');
            $table->foreign('vehiculo_canje_id')->references('id')->on('vehiculos')->nullOnDelete();
        });

        // ── cuotas ──────────────────────────────────────────────────────────
        Schema::create('cuotas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('plan_cuotas_id');
            $table->unsignedBigInteger('venta_id');
            $table->smallInteger('numero_cuota');
            $table->smallInteger('total_cuotas');
            $table->enum('tipo_plan', ['FRANCESA','ALEMANA','MANUAL'])->default('FRANCESA');
            $table->char('moneda', 3)->default('USD');
            $table->decimal('capital', 20, 4);
            $table->decimal('interes', 20, 4)->default(0);
            $table->date('fecha_vencimiento');
            $table->enum('estado', ['PENDIENTE','PAGADA','PAGADA_PARCIAL','VENCIDA','EN_MORA','ANULADA'])->default('PENDIENTE');
            $table->date('fecha_pago_efectivo')->nullable();
            $table->decimal('monto_pagado',  20, 4)->default(0);
            $table->decimal('interes_mora',  20, 4)->default(0);
            $table->unsignedBigInteger('caja_cobro_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unsignedBigInteger('created_by')->default(0);
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->index('venta_id');
            $table->index('plan_cuotas_id');
            $table->index('fecha_vencimiento');
            $table->index('estado');
            $table->foreign('venta_id')->references('id')->on('ventas');
            $table->foreign('plan_cuotas_id')->references('id')->on('planes_cuotas');
        });

        // ── cajas ────────────────────────────────────────────────────────────
        Schema::create('cajas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100);
            $table->enum('tipo', ['CAJA_CHICA','BANCO','CAJA_FUERTE','OTRA'])->default('CAJA_CHICA');
            $table->char('moneda_principal', 3)->default('PYG');
            $table->string('descripcion', 300)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->unsignedBigInteger('created_by')->default(0);
        });

        // ── movimientos_caja ─────────────────────────────────────────────────
        Schema::create('movimientos_caja', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('caja_id');
            $table->enum('tipo', ['INGRESO','EGRESO']);
            $table->string('concepto', 300);
            $table->unsignedBigInteger('referencia_id')->nullable();
            $table->string('ref_type', 50)->nullable();
            $table->char('moneda', 3)->default('PYG');
            $table->decimal('monto',     20, 4);
            $table->decimal('monto_usd', 20, 4)->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unsignedBigInteger('created_by')->default(0);

            $table->index('caja_id');
            $table->index(['ref_type', 'referencia_id']);
            $table->foreign('caja_id')->references('id')->on('cajas');
        });

        // ── arqueos_caja ─────────────────────────────────────────────────────
        Schema::create('arqueos_caja', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('caja_id');
            $table->char('moneda', 3)->default('PYG');
            $table->decimal('saldo_sistema', 20, 4);
            $table->decimal('saldo_fisico',  20, 4);
            $table->decimal('diferencia',    20, 4);
            $table->text('observaciones')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->unsignedBigInteger('created_by')->default(0);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('arqueos_caja');
        Schema::dropIfExists('movimientos_caja');
        Schema::dropIfExists('cajas');
        Schema::dropIfExists('cuotas');
        Schema::dropIfExists('detalles_pago');
        Schema::dropIfExists('planes_cuotas');
        Schema::dropIfExists('ventas');
        Schema::dropIfExists('gastos_vehiculo');
        Schema::dropIfExists('facturas_proveedores');
        Schema::dropIfExists('vehiculos');
        Schema::dropIfExists('stock_repuestos');
        Schema::dropIfExists('proveedores');
        Schema::dropIfExists('clientes');
        Schema::dropIfExists('monedas_historial');
    }
};
