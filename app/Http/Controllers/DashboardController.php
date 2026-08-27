<?php

namespace App\Http\Controllers;

use App\Models\Actividad;
use App\Models\Comunicado;
use App\Support\LocalDistrictAccess;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        if ($request->user()->hasRole('Lonas')) {
            return redirect()->route('lonas.index');
        }

        $afiliados = DB::table('afiliados');
        LocalDistrictAccess::scope($afiliados);

        $total = (clone $afiliados)->count();

        $validado = (clone $afiliados)
            ->where('estatus', 'validado')
            ->count();

        $pendiente = (clone $afiliados)
            ->where('estatus', 'pendiente')
            ->count();

        $descartado = (clone $afiliados)
            ->where('estatus', 'descartado')
            ->count();

        $hoy = (clone $afiliados)
            ->whereDate('created_at', Carbon::today())
            ->count();

        $stats = compact(
            'total',
            'validado',
            'pendiente',
            'descartado',
            'hoy'
        );

        $desde = Carbon::today()->subDays(6);

        $raw = DB::table('afiliados')
            ->select(
                DB::raw('DATE(created_at) as d'),
                DB::raw('COUNT(*) as c')
            )
            ->where('created_at', '>=', $desde->copy()->startOfDay());

        LocalDistrictAccess::scope($raw);

        $raw = $raw
            ->groupBy('d')
            ->orderBy('d')
            ->get();

        $map = [];

        foreach ($raw as $registro) {
            $map[$registro->d] = (int) $registro->c;
        }

        $labels7 = [];
        $series7 = [];

        for ($i = 6; $i >= 0; $i--) {
            $dia = Carbon::today()->subDays($i);
            $fecha = $dia->toDateString();

            $labels7[] = $dia->format('d/m');
            $series7[] = $map[$fecha] ?? 0;
        }

        $porMunicipio = DB::table('afiliados')
            ->select(
                'municipio',
                DB::raw('COUNT(*) as total')
            );

        LocalDistrictAccess::scope($porMunicipio);

        $porMunicipio = $porMunicipio
            ->groupBy('municipio')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        $porSeccion = DB::table('afiliados')
            ->select(
                'seccion',
                DB::raw('COUNT(*) as total')
            )
            ->whereNotNull('seccion');

        LocalDistrictAccess::scope($porSeccion);

        $porSeccion = $porSeccion
            ->groupBy('seccion')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        $ahora = Carbon::now();

        $actividades = Actividad::query()
            ->where('inicio', '>=', $ahora)
            ->where('inicio', '<=', $ahora->copy()->addDays(7))
            ->orderBy('inicio')
            ->limit(8)
            ->get();

        $userId = Auth::id();

        $comunicadosRecientes = Comunicado::query()
            ->orderByDesc('created_at')
            ->withCount([
                'lectores as leido_por_mi' => function ($query) use ($userId) {
                    $query
                        ->where('user_id', $userId)
                        ->whereNotNull('leido_at');
                },
            ])
            ->limit(8)
            ->get();

        return view('dashboard', compact(
            'stats',
            'labels7',
            'series7',
            'porMunicipio',
            'porSeccion',
            'actividades',
            'comunicadosRecientes'
        ));
    }
}
