<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehiculos', function (Blueprint $table) {
            // Especificaciones técnicas para el catálogo público
            $table->string('motor_descripcion', 100)->nullable()->after('numero_motor');
            $table->unsignedSmallInteger('potencia_hp')->nullable()->after('motor_descripcion');
            $table->unsignedSmallInteger('par_nm')->nullable()->after('potencia_hp');
            $table->enum('tipo_traccion', ['4x2', '4x4', '6x2', '6x4', '8x4', '6x6', '8x8'])->nullable()->after('par_nm');
            $table->enum('tipo_transmision', ['MANUAL', 'AUTOMATICA', 'AUTOMATIZADA'])->nullable()->after('tipo_traccion');
            $table->string('cabina', 80)->nullable()->after('tipo_transmision');
            $table->string('norma_euro', 10)->nullable()->after('cabina');
            $table->decimal('peso_bruto_t', 8, 2)->nullable()->after('norma_euro');
            $table->unsignedSmallInteger('deposito_litros')->nullable()->after('peso_bruto_t');
            $table->string('neumaticos', 50)->nullable()->after('deposito_litros');

            // Información comercial para el sitio web
            $table->text('descripcion_publica')->nullable()->after('neumaticos');
            $table->json('equipamiento')->nullable()->after('descripcion_publica');

            // Control de publicación
            $table->boolean('publicar_en_web')->default(false)->after('equipamiento');
            $table->boolean('mostrar_precio')->default(true)->after('publicar_en_web');
        });
    }

    public function down(): void
    {
        Schema::table('vehiculos', function (Blueprint $table) {
            $table->dropColumn([
                'motor_descripcion',
                'potencia_hp',
                'par_nm',
                'tipo_traccion',
                'tipo_transmision',
                'cabina',
                'norma_euro',
                'peso_bruto_t',
                'deposito_litros',
                'neumaticos',
                'descripcion_publica',
                'equipamiento',
                'publicar_en_web',
                'mostrar_precio',
            ]);
        });
    }
};
