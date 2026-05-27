<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('facturas_electronicas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('venta_id');
            $table->enum('tipo_documento', ['FACTURA','NOTA_CREDITO','NOTA_DEBITO','AUTOFACTURA'])->default('FACTURA');
            $table->string('numero_documento', 30);
            $table->string('cdc', 64)->nullable()->index();           // Código de Control (Paraguay)
            $table->string('provider', 50);                            // null, facturasend, ekuatia, etc.
            $table->enum('estado', [
                'PENDIENTE','ENVIADA','APROBADO','RECHAZADO','CANCELADO','ERROR'
            ])->default('PENDIENTE');

            $table->decimal('total_neto', 20, 4);
            $table->decimal('total_iva',  20, 4)->default(0);
            $table->decimal('total_general', 20, 4);
            $table->char('moneda', 3)->default('PYG');

            $table->string('url_pdf', 500)->nullable();
            $table->string('url_xml', 500)->nullable();
            $table->text('qr_code')->nullable();

            $table->string('error_code', 50)->nullable();
            $table->text('error_message')->nullable();
            $table->json('raw_response')->nullable();

            $table->timestamp('emitida_at')->nullable();
            $table->timestamp('aprobada_at')->nullable();
            $table->timestamp('cancelada_at')->nullable();
            $table->string('motivo_cancelacion', 255)->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('venta_id');
            $table->index('estado');
            $table->index(['provider', 'estado']);

            $table->foreign('venta_id')->references('id')->on('ventas');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facturas_electronicas');
    }
};
