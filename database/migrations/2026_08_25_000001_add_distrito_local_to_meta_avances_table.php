<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('meta_avances', function (Blueprint $table) {
            $table->dropUnique('meta_avances_tipo_municipio_periodo_unique');
            $table->unsignedSmallInteger('distrito_local')->nullable()->after('cve_mun');
            $table->unique(
                ['tipo', 'cve_mun', 'distrito_local', 'fecha_inicio', 'fecha_fin'],
                'meta_avances_tipo_municipio_distrito_periodo_unique'
            );
            $table->index(['distrito_local', 'tipo'], 'meta_avances_distrito_tipo_index');
        });
    }

    public function down(): void
    {
        DB::table('meta_avances')
            ->select('tipo', 'cve_mun', 'fecha_inicio', 'fecha_fin')
            ->groupBy('tipo', 'cve_mun', 'fecha_inicio', 'fecha_fin')
            ->havingRaw('COUNT(*) > 1')
            ->get()
            ->each(function ($duplicada) {
                $filas = DB::table('meta_avances')
                    ->where('tipo', $duplicada->tipo)
                    ->where('cve_mun', $duplicada->cve_mun)
                    ->where('fecha_inicio', $duplicada->fecha_inicio)
                    ->where('fecha_fin', $duplicada->fecha_fin)
                    ->orderBy('id')
                    ->get();

                DB::table('meta_avances')
                    ->where('id', $filas->first()->id)
                    ->update(['meta' => $filas->sum('meta')]);

                DB::table('meta_avances')
                    ->whereIn('id', $filas->skip(1)->pluck('id'))
                    ->delete();
            });

        Schema::table('meta_avances', function (Blueprint $table) {
            $table->dropUnique('meta_avances_tipo_municipio_distrito_periodo_unique');
            $table->dropIndex('meta_avances_distrito_tipo_index');
            $table->dropColumn('distrito_local');
            $table->unique(
                ['tipo', 'cve_mun', 'fecha_inicio', 'fecha_fin'],
                'meta_avances_tipo_municipio_periodo_unique'
            );
        });
    }
};
