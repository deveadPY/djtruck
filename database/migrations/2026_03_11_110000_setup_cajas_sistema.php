<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // 1. Agregar columna 'codigo' como identificador estable de cada caja
        Schema::table('cajas', function (Blueprint $table) {
            $table->string('codigo', 30)->nullable()->unique()->after('nombre');
        });

        // 2. Eliminar cajas anteriores y dejar solo las 2 del sistema
        //    (en entornos de producción esto se haría con updateOrInsert)
        DB::table('cajas')->whereNotIn('codigo', ['CAJA_CHICA', 'CAJA_CAPITAL'])->delete();

        DB::table('cajas')->updateOrInsert(
            ['codigo' => 'CAJA_CHICA'],
            [
                'nombre'           => 'Caja Chica',
                'tipo'             => 'CAJA_CHICA',
                'moneda_principal' => 'PYG',
                'descripcion'      => 'Gastos operativos y administrativos locales.',
                'activo'           => true,
                'created_by'       => 1,
                'created_at'       => now(),
                'updated_at'       => now(),
            ]
        );

        DB::table('cajas')->updateOrInsert(
            ['codigo' => 'CAJA_CAPITAL'],
            [
                'nombre'           => 'Caja Capital',
                'tipo'             => 'CAJA_FUERTE',
                'moneda_principal' => 'USD',
                'descripcion'      => 'Ingresos por ventas, cobro de cuotas y gastos de vehículos.',
                'activo'           => true,
                'created_by'       => 1,
                'created_at'       => now(),
                'updated_at'       => now(),
            ]
        );
    }

    public function down(): void
    {
        Schema::table('cajas', function (Blueprint $table) {
            $table->dropUnique(['codigo']);
            $table->dropColumn('codigo');
        });
    }
};
