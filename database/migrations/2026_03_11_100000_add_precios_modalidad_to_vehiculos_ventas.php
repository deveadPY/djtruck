<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('vehiculos', function (Blueprint $table) {
            $table->decimal('precio_contado_usd', 20, 4)->nullable()->after('precio_venta_sugerido_usd');
            $table->decimal('precio_cuotas_usd',  20, 4)->nullable()->after('precio_contado_usd');
        });

        Schema::table('ventas', function (Blueprint $table) {
            $table->enum('modalidad_pago', ['CONTADO', 'CUOTAS'])->default('CONTADO')->after('precio_venta_usd');
        });
    }

    public function down(): void
    {
        Schema::table('vehiculos', function (Blueprint $table) {
            $table->dropColumn(['precio_contado_usd', 'precio_cuotas_usd']);
        });

        Schema::table('ventas', function (Blueprint $table) {
            $table->dropColumn('modalidad_pago');
        });
    }
};
