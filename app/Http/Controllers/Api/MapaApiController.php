<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class MapaApiController extends Controller
{
    public function index()
    {
        $rows = DB::table('afiliados')
            ->selectRaw("LPAD(cve_mun,3,'0') as cve_mun, municipio, COUNT(*) as total")
            ->groupBy('cve_mun','municipio')
            ->get();

        $conteoPorCVE = [];
        foreach ($rows as $r) {
            $conteoPorCVE['16'.$r->cve_mun] = (int)$r->total;
        }
        return response()->json(['conteo' => $conteoPorCVE]);
    }

    public function data()
    {
        $rows = DB::table('afiliados')
            ->select('id','nombre','apellido_paterno','apellido_materno','municipio','lat','lng')
            ->whereNotNull('lat')->whereNotNull('lng')
            ->limit(2000)
            ->get();
        return response()->json($rows);
    }
}
