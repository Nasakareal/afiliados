<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class AfiliadosResumenService
{
    public function rebuild(): array
    {
        return DB::transaction(function (): array {
            DB::table('afiliados_resumen')->delete();

            $sqlite = DB::getDriverName() === 'sqlite';
            $now = $sqlite
                ? 'CURRENT_TIMESTAMP'
                : 'NOW()';
            $key = $sqlite
                ? "QUOTE(COALESCE(cve_mun, '')) || '|' || QUOTE(COALESCE(municipio, '')) || '|' || QUOTE(COALESCE(seccion, '')) || '|' || QUOTE(COALESCE(distrito_local, 0)) || '|' || QUOTE(COALESCE(distrito_federal, 0)) || '|' || QUOTE(COALESCE(capturista_id, 0)) || '|' || QUOTE(SUBSTR(TRIM(COALESCE(perfil, '')), 1, 191)) || '|' || QUOTE(COALESCE(estatus, ''))"
                : "SHA2(CONCAT_WS(CHAR(31), COALESCE(cve_mun, ''), COALESCE(municipio, ''), COALESCE(seccion, ''), COALESCE(distrito_local, 0), COALESCE(distrito_federal, 0), COALESCE(capturista_id, 0), LEFT(TRIM(COALESCE(perfil, '')), 191), COALESCE(estatus, '')), 256)";

            DB::statement(<<<SQL
                INSERT INTO afiliados_resumen (
                    dimension_key, cve_mun, municipio, seccion, distrito_local, distrito_federal,
                    capturista_id, referente, estatus, total, created_at, updated_at
                )
                SELECT
                    {$key},
                    COALESCE(cve_mun, ''),
                    COALESCE(municipio, ''),
                    COALESCE(seccion, ''),
                    COALESCE(distrito_local, 0),
                    COALESCE(distrito_federal, 0),
                    COALESCE(capturista_id, 0),
                    SUBSTR(TRIM(COALESCE(perfil, '')), 1, 191),
                    COALESCE(estatus, ''),
                    COUNT(*),
                    {$now},
                    {$now}
                FROM afiliados
                WHERE deleted_at IS NULL
                GROUP BY
                    {$key},
                    COALESCE(cve_mun, ''),
                    COALESCE(municipio, ''),
                    COALESCE(seccion, ''),
                    COALESCE(distrito_local, 0),
                    COALESCE(distrito_federal, 0),
                    COALESCE(capturista_id, 0),
                    SUBSTR(TRIM(COALESCE(perfil, '')), 1, 191),
                    COALESCE(estatus, '')
            SQL);

            return [
                'rows' => DB::table('afiliados_resumen')->count(),
                'total' => (int) DB::table('afiliados_resumen')->sum('total'),
            ];
        });
    }
}
