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

        // === NUEVO: buscar todas las capas .geojson en public/maps/out
        $layers = [];
        $dir = public_path('maps/out');
        if (is_dir($dir)) {
            $files = glob($dir.'/*.geojson');
            sort($files);
            foreach ($files as $path) {
                $file  = basename($path);
                $base  = pathinfo($file, PATHINFO_FILENAME); // p.ej. AUTOPISTA
                // Etiqueta amigable: AUTOPISTA -> Autopista, CABECERA_MUNICIPAL -> Cabecera Municipal
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
            'layers'          => $layers,   // <<< pásalo a la vista
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
