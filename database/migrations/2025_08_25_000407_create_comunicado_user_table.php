<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('comunicado_user', function (Blueprint $t) {
            $t->id();

            $t->foreignId('comunicado_id')->constrained('comunicados')->cascadeOnDelete();
            $t->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            // tracking
            $t->timestamp('entregado_at')->nullable();
            $t->timestamp('leido_at')->nullable();

            $t->timestamps();

            // no repetir lecturas por usuario
            $t->unique(['comunicado_id','user_id']);

            // consultas típicas
            $t->index(['user_id','leido_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comunicado_user');
    }
};
