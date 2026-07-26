<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lona;
use App\Services\LonaPhotoProcessor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class LonaApiController extends Controller
{
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
        }

        return response()->json($this->payload($lona->fresh()->load('capturista:id,name')));
    }

    public function destroy(Lona $lona)
    {
        $photoPath = $lona->foto_path;
        $lona->forceDelete();
        Storage::disk('local')->delete($photoPath);

        return response()->json(['ok' => true]);
    }

    public function mapData()
    {
        $lonas = Lona::with('capturista:id,name')
            ->latest()
            ->get()
            ->map(fn (Lona $lona) => $this->payload($lona));

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

    private function validateLona(Request $request, bool $photoRequired): array
    {
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
