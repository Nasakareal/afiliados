<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('actividades', function (Blueprint $table) {
            $table->unsignedSmallInteger('distrito_local')->nullable()->after('creado_por');
            $table->index('distrito_local', 'actividades_distrito_local_index');
        });

        DB::table('actividades')
            ->select('id', 'creado_por')
            ->orderBy('id')
            ->chunkById(200, function ($activities) {
                $districts = DB::table('users')
                    ->whereIn('id', $activities->pluck('creado_por')->unique())
                    ->pluck('distrito_local', 'id');

                foreach ($activities as $activity) {
                    DB::table('actividades')
                        ->where('id', $activity->id)
                        ->update(['distrito_local' => $districts[$activity->creado_por] ?? null]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('actividades', function (Blueprint $table) {
            $table->dropIndex('actividades_distrito_local_index');
            $table->dropColumn('distrito_local');
        });
    }
};
