<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('touchpoints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained()->cascadeOnDelete();
            // contacto | descarga | diagnostico | reserva | newsletter
            $table->string('type', 30)->index();
            // Título legible, p. ej. "Descargó: Playbook de automatización"
            $table->string('title');
            // Datos completos de la interacción (mensaje, respuestas, resultado…)
            $table->json('payload')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('touchpoints');
    }
};
