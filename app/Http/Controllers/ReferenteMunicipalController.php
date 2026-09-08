<?php

namespace App\Http\Controllers;

use App\Models\ReferenteMunicipal;
use App\Support\LocalDistrictAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;

class ReferenteMunicipalController extends Controller
{
    public const PER_PAGE_OPTIONS = [25, 50, 100, 200, 300, 500];

    public function index(Request $request)
    {
        $q = trim((string) $request->query('q'));
        $cveMun = $request->query('cve_mun');
        $activo = $request->query('activo');
        $perPage = $this->perPage($request);

        $referentes = ReferenteMunicipal::query();

        $this->scopeAcceso($referentes);

        $referentes
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($where) use ($q) {
                    $where->where('nombre_completo', 'like', "%{$q}%")
                        ->orWhere('telefono', 'like', "%{$q}%")
                        ->orWhere('telefono_alternativo', 'like', "%{$q}%")
                        ->orWhere('correo', 'like', "%{$q}%")
                        ->orWhere('municipio', 'like', "%{$q}%")
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
            ->when($activo !== null && $activo !== '', function ($query) use ($activo) {
                $query->where('activo', (bool) $activo);
            });

        $referentes = $referentes
            ->orderBy('municipio')
            ->orderBy('nombre_completo')
            ->paginate($perPage)
            ->withQueryString();

        $municipios = $this->cargarTodosMunicipios();

        $perPageOptions = self::PER_PAGE_OPTIONS;

        return view('referentes_municipales.index', compact(
            'referentes',
            'municipios',
            'q',
            'cveMun',
            'activo',
            'perPageOptions'
        ));
    }

    public function create()
    {
        $municipios = $this->cargarMunicipios();

        return view('referentes_municipales.create', compact(
            'municipios'
        ));
    }

    public function store(Request $request)
    {
        $this->normalizar($request);

        $data = $request->validate(
            $this->rules(),
            $this->messages()
        );

        $municipio = $this->obtenerMunicipioPermitido(
            $data['cve_mun']
        );

        if (!$municipio) {
            throw ValidationException::withMessages([
                'cve_mun' => 'El municipio seleccionado no existe o no tienes acceso a él.',
            ]);
        }

        $data['cve_mun'] = str_pad(
            (string) $municipio->cve_mun,
            3,
            '0',
            STR_PAD_LEFT
        );

        $data['municipio'] = $municipio->municipio;

        $referente = ReferenteMunicipal::create($data);

        return redirect()
            ->route('referentes_municipales.show', $referente)
            ->with('status', 'Referente municipal creado correctamente.');
    }

    public function show(ReferenteMunicipal $referenteMunicipal)
    {
        $this->verificarAcceso($referenteMunicipal);

        return view(
            'referentes_municipales.show',
            [
                'referente' => $referenteMunicipal,
            ]
        );
    }

    public function edit(ReferenteMunicipal $referenteMunicipal)
    {
        $this->verificarAcceso($referenteMunicipal);

        $municipios = $this->cargarMunicipios($referenteMunicipal);

        return view(
            'referentes_municipales.edit',
            [
                'referente' => $referenteMunicipal,
                'municipios' => $municipios,
            ]
        );
    }

    public function update(
        Request $request,
        ReferenteMunicipal $referenteMunicipal
    ) {
        $this->verificarAcceso($referenteMunicipal);

        $this->normalizar($request);

        $data = $request->validate(
            $this->rules($referenteMunicipal),
            $this->messages()
        );

        $municipio = $this->obtenerMunicipioPermitido(
            $data['cve_mun']
        );

        if (!$municipio) {
            throw ValidationException::withMessages([
                'cve_mun' => 'El municipio seleccionado no existe o no tienes acceso a él.',
            ]);
        }

        $data['cve_mun'] = str_pad(
            (string) $municipio->cve_mun,
            3,
            '0',
            STR_PAD_LEFT
        );

        $data['municipio'] = $municipio->municipio;

        $referenteMunicipal->update($data);

        return redirect()
            ->route(
                'referentes_municipales.show',
                $referenteMunicipal
            )
            ->with(
                'status',
                'Referente municipal actualizado correctamente.'
            );
    }

    public function destroy(
        ReferenteMunicipal $referenteMunicipal
    ) {
        $this->verificarAcceso($referenteMunicipal);

        try {
            $referenteMunicipal->delete();

            return redirect()
                ->route('referentes_municipales.index')
                ->with(
                    'status',
                    'Referente municipal eliminado correctamente.'
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

    private function rules(?ReferenteMunicipal $actual = null): array
    {
        $municipioUnique = Rule::unique(
            'referentes_municipales',
            'cve_mun'
        );

        if ($actual) {
            $municipioUnique->ignore($actual->id);
        }

        return [
            'cve_mun' => [
                'required',
                'string',
                'max:3',
                $municipioUnique,
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

    private function normalizar(Request $request): void
    {
        $nombre = trim(
            preg_replace(
                '/\s+/u',
                ' ',
                (string) $request->input('nombre_completo')
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
            'telefono' => $request->filled('telefono')
                ? trim((string) $request->input('telefono'))
                : null,
            'telefono_alternativo' =>
                $request->filled('telefono_alternativo')
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
            'whatsapp' => $request->boolean('whatsapp'),
            'activo' => $request->boolean('activo'),
        ]);
    }

    private function cargarMunicipios(?ReferenteMunicipal $actual = null)
    {
        $query = DB::table('secciones')
            ->select(
                'cve_mun',
                'municipio'
            )
            ->distinct();

        LocalDistrictAccess::scope($query);

        $ocupados = ReferenteMunicipal::query()
            ->when(
                $actual,
                fn ($q) => $q->where('id', '!=', $actual->id)
            )
            ->pluck('cve_mun')
            ->map(
                fn ($cve) => str_pad(
                    (string) $cve,
                    3,
                    '0',
                    STR_PAD_LEFT
                )
            )
            ->all();

        if (!empty($ocupados)) {
            $query->whereNotIn('cve_mun', $ocupados);
        }

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

    private function cargarTodosMunicipios()
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

    private function obtenerMunicipioPermitido(
        string $cveMun
    ) {
        $cveMun = str_pad(
            $cveMun,
            3,
            '0',
            STR_PAD_LEFT
        );

        $query = DB::table('secciones')
            ->where('cve_mun', $cveMun);

        LocalDistrictAccess::scope($query);

        return $query
            ->select(
                'cve_mun',
                'municipio'
            )
            ->first();
    }

    private function verificarAcceso(
        ReferenteMunicipal $referente
    ): void {
        if (!LocalDistrictAccess::restricted()) {
            return;
        }

        $municipio = $this->obtenerMunicipioPermitido(
            $referente->cve_mun
        );

        if (!$municipio) {
            abort(403);
        }
    }

    private function scopeAcceso($query): void
    {
        if (!LocalDistrictAccess::restricted()) {
            return;
        }

        $permitidos = DB::table('secciones')
            ->select('cve_mun')
            ->distinct();

        LocalDistrictAccess::scope($permitidos);

        $query->whereIn(
            'cve_mun',
            $permitidos
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

    private function messages(): array
    {
        return [
            'cve_mun.required' =>
                'Selecciona un municipio.',

            'cve_mun.unique' =>
                'Este municipio ya tiene un referente municipal registrado.',

            'nombre_completo.required' =>
                'Captura el nombre completo del referente.',

            'correo.email' =>
                'El correo electrónico no tiene un formato válido.',
        ];
    }
}
