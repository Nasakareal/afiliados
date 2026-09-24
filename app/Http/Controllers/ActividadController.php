<?php

namespace App\Http\Controllers;

use App\Models\Actividad;
use App\Models\User;
use App\Support\LocalDistrictAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

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

        $query = Actividad::entreFechas($desde, $hasta);
        $this->scopeVisibleActivities($query, $request->user());
        $actividades = $query->get();

        $eventos = $actividades->map(function ($a) {
            return [
                'id'        => $a->id,
                'title'     => $a->titulo,
                'start'     => $a->inicio ? $a->inicio->toIso8601String() : null,
                'end'       => $a->fin ? $a->fin->toIso8601String() : null,
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
        $query = Actividad::with(['creador', 'responsable', 'participantes'])->latest();
        $this->scopeVisibleActivities($query, request()->user());
        $actividades = $query->paginate(15);
        return view('actividades.index', compact('actividades'));
    }

    public function reportForm()
    {
        return view('actividades.reportar');
    }

    public function storeReport(Request $request)
    {
        $validated = $request->validate([
            'titulo' => 'required|string|max:180',
            'tipo' => 'required|string|max:80',
            'descripcion' => 'nullable|string|max:3000',
            'inicio' => 'required|date|before_or_equal:now',
            'fin' => 'nullable|date|after_or_equal:inicio',
            'lugar' => 'nullable|string|max:200',
            'colonia' => 'required|string|max:150',
            'asistentes' => 'required|integer|min:1|max:1000000',
            'yo_asisti' => 'nullable|boolean',
            'evidencia' => 'required|file|mimes:jpg,jpeg,png,webp,pdf|max:10240',
        ]);

        $evidence = $request->file('evidencia');
        $path = $evidence->store('actividades/evidencias', 'local');
        unset($validated['evidencia'], $validated['yo_asisti']);

        $validated = array_merge($validated, [
            'all_day' => false,
            'creado_por' => Auth::id(),
            'responsable_id' => Auth::id(),
            'capturista_id' => Auth::id(),
            'estado' => 'realizada',
            'origen' => 'reporte_usuario',
            'estado_revision' => 'pendiente',
            'capturada_en' => now(),
            'evidencia_path' => $path,
            'evidencia_nombre' => $evidence->getClientOriginalName(),
        ]);
        $validated = LocalDistrictAccess::force($validated, $request->user());

        $actividad = Actividad::create($validated);
        if ($request->boolean('yo_asisti')) {
            $actividad->participantes()->attach(Auth::id(), ['asistio_en' => now()]);
        }

        return redirect()->route('actividades.show', $actividad)
            ->with('status', 'Tu actividad fue reportada y quedó pendiente de revisión.');
    }

    public function create()
    {
        $responsables = User::query()->orderBy('name')->get(['id', 'name']);
        return view('actividades.create', compact('responsables'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'titulo'      => 'required|string|max:180',
            'tipo'        => 'nullable|string|max:80',
            'descripcion' => 'nullable|string',
            'inicio'      => 'required|date',
            'fin'         => 'nullable|date|after_or_equal:inicio',
            'all_day'     => 'boolean',
            'lugar'       => 'nullable|string|max:200',
            'colonia'     => 'nullable|string|max:150',
            'responsable_id' => 'nullable|exists:users,id',
            'capturista_id'  => 'nullable|exists:users,id',
            'asistentes'     => 'nullable|integer|min:0|max:1000000',
            'evidencia_url'  => 'nullable|url|max:500',
            'capturada_en'   => 'nullable|date',
            'estado'      => 'in:programada,cancelada,realizada',
        ]);

        $validated['creado_por'] = Auth::id();
        $validated['responsable_id'] = $validated['responsable_id'] ?? Auth::id();
        $validated['capturista_id'] = $validated['capturista_id'] ?? Auth::id();
        $validated['origen'] = 'oficial';
        $validated['estado_revision'] = 'aprobada';
        $validated = LocalDistrictAccess::force($validated, $request->user());

        $actividad = Actividad::create($validated);

        return redirect()->route('actividades.show', $actividad->id)
            ->with('status','Actividad creada correctamente.');
    }

    public function show(Actividad $actividad)
    {
        $this->authorizeVisibleActivity($actividad, request()->user());
        $actividad->load(['creador', 'responsable', 'participantes']);
        $yoAsisti = $actividad->participantes->contains('id', Auth::id());
        return view('actividades.show', compact('actividad', 'yoAsisti'));
    }

    public function edit(Actividad $actividad)
    {
        $this->authorizeVisibleActivity($actividad, request()->user());
        $responsables = User::query()->orderBy('name')->get(['id', 'name']);
        return view('actividades.edit', compact('actividad', 'responsables'));
    }

    public function update(Request $request, Actividad $actividad)
    {
        $validated = $request->validate([
            'titulo'      => 'required|string|max:180',
            'tipo'        => 'nullable|string|max:80',
            'descripcion' => 'nullable|string',
            'inicio'      => 'required|date',
            'fin'         => 'nullable|date|after_or_equal:inicio',
            'all_day'     => 'boolean',
            'lugar'       => 'nullable|string|max:200',
            'colonia'     => 'nullable|string|max:150',
            'responsable_id' => 'nullable|exists:users,id',
            'capturista_id'  => 'nullable|exists:users,id',
            'asistentes'     => 'nullable|integer|min:0|max:1000000',
            'evidencia_url'  => 'nullable|url|max:500',
            'capturada_en'   => 'nullable|date',
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

    public function attend(Request $request, Actividad $actividad)
    {
        $this->authorizeVisibleActivity($actividad, $request->user());
        $actividad->participantes()->syncWithoutDetaching([
            $request->user()->id => ['asistio_en' => now()],
        ]);

        return back()->with('status', 'Tu asistencia quedó registrada.');
    }

    public function evidence(Request $request, Actividad $actividad)
    {
        $this->authorizeVisibleActivity($actividad, $request->user());
        abort_unless($actividad->evidencia_path && Storage::disk('local')->exists($actividad->evidencia_path), 404);

        return Storage::disk('local')->response(
            $actividad->evidencia_path,
            $actividad->evidencia_nombre ?: basename($actividad->evidencia_path)
        );
    }

    public function review(Request $request, Actividad $actividad)
    {
        abort_unless($actividad->origen === 'reporte_usuario', 422, 'Sólo los reportes del equipo requieren revisión.');
        $validated = $request->validate([
            'estado_revision' => 'required|in:aprobada,rechazada',
        ]);
        $actividad->update([
            'estado_revision' => $validated['estado_revision'],
            'revisado_por' => $request->user()->id,
            'revisado_en' => now(),
        ]);

        return back()->with('status', 'El reporte fue '.($validated['estado_revision'] === 'aprobada' ? 'aprobado.' : 'marcado como rechazado.'));
    }

    private function scopeVisibleActivities($query, $user): void
    {
        if ($user->hasAnyRole(['Admin', 'SuperAdmin', 'Coordinador'])) {
            return;
        }

        $query->where(function ($visible) use ($user): void {
            $visible->where('origen', 'oficial')
                ->orWhere('creado_por', $user->id)
                ->orWhereHas('participantes', fn ($participants) => $participants->where('users.id', $user->id));
        });
    }

    private function authorizeVisibleActivity(Actividad $actividad, $user): void
    {
        if ($user->hasAnyRole(['Admin', 'SuperAdmin', 'Coordinador'])) {
            return;
        }

        $visible = $actividad->origen === 'oficial'
            || (int) $actividad->creado_por === (int) $user->id
            || $actividad->participantes()->where('users.id', $user->id)->exists();
        abort_unless($visible, 404);
    }

    /**
     * Colores según estado
     */
    private function estadoColor(string $estado): string
    {
        $colors = [
            'programada' => '#1976d2', // azul
            'cancelada'  => '#d32f2f', // rojo
            'realizada'  => '#388e3c', // verde
        ];

        return $colors[$estado] ?? '#616161'; // gris
    }
}
