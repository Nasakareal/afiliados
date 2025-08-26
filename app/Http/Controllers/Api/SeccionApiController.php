<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Seccion;
use Illuminate\Http\Request;
use App\Http\Resources\SeccionResource;

class SeccionApiController extends Controller
{
    public function index(Request $request)
    {
        $cveMun    = $request->query('cve_mun');
        $municipio = $request->query('municipio');
        $perPage   = (int) ($request->query('per_page', 50));

        $rows = Seccion::query()
            ->when($cveMun, fn($q) => $q->where('cve_mun', $cveMun))
            ->when($municipio, fn($q) => $q->where('municipio', $municipio))
            ->orderBy('seccion')
            ->paginate($perPage > 0 ? $perPage : 50)
            ->withQueryString();

        return SeccionResource::collection($rows);
    }

    public function store(Request $request)
    {
        $data = $this->rules($request);
        $s = Seccion::create($data);
        return (new SeccionResource($s))->response()->setStatusCode(201);
    }

    public function show(Seccion $seccion)
    {
        return new SeccionResource($seccion);
    }

    public function update(Request $request, Seccion $seccion)
    {
        $data = $this->rules($request);
        $seccion->update($data);
        return new SeccionResource($seccion);
    }

    public function destroy(Seccion $seccion)
    {
        $seccion->delete();
        return response()->json(['ok' => true]);
    }

    private function rules(Request $request): array
    {
        return $request->validate([
            'seccion'           => ['required','string','max:6'],
            'municipio'         => ['required','string','max:120'],
            'cve_mun'           => ['nullable','string','size:3'],
            'lista_nominal'     => ['nullable','integer'],
            'distrito_local'    => ['nullable','integer'],
            'distrito_federal'  => ['nullable','integer'],
            'centroid_lat'      => ['nullable','numeric'],
            'centroid_lng'      => ['nullable','numeric'],
        ]);
    }
}
