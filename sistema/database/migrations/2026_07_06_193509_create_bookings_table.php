<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained()->cascadeOnDelete();
            $table->dateTime('starts_at')->index(); // en UTC
            $table->dateTime('ends_at');
            // confirmada | cancelada | realizada
            $table->string('status', 20)->default('confirmada')->index();
            $table->string('visitor_timezone', 60)->nullable();
            $table->text('tema')->nullable(); // qué quiere tratar en la sesión
            $table->timestamps();
            // La disponibilidad del horario se valida en la capa de aplicación.
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
