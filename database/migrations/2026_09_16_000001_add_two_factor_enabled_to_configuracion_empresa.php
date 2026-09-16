<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('configuracion_empresa', function (Blueprint $table) {
            $table->boolean('two_factor_enabled')->default(true)->after('prefijo_factura');
        });
    }

    public function down(): void
    {
        Schema::table('configuracion_empresa', function (Blueprint $table) {
            $table->dropColumn('two_factor_enabled');
        });
    }
};
