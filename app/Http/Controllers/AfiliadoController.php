<?php

namespace App\Http\Controllers;

use App\Models\Afiliado;
use App\Models\Seccion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class AfiliadoController extends Controller
{
    /**
     * Listado con filtros opcionales:
     * ?q=texto (nombre, curp, teléfono)
     * ?seccion_id=ID
     * ?cvegeo=XXXXX (municipio INEGI)
     */
    public function index(\Illuminate\Http\Request $request)
    {
        $q         = trim((string)$request->query('q'));
        $seccion   = $request->query('seccion');     // p.ej. "1234"
        $cveMun    = $request->query('cve_mun');     // p.ej. "053"
        $municipio = $request->query('municipio');   // p.ej. "Morelia"
        $estatus   = $request->query('estatus');     // "pendiente|validado|descartado"
        $capId     = $request->query('capturista_id');

        $afiliados = \App\Models\Afiliado::query()
            ->leftJoin('secciones', function($j){
                // une por seccion y cve_mun si existe, si no por nombre de municipio
                $j->on('secciones.seccion','=','afiliados.seccion');
                if (\Illuminate\Support\Facades\Schema::hasColumn('afiliados','cve_mun')) {
                    $j->on('secciones.cve_mun','=','afiliados.cve_mun');
                } else {
                    $j->on('secciones.municipio','=','afiliados.municipio');
                }
            })
            ->leftJoin('users','users.id','=','afiliados.capturista_id')
            ->when($q !== '', function($qb) use ($q){
                $qb->where(function($w) use ($q){
                    $w->whereRaw("CONCAT_WS(' ',afiliados.nombre,afiliados.apellido_paterno,afiliados.apellido_materno) like ?", ["%{$q}%"])
                      ->orWhere('afiliados.telefono','like',"%{$q}%")
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


    /**
     * Form de creación
     */
    public function create()
    {
        $secciones = Seccion::orderBy('numero')->get(['id','numero','nombre']);

        $municipios = DB::table('cat_municipios')
            ->select('cvegeo','nomgeo')
            ->where('cve_ent','16')
            ->orderBy('nomgeo')
            ->get();

        return view('afiliados.create', compact('secciones','municipios'));
    }

    /**
     * Guardar nuevo afiliado
     */
    public function store(Request $request)
    {
        $data = $this->validateData($request);

        // normalizaciones mínimas (sin cambiar nombres de columnas)
        $data['nombre']            = Str::of($data['nombre'])->squish();
        $data['apellido_paterno']  = Str::of($data['apellido_paterno'])->squish();
        $data['apellido_materno']  = Str::of($data['apellido_materno'] ?? '')->squish();
        $data['capturista_id']     = Auth::id();

        $afiliado = Afiliado::create($data);

        return redirect()->route('afiliados.show',$afiliado->id)
            ->with('status','Afiliado creado correctamente.');
    }

    /**
     * Detalle
     */
    public function show(Afiliado $afiliado)
    {
        $afiliado->load(['seccion','capturista']);
        // si quieres nombre de municipio:
        $municipio = DB::table('cat_municipios')
            ->select('nomgeo')
            ->where('cvegeo',$afiliado->cvegeo)
            ->first();

        return view('afiliados.show', compact('afiliado','municipio'));
    }

    /**
     * Form de edición
     */
    public function edit(Afiliado $afiliado)
    {
        $secciones = Seccion::orderBy('numero')->get(['id','numero','nombre']);
        $municipios = DB::table('cat_municipios')
            ->select('cvegeo','nomgeo')
            ->where('cve_ent','16')
            ->orderBy('nomgeo')
            ->get();

        return view('afiliados.edit', compact('afiliado','secciones','municipios'));
    }

    /**
     * Actualizar
     */
    public function update(Request $request, Afiliado $afiliado)
    {
        $data = $this->validateData($request, $afiliado->id);

        $data['nombre']           = Str::of($data['nombre'])->squish();
        $data['apellido_paterno'] = Str::of($data['apellido_paterno'])->squish();
        $data['apellido_materno'] = Str::of($data['apellido_materno'] ?? '')->squish();

        $afiliado->update($data);

        return redirect()->route('afiliados.show',$afiliado->id)
            ->with('status','Afiliado actualizado correctamente.');
    }

    /**
     * Eliminar
     */
    public function destroy(Afiliado $afiliado)
    {
        $afiliado->delete();

        return redirect()->route('afiliados.index')
            ->with('status','Afiliado eliminado correctamente.');
    }

    /**
     * Reglas de validación centralizadas
     * Ajusta columnas según tu migración real.
     */
    private function validateData(Request $request, ?int $id = null): array
    {
        return $request->validate([
            'nombre'            => ['required','string','max:120'],
            'apellido_paterno'  => ['required','string','max:120'],
            'apellido_materno'  => ['nullable','string','max:120'],
            'curp'              => [
                'nullable','string','max:18',
                Rule::unique('afiliados','curp')->ignore($id)
            ],
            'telefono'          => ['nullable','string','max:25'],
            'domicilio'         => ['nullable','string','max:255'],

            // Relacionales
            'seccion_id'        => ['required','integer','exists:secciones,id'],
            'cvegeo'            => [
                'required','string','max:5',
                // valida que exista en catálogo (INEGI) si lo tienes
                Rule::exists('cat_municipios','cvegeo')
            ],

            // Extras opcionales si existen en tu tabla:
            'referencia'        => ['nullable','string','max:255'],
            'observaciones'     => ['nullable','string'],
        ],[
            'cvegeo.exists'     => 'El municipio seleccionado no existe en el catálogo.',
            'seccion_id.exists' => 'La sección seleccionada no existe.',
        ]);
    }
}
