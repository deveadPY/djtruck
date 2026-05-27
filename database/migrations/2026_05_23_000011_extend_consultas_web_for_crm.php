<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Extiende `consultas_web` (que es la tabla base de leads) para CRM completo.
 * Mantiene el nombre existente y agrega tracking + asignación + conversión.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('consultas_web', function (Blueprint $table) {
            if (!Schema::hasColumn('consultas_web', 'asignado_a')) {
                $table->unsignedBigInteger('asignado_a')->nullable()->after('estado');
                $table->foreign('asignado_a')->references('id')->on('users')->nullOnDelete();
                $table->index('asignado_a');
            }
            if (!Schema::hasColumn('consultas_web', 'asignado_en')) {
                $table->timestamp('asignado_en')->nullable();
            }
            if (!Schema::hasColumn('consultas_web', 'contactado_en')) {
                $table->timestamp('contactado_en')->nullable();
            }
            if (!Schema::hasColumn('consultas_web', 'venta_id')) {
                $table->unsignedBigInteger('venta_id')->nullable()->comment('si se concretó');
                $table->foreign('venta_id')->references('id')->on('ventas')->nullOnDelete();
            }
            if (!Schema::hasColumn('consultas_web', 'motivo_perdido')) {
                $table->string('motivo_perdido', 200)->nullable();
            }
            if (!Schema::hasColumn('consultas_web', 'notas_internas')) {
                $table->text('notas_internas')->nullable();
            }
            if (!Schema::hasColumn('consultas_web', 'ip_address')) {
                $table->string('ip_address', 45)->nullable();
            }
            if (!Schema::hasColumn('consultas_web', 'user_agent')) {
                $table->string('user_agent', 500)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('consultas_web', function (Blueprint $table) {
            $table->dropForeign(['asignado_a']);
            $table->dropForeign(['venta_id']);
            $table->dropColumn([
                'asignado_a', 'asignado_en', 'contactado_en', 'venta_id',
                'motivo_perdido', 'notas_internas', 'ip_address', 'user_agent',
            ]);
        });
    }
};
