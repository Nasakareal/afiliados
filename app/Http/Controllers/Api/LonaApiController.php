<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lona;
use App\Services\GoogleMapsUrlParser;
use App\Services\LonaPhotoProcessor;
use App\Support\LocalDistrictAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class LonaApiController extends Controller
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

        $lonas = Lona::with('capturista:id,name')
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($where) use ($q) {
                    $where->where('direccion', 'like', "%{$q}%")
                        ->orWhere('responsable', 'like', "%{$q}%");
                });
            })
            ->when($seccion !== '', fn ($query) => $query->where('seccion', $seccion))
            ->latest()
            ->paginate(20);

        $lonas->getCollection()->transform(fn (Lona $lona) => $this->payload($lona));

        return response()->json($lonas);
    }

    public function store(Request $request, LonaPhotoProcessor $photoProcessor)
    {
        $data = $this->validateLona($request, true);
        if (!LocalDistrictAccess::sectionIsAllowed($data['seccion'], $request->user())) {
            abort(403, 'No tienes acceso a esa sección.');
        }
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
        } catch (\Throwable $error) {
            Storage::disk('local')->delete($photo['path']);
            throw $error;
        }

        return response()->json($this->payload($lona->load('capturista:id,name')), 201);
    }

    public function show(Lona $lona)
    {
        return response()->json($this->payload($lona->load('capturista:id,name')));
    }

    public function update(Request $request, Lona $lona, LonaPhotoProcessor $photoProcessor)
    {
        $data = $this->validateLona($request, false);
        if (!LocalDistrictAccess::sectionIsAllowed($data['seccion'], $request->user())) {
            abort(403, 'No tienes acceso a esa sección.');
        }
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
        } catch (\Throwable $error) {
            if ($newPhoto) {
                Storage::disk('local')->delete($newPhoto['path']);
            }
            throw $error;
        }

        if ($newPhoto && $oldPath !== $newPhoto['path']) {
            Storage::disk('local')->delete($oldPath);
            Storage::disk('local')->delete($this->feedPhotoPath($lona));
        }

        return response()->json($this->payload($lona->fresh()->load('capturista:id,name')));
    }

    public function destroy(Lona $lona)
    {
        $photoPath = $lona->foto_path;
        $lona->forceDelete();
        Storage::disk('local')->delete($photoPath);
        Storage::disk('local')->delete($this->feedPhotoPath($lona));

        return response()->json(['ok' => true]);
    }

    public function mapData(Request $request)
    {
        $limit = min(300, max(25, (int) $request->query('limit', 180)));
        $bbox = array_map('floatval', explode(',', (string) $request->query('bbox', '')));

        $query = Lona::query()
            ->select([
                'id', 'seccion', 'direccion', 'responsable', 'lat', 'lng',
                'foto_path', 'capturado_por', 'created_at',
            ])
            ->with('capturista:id,name')
            ->whereNotNull('lat')
            ->whereNotNull('lng');

        if (count($bbox) === 4) {
            [$minLng, $minLat, $maxLng, $maxLat] = $bbox;
            if ($minLng < $maxLng && $minLat < $maxLat) {
                $query->whereBetween('lat', [$minLat, $maxLat])
                    ->whereBetween('lng', [$minLng, $maxLng]);
            }
        }

        $lonas = $query
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn (Lona $lona) => $this->mapPayload($lona));

        return response()->json($lonas);
    }

    public function photo(Request $request, Lona $lona)
    {
        if (!$lona->foto_path || !Storage::disk('local')->exists($lona->foto_path)) {
            abort(404);
        }

        $path = $lona->foto_path;
        if ($request->query('variant') === 'feed') {
            $path = $this->ensureFeedPhoto($lona) ?: $path;
        }

        return response()->file(
            Storage::disk('local')->path($path),
            [
                'Content-Type' => 'image/jpeg',
                'Cache-Control' => 'private, max-age=604800',
                'X-Content-Type-Options' => 'nosniff',
            ]
        );
    }

    private function ensureFeedPhoto(Lona $lona): ?string
    {
        $targetPath = $this->feedPhotoPath($lona);
        if (Storage::disk('local')->exists($targetPath)) {
            return $targetPath;
        }

        $raw = @file_get_contents(Storage::disk('local')->path($lona->foto_path));
        $source = $raw === false ? false : @imagecreatefromstring($raw);
        unset($raw);
        if (!$source) {
            return null;
        }

        $sourceWidth = imagesx($source);
        $sourceHeight = imagesy($source);
        $scale = min(1, 960 / max($sourceWidth, $sourceHeight));
        $width = max(1, (int) round($sourceWidth * $scale));
        $height = max(1, (int) round($sourceHeight * $scale));
        $target = imagecreatetruecolor($width, $height);
        $white = imagecolorallocate($target, 255, 255, 255);
        imagefill($target, 0, 0, $white);
        imagecopyresampled(
            $target,
            $source,
            0,
            0,
            0,
            0,
            $width,
            $height,
            $sourceWidth,
            $sourceHeight
        );

        ob_start();
        imagejpeg($target, null, 68);
        $jpeg = ob_get_clean();
        imagedestroy($target);
        imagedestroy($source);

        if ($jpeg === false || !Storage::disk('local')->put($targetPath, $jpeg)) {
            return null;
        }

        return $targetPath;
    }

    private function feedPhotoPath(Lona $lona): string
    {
        return 'lonas/feed/' . $lona->id . '.jpg';
    }

    private function payload(Lona $lona): array
    {
        return [
            'id' => $lona->id,
            'seccion' => $lona->seccion,
            'direccion' => $lona->direccion,
            'responsable' => $lona->responsable,
            'ubicacion_google' => $lona->ubicacion_google,
            'lat' => (float) $lona->lat,
            'lng' => (float) $lona->lng,
            'foto_url' => route('api.lonas.photo', $lona),
            'foto_bytes_final' => $lona->foto_bytes_final,
            'capturista' => $lona->relationLoaded('capturista') && $lona->capturista
                ? ['id' => $lona->capturista->id, 'name' => $lona->capturista->name]
                : null,
            'created_at' => optional($lona->created_at)->toIso8601String(),
            'updated_at' => optional($lona->updated_at)->toIso8601String(),
        ];
    }

    private function mapPayload(Lona $lona): array
    {
        return [
            'id' => $lona->id,
            'seccion' => $lona->seccion,
            'direccion' => $lona->direccion,
            'responsable' => $lona->responsable,
            'lat' => (float) $lona->lat,
            'lng' => (float) $lona->lng,
            'foto_url' => route('api.lonas.photo', $lona),
            'capturista' => $lona->relationLoaded('capturista') && $lona->capturista
                ? ['id' => $lona->capturista->id, 'name' => $lona->capturista->name]
                : null,
            'created_at' => optional($lona->created_at)->toIso8601String(),
        ];
    }

    private function validateLona(Request $request, bool $photoRequired): array
    {
        $request->merge([
            'ubicacion_google' => $this->googleMapsUrlParser->normalize(
                $request->input('ubicacion_google')
            ),
        ]);

        $validated = $request->validate([
            'seccion' => ['required', 'string', 'max:10'],
            'direccion' => ['required', 'string', 'max:500'],
            'ubicacion_google' => ['nullable', 'url', 'max:2000'],
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
            'responsable' => ['required', 'string', 'max:150'],
            'foto' => [
                $photoRequired ? 'required' : 'nullable',
                'file',
                'mimes:jpg,jpeg,png,webp,gif,bmp,heic,heif',
                'max:9216',
            ],
        ], [
            'lat.required' => 'Selecciona la ubicación en el mapa.',
            'lng.required' => 'Selecciona la ubicación en el mapa.',
            'foto.max' => 'La foto no debe superar 9 MB.',
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
