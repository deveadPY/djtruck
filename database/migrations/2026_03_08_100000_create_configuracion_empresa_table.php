<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('configuracion_empresa', function (Blueprint $table) {
            $table->id();
            $table->string('nombre_empresa')->default('Mi Empresa');
            $table->string('ruc', 30)->nullable();
            $table->string('telefono', 30)->nullable();
            $table->string('email')->nullable();
            $table->string('direccion')->nullable();
            $table->string('ciudad', 100)->nullable();
            $table->string('pais', 100)->default('Paraguay');
            $table->string('sitio_web')->nullable();
            $table->char('moneda_base', 3)->default('USD');
            $table->string('logo_path')->nullable();
            $table->string('prefijo_venta', 5)->default('V');
            $table->string('prefijo_factura', 5)->default('F');
            $table->string('timbrado', 20)->nullable();
            $table->date('vigencia_timbrado')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('configuracion_empresa');
    }
};
