<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('referencias_clientes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cliente_id');
            $table->enum('tipo', ['COMERCIAL', 'PERSONAL'])->default('PERSONAL');
            $table->string('nombre', 150);
            $table->string('empresa', 150)->nullable();
            $table->string('telefono', 30)->nullable();
            $table->string('relacion', 100)->nullable();
            $table->text('observaciones')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('cliente_id')->references('id')->on('clientes')->onDelete('cascade');
            $table->index('cliente_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referencias_clientes');
    }
};
