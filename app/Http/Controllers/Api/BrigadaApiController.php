<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Brigada;
use App\Models\BrigadaParticipante;
use App\Models\Seccion;
use App\Services\BrigadaParticipationService;
use App\Services\GoogleMapsUrlParser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class BrigadaApiController extends Controller
{
    private const MORELIA_CVE_MUN = '053';
    private const DISTRICTS = [10, 11, 16, 17];

    public function index(Request $request)
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:150'], 'seccion' => ['nullable', 'string', 'max:6'],
            'distrito' => ['nullable', 'integer', Rule::in(self::DISTRICTS)], 'activo' => ['nullable', 'boolean'],
        ]);
        $items = $this->query($request)
            ->when($filters['q'] ?? null, fn ($query, $value) => $query->where(fn ($where) => $where->where('responsable', 'like', '%'.$value.'%')->orWhere('direccion', 'like', '%'.$value.'%')))
            ->when($filters['seccion'] ?? null, fn ($query, $value) => $query->whereHas('seccion', fn ($section) => $section->where('seccion', $value)))
            ->when($filters['distrito'] ?? null, fn ($query, $value) => $query->whereHas('seccion', fn ($section) => $section->where('distrito_local', $value)))
            ->when(array_key_exists('activo', $filters), fn ($query) => $query->where('activo', $filters['activo']))
            ->latest()->paginate(min(50, max(1, (int) $request->query('per_page', 20))));
        $items->getCollection()->transform(fn (Brigada $brigada) => $this->payload($brigada, $request));

        return response()->json($items);
    }

    public function sections()
    {
        return response()->json(Seccion::query()->where('cve_mun', self::MORELIA_CVE_MUN)->whereIn('distrito_local', self::DISTRICTS)
            ->orderBy('distrito_local')->orderByRaw('CAST(seccion AS UNSIGNED)')
            ->get(['id', 'seccion', 'municipio', 'distrito_local', 'distrito_federal']));
    }

    public function show(Request $request, Brigada $brigada)
    {
        $this->ensureMorelia($brigada);
        $brigada = $this->query($request)->findOrFail($brigada->id);
        $payload = $this->payload($brigada, $request);
        if ($request->user()->can('brigadas.participantes.ver')) {
            $payload['participantes'] = $brigada->participantes()->with('usuario:id,name')->latest('asistio_en')->get()->map(fn ($item) => $this->participantPayload($item, $brigada));
        }
        return response()->json($payload);
    }

    public function store(Request $request, GoogleMapsUrlParser $parser)
    {
        $brigada = Brigada::create($this->data($request, $parser) + ['capturado_por' => $request->user()->id]);
        return response()->json($this->payload($this->query($request)->findOrFail($brigada->id), $request), 201);
    }

    public function update(Request $request, Brigada $brigada, GoogleMapsUrlParser $parser)
    {
        $this->ensureMorelia($brigada);
        $brigada->update($this->data($request, $parser));
        return response()->json($this->payload($this->query($request)->findOrFail($brigada->id), $request));
    }

    public function destroy(Brigada $brigada)
    {
        $this->ensureMorelia($brigada);
        $brigada->delete();
        return response()->json(['ok' => true]);
    }

    public function participate(Request $request, Brigada $brigada, BrigadaParticipationService $service)
    {
        $this->ensureMorelia($brigada);
        $data = $request->validate([
            'foto' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:9216'],
            'observaciones' => ['nullable', 'string', 'max:2000'],
        ]);
        $participant = $service->register($brigada, $request->user()->id, $request->file('foto'), $data['observaciones'] ?? null);
        return response()->json($this->participantPayload($participant, $brigada), 201);
    }

    public function evidence(Request $request, Brigada $brigada, BrigadaParticipante $participante)
    {
        abort_unless((int) $participante->brigada_id === (int) $brigada->id, 404);
        abort_unless((int) $participante->user_id === (int) $request->user()->id || $request->user()->can('brigadas.participantes.ver'), 403);
        abort_unless(Storage::disk('local')->exists($participante->evidencia_path), 404);
        return response()->file(Storage::disk('local')->path($participante->evidencia_path), ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }

    private function query(Request $request)
    {
        return Brigada::query()->with('seccion')->withCount('participantes')
            ->with(['participantes' => fn ($query) => $query->where('user_id', $request->user()->id)])
            ->whereHas('seccion', fn ($query) => $query->where('cve_mun', self::MORELIA_CVE_MUN)->whereIn('distrito_local', self::DISTRICTS));
    }

    private function payload(Brigada $brigada, Request $request): array
    {
        $mine = $brigada->participantes->first();
        return [
            'id' => $brigada->id,
            'seccion' => ['id' => $brigada->seccion->id, 'numero' => (string) $brigada->seccion->seccion, 'distrito_local' => (int) $brigada->seccion->distrito_local, 'municipio' => $brigada->seccion->municipio],
            'responsable' => $brigada->responsable, 'telefono' => $brigada->telefono, 'direccion' => $brigada->direccion,
            'ubicacion_google' => $brigada->google_maps_url, 'lat' => (float) $brigada->lat, 'lng' => (float) $brigada->lng,
            'activo' => (bool) $brigada->activo, 'observaciones' => $brigada->observaciones,
            'participantes_count' => (int) $brigada->participantes_count, 'ya_participe' => (bool) $mine,
            'mi_participacion' => $mine ? $this->participantPayload($mine, $brigada) : null,
            'created_at' => optional($brigada->created_at)->toIso8601String(), 'updated_at' => optional($brigada->updated_at)->toIso8601String(),
        ];
    }

    private function participantPayload(BrigadaParticipante $participant, Brigada $brigada): array
    {
        return [
            'id' => $participant->id,
            'usuario' => $participant->relationLoaded('usuario') && $participant->usuario ? ['id' => $participant->usuario->id, 'name' => $participant->usuario->name] : null,
            'asistio_en' => optional($participant->asistio_en)->toIso8601String(), 'observaciones' => $participant->observaciones,
            'evidencia_url' => route('api.brigadas.evidencia', [$brigada, $participant]),
        ];
    }

    private function data(Request $request, GoogleMapsUrlParser $parser): array
    {
        $data = $request->validate([
            'seccion_id' => ['required', 'integer'], 'responsable' => ['required', 'string', 'max:150'],
            'telefono' => ['nullable', 'string', 'max:30'], 'direccion' => ['nullable', 'string', 'max:500'],
            'ubicacion_google' => ['nullable', 'string', 'max:2000'], 'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'numeric', 'between:-180,180'], 'activo' => ['required', 'boolean'], 'observaciones' => ['nullable', 'string', 'max:2000'],
        ]);
        if (!Seccion::query()->whereKey($data['seccion_id'])->where('cve_mun', self::MORELIA_CVE_MUN)->whereIn('distrito_local', self::DISTRICTS)->exists()) {
            throw ValidationException::withMessages(['seccion_id' => 'Selecciona una sección de Morelia.']);
        }
        $url = $parser->normalize($data['ubicacion_google'] ?? null);
        if ($url && ($point = $parser->coordinates($url))) {
            [$data['lat'], $data['lng']] = $point;
        }
        if (!isset($data['lat'], $data['lng'])) {
            throw ValidationException::withMessages(['lat' => 'Envía una ubicación válida.']);
        }
        $data['ubicacion_google'] = sprintf('https://www.google.com/maps/search/?api=1&query=%s,%s', $data['lat'], $data['lng']);
        return $data;
    }

    private function ensureMorelia(Brigada $brigada): void
    {
        $brigada->loadMissing('seccion');
        abort_unless($brigada->seccion && $brigada->seccion->cve_mun === self::MORELIA_CVE_MUN && in_array((int) $brigada->seccion->distrito_local, self::DISTRICTS, true), 404);
    }
}
