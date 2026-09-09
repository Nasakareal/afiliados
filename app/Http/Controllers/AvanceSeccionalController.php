<?php

namespace App\Http\Controllers;

use App\Models\ReferenteSeccional;
use App\Support\LocalDistrictAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AvanceSeccionalController extends Controller
{
    public function index(Request $request)
    {
        $filtros = $this->resolveFilters($request);

        $usuario = $filtros['usuario'];
        $puedeVerTodo = $filtros['puedeVerTodo'];
        $cveMun = $filtros['cveMun'];
        $seccion = $filtros['seccion'];
        $distritoLocal = $filtros['distritoLocal'];
        $distritoFederal = $filtros['distritoFederal'];
        $estado = $filtros['estado'];
        $q = $filtros['q'];
        $distritoLocalRestringido = $filtros['distritoLocalRestringido'];
        $distritosLocalesAsignados = $filtros['distritosLocalesAsignados'];

        $seccionesQuery = DB::table('secciones')
            ->select(
                'seccion',
                'cve_mun',
                'municipio',
                'distrito_local',
                'distrito_federal',
                'lista_nominal'
            );

        LocalDistrictAccess::scope($seccionesQuery);

        $secciones = $seccionesQuery
            ->when(
                $cveMun !== '',
                fn ($query) => $query->where(
                    'cve_mun',
                    $cveMun
                )
            )
            ->when(
                $seccion !== '',
                fn ($query) => $query->where(
                    'seccion',
                    $seccion
                )
            )
            ->when(
                $distritoLocal !== '',
                fn ($query) => $query->where(
                    'distrito_local',
                    $distritoLocal
                )
            )
            ->when(
                $distritoFederal !== '',
                fn ($query) => $query->where(
                    'distrito_federal',
                    $distritoFederal
                )
            )
            ->orderBy('distrito_local')
            ->orderBy('municipio')
            ->orderByRaw('CAST(seccion AS UNSIGNED)')
            ->get()
            ->map(function ($fila) {
                $fila->cve_mun = str_pad(
                    (string) $fila->cve_mun,
                    3,
                    '0',
                    STR_PAD_LEFT
                );

                return $fila;
            });

        $cvesMunicipales = $secciones
            ->pluck('cve_mun')
            ->unique()
            ->values();

        $numerosSeccion = $secciones
            ->pluck('seccion')
            ->map(
                fn ($numero) => (string) (int) $numero
            )
            ->unique()
            ->values();

        $referentesQuery = ReferenteSeccional::query();

        if ($cvesMunicipales->isNotEmpty()) {
            $referentesQuery->whereIn(
                'cve_mun',
                $cvesMunicipales
            );
        } else {
            $referentesQuery->whereRaw('1 = 0');
        }

        if ($numerosSeccion->isNotEmpty()) {
            $referentesQuery->whereIn(
                'seccion',
                $numerosSeccion
            );
        }

        $referentes = $referentesQuery
            ->get()
            ->groupBy(function ($referente) {
                return self::sectionKey(
                    (string) $referente->cve_mun,
                    $referente->seccion
                );
            });

        $avanceCompleto = $secciones
            ->map(function ($fila) use ($referentes) {
                $key = self::sectionKey(
                    (string) $fila->cve_mun,
                    $fila->seccion
                );

                $referentesSeccion = $referentes
                    ->get($key, collect())
                    ->sortBy('posicion')
                    ->values();

                $referente = $referentesSeccion->first();

                $cantidadReferentes = $referentesSeccion->count();
                $tieneReferente = $cantidadReferentes > 0;

                return [
                    'cve_mun' => str_pad(
                        (string) $fila->cve_mun,
                        3,
                        '0',
                        STR_PAD_LEFT
                    ),

                    'municipio' =>
                        $fila->municipio,

                    'seccion' =>
                        (string) $fila->seccion,

                    'seccion_formateada' =>
                        str_pad(
                            (string) $fila->seccion,
                            4,
                            '0',
                            STR_PAD_LEFT
                        ),

                    'distrito_local' =>
                        $fila->distrito_local !== null
                            ? (int) $fila->distrito_local
                            : null,

                    'distrito_federal' =>
                        $fila->distrito_federal !== null
                            ? (int) $fila->distrito_federal
                            : null,

                    'lista_nominal' =>
                        (int) ($fila->lista_nominal ?? 0),

                    'tiene_referente' =>
                        $tieneReferente,

                    'cantidad_referentes' =>
                        $cantidadReferentes,

                    'cupo_completo' =>
                        $cantidadReferentes >= 2,

                    'referentes' =>
                        $referentesSeccion,

                    'referente_id' =>
                        $referente?->id,

                    'referente_activo' =>
                        $referente
                            ? (bool) $referente->activo
                            : false,

                    'nombre_completo' =>
                        $referente?->nombre_completo,

                    'telefono' =>
                        $referente?->telefono,

                    'telefono_alternativo' =>
                        $referente?->telefono_alternativo,

                    'correo' =>
                        $referente?->correo,

                    'whatsapp' =>
                        $referente
                            ? (bool) $referente->whatsapp
                            : false,

                    'cargo' =>
                        $referente?->cargo,

                    'organizacion' =>
                        $referente?->organizacion,

                    'localidad' =>
                        $referente?->localidad,

                    'colonia' =>
                        $referente?->colonia,

                    'direccion' =>
                        $referente?->direccion,

                    'activo' =>
                        $referente
                            ? (bool) $referente->activo
                            : null,
                ];
            });

        $totalSecciones = $avanceCompleto->count();

        $totalConReferente = $avanceCompleto
            ->filter(
                fn ($fila) =>
                    $fila['tiene_referente']
            )
            ->count();

        $totalSinReferente =
            $totalSecciones - $totalConReferente;

        $totalActivos = $avanceCompleto
            ->filter(
                fn ($fila) =>
                    $fila['tiene_referente']
                    && $fila['referente_activo']
            )
            ->count();

        $totalInactivos = $avanceCompleto
            ->filter(
                fn ($fila) =>
                    $fila['tiene_referente']
                    && !$fila['referente_activo']
            )
            ->count();

        $listaNominalTotal = (int) $avanceCompleto
            ->sum('lista_nominal');

        $listaNominalCubierta = (int) $avanceCompleto
            ->filter(
                fn ($fila) =>
                    $fila['tiene_referente']
            )
            ->sum('lista_nominal');

        $porcentajeCobertura =
            $totalSecciones > 0
                ? round(
                    (
                        $totalConReferente
                        / $totalSecciones
                    ) * 100,
                    2
                )
                : 0;

        $porcentajeListaNominalCubierta =
            $listaNominalTotal > 0
                ? round(
                    (
                        $listaNominalCubierta
                        / $listaNominalTotal
                    ) * 100,
                    2
                )
                : 0;

        $totales = [
            'secciones' =>
                $totalSecciones,

            'con_referente' =>
                $totalConReferente,

            'sin_referente' =>
                $totalSinReferente,

            'activos' =>
                $totalActivos,

            'inactivos' =>
                $totalInactivos,

            'porcentaje_cobertura' =>
                $porcentajeCobertura,

            'lista_nominal' =>
                $listaNominalTotal,

            'lista_nominal_cubierta' =>
                $listaNominalCubierta,

            'porcentaje_lista_nominal_cubierta' =>
                $porcentajeListaNominalCubierta,
        ];

        $avance = $avanceCompleto;

        if ($estado !== '') {
            $avance = $avance
                ->filter(function ($fila) use ($estado) {
                    return match ($estado) {
                        'con_referente' =>
                            $fila['tiene_referente'],

                        'sin_referente' =>
                            !$fila['tiene_referente'],

                        'activo' =>
                            $fila['tiene_referente']
                            && $fila['referente_activo'],

                        'inactivo' =>
                            $fila['tiene_referente']
                            && !$fila['referente_activo'],

                        default => true,
                    };
                })
                ->values();
        }

        if ($q !== '') {
            $busqueda = mb_strtolower($q);

            $avance = $avance
                ->filter(function ($fila) use ($busqueda) {
                    $campos = [
                        $fila['municipio'],
                        $fila['cve_mun'],
                        $fila['seccion'],
                        $fila['seccion_formateada'],
                        $fila['nombre_completo'],
                        $fila['telefono'],
                        $fila['telefono_alternativo'],
                        $fila['correo'],
                        $fila['cargo'],
                        $fila['organizacion'],
                        $fila['localidad'],
                        $fila['colonia'],
                    ];

                    foreach ($campos as $campo) {
                        if (
                            $campo !== null &&
                            str_contains(
                                mb_strtolower(
                                    (string) $campo
                                ),
                                $busqueda
                            )
                        ) {
                            return true;
                        }
                    }

                    return false;
                })
                ->values();
        }

        $municipiosQuery = DB::table('secciones')
            ->select(
                'cve_mun',
                'municipio'
            )
            ->distinct();

        LocalDistrictAccess::scope(
            $municipiosQuery
        );

        $municipios = $municipiosQuery
            ->orderBy('municipio')
            ->get()
            ->map(function ($municipio) {
                $municipio->cve_mun = str_pad(
                    (string) $municipio->cve_mun,
                    3,
                    '0',
                    STR_PAD_LEFT
                );

                return $municipio;
            });

        $distritosLocalesQuery = DB::table('secciones')
            ->select('distrito_local')
            ->whereNotNull('distrito_local')
            ->distinct();

        LocalDistrictAccess::scope(
            $distritosLocalesQuery
        );

        $distritosLocales = $distritosLocalesQuery
            ->orderBy('distrito_local')
            ->pluck('distrito_local');

        $distritosFederalesQuery = DB::table('secciones')
            ->select('distrito_federal')
            ->whereNotNull('distrito_federal')
            ->distinct();

        LocalDistrictAccess::scope(
            $distritosFederalesQuery
        );

        $distritosFederales = $distritosFederalesQuery
            ->orderBy('distrito_federal')
            ->pluck('distrito_federal');

        $seccionesFiltroQuery = DB::table('secciones')
            ->select(
                'seccion',
                'cve_mun',
                'municipio'
            );

        LocalDistrictAccess::scope(
            $seccionesFiltroQuery
        );

        $seccionesFiltro = $seccionesFiltroQuery
            ->when(
                $cveMun !== '',
                fn ($query) => $query->where(
                    'cve_mun',
                    $cveMun
                )
            )
            ->orderByRaw(
                'CAST(seccion AS UNSIGNED)'
            )
            ->get();

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

        $nombreDistritoFederal =
            $distritoFederal !== ''
                ? (
                    $cabecerasDistritosFederales[
                        (string) $distritoFederal
                    ] ?? ''
                )
                : 'Michoacán';

        $avancePorMunicipio = $avanceCompleto
            ->groupBy(
                fn ($fila) =>
                    $fila['cve_mun']
            )
            ->map(function ($filas) {
                $total = $filas->count();

                $cubiertas = $filas
                    ->filter(
                        fn ($fila) =>
                            $fila['tiene_referente']
                    )
                    ->count();

                $pendientes =
                    $total - $cubiertas;

                $porcentaje =
                    $total > 0
                        ? round(
                            (
                                $cubiertas
                                / $total
                            ) * 100,
                            2
                        )
                        : 0;

                $primera = $filas->first();

                return [
                    'cve_mun' =>
                        $primera['cve_mun'],

                    'municipio' =>
                        $primera['municipio'],

                    'secciones' =>
                        $total,

                    'cubiertas' =>
                        $cubiertas,

                    'pendientes' =>
                        $pendientes,

                    'porcentaje' =>
                        $porcentaje,
                ];
            })
            ->sortBy('municipio')
            ->values();

        $data = compact(
            'avance',
            'avanceCompleto',
            'avancePorMunicipio',
            'totales',
            'municipios',
            'seccionesFiltro',
            'distritosLocales',
            'distritosFederales',
            'cveMun',
            'seccion',
            'distritoLocal',
            'distritoFederal',
            'estado',
            'q',
            'nombreDistritoFederal',
            'puedeVerTodo',
            'distritoLocalRestringido',
            'distritosLocalesAsignados'
        );

        if ($request->expectsJson()) {
            return response()->json($data);
        }

        return view(
            'avance_seccional.index',
            $data
        );
    }

    private function resolveFilters(
        Request $request
    ): array {
        $usuario = $request->user();

        $puedeVerTodo =
            $usuario->hasAnyRole([
                'Admin',
                'SuperAdmin',
            ]);

        $distritosLocalesAsignados =
            LocalDistrictAccess::districts(
                $usuario
            );

        $distritoLocalRestringido =
            $distritosLocalesAsignados !== [];

        $distritoSolicitado = trim(
            (string) $request->query(
                'distrito_local'
            )
        );

        if ($distritoLocalRestringido) {
            $distritoLocal =
                in_array(
                    (int) $distritoSolicitado,
                    $distritosLocalesAsignados,
                    true
                )
                    ? (string) (
                        (int) $distritoSolicitado
                    )
                    : (
                        isset(
                            $distritosLocalesAsignados[0]
                        )
                            ? (string) (
                                (int)
                                $distritosLocalesAsignados[0]
                            )
                            : ''
                    );
        } else {
            $distritoLocal =
                $distritoSolicitado;
        }

        $cveMun = trim(
            (string) $request->query(
                'cve_mun'
            )
        );

        if ($cveMun !== '') {
            $cveMun = str_pad(
                $cveMun,
                3,
                '0',
                STR_PAD_LEFT
            );
        }

        $seccion = trim(
            (string) $request->query(
                'seccion'
            )
        );

        if ($seccion !== '') {
            $seccion = (string) (
                (int) $seccion
            );
        }

        $distritoFederal = trim(
            (string) $request->query(
                'distrito_federal'
            )
        );

        $estado = trim(
            (string) $request->query(
                'estado'
            )
        );

        if (
            !in_array(
                $estado,
                [
                    '',
                    'con_referente',
                    'sin_referente',
                    'activo',
                    'inactivo',
                ],
                true
            )
        ) {
            $estado = '';
        }

        $q = trim(
            (string) $request->query('q')
        );

        return compact(
            'usuario',
            'puedeVerTodo',
            'cveMun',
            'seccion',
            'distritosLocalesAsignados',
            'distritoLocalRestringido',
            'distritoLocal',
            'distritoFederal',
            'estado',
            'q'
        );
    }

    private static function sectionKey(
        string $cveMun,
        $seccion
    ): string {
        return str_pad(
            $cveMun,
            3,
            '0',
            STR_PAD_LEFT
        )
            .'|'.
            (string) (int) $seccion;
    }
}
