<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('secciones', function (Blueprint $t) {
            $t->id();

            // Claves electorales
            $t->string('cve_ent', 2)->default('16');
            $t->string('cve_mun', 3);
            $t->string('municipio', 120);

            // Sección electoral (hasta 4 dígitos; dejamos 6 por seguridad)
            $t->string('seccion', 6);

            // Distritos
            $t->unsignedSmallInteger('distrito_federal')->nullable();
            $t->unsignedSmallInteger('distrito_local')->nullable();

            // Lista nominal actual (para % de convencidos)
            $t->unsignedInteger('lista_nominal')->nullable();

            // Centroide opcional (para mapa)
            $t->decimal('centroid_lat', 10, 7)->nullable();
            $t->decimal('centroid_lng', 10, 7)->nullable();

            $t->timestamps();

            // Unicidad por clave estatal, municipio y sección
            $t->unique(['cve_ent','cve_mun','seccion'], 'secciones_cve_unique');

            // Índices para filtros
            $t->index(['municipio']);
            $t->index(['seccion']);
            $t->index(['distrito_federal','distrito_local']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('secciones');
    }
};
