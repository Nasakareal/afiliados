<?php

namespace App\Http\Controllers;

use App\Models\Afiliado;
use App\Models\Comunicado;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // ---- KPIs principales ----
        $total      = Afiliado::count();
        $validado   = Afiliado::where('estatus','validado')->count();
        $pendiente  = Afiliado::where('estatus','pendiente')->count();
        $descartado = Afiliado::where('estatus','descartado')->count();
        $hoy        = Afiliado::whereDate('created_at', Carbon::today())->count();
        $stats = compact('total','validado','pendiente','descartado','hoy');

        // ---- Serie últimos 7 días (incluye ceros) ----
        $desde = Carbon::today()->subDays(6);
        $raw = DB::table('afiliados')
            ->select(DB::raw('DATE(created_at) as d'), DB::raw('COUNT(*) as c'))
            ->where('created_at', '>=', $desde->copy()->startOfDay())
            ->groupBy('d')->orderBy('d')->get();

        $map = [];
        foreach ($raw as $r) $map[$r->d] = (int)$r->c;

        $labels7 = [];
        $series7 = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = Carbon::today()->subDays($i);
            $key = $day->toDateString();
            $labels7[] = $day->format('d/m');
            $series7[] = $map[$key] ?? 0;
        }

        // ---- Top municipios y secciones ----
        $porMunicipio = DB::table('afiliados')
            ->select('municipio', DB::raw('COUNT(*) as total'))
            ->groupBy('municipio')->orderByDesc('total')->limit(10)->get();

        $porSeccion = DB::table('afiliados')
            ->select('seccion', DB::raw('COUNT(*) as total'))
            ->whereNotNull('seccion')
            ->groupBy('seccion')->orderByDesc('total')->limit(10)->get();

        // ---- Próximas actividades (7 días) ----
        $actividades = DB::table('actividades')
            ->where('inicio', '>=', Carbon::now())
            ->where('inicio', '<=', Carbon::now()->addDays(7))
            ->orderBy('inicio')->limit(8)->get();

        // ---- Comunicados recientes (marcando si YO ya los leí) ----
        $userId = Auth::id();
        $comunicadosRecientes = Comunicado::orderByDesc('created_at')
            ->withCount([
                'lectores as leido_por_mi' => function ($q) use ($userId) {
                    $q->where('user_id', $userId)->whereNotNull('leido_at');
                }
            ])
            ->limit(8)
            ->get();

        return view('dashboard', compact(
            'stats','labels7','series7',
            'porMunicipio','porSeccion',
            'actividades','comunicadosRecientes'
        ));
    }
}
