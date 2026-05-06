<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('facturas_proveedores', function (Blueprint $table) {
            $table->unsignedBigInteger('compra_id')->nullable()->after('vehiculo_id');
            
            // Note: modifying an enum column in Laravel sometimes requires raw SQL if DBAL is not installed
            // or if we want to be safe across different versions. 
            // Since we are likely on MySQL/MariaDB based on the earlier DESCRIBE call.
        });

        // Add 'REPOSICION' to the enum
        DB::statement("ALTER TABLE facturas_proveedores MODIFY COLUMN destino ENUM('VEHICULO','GASTO_OPERATIVO','MIXTO','REPOSICION') NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('facturas_proveedores', function (Blueprint $table) {
            $table->dropColumn('compra_id');
        });

        DB::statement("ALTER TABLE facturas_proveedores MODIFY COLUMN destino ENUM('VEHICULO','GASTO_OPERATIVO','MIXTO') NOT NULL");
    }
};
