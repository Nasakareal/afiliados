<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('afiliados', function (Blueprint $t) {
            $t->id();

            // Quién lo capturó (para conteos por usuario)
            $t->foreignId('capturista_id')->constrained('users')->cascadeOnDelete();

            // Datos generales de la persona
            $t->string('nombre', 120);
            $t->string('apellido_paterno', 120)->nullable();
            $t->string('apellido_materno', 120)->nullable();

            // Demográficos
            $t->unsignedTinyInteger('edad')->nullable();
            $t->enum('sexo', ['M','F','Otro'])->nullable();

            // Contacto
            $t->string('telefono', 30)->nullable();
            $t->string('email', 150)->nullable();

            // Ubicación / domicilio para filtros y mapa
            $t->string('municipio', 120);
            $t->string('cve_mun', 3)->nullable();
            $t->string('localidad', 150)->nullable();
            $t->string('colonia', 150)->nullable();
            $t->string('calle', 150)->nullable();
            $t->string('numero_ext', 20)->nullable();
            $t->string('numero_int', 20)->nullable();
            $t->string('cp', 10)->nullable();
            $t->decimal('lat', 10, 7)->nullable();
            $t->decimal('lng', 10, 7)->nullable();

            // Estructura electoral
            $t->string('seccion', 6)->nullable();
            $t->unsignedSmallInteger('distrito_federal')->nullable();
            $t->unsignedSmallInteger('distrito_local')->nullable();

            // Perfil / notas (lo que pediste como “pequeño perfil”)
            $t->text('perfil')->nullable();
            $t->text('observaciones')->nullable();

            // Estado de registro (por si validan después)
            $t->enum('estatus', ['pendiente','validado','descartado'])->default('pendiente');

            // Fecha útil (cuando quedó convencido / se capturó)
            $t->timestamp('fecha_convencimiento')->nullable();

            $t->timestamps();
            $t->softDeletes();

            // Índices para filtros y agregaciones
            $t->index(['municipio']);
            $t->index(['seccion']);
            $t->index(['distrito_federal','distrito_local']);
            $t->index(['lat','lng']);
            $t->index(['estatus']);
            $t->index(['capturista_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('afiliados');
    }
};
