<?php

namespace App\Http\Controllers;

use App\Models\Lona;
use App\Services\GoogleMapsUrlParser;
use App\Services\LonaPhotoProcessor;
use App\Services\LonasExcelExporter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class LonaController extends Controller
{
    /** @var GoogleMapsUrlParser */
    private $googleMapsUrlParser;

    public function __construct(GoogleMapsUrlParser $googleMapsUrlParser)
    {
        $this->googleMapsUrlParser = $googleMapsUrlParser;
    }

    public function index(Request $request)
    {
        $q = trim((string) $request->query('q'));
        $seccion = trim((string) $request->query('seccion'));

        $lonas = $this->filteredQuery($q, $seccion)
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('lonas.index', compact('lonas', 'q', 'seccion'));
    }

    public function export(Request $request, LonasExcelExporter $exporter)
    {
        $q = trim((string) $request->query('q'));
        $seccion = trim((string) $request->query('seccion'));

        $path = $exporter->create(
            $this->filteredQuery($q, $seccion)->latest()->get()
        );

        return response()->download(
            $path,
            'lonas_'.now()->format('Ymd_His').'.xlsx',
            [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            ]
        )->deleteFileAfterSend(true);
    }

    public function create()
    {
        return view('lonas.create');
    }

    public function store(Request $request, LonaPhotoProcessor $photoProcessor)
    {
        $data = $this->validateLona($request, true);
        $photo = $photoProcessor->process($request->file('foto'));

        try {
            $lona = DB::transaction(function () use ($data, $photo, $request) {
                return Lona::create(array_merge($data, [
                    'foto_path' => $photo['path'],
                    'foto_nombre_original' => $photo['original_name'],
                    'foto_bytes_original' => $photo['original_bytes'],
                    'foto_bytes_final' => $photo['final_bytes'],
                    'capturado_por' => $request->user()->id,
                ]));
            });
        } catch (\Throwable $e) {
            Storage::disk('local')->delete($photo['path']);
            throw $e;
        }

        return redirect()->route('lonas.show', $lona)
            ->with('success', 'Lona registrada correctamente.');
    }

    public function show(Lona $lona)
    {
        $lona->load('capturista');
        return view('lonas.show', compact('lona'));
    }

    public function edit(Lona $lona)
    {
        return view('lonas.edit', compact('lona'));
    }

    public function update(Request $request, Lona $lona, LonaPhotoProcessor $photoProcessor)
    {
        $data = $this->validateLona($request, false);
        $newPhoto = $request->hasFile('foto')
            ? $photoProcessor->process($request->file('foto'))
            : null;
        $oldPath = $lona->foto_path;

        if ($newPhoto) {
            $data = array_merge($data, [
                'foto_path' => $newPhoto['path'],
                'foto_nombre_original' => $newPhoto['original_name'],
                'foto_bytes_original' => $newPhoto['original_bytes'],
                'foto_bytes_final' => $newPhoto['final_bytes'],
            ]);
        }

        try {
            DB::transaction(fn () => $lona->update($data));
        } catch (\Throwable $e) {
            if ($newPhoto) {
                Storage::disk('local')->delete($newPhoto['path']);
            }
            throw $e;
        }

        if ($newPhoto && $oldPath !== $newPhoto['path']) {
            Storage::disk('local')->delete($oldPath);
        }

        return redirect()->route('lonas.show', $lona)
            ->with('success', 'Lona actualizada correctamente.');
    }

    public function destroy(Lona $lona)
    {
        $photoPath = $lona->foto_path;
        $lona->forceDelete();
        Storage::disk('local')->delete($photoPath);

        return redirect()->route('lonas.index')
            ->with('success', 'Lona eliminada correctamente.');
    }

    public function map()
    {
        return view('lonas.map');
    }

    public function mapData()
    {
        $lonas = Lona::query()
            ->select('id', 'seccion', 'direccion', 'responsable', 'ubicacion_google', 'lat', 'lng', 'created_at')
            ->latest()
            ->get()
            ->map(function (Lona $lona) {
                return [
                    'id' => $lona->id,
                    'seccion' => $lona->seccion,
                    'direccion' => $lona->direccion,
                    'responsable' => $lona->responsable,
                    'lat' => (float) $lona->lat,
                    'lng' => (float) $lona->lng,
                    'fecha' => optional($lona->created_at)->format('d/m/Y H:i'),
                    'foto_url' => route('lonas.foto', $lona),
                    'detalle_url' => route('lonas.show', $lona),
                    'google_url' => $lona->ubicacion_google
                        ?: "https://www.google.com/maps?q={$lona->lat},{$lona->lng}",
                ];
            });

        return response()->json($lonas);
    }

    public function photo(Lona $lona)
    {
        if (!$lona->foto_path || !Storage::disk('local')->exists($lona->foto_path)) {
            abort(404);
        }

        return response()->file(
            Storage::disk('local')->path($lona->foto_path),
            [
                'Content-Type' => 'image/jpeg',
                'Cache-Control' => 'private, max-age=86400',
                'X-Content-Type-Options' => 'nosniff',
            ]
        );
    }

    private function filteredQuery(string $q, string $seccion)
    {
        return Lona::with('capturista:id,name')
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($where) use ($q) {
                    $where->where('direccion', 'like', "%{$q}%")
                        ->orWhere('responsable', 'like', "%{$q}%");
                });
            })
            ->when($seccion !== '', fn ($query) => $query->where('seccion', $seccion));
    }

    private function validateLona(Request $request, bool $photoRequired): array
    {
        $request->merge([
            'ubicacion_google' => $this->googleMapsUrlParser->normalize(
                $request->input('ubicacion_google')
            ),
        ]);

        $photoRules = [$photoRequired ? 'required' : 'nullable', 'file', 'max:9216', function ($attribute, $value, $fail) {
            if (!$value) {
                return;
            }

            $extension = strtolower($value->getClientOriginalExtension());
            $mime = strtolower((string) $value->getMimeType());
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'bmp', 'heic', 'heif'];
            $allowedMimes = [
                'image/jpeg', 'image/png', 'image/webp', 'image/gif', 'image/bmp',
                'image/x-ms-bmp', 'image/heic', 'image/heif', 'application/octet-stream',
            ];

            if (!in_array($extension, $allowedExtensions, true) || !in_array($mime, $allowedMimes, true)) {
                $fail('La foto debe ser JPG, PNG, WebP, GIF, BMP, HEIC o HEIF.');
            }
        }];

        $validated = $request->validate([
            'seccion' => ['required', 'string', 'max:10'],
            'direccion' => ['required', 'string', 'max:500'],
            'ubicacion_google' => ['nullable', 'url', 'max:2000'],
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
            'responsable' => ['required', 'string', 'max:150'],
            'foto' => $photoRules,
        ], [
            'lat.required' => 'Selecciona la ubicación en el mapa.',
            'lng.required' => 'Selecciona la ubicación en el mapa.',
            'ubicacion_google.url' => 'La ubicación de Google Maps debe ser un enlace válido.',
            'foto.max' => 'La foto procesada no debe superar 9 MB. Espera a que termine la compresión e inténtalo de nuevo.',
        ]);

        unset($validated['foto']);

        $googleCoordinates = $this->googleMapsUrlParser->coordinates(
            $validated['ubicacion_google'] ?? null
        );
        if ($googleCoordinates) {
            [$validated['lat'], $validated['lng']] = $googleCoordinates;
        }

        if (empty($validated['ubicacion_google'])) {
            $validated['ubicacion_google'] = sprintf(
                'https://www.google.com/maps?q=%s,%s',
                $validated['lat'],
                $validated['lng']
            );
        }

        return $validated;
    }
}
