<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Consultas que llegan desde el sitio web público
        Schema::create('consultas_web', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehiculo_id')->nullable()->constrained('vehiculos')->nullOnDelete();
            $table->string('nombre', 100);
            $table->string('telefono', 50);
            $table->string('email', 150)->nullable();
            $table->enum('canal', ['WhatsApp', 'Formulario'])->default('Formulario');
            $table->enum('estado', ['nuevo', 'contactado', 'cerrado', 'perdido'])->default('nuevo');
            $table->text('mensaje')->nullable();
            $table->timestamps();

            $table->index('estado');
            $table->index('created_at');
        });

        // Configuración del sitio web público
        Schema::create('configuracion_sitio', function (Blueprint $table) {
            $table->id();
            $table->string('clave', 80)->unique();
            $table->text('valor')->nullable();
            $table->timestamps();
        });

        // Datos iniciales de configuración
        DB::table('configuracion_sitio')->insert([
            ['clave' => 'nombre_sitio',       'valor' => 'DJ Trucks & Cars',       'created_at' => now(), 'updated_at' => now()],
            ['clave' => 'wa_numero',           'valor' => '595XXXXXXXXX',           'created_at' => now(), 'updated_at' => now()],
            ['clave' => 'wa_numero_display',   'valor' => '+595 XXX XXX XXX',       'created_at' => now(), 'updated_at' => now()],
            ['clave' => 'email_contacto',      'valor' => 'info@djtrucks.com.py',   'created_at' => now(), 'updated_at' => now()],
            ['clave' => 'direccion',           'valor' => 'Ciudad del Este, Paraguay', 'created_at' => now(), 'updated_at' => now()],
            ['clave' => 'horario_semana',      'valor' => '8:00 – 18:00',           'created_at' => now(), 'updated_at' => now()],
            ['clave' => 'horario_sabado',      'valor' => '8:00 – 13:00',           'created_at' => now(), 'updated_at' => now()],
            ['clave' => 'instagram',           'valor' => 'djtruckspy',             'created_at' => now(), 'updated_at' => now()],
            ['clave' => 'facebook',            'valor' => 'djtruckspy',             'created_at' => now(), 'updated_at' => now()],
            ['clave' => 'erp_base_url',        'valor' => 'http://localhost/djtrucks/public', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('consultas_web');
        Schema::dropIfExists('configuracion_sitio');
    }
};
