<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Rename columns that had ñ/special chars — stored as ?? in some environments
        // vehiculos: año -> anio, año_fabricacion -> anio_fabricacion
        // documentos: tamaño_bytes -> tamano_bytes
        $renames = [
            ['vehiculos',  'a??o',             'anio',              'smallint(6) NOT NULL'],
            ['vehiculos',  'a??o_fabricacion',  'anio_fabricacion',  'smallint(6) NULL DEFAULT NULL'],
            ['documentos', 'tama??o_bytes',     'tamano_bytes',      'bigint unsigned NULL DEFAULT NULL'],
        ];

        foreach ($renames as [$table, $from, $to, $type]) {
            if ($this->columnExists($table, $from)) {
                DB::statement("ALTER TABLE `{$table}` CHANGE `{$from}` `{$to}` {$type}");
            }
        }
    }

    public function down(): void
    {
        $renames = [
            ['vehiculos',  'anio',             "a\xC3\xB1o",             'smallint(6) NOT NULL'],
            ['vehiculos',  'anio_fabricacion',  "a\xC3\xB1o_fabricacion", 'smallint(6) NULL DEFAULT NULL'],
            ['documentos', 'tamano_bytes',      "tama\xC3\xB1o_bytes",    'bigint unsigned NULL DEFAULT NULL'],
        ];

        foreach ($renames as [$table, $from, $to, $type]) {
            if ($this->columnExists($table, $from)) {
                DB::statement("ALTER TABLE `{$table}` CHANGE `{$from}` `{$to}` {$type}");
            }
        }
    }

    private function columnExists(string $table, string $column): bool
    {
        $result = DB::select(
            "SELECT COUNT(*) as cnt FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?",
            [$table, $column]
        );
        return !empty($result) && $result[0]->cnt > 0;
    }
};
