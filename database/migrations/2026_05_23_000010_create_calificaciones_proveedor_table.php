<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('calificaciones_proveedor', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('proveedor_id');
            $table->enum('criterio', [
                'CALIDAD_PRODUCTO',
                'TIEMPO_ENTREGA',
                'PRECIO',
                'SERVICIO_POSTVENTA',
                'COMUNICACION',
                'GENERAL',
            ]);
            $table->tinyInteger('puntaje');                       // 1-5 (estrellas)
            $table->text('comentario')->nullable();
            $table->unsignedBigInteger('compra_id')->nullable();  // si la calificación se origina en una compra
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('proveedor_id')->references('id')->on('proveedores')->cascadeOnDelete();
            $table->foreign('compra_id')->references('id')->on('compras')->nullOnDelete();
            $table->index(['proveedor_id', 'criterio']);
            $table->index('puntaje');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calificaciones_proveedor');
    }
};
