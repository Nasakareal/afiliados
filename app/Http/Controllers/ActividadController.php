<?php

namespace App\Http\Controllers;

use App\Models\Actividad;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ActividadController extends Controller
{
    /**
     * Vista del calendario (FullCalendar)
     */
    public function index()
    {
        return view('actividades.calendario');
    }

    /**
     * Endpoint JSON para FullCalendar
     */
    public function feed(Request $request)
    {
        $desde = $request->query('start');
        $hasta = $request->query('end');

        $actividades = Actividad::entreFechas($desde, $hasta)->get();

        $eventos = $actividades->map(function ($a) {
            return [
                'id'        => $a->id,
                'title'     => $a->titulo,
                'start'     => $a->inicio?->toIso8601String(),
                'end'       => $a->fin?->toIso8601String(),
                'allDay'    => (bool) $a->all_day,
                'color'     => $this->estadoColor($a->estado ?? 'programada'),
                'url'       => route('actividades.show',$a->id),
                'descripcion'=> $a->descripcion,
                'lugar'      => $a->lugar,
                'estado'     => $a->estado,
                'editUrl'    => route('actividades.edit',$a->id),
            ];
        });

        return response()->json($eventos);
    }

    /**
     * Listado simple en tabla
     */
    public function list()
    {
        $actividades = Actividad::with('creador')->latest()->paginate(15);
        return view('actividades.index', compact('actividades'));
    }

    public function create()
    {
        return view('actividades.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'titulo'      => 'required|string|max:180',
            'descripcion' => 'nullable|string',
            'inicio'      => 'required|date',
            'fin'         => 'nullable|date|after_or_equal:inicio',
            'all_day'     => 'boolean',
            'lugar'       => 'nullable|string|max:200',
            'estado'      => 'in:programada,cancelada,realizada',
        ]);

        $validated['creado_por'] = Auth::id();

        $actividad = Actividad::create($validated);

        return redirect()->route('actividades.show', $actividad->id)
            ->with('status','Actividad creada correctamente.');
    }

    public function show(Actividad $actividad)
    {
        return view('actividades.show', compact('actividad'));
    }

    public function edit(Actividad $actividad)
    {
        return view('actividades.edit', compact('actividad'));
    }

    public function update(Request $request, Actividad $actividad)
    {
        $validated = $request->validate([
            'titulo'      => 'required|string|max:180',
            'descripcion' => 'nullable|string',
            'inicio'      => 'required|date',
            'fin'         => 'nullable|date|after_or_equal:inicio',
            'all_day'     => 'boolean',
            'lugar'       => 'nullable|string|max:200',
            'estado'      => 'in:programada,cancelada,realizada',
        ]);

        $actividad->update($validated);

        return redirect()->route('actividades.show', $actividad->id)
            ->with('status','Actividad actualizada correctamente.');
    }

    public function destroy(Actividad $actividad)
    {
        $actividad->delete();

        return redirect()->route('actividades.index')
            ->with('status','Actividad eliminada correctamente.');
    }

    /**
     * Colores según estado
     */
    private function estadoColor(string $estado): string
    {
        return match($estado) {
            'programada' => '#1976d2', // azul
            'cancelada'  => '#d32f2f', // rojo
            'realizada'  => '#388e3c', // verde
            default      => '#616161', // gris
        };
    }
}
