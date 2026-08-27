<?php

namespace App\Http\Controllers;

use App\Models\Afiliado;
use App\Services\AfiliadosExcelExporter;
use App\Support\LocalDistrictAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Validation\ValidationException;

class AfiliadoController extends Controller
{
    public const PER_PAGE_OPTIONS = [25, 50, 100, 200, 300, 500];

    public function index(Request $request)
    {
        $q         = trim((string)$request->query('q'));
        $seccion   = $request->query('seccion');
        $cveMun    = $request->query('cve_mun');
        $municipio = $request->query('municipio');
        $estatus   = $request->query('estatus');
        $referente  = trim((string)$request->query('referente'));
        $capturista = trim((string)$request->query('capturista'));

        $full = $this->fullNameField();
        $hasCveMun = Schema::hasColumn('afiliados', 'cve_mun');
        $perPage = $this->perPage($request);
        $clave = $this->normalizeClaveElector($q);

        $afiliados = Afiliado::query()
            ->leftJoin('secciones', function($j) use ($hasCveMun){
                $j->on('secciones.seccion','=','afiliados.seccion');
                if ($hasCveMun) {
                    $j->on('secciones.cve_mun','=','afiliados.cve_mun');
                } else {
                    $j->on('secciones.municipio','=','afiliados.municipio');
                }
            })
            ->leftJoin('users','users.id','=','afiliados.capturista_id')
            ->when($q !== '', function($qb) use ($q, $full, $clave){
                $qb->where(function($w) use ($q, $full, $clave){
                    if ($full === 'nombre_completo') {
                        $w->where('afiliados.nombre_completo','like',"%{$q}%");
                    } else {
                        $w->whereRaw("CONCAT_WS(' ',afiliados.nombre,afiliados.apellido_paterno,afiliados.apellido_materno) like ?", ["%{$q}%"]);
                    }
                    $w->orWhere('afiliados.telefono','like',"%{$q}%")
                      ->orWhere('afiliados.email','like',"%{$q}%")
                      ->orWhere('afiliados.clave_elector','like',"{$clave}%");
                });
            })
            ->when($seccion,   fn($qb)=>$qb->where('afiliados.seccion',$seccion))
            ->when($cveMun,    fn($qb)=>$qb->where('afiliados.cve_mun',$cveMun))
            ->when($municipio, fn($qb)=>$qb->where('afiliados.municipio',$municipio))
            ->when($estatus, fn($qb)=>$qb->where('afiliados.estatus',$estatus))
            ->when($referente !== '', fn($qb)=>$qb->where('afiliados.perfil','like',"%{$referente}%"))
            ->when($capturista !== '', fn($qb)=>$qb->where('users.name','like',"%{$capturista}%"))
            ->select([
                'afiliados.*',
                'secciones.municipio as s_municipio',
                'secciones.cve_mun as s_cve_mun',
                'secciones.lista_nominal as s_lista_nominal',
                'secciones.distrito_local as s_distrito_local',
                'secciones.distrito_federal as s_distrito_federal',
                'secciones.centroid_lat as s_centroid_lat',
                'secciones.centroid_lng as s_centroid_lng',
                'users.name as capturista_nombre',
            ])
            ->orderByDesc('afiliados.id')
            ->simplePaginate($perPage)
            ->withQueryString();

        $perPageOptions = self::PER_PAGE_OPTIONS;
        $tiposVinculo = Afiliado::TIPOS_VINCULO;

        return view('afiliados.index', compact('afiliados','q','seccion','cveMun','municipio','estatus','referente','capturista','perPageOptions','tiposVinculo'));
    }

