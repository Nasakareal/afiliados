<?php

namespace App\Http\Controllers;

use App\Models\Seccion;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SeccionController extends Controller
{
    /** LISTADO con filtros */
    public function index(Request $request)
    {
        $q         = trim((string)$request->query('q', ''));
        $cveMun    = $request->query('cve_mun');
        $municipio = $request->query('municipio');

        $secciones = Seccion::query()
            ->when($q !== '', function($qb) use ($q){
                $qb->where(function($w) use ($q){
                    $w->where('seccion', 'like', "%{$q}%")
                      ->orWhere('municipio','like', "%{$q}%")
                      ->orWhere('cve_mun', 'like', "%{$q}%")
                      ->orWhere('distrito_local', 'like', "%{$q}%")
                      ->orWhere('distrito_federal', 'like', "%{$q}%");
                });
            })
            ->when($cveMun,    fn($qb)=>$qb->where('cve_mun',$cveMun))
            ->when($municipio, fn($qb)=>$qb->where('municipio',$municipio))
            ->orderBy('municipio')
            ->orderByRaw('CAST(seccion AS UNSIGNED), seccion')
            ->paginate(25)
            ->withQueryString();

        $municipios = $this->cargarMunicipiosDesdeGeo();
        $required   = $this->requiredMap($this->rulesStore());

        return view('secciones.index', compact('secciones','q','cveMun','municipio','municipios','required'));
    }

    /** FORM CREAR */
    public function create()
    {
        $municipios = $this->cargarMunicipiosDesdeGeo();
        $required   = $this->requiredMap($this->rulesStore());

        return view('secciones.create', compact('municipios','required'));
    }

    /** GUARDAR */
    public function store(Request $request)
    {
        if ($request->filled('municipio')) {
            $request->merge(['municipio' => $this->squish($request->input('municipio'))]);
        }

        if (!$request->filled('cve_ent')) {
            $request->merge(['cve_ent' => '16']);
        }

        $data = $request->validate($this->rulesStore($request));

        $seccion = Seccion::create($data);

        return redirect()
            ->route('secciones.show', $seccion->id ?? $seccion)
            ->with('status', 'Sección creada correctamente.');
    }

    /** VER */
    public function show(Seccion $seccion)
    {
        return view('secciones.show', compact('seccion'));
    }

    /** FORM EDITAR */
    public function edit(Seccion $seccion)
    {
        $municipios = $this->cargarMunicipiosDesdeGeo();
        $required   = $this->requiredMap($this->rulesUpdate($seccion));

        return view('secciones.edit', compact('seccion','municipios','required'));
    }

    /** ACTUALIZAR */
    public function update(Request $request, Seccion $seccion)
    {
        if ($request->filled('municipio')) {
            $request->merge(['municipio' => $this->squish($request->input('municipio'))]);
        }

        if (!$request->filled('cve_ent')) {
            $request->merge(['cve_ent' => '16']);
        }

        $data = $request->validate($this->rulesUpdate($seccion, $request));

        $seccion->update($data);

        return redirect()
            ->route('secciones.show', $seccion->id ?? $seccion)
            ->with('status', 'Sección actualizada correctamente.');
    }

    /** ELIMINAR */
    public function destroy(Seccion $seccion)
    {
        try {
            $seccion->delete();
            return redirect()->route('secciones.index')->with('status','Sección eliminada.');
        } catch (\Illuminate\Database\QueryException $e) {
            if ($e->getCode() === '23000') {
                return back()->with('error','No se puede borrar: hay registros relacionados (FK).');
            }
            throw $e;
        }
    }

    /* =========================
     *        VALIDACIONES
     * ========================= */

    private function rulesStore(?Request $request = null): array
    {
        $req = $request ?: request();

        return [
            'municipio'        => ['required','string','max:120'],
            'cve_mun'          => ['required','string','size:3'],
            'seccion'          => [
                'required','string','max:6',
                Rule::unique('secciones','seccion')->where(function($q) use ($req){
                    return $q->where('cve_mun', $req->input('cve_mun'));
                }),
            ],
            'distrito_local'   => ['nullable','integer','min:1'],
            'distrito_federal' => ['nullable','integer','min:1'],
            'lista_nominal'    => ['nullable','integer','min:0'],
            'centroid_lat'     => ['nullable','numeric','between:-90,90'],
            'centroid_lng'     => ['nullable','numeric','between:-180,180'],
            'cve_ent'          => ['nullable','string','size:2'],
        ];
    }

    private function rulesUpdate(Seccion $seccion, ?Request $request = null): array
    {
        $req = $request ?: request();
        $ignoreId = $seccion->id ?? $seccion->getKey();

        return [
            'municipio'        => ['required','string','max:120'],
            'cve_mun'          => ['required','string','size:3'],
            'seccion'          => [
                'required','string','max:6',
                Rule::unique('secciones','seccion')
                    ->ignore($ignoreId, 'id')
                    ->where(function($q) use ($req){
                        return $q->where('cve_mun', $req->input('cve_mun'));
                    }),
            ],
            'distrito_local'   => ['nullable','integer','min:1'],
            'distrito_federal' => ['nullable','integer','min:1'],
            'lista_nominal'    => ['nullable','integer','min:0'],
            'centroid_lat'     => ['nullable','numeric','between:-90,90'],
            'centroid_lng'     => ['nullable','numeric','between:-180,180'],
            'cve_ent'          => ['nullable','string','size:2'],
        ];
    }

    private function requiredMap(array $rules): array
    {
        $map = [];
        foreach ($rules as $field => $ruleList) {
            $arr = is_array($ruleList) ? $ruleList : explode('|', (string)$ruleList);
            $map[$field] = collect($arr)->contains(fn($r)=> is_string($r) && str_starts_with($r, 'required'));
        }
        return $map;
    }

    /* =========================
     *          HELPERS
     * ========================= */

    private function squish($value): string
    {
        if (method_exists(Str::class, 'squish')) {
            return Str::squish($value);
        }
        return preg_replace('/\s+/u', ' ', trim((string)$value));
    }

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
}
