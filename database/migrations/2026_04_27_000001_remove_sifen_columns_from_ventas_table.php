<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $columns = [
                'tiene_factura_electronica',
                'cdc_sifen',
                'sifen_error',
                'estado_sifen',
                'sifen_kude_path',
                'sifen_xml_path',
                'sifen_numero_lote',
                'tipo_comprobante_sifen',
                'numero_timbrado',
                'fecha_emision_fe',
            ];

            $existing = array_filter(
                $columns,
                fn($col) => Schema::hasColumn('ventas', $col)
            );

            if (!empty($existing)) {
                $table->dropColumn(array_values($existing));
            }
        });
    }

    public function down(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->boolean('tiene_factura_electronica')->default(false)->after('observaciones');
            $table->string('cdc_sifen', 44)->nullable()->after('tiene_factura_electronica');
            $table->text('sifen_error')->nullable();
            $table->string('estado_sifen', 30)->nullable();
            $table->string('sifen_kude_path')->nullable();
            $table->string('sifen_xml_path')->nullable();
            $table->string('sifen_numero_lote')->nullable();
            $table->string('tipo_comprobante_sifen', 10)->nullable();
            $table->string('numero_timbrado', 20)->nullable();
            $table->timestamp('fecha_emision_fe')->nullable();
        });
    }
};
