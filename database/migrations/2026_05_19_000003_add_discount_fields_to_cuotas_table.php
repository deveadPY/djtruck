<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cuotas', function (Blueprint $table) {
            $table->decimal('descuento_anticipo', 20, 4)->default(0)->after('monto_pagado');
            $table->decimal('descuento_liquidacion', 20, 4)->default(0)->after('descuento_anticipo');
        });
    }

    public function down(): void
    {
        Schema::table('cuotas', function (Blueprint $table) {
            $table->dropColumn(['descuento_anticipo', 'descuento_liquidacion']);
        });
    }
};
