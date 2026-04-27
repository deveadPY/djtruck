<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_plantillas', function (Blueprint $table) {
            $table->id();
            $table->string('tipo', 50)->unique();       // BIENVENIDA_VENTA | RECIBO_CUOTA | CUOTA_VENCIDA | RECORDATORIO_CUOTA | ESTADO_CUENTA
            $table->string('nombre', 150);              // Human-readable name
            $table->string('asunto', 250);              // Subject line, may contain {{variables}}
            $table->longText('cuerpo_html');            // Full HTML body with {{variable}} placeholders
            $table->text('variables_disponibles');      // JSON array of variable names for the editor UI
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->unsignedBigInteger('updated_by')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_plantillas');
    }
};
