<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('brigadas', function (Blueprint $table) {
            $table->id();

            $table->foreignId('seccion_id')
                ->constrained('secciones')
                ->restrictOnDelete();

            $table->string('responsable', 150);
            $table->string('telefono', 30)->nullable();
            $table->string('direccion', 500)->nullable();
            $table->text('ubicacion_google')->nullable();

            $table->decimal('lat', 10, 7);
            $table->decimal('lng', 10, 7);

            $table->boolean('activo')->default(true);
            $table->text('observaciones')->nullable();

            $table->foreignId('capturado_por')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index('responsable');
            $table->index('activo');
            $table->index(['lat', 'lng']);
            $table->index(['seccion_id', 'activo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('brigadas');
    }
};
