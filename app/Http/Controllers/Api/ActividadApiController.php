<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Actividad;
use Illuminate\Http\Request;
use App\Http\Resources\ActividadResource;

class ActividadApiController extends Controller
{
    public function index()
    {
        $rows = Actividad::with('creador')->latest()->paginate(20);
        return ActividadResource::collection($rows);
    }

    public function feed(Request $request)
    {
        $desde = $request->query('start');
        $hasta = $request->query('end');

        $actividades = Actividad::entreFechas($desde, $hasta)->get();

        $eventos = $actividades->map(fn($a) => [
            'id'    => $a->id,
            'title' => $a->titulo,
            'start' => $a->inicio->toIso8601String(),
            'end'   => $a->fin?->toIso8601String(),
            'allDay'=> $a->all_day,
            'color' => $this->estadoColor($a->estado),
        ]);

        return response()->json($eventos);
    }

    public function store(Request $request)
    {
        $v = $request->validate([
            'titulo'      => 'required|string|max:180',
            'descripcion' => 'nullable|string',
            'inicio'      => 'required|date',
            'fin'         => 'nullable|date|after_or_equal:inicio',
            'all_day'     => 'boolean',
            'lugar'       => 'nullable|string|max:200',
            'estado'      => 'in:programada,cancelada,realizada',
        ]);
        $a = Actividad::create($v + ['creado_por' => $request->user()->id]);
        return (new ActividadResource($a))->response()->setStatusCode(201);
    }

    public function show(Actividad $actividad) { return new ActividadResource($actividad); }

    public function update(Request $r, Actividad $actividad)
    {
        $v = $r->validate([
            'titulo'      => 'required|string|max:180',
            'descripcion' => 'nullable|string',
            'inicio'      => 'required|date',
            'fin'         => 'nullable|date|after_or_equal:inicio',
            'all_day'     => 'boolean',
            'lugar'       => 'nullable|string|max:200',
            'estado'      => 'in:programada,cancelada,realizada',
        ]);
        $actividad->update($v);
        return new ActividadResource($actividad);
    }

    public function destroy(Actividad $actividad) { $actividad->delete(); return response()->json(['ok'=>true]); }

    private function estadoColor(string $estado): string
    {
        return match($estado) {
            'programada' => '#1976d2',
            'cancelada'  => '#d32f2f',
            'realizada'  => '#388e3c',
            default      => '#616161',
        };
    }
}
