<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_configuracion', function (Blueprint $table) {
            $table->id();
            $table->string('mailer', 20)->default('smtp');          // smtp | log | sendmail
            $table->string('host', 150)->nullable();                 // e.g. smtp.gmail.com
            $table->unsignedSmallInteger('port')->default(587);      // 587 | 465 | 25
            $table->string('encryption', 10)->nullable();            // tls | ssl | starttls
            $table->string('username', 150)->nullable();
            $table->text('password')->nullable();                    // stored with Crypt::encrypt()
            $table->string('from_address', 150)->nullable();
            $table->string('from_name', 150)->nullable();
            $table->boolean('activo')->default(false);               // false = use .env fallback
            $table->timestamps();
            $table->unsignedBigInteger('updated_by')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_configuracion');
    }
};
