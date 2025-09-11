<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ComunicadoApiController extends Controller
{
    public function index(Request $request)
    {
        $now = $request->query('now');
        $now = $now ? Carbon::parse($now) : Carbon::now();

        $municipio = trim((string) $request->query('municipio', ''));

        $page  = max(1, (int) $request->query('page', 1));
        $limit = min(100, max(10, (int) $request->query('limit', 20)));
        $offset = ($page - 1) * $limit;

        $base = DB::table('comunicados')
            ->select('id','titulo','contenido','visible_desde','visible_hasta','estado','filtros','created_at','updated_at')
            ->whereNull('deleted_at')
            ->where('estado', 'publicado')
            ->where(function ($q) use ($now) {
                $q->whereNull('visible_desde')
                  ->orWhere('visible_desde', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('visible_hasta')
                  ->orWhere('visible_hasta', '>=', $now);
            });

        if ($municipio !== '') {
            $base->where(function($q) use ($municipio) {
                $q->whereNull('filtros')
                  ->orWhereRaw("JSON_EXTRACT(filtros, '$.municipios') IS NULL")
                  ->orWhereRaw("JSON_CONTAINS(JSON_EXTRACT(filtros, '$.municipios'), JSON_QUOTE(?))", [$municipio]);
            });
        }

        $total = (clone $base)->count();

        $rows = $base
            ->orderByDesc('visible_desde')
            ->orderByDesc('created_at')
            ->offset($offset)
            ->limit($limit)
            ->get();

        $data = [];
        foreach ($rows as $r) {
            if ($municipio !== '' && !self::passesMunicipioFilter($r->filtros, $municipio)) {
                continue;
            }
            $data[] = [
                'id'            => (int) $r->id,
                'titulo'        => (string) $r->titulo,
                'contenido'     => (string) $r->contenido,
                'visible_desde' => $r->visible_desde,
                'visible_hasta' => $r->visible_hasta,
                'created_at'    => $r->created_at,
                'updated_at'    => $r->updated_at,
            ];
        }

        return response()->json([
            'page'   => $page,
            'limit'  => $limit,
            'total'  => $total,
            'count'  => count($data),
            'items'  => $data,
        ]);
    }

    public function show(Request $request, $id)
    {
        $now = Carbon::now();
        $municipio = trim((string) $request->query('municipio', ''));

        $r = DB::table('comunicados')
            ->select('id','titulo','contenido','visible_desde','visible_hasta','estado','filtros','created_at','updated_at')
            ->where('id', $id)
            ->whereNull('deleted_at')
            ->where('estado', 'publicado')
            ->where(function ($q) use ($now) {
                $q->whereNull('visible_desde')
                  ->orWhere('visible_desde', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('visible_hasta')
                  ->orWhere('visible_hasta', '>=', $now);
            })
            ->first();

        if (!$r) {
            return response()->json(['message' => 'No encontrado'], 404);
        }

        if ($municipio !== '' && !self::passesMunicipioFilter($r->filtros, $municipio)) {
            return response()->json(['message' => 'No autorizado para este municipio'], 403);
        }

        return response()->json([
            'id'            => (int) $r->id,
            'titulo'        => (string) $r->titulo,
            'contenido'     => (string) $r->contenido,
            'visible_desde' => $r->visible_desde,
            'visible_hasta' => $r->visible_hasta,
            'created_at'    => $r->created_at,
            'updated_at'    => $r->updated_at,
        ]);
    }

    private static function passesMunicipioFilter($filtros, string $municipio): bool
    {
        if (!$filtros) return true;
        try {
            $j = json_decode($filtros, true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable $e) {
            return true;
        }
        if (!is_array($j)) return true;
        if (!isset($j['municipios']) || !is_array($j['municipios']) || empty($j['municipios'])) return true;

        $norm = fn($s) => mb_strtoupper(trim(iconv('UTF-8','ASCII//TRANSLIT', (string)$s)));
        $needle = $norm($municipio);
        foreach ($j['municipios'] as $m) {
            if ($norm($m) === $needle) return true;
        }
        return false;
    }
}
