<?php

namespace App\Http\Controllers;

use App\Models\MetaAvance;
use App\Models\User;
use App\Support\LocalDistrictAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class MetaAvanceController extends Controller
{
    private const META_FECHA_INICIO = '2000-01-01';
    private const META_FECHA_FIN = '2099-12-31';

    public function index(Request $request)
    {
        $usuario = $request->user();

        $puedeVerTodo = $usuario->hasAnyRole([
            'Admin',
            'SuperAdmin',
        ]);

        $cveMun = trim((string) $request->query('cve_mun'));

        $distritosLocalesAsignados = LocalDistrictAccess::districts($usuario);
        $distritoLocalRestringido = $distritosLocalesAsignados !== [];
        $distritoSolicitado = trim((string) $request->query('distrito_local'));
        $distritoLocal = $distritoLocalRestringido
            ? (
                in_array((int) $distritoSolicitado, $distritosLocalesAsignados, true)
                    ? (string) (int) $distritoSolicitado
                    : (string) $distritosLocalesAsignados[0]
            )
            : $distritoSolicitado;
        $distritoLocalAsignado = $distritosLocalesAsignados[0] ?? null;

        $distritoFederal = trim(
            (string) $request->query('distrito_federal')
        );

        $referente = trim(
            (string) $request->query('referente')
        );

        $capturistaId = $puedeVerTodo
            ? (
                $request->filled('capturista_id')
                    ? (int) $request->query('capturista_id')
                    : null
            )
            : (int) $usuario->id;

        $municipios = DB::table('secciones')
            ->select(
                'cve_mun',
                'municipio',
                'distrito_local',
                DB::raw(
                    'GROUP_CONCAT(DISTINCT distrito_federal) AS distritos_federales'
                ),
                DB::raw('COUNT(*) AS total_secciones')
            )
            ->when(
                $cveMun !== '',
                fn($query) => $query->where('cve_mun', $cveMun)
            )
            ->when(
                $distritoLocal !== '',
                fn($query) => $query->where(
                    'distrito_local',
                    $distritoLocal
                )
            )
            ->when(
                $distritoFederal !== '',
                fn($query) => $query->where(
                    'distrito_federal',
                    $distritoFederal
                )
            )
            ->groupBy(
                'cve_mun',
                'municipio',
                'distrito_local'
            )
            ->orderBy('distrito_local')
            ->orderBy('municipio')
            ->get();

        $convencidos = DB::table('afiliados as a')
        ->join('secciones as s', function ($join) {
            $join->on(
                DB::raw('CAST(s.seccion AS UNSIGNED)'),
                '=',
                DB::raw('CAST(a.seccion AS UNSIGNED)')
            );
        })
        ->select(
            's.cve_mun',
            's.distrito_local',
            DB::raw('COUNT(DISTINCT a.id) AS total')
        )
        ->whereNull('a.deleted_at')
        ->when(
            $cveMun !== '',
            fn($query) => $query->where(
                's.cve_mun',
                $cveMun
            )
        )
        ->when(
            $distritoLocal !== '',
            fn($query) => $query->where(
                's.distrito_local',
                $distritoLocal
            )
        )
        ->when(
            $distritoFederal !== '',
            fn($query) => $query->where(
                's.distrito_federal',
                $distritoFederal
            )
        )
        ->when(
            $referente !== '',
            fn($query) => $query->whereRaw(
                'TRIM(a.perfil) = ?',
                [$referente]
            )
        )
        ->when(
            $capturistaId,
            fn($query) => $query->where(
                'a.capturista_id',
                $capturistaId
            )
        )
        ->groupBy(
            's.cve_mun',
            's.distrito_local'
        )
        ->get()
        ->mapWithKeys(fn($fila) => [
            self::scopeKey(
                $fila->cve_mun,
                $fila->distrito_local
            ) => (int) $fila->total,
        ]);

        $convencidosPorSeccion = DB::table('afiliados')
            ->select(
                'seccion',
                DB::raw('COUNT(*) AS total')
            )
            ->whereNull('deleted_at')
            ->whereNotNull('seccion')
            ->when(
                $cveMun !== '',
                fn($query) => $query->where(
                    'cve_mun',
                    $cveMun
                )
            )
            ->when(
                $distritoLocal !== '',
                fn($query) => $query->where(
                    'distrito_local',
                    $distritoLocal
                )
            )
            ->when(
                $distritoFederal !== '',
                fn($query) => $query->where(
                    'distrito_federal',
                    $distritoFederal
                )
            )
            ->when(
                $referente !== '',
                fn($query) => $query->whereRaw(
                    'TRIM(perfil) = ?',
                    [$referente]
                )
            )
            ->when(
                $capturistaId,
                fn($query) => $query->where(
                    'capturista_id',
                    $capturistaId
                )
            )
            ->groupBy('seccion')
            ->pluck('total', 'seccion')
            ->mapWithKeys(fn($total, $seccion) => [
                (string) (int) $seccion => (int) $total,
            ]);

        $lonas = DB::table('lonas')
            ->join(
                'secciones',
                'secciones.seccion',
                '=',
                'lonas.seccion'
            )
            ->select(
                'secciones.cve_mun',
                'secciones.distrito_local',
                DB::raw('COUNT(DISTINCT lonas.id) AS total')
            )
            ->whereNull('lonas.deleted_at')
            ->when(
                $cveMun !== '',
                fn($query) => $query->where(
                    'secciones.cve_mun',
                    $cveMun
                )
            )
            ->when(
                $distritoLocal !== '',
                fn($query) => $query->where(
                    'secciones.distrito_local',
                    $distritoLocal
                )
            )
            ->when(
                $distritoFederal !== '',
                fn($query) => $query->where(
                    'secciones.distrito_federal',
                    $distritoFederal
                )
            )
            ->when(
                $referente !== '',
                fn($query) => $query->whereRaw(
                    'TRIM(lonas.responsable) = ?',
                    [$referente]
                )
            )
            ->when(
                $capturistaId,
                fn($query) => $query->where(
                    'lonas.capturado_por',
                    $capturistaId
                )
            )
            ->groupBy(
                'secciones.cve_mun',
                'secciones.distrito_local'
            )
            ->get()
            ->mapWithKeys(fn($fila) => [
                self::scopeKey(
                    $fila->cve_mun,
                    $fila->distrito_local
                ) => (int) $fila->total,
            ]);

        $metas = MetaAvance::query()
            ->where('activa', true)
            ->when(
                $cveMun !== '',
                fn($query) => $query->where(
                    'cve_mun',
                    $cveMun
                )
            )
            ->when(
                $distritoLocal !== '',
                fn($query) => $query->where(
                    'distrito_local',
                    $distritoLocal
                )
            )
            ->orderBy('id')
            ->get()
            ->groupBy(
                fn(MetaAvance $meta) => self::scopeKey(
                    $meta->cve_mun,
                    $meta->distrito_local
                )
            );

        $avance = $municipios->map(
            function ($municipio) use (
                $convencidos,
                $lonas,
                $metas
            ) {
                $scopeKey = self::scopeKey(
                    $municipio->cve_mun,
                    $municipio->distrito_local
                );

                $metasMunicipio = $metas->get(
                    $scopeKey,
                    collect()
                );

                $metaConvencidos = $metasMunicipio
                    ->where(
                        'tipo',
                        MetaAvance::TIPO_CONVENCIDOS
                    )
                    ->sortByDesc('id')
                    ->first();

                $metaLonas = $metasMunicipio
                    ->where(
                        'tipo',
                        MetaAvance::TIPO_LONAS
                    )
                    ->sortByDesc('id')
                    ->first();

                $totalConvencidos = (int) (
                    $convencidos[$scopeKey] ?? 0
                );

                $totalLonas = (int) (
                    $lonas[$scopeKey] ?? 0
                );

                $cantidadMetaConvencidos = (int) (
                    $metaConvencidos->meta ?? 0
                );

                $cantidadMetaLonas = (int) (
                    $metaLonas->meta ?? 0
                );

                return [
                    'cve_mun' => $municipio->cve_mun,
                    'municipio' => $municipio->municipio,
                    'distrito_local' => (int) $municipio->distrito_local,
                    'distritos_locales' => (string) $municipio->distrito_local,
                    'distritos_federales' => $municipio->distritos_federales,
                    'secciones' => (int) $municipio->total_secciones,
                    'meta_convencidos_id' => $metaConvencidos->id ?? null,
                    'meta_convencidos' => $cantidadMetaConvencidos,
                    'total_convencidos' => $totalConvencidos,
                    'porcentaje_convencidos' => $cantidadMetaConvencidos > 0
                        ? round(
                            ($totalConvencidos / $cantidadMetaConvencidos) * 100,
                            2
                        )
                        : 0,
                    'meta_lonas_id' => $metaLonas->id ?? null,
                    'meta_lonas' => $cantidadMetaLonas,
                    'total_lonas' => $totalLonas,
                    'porcentaje_lonas' => $cantidadMetaLonas > 0
                        ? round(
                            ($totalLonas / $cantidadMetaLonas) * 100,
                            2
                        )
                        : 0,
                ];
            }
        );

        $totalMetaConvencidos = (int) $avance->sum(
            'meta_convencidos'
        );

        $totalConvencidosQuery = DB::table('afiliados as a')
            ->whereNull('a.deleted_at');

        $requiereGeografia =
            $cveMun !== '' ||
            $distritoLocal !== '' ||
            $distritoFederal !== '';

        if ($requiereGeografia) {
            $totalConvencidosQuery
                ->join('secciones as s_total', function ($join) {
                    $join->on(
                        DB::raw('CAST(s_total.seccion AS UNSIGNED)'),
                        '=',
                        DB::raw('CAST(a.seccion AS UNSIGNED)')
                    );
                });

            if ($cveMun !== '') {
                $totalConvencidosQuery->where(
                    's_total.cve_mun',
                    $cveMun
                );
            }

            if ($distritoLocal !== '') {
                $totalConvencidosQuery->where(
                    's_total.distrito_local',
                    $distritoLocal
                );
            }

            if ($distritoFederal !== '') {
                $totalConvencidosQuery->where(
                    's_total.distrito_federal',
                    $distritoFederal
                );
            }
        }

        if ($referente !== '') {
            $totalConvencidosQuery->whereRaw(
                'TRIM(a.perfil) = ?',
                [$referente]
            );
        }

        if ($capturistaId) {
            $totalConvencidosQuery->where(
                'a.capturista_id',
                $capturistaId
            );
        }

        $totalConvencidos = (int) $totalConvencidosQuery
            ->distinct()
            ->count('a.id');

        $totalMetaLonas = (int) $avance->sum(
            'meta_lonas'
        );

        $totalLonas = (int) $avance->sum(
            'total_lonas'
        );

        $totales = [
            'secciones' => (int) $avance->sum('secciones'),
            'meta_convencidos' => $totalMetaConvencidos,
            'total_convencidos' => $totalConvencidos,
            'porcentaje_convencidos' => $totalMetaConvencidos > 0
                ? round(
                    ($totalConvencidos / $totalMetaConvencidos) * 100,
                    2
                )
                : 0,
            'meta_lonas' => $totalMetaLonas,
            'total_lonas' => $totalLonas,
            'porcentaje_lonas' => $totalMetaLonas > 0
                ? round(
                    ($totalLonas / $totalMetaLonas) * 100,
                    2
                )
                : 0,
        ];

        $topCapturistas = DB::table('afiliados')
            ->join(
                'users',
                'users.id',
                '=',
                'afiliados.capturista_id'
            )
            ->select(
                'users.id',
                'users.name',
                DB::raw('COUNT(*) AS total')
            )
            ->whereNull('afiliados.deleted_at')
            ->when(
                $cveMun !== '',
                fn($query) => $query->where(
                    'afiliados.cve_mun',
                    $cveMun
                )
            )
            ->when(
                $distritoLocal !== '',
                fn($query) => $query->where(
                    'afiliados.distrito_local',
                    $distritoLocal
                )
            )
            ->when(
                $distritoFederal !== '',
                fn($query) => $query->where(
                    'afiliados.distrito_federal',
                    $distritoFederal
                )
            )
            ->when(
                $referente !== '',
                fn($query) => $query->whereRaw(
                    'TRIM(afiliados.perfil) = ?',
                    [$referente]
                )
            )
            ->when(
                $capturistaId,
                fn($query) => $query->where(
                    'afiliados.capturista_id',
                    $capturistaId
                )
            )
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('total')
            ->orderBy('users.name')
            ->limit(5)
            ->get();

        $topReferentes = DB::table('afiliados')
            ->selectRaw(
                'TRIM(perfil) AS name, COUNT(*) AS total'
            )
            ->whereNull('deleted_at')
            ->whereNotNull('perfil')
            ->whereRaw("TRIM(perfil) <> ''")
            ->when(
                $cveMun !== '',
                fn($query) => $query->where(
                    'cve_mun',
                    $cveMun
                )
            )
            ->when(
                $distritoLocal !== '',
                fn($query) => $query->where(
                    'distrito_local',
                    $distritoLocal
                )
            )
            ->when(
                $distritoFederal !== '',
                fn($query) => $query->where(
                    'distrito_federal',
                    $distritoFederal
                )
            )
            ->when(
                $referente !== '',
                fn($query) => $query->whereRaw(
                    'TRIM(perfil) = ?',
                    [$referente]
                )
            )
            ->when(
                $capturistaId,
                fn($query) => $query->where(
                    'capturista_id',
                    $capturistaId
                )
            )
            ->groupByRaw('TRIM(perfil)')
            ->orderByDesc('total')
            ->orderBy('name')
            ->get();

        $capturistas = User::query()
            ->when(
                !$puedeVerTodo,
                fn($query) => $query->whereKey($usuario->id)
            )
            ->where(function ($query) use (
                $cveMun,
                $distritoLocal,
                $distritoFederal
            ) {
                $query->whereExists(function ($subquery) use (
                    $cveMun,
                    $distritoLocal,
                    $distritoFederal
                ) {
                    $subquery
                        ->selectRaw('1')
                        ->from('afiliados')
                        ->whereColumn(
                            'afiliados.capturista_id',
                            'users.id'
                        )
                        ->whereNull('afiliados.deleted_at')
                        ->when(
                            $cveMun !== '',
                            fn($query) => $query->where(
                                'afiliados.cve_mun',
                                $cveMun
                            )
                        )
                        ->when(
                            $distritoLocal !== '',
                            fn($query) => $query->where(
                                'afiliados.distrito_local',
                                $distritoLocal
                            )
                        )
                        ->when(
                            $distritoFederal !== '',
                            fn($query) => $query->where(
                                'afiliados.distrito_federal',
                                $distritoFederal
                            )
                        );
                })->orWhereExists(function ($subquery) use (
                    $cveMun,
                    $distritoLocal,
                    $distritoFederal
                ) {
                    $subquery
                        ->selectRaw('1')
                        ->from('lonas')
                        ->join(
                            'secciones as secciones_capturista',
                            'secciones_capturista.seccion',
                            '=',
                            'lonas.seccion'
                        )
                        ->whereColumn(
                            'lonas.capturado_por',
                            'users.id'
                        )
                        ->whereNull('lonas.deleted_at')
                        ->when(
                            $cveMun !== '',
                            fn($query) => $query->where(
                                'secciones_capturista.cve_mun',
                                $cveMun
                            )
                        )
                        ->when(
                            $distritoLocal !== '',
                            fn($query) => $query->where(
                                'secciones_capturista.distrito_local',
                                $distritoLocal
                            )
                        )
                        ->when(
                            $distritoFederal !== '',
                            fn($query) => $query->where(
                                'secciones_capturista.distrito_federal',
                                $distritoFederal
                            )
                        );
                });
            })
            ->orderBy('name')
            ->get(['id', 'name']);

        $referentesAfiliados = DB::table('afiliados')
            ->selectRaw('TRIM(perfil) AS referente')
            ->whereNull('deleted_at')
            ->whereNotNull('perfil')
            ->whereRaw("TRIM(perfil) <> ''")
            ->when(
                $cveMun !== '',
                fn($query) => $query->where(
                    'cve_mun',
                    $cveMun
                )
            )
            ->when(
                $distritoLocal !== '',
                fn($query) => $query->where(
                    'distrito_local',
                    $distritoLocal
                )
            )
            ->when(
                $distritoFederal !== '',
                fn($query) => $query->where(
                    'distrito_federal',
                    $distritoFederal
                )
            )
            ->when(
                $capturistaId,
                fn($query) => $query->where(
                    'capturista_id',
                    $capturistaId
                )
            );

        $referentesLonas = DB::table('lonas')
            ->join(
                'secciones as secciones_referente',
                'secciones_referente.seccion',
                '=',
                'lonas.seccion'
            )
            ->selectRaw(
                'TRIM(lonas.responsable) AS referente'
            )
            ->whereNull('lonas.deleted_at')
            ->whereNotNull('lonas.responsable')
            ->whereRaw("TRIM(lonas.responsable) <> ''")
            ->when(
                $cveMun !== '',
                fn($query) => $query->where(
                    'secciones_referente.cve_mun',
                    $cveMun
                )
            )
            ->when(
                $distritoLocal !== '',
                fn($query) => $query->where(
                    'secciones_referente.distrito_local',
                    $distritoLocal
                )
            )
            ->when(
                $distritoFederal !== '',
                fn($query) => $query->where(
                    'secciones_referente.distrito_federal',
                    $distritoFederal
                )
            )
            ->when(
                $capturistaId,
                fn($query) => $query->where(
                    'lonas.capturado_por',
                    $capturistaId
                )
            );

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
            ->when(
                $distritoLocalRestringido,
                fn($query) => $query->whereIn(
                    'distrito_local',
                    $distritosLocalesAsignados
                )
            )
            ->distinct()
            ->orderBy('distrito_local')
            ->pluck('distrito_local');

        $distritosFederales = DB::table('secciones')
            ->whereNotNull('distrito_federal')
            ->when(
                $distritoLocalRestringido,
                fn($query) => $query->whereIn(
                    'distrito_local',
                    $distritosLocalesAsignados
                )
            )
            ->distinct()
            ->orderBy('distrito_federal')
            ->pluck('distrito_federal');

        $cabecerasDistritosFederales = [
            '1' => 'Lázaro Cárdenas',
            '2' => 'Puruándiro',
            '3' => 'Zitácuaro',
            '4' => 'Jiquilpan',
            '5' => 'Zamora',
            '6' => 'Ciudad Hidalgo',
            '7' => 'Zacapu',
            '8' => 'Morelia',
            '9' => 'Uruapan',
            '10' => 'Morelia',
            '11' => 'Pátzcuaro',
        ];

        $nombreDistritoFederal = $distritoFederal !== ''
            ? (
                $cabecerasDistritosFederales[
                    (string) $distritoFederal
                ] ?? ''
            )
            : 'Michoacán';

        $municipioPorSeccion = DB::table('secciones')
            ->select(
                'seccion',
                'cve_mun',
                'municipio'
            )
            ->when(
                $cveMun !== '',
                fn($query) => $query->where(
                    'cve_mun',
                    $cveMun
                )
            )
            ->when(
                $distritoLocal !== '',
                fn($query) => $query->where(
                    'distrito_local',
                    $distritoLocal
                )
            )
            ->when(
                $distritoFederal !== '',
                fn($query) => $query->where(
                    'distrito_federal',
                    $distritoFederal
                )
            )
            ->get()
            ->mapWithKeys(function ($seccion) {
                return [
                    (string) (int) $seccion->seccion => [
                        'cve_mun' => $seccion->cve_mun,
                        'municipio' => $seccion->municipio,
                    ],
                ];
            });

        return view('avance.index', compact(
            'avance',
            'totales',
            'cveMun',
            'distritoLocal',
            'distritoLocalRestringido',
            'distritosLocalesAsignados',
            'distritoFederal',
            'referente',
            'capturistaId',
            'capturistas',
            'referentes',
            'topCapturistas',
            'topReferentes',
            'convencidosPorSeccion',
            'distritosLocales',
            'distritosFederales',
            'nombreDistritoFederal',
            'municipioPorSeccion',
            'puedeVerTodo'
        ));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'cve_mun' => [
                'required',
                'string',
                'size:3',
                Rule::exists('secciones', 'cve_mun'),
            ],
            'meta_convencidos' => ['required', 'integer', 'min:1'],
            'meta_lonas' => ['nullable', 'integer', 'min:0'],
            'distrito_local' => [
                'required',
                'integer',
                Rule::exists('secciones', 'distrito_local')
                    ->where(fn($query) => $query->where('cve_mun', $request->input('cve_mun'))),
            ],
        ]);

        $this->authorizeDistrict($request, (int)$data['distrito_local']);

        DB::transaction(function () use ($data, $request) {
            foreach ([
                MetaAvance::TIPO_CONVENCIDOS => $data['meta_convencidos'],
                MetaAvance::TIPO_LONAS => $data['meta_lonas'] ?? 0,
            ] as $tipo => $cantidad) {
                $meta = MetaAvance::query()
                    ->where('tipo', $tipo)
                    ->where('cve_mun', $data['cve_mun'])
                    ->where('distrito_local', $data['distrito_local'])
                    ->latest('id')
                    ->first();

                if ($cantidad <= 0) {
                    if ($meta) {
                        $meta->delete();
                    }
                    continue;
                }

                $valores = [
                    'tipo' => $tipo,
                    'cve_mun' => $data['cve_mun'],
                    'distrito_local' => $data['distrito_local'],
                    'meta' => $cantidad,
                    'fecha_inicio' => self::META_FECHA_INICIO,
                    'fecha_fin' => self::META_FECHA_FIN,
                    'activa' => true,
                    'asignado_por' => $request->user()->id,
                ];

                $meta ? $meta->update($valores) : MetaAvance::create($valores);
            }
        });

        return redirect()
            ->route('avance.index')
            ->with('status', 'Meta guardada correctamente.');
    }

    public function update(Request $request, MetaAvance $metaAvance)
    {
        $this->authorizeDistrict($request, (int)$metaAvance->distrito_local);

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
            'distrito_local' => [
                'required',
                'integer',
                Rule::exists('secciones', 'distrito_local')
                    ->where(fn($query) => $query->where('cve_mun', $request->input('cve_mun'))),
            ],
        ]);

        $this->authorizeDistrict($request, (int)$data['distrito_local']);

        $duplicada = MetaAvance::query()
            ->where('id', '!=', $metaAvance->id)
            ->where('tipo', $data['tipo'])
            ->where('cve_mun', $data['cve_mun'])
            ->where('distrito_local', $data['distrito_local'])
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
            'distrito_local' => $data['distrito_local'],
            'meta' => $data['meta'],
            'fecha_inicio' => self::META_FECHA_INICIO,
            'fecha_fin' => self::META_FECHA_FIN,
            'activa' => $request->has('activa')
                ? $request->boolean('activa')
                : $metaAvance->activa,
            'asignado_por' => $request->user()->id,
        ]);

        return back()->with('status', 'Meta actualizada correctamente.');
    }

    public function destroy(Request $request, MetaAvance $metaAvance)
    {
        $this->authorizeDistrict($request, (int)$metaAvance->distrito_local);
        $metaAvance->delete();

        return back()->with('status', 'Meta eliminada correctamente.');
    }

    private static function scopeKey(string $cveMun, $distritoLocal): string
    {
        return $cveMun.'|'.(string)$distritoLocal;
    }

    private function authorizeDistrict(Request $request, int $distritoLocal): void {
        LocalDistrictAccess::authorize($distritoLocal, $request->user());
    }
}
