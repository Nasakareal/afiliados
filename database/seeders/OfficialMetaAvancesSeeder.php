<?php

namespace Database\Seeders;

use App\Models\MetaAvance;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class OfficialMetaAvancesSeeder extends Seeder
{
    private const FECHA_INICIO = '2000-01-01';
    private const FECHA_FIN = '2099-12-31';

    public function run(): void
    {
        $metas = require database_path('data/official_meta_avances.php');

        if (count($metas) !== 117 || array_sum(array_column($metas, 'meta')) !== 326400) {
            throw new RuntimeException('El catálogo de metas oficiales está incompleto o fue alterado.');
        }

        foreach ($metas as $meta) {
            if ($meta['meta_diaria'] !== $meta['secciones'] * 2 || $meta['meta'] !== $meta['secciones'] * 120) {
                throw new RuntimeException("La meta oficial de {$meta['municipio']} no coincide con sus secciones.");
            }
        }

        $ahora = now();
        $filas = array_map(static fn(array $meta) => [
            'tipo' => MetaAvance::TIPO_CONVENCIDOS,
            'cve_mun' => $meta['cve_mun'],
            'distrito_local' => $meta['distrito_local'],
            'meta' => $meta['meta'],
            'fecha_inicio' => self::FECHA_INICIO,
            'fecha_fin' => self::FECHA_FIN,
            'activa' => true,
            'asignado_por' => null,
            'created_at' => $ahora,
            'updated_at' => $ahora,
        ], $metas);

        DB::transaction(function () use ($filas) {
            // Sustituye únicamente las metas de convencidos; el archivo oficial no define metas de lonas.
            DB::table('meta_avances')
                ->where('tipo', MetaAvance::TIPO_CONVENCIDOS)
                ->delete();

            foreach (array_chunk($filas, 100) as $lote) {
                DB::table('meta_avances')->insert($lote);
            }
        });
    }
}
