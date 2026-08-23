<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meta_avances', function (Blueprint $table) {
            $table->id();
            $table->string('tipo', 30);
            $table->string('cve_mun', 3);
            $table->unsignedInteger('meta');
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->boolean('activa')->default(true);
            $table->foreignId('asignado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['tipo', 'cve_mun', 'fecha_inicio', 'fecha_fin'], 'meta_avances_tipo_municipio_periodo_unique');
            $table->index(['tipo', 'activa']);
            $table->index(['cve_mun', 'tipo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meta_avances');
    }
};
