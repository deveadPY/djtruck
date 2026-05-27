<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('equivalencias_repuestos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('repuesto_id');
            $table->string('codigo_externo', 100);
            $table->string('fabricante', 100)->nullable();
            $table->string('descripcion', 250)->nullable();
            $table->enum('tipo', ['OEM', 'AFTERMARKET', 'COMPETENCIA', 'OTRO'])->default('AFTERMARKET');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('repuesto_id')->references('id')->on('stock_repuestos')->cascadeOnDelete();
            $table->index(['codigo_externo', 'fabricante']);
            $table->unique(['repuesto_id', 'codigo_externo', 'fabricante'], 'eq_repuesto_codigo_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equivalencias_repuestos');
    }
};
