<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('vehiculo_imagenes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vehiculo_id');
            $table->string('ruta', 500);
            $table->string('nombre_original', 255)->nullable();
            $table->integer('orden')->default(0);
            $table->boolean('es_portada')->default(false);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('vehiculo_id')->references('id')->on('vehiculos')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehiculo_imagenes');
    }
};
