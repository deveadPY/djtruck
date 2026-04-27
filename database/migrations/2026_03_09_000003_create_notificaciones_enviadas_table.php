<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notificaciones_enviadas', function (Blueprint $table) {
            $table->id();
            $table->string('tipo', 50)->index();                     // matches email_plantillas.tipo
            $table->unsignedBigInteger('cliente_id')->nullable();
            $table->unsignedBigInteger('venta_id')->nullable();
            $table->unsignedBigInteger('cuota_id')->nullable();
            $table->string('destinatario_email', 150);
            $table->string('destinatario_nombre', 200)->nullable();
            $table->string('asunto', 250);
            $table->enum('estado', ['ENVIADO', 'FALLIDO', 'SIMULADO'])->default('ENVIADO');
            $table->text('error_mensaje')->nullable();               // populated on FALLIDO
            $table->unsignedBigInteger('enviado_por')->nullable();   // user_id (null = system/event)
            $table->timestamp('enviado_en')->useCurrent();
            // No timestamps(), no softDeletes — immutable audit log

            $table->index(['tipo', 'cliente_id']);
            $table->index('enviado_en');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notificaciones_enviadas');
    }
};
