<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('planes_cuotas', function (Blueprint $table) {
            $table->date('fecha_liquidacion')->nullable()->after('estado');
            $table->decimal('descuento_liquidacion_aplicado', 20, 4)->default(0)->after('fecha_liquidacion');
        });
    }

    public function down(): void
    {
        Schema::table('planes_cuotas', function (Blueprint $table) {
            $table->dropColumn(['fecha_liquidacion', 'descuento_liquidacion_aplicado']);
        });
    }
};
