<?php

namespace App\Http\Controllers;

use App\Models\Seccion;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;

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

    /** IMPORTAR EXCEL/CSV DE SECCIONES */
public function importExcel(Request $request)
{
    if (function_exists('set_time_limit')) @set_time_limit(300);
    if (function_exists('ini_set')) @ini_set('memory_limit', '512M');
    \DB::connection()->disableQueryLog();

    $request->validate([
        'archivo' => ['required','file','mimes:xlsx,xls,csv,ods','max:10240'],
    ]);

    try {
        $filePath = $request->file('archivo')->getRealPath();

        $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($filePath);
        if (method_exists($reader, 'setReadDataOnly')) {
            $reader->setReadDataOnly(true);
        }

        $spreadsheet = $reader->load($filePath);
        $sheet = $spreadsheet->getActiveSheet();

        $rows = $sheet->toArray(null, true, true, false);
        $rows = array_values(array_filter($rows, fn($r) => is_array($r)));

        if (!$rows || count($rows) === 0) {
            return back()->with('error','El archivo está vacío o no se pudo leer.');
        }

        [$hdr, $startIndex] = $this->excelHeaderMap($rows);
        if (!isset($hdr['municipio']) || !isset($hdr['seccion'])) {
            return back()->with('error','Faltan columnas requeridas: MUNICIPIO y SECCIÓN.');
        }

        $munIndex = $this->municipioIndex();

        $total=0; $insertados=0; $actualizados=0; $omitidos=0; $errores=[];

        for ($i = $startIndex; $i < count($rows); $i++) {
            $r = $rows[$i];

            $municipioRaw = $r[$hdr['municipio']] ?? null;
            $seccionRaw   = $r[$hdr['seccion']]   ?? null;

            if ($this->squish((string)$municipioRaw) === '' && $this->squish((string)$seccionRaw) === '') {
                continue;
            }

            $municipio = $this->squish((string)$municipioRaw);
            $seccion   = $this->squish((string)$seccionRaw);

            if ($municipio === '' || $seccion === '') {
                $omitidos++; $errores[] = "Fila ".($i+1).": municipio o sección vacío.";
                continue;
            }

            $munKey = $this->normalizeKey($municipio);
            if (!isset($munIndex[$munKey])) {
                $omitidos++; $errores[] = "Fila ".($i+1).": municipio '{$municipio}' no reconocido.";
                continue;
            }

            $cve_mun        = $munIndex[$munKey]['cve_mun'];
            $municipioCanon = $munIndex[$munKey]['municipio'];

            $df = isset($hdr['distrito_federal']) ? $this->toNullableInt($r[$hdr['distrito_federal']] ?? null) : null;
            $dl = isset($hdr['distrito_local'])   ? $this->toNullableInt($r[$hdr['distrito_local']]   ?? null) : null;

            $seccion = preg_replace('/[^\p{N}\p{L}\-]+/u', '', (string)$seccion);

            $total++;
            $payload = [
                'municipio'        => $municipioCanon,
                'cve_mun'          => $cve_mun,
                'distrito_local'   => $dl,
                'distrito_federal' => $df,
                'cve_ent'          => $request->input('cve_ent', '16'),
            ];

            $existente = \App\Models\Seccion::where('cve_mun',$cve_mun)
                           ->where('seccion',(string)$seccion)->first();

            if ($existente) {
                $existente->update($payload);
                $actualizados++;
            } else {
                $payload['seccion'] = (string)$seccion;
                \App\Models\Seccion::create($payload);
                $insertados++;
            }
        }

        $msg = "Procesadas: {$total}. Insertadas: {$insertados}. Actualizadas: {$actualizados}. Omitidas: {$omitidos}.";
        $redir = back()->with('status', $msg);

        if ($errores) {
            $sample = implode("\n", array_slice($errores, 0, 10));
            $redir = $redir->with('error', "Se detectaron ".count($errores)." filas con detalle. Primeras:\n{$sample}");
        }

        return $redir;

    } catch (\Throwable $e) {
        return back()->with('error', 'Error al procesar el archivo: '.$e->getMessage());
    }
}

    private function excelHeaderMap(array $rows): array
    {
        $aliases = [
            'municipio'        => ['municipio','nombre_municipio','nom_municipio'],
            'seccion'          => ['seccion','sección','sec','secc'],
            'distrito_local'   => ['distrito_local','distrito local','dl'],
            'distrito_federal' => ['distrito_federal','distrito federal','df'],
        ];

        $maxScan = min(5, count($rows));
        for ($i = 0; $i < $maxScan; $i++) {
            $row = $rows[$i];
            if (!is_array($row)) { continue; }

            $map = [];
            foreach ($row as $colKey => $val) {
                $label = $this->normalizeKey((string)$val);
                foreach ($aliases as $target => $opts) {
                    foreach ($opts as $opt) {
                        if ($label === $this->normalizeKey($opt) && !isset($map[$target])) {
                            $map[$target] = $colKey;
                        }
                    }
                }
            }

            if (isset($map['municipio']) && isset($map['seccion'])) {
                return [$map, $i + 1];
            }
        }

        $first = reset($rows);
        $keys = array_keys($first ?: []);
        $map = [
            'distrito_federal' => $keys[0] ?? 'A',
            'distrito_local'   => $keys[1] ?? 'B',
            'municipio'        => $keys[2] ?? 'C',
            'seccion'          => $keys[3] ?? 'D',
        ];
        return [$map, 1];
    }

    private function municipioIndex(): array
    {
        $items = $this->cargarMunicipiosDesdeGeo();
        $idx = [];
        foreach ($items as $it) {
            $key = $this->normalizeKey($it->municipio ?? (is_array($it)?($it['municipio']??''): ''));
            if ($key !== '') {
                $idx[$key] = [
                    'cve_mun'  => str_pad((string)($it->cve_mun ?? ''), 3, '0', STR_PAD_LEFT),
                    'municipio'=> $this->squish((string)($it->municipio ?? '')),
                ];
            }
        }
        return $idx;
    }

    private function normalizeKey($s): string
    {
        $s = (string)($s ?? '');
        if (class_exists('\Normalizer')) {
            $s = \Normalizer::normalize($s, \Normalizer::FORM_D) ?: $s;
        }
        $s = preg_replace('/[\p{Mn}]+/u', '', $s);
        $s = preg_replace('/[^A-Z0-9 ]/iu', ' ', $s);
        $s = preg_replace('/\s+/u', ' ', trim($s));
        return strtoupper($s);
    }

    private function toNullableInt($v): ?int
    {
        if ($v === null) return null;
        $v = trim((string)$v);
        if ($v === '') return null;
        if (!is_numeric($v)) return null;
        return (int)$v;
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

    public function lookup(Request $request)
    {
        $seccion = $this->squish((string)$request->input('seccion', ''));
        if ($seccion === '') {
            return response()->json(['message' => 'Sección requerida'], 422);
        }

        $q = DB::table('secciones')->where('seccion', $seccion);

        if ($request->filled('cve_mun')) {
            $cve = str_pad((string)$request->input('cve_mun'), 3, '0', STR_PAD_LEFT);
            $q->where('cve_mun', $cve);
        } elseif ($request->filled('municipio')) {
            $q->where('municipio', $this->squish((string)$request->input('municipio')));
        }

        $row = $q->select('seccion','municipio','cve_mun','distrito_local','distrito_federal')->first();

        if (!$row) {
            return response()->json(['message' => 'No encontrada'], 404);
        }
        return response()->json($row);
    }
}
