<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Afiliado;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use App\Http\Resources\AfiliadoResource;
use App\Support\LocalDistrictAccess;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AfiliadoApiController extends Controller
{
    private const PER_PAGE_OPTIONS = [25, 50, 100, 200, 300, 500];

    public function index(Request $request)
    {
        $q         = trim((string)$request->query('q'));
        $seccion   = $request->query('seccion');
        $cveMun    = $request->query('cve_mun');
        $municipio = $request->query('municipio');
        $estatus   = $request->query('estatus');
        $capId     = $request->query('capturista_id');
        $requested = (int) $request->query('per_page', 25);
        $perPage = in_array($requested, self::PER_PAGE_OPTIONS, true) ? $requested : 25;
        $clave = preg_replace('/\s+/', '', mb_strtoupper($q, 'UTF-8'));

        $rows = Afiliado::query()
            ->when($q !== '', function($qb) use ($q, $clave){
                $qb->where(function($w) use ($q, $clave){
                    $w->whereRaw("CONCAT_WS(' ',nombre,apellido_paterno,apellido_materno) like ?", ["%{$q}%"])
                      ->orWhere('telefono','like',"%{$q}%")
                      ->orWhere('email','like',"%{$q}%")
                      ->orWhere('clave_elector','like',"{$clave}%");
                });
            })
            ->when($seccion,   fn($qb)=>$qb->where('seccion',$seccion))
            ->when($cveMun,    fn($qb)=>$qb->where('cve_mun',$cveMun))
            ->when($municipio, fn($qb)=>$qb->where('municipio',$municipio))
            ->when($estatus,   fn($qb)=>$qb->where('estatus',$estatus))
            ->when($capId,     fn($qb)=>$qb->where('capturista_id',$capId))
            ->orderByDesc('id')
            ->simplePaginate($perPage)
            ->withQueryString();

        return AfiliadoResource::collection($rows);
    }

    public function store(Request $request)
    {
        $validated = $this->rules($request);
        if (!LocalDistrictAccess::sectionIsAllowed((string)($validated['seccion'] ?? ''), $request->user())) {
            abort(403, 'No tienes acceso a esa sección.');
        }
        $validated = $this->applySectionData($validated, $request);
        $validated['capturista_id'] = Auth::id();
        $a = Afiliado::create($validated);
        return (new AfiliadoResource($a))->response()->setStatusCode(201);
    }

    public function show(Afiliado $afiliado)
    {
        return new AfiliadoResource($afiliado);
    }

    public function update(Request $request, Afiliado $afiliado)
    {
        $validated = $this->rules($request, $afiliado->id);
        if (!LocalDistrictAccess::sectionIsAllowed((string)($validated['seccion'] ?? ''), $request->user())) {
            abort(403, 'No tienes acceso a esa sección.');
        }
        $validated = $this->applySectionData($validated, $request);
        $afiliado->update($validated);
        return new AfiliadoResource($afiliado);
    }

    public function destroy(Afiliado $afiliado)
    {
        $afiliado->delete();
        return response()->json(['ok' => true]);
    }

    private function rules(Request $request, ?int $id = null): array
    {
        $clave = preg_replace('/\s+/', '', mb_strtoupper(trim((string) $request->input('clave_elector')), 'UTF-8'));
        $request->merge([
            'clave_elector' => $clave !== '' ? $clave : null,
            'tipo_vinculo' => $request->filled('tipo_vinculo') ? $request->input('tipo_vinculo') : null,
        ]);

        $unique = Rule::unique('afiliados', 'clave_elector');
        if ($id) {
            $unique->ignore($id, 'id');
        }

        $data = $request->validate([
            'nombre'            => ['required','string','max:120'],
            'apellido_paterno'  => ['nullable','string','max:120'],
            'apellido_materno'  => ['nullable','string','max:120'],
            'edad'              => ['nullable','integer','min:0','max:120'],
            'sexo'              => ['nullable', Rule::in(['M','F','Otro'])],
            'telefono'          => ['nullable','string','max:30'],
            'email'             => ['nullable','email','max:150'],
            'clave_elector'     => ['nullable','string','max:30',$unique],
            'tipo_vinculo'      => ['nullable','string',Rule::in(array_keys(Afiliado::TIPOS_VINCULO))],
            'numero_mov'        => ['nullable','string','max:50'],
            'municipio'         => ['nullable','string','max:120'],
            'cve_mun'           => ['nullable','string','size:3'],
            'localidad'         => ['nullable','string','max:150'],
            'colonia'           => ['nullable','string','max:150'],
            'calle'             => ['nullable','string','max:150'],
            'numero_ext'        => ['nullable','string','max:20'],
            'numero_int'        => ['nullable','string','max:20'],
            'cp'                => ['nullable','string','max:10'],
            'lat'               => ['nullable','numeric'],
            'lng'               => ['nullable','numeric'],
            'seccion'           => ['required','string','max:6'],
            'distrito_federal'  => ['nullable','integer'],
            'distrito_local'    => ['nullable','integer'],
            'perfil'            => ['nullable','string'],
            'observaciones'     => ['nullable','string'],
            'estatus'           => ['required', Rule::in(['validado','descartado'])],
            'fecha_convencimiento' => ['nullable','date'],
        ], [
            'clave_elector.unique' => 'La clave de elector ya pertenece a otro registro.',
            'tipo_vinculo.in' => 'Selecciona únicamente DV, Comité o MOV.',
            'estatus.required' => 'Indica si la persona está afiliada.',
            'estatus.in' => 'Selecciona Sí o No en el campo Afiliado.',
        ]);

        if (($data['tipo_vinculo'] ?? null) !== 'mov') {
            $data['numero_mov'] = null;
        }

        return $data;
    }

    private function applySectionData(array $data, Request $request): array
    {
        $section = DB::table('secciones')
            ->where('seccion', $data['seccion']);
        LocalDistrictAccess::scope($section, 'distrito_local', $request->user());
        $section = $section->select('municipio', 'cve_mun', 'distrito_local', 'distrito_federal')->first();

        if (!$section) {
            throw ValidationException::withMessages([
                'seccion' => 'La sección capturada no existe o no pertenece a tus distritos asignados.',
            ]);
        }

        $data['municipio'] = $section->municipio;
        $data['cve_mun'] = $section->cve_mun;
        $data['distrito_local'] = $section->distrito_local;
        $data['distrito_federal'] = $section->distrito_federal;

        return LocalDistrictAccess::force($data, $request->user());
    }
}
