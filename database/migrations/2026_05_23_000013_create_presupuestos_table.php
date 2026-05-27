<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('presupuestos', function (Blueprint $table) {
            $table->id();
            $table->string('numero_presupuesto', 30)->unique();
            $table->unsignedBigInteger('cliente_id');
            $table->unsignedBigInteger('lead_id')->nullable();
            $table->unsignedBigInteger('vendedor_id')->nullable();
            $table->enum('estado', ['BORRADOR','ENVIADO','ACEPTADO','RECHAZADO','VENCIDO','CONVERTIDO'])->default('BORRADOR');

            $table->date('fecha_emision');
            $table->date('vigencia_hasta');
            $table->char('moneda', 3)->default('USD');
            $table->decimal('tasa_cambio', 20, 8)->default(1);

            $table->decimal('subtotal_usd', 20, 4)->default(0);
            $table->decimal('descuento_usd', 20, 4)->default(0);
            $table->decimal('total_usd', 20, 4)->default(0);

            $table->enum('modalidad_pago_sugerida', ['CONTADO','CUOTAS'])->default('CONTADO');
            $table->integer('cuotas_sugeridas')->nullable();

            $table->text('observaciones')->nullable();
            $table->text('terminos_condiciones')->nullable();

            $table->timestamp('enviado_at')->nullable();
            $table->timestamp('aceptado_at')->nullable();
            $table->timestamp('convertido_at')->nullable();
            $table->unsignedBigInteger('venta_id')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('cliente_id')->references('id')->on('clientes');
            $table->foreign('lead_id')->references('id')->on('consultas_web')->nullOnDelete();
            $table->foreign('vendedor_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('venta_id')->references('id')->on('ventas')->nullOnDelete();
            $table->index(['estado', 'vigencia_hasta']);
            $table->index('cliente_id');
        });

        Schema::create('presupuesto_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('presupuesto_id');
            $table->unsignedBigInteger('itemable_id');
            $table->string('itemable_type', 100);                  // App\...VehicleModel / RepuestoModel
            $table->string('descripcion', 300);
            $table->decimal('cantidad', 12, 3)->default(1);
            $table->decimal('precio_unitario_usd', 20, 4);
            $table->decimal('subtotal_usd', 20, 4);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('presupuesto_id')->references('id')->on('presupuestos')->cascadeOnDelete();
            $table->index('presupuesto_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('presupuesto_items');
        Schema::dropIfExists('presupuestos');
    }
};
