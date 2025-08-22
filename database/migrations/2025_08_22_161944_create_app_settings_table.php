<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('app_settings', function (Blueprint $t) {
            $t->id();
            $t->boolean('captura_habilitada')->default(true);
            $t->string('motivo_bloqueo', 255)->nullable();
            $t->timestamps();
        });

        // Deja creada una fila por defecto (habilitado)
        // Nota: Si prefieres, crea un seeder en lugar de esto.
        if (Schema::hasTable('app_settings')) {
            \Illuminate\Support\Facades\DB::table('app_settings')->insert([
                'captura_habilitada' => true,
                'motivo_bloqueo'     => null,
                'created_at'         => now(),
                'updated_at'         => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('app_settings');
    }
};
