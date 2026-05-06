<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->decimal('margen_bruto_usd', 20, 4)->nullable()->default(null)->after('valor_libro_snapshot');
            $table->decimal('margen_pct',       8,  4)->nullable()->default(null)->after('margen_bruto_usd');
            $table->text('sifen_error')->nullable()->after('fecha_emision_fe');
        });

        // Calcular margen para registros existentes
        DB::statement("
            UPDATE ventas
            SET margen_bruto_usd = precio_venta_usd - valor_libro_snapshot,
                margen_pct       = CASE
                                       WHEN valor_libro_snapshot > 0
                                       THEN ROUND((precio_venta_usd - valor_libro_snapshot) / valor_libro_snapshot * 100, 4)
                                       ELSE 0
                                   END
            WHERE estado = 'COMPLETADO'
              AND deleted_at IS NULL
        ");
    }

    public function down(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->dropColumn(['margen_bruto_usd', 'margen_pct', 'sifen_error']);
        });
    }
};
