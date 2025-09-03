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
        $estatus = $request->query('estatus', 'validado');
        $allowed = ['validado','pendiente','descartado','todos'];
        if (!in_array($estatus, $allowed, true)) $estatus = 'validado';

        $rows = DB::table('afiliados')
            ->selectRaw("LPAD(cve_mun,3,'0') as cve_mun, municipio, COUNT(*) as total")
            ->whereNull('deleted_at')
            ->when($estatus !== 'todos', fn($q)=>$q->where('estatus', $estatus))
            ->groupBy('cve_mun','municipio')
            ->get();

        $conteo = [];
        $conteoPorNombre = [];

        foreach ($rows as $r) {
            $cvegeo = '16' . $r->cve_mun;
            $conteo[$cvegeo] = (int)$r->total;

            $norm = $this->normalize($r->municipio);
            $conteoPorNombre[$norm] = (int)$r->total;
        }

        return view('mapa.index', [
            'conteo'          => $conteo,
            'conteoPorNombre' => $conteoPorNombre,
            'estatus'         => $estatus,
        ]);
    }

    public function data(Request $request)
    {
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
