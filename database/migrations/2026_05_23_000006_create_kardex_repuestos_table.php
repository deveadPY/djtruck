<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('kardex_repuestos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('repuesto_id');
            $table->enum('tipo', ['ENTRADA', 'SALIDA', 'AJUSTE', 'RESERVA', 'RELEASE_RESERVA'])->index();
            $table->enum('motivo', [
                'COMPRA', 'VENTA', 'AJUSTE_INVENTARIO', 'MERMA', 'ROBO', 'DAÑO',
                'DEVOLUCION_CLIENTE', 'DEVOLUCION_PROVEEDOR', 'TRANSFERENCIA',
                'RESERVA_PRESUPUESTO', 'CANCELACION_RESERVA', 'OTRO'
            ]);
            $table->decimal('cantidad', 12, 3);
            $table->decimal('costo_unitario_usd', 20, 4)->nullable();
            $table->decimal('saldo_resultante', 12, 3);
            $table->decimal('costo_promedio_resultante', 20, 4)->nullable();
            $table->string('referencia_type', 50)->nullable();    // venta, compra, ajuste, etc.
            $table->unsignedBigInteger('referencia_id')->nullable();
            $table->text('observaciones')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('repuesto_id')->references('id')->on('stock_repuestos')->cascadeOnDelete();
            $table->index('repuesto_id');
            $table->index(['referencia_type', 'referencia_id']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kardex_repuestos');
    }
};
