<?php

namespace App\Http\Controllers;

use App\Models\MetaAvance;
use App\Models\SeccionPrioritaria;
use App\Models\User;
use App\Services\AvanceExcelExporter;
use App\Services\AvancePrioritariasExcelExporter;
use App\Services\AvanceSeccionesExcelExporter;
use App\Support\LocalDistrictAccess;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class MetaAvanceController extends Controller
{
    private const MORELIA_CVE_MUN = '053';
    private const MORELIA_LOCAL_DISTRICTS = [10, 11, 16, 17];
    private const META_FECHA_INICIO = '2000-01-01';
    private const META_FECHA_FIN = '2099-12-31';

    private const REFERENTES_DISTRITALES = [
        'Gladyz Butanda',
        'Moises Navarro',
        'Andrea Serna',
        'Oscar Solis',
        'Erendira Isauro',
        'David Alfaro',
        'Monica Valdez',
        'Sergio Ramirez',
        'Emmanuel Uribe',
        'Marco Polo',
        'Alejandra Anguiano',
        'Antonio Itlahuac',
        'Eranderini',
        'Alejandro Mendez',
        'Olivia Guzman',
        'Alex Moran',
        'Elias Ibarra',
        'Elías Ibarra Torres',
        'Salvador Vázquez',
        'Erandeni',
        'Sergio Baez',
        'Yeyo Pimentel',
        'Fany Arreola',
        'Nallely Pedraza',
    ];

    public function index(Request $request)
    {
        extract($this->resolveFilters($request));

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

        $convencidosDetalleQuery = DB::table('afiliados_resumen as a')
            ->select(
                'a.seccion',
                'a.cve_mun',
                'a.distrito_local',
                'a.capturista_id',
                'a.referente',
                DB::raw('SUM(a.total) AS total')
            )
            ->when(
                $cveMun !== '',
                fn($query) => $query->where(
                    'a.cve_mun',
                    $cveMun
                )
            )
            ->when(
                $distritoLocal !== '',
                fn($query) => $query->where(
                    'a.distrito_local',
                    $distritoLocal
                )
            )
            ->when(
                $distritoFederal !== '',
                fn($query) => $query->where(
                    'a.distrito_federal',
                    $distritoFederal
                )
            )
            ->when(
                $referente !== '',
                fn($query) => $query->whereRaw(
                    'TRIM(a.referente) = ?',
                    [$referente]
                )
            )
            ->when(
                $capturistaId,
                fn($query) => $query->where(
                    'a.capturista_id',
                    $capturistaId
                )
            );

        $this->applyTipoCargaFilter(
            $convencidosDetalleQuery,
            $tipoCarga,
            $referentesOficiales,
            'a.referente',
            'a.capturista_id'
        );

        $convencidosDetalle = $convencidosDetalleQuery
            ->groupBy(
                'a.seccion',
                'a.cve_mun',
                'a.distrito_local',
                'a.capturista_id',
                'a.referente'
            )
            ->get();

        $convencidos = $convencidosDetalle
            ->groupBy(fn($fila) => self::scopeKey(
                (string) $fila->cve_mun,
                $fila->distrito_local
            ))
            ->map(fn($filas) => (int) $filas->sum('total'));

        $convencidosConSeccion = $convencidosDetalle
            ->whereNotNull('seccion');

        $convencidosPorSeccion = $convencidosConSeccion
            ->groupBy(fn($fila) => (string) (int) $fila->seccion)
            ->map(fn($filas) => (int) $filas->sum('total'));

        $convencidosPorAlcanceSeccion = $convencidosConSeccion
            ->groupBy(fn($fila) => self::scopeKey(
                (string) $fila->cve_mun,
                $fila->distrito_local
            ).'|'.(string) (int) $fila->seccion)
            ->map(fn($filas) => (int) $filas->sum('total'));

        $capturistaNames = User::query()
            ->whereIn(
                'id',
                $convencidosDetalle
                    ->pluck('capturista_id')
                    ->filter()
                    ->unique()
            )
            ->pluck('name', 'id');

        $topCapturistas = $convencidosDetalle
            ->groupBy('capturista_id')
            ->map(function ($filas, $id) use ($capturistaNames) {
                return (object) [
                    'id' => (int) $id,
                    'name' => (string) (
                        $capturistaNames[$id] ?? 'Sin capturista'
                    ),
                    'total' => (int) $filas->sum('total'),
                ];
            })
            ->sort(
                fn($a, $b) =>
                    $b->total <=> $a->total
                    ?: strcmp($a->name, $b->name)
            )
            ->take(5)
            ->values();

        $topReferentesQuery = DB::table('afiliados as a')
            ->selectRaw(
                'TRIM(a.perfil) AS name, COUNT(*) AS total'
            )
            ->whereNull('a.deleted_at')
            ->whereNotNull('a.perfil')
            ->whereRaw("TRIM(a.perfil) <> ''")
            ->when(
                $cveMun !== '',
                fn($query) => $query->where(
                    'a.cve_mun',
                    $cveMun
                )
            )
            ->when(
                $distritoLocal !== '',
                fn($query) => $query->where(
                    'a.distrito_local',
                    $distritoLocal
                )
            )
            ->when(
                $distritoFederal !== '',
                fn($query) => $query->where(
                    'a.distrito_federal',
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
            );

        $this->applyTipoCargaFilter(
            $topReferentesQuery,
            $tipoCarga,
            $referentesOficiales,
            'a.perfil',
            'a.capturista_id'
        );

        $topReferentes = $topReferentesQuery
            ->groupByRaw('TRIM(a.perfil)')
            ->orderByDesc('total')
            ->orderBy('name')
            ->get();

        $lonasQuery = DB::table('lonas')
            ->join(
                'secciones',
                'secciones.seccion',
                '=',
                'lonas.seccion'
            )
            ->select(
                'secciones.cve_mun',
                'secciones.distrito_local',
                DB::raw(
                    'COUNT(DISTINCT lonas.id) AS total'
                )
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
            );

        $this->applyTipoCargaFilter(
            $lonasQuery,
            $tipoCarga,
            $referentesOficiales,
            'lonas.responsable',
            'lonas.capturado_por'
        );

        $lonas = $lonasQuery
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
                            (
                                $totalConvencidos
                                / $cantidadMetaConvencidos
                            ) * 100,
                            2
                        )
                        : 0,
                    'meta_lonas_id' => $metaLonas->id ?? null,
                    'meta_lonas' => $cantidadMetaLonas,
                    'total_lonas' => $totalLonas,
                    'porcentaje_lonas' => $cantidadMetaLonas > 0
                        ? round(
                            (
                                $totalLonas
                                / $cantidadMetaLonas
                            ) * 100,
                            2
                        )
                        : 0,
                ];
            }
        );

        $totalMetaConvencidos = (int) $avance->sum(
            'meta_convencidos'
        );

        $totalConvencidos = (int) $convencidos->sum();

        $totalSeccionesCubiertas = $convencidosPorAlcanceSeccion
            ->filter(
                fn($total) => (int) $total > 0
            )
            ->count();

        $totalSecciones = (int) $avance->sum(
            'secciones'
        );

        $totalMetaLonas = (int) $avance->sum(
            'meta_lonas'
        );

        $totalLonas = (int) $avance->sum(
            'total_lonas'
        );

        $totales = [
            'secciones' => $totalSecciones,
            'secciones_cubiertas' => $totalSeccionesCubiertas,
            'porcentaje_secciones_cubiertas' => $totalSecciones > 0
                ? round(
                    (
                        $totalSeccionesCubiertas
                        / $totalSecciones
                    ) * 100,
                    2
                )
                : 0,
            'meta_convencidos' => $totalMetaConvencidos,
            'total_convencidos' => $totalConvencidos,
            'porcentaje_convencidos' => $totalMetaConvencidos > 0
                ? round(
                    (
                        $totalConvencidos
                        / $totalMetaConvencidos
                    ) * 100,
                    2
                )
                : 0,
            'meta_lonas' => $totalMetaLonas,
            'total_lonas' => $totalLonas,
            'porcentaje_lonas' => $totalMetaLonas > 0
                ? round(
                    (
                        $totalLonas
                        / $totalMetaLonas
                    ) * 100,
                    2
                )
                : 0,
        ];

        $capturistas = User::query()
            ->when(
                !$puedeVerTodo,
                fn($query) => $query->whereKey(
                    $usuario->id
                )
            )
            ->where(function ($query) use (
                $cveMun,
                $distritoLocal,
                $distritoFederal,
                $tipoCarga,
                $referentesOficiales
            ) {
                $query->whereExists(function ($subquery) use (
                    $cveMun,
                    $distritoLocal,
                    $distritoFederal,
                    $tipoCarga,
                    $referentesOficiales
                ) {
                    $subquery
                        ->selectRaw('1')
                        ->from('afiliados_resumen')
                        ->whereColumn(
                            'afiliados_resumen.capturista_id',
                            'users.id'
                        )
                        ->when(
                            $cveMun !== '',
                            fn($query) => $query->where(
                                'afiliados_resumen.cve_mun',
                                $cveMun
                            )
                        )
                        ->when(
                            $distritoLocal !== '',
                            fn($query) => $query->where(
                                'afiliados_resumen.distrito_local',
                                $distritoLocal
                            )
                        )
                        ->when(
                            $distritoFederal !== '',
                            fn($query) => $query->where(
                                'afiliados_resumen.distrito_federal',
                                $distritoFederal
                            )
                        );

                    $this->applyTipoCargaFilter(
                        $subquery,
                        $tipoCarga,
                        $referentesOficiales,
                        'afiliados_resumen.referente',
                        'afiliados_resumen.capturista_id'
                    );
                })
                ->orWhereExists(function ($subquery) use (
                    $cveMun,
                    $distritoLocal,
                    $distritoFederal,
                    $tipoCarga,
                    $referentesOficiales
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
                        ->whereNull(
                            'lonas.deleted_at'
                        )
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

                    $this->applyTipoCargaFilter(
                        $subquery,
                        $tipoCarga,
                        $referentesOficiales,
                        'lonas.responsable',
                        'lonas.capturado_por'
                    );
                });
            })
            ->orderBy('name')
            ->get([
                'id',
                'name',
            ]);

        $referentesAfiliados = $topReferentes
            ->pluck('name');

        $referentesLonasQuery = DB::table('lonas')
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
            ->whereRaw(
                "TRIM(lonas.responsable) <> ''"
            )
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

        $this->applyTipoCargaFilter(
            $referentesLonasQuery,
            $tipoCarga,
            $referentesOficiales,
            'lonas.responsable',
            'lonas.capturado_por'
        );

        $referentesLonas = $referentesLonasQuery
            ->distinct()
            ->pluck('referente');

        $referentesRegistrados = $referentesAfiliados
            ->merge($referentesLonas)
            ->filter()
            ->unique()
            ->sort()
            ->values();

        $referentes = $referentesRegistrados
            ->unique()
            ->sort()
            ->values();

        $distritosLocales = DB::table('secciones')
            ->where('cve_mun', $this->campaignCveMun())
            ->whereIn('distrito_local', $this->campaignDistricts())
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
            ->where('cve_mun', $this->campaignCveMun())
            ->whereIn('distrito_local', $this->campaignDistricts())
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
            : 'Morelia';

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

        $seccionesPorMunicipio = DB::table('secciones')
            ->select(
                'seccion',
                'cve_mun',
                'municipio',
                'distrito_local',
                'distrito_federal'
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
            ->orderByRaw(
                'CAST(seccion AS UNSIGNED), seccion'
            )
            ->get()
            ->groupBy(
                fn($seccion) => self::scopeKey(
                    (string) $seccion->cve_mun,
                    $seccion->distrito_local
                )
            )
            ->map(
                function (
                    $secciones,
                    $scope
                ) use (
                    $convencidosPorAlcanceSeccion
                ) {
                    return $secciones
                        ->map(
                            function (
                                $seccion
                            ) use (
                                $scope,
                                $convencidosPorAlcanceSeccion
                            ) {
                                $numeroNormalizado = (string) (int) $seccion->seccion;

                                return [
                                    'seccion' => str_pad(
                                        (string) $seccion->seccion,
                                        4,
                                        '0',
                                        STR_PAD_LEFT
                                    ),
                                    'distrito_federal' => $seccion->distrito_federal,
                                    'total' => (int) (
                                        $convencidosPorAlcanceSeccion[
                                            $scope
                                            .'|'
                                            .$numeroNormalizado
                                        ] ?? 0
                                    ),
                                ];
                            }
                        )
                        ->values();
                }
            );

        $campaignCveMun = $this->campaignCveMun();
        $campaignDistricts = $this->campaignDistricts();

        $seccionesPrioritarias = SeccionPrioritaria::query()
            ->where('cve_mun', $campaignCveMun)
            ->orderByDesc('prioridad')
            ->orderByRaw('CAST(seccion AS UNSIGNED)')
            ->get();

        $seccionesMoreliaDisponibles = DB::table('secciones')
            ->where('cve_mun', $campaignCveMun)
            ->whereIn('distrito_local', $campaignDistricts)
            ->select('seccion', 'distrito_local')
            ->orderBy('distrito_local')
            ->orderByRaw('CAST(seccion AS UNSIGNED)')
            ->get();

        $seccionesMoreliaPorNumero = $seccionesMoreliaDisponibles
            ->keyBy(fn($row) => (string) (int) $row->seccion);

        $prioridadConteos = [
            'total' => $seccionesPrioritarias->count(),
            'alta' => $seccionesPrioritarias->where('prioridad', 3)->count(),
            'media' => $seccionesPrioritarias->where('prioridad', 2)->count(),
            'baja' => $seccionesPrioritarias->where('prioridad', 1)->count(),
            'con_meta' => $seccionesPrioritarias->where('meta_convencidos', '>', 0)->count(),
        ];

        $data = compact(
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
            'seccionesPorMunicipio',
            'puedeVerTodo',
            'tipoCarga',
            'seccionesPrioritarias',
            'seccionesMoreliaDisponibles',
            'seccionesMoreliaPorNumero',
            'prioridadConteos'
        );

        if ($request->expectsJson()) {
            return response()->json($data);
        }

        return view(
            'avance.index',
            $data
        );
    }

    public function convencidos(Request $request)
    {
        $filters = $this->resolveFilters($request);
        $personas = $this->convencidosQuery($filters)
            ->orderBy('a.distrito_local')
            ->orderBy('a.distrito_federal')
            ->orderByRaw('CAST(a.seccion AS UNSIGNED)')
            ->orderBy('a.nombre')
            ->paginate(50)
            ->withQueryString();

        if ($request->expectsJson()) {
            return response()->json($personas);
        }

        return view('avance.convencidos', [
            'personas' => $personas,
            'filtros' => $this->filterSummary($filters),
            'backQuery' => $request->query(),
        ]);
    }

    public function export(
        Request $request,
        AvanceExcelExporter $exporter
    ) {
        $filters = $this->resolveFilters($request);
        $view = $this->index($request);
        $data = $view->getData();

        $path = $exporter->create(
            $data['avance'],
            $data['totales'],
            $this->convencidosQuery($filters),
            $this->filterSummary($filters)
        );

        return response()
            ->download(
                $path,
                'avance_filtrado_'.now()->format('Ymd_His').'.xlsx',
                [
                    'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
                ]
            )
            ->deleteFileAfterSend(true);
    }

    public function exportSecciones(
        Request $request,
        AvanceSeccionesExcelExporter $exporter
    ) {
        $filters = $this->resolveFilters($request);
        $path = $exporter->create(
            $this->resumenSecciones($filters),
            $this->filterSummary($filters)
        );

        return response()
            ->download(
                $path,
                'avance_por_seccion_'.now()->format('Ymd_His').'.xlsx',
                [
                    'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
                ]
            )
            ->deleteFileAfterSend(true);
    }

    public function exportSeccionesPrioritarias(
        Request $request,
        AvancePrioritariasExcelExporter $exporter
    ) {
        $filters = $this->resolveFilters($request);
        $filters['cveMun'] = $this->campaignCveMun();

        $prioridades = SeccionPrioritaria::query()
            ->where('cve_mun', $this->campaignCveMun())
            ->get()
            ->keyBy(fn(SeccionPrioritaria $row) => (string) (int) $row->seccion);

        $reference = $this->priorityReference();
        $rows = $this->resumenSecciones($filters)
            ->filter(fn(array $row) => $prioridades->has((string) (int) $row['seccion']))
            ->map(function (array $row) use ($prioridades, $reference) {
                $key = (string) (int) $row['seccion'];
                $configured = $prioridades->get($key);
                $source = $reference[$key] ?? [];

                return array_merge($row, [
                    'prioridad' => (int) $configured->prioridad,
                    'prioridad_etiqueta' => [3 => 'Alta', 2 => 'Media', 1 => 'Baja'][(int) $configured->prioridad],
                    'meta_convencidos' => (int) $configured->meta_convencidos,
                    'meta_lonas' => (int) $configured->meta_lonas,
                    'colonias' => $row['colonias'] !== '' ? $row['colonias'] : (string) ($source['colonias'] ?? ''),
                    'lista_nominal_2024' => (int) ($source['lista_nominal_2024'] ?? $row['lista_nominal'] ?? 0),
                ]);
            })
            ->sortBy([['distrito', 'asc'], ['seccion', 'asc']])
            ->values();

        $sectionNumbers = $rows->pluck('seccion')->map(fn($value) => (string) (int) $value)->all();
        $people = $this->convencidosQuery($filters)
            ->whereIn(DB::raw('CAST(a.seccion AS UNSIGNED)'), array_map('intval', $sectionNumbers))
            ->get();

        $path = $exporter->create(
            $rows,
            $people,
            $this->filterSummary($filters)
        );

        return response()->download(
            $path,
            'avance_secciones_prioritarias_'.now()->format('Ymd_His').'.xlsx',
            [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            ]
        )->deleteFileAfterSend(true);
    }

    public function storeSeccionPrioritaria(Request $request)
    {
        $campaignCveMun = $this->campaignCveMun();
        $campaignDistricts = $this->campaignDistricts();
        $data = $request->validate([
            'seccion' => [
                'required',
                'string',
                'regex:/^\d{1,6}$/',
                function ($attribute, $value, $fail) use ($campaignCveMun, $campaignDistricts) {
                    $exists = DB::table('secciones')
                        ->where('cve_mun', $campaignCveMun)
                        ->whereIn('distrito_local', $campaignDistricts)
                        ->whereRaw('CAST(seccion AS UNSIGNED) = ?', [(int) $value])
                        ->exists();

                    if (!$exists) {
                        $fail('La sección seleccionada no pertenece a Morelia.');
                    }
                },
            ],
            'prioridad' => ['required', 'integer', Rule::in([1, 2, 3])],
            'meta_convencidos' => ['required', 'integer', 'min:0'],
            'meta_lonas' => ['nullable', 'integer', 'min:0'],
        ]);

        SeccionPrioritaria::query()->updateOrCreate(
            ['cve_mun' => $campaignCveMun, 'seccion' => str_pad((string) $data['seccion'], 4, '0', STR_PAD_LEFT)],
            [
                'prioridad' => (int) $data['prioridad'],
                'meta_convencidos' => (int) $data['meta_convencidos'],
                'meta_lonas' => (int) ($data['meta_lonas'] ?? 0),
                'asignado_por' => $request->user()->id,
            ]
        );

        return back()->with('status', 'Sección prioritaria guardada correctamente.');
    }

    public function destroySeccionPrioritaria(SeccionPrioritaria $seccionPrioritaria)
    {
        abort_unless($seccionPrioritaria->cve_mun === $this->campaignCveMun(), 404);
        $seccionPrioritaria->delete();

        return back()->with('status', 'Sección retirada de la lista prioritaria.');
    }

    public function store(Request $request)
    {
        $campaignCveMun = $this->campaignCveMun();
        $campaignDistricts = $this->campaignDistricts();
        $data = $request->validate([
            'cve_mun' => [
                'required',
                'string',
                'size:3',
                Rule::in([$campaignCveMun]),
            ],
            'meta_convencidos' => ['required', 'integer', 'min:1'],
            'meta_lonas' => ['nullable', 'integer', 'min:0'],
            'distrito_local' => [
                'required',
                'integer',
                Rule::exists('secciones', 'distrito_local')
                    ->where(fn($query) => $query->where('cve_mun', $campaignCveMun)),
                Rule::in($campaignDistricts),
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

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'message' => 'Meta guardada correctamente.',
            ]);
        }

        return redirect()
            ->route('avance.index')
            ->with('status', 'Meta guardada correctamente.');
    }

    public function update(Request $request, MetaAvance $metaAvance)
    {
        $this->authorizeDistrict($request, (int)$metaAvance->distrito_local);

        $campaignCveMun = $this->campaignCveMun();
        $campaignDistricts = $this->campaignDistricts();

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
                Rule::in([$campaignCveMun]),
            ],
            'meta' => ['required', 'integer', 'min:0'],
            'distrito_local' => [
                'required',
                'integer',
                Rule::exists('secciones', 'distrito_local')
                    ->where(fn($query) => $query->where('cve_mun', $campaignCveMun)),
                Rule::in($campaignDistricts),
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

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'message' => 'Meta actualizada correctamente.',
            ]);
        }

        return back()->with('status', 'Meta actualizada correctamente.');
    }

    public function destroy(Request $request, MetaAvance $metaAvance)
    {
        $this->authorizeDistrict($request, (int)$metaAvance->distrito_local);
        $metaAvance->delete();

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'message' => 'Meta eliminada correctamente.',
            ]);
        }

        return back()->with('status', 'Meta eliminada correctamente.');
    }

    private static function scopeKey(string $cveMun, $distritoLocal): string
    {
        return $cveMun.'|'.(string)$distritoLocal;
    }

    private function resolveFilters(Request $request): array
    {
        $usuario = $request->user();

        $puedeVerTodo = $usuario->hasAnyRole([
            'Admin',
            'SuperAdmin',
        ]);

        $cveMun = $this->campaignCveMun();

        $distritosLocalesOriginales = LocalDistrictAccess::districts(
            $usuario
        );

        $distritosLocalesAsignados = array_values(array_intersect(
            $distritosLocalesOriginales,
            $this->campaignDistricts()
        ));

        $distritoLocalRestringido = (
            $distritosLocalesOriginales !== []
        );

        $distritoSolicitado = trim(
            (string) $request->query(
                'distrito_local'
            )
        );

        $distritoSolicitado = in_array((int) $distritoSolicitado, $this->campaignDistricts(), true)
            ? (string) (int) $distritoSolicitado
            : '';

        $distritoLocal = $distritoLocalRestringido
            ? (
                $distritosLocalesAsignados === []
                    ? '-1'
                    : (
                in_array(
                    (int) $distritoSolicitado,
                    $distritosLocalesAsignados,
                    true
                )
                    ? (string) (int) $distritoSolicitado
                    : (string) $distritosLocalesAsignados[0]
                    )
            )
            : $distritoSolicitado;

        $distritoFederal = '';

        $referente = trim(
            (string) $request->query(
                'referente'
            )
        );

        $referentesOficiales = self::REFERENTES_DISTRITALES;

        $tipoCarga = Str::lower(
            trim(
                (string) $request->query(
                    'tipo_carga',
                    'todos'
                )
            )
        );

        if (
            !in_array(
                $tipoCarga,
                [
                    'todos',
                    'distritales',
                    'politicos',
                ],
                true
            )
        ) {
            $tipoCarga = 'todos';
        }

        $capturistaId = $puedeVerTodo
            ? (
                $request->filled(
                    'capturista_id'
                )
                    ? (int) $request->query(
                        'capturista_id'
                    )
                    : null
            )
            : (int) $usuario->id;

        return compact(
            'usuario',
            'puedeVerTodo',
            'cveMun',
            'distritosLocalesAsignados',
            'distritoLocalRestringido',
            'distritoLocal',
            'distritoFederal',
            'referente',
            'referentesOficiales',
            'capturistaId',
            'tipoCarga'
        );
    }

    private function convencidosQuery(array $filters): Builder
    {
        $query = DB::table('afiliados as a')
            ->leftJoin(
                'users as capturistas_detalle',
                'capturistas_detalle.id',
                '=',
                'a.capturista_id'
            )
            ->select([
                'a.id',
                'a.nombre',
                'a.apellido_paterno',
                'a.apellido_materno',
                'a.telefono',
                'a.perfil as referente',
                'capturistas_detalle.name as capturista',
                'a.seccion',
                'a.municipio',
                'a.cve_mun',
                'a.distrito_local',
                'a.distrito_federal',
                'a.fecha_convencimiento',
                'a.created_at',
            ])
            ->whereNull('a.deleted_at')
            ->when(
                $filters['cveMun'] !== '',
                fn($query) => $query->where(
                    'a.cve_mun',
                    $filters['cveMun']
                )
            )
            ->when(
                $filters['distritoLocal'] !== '',
                fn($query) => $query->where(
                    'a.distrito_local',
                    $filters['distritoLocal']
                )
            )
            ->when(
                $filters['distritoFederal'] !== '',
                fn($query) => $query->where(
                    'a.distrito_federal',
                    $filters['distritoFederal']
                )
            )
            ->when(
                $filters['referente'] !== '',
                fn($query) => $query->whereRaw(
                    'TRIM(a.perfil) = ?',
                    [$filters['referente']]
                )
            )
            ->when(
                $filters['capturistaId'],
                fn($query) => $query->where(
                    'a.capturista_id',
                    $filters['capturistaId']
                )
            );

        $this->applyTipoCargaFilter(
            $query,
            $filters['tipoCarga'],
            $filters['referentesOficiales'],
            'a.perfil',
            'a.capturista_id'
        );

        return $query;
    }

    private function resumenSecciones(array $filters)
    {
        $secciones = DB::table('secciones as s')
            ->select(
                's.seccion',
                's.cve_mun',
                's.municipio',
                's.distrito_local',
                's.distrito_federal',
                's.lista_nominal'
            )
            ->when(
                $filters['cveMun'] !== '',
                fn($query) => $query->where('s.cve_mun', $filters['cveMun'])
            )
            ->when(
                $filters['distritoLocal'] !== '',
                fn($query) => $query->where('s.distrito_local', $filters['distritoLocal'])
            )
            ->when(
                $filters['distritoFederal'] !== '',
                fn($query) => $query->where('s.distrito_federal', $filters['distritoFederal'])
            )
            ->get();

        $convencidosQuery = DB::table('afiliados as a')
            ->select(
                'a.cve_mun',
                'a.distrito_local',
                'a.seccion',
                'a.colonia',
                DB::raw('COUNT(*) AS total')
            )
            ->whereNull('a.deleted_at')
            ->when(
                $filters['cveMun'] !== '',
                fn($query) => $query->where('a.cve_mun', $filters['cveMun'])
            )
            ->when(
                $filters['distritoLocal'] !== '',
                fn($query) => $query->where('a.distrito_local', $filters['distritoLocal'])
            )
            ->when(
                $filters['distritoFederal'] !== '',
                fn($query) => $query->where('a.distrito_federal', $filters['distritoFederal'])
            )
            ->when(
                $filters['referente'] !== '',
                fn($query) => $query->whereRaw('TRIM(a.perfil) = ?', [$filters['referente']])
            )
            ->when(
                $filters['capturistaId'],
                fn($query) => $query->where('a.capturista_id', $filters['capturistaId'])
            );

        $this->applyTipoCargaFilter(
            $convencidosQuery,
            $filters['tipoCarga'],
            $filters['referentesOficiales'],
            'a.perfil',
            'a.capturista_id'
        );

        $convencidos = $convencidosQuery
            ->groupBy(
                'a.cve_mun',
                'a.distrito_local',
                'a.seccion',
                'a.colonia'
            )
            ->get()
            ->groupBy(fn($row) => $this->sectionSummaryKey(
                $row->cve_mun,
                $row->distrito_local,
                $row->seccion
            ));

        $lonasQuery = DB::table('lonas as l')
            ->join('secciones as s', 's.seccion', '=', 'l.seccion')
            ->select(
                's.cve_mun',
                's.distrito_local',
                's.seccion',
                DB::raw('COUNT(DISTINCT l.id) AS total')
            )
            ->whereNull('l.deleted_at')
            ->when(
                $filters['cveMun'] !== '',
                fn($query) => $query->where('s.cve_mun', $filters['cveMun'])
            )
            ->when(
                $filters['distritoLocal'] !== '',
                fn($query) => $query->where('s.distrito_local', $filters['distritoLocal'])
            )
            ->when(
                $filters['distritoFederal'] !== '',
                fn($query) => $query->where('s.distrito_federal', $filters['distritoFederal'])
            )
            ->when(
                $filters['referente'] !== '',
                fn($query) => $query->whereRaw('TRIM(l.responsable) = ?', [$filters['referente']])
            )
            ->when(
                $filters['capturistaId'],
                fn($query) => $query->where('l.capturado_por', $filters['capturistaId'])
            );

        $this->applyTipoCargaFilter(
            $lonasQuery,
            $filters['tipoCarga'],
            $filters['referentesOficiales'],
            'l.responsable',
            'l.capturado_por'
        );

        $lonas = $lonasQuery
            ->groupBy('s.cve_mun', 's.distrito_local', 's.seccion')
            ->get()
            ->keyBy(fn($row) => $this->sectionSummaryKey(
                $row->cve_mun,
                $row->distrito_local,
                $row->seccion
            ));

        return $secciones
            ->map(function ($seccion) use ($convencidos, $lonas) {
                $key = $this->sectionSummaryKey(
                    $seccion->cve_mun,
                    $seccion->distrito_local,
                    $seccion->seccion
                );
                $personas = $convencidos->get($key, collect());
                $colonias = $personas
                    ->pluck('colonia')
                    ->map(fn($colonia) => trim((string) $colonia))
                    ->filter()
                    ->unique(fn($colonia) => Str::lower($colonia))
                    ->sort(SORT_NATURAL | SORT_FLAG_CASE)
                    ->values();

                return [
                    'distrito' => (int) $seccion->distrito_local,
                    'seccion' => str_pad((string) $seccion->seccion, 4, '0', STR_PAD_LEFT),
                    'colonias' => $colonias->implode(', '),
                    'lista_nominal' => (int) $seccion->lista_nominal,
                    'lonas' => (int) optional($lonas->get($key))->total,
                    'convencidos' => (int) $personas->sum('total'),
                ];
            })
            ->sortBy([
                ['distrito', 'asc'],
                ['seccion', 'asc'],
            ])
            ->values();
    }

    private function sectionSummaryKey($cveMun, $distritoLocal, $seccion): string
    {
        return (string) $cveMun
            .'|'.(string) (int) $distritoLocal
            .'|'.(string) (int) $seccion;
    }

    private function priorityReference(): array
    {
        $path = database_path('data/electoral/morelia_priority_colonies_reference.json');
        if (!is_file($path)) {
            return [];
        }

        $reference = json_decode(file_get_contents($path), true);

        return collect($reference['filas'] ?? [])->mapWithKeys(
            fn(array $row) => [(string) (int) $row['seccion'] => $row]
        )->all();
    }

    private function campaignCveMun(): string
    {
        return (string) config('avance.municipio_cve', self::MORELIA_CVE_MUN);
    }

    private function campaignDistricts(): array
    {
        return array_map('intval', config('avance.distritos_locales', self::MORELIA_LOCAL_DISTRICTS));
    }

    private function applyTipoCargaFilter(
        Builder $query,
        string $tipoCarga,
        array $referentesOficiales,
        string $referenteColumn,
        string $capturistaColumn
    ): Builder {
        if ($tipoCarga === 'todos') {
            return $query;
        }

        if ($tipoCarga === 'distritales') {
            $referentesNormalizados = array_map(
                fn($nombre) => Str::lower(trim($nombre)),
                $referentesOficiales
            );

            $query->where(
                function ($query) use (
                    $referentesNormalizados,
                    $referenteColumn,
                    $capturistaColumn
                ) {
                    $query
                        ->whereIn(
                            DB::raw(
                                "LOWER(TRIM({$referenteColumn}))"
                            ),
                            $referentesNormalizados
                        )
                        ->orWhereExists(
                            function ($subquery) use (
                                $capturistaColumn
                            ) {
                                $subquery
                                    ->selectRaw('1')
                                    ->from(
                                        'model_has_roles as mhr_tipo'
                                    )
                                    ->join(
                                        'roles as roles_tipo',
                                        'roles_tipo.id',
                                        '=',
                                        'mhr_tipo.role_id'
                                    )
                                    ->whereColumn(
                                        'mhr_tipo.model_id',
                                        $capturistaColumn
                                    )
                                    ->where(
                                        'mhr_tipo.model_type',
                                        User::class
                                    )
                                    ->where(
                                        'roles_tipo.name',
                                        'Distrito Local'
                                    );
                            }
                        );
                }
            );

            return $query;
        }

        if ($tipoCarga === 'politicos') {
            $referentesNormalizados = array_map(
                fn($nombre) => Str::lower(trim($nombre)),
                $referentesOficiales
            );

            $query
                ->whereNotNull($referenteColumn)
                ->whereRaw("TRIM({$referenteColumn}) <> ''")
                ->whereNotIn(
                    DB::raw(
                        "LOWER(TRIM({$referenteColumn}))"
                    ),
                    $referentesNormalizados
                );

            $query->whereNotExists(
                function ($subquery) use (
                    $capturistaColumn
                ) {
                    $subquery
                        ->selectRaw('1')
                        ->from(
                            'model_has_roles as mhr_tipo'
                        )
                        ->join(
                            'roles as roles_tipo',
                            'roles_tipo.id',
                            '=',
                            'mhr_tipo.role_id'
                        )
                        ->whereColumn(
                            'mhr_tipo.model_id',
                            $capturistaColumn
                        )
                        ->where(
                            'mhr_tipo.model_type',
                            User::class
                        )
                        ->where(
                            'roles_tipo.name',
                            'Distrito Local'
                        );
                }
            );
        }

        return $query;
    }


    private function filterSummary(array $filters): array
    {
        $municipio = 'Morelia';

        $capturista = $filters['capturistaId']
            ? DB::table('users')
                ->where('id', $filters['capturistaId'])
                ->value('name')
            : null;

        $tipoCarga = match ($filters['tipoCarga']) {
            'distritales' => 'Distrito local',
            'politicos' => 'Políticos',
            default => 'Todos',
        };

        return [
            'Tipo de carga' => $tipoCarga,
            'Distrito local' => $filters['distritoLocal'] !== ''
                ? str_pad(
                    $filters['distritoLocal'],
                    2,
                    '0',
                    STR_PAD_LEFT
                )
                : 'Todos',
            'Municipio' => $municipio,
            'Referente' => $filters['referente'] ?: 'Todos',
            'Capturista' => $capturista ?: 'Todos',
        ];
    }

    private function authorizeDistrict(Request $request, int $distritoLocal): void {
        LocalDistrictAccess::authorize($distritoLocal, $request->user());
    }
}
