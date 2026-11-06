<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('actividades', function (Blueprint $table): void {
            $table->text('ubicacion_google')->nullable()->after('lugar');
        });

        Schema::table('actividad_participantes', function (Blueprint $table): void {
            $table->string('evidencia_path', 500)->nullable()->after('asistio_en');
            $table->string('evidencia_nombre', 255)->nullable()->after('evidencia_path');
        });
    }

    public function down(): void
    {
        Schema::table('actividad_participantes', function (Blueprint $table): void {
            $table->dropColumn(['evidencia_path', 'evidencia_nombre']);
        });

        Schema::table('actividades', function (Blueprint $table): void {
            $table->dropColumn('ubicacion_google');
        });
    }
};
