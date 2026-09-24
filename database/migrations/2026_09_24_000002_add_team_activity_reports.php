<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('actividades', function (Blueprint $table): void {
            $table->string('origen', 30)->default('oficial')->after('tipo');
            $table->string('estado_revision', 30)->default('aprobada')->after('estado');
            $table->foreignId('revisado_por')->nullable()->after('estado_revision')
                ->constrained('users')->nullOnDelete();
            $table->timestamp('revisado_en')->nullable()->after('revisado_por');
            $table->string('evidencia_path', 500)->nullable()->after('evidencia_url');
            $table->string('evidencia_nombre', 255)->nullable()->after('evidencia_path');

            $table->index(['origen', 'estado_revision'], 'actividades_origen_revision_index');
        });

        Schema::create('actividad_participantes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('actividad_id')->constrained('actividades')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('asistio_en')->nullable();
            $table->timestamps();
            $table->unique(['actividad_id', 'user_id'], 'actividad_participante_unique');
        });

        $permissionId = DB::table('permissions')
            ->where('name', 'actividades.reportar')->where('guard_name', 'web')->value('id');
        if (!$permissionId) {
            $permissionId = DB::table('permissions')->insertGetId([
                'name' => 'actividades.reportar',
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        $roleIds = DB::table('roles')
            ->whereIn('name', ['SuperAdmin', 'Admin', 'Coordinador', 'Capturista'])
            ->where('guard_name', 'web')->pluck('id');
        foreach ($roleIds as $roleId) {
            DB::table('role_has_permissions')->insertOrIgnore([
                'permission_id' => $permissionId,
                'role_id' => $roleId,
            ]);
        }
    }

    public function down(): void
    {
        $permissionId = DB::table('permissions')
            ->where('name', 'actividades.reportar')->where('guard_name', 'web')->value('id');
        if ($permissionId) {
            DB::table('role_has_permissions')->where('permission_id', $permissionId)->delete();
            DB::table('model_has_permissions')->where('permission_id', $permissionId)->delete();
            DB::table('permissions')->where('id', $permissionId)->delete();
        }
        Schema::dropIfExists('actividad_participantes');
        Schema::table('actividades', function (Blueprint $table): void {
            $table->dropIndex('actividades_origen_revision_index');
            $table->dropConstrainedForeignId('revisado_por');
            $table->dropColumn([
                'origen', 'estado_revision', 'revisado_en',
                'evidencia_path', 'evidencia_nombre',
            ]);
        });
    }
};
