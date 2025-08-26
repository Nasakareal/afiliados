<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Afiliado;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use App\Http\Resources\AfiliadoResource;

class AfiliadoApiController extends Controller
{
    public function index(Request $request)
    {
        $q         = trim((string)$request->query('q'));
        $seccion   = $request->query('seccion');
        $cveMun    = $request->query('cve_mun');
        $municipio = $request->query('municipio');
        $estatus   = $request->query('estatus');
        $capId     = $request->query('capturista_id');

        $rows = Afiliado::query()
            ->when($q !== '', function($qb) use ($q){
                $qb->where(function($w) use ($q){
                    $w->whereRaw("CONCAT_WS(' ',nombre,apellido_paterno,apellido_materno) like ?", ["%{$q}%"])
                      ->orWhere('telefono','like',"%{$q}%")
                      ->orWhere('email','like',"%{$q}%");
                });
            })
            ->when($seccion,   fn($qb)=>$qb->where('seccion',$seccion))
            ->when($cveMun,    fn($qb)=>$qb->where('cve_mun',$cveMun))
            ->when($municipio, fn($qb)=>$qb->where('municipio',$municipio))
            ->when($estatus,   fn($qb)=>$qb->where('estatus',$estatus))
            ->when($capId,     fn($qb)=>$qb->where('capturista_id',$capId))
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return AfiliadoResource::collection($rows);
    }

    public function store(Request $request)
    {
        $validated = $this->rules($request);
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
        return $request->validate([
            'nombre'            => ['required','string','max:120'],
            'apellido_paterno'  => ['nullable','string','max:120'],
            'apellido_materno'  => ['nullable','string','max:120'],
            'edad'              => ['nullable','integer','min:0','max:120'],
            'sexo'              => ['nullable', Rule::in(['M','F','Otro'])],
            'telefono'          => ['nullable','string','max:30'],
            'email'             => ['nullable','email','max:150'],
            'municipio'         => ['required','string','max:120'],
            'cve_mun'           => ['nullable','string','size:3'],
            'localidad'         => ['nullable','string','max:150'],
            'colonia'           => ['nullable','string','max:150'],
            'calle'             => ['nullable','string','max:150'],
            'numero_ext'        => ['nullable','string','max:20'],
            'numero_int'        => ['nullable','string','max:20'],
            'cp'                => ['nullable','string','max:10'],
            'lat'               => ['nullable','numeric'],
            'lng'               => ['nullable','numeric'],
            'seccion'           => ['nullable','string','max:6'],
            'distrito_federal'  => ['nullable','integer'],
            'distrito_local'    => ['nullable','integer'],
            'perfil'            => ['nullable','string'],
            'observaciones'     => ['nullable','string'],
            'estatus'           => ['nullable', Rule::in(['pendiente','validado','descartado'])],
            'fecha_convencimiento' => ['nullable','date'],
        ]);
    }
}
