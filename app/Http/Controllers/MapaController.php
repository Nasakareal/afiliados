<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MapaController extends Controller
{
    private function normalize($s): string
    {
        $s = (string)($s ?? '');
        $s = \Normalizer::normalize($s, \Normalizer::FORM_D) ?: $s;
        $s = preg_replace('/[\p{Mn}]+/u', '', $s);
        $s = preg_replace('/[^A-Z0-9 ]/iu', '', $s);
        return strtoupper(trim($s));
    }

    public function index(Request $request)
    {
        // Para el endpoint /data se mantiene el filtro por estatus si lo usas,
        // pero para pintar el mapa (choropleth) SIEMPRE contaremos a TODOS.
        $estatus = $request->query('estatus', 'validado');
        $allowed = ['validado','pendiente','descartado','todos'];
        if (!in_array($estatus, $allowed, true)) $estatus = 'validado';

        // === Conteos por municipio SIN filtrar (todos): total, por estatus y derivados
        $rows = DB::table('afiliados')
            ->selectRaw("
                LPAD(cve_mun,3,'0')              as cve_mun,
                municipio,
                COUNT(*)                          as total,
                SUM(CASE WHEN estatus='validado'   THEN 1 ELSE 0 END) as afiliados,
                SUM(CASE WHEN estatus='descartado' THEN 1 ELSE 0 END) as no_afiliados,
                SUM(CASE WHEN estatus='pendiente'  THEN 1 ELSE 0 END) as pendientes
            ")
            ->whereNull('deleted_at')
            ->groupBy('cve_mun','municipio')
            ->get();

        // Mapa para color (todos), y mapas de stats por CVEGEO y por nombre normalizado
        $conteo = [];             // choropleth (todos)
        $conteoPorNombre = [];    // fallback choropleth por nombre
        $statsCVE = [];           // CVEGEO => {total, afiliados, no_afiliados, pendientes, convencidos}
        $statsNombre = [];        // NOMGEO norm => { ... }

        foreach ($rows as $r) {
            $cvegeo = '16' . $r->cve_mun;

            $afiliados    = (int)$r->afiliados;
            $no_afiliados = (int)$r->no_afiliados;
            $pendientes   = (int)$r->pendientes;
            $total        = (int)$r->total;
            $convencidos  = $afiliados + $no_afiliados;

            $conteo[$cvegeo] = $total;

            $norm = $this->normalize($r->municipio);
            $conteoPorNombre[$norm] = $total;

            $stats = [
                'total'        => $total,
                'afiliados'    => $afiliados,
                'no_afiliados' => $no_afiliados,
                'pendientes'   => $pendientes,
                'convencidos'  => $convencidos,
            ];

            $statsCVE[$cvegeo]    = $stats;
            $statsNombre[$norm]   = $stats;
        }

        // === Capas adicionales
        $layers = [];
        $dir = public_path('maps/out');
        if (is_dir($dir)) {
            $files = glob($dir.'/*.geojson');
            sort($files);
            foreach ($files as $path) {
                $file   = basename($path);
                $base   = pathinfo($file, PATHINFO_FILENAME);
                $pretty = str_replace('_',' ', ucwords(strtolower($base), '_'));
                $layers[] = [
                    'id'   => $base,
                    'name' => $pretty,
                    'url'  => asset('maps/out/'.$file),
                ];
            }
        }

        return view('mapa.index', [
            'conteo'          => $conteo,
            'conteoPorNombre' => $conteoPorNombre,
            'estatus'         => $estatus,
            'layers'          => $layers,
            'statsCVE'        => $statsCVE,
            'statsNombre'     => $statsNombre,
        ]);
    }

    public function data(Request $request)
    {
        // Aquí sí respetamos el filtro si lo ocupas para puntos/cluster
        $estatus = $request->query('estatus', 'validado');
        $allowed = ['validado','pendiente','descartado','todos'];
        if (!in_array($estatus, $allowed, true)) $estatus = 'validado';

        $rows = DB::table('afiliados')
            ->select('id','nombre','apellido_paterno','apellido_materno','municipio','lat','lng')
            ->whereNull('deleted_at')
            ->when($estatus !== 'todos', fn($q)=>$q->where('estatus', $estatus))
            ->whereNotNull('lat')->whereNotNull('lng')
            ->limit(2000)
            ->get();

        return response()->json($rows);
    }
}
