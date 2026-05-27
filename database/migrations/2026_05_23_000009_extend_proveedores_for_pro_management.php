<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('proveedores', function (Blueprint $table) {
            if (!Schema::hasColumn('proveedores', 'direccion')) {
                $table->string('direccion', 300)->nullable()->after('telefono');
            }
            if (!Schema::hasColumn('proveedores', 'ciudad')) {
                $table->string('ciudad', 100)->nullable()->after('direccion');
            }
            if (!Schema::hasColumn('proveedores', 'sitio_web')) {
                $table->string('sitio_web', 200)->nullable()->after('ciudad');
            }
            if (!Schema::hasColumn('proveedores', 'dias_credito')) {
                $table->integer('dias_credito')->default(0)->after('moneda_principal');
            }
            if (!Schema::hasColumn('proveedores', 'descuento_pago_anticipado_pct')) {
                $table->decimal('descuento_pago_anticipado_pct', 5, 2)->default(0)->after('dias_credito');
            }
            if (!Schema::hasColumn('proveedores', 'contacto_principal')) {
                $table->string('contacto_principal', 150)->nullable();
            }
            if (!Schema::hasColumn('proveedores', 'banco')) {
                $table->string('banco', 100)->nullable();
            }
            if (!Schema::hasColumn('proveedores', 'cuenta_bancaria')) {
                $table->string('cuenta_bancaria', 100)->nullable();
            }
            if (!Schema::hasColumn('proveedores', 'score_actual')) {
                $table->decimal('score_actual', 5, 2)->default(0)->comment('0-100, calculado por SupplierScoreService');
            }
            if (!Schema::hasColumn('proveedores', 'observaciones')) {
                $table->text('observaciones')->nullable();
            }
            if (!Schema::hasColumn('proveedores', 'updated_by')) {
                $table->unsignedBigInteger('updated_by')->nullable();
            }
            if (!Schema::hasColumn('proveedores', 'deleted_by')) {
                $table->unsignedBigInteger('deleted_by')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('proveedores', function (Blueprint $table) {
            $table->dropColumn([
                'direccion', 'ciudad', 'sitio_web',
                'dias_credito', 'descuento_pago_anticipado_pct',
                'contacto_principal', 'banco', 'cuenta_bancaria',
                'score_actual', 'observaciones', 'updated_by', 'deleted_by',
            ]);
        });
    }
};
