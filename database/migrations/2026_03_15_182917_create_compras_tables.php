<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('compras', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('proveedor_id')->nullable();
            $table->string('numero_factura', 50)->nullable();
            $table->date('fecha_compra');
            $table->string('moneda_compra', 3)->default('USD');
            $table->decimal('monto_total_moneda', 16, 2)->default(0);
            $table->decimal('monto_total_usd', 16, 2)->default(0);
            $table->decimal('tasa_cambio', 16, 4)->default(1);
            $table->string('estado', 20)->default('COMPLETADO');
            $table->text('observaciones')->nullable();
            $table->unsignedBigInteger('caja_id')->nullable(); // Para descontar de Caja Capital
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();

            $table->foreign('proveedor_id')->references('id')->on('proveedores')->nullOnDelete();
            $table->foreign('caja_id')->references('id')->on('cajas')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('compra_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('compra_id');
            $table->unsignedBigInteger('repuesto_id');
            $table->decimal('cantidad', 16, 3);
            $table->decimal('precio_compra_moneda', 16, 2);
            $table->decimal('precio_compra_usd', 16, 2);
            $table->decimal('precio_venta_sugerido_usd', 16, 2)->nullable();
            $table->decimal('subtotal_usd', 16, 2);
            $table->timestamps();

            $table->foreign('compra_id')->references('id')->on('compras')->onDelete('cascade');
            $table->foreign('repuesto_id')->references('id')->on('stock_repuestos')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('compra_items');
        Schema::dropIfExists('compras');
    }
};
