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

        $afiliados = DB::table('afiliados')
            ->whereNull('deleted_at');
        $this->aplicarAcceso($afiliados, $user);

        $summary = $afiliados
            ->selectRaw('COUNT(*) AS total')
            ->selectRaw("SUM(CASE WHEN estatus = 'validado' THEN 1 ELSE 0 END) AS validado")
            ->selectRaw("SUM(CASE WHEN estatus = 'descartado' THEN 1 ELSE 0 END) AS descartado")
            ->first();

        $total = (int) ($summary->total ?? 0);
        $validado = (int) ($summary->validado ?? 0);
        $descartado = (int) ($summary->descartado ?? 0);

        $stats = compact(
            'total',
            'validado',
            'descartado'
        );

        $porMunicipio = DB::table('afiliados')
            ->select(
                'municipio',
                DB::raw('COUNT(*) as total')
            )
            ->whereNull('deleted_at');

        $this->aplicarAcceso($porMunicipio, $user);

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
            ->whereNotNull('seccion')
            ->whereNull('deleted_at');

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
