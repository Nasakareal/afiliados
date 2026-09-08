<?php

namespace App\Http\Controllers;

use App\Models\ReferenteMunicipal;
use App\Support\LocalDistrictAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AvanceMunicipalController extends Controller
{
    public function index(Request $request)
    {
        $filtros = $this->resolveFilters($request);

        $usuario = $filtros['usuario'];
        $puedeVerTodo = $filtros['puedeVerTodo'];
        $cveMun = $filtros['cveMun'];
        $distritoLocal = $filtros['distritoLocal'];
        $distritoFederal = $filtros['distritoFederal'];
        $estado = $filtros['estado'];
        $q = $filtros['q'];
        $distritoLocalRestringido = $filtros['distritoLocalRestringido'];
        $distritosLocalesAsignados = $filtros['distritosLocalesAsignados'];

        $municipiosQuery = DB::table('secciones')
            ->select(
                'cve_mun',
                'municipio',
                DB::raw(
                    "GROUP_CONCAT(
                        DISTINCT distrito_local
                        ORDER BY distrito_local
                        SEPARATOR ','
                    ) AS distritos_locales"
                ),
                DB::raw(
                    "GROUP_CONCAT(
                        DISTINCT distrito_federal
                        ORDER BY distrito_federal
                        SEPARATOR ','
                    ) AS distritos_federales"
                ),
                DB::raw('COUNT(*) AS total_secciones')
            );

        LocalDistrictAccess::scope($municipiosQuery);

        $municipios = $municipiosQuery
            ->when(
                $cveMun !== '',
                fn ($query) => $query->where(
                    'cve_mun',
                    $cveMun
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
            ->groupBy(
                'cve_mun',
                'municipio'
            )
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

        $cvesMunicipales = $municipios
            ->pluck('cve_mun')
            ->unique()
            ->values();

        $referentesQuery = ReferenteMunicipal::query();

        if ($cvesMunicipales->isNotEmpty()) {
            $referentesQuery->whereIn(
                'cve_mun',
                $cvesMunicipales
            );
        } else {
            $referentesQuery->whereRaw('1 = 0');
        }

        $referentes = $referentesQuery
            ->get()
            ->keyBy(function ($referente) {
                return str_pad(
                    (string) $referente->cve_mun,
                    3,
                    '0',
                    STR_PAD_LEFT
                );
            });

        $avanceCompleto = $municipios
            ->map(function ($municipio) use ($referentes) {
                $cve = str_pad(
                    (string) $municipio->cve_mun,
                    3,
                    '0',
                    STR_PAD_LEFT
                );

                $referente = $referentes->get($cve);

                $tieneReferente =
                    $referente !== null;

                $referenteActivo =
                    $referente
                        ? (bool) $referente->activo
                        : false;

                return [
                    'cve_mun' =>
                        $cve,

                    'municipio' =>
                        $municipio->municipio,

                    'distritos_locales' =>
                        $municipio->distritos_locales,

                    'distritos_federales' =>
                        $municipio->distritos_federales,

                    'secciones' =>
                        (int) $municipio->total_secciones,

                    'tiene_referente' =>
                        $tieneReferente,

                    'referente_activo' =>
                        $referenteActivo,

                    'referente_id' =>
                        $referente?->id,

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

        $totalMunicipios =
            $avanceCompleto->count();

        $totalConReferente =
            $avanceCompleto
                ->filter(
                    fn ($fila) =>
                        $fila['tiene_referente']
                )
                ->count();

        $totalSinReferente =
            $totalMunicipios -
            $totalConReferente;

        $totalActivos =
            $avanceCompleto
                ->filter(
                    fn ($fila) =>
                        $fila['tiene_referente']
                        &&
                        $fila['referente_activo']
                )
                ->count();

        $totalInactivos =
            $avanceCompleto
                ->filter(
                    fn ($fila) =>
                        $fila['tiene_referente']
                        &&
                        !$fila['referente_activo']
                )
                ->count();

        $porcentajeCobertura =
            $totalMunicipios > 0
                ? round(
                    (
                        $totalConReferente
                        /
                        $totalMunicipios
                    ) * 100,
                    2
                )
                : 0;

        $porcentajeActivos =
            $totalMunicipios > 0
                ? round(
                    (
                        $totalActivos
                        /
                        $totalMunicipios
                    ) * 100,
                    2
                )
                : 0;

        $totales = [
            'municipios' =>
                $totalMunicipios,

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

            'porcentaje_activos' =>
                $porcentajeActivos,
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
                            &&
                            $fila['referente_activo'],

                        'inactivo' =>
                            $fila['tiene_referente']
                            &&
                            !$fila['referente_activo'],

                        default =>
                            true,
                    };
                })
                ->values();
        }

        if ($q !== '') {
            $busqueda =
                mb_strtolower($q);

            $avance = $avance
                ->filter(function ($fila) use ($busqueda) {
                    $campos = [
                        $fila['municipio'],
                        $fila['cve_mun'],
                        $fila['nombre_completo'],
                        $fila['telefono'],
                        $fila['telefono_alternativo'],
                        $fila['correo'],
                        $fila['cargo'],
                        $fila['organizacion'],
                        $fila['localidad'],
                        $fila['colonia'],
                        $fila['direccion'],
                    ];

                    foreach ($campos as $campo) {
                        if (
                            $campo !== null
                            &&
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

        $municipiosFiltroQuery =
            DB::table('secciones')
                ->select(
                    'cve_mun',
                    'municipio'
                )
                ->distinct();

        LocalDistrictAccess::scope(
            $municipiosFiltroQuery
        );

        $municipiosFiltro =
            $municipiosFiltroQuery
                ->orderBy('municipio')
                ->get()
                ->map(function ($municipio) {
                    $municipio->cve_mun =
                        str_pad(
                            (string) $municipio->cve_mun,
                            3,
                            '0',
                            STR_PAD_LEFT
                        );

                    return $municipio;
                });

        $distritosLocalesQuery =
            DB::table('secciones')
                ->select(
                    'distrito_local'
                )
                ->whereNotNull(
                    'distrito_local'
                )
                ->distinct();

        LocalDistrictAccess::scope(
            $distritosLocalesQuery
        );

        $distritosLocales =
            $distritosLocalesQuery
                ->orderBy(
                    'distrito_local'
                )
                ->pluck(
                    'distrito_local'
                );

        $distritosFederalesQuery =
            DB::table('secciones')
                ->select(
                    'distrito_federal'
                )
                ->whereNotNull(
                    'distrito_federal'
                )
                ->distinct();

        LocalDistrictAccess::scope(
            $distritosFederalesQuery
        );

        $distritosFederales =
            $distritosFederalesQuery
                ->orderBy(
                    'distrito_federal'
                )
                ->pluck(
                    'distrito_federal'
                );

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

        $municipioPorSeccionQuery =
            DB::table('secciones')
                ->select(
                    'seccion',
                    'cve_mun',
                    'municipio'
                );

        LocalDistrictAccess::scope(
            $municipioPorSeccionQuery
        );

        $municipioPorSeccion =
            $municipioPorSeccionQuery
                ->when(
                    $cveMun !== '',
                    fn ($query) =>
                        $query->where(
                            'cve_mun',
                            $cveMun
                        )
                )
                ->when(
                    $distritoLocal !== '',
                    fn ($query) =>
                        $query->where(
                            'distrito_local',
                            $distritoLocal
                        )
                )
                ->when(
                    $distritoFederal !== '',
                    fn ($query) =>
                        $query->where(
                            'distrito_federal',
                            $distritoFederal
                        )
                )
                ->get()
                ->mapWithKeys(function ($fila) {
                    return [
                        (string) (int) $fila->seccion => [
                            'cve_mun' =>
                                str_pad(
                                    (string) $fila->cve_mun,
                                    3,
                                    '0',
                                    STR_PAD_LEFT
                                ),

                            'municipio' =>
                                $fila->municipio,
                        ],
                    ];
                });

        $data = compact(
            'avance',
            'avanceCompleto',
            'totales',
            'municipiosFiltro',
            'distritosLocales',
            'distritosFederales',
            'municipioPorSeccion',
            'cveMun',
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
            return response()->json(
                $data
            );
        }

        return view(
            'avance_municipal.index',
            $data
        );
    }

    private function resolveFilters(
        Request $request
    ): array {
        $usuario =
            $request->user();

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

        $distritoSolicitado =
            trim(
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

        $cveMun =
            trim(
                (string) $request->query(
                    'cve_mun'
                )
            );

        if ($cveMun !== '') {
            $cveMun =
                str_pad(
                    $cveMun,
                    3,
                    '0',
                    STR_PAD_LEFT
                );
        }

        $distritoFederal =
            trim(
                (string) $request->query(
                    'distrito_federal'
                )
            );

        $estado =
            trim(
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

        $q =
            trim(
                (string) $request->query(
                    'q'
                )
            );

        return compact(
            'usuario',
            'puedeVerTodo',
            'cveMun',
            'distritosLocalesAsignados',
            'distritoLocalRestringido',
            'distritoLocal',
            'distritoFederal',
            'estado',
            'q'
        );
    }
}
