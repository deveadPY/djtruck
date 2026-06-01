<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Fix de columnas con encoding UTF-8 problemático que no fueron cubiertas
 * por la migración 2026_04_27_000002_rename_special_char_columns.
 *
 * Casos cubiertos:
 *   - documentos.tamaño_bytes  (ñ UTF-8 válida) → tamano_bytes
 *   - documentos.tama??o_bytes (ñ con encoding roto) → tamano_bytes
 *
 * En algunos entornos (ej. SiteGround MySQL con utf8mb4) la columna se guardó
 * correctamente como `tamaño_bytes`, mientras que en otros (ej. XAMPP local
 * con latin1) se guardó como `tama??o_bytes`. Esta migración maneja ambos casos.
 */
return new class extends Migration {
    public function up(): void
    {
        $columnasARenombrar = [
            // [tabla, posibles nombres origen, nombre destino, tipo]
            ['documentos', ['tamaño_bytes', 'tama??o_bytes'], 'tamano_bytes', 'BIGINT UNSIGNED NULL'],
            ['vehiculos',  ['año', 'a??o'],                     'anio',         'SMALLINT NOT NULL'],
            ['vehiculos',  ['año_fabricacion', 'a??o_fabricacion'], 'anio_fabricacion', 'SMALLINT NULL'],
        ];

        foreach ($columnasARenombrar as [$tabla, $origenes, $destino, $tipo]) {
            // Si la columna destino ya existe, no hacer nada
            if ($this->columnExists($tabla, $destino)) {
                continue;
            }

            foreach ($origenes as $origen) {
                if ($this->columnExists($tabla, $origen)) {
                    DB::statement("ALTER TABLE `{$tabla}` CHANGE `{$origen}` `{$destino}` {$tipo}");
                    break;
                }
            }
        }
    }

    public function down(): void
    {
        // No revertimos: el nombre correcto es el destino.
        // Si fuera necesario, manualmente renombrar de vuelta.
    }

    private function columnExists(string $table, string $column): bool
    {
        $result = DB::select(
            "SELECT COUNT(*) AS cnt FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?",
            [$table, $column]
        );
        return !empty($result) && (int) $result[0]->cnt > 0;
    }
};
