<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Esquema de cálculo de comisión (configurable por vendedor o global)
        Schema::create('esquemas_comision', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100);
            $table->unsignedBigInteger('vendedor_id')->nullable()->comment('null = aplica a todos');
            $table->enum('tipo_calculo', ['PCT_VENTA', 'PCT_MARGEN', 'FIJO_POR_VENTA', 'ESCALONADO'])->default('PCT_MARGEN');
            $table->decimal('porcentaje', 5, 2)->default(0);
            $table->decimal('monto_fijo_usd', 20, 4)->default(0);
            $table->json('escala')->nullable()->comment('[{desde, hasta, pct}] para tipo ESCALONADO');
            $table->date('vigencia_desde');
            $table->date('vigencia_hasta')->nullable();
            $table->boolean('activo')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('vendedor_id')->references('id')->on('users')->nullOnDelete();
            $table->index(['vendedor_id', 'activo']);
            $table->index('vigencia_desde');
        });

        // Comisiones calculadas por venta
        Schema::create('comisiones_calculadas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('venta_id');
            $table->unsignedBigInteger('vendedor_id');
            $table->unsignedBigInteger('esquema_id')->nullable();
            $table->date('fecha_venta');

            $table->decimal('base_calculo_usd', 20, 4);            // venta o margen
            $table->decimal('porcentaje_aplicado', 5, 2)->nullable();
            $table->decimal('monto_comision_usd', 20, 4);

            $table->enum('estado', ['CALCULADA', 'APROBADA', 'PAGADA', 'ANULADA'])->default('CALCULADA');
            $table->date('fecha_aprobacion')->nullable();
            $table->date('fecha_pago')->nullable();
            $table->unsignedBigInteger('aprobada_por')->nullable();
            $table->unsignedBigInteger('movimiento_caja_id')->nullable();
            $table->text('observaciones')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('venta_id')->references('id')->on('ventas');
            $table->foreign('vendedor_id')->references('id')->on('users');
            $table->foreign('esquema_id')->references('id')->on('esquemas_comision')->nullOnDelete();
            $table->foreign('aprobada_por')->references('id')->on('users')->nullOnDelete();
            $table->unique(['venta_id', 'vendedor_id']);
            $table->index(['vendedor_id', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comisiones_calculadas');
        Schema::dropIfExists('esquemas_comision');
    }
};
