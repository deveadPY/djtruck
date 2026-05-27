<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('planes_cuotas', function (Blueprint $table) {
            if (!Schema::hasColumn('planes_cuotas', 'deleted_at')) {
                $table->softDeletes()->after('updated_at');
            }
            if (!Schema::hasColumn('planes_cuotas', 'updated_by')) {
                $table->unsignedBigInteger('updated_by')->nullable()->after('created_by');
            }
            if (!Schema::hasColumn('planes_cuotas', 'deleted_by')) {
                $table->unsignedBigInteger('deleted_by')->nullable()->after('updated_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('planes_cuotas', function (Blueprint $table) {
            if (Schema::hasColumn('planes_cuotas', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
            if (Schema::hasColumn('planes_cuotas', 'updated_by')) {
                $table->dropColumn('updated_by');
            }
            if (Schema::hasColumn('planes_cuotas', 'deleted_by')) {
                $table->dropColumn('deleted_by');
            }
        });
    }
};
