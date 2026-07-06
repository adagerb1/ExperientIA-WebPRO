<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resources', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            // article (lectura libre) | download (descargable con formulario)
            $table->string('type', 20)->default('article')->index();
            $table->json('tipo_label');   // Guía ejecutiva, Playbook…
            $table->json('titulo');
            $table->json('extracto');
            $table->json('cuerpo')->nullable();      // artículos: contenido rico
            $table->string('file_path')->nullable(); // descargables: archivo en storage
            $table->unsignedInteger('downloads')->default(0);
            $table->unsignedInteger('sort')->default(0);
            $table->boolean('active')->default(true);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resources');
    }
};
