<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notificaciones_descartadas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('tipo', 30);        // mora | hoy | prox | pagar | declarar | stock
            $table->unsignedBigInteger('referencia_id');
            $table->timestamp('descartado_at')->useCurrent();

            $table->unique(['user_id', 'tipo', 'referencia_id'], 'notif_descartada_unique');
            $table->index(['user_id', 'tipo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notificaciones_descartadas');
    }
};
