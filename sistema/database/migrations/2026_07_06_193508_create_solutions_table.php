<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('solutions', function (Blueprint $table) {
            $table->id();
            $table->string('key', 40)->unique(); // clave interna estable (estrategia, automatizacion…)
            $table->string('icon', 30)->default('target');
            $table->json('titulo');   // traducible {es,en,pt}
            $table->json('pilar');
            $table->json('problema');
            $table->json('como');     // traducible: {es:[...], en:[...], pt:[...]}
            $table->json('cambia');
            $table->unsignedInteger('sort')->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('solutions');
    }
};
