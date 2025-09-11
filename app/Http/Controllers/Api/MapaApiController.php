<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MapaApiController extends Controller
{
    public function index(Request $request)
    {
        $rows = DB::table('afiliados')
            ->selectRaw("
                LPAD(cve_mun, 3, '0')  as cve_mun,
                municipio,
                COUNT(*)               as total
            ")
            ->whereNull('deleted_at')
            ->groupBy('cve_mun','municipio')
            ->get();

        $conteoPorCVE = [];
        foreach ($rows as $r) {
            $conteoPorCVE['16' . $r->cve_mun] = (int) $r->total;
        }

        return response()->json([
            'conteo' => $conteoPorCVE,
        ]);
    }

    public function data(Request $request)
    {
        $allowed = ['validado','pendiente','descartado','todos'];
        $estatus = $request->query('estatus', 'validado');
        if (!in_array($estatus, $allowed, true)) {
            $estatus = 'validado';
        }

        $bbox = $request->query('bbox');
        $page  = max(1, (int) $request->query('page', 1));
        $limit = min(5000, max(100, (int) $request->query('limit', 1000)));
        $offset = ($page - 1) * $limit;

        $q = DB::table('afiliados')
            ->select('id','nombre','apellido_paterno','apellido_materno','municipio','lat','lng')
            ->whereNull('deleted_at')
            ->whereNotNull('lat')
            ->whereNotNull('lng');

        if ($estatus !== 'todos') {
            $q->where('estatus', $estatus);
        }

        if ($bbox) {
            $parts = explode(',', $bbox);
            if (count($parts) === 4) {
                [$minLng, $minLat, $maxLng, $maxLat] = array_map('floatval', $parts);
                if ($minLat < $maxLat && $minLng < $maxLng) {
                    $q->whereBetween('lat', [$minLat, $maxLat])
                      ->whereBetween('lng', [$minLng, $maxLng]);
                }
            }
        }

        $rows = $q->offset($offset)->limit($limit)->get();

        return response()->json([
            'page'  => $page,
            'limit' => $limit,
            'count' => $rows->count(),
            'data'  => $rows,
        ]);
    }
}
