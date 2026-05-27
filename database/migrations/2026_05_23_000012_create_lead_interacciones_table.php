<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('lead_interacciones', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lead_id');
            $table->enum('tipo', ['LLAMADA', 'EMAIL', 'WHATSAPP', 'VISITA', 'REUNION', 'COTIZACION', 'OTRO']);
            $table->string('asunto', 250);
            $table->text('descripcion')->nullable();
            $table->enum('resultado', ['POSITIVO', 'NEGATIVO', 'NEUTRO', 'PENDIENTE'])->default('NEUTRO');
            $table->timestamp('fecha_interaccion');
            $table->timestamp('proximo_seguimiento')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            $table->foreign('lead_id')->references('id')->on('consultas_web')->cascadeOnDelete();
            $table->index('lead_id');
            $table->index('proximo_seguimiento');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_interacciones');
    }
};
