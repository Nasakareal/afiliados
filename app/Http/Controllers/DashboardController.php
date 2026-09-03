<?php

namespace App\Http\Controllers;

use App\Models\Actividad;
use App\Models\Comunicado;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Support\LocalDistrictAccess;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->hasRole('Lonas')) {
            return redirect()->route('lonas.index');
        }

        $afiliados = DB::table('afiliados_resumen');
        $this->aplicarAcceso($afiliados, $user);

        $summary = $afiliados
            ->selectRaw('COALESCE(SUM(total), 0) AS total')
            ->selectRaw("COALESCE(SUM(CASE WHEN estatus = 'validado' THEN total ELSE 0 END), 0) AS validado")
            ->selectRaw("COALESCE(SUM(CASE WHEN estatus = 'descartado' THEN total ELSE 0 END), 0) AS descartado")
            ->first();

        $total = (int) ($summary->total ?? 0);
        $validado = (int) ($summary->validado ?? 0);
        $descartado = (int) ($summary->descartado ?? 0);

        $stats = compact(
            'total',
            'validado',
            'descartado'
        );

        $porMunicipio = DB::table('afiliados_resumen')
            ->select(
                'municipio',
                DB::raw('SUM(total) as total')
            );

        $this->aplicarAcceso($porMunicipio, $user);

        $porMunicipio = $porMunicipio
            ->groupBy('municipio')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        $porSeccion = DB::table('afiliados_resumen')
            ->select(
                'seccion',
                DB::raw('SUM(total) as total')
            )
            ->where('seccion', '<>', '');

        $this->aplicarAcceso($porSeccion, $user);

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
            'porMunicipio',
            'porSeccion',
            'actividades',
            'comunicadosRecientes'
        ));
    }

    private function aplicarAcceso(Builder $query, $user): void
    {
        if ($user->hasAnyRole(['Admin', 'SuperAdmin'])) {
            if (LocalDistrictAccess::restricted($user)) {
                LocalDistrictAccess::scope($query, 'distrito_local', $user);
            }

            return;
        }

        $query->where('capturista_id', $user->id);
    }
}
