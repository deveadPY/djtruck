<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('garantias', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('venta_id');
            $table->unsignedBigInteger('vehiculo_id')->nullable();
            $table->unsignedBigInteger('repuesto_id')->nullable();
            $table->enum('tipo', ['FABRICA', 'EXTENDIDA', 'TALLER', 'OTRA'])->default('FABRICA');

            $table->date('inicio');
            $table->date('vencimiento');
            $table->integer('km_inicio')->nullable();
            $table->integer('km_limite')->nullable();           // km máximo para garantía vigente

            $table->text('cobertura')->nullable();              // qué cubre
            $table->text('exclusiones')->nullable();            // qué NO cubre
            $table->enum('estado', ['VIGENTE', 'VENCIDA', 'AGOTADA_KM', 'ANULADA'])->default('VIGENTE');

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('venta_id')->references('id')->on('ventas');
            $table->foreign('vehiculo_id')->references('id')->on('vehiculos')->nullOnDelete();
            $table->foreign('repuesto_id')->references('id')->on('stock_repuestos')->nullOnDelete();
            $table->index(['venta_id', 'estado']);
            $table->index('vencimiento');
        });

        Schema::create('reclamos_garantia', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('garantia_id');
            $table->string('numero_reclamo', 30)->unique();
            $table->date('fecha_reclamo');
            $table->text('descripcion_problema');
            $table->text('diagnostico')->nullable();
            $table->text('solucion_aplicada')->nullable();
            $table->enum('estado', ['ABIERTO', 'EN_DIAGNOSTICO', 'APROBADO', 'RECHAZADO', 'EN_REPARACION', 'RESUELTO'])->default('ABIERTO');
            $table->decimal('costo_reparacion_usd', 20, 4)->default(0);
            $table->boolean('cubierto_por_garantia')->default(true);
            $table->date('fecha_resolucion')->nullable();
            $table->unsignedBigInteger('tecnico_asignado_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('garantia_id')->references('id')->on('garantias')->cascadeOnDelete();
            $table->foreign('tecnico_asignado_id')->references('id')->on('users')->nullOnDelete();
            $table->index(['garantia_id', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reclamos_garantia');
        Schema::dropIfExists('garantias');
    }
};
