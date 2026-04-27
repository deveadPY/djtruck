<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('cotizaciones', function (Blueprint $table) {
            $table->id();
            $table->date('fecha');
            $table->char('moneda_destino', 3); // PYG o BRL
            $table->decimal('compra', 20, 2);
            $table->decimal('venta', 20, 2);
            $table->timestamps();
            $table->unsignedBigInteger('created_by')->default(0);

            // Index to quickly fetch by date and currency
            $table->unique(['fecha', 'moneda_destino']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cotizaciones');
    }
};
