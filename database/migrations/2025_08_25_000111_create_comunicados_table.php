<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('comunicados', function (Blueprint $t) {
            $t->id();

            // autor del comunicado
            $t->foreignId('creado_por')->constrained('users')->cascadeOnDelete();

            // contenido
            $t->string('titulo', 180);
            $t->text('contenido'); // markdown/html/texto

            // ventana de visibilidad (opcional)
            $t->timestamp('visible_desde')->nullable();
            $t->timestamp('visible_hasta')->nullable();

            // estado del comunicado
            $t->enum('estado', ['borrador','publicado','archivado'])->default('borrador');

            // segmentación opcional (roles, municipios, secciones, etc.)
            $t->json('filtros')->nullable();

            $t->timestamps();
            $t->softDeletes();

            // índices útiles
            $t->index(['estado']);
            $t->index(['visible_desde','visible_hasta']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comunicados');
    }
};
