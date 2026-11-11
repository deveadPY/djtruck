<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Columnas de auditoría que los modelos escriben pero faltaban en producción (causaban error 500).
return new class extends Migration {
    private const COLUMNS = [
        'vehiculo_imagenes' => ['updated_by', 'deleted_by'],
        'clientes'          => ['updated_by', 'deleted_by'],
        'documentos'        => ['updated_by', 'deleted_by'],
        'cajas'             => ['updated_by'],
        'compras'           => ['updated_by'],
        'movimientos_caja'  => ['updated_by'],
    ];

    public function up(): void
    {
        foreach (self::COLUMNS as $table => $columns) {
            Schema::table($table, function (Blueprint $t) use ($table, $columns) {
                foreach ($columns as $column) {
                    if (!Schema::hasColumn($table, $column)) {
                        $t->unsignedBigInteger($column)->nullable();
                    }
                }
            });
        }
    }

    public function down(): void
    {
        // shortcut: también elimina la columna si ya existía antes de esta migración; solo se usa para rollback inmediato.
        foreach (self::COLUMNS as $table => $columns) {
            Schema::table($table, function (Blueprint $t) use ($table, $columns) {
                foreach ($columns as $column) {
                    if (Schema::hasColumn($table, $column)) {
                        $t->dropColumn($column);
                    }
                }
            });
        }
    }
};
