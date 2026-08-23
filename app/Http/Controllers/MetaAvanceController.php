<?php

namespace App\Http\Controllers;

use App\Models\MetaAvance;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class MetaAvanceController extends Controller
{
    public function index(Request $request)
    {
        $fechaInicio = trim((string)$request->query('fecha_inicio', now()->startOfMonth()->toDateString()));
        $fechaFin = trim((string)$request->query('fecha_fin', now()->endOfMonth()->toDateString()));
        $cveMun = trim((string)$request->query('cve_mun'));
        $distritoLocal = trim((string)$request->query('distrito_local'));
        $distritoFederal = trim((string)$request->query('distrito_federal'));
        $referente = trim((string)$request->query('referente'));
        $capturistaId = $request->filled('capturista_id') ? (int)$request->query('capturista_id') : null;

        $municipios = DB::table('secciones')
            ->select(
                'cve_mun',
                'municipio',
                DB::raw("GROUP_CONCAT(DISTINCT distrito_local ORDER BY distrito_local SEPARATOR ', ') AS distritos_locales"),
                DB::raw("GROUP_CONCAT(DISTINCT distrito_federal ORDER BY distrito_federal SEPARATOR ', ') AS distritos_federales"),
                DB::raw('COUNT(*) AS total_secciones')
            )
            ->when($cveMun !== '', fn($q) => $q->where('cve_mun', $cveMun))
            ->when($distritoLocal !== '', fn($q) => $q->where('distrito_local', $distritoLocal))
            ->when($distritoFederal !== '', fn($q) => $q->where('distrito_federal', $distritoFederal))
            ->groupBy('cve_mun', 'municipio')
            ->orderBy('municipio')
            ->get();

        $convencidos = DB::table('afiliados')
            ->select('cve_mun', DB::raw('COUNT(*) AS total'))
            ->whereNull('deleted_at')
            ->whereNotNull('cve_mun')
            ->whereRaw(
                'DATE(COALESCE(fecha_convencimiento, created_at)) BETWEEN ? AND ?',
                [$fechaInicio, $fechaFin]
            )
            ->when($cveMun !== '', fn($q) => $q->where('cve_mun', $cveMun))
            ->when($distritoLocal !== '', fn($q) => $q->where('distrito_local', $distritoLocal))
            ->when($distritoFederal !== '', fn($q) => $q->where('distrito_federal', $distritoFederal))
            ->when($referente !== '', fn($q) => $q->whereRaw('TRIM(perfil) = ?', [$referente]))
            ->when($capturistaId, fn($q) => $q->where('capturista_id', $capturistaId))
            ->groupBy('cve_mun')
            ->pluck('total', 'cve_mun');

        $lonas = DB::table('lonas')
            ->join('secciones', 'secciones.seccion', '=', 'lonas.seccion')
            ->select('secciones.cve_mun', DB::raw('COUNT(DISTINCT lonas.id) AS total'))
            ->whereNull('lonas.deleted_at')
            ->whereDate('lonas.created_at', '>=', $fechaInicio)
            ->whereDate('lonas.created_at', '<=', $fechaFin)
            ->when($cveMun !== '', fn($q) => $q->where('secciones.cve_mun', $cveMun))
            ->when($distritoLocal !== '', fn($q) => $q->where('secciones.distrito_local', $distritoLocal))
            ->when($distritoFederal !== '', fn($q) => $q->where('secciones.distrito_federal', $distritoFederal))
            ->when($referente !== '', fn($q) => $q->whereRaw('TRIM(lonas.responsable) = ?', [$referente]))
            ->when($capturistaId, fn($q) => $q->where('lonas.capturado_por', $capturistaId))
            ->groupBy('secciones.cve_mun')
            ->pluck('total', 'cve_mun');

        $metas = MetaAvance::query()
            ->where('activa', true)
            ->whereDate('fecha_inicio', '<=', $fechaFin)
            ->whereDate('fecha_fin', '>=', $fechaInicio)
            ->when($cveMun !== '', fn($q) => $q->where('cve_mun', $cveMun))
            ->orderBy('id')
            ->get()
            ->groupBy('cve_mun');

        $avance = $municipios->map(function ($municipio) use ($convencidos, $lonas, $metas) {
            $metasMunicipio = $metas->get($municipio->cve_mun, collect());

            $metaConvencidos = $metasMunicipio
                ->where('tipo', MetaAvance::TIPO_CONVENCIDOS)
                ->sortByDesc('id')
                ->first();

            $metaLonas = $metasMunicipio
                ->where('tipo', MetaAvance::TIPO_LONAS)
                ->sortByDesc('id')
                ->first();

            $totalConvencidos = (int)($convencidos[$municipio->cve_mun] ?? 0);
            $totalLonas = (int)($lonas[$municipio->cve_mun] ?? 0);

            $cantidadMetaConvencidos = (int)($metaConvencidos->meta ?? 0);
            $cantidadMetaLonas = (int)($metaLonas->meta ?? 0);

            return [
                'cve_mun' => $municipio->cve_mun,
                'municipio' => $municipio->municipio,
                'distritos_locales' => $municipio->distritos_locales,
                'distritos_federales' => $municipio->distritos_federales,
                'secciones' => (int)$municipio->total_secciones,
                'meta_convencidos_id' => $metaConvencidos->id ?? null,
                'meta_convencidos' => $cantidadMetaConvencidos,
                'convencidos' => $totalConvencidos,
                'porcentaje_convencidos' => $cantidadMetaConvencidos > 0
                    ? round(($totalConvencidos / $cantidadMetaConvencidos) * 100, 2)
                    : 0,
                'meta_lonas_id' => $metaLonas->id ?? null,
                'meta_lonas' => $cantidadMetaLonas,
                'lonas' => $totalLonas,
                'porcentaje_lonas' => $cantidadMetaLonas > 0
                    ? round(($totalLonas / $cantidadMetaLonas) * 100, 2)
                    : 0,
            ];
        });

        $totalMetaConvencidos = (int)$avance->sum('meta_convencidos');
        $totalConvencidos = (int)$avance->sum('convencidos');
        $totalMetaLonas = (int)$avance->sum('meta_lonas');
        $totalLonas = (int)$avance->sum('lonas');

        $totales = [
            'meta_convencidos' => $totalMetaConvencidos,
            'convencidos' => $totalConvencidos,
            'porcentaje_convencidos' => $totalMetaConvencidos > 0
                ? round(($totalConvencidos / $totalMetaConvencidos) * 100, 2)
                : 0,
            'meta_lonas' => $totalMetaLonas,
            'lonas' => $totalLonas,
            'porcentaje_lonas' => $totalMetaLonas > 0
                ? round(($totalLonas / $totalMetaLonas) * 100, 2)
                : 0,
        ];

        $capturistas = User::query()
            ->where(function ($query) {
                $query->whereExists(function ($sub) {
                    $sub->selectRaw('1')
                        ->from('afiliados')
                        ->whereColumn('afiliados.capturista_id', 'users.id')
                        ->whereNull('afiliados.deleted_at');
                })->orWhereExists(function ($sub) {
                    $sub->selectRaw('1')
                        ->from('lonas')
                        ->whereColumn('lonas.capturado_por', 'users.id')
                        ->whereNull('lonas.deleted_at');
                });
            })
            ->orderBy('name')
            ->get(['id', 'name']);

        $referentesAfiliados = DB::table('afiliados')
            ->selectRaw('TRIM(perfil) AS referente')
            ->whereNull('deleted_at')
            ->whereNotNull('perfil')
            ->whereRaw("TRIM(perfil) <> ''");

        $referentesLonas = DB::table('lonas')
            ->selectRaw('TRIM(responsable) AS referente')
            ->whereNull('deleted_at')
            ->whereNotNull('responsable')
            ->whereRaw("TRIM(responsable) <> ''");

        $referentes = DB::query()
            ->fromSub(
                $referentesAfiliados->union($referentesLonas),
                'referentes'
            )
            ->select('referente')
            ->distinct()
            ->orderBy('referente')
            ->pluck('referente');

        $distritosLocales = DB::table('secciones')
            ->whereNotNull('distrito_local')
            ->distinct()
            ->orderBy('distrito_local')
            ->pluck('distrito_local');

        $distritosFederales = DB::table('secciones')
            ->whereNotNull('distrito_federal')
            ->distinct()
            ->orderBy('distrito_federal')
            ->pluck('distrito_federal');

        return view('avance.index', compact(
            'avance',
            'totales',
            'fechaInicio',
            'fechaFin',
            'cveMun',
            'distritoLocal',
            'distritoFederal',
            'referente',
            'capturistaId',
            'capturistas',
            'referentes',
            'distritosLocales',
            'distritosFederales'
        ));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'tipo' => [
                'required',
                Rule::in([
                    MetaAvance::TIPO_CONVENCIDOS,
                    MetaAvance::TIPO_LONAS,
                ]),
            ],
            'cve_mun' => [
                'required',
                'string',
                'size:3',
                Rule::exists('secciones', 'cve_mun'),
            ],
            'meta' => ['required', 'integer', 'min:0'],
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['required', 'date', 'after_or_equal:fecha_inicio'],
        ]);

        MetaAvance::updateOrCreate(
            [
                'tipo' => $data['tipo'],
                'cve_mun' => $data['cve_mun'],
                'fecha_inicio' => $data['fecha_inicio'],
                'fecha_fin' => $data['fecha_fin'],
            ],
            [
                'meta' => $data['meta'],
                'activa' => true,
                'asignado_por' => $request->user()->id,
            ]
        );

        return redirect()
            ->route('avance.index', [
                'fecha_inicio' => $data['fecha_inicio'],
                'fecha_fin' => $data['fecha_fin'],
            ])
            ->with('status', 'Meta guardada correctamente.');
    }

    public function update(Request $request, MetaAvance $metaAvance)
    {
        $data = $request->validate([
            'tipo' => [
                'required',
                Rule::in([
                    MetaAvance::TIPO_CONVENCIDOS,
                    MetaAvance::TIPO_LONAS,
                ]),
            ],
            'cve_mun' => [
                'required',
                'string',
                'size:3',
                Rule::exists('secciones', 'cve_mun'),
            ],
            'meta' => ['required', 'integer', 'min:0'],
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['required', 'date', 'after_or_equal:fecha_inicio'],
        ]);

        $duplicada = MetaAvance::query()
            ->where('id', '!=', $metaAvance->id)
            ->where('tipo', $data['tipo'])
            ->where('cve_mun', $data['cve_mun'])
            ->whereDate('fecha_inicio', $data['fecha_inicio'])
            ->whereDate('fecha_fin', $data['fecha_fin'])
            ->exists();

        if ($duplicada) {
            return back()
                ->withInput()
                ->withErrors([
                    'meta' => 'Ya existe una meta para ese municipio, tipo y periodo.',
                ]);
        }

        $metaAvance->update([
            'tipo' => $data['tipo'],
            'cve_mun' => $data['cve_mun'],
            'meta' => $data['meta'],
            'fecha_inicio' => $data['fecha_inicio'],
            'fecha_fin' => $data['fecha_fin'],
            'activa' => $request->has('activa')
                ? $request->boolean('activa')
                : $metaAvance->activa,
            'asignado_por' => $request->user()->id,
        ]);

        return back()->with('status', 'Meta actualizada correctamente.');
    }

    public function destroy(MetaAvance $metaAvance)
    {
        $metaAvance->delete();

        return back()->with('status', 'Meta eliminada correctamente.');
    }
}
