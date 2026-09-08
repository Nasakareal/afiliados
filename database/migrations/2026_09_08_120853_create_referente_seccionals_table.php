<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('referentes_seccionales', function (Blueprint $table) {
            $table->id();

            $table->string('cve_mun', 3);
            $table->string('municipio', 120);

            $table->string('seccion', 6);
            $table->unsignedInteger('distrito_local')->nullable();
            $table->unsignedInteger('distrito_federal')->nullable();

            $table->string('nombre_completo', 150);
            $table->string('telefono', 20)->nullable();
            $table->string('telefono_alternativo', 20)->nullable();
            $table->string('correo', 150)->nullable();

            $table->string('cargo', 150)->nullable();
            $table->string('organizacion', 150)->nullable();

            $table->string('localidad', 150)->nullable();
            $table->string('colonia', 150)->nullable();
            $table->text('direccion')->nullable();

            $table->boolean('whatsapp')->default(true);
            $table->boolean('activo')->default(true);

            $table->text('observaciones')->nullable();

            $table->timestamps();

            $table->index('cve_mun');
            $table->index('municipio');
            $table->index('seccion');
            $table->index('distrito_local');
            $table->index('distrito_federal');
            $table->index('nombre_completo');
            $table->index('telefono');
            $table->index('activo');

            $table->index(['cve_mun', 'seccion']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('referentes_seccionales');
    }
};
