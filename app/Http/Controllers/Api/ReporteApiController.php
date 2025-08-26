<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class ReporteApiController extends Controller
{
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
