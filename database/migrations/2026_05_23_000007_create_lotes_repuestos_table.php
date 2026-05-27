<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('lotes_repuestos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('repuesto_id');
            $table->string('numero_lote', 80);
            $table->date('fecha_ingreso');
            $table->date('fecha_vencimiento')->nullable();
            $table->decimal('cantidad_inicial', 12, 3);
            $table->decimal('cantidad_actual', 12, 3);
            $table->decimal('costo_unitario_usd', 20, 4);
            $table->unsignedBigInteger('proveedor_id')->nullable();
            $table->unsignedBigInteger('compra_id')->nullable();
            $table->enum('estado', ['ACTIVO', 'AGOTADO', 'VENCIDO', 'BLOQUEADO'])->default('ACTIVO');
            $table->text('observaciones')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('repuesto_id')->references('id')->on('stock_repuestos')->cascadeOnDelete();
            $table->foreign('proveedor_id')->references('id')->on('proveedores')->nullOnDelete();
            $table->foreign('compra_id')->references('id')->on('compras')->nullOnDelete();
            $table->index(['repuesto_id', 'estado']);
            $table->index('fecha_vencimiento');
            $table->unique(['repuesto_id', 'numero_lote']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lotes_repuestos');
    }
};
