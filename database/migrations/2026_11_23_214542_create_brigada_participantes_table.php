<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('brigada_participantes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('brigada_id')
                ->constrained('brigadas')
                ->restrictOnDelete();

            $table->foreignId('user_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->dateTime('asistio_en');
            $table->string('evidencia_path', 500);
            $table->string('evidencia_nombre', 255)->nullable();
            $table->text('observaciones')->nullable();

            $table->timestamps();

            $table->unique(['brigada_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('brigada_participantes');
    }
};
