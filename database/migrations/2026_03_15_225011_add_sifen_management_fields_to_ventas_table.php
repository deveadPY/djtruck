<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ventas', function (Blueprint $blueprint) {
            $blueprint->string('estado_sifen', 20)->default('PENDIENTE')->after('tiene_factura_electronica');
            $blueprint->string('sifen_kude_path')->nullable()->after('estado_sifen');
            $blueprint->string('sifen_xml_path')->nullable()->after('sifen_kude_path');
            $blueprint->string('sifen_numero_lote')->nullable()->after('sifen_xml_path');
            $blueprint->string('tipo_comprobante_sifen', 5)->default('01')->after('sifen_numero_lote');
        });
    }

    public function down(): void
    {
        Schema::table('ventas', function (Blueprint $blueprint) {
            $blueprint->dropColumn([
                'estado_sifen',
                'sifen_kude_path',
                'sifen_xml_path',
                'sifen_numero_lote',
                'tipo_comprobante_sifen'
            ]);
        });
    }
};
