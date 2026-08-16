<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class ReporteApiController extends Controller
{
    public function afiliados()
    {
        $rows = DB::table('afiliados')
            ->whereNull('deleted_at')
            ->selectRaw("COALESCE(estatus, 'sin estatus') as estatus, COUNT(*) as total")
            ->groupBy('estatus')->orderByDesc('total')->get()
            ->map(fn ($row) => ['label' => ucfirst($row->estatus), 'estatus' => $row->estatus, 'total' => $row->total]);

        return response()->json($rows);
    }

    public function secciones()
    {
        $rows = DB::table('afiliados')
            ->select('seccion', DB::raw('COUNT(*) as total'))
            ->whereNotNull('seccion')
            ->groupBy('seccion')->orderByDesc('total')->limit(200)->get();

        return response()->json($rows);
    }

    public function capturistas()
    {
        $rows = DB::table('afiliados')
            ->join('users','users.id','=','afiliados.capturista_id')
            ->select('users.id','users.name', DB::raw('COUNT(*) as total'))
            ->groupBy('users.id','users.name')
            ->orderByDesc('total')->limit(200)->get();

        return response()->json($rows);
    }
}
