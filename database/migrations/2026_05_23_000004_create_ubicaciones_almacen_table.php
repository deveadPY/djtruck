<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ubicaciones_almacen', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->unique();           // ej: A-01-03 (estante A, fila 01, bin 03)
            $table->string('descripcion', 200)->nullable();
            $table->string('zona', 50)->nullable();           // ej: PASILLO_A, BODEGA_1
            $table->string('estante', 50)->nullable();
            $table->string('fila', 50)->nullable();
            $table->string('bin', 50)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('activo');
            $table->index('zona');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ubicaciones_almacen');
    }
};
