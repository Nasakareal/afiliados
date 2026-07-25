<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lonas', function (Blueprint $table) {
            $table->id();
            $table->string('seccion', 10);
            $table->string('direccion', 500);
            $table->text('ubicacion_google')->nullable();
            $table->decimal('lat', 10, 7);
            $table->decimal('lng', 10, 7);
            $table->string('foto_path');
            $table->string('foto_nombre_original')->nullable();
            $table->unsignedBigInteger('foto_bytes_original')->nullable();
            $table->unsignedBigInteger('foto_bytes_final')->nullable();
            $table->string('responsable', 150);
            $table->foreignId('capturado_por')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('seccion');
            $table->index(['lat', 'lng']);
            $table->index('capturado_por');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lonas');
    }
};
