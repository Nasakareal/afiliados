<?php

namespace App\Http\Controllers;

use App\Models\ReferenteSeccional;
use App\Support\LocalDistrictAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;

class ReferenteSeccionalController extends Controller
{
    public const PER_PAGE_OPTIONS = [25, 50, 100, 200, 300, 500];

    public function index(Request $request)
    {
        $q = trim((string) $request->query('q'));
        $cveMun = $request->query('cve_mun');
        $seccion = $request->query('seccion');
        $distritoLocal = $request->query('distrito_local');
        $distritoFederal = $request->query('distrito_federal');
        $activo = $request->query('activo');

        $perPage = $this->perPage($request);

        $referentes = ReferenteSeccional::query();

        $this->scopeAcceso($referentes);

        $referentes
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($where) use ($q) {
                    $where
                        ->where('nombre_completo', 'like', "%{$q}%")
                        ->orWhere('telefono', 'like', "%{$q}%")
                        ->orWhere('telefono_alternativo', 'like', "%{$q}%")
                        ->orWhere('correo', 'like', "%{$q}%")
                        ->orWhere('municipio', 'like', "%{$q}%")
                        ->orWhere('seccion', 'like', "%{$q}%")
                        ->orWhere('localidad', 'like', "%{$q}%")
                        ->orWhere('colonia', 'like', "%{$q}%");
                });
            })
            ->when($cveMun, function ($query) use ($cveMun) {
                $query->where(
                    'cve_mun',
                    str_pad((string) $cveMun, 3, '0', STR_PAD_LEFT)
                );
            })
            ->when(
                $seccion,
                fn ($query) => $query->where('seccion', $seccion)
            )
            ->when(
                $distritoLocal,
                fn ($query) => $query->where(
                    'distrito_local',
                    $distritoLocal
                )
            )
            ->when(
                $distritoFederal,
                fn ($query) => $query->where(
                    'distrito_federal',
                    $distritoFederal
                )
            )
            ->when(
                $activo !== null && $activo !== '',
                fn ($query) => $query->where(
                    'activo',
                    (bool) $activo
                )
            );

        $referentes = $referentes
            ->orderBy('municipio')
            ->orderByRaw('CAST(seccion AS UNSIGNED)')
            ->orderBy('nombre_completo')
            ->paginate($perPage)
            ->withQueryString();

        $municipios = $this->cargarMunicipios();
        $distritosLocales = $this->cargarDistritosLocales();
        $distritosFederales = $this->cargarDistritosFederales();

        $perPageOptions = self::PER_PAGE_OPTIONS;

        return view('referentes_seccionales.index', compact(
            'referentes',
            'municipios',
            'distritosLocales',
            'distritosFederales',
            'q',
            'cveMun',
            'seccion',
            'distritoLocal',
            'distritoFederal',
            'activo',
            'perPageOptions'
        ));
    }

    public function create()
    {
        $municipios = $this->cargarMunicipios();

        $query = DB::table('secciones')
            ->whereNotExists(function ($subquery) {
                $subquery
                    ->select(DB::raw(1))
                    ->from('referentes_seccionales as rs')
                    ->whereColumn(
                        'rs.cve_mun',
                        'secciones.cve_mun'
                    )
                    ->whereColumn(
                        'rs.seccion',
                        'secciones.seccion'
                    );
            });

        LocalDistrictAccess::scope($query);

        $secciones = $query
            ->select(
                'seccion',
                'cve_mun',
                'municipio',
                'distrito_local',
                'distrito_federal'
            )
            ->orderBy('municipio')
            ->orderByRaw('CAST(seccion AS UNSIGNED)')
            ->get();

        return view('referentes_seccionales.create', compact(
            'municipios',
            'secciones'
        ));
    }

    public function store(Request $request)
    {
        $this->normalizar($request);

        $data = $request->validate(
            $this->rules(),
            $this->messages()
        );

        $data = $this->aplicarDatosSeccion($data);

        $referente = ReferenteSeccional::create($data);

        return redirect()
            ->route(
                'referentes_seccionales.show',
                $referente
            )
            ->with(
                'status',
                'Referente seccional creado correctamente.'
            );
    }

    public function show(
        ReferenteSeccional $referenteSeccional
    ) {
        $this->verificarAcceso($referenteSeccional);

        $seccionInfo = DB::table('secciones')
            ->where(
                'seccion',
                $referenteSeccional->seccion
            )
            ->where(
                'cve_mun',
                $referenteSeccional->cve_mun
            );

        LocalDistrictAccess::scope($seccionInfo);

        $seccionInfo = $seccionInfo
            ->select(
                'seccion',
                'municipio',
                'cve_mun',
                'distrito_local',
                'distrito_federal',
                'lista_nominal',
                'centroid_lat',
                'centroid_lng'
            )
            ->first();

        return view(
            'referentes_seccionales.show',
            [
                'referente' => $referenteSeccional,
                'seccionInfo' => $seccionInfo,
            ]
        );
    }

    public function edit(ReferenteSeccional $referenteSeccional)
    {
        $this->verificarAcceso($referenteSeccional);

        $municipios = $this->cargarMunicipios();

        $query = DB::table('secciones')
            ->where(
                'cve_mun',
                $referenteSeccional->cve_mun
            )
            ->whereNotExists(function ($subquery) use ($referenteSeccional) {
                $subquery
                    ->select(DB::raw(1))
                    ->from('referentes_seccionales as rs')
                    ->whereColumn(
                        'rs.cve_mun',
                        'secciones.cve_mun'
                    )
                    ->whereColumn(
                        'rs.seccion',
                        'secciones.seccion'
                    )
                    ->where(
                        'rs.id',
                        '!=',
                        $referenteSeccional->id
                    );
            });

        LocalDistrictAccess::scope($query);

        $secciones = $query
            ->select(
                'seccion',
                'cve_mun',
                'municipio',
                'distrito_local',
                'distrito_federal'
            )
            ->orderByRaw('CAST(seccion AS UNSIGNED)')
            ->get();

        return view(
            'referentes_seccionales.edit',
            [
                'referente' => $referenteSeccional,
                'municipios' => $municipios,
                'secciones' => $secciones,
            ]
        );
    }

    public function update(Request $request,ReferenteSeccional $referenteSeccional)
    {
        $this->verificarAcceso($referenteSeccional);

        $this->normalizar($request);

        $data = $request->validate(
            $this->rules($referenteSeccional),
            $this->messages()
        );

        $data = $this->aplicarDatosSeccion($data);

        $referenteSeccional->update($data);

        return redirect()
            ->route(
                'referentes_seccionales.show',
                $referenteSeccional
            )
            ->with(
                'status',
                'Referente seccional actualizado correctamente.'
            );
    }

    public function destroy(
        ReferenteSeccional $referenteSeccional
    ) {
        $this->verificarAcceso($referenteSeccional);

        try {
            $referenteSeccional->delete();

            return redirect()
                ->route('referentes_seccionales.index')
                ->with(
                    'status',
                    'Referente seccional eliminado correctamente.'
                );
        } catch (\Illuminate\Database\QueryException $e) {
            if ($e->getCode() === '23000') {
                return back()->with(
                    'error',
                    'No se puede eliminar porque existen registros relacionados.'
                );
            }

            throw $e;
        }
    }

    private function rules(?ReferenteSeccional $actual = null): array
    {
        $seccionUnique = Rule::unique(
            'referentes_seccionales',
            'seccion'
        )->where(function ($query) {
            return $query->where(
                'cve_mun',
                request()->input('cve_mun')
            );
        });

        if ($actual) {
            $seccionUnique->ignore($actual->id);
        }

        return [
            'cve_mun' => [
                'required',
                'string',
                'max:3',
            ],

            'seccion' => [
                'required',
                'string',
                'max:6',
                $seccionUnique,
            ],

            'nombre_completo' => [
                'required',
                'string',
                'max:150',
            ],

            'telefono' => [
                'nullable',
                'string',
                'max:20',
            ],

            'telefono_alternativo' => [
                'nullable',
                'string',
                'max:20',
            ],

            'correo' => [
                'nullable',
                'email',
                'max:150',
            ],

            'cargo' => [
                'nullable',
                'string',
                'max:150',
            ],

            'organizacion' => [
                'nullable',
                'string',
                'max:150',
            ],

            'localidad' => [
                'nullable',
                'string',
                'max:150',
            ],

            'colonia' => [
                'nullable',
                'string',
                'max:150',
            ],

            'direccion' => [
                'nullable',
                'string',
                'max:500',
            ],

            'whatsapp' => [
                'nullable',
                'boolean',
            ],

            'activo' => [
                'nullable',
                'boolean',
            ],

            'observaciones' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ];
    }

    private function messages(): array
    {
        return [
            'cve_mun.required' =>
                'Selecciona un municipio.',
            'seccion.required' =>
                'Selecciona una sección electoral.',
            'nombre_completo.required' =>
                'Captura el nombre completo del referente.',
            'correo.email' =>
                'El correo electrónico no tiene un formato válido.',
            'seccion.unique' =>
                'Esta sección ya tiene un referente seccional registrado.',
        ];
    }

    private function normalizar(
        Request $request
    ): void {
        $nombre = trim(
            preg_replace(
                '/\s+/u',
                ' ',
                (string) $request->input(
                    'nombre_completo'
                )
            )
        );

        $request->merge([
            'nombre_completo' => Str::upper(
                Str::ascii($nombre)
            ),

            'cve_mun' => $request->filled('cve_mun')
                ? str_pad(
                    (string) $request->input('cve_mun'),
                    3,
                    '0',
                    STR_PAD_LEFT
                )
                : null,

            'seccion' => $request->filled('seccion')
                ? trim(
                    (string) $request->input('seccion')
                )
                : null,

            'telefono' => $request->filled('telefono')
                ? trim(
                    (string) $request->input('telefono')
                )
                : null,

            'telefono_alternativo' =>
                $request->filled(
                    'telefono_alternativo'
                )
                    ? trim(
                        (string) $request->input(
                            'telefono_alternativo'
                        )
                    )
                    : null,

            'correo' => $request->filled('correo')
                ? mb_strtolower(
                    trim(
                        (string) $request->input('correo')
                    )
                )
                : null,

            'whatsapp' => $request->boolean(
                'whatsapp'
            ),

            'activo' => $request->boolean(
                'activo'
            ),
        ]);
    }

    private function aplicarDatosSeccion(
        array $data
    ): array {
        if (
            !LocalDistrictAccess::sectionIsAllowed(
                (string) $data['seccion']
            )
        ) {
            abort(
                403,
                'No tienes acceso a esa sección.'
            );
        }

        $query = DB::table('secciones')
            ->where(
                'seccion',
                $data['seccion']
            )
            ->where(
                'cve_mun',
                $data['cve_mun']
            );

        LocalDistrictAccess::scope($query);

        $seccion = $query
            ->select(
                'seccion',
                'municipio',
                'cve_mun',
                'distrito_local',
                'distrito_federal'
            )
            ->first();

        if (!$seccion) {
            throw ValidationException::withMessages([
                'seccion' =>
                    'La sección seleccionada no existe para ese municipio.',
            ]);
        }

        $data['cve_mun'] = str_pad(
            (string) $seccion->cve_mun,
            3,
            '0',
            STR_PAD_LEFT
        );

        $data['municipio'] =
            $seccion->municipio;

        $data['seccion'] =
            $seccion->seccion;

        $data['distrito_local'] =
            $seccion->distrito_local;

        $data['distrito_federal'] =
            $seccion->distrito_federal;

        return $data;
    }

    private function cargarMunicipios()
    {
        $query = DB::table('secciones')
            ->select(
                'cve_mun',
                'municipio'
            )
            ->distinct();

        LocalDistrictAccess::scope($query);

        return $query
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
    }

    private function cargarDistritosLocales()
    {
        $query = DB::table('secciones')
            ->select('distrito_local')
            ->whereNotNull('distrito_local')
            ->distinct();

        LocalDistrictAccess::scope($query);

        return $query
            ->orderBy('distrito_local')
            ->pluck('distrito_local');
    }

    private function cargarDistritosFederales()
    {
        $query = DB::table('secciones')
            ->select('distrito_federal')
            ->whereNotNull('distrito_federal')
            ->distinct();

        LocalDistrictAccess::scope($query);

        return $query
            ->orderBy('distrito_federal')
            ->pluck('distrito_federal');
    }

    private function verificarAcceso(
        ReferenteSeccional $referente
    ): void {
        if (!LocalDistrictAccess::restricted()) {
            return;
        }

        if (
            !LocalDistrictAccess::sectionIsAllowed(
                (string) $referente->seccion
            )
        ) {
            abort(403);
        }

        $query = DB::table('secciones')
            ->where(
                'seccion',
                $referente->seccion
            )
            ->where(
                'cve_mun',
                $referente->cve_mun
            );

        LocalDistrictAccess::scope($query);

        if (!$query->exists()) {
            abort(403);
        }
    }

    private function scopeAcceso(
        $query
    ): void {
        if (!LocalDistrictAccess::restricted()) {
            return;
        }

        $seccionesPermitidas = DB::table(
            'secciones'
        )
            ->select('seccion')
            ->distinct();

        LocalDistrictAccess::scope(
            $seccionesPermitidas
        );

        $query->whereIn(
            'seccion',
            $seccionesPermitidas
        );
    }

    private function perPage(
        Request $request
    ): int {
        $requested = (int) $request->query(
            'per_page',
            25
        );

        return in_array(
            $requested,
            self::PER_PAGE_OPTIONS,
            true
        )
            ? $requested
            : 25;
    }
}
