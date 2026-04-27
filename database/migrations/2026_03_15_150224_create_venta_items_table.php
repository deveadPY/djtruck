<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('venta_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('venta_id');
            $table->morphs('itemable'); // vehiculo or repuesto
            $table->string('descripcion', 300);
            $table->decimal('cantidad', 10, 3)->default(1);
            $table->decimal('precio_unitario_moneda', 20, 4);
            $table->decimal('precio_unitario_usd', 20, 4);
            $table->decimal('subtotal_moneda', 20, 4);
            $table->decimal('subtotal_usd', 20, 4);
            $table->decimal('costo_snapshot_usd', 20, 4)->default(0); // For margin tracking
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('venta_id')->references('id')->on('ventas')->onDelete('cascade');
        });

        // Make vehiculo_id nullable in ventas table (raw SQL to avoid doctrine/dbal dependency)
        DB::statement('ALTER TABLE ventas MODIFY COLUMN vehiculo_id BIGINT UNSIGNED NULL');

        // Data Migration: Move existing sales to venta_items (only if ventas has data)
        if (Schema::hasTable('ventas') && DB::table('ventas')->exists()) {
            $ventas = DB::table('ventas')->get();
            foreach ($ventas as $venta) {
                DB::table('venta_items')->insert([
                    'venta_id' => $venta->id,
                    'itemable_id' => $venta->vehiculo_id,
                    'itemable_type' => 'App\\Models\\Vehicle',
                    'descripcion' => 'Vehiculo ID: ' . $venta->vehiculo_id,
                    'cantidad' => 1,
                    'precio_unitario_moneda' => $venta->precio_venta_moneda,
                    'precio_unitario_usd' => $venta->precio_venta_usd,
                    'subtotal_moneda' => $venta->precio_venta_moneda,
                    'subtotal_usd' => $venta->precio_venta_usd,
                    'costo_snapshot_usd' => $venta->valor_libro_snapshot,
                    'created_at' => $venta->created_at,
                    'updated_at' => $venta->updated_at,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('venta_items');
        // Revert nullable change (raw SQL to avoid doctrine/dbal dependency)
        DB::statement('ALTER TABLE ventas MODIFY COLUMN vehiculo_id BIGINT UNSIGNED NOT NULL');
    }
};
