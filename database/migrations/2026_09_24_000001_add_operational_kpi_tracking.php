<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('demo_batch', 64)->nullable()->after('distrito_local')->index();
        });

        Schema::table('actividades', function (Blueprint $table): void {
            $table->string('tipo', 80)->nullable()->after('titulo');
            $table->string('colonia', 150)->nullable()->after('lugar');
            $table->foreignId('responsable_id')->nullable()->after('creado_por')
                ->constrained('users')->nullOnDelete();
            $table->foreignId('capturista_id')->nullable()->after('responsable_id')
                ->constrained('users')->nullOnDelete();
            $table->unsignedInteger('asistentes')->nullable()->after('distrito_local');
            $table->string('evidencia_url', 500)->nullable()->after('asistentes');
            $table->timestamp('capturada_en')->nullable()->after('evidencia_url');
            $table->string('demo_batch', 64)->nullable()->after('capturada_en');

            $table->index(['responsable_id', 'estado'], 'actividades_responsable_estado_index');
            $table->index(['colonia', 'inicio'], 'actividades_colonia_inicio_index');
            $table->index('demo_batch', 'actividades_demo_batch_index');
        });

        Schema::table('afiliados', function (Blueprint $table): void {
            $table->foreignId('actividad_id')->nullable()->after('capturista_id')
                ->constrained('actividades')->nullOnDelete();
            $table->index(['actividad_id', 'estatus'], 'afiliados_actividad_estatus_index');
        });

        Schema::create('auditoria_cambios', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('usuario_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('entidad', 80);
            $table->unsignedBigInteger('entidad_id')->nullable();
            $table->string('accion', 30);
            $table->string('resumen', 255);
            $table->json('cambios')->nullable();
            $table->string('demo_batch', 64)->nullable();
            $table->timestamps();

            $table->index(['entidad', 'entidad_id'], 'auditoria_entidad_index');
            $table->index(['usuario_id', 'created_at'], 'auditoria_usuario_fecha_index');
            $table->index('demo_batch', 'auditoria_demo_batch_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auditoria_cambios');

        Schema::table('afiliados', function (Blueprint $table): void {
            $table->dropIndex('afiliados_actividad_estatus_index');
            $table->dropConstrainedForeignId('actividad_id');
        });

        Schema::table('actividades', function (Blueprint $table): void {
            $table->dropIndex('actividades_responsable_estado_index');
            $table->dropIndex('actividades_colonia_inicio_index');
            $table->dropIndex('actividades_demo_batch_index');
            $table->dropConstrainedForeignId('responsable_id');
            $table->dropConstrainedForeignId('capturista_id');
            $table->dropColumn([
                'tipo', 'colonia', 'asistentes', 'evidencia_url',
                'capturada_en', 'demo_batch',
            ]);
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex(['demo_batch']);
            $table->dropColumn('demo_batch');
        });
    }
};
