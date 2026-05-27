<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Renombra `cotizaciones` (que contiene tasas de cambio PYG/BRL) a `tasas_cambio`
 * para liberar el nombre `cotizaciones` para el futuro módulo de presupuestos de venta.
 */
return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('cotizaciones') && !Schema::hasTable('tasas_cambio')) {
            Schema::rename('cotizaciones', 'tasas_cambio');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('tasas_cambio') && !Schema::hasTable('cotizaciones')) {
            Schema::rename('tasas_cambio', 'cotizaciones');
        }
    }
};
