<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('stock_repuestos', function (Blueprint $table) {
            if (!Schema::hasColumn('stock_repuestos', 'categoria_id')) {
                $table->unsignedBigInteger('categoria_id')->nullable()->after('marca_compatible');
                $table->foreign('categoria_id')->references('id')->on('categorias_repuestos')->nullOnDelete();
                $table->index('categoria_id');
            }
            if (!Schema::hasColumn('stock_repuestos', 'ubicacion_id')) {
                $table->unsignedBigInteger('ubicacion_id')->nullable()->after('categoria_id');
                $table->foreign('ubicacion_id')->references('id')->on('ubicaciones_almacen')->nullOnDelete();
                $table->index('ubicacion_id');
            }
            if (!Schema::hasColumn('stock_repuestos', 'codigo_barras')) {
                $table->string('codigo_barras', 50)->nullable()->after('codigo')->index();
            }
            if (!Schema::hasColumn('stock_repuestos', 'stock_comprometido')) {
                $table->decimal('stock_comprometido', 12, 3)->default(0)->after('stock_actual');
            }
            if (!Schema::hasColumn('stock_repuestos', 'updated_by')) {
                $table->unsignedBigInteger('updated_by')->nullable()->after('created_by');
            }
            if (!Schema::hasColumn('stock_repuestos', 'deleted_by')) {
                $table->unsignedBigInteger('deleted_by')->nullable()->after('updated_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('stock_repuestos', function (Blueprint $table) {
            $table->dropForeign(['categoria_id']);
            $table->dropForeign(['ubicacion_id']);
            $table->dropColumn(['categoria_id', 'ubicacion_id', 'codigo_barras', 'stock_comprometido', 'updated_by', 'deleted_by']);
        });
    }
};
