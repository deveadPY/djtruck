<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('documentos', function (Blueprint $table) {
            $table->id();
            $table->string('documentable_type', 50);
            $table->unsignedBigInteger('documentable_id');
            $table->string('ruta', 500);
            $table->string('nombre_original', 255);
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('tamano_bytes')->nullable();
            $table->string('descripcion', 255)->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['documentable_type', 'documentable_id'], 'doc_polymorphic_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documentos');
    }
};
