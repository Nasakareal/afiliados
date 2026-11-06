<?php

namespace App\Http\Controllers;

use App\Models\Actividad;
use App\Models\User;
use App\Services\GoogleMapsUrlParser;
use App\Support\LocalDistrictAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ActividadController extends Controller
{
    private GoogleMapsUrlParser $googleMapsUrlParser;

    public function __construct(GoogleMapsUrlParser $googleMapsUrlParser)
    {
        $this->googleMapsUrlParser = $googleMapsUrlParser;
    }

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
        $this->normalizeGoogleMapsLocation($request);
        $validated = $request->validate([
            'titulo' => 'required|string|max:180',
            'tipo' => 'required|string|max:80',
            'descripcion' => 'nullable|string|max:3000',
            'inicio' => 'required|date|before_or_equal:now',
            'fin' => 'nullable|date|after_or_equal:inicio',
            'lugar' => 'nullable|string|max:200',
            'ubicacion_google' => 'nullable|url|max:2000',
            'colonia' => 'required|string|max:150',
            'asistentes' => 'required|integer|min:1|max:1000000',
            'yo_asisti' => 'nullable|boolean',
            'evidencia' => 'required|image|mimes:jpg,jpeg,png,webp|max:10240',
        ], $this->validationMessages());

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
            $actividad->participantes()->attach(Auth::id(), [
                'asistio_en' => now(),
                'evidencia_path' => $path,
                'evidencia_nombre' => $evidence->getClientOriginalName(),
            ]);
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
        $this->normalizeGoogleMapsLocation($request);
        $validated = $request->validate([
            'titulo'      => 'required|string|max:180',
            'tipo'        => 'nullable|string|max:80',
            'descripcion' => 'nullable|string',
            'inicio'      => 'required|date',
            'fin'         => 'nullable|date|after_or_equal:inicio',
            'all_day'     => 'boolean',
            'lugar'       => 'nullable|string|max:200',
            'ubicacion_google' => 'nullable|url|max:2000',
            'colonia'     => 'nullable|string|max:150',
            'responsable_id' => 'nullable|exists:users,id',
            'capturista_id'  => 'nullable|exists:users,id',
            'asistentes'     => 'nullable|integer|min:0|max:1000000',
            'evidencia_url'  => 'nullable|url|max:500',
            'foto_actividad' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:10240',
            'capturada_en'   => 'nullable|date',
            'estado'      => 'in:programada,cancelada,realizada',
        ], $this->validationMessages());

        $this->storeActivityPhoto($request, $validated);

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
        $this->normalizeGoogleMapsLocation($request);
        $validated = $request->validate([
            'titulo'      => 'required|string|max:180',
            'tipo'        => 'nullable|string|max:80',
            'descripcion' => 'nullable|string',
            'inicio'      => 'required|date',
            'fin'         => 'nullable|date|after_or_equal:inicio',
            'all_day'     => 'boolean',
            'lugar'       => 'nullable|string|max:200',
            'ubicacion_google' => 'nullable|url|max:2000',
            'colonia'     => 'nullable|string|max:150',
            'responsable_id' => 'nullable|exists:users,id',
            'capturista_id'  => 'nullable|exists:users,id',
            'asistentes'     => 'nullable|integer|min:0|max:1000000',
            'evidencia_url'  => 'nullable|url|max:500',
            'foto_actividad' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:10240',
            'capturada_en'   => 'nullable|date',
            'estado'      => 'in:programada,cancelada,realizada',
        ], $this->validationMessages());

        $previousEvidence = $actividad->evidencia_path;
        $this->storeActivityPhoto($request, $validated);

        $actividad->update($validated);

        $evidenceIsUsedByParticipant = $previousEvidence && $actividad->participantes()
            ->wherePivot('evidencia_path', $previousEvidence)->exists();
        if ($request->hasFile('foto_actividad') && $previousEvidence && !$evidenceIsUsedByParticipant) {
            Storage::disk('local')->delete($previousEvidence);
        }

        return redirect()->route('actividades.show', $actividad->id)
            ->with('status','Actividad actualizada correctamente.');
    }

    public function destroy(Actividad $actividad)
    {
        $paths = $actividad->participantes()->pluck('actividad_participantes.evidencia_path')
            ->push($actividad->evidencia_path)->filter()->unique()->values()->all();
        $actividad->delete();
        Storage::disk('local')->delete($paths);

        return redirect()->route('actividades.index')
            ->with('status','Actividad eliminada correctamente.');
    }

    public function attend(Request $request, Actividad $actividad)
    {
        $this->authorizeVisibleActivity($actividad, $request->user());
        $request->validate([
            'evidencia_asistencia' => 'required|image|mimes:jpg,jpeg,png,webp|max:10240',
        ], $this->validationMessages());
        $evidence = $request->file('evidencia_asistencia');
        $path = $evidence->store('actividades/asistencias', 'local');
        $previousPath = $actividad->participantes()
            ->where('users.id', $request->user()->id)->first()?->pivot?->evidencia_path;
        $actividad->participantes()->syncWithoutDetaching([
            $request->user()->id => [
                'asistio_en' => now(),
                'evidencia_path' => $path,
                'evidencia_nombre' => $evidence->getClientOriginalName(),
            ],
        ]);
        if ($previousPath && $previousPath !== $actividad->evidencia_path) {
            Storage::disk('local')->delete($previousPath);
        }

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

    public function attendanceEvidence(Request $request, Actividad $actividad, User $user)
    {
        $this->authorizeVisibleActivity($actividad, $request->user());
        abort_unless(
            (int) $request->user()->id === (int) $user->id
                || $request->user()->hasAnyRole(['Admin', 'SuperAdmin', 'Coordinador']),
            403
        );
        $participant = $actividad->participantes()->where('users.id', $user->id)->firstOrFail();
        $path = $participant->pivot->evidencia_path;
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response(
            $path,
            $participant->pivot->evidencia_nombre ?: basename($path)
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

    private function normalizeGoogleMapsLocation(Request $request): void
    {
        $request->merge([
            'ubicacion_google' => $this->googleMapsUrlParser->normalize(
                $request->input('ubicacion_google')
            ),
        ]);
    }

    private function storeActivityPhoto(Request $request, array &$validated): void
    {
        unset($validated['foto_actividad']);
        if (!$request->hasFile('foto_actividad')) {
            return;
        }

        $photo = $request->file('foto_actividad');
        $validated['evidencia_path'] = $photo->store('actividades/evidencias', 'local');
        $validated['evidencia_nombre'] = $photo->getClientOriginalName();
    }

    private function validationMessages(): array
    {
        return [
            'ubicacion_google.url' => 'Pega un enlace válido de Google Maps.',
            'evidencia.image' => 'La evidencia debe ser una foto.',
            'evidencia_asistencia.required' => 'Para confirmar tu asistencia debes subir una foto como evidencia.',
            'evidencia_asistencia.image' => 'La evidencia de asistencia debe ser una foto.',
            'foto_actividad.image' => 'La evidencia de la actividad debe ser una foto.',
            '*.max' => 'La foto no debe superar 10 MB.',
        ];
    }
}
