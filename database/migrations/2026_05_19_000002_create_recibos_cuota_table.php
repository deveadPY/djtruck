<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recibos_cuota', function (Blueprint $table) {
            $table->id();
            $table->string('numero_recibo', 20)->unique();
            $table->unsignedBigInteger('plan_cuotas_id')->nullable();
            $table->unsignedBigInteger('venta_id');
            $table->enum('tipo', ['CUOTA', 'MULTIPLE', 'LIQUIDACION'])->default('CUOTA');
            $table->decimal('monto_capital', 20, 4)->default(0);
            $table->decimal('monto_interes', 20, 4)->default(0);
            $table->decimal('monto_mora', 20, 4)->default(0);
            $table->decimal('descuento_anticipo', 20, 4)->default(0);
            $table->decimal('descuento_liquidacion', 20, 4)->default(0);
            $table->decimal('total_pagado', 20, 4);
            $table->char('moneda', 3)->default('USD');
            $table->json('cuotas_ids')->nullable();
            $table->date('fecha_pago');
            $table->unsignedBigInteger('caja_id')->nullable();
            $table->string('observaciones', 500)->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            $table->index('numero_recibo');
            $table->index('plan_cuotas_id');
            $table->index('venta_id');
            $table->index('fecha_pago');
            $table->foreign('venta_id')->references('id')->on('ventas');
            $table->foreign('caja_id')->references('id')->on('cajas')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recibos_cuota');
    }
};
