<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable()->index();
            // Teléfono normalizado E.164 sin "+" (formato wa.me), p. ej. 573001234567
            $table->string('phone_wa', 20)->nullable()->index();
            $table->string('phone_dial', 5)->nullable();
            $table->char('country', 2)->nullable()->index();
            $table->string('company')->nullable();
            $table->string('role')->nullable();
            $table->string('industry', 60)->nullable()->index();
            // micro | pequena | mediana | grande | corporativa
            $table->string('company_size', 20)->nullable();
            // nuevo | contactado | calificado | propuesta | cliente | descartado
            $table->string('status', 20)->default('nuevo')->index();
            // Primer punto de contacto: contacto | descarga | diagnostico | reserva | newsletter
            $table->string('source', 30)->nullable();
            $table->char('locale', 2)->default('es');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
