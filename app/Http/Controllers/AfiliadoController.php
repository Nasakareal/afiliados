<?php

namespace App\Http\Controllers;

use App\Models\Afiliado;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AfiliadoController extends Controller
{
    public function index(Request $request)
    {
        $q         = trim((string)$request->query('q'));
        $seccion   = $request->query('seccion');
        $cveMun    = $request->query('cve_mun');
        $municipio = $request->query('municipio');
        $estatus   = $request->query('estatus');
        $capId     = $request->query('capturista_id');

        $full = $this->fullNameField();
        $hasCveMun = Schema::hasColumn('afiliados', 'cve_mun');

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
            ->when($q !== '', function($qb) use ($q, $full){
                $qb->where(function($w) use ($q, $full){
                    if ($full === 'nombre_completo') {
                        $w->where('afiliados.nombre_completo','like',"%{$q}%");
                    } else {
                        // Fallback: nombre + apellidos
                        $w->whereRaw("CONCAT_WS(' ',afiliados.nombre,afiliados.apellido_paterno,afiliados.apellido_materno) like ?", ["%{$q}%"]);
                    }
                    $w->orWhere('afiliados.telefono','like',"%{$q}%")
                      ->orWhere('afiliados.email','like',"%{$q}%");
                });
            })
            ->when($seccion,   fn($qb)=>$qb->where('afiliados.seccion',$seccion))
            ->when($cveMun,    fn($qb)=>$qb->where('afiliados.cve_mun',$cveMun))
            ->when($municipio, fn($qb)=>$qb->where('afiliados.municipio',$municipio))
            ->when($estatus,   fn($qb)=>$qb->where('afiliados.estatus',$estatus))
            ->when($capId,     fn($qb)=>$qb->where('afiliados.capturista_id',$capId))
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
            ->paginate(20)
            ->withQueryString();

        return view('afiliados.index', compact('afiliados','q','seccion','cveMun','municipio','estatus','capId'));
    }

    public function create()
    {
        $municipios = $this->cargarMunicipiosDesdeGeo();

        $secciones = collect();
        if ($municipios->count() > 0) {
            $cve = $municipios->first()->cve_mun;
            $secciones = DB::table('secciones')
                ->where('cve_mun', $cve)
                ->orderBy('seccion')
                ->pluck('seccion');
        }

        // Para asteriscos/required en la vista:
        $rules    = $this->rulesStore();
        $required = $this->requiredMap($rules);
        $fullNameField = $this->fullNameField();

        return view('afiliados.create', compact('municipios','secciones','required','fullNameField'));
    }

    public function store(Request $request)
    {
        $full = $this->fullNameField();

        // Normaliza nombre (sin acentos, MAYÚSCULAS y sin espacios dobles)
        $raw  = $this->squish($request->input($full, ''));
        $name = Str::upper(Str::ascii($raw));
        $request->merge([$full => $name]);

        $rules = $this->rulesStore();
        $data  = $request->validate($rules);

        // Default si no mandan fecha
        if (empty($data['fecha_convencimiento'])) {
            $data['fecha_convencimiento'] = now();
        }

        $data['capturista_id'] = Auth::id();

        $afiliado = Afiliado::create($data);

        return redirect()->route('afiliados.show',$afiliado->id)
            ->with('status','Afiliado creado correctamente.');
    }

    public function show(Afiliado $afiliado)
    {
        $afiliado->load('capturista');

        $seccionInfo = DB::table('secciones')
            ->where('seccion', $afiliado->seccion)
            ->when($afiliado->cve_mun, fn($q)=>$q->where('cve_mun',$afiliado->cve_mun),
                                fn($q)=>$q->where('municipio',$afiliado->municipio))
            ->select('seccion','municipio','cve_mun','distrito_local','distrito_federal','lista_nominal','centroid_lat','centroid_lng')
            ->first();

        return view('afiliados.show', compact('afiliado','seccionInfo'));
    }

    public function edit(Afiliado $afiliado)
    {
        $municipios = $this->cargarMunicipiosDesdeGeo();

        $selCve = $afiliado->cve_mun;
        if (!$selCve) {
            $hit = $municipios->firstWhere('municipio', $afiliado->municipio);
            $selCve = $hit->cve_mun ?? null;
        }

        $secciones = DB::table('secciones')
            ->when($selCve, fn($q)=>$q->where('cve_mun',$selCve),
                           fn($q)=>$q->where('municipio',$afiliado->municipio))
            ->orderBy('seccion')
            ->pluck('seccion');

        // Para asteriscos/required en la vista:
        $rules    = $this->rulesUpdate($afiliado);
        $required = $this->requiredMap($rules);
        $fullNameField = $this->fullNameField();

        return view('afiliados.edit', compact('afiliado','municipios','secciones','required','fullNameField'));
    }

    public function update(Request $request, Afiliado $afiliado)
    {
        $full = $this->fullNameField();

        // Normaliza nombre (sin acentos, MAYÚSCULAS y sin espacios dobles)
        $raw  = $this->squish($request->input($full, $afiliado->{$full} ?? ''));
        $name = Str::upper(Str::ascii($raw));
        $request->merge([$full => $name]);

        $rules = $this->rulesUpdate($afiliado);
        $data  = $request->validate($rules);

        if (empty($data['fecha_convencimiento'])) {
            $data['fecha_convencimiento'] = now();
        }

        $afiliado->update($data);

        return redirect()
            ->route('afiliados.show', $afiliado->id)
            ->with('status', 'Afiliado actualizado correctamente.');
    }

    public function destroy(Afiliado $afiliado)
    {
        $afiliado->delete();

        return redirect()->route('afiliados.index')
            ->with('status','Afiliado eliminado correctamente.');
    }

    /* =========================
     *       REGLAS / UTILS
     * ========================= */

    /** Campo de nombre completo según el esquema real */
    private function fullNameField(): string
    {
        return Schema::hasColumn('afiliados', 'nombre_completo') ? 'nombre_completo' : 'nombre';
    }

    /** Reglas para STORE (creación) */
    private function rulesStore(): array
    {
        $full = $this->fullNameField();

        return [
            $full              => ['required','string','max:120', Rule::unique('afiliados', $full)],

            // NO obligatorios
            'edad'             => ['nullable','integer','min:0','max:120'],
            'sexo'             => ['nullable', Rule::in(['M','F','Otro'])],

            // Obligatorios (según tu último store)
            'telefono'         => ['required','string','max:30'],
            'email'            => ['required','email','max:150'],
            'municipio'        => ['required','string','max:120'],
            'cve_mun'          => ['required','string','size:3'],
            'localidad'        => ['required','string','max:150'],
            'colonia'          => ['required','string','max:150'],
            'seccion'          => ['required','string','max:6'],
            'distrito_federal' => ['required','integer'],
            'distrito_local'   => ['required','integer'],
            'estatus'          => ['required', Rule::in(['pendiente','validado','descartado'])],

            'fecha_convencimiento' => ['nullable','date'],
        ];
    }

    /** Reglas para UPDATE (edición) */
    private function rulesUpdate(Afiliado $afiliado): array
    {
        $full = $this->fullNameField();

        return [
            $full              => ['required','string','max:120', Rule::unique('afiliados', $full)->ignore($afiliado->id, 'id')],

            // NO obligatorios
            'edad'             => ['nullable','integer','min:0','max:120'],
            'sexo'             => ['nullable', Rule::in(['M','F','Otro'])],

            // Obligatorios (igual que store)
            'telefono'         => ['required','string','max:30'],
            'email'            => ['required','email','max:150'],
            'municipio'        => ['required','string','max:120'],
            'cve_mun'          => ['required','string','size:3'],
            'localidad'        => ['required','string','max:150'],
            'colonia'          => ['required','string','max:150'],
            'seccion'          => ['required','string','max:6'],
            'distrito_federal' => ['required','integer'],
            'distrito_local'   => ['required','integer'],
            'estatus'          => ['required', Rule::in(['pendiente','validado','descartado'])],

            'fecha_convencimiento' => ['nullable','date'],
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

    /* =========================
     *      Helpers existentes
     * ========================= */

    private function cargarMunicipiosDesdeGeo()
    {
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

        return DB::table('secciones')
            ->select('cve_mun','municipio')
            ->distinct()
            ->orderBy('municipio')
            ->get()
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
}
