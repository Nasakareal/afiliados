<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('actividades', function (Blueprint $t) {
            $t->id();

            $t->string('titulo', 180);
            $t->text('descripcion')->nullable();

            $t->dateTime('inicio');
            $t->dateTime('fin')->nullable();
            $t->boolean('all_day')->default(false);

            $t->string('lugar', 200)->nullable();

            $t->foreignId('creado_por')->constrained('users')->cascadeOnDelete();

            $t->enum('estado', ['programada','cancelada','realizada'])->default('programada');

            $t->timestamps();

            $t->index(['inicio']);
            $t->index(['creado_por']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('actividades');
    }
};