    public function exportarPagina(Request $request)
    {
        $q = trim((string) $request->query('q'));
        $full = $this->fullNameField();
        $clave = $this->normalizeClaveElector($q);
        $perPage = $this->perPage($request);
        $page = max(1, min((int) $request->query('page', 1), 100000000));

        $afiliados = Afiliado::query()
            ->when($q !== '', function ($query) use ($q, $full, $clave) {
                $query->where(function ($where) use ($q, $full, $clave) {
                    if ($full === 'nombre_completo') {
                        $where->where('nombre_completo', 'like', "%{$q}%");
                    } else {
                        $where->whereRaw("CONCAT_WS(' ',nombre,apellido_paterno,apellido_materno) like ?", ["%{$q}%"]);
                    }

                    $where->orWhere('telefono', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%")
                        ->orWhere('clave_elector', 'like', "{$clave}%");
                });
            })
            ->when($request->query('seccion'), fn ($query, $value) => $query->where('seccion', $value))
            ->when($request->query('cve_mun'), fn ($query, $value) => $query->where('cve_mun', $value))
            ->when($request->query('municipio'), fn ($query, $value) => $query->where('municipio', $value))
            ->when($request->query('estatus'), fn ($query, $value) => $query->where('estatus', $value))
            ->when($request->query('referente'), fn ($query, $value) => $query->where('perfil', 'like', "%{$value}%"))
            ->when($request->query('capturista'), function ($query, $value) {
                $query->whereHas('capturista', function ($user) use ($value) {
                    $user->where('name', 'like', "%{$value}%");
                });
            })
            ->orderByDesc('id')
            ->forPage($page, $perPage)
            ->get();

        if ($afiliados->isEmpty()) {
            return redirect()->route('afiliados.index', $request->except('page'))
                ->with('error', 'La página seleccionada no contiene registros para exportar.');
        }

        return Pdf::loadView('afiliados.pdf_pagina', [
            'afiliados' => $afiliados,
            'paginaListado' => $page,
            'perPage' => $perPage,
            'numeroInicial' => (($page - 1) * $perPage) + 1,
        ])->setPaper('a4', 'landscape')->download(sprintf(
            'personas_convencidas_pagina_%d_%s.pdf',
            $page,
            now('America/Mexico_City')->format('Ymd_His')
        ));
    }

    public function create()
    {
        $municipios = $this->cargarMunicipiosDesdeGeo();

        $secciones = collect();

        if ($municipios->isNotEmpty()) {
            $cve = $municipios->first()->cve_mun;

            $query = DB::table('secciones')
                ->where('cve_mun', $cve);

            LocalDistrictAccess::scope($query);

            $secciones = $query
                ->orderBy('seccion')
                ->pluck('seccion');
        }

        $rules = $this->rulesStore();
        $required = $this->requiredMap($rules);
        $fullNameField = $this->fullNameField();
        $esDistritoLocal = $this->isDistritoLocal();

        return view('afiliados.create', compact(
            'municipios',
            'secciones',
            'required',
            'fullNameField',
            'esDistritoLocal'
        ));
    }

    public function store(Request $request)
    {
        $full = $this->fullNameField();

        $this->normalizeElectoralFields($request);

        $raw = $this->squish($request->input($full, ''));
        $name = Str::upper(Str::ascii($raw));

        $request->merge([$full => $name]);

        $data = $request->validate(
            $this->rulesStore(),
            $this->validationMessages()
        );

        $data = $this->applySectionData($data);

        if (
            array_key_exists('tipo_vinculo', $data) &&
            $data['tipo_vinculo'] !== 'mov'
        ) {
            $data['numero_mov'] = null;
        }

        if (empty($data['fecha_convencimiento'])) {
            $data['fecha_convencimiento'] = now();
        }

        $data['estatus'] = $data['estatus'] ?? 'pendiente';
        $data['capturista_id'] = Auth::id();

        $afiliado = Afiliado::create($data);

        return redirect()
            ->route('afiliados.show', $afiliado->id)
            ->with('status', 'Afiliado creado correctamente.');
    }

    public function show(Afiliado $afiliado)
    {
        $afiliado->load('capturista');

        $seccionInfo = DB::table('secciones')
            ->where('seccion', $afiliado->seccion)
            ->when($afiliado->cve_mun, fn($q)=>$q->where('cve_mun',$afiliado->cve_mun),
                                fn($q)=>$q->where('municipio',$afiliado->municipio));
        LocalDistrictAccess::scope($seccionInfo);
        $seccionInfo = $seccionInfo
            ->select('seccion','municipio','cve_mun','distrito_local','distrito_federal','lista_nominal','centroid_lat','centroid_lng')
            ->first();

        return view('afiliados.show', compact('afiliado','seccionInfo'));
    }

    public function edit(Afiliado $afiliado)
    {
        $municipios = $this->cargarMunicipiosDesdeGeo();

        $selCve = $afiliado->cve_mun;

        if (!$selCve) {
            $municipio = $municipios->firstWhere(
                'municipio',
                $afiliado->municipio
            );

            $selCve = $municipio->cve_mun ?? null;
        }

        $query = DB::table('secciones')
            ->when(
                $selCve,
                fn($query) => $query->where('cve_mun', $selCve),
                fn($query) => $query->where('municipio', $afiliado->municipio)
            );

        LocalDistrictAccess::scope($query);

        $secciones = $query
            ->orderBy('seccion')
            ->pluck('seccion');

        $rules = $this->rulesUpdate($afiliado);
        $required = $this->requiredMap($rules);
        $fullNameField = $this->fullNameField();
        $esDistritoLocal = $this->isDistritoLocal();

        return view('afiliados.edit', compact(
            'afiliado',
            'municipios',
            'secciones',
            'required',
            'fullNameField',
            'esDistritoLocal'
        ));
    }

    public function update(Request $request, Afiliado $afiliado)
    {
        $full = $this->fullNameField();

        $this->normalizeElectoralFields($request);

        $raw = $this->squish(
            $request->input($full, $afiliado->{$full} ?? '')
        );

        $name = Str::upper(Str::ascii($raw));

        $request->merge([$full => $name]);

        $data = $request->validate(
            $this->rulesUpdate($afiliado),
            $this->validationMessages()
        );

        $data = $this->applySectionData($data);

        if (
            array_key_exists('tipo_vinculo', $data) &&
            $data['tipo_vinculo'] !== 'mov'
        ) {
            $data['numero_mov'] = null;
        }

        if (
            array_key_exists('fecha_convencimiento', $data) &&
            empty($data['fecha_convencimiento'])
        ) {
            $data['fecha_convencimiento'] = now();
        }

        $afiliado->update($data);

        return redirect()
            ->route('afiliados.show', $afiliado->id)
            ->with('status', 'Afiliado actualizado correctamente.');
    }

    public function destroy(Afiliado $afiliado)
    {
        try {
            $afiliado->forceDelete();
            return redirect()->route('afiliados.index')
                ->with('status','Afiliado eliminado definitivamente.');
        } catch (\Illuminate\Database\QueryException $e) {
            if ($e->getCode() === '23000') {
                return back()->with('error','No se puede borrar: hay registros relacionados (FK).');
            }
            throw $e;
        }
    }

    /* =========================
     *       REGLAS / UTILS
     * ========================= */

    /** Campo de nombre completo según el esquema real */
    private function fullNameField(): string
    {
        return Schema::hasColumn('afiliados', 'nombre_completo') ? 'nombre_completo' : 'nombre';
    }

    private function rulesStore(): array
    {
        $full = $this->fullNameField();

        if ($this->isDistritoLocal()) {
            return $this->districtLocalRules($full);
        }

        return [
            $full => [
                'required',
                'string',
                'max:120',
                Rule::unique('afiliados', $full),
            ],
            'edad' => ['nullable', 'integer', 'min:0', 'max:120'],
            'sexo' => ['nullable', Rule::in(['M', 'F', 'Otro'])],
            'email' => ['nullable', 'email', 'max:150'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'clave_elector' => [
                'nullable',
                'string',
                'max:30',
                Rule::unique('afiliados', 'clave_elector'),
            ],
            'tipo_vinculo' => [
                'nullable',
                'string',
                Rule::in(array_keys(Afiliado::TIPOS_VINCULO)),
            ],
            'numero_mov' => ['nullable', 'string', 'max:50'],
            'municipio' => ['required', 'string', 'max:120'],
            'cve_mun' => ['required', 'string', 'size:3'],
            'seccion' => ['required', 'string', 'max:6'],
            'distrito_federal' => ['nullable', 'integer'],
            'distrito_local' => ['nullable', 'integer'],
            'perfil' => ['required', 'string', 'max:120'],
            'localidad' => ['nullable', 'string', 'max:150'],
            'colonia' => ['nullable', 'string', 'max:150'],
            'calle' => ['nullable', 'string', 'max:150'],
            'numero_ext' => ['nullable', 'string', 'max:20'],
            'numero_int' => ['nullable', 'string', 'max:20'],
            'cp' => ['nullable', 'string', 'max:10'],
            'estatus' => [
                'required',
                Rule::in(['pendiente', 'validado', 'descartado']),
            ],
            'fecha_convencimiento' => ['nullable', 'date'],
        ];
    }

    private function rulesUpdate(Afiliado $afiliado): array
    {
        $full = $this->fullNameField();

        if ($this->isDistritoLocal()) {
            return $this->districtLocalRules($full);
        }

        return [
            $full => [
                'required',
                'string',
                'max:120',
                Rule::unique('afiliados', $full)
                    ->ignore($afiliado->id, 'id'),
            ],
            'edad' => ['nullable', 'integer', 'min:0', 'max:120'],
            'sexo' => ['nullable', Rule::in(['M', 'F', 'Otro'])],
            'email' => ['nullable', 'email', 'max:150'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'clave_elector' => [
                'nullable',
                'string',
                'max:30',
                Rule::unique('afiliados', 'clave_elector')
                    ->ignore($afiliado->id, 'id'),
            ],
            'tipo_vinculo' => [
                'nullable',
                'string',
                Rule::in(array_keys(Afiliado::TIPOS_VINCULO)),
            ],
            'numero_mov' => ['nullable', 'string', 'max:50'],
            'municipio' => ['required', 'string', 'max:120'],
            'cve_mun' => ['required', 'string', 'size:3'],
            'seccion' => ['required', 'string', 'max:6'],
            'distrito_federal' => ['nullable', 'integer'],
            'distrito_local' => ['nullable', 'integer'],
            'perfil' => ['required', 'string', 'max:120'],
            'localidad' => ['nullable', 'string', 'max:150'],
            'colonia' => ['nullable', 'string', 'max:150'],
            'calle' => ['nullable', 'string', 'max:150'],
            'numero_ext' => ['nullable', 'string', 'max:20'],
            'numero_int' => ['nullable', 'string', 'max:20'],
            'cp' => ['nullable', 'string', 'max:10'],
            'estatus' => [
                'required',
                Rule::in(['pendiente', 'validado', 'descartado']),
            ],
            'fecha_convencimiento' => ['nullable', 'date'],
        ];
    }

    /**
     * Genera mapa de obligatorios a partir de reglas:
     * ['campo' => true/false]
     */
    private function requiredMap(array $rules): array
    {
        $map = [];
        foreach ($rules as $field => $ruleList) {
            $arr = is_array($ruleList) ? $ruleList : explode('|', (string)$ruleList);
            $hasRequired = false;
            foreach ($arr as $r) {
                if (is_string($r) && str_starts_with($r, 'required')) {
                    $hasRequired = true; break;
                }
            }
            $map[$field] = $hasRequired;
        }
        return $map;
    }

    private function perPage(Request $request): int
    {
        $requested = (int) $request->query('per_page', 25);

        return in_array($requested, self::PER_PAGE_OPTIONS, true) ? $requested : 25;
    }

    private function normalizeElectoralFields(Request $request): void
    {
        $clave = $this->normalizeClaveElector((string) $request->input('clave_elector'));
        $request->merge([
            'clave_elector' => $clave !== '' ? $clave : null,
            'tipo_vinculo' => $request->filled('tipo_vinculo') ? $request->input('tipo_vinculo') : null,
        ]);
    }

    private function normalizeClaveElector(string $value): string
    {
        return preg_replace('/\s+/', '', mb_strtoupper(trim($value), 'UTF-8'));
    }

    private function validationMessages(): array
    {
        return [
            'clave_elector.unique' => 'La clave de elector ya pertenece a otro registro.',
            'tipo_vinculo.in' => 'Selecciona únicamente DV, Comité o MOV.',
        ];
    }

    public function exportarExcel(Request $request,AfiliadosExcelExporter $exporter) {
        $q = trim((string) $request->query('q'));
        $seccion = $request->query('seccion');
        $cveMun = $request->query('cve_mun');
        $municipio = $request->query('municipio');
        $estatus = $request->query('estatus');
        $referente = trim((string)$request->query('referente'));
        $capturista = trim((string)$request->query('capturista'));

        $full = $this->fullNameField();
        $hasCveMun = Schema::hasColumn('afiliados', 'cve_mun');
        $clave = $this->normalizeClaveElector($q);

        $query = Afiliado::query()
            ->leftJoin('secciones', function ($join) use ($hasCveMun) {
                $join->on(
                    'secciones.seccion',
                    '=',
                    'afiliados.seccion'
                );

                if ($hasCveMun) {
                    $join->on(
                        'secciones.cve_mun',
                        '=',
                        'afiliados.cve_mun'
                    );
                } else {
                    $join->on(
                        'secciones.municipio',
                        '=',
                        'afiliados.municipio'
                    );
                }
            })
            ->leftJoin(
                'users',
                'users.id',
                '=',
                'afiliados.capturista_id'
            )
            ->when(
                $q !== '',
                function ($query) use ($q, $full, $clave) {
                    $query->where(
                        function ($where) use ($q, $full, $clave) {
                            if ($full === 'nombre_completo') {
                                $where->where(
                                    'afiliados.nombre_completo',
                                    'like',
                                    "%{$q}%"
                                );
                            } else {
                                $where->whereRaw(
                                    "CONCAT_WS(' ', afiliados.nombre, afiliados.apellido_paterno, afiliados.apellido_materno) like ?",
                                    ["%{$q}%"]
                                );
                            }

                            $where
                                ->orWhere(
                                    'afiliados.telefono',
                                    'like',
                                    "%{$q}%"
                                )
                                ->orWhere(
                                    'afiliados.email',
                                    'like',
                                    "%{$q}%"
                                )
                                ->orWhere(
                                    'afiliados.clave_elector',
                                    'like',
                                    "{$clave}%"
                                );
                        }
                    );
                }
            )
            ->when(
                $seccion,
                fn ($query) =>
                    $query->where(
                        'afiliados.seccion',
                        $seccion
                    )
            )
            ->when(
                $cveMun,
                fn ($query) =>
                    $query->where(
                        'afiliados.cve_mun',
                        $cveMun
                    )
            )
            ->when(
                $municipio,
                fn ($query) =>
                    $query->where(
                        'afiliados.municipio',
                        $municipio
                    )
            )
            ->when(
                $estatus,
                fn ($query) =>
                    $query->where(
                        'afiliados.estatus',
                        $estatus
                    )
            )
            ->when(
                $referente !== '',
                fn ($query) =>
                    $query->where(
                        'afiliados.perfil',
                        'like',
                        "%{$referente}%"
                    )
            )
            ->when(
                $capturista !== '',
                fn ($query) =>
                    $query->where(
                        'users.name',
                        'like',
                        "%{$capturista}%"
                    )
            )
            ->select([
                'afiliados.*',
                'secciones.municipio as s_municipio',
                'secciones.cve_mun as s_cve_mun',
                'secciones.lista_nominal as s_lista_nominal',
                'secciones.distrito_local as s_distrito_local',
                'secciones.distrito_federal as s_distrito_federal',
                'users.name as capturista_nombre',
            ]);

        $path = $exporter->create($query);

        return response()->download(
            $path,
            'personas_convencidas_'
            .now('America/Mexico_City')->format('Ymd_His')
            .'.xlsx',
            [
                'Content-Type' =>
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Cache-Control' =>
                    'no-store, no-cache, must-revalidate, max-age=0',
            ]
        )->deleteFileAfterSend(true);
    }

    /* =========================
     *      Helpers existentes
     * ========================= */

    private function cargarMunicipiosDesdeGeo()
    {
        if (LocalDistrictAccess::assigned() !== null) {
            $query = DB::table('secciones')
                ->select('cve_mun', 'municipio')
                ->distinct()
                ->orderBy('municipio');
            LocalDistrictAccess::scope($query);

            return $query->get()->map(function ($row) {
                $row->cve_mun = str_pad((string)$row->cve_mun, 3, '0', STR_PAD_LEFT);
                return $row;
            });
        }

        $posibles = [
            public_path('geo/michoacan.json'),
            public_path('geo/16_michoacan.json'),
            public_path('geo/16/municipios.json'),
            public_path('geo/16/michoacan.json'),
        ];

        foreach ($posibles as $ruta) {
            if (is_file($ruta)) {
                $raw = @file_get_contents($ruta);
                $json = json_decode($raw, true);
                if (isset($json['features']) && is_array($json['features'])) {
                    $items = collect($json['features'])->map(function($f){
                        $p = $f['properties'] ?? [];
                        $cve = $p['CVE_MUN'] ?? $p['CVE_MUNI'] ?? $p['CVE_MPIO'] ?? null;
                        if (!$cve && isset($p['CVEGEO'])) {
                            $cve = substr((string)$p['CVEGEO'], -3);
                        }
                        $nom = $p['NOMGEO'] ?? $p['NOM_MUN'] ?? $p['NOM_MPIO'] ?? $p['NOMMUN'] ?? null;

                        if ($cve && $nom) {
                            return (object)[
                                'cve_mun'  => str_pad($cve, 3, '0', STR_PAD_LEFT),
                                'municipio'=> $nom,
                            ];
                        }
                        return null;
                    })->filter()->unique('cve_mun')->sortBy('municipio')->values();

                    if ($items->count() > 0) return $items;
                }
            }
        }

        $query = DB::table('secciones')
            ->select('cve_mun','municipio')
            ->distinct()
            ->orderBy('municipio');
        LocalDistrictAccess::scope($query);

        return $query->get()
            ->map(function($r){
                $r->cve_mun = str_pad((string)$r->cve_mun, 3, '0', STR_PAD_LEFT);
                return $r;
            });
    }

    private function squish($value): string
    {
        if (method_exists(Str::class, 'squish')) {
            return Str::squish($value);
        }
        return preg_replace('/\s+/u', ' ', trim((string)$value));
    }

    private function isDistritoLocal(): bool
    {
        return Auth::user()?->hasRole('Distrito Local') ?? false;
    }

    private function districtLocalRules(string $full): array
    {
        return [
            $full => ['required', 'string', 'max:120'],
            'sexo' => [
                'required',
                Rule::in(['M', 'F', 'Otro']),
            ],
            'telefono' => [
                'required',
                'string',
                'max:30',
            ],
            'municipio' => [
                'required',
                'string',
                'max:120',
            ],
            'cve_mun' => [
                'required',
                'string',
                'size:3',
            ],
            'seccion' => [
                'required',
                'string',
                'max:6',
            ],
            'distrito_local' => [
                'nullable',
                'integer',
            ],
            'distrito_federal' => [
                'nullable',
                'integer',
            ],
            'perfil' => [
                'nullable',
                'string',
                'max:120',
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
            'calle' => [
                'nullable',
                'string',
                'max:150',
            ],
            'numero_ext' => [
                'nullable',
                'string',
                'max:20',
            ],
            'numero_int' => [
                'nullable',
                'string',
                'max:20',
            ],
            'cp' => [
                'nullable',
                'string',
                'max:10',
            ],
        ];
    }

    private function applySectionData(array $data): array
    {
        if (
            !LocalDistrictAccess::sectionIsAllowed(
                (string) $data['seccion']
            )
        ) {
            abort(403, 'No tienes acceso a esa sección.');
        }

        $seccion = DB::table('secciones')
            ->where('seccion', $data['seccion'])
            ->where('cve_mun', $data['cve_mun'])
            ->select(
                'municipio',
                'cve_mun',
                'distrito_local',
                'distrito_federal'
            )
            ->first();

        if (!$seccion) {
            throw ValidationException::withMessages([
                'seccion' => 'La sección no corresponde al municipio seleccionado.',
            ]);
        }

        $data['municipio'] = $seccion->municipio;
        $data['cve_mun'] = $seccion->cve_mun;
        $data['distrito_local'] = $seccion->distrito_local;
        $data['distrito_federal'] = $seccion->distrito_federal;

        return LocalDistrictAccess::force($data);
    }
}
