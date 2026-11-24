<?php

namespace App\Services;

use App\Models\Brigada;
use App\Models\BrigadaParticipante;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class BrigadaParticipationService
{
    public function register(Brigada $brigada, int $userId, UploadedFile $photo, ?string $notes): BrigadaParticipante
    {
        if (!$brigada->activo) {
            throw ValidationException::withMessages(['brigada' => 'Esta brigada ya no acepta asistencias.']);
        }
        $path = $photo->store('brigadas/evidencias', 'local');

        try {
            return DB::transaction(function () use ($brigada, $userId, $photo, $notes, $path) {
                $locked = Brigada::query()->whereKey($brigada->id)->lockForUpdate()->firstOrFail();
                if (!$locked->activo) {
                    throw ValidationException::withMessages(['brigada' => 'Esta brigada ya no acepta asistencias.']);
                }
                if ($locked->participantes()->where('user_id', $userId)->exists()) {
                    throw ValidationException::withMessages(['foto' => 'Ya registraste tu asistencia en esta brigada.']);
                }
                return $locked->participantes()->create([
                    'user_id' => $userId, 'asistio_en' => now(), 'evidencia_path' => $path,
                    'evidencia_nombre' => $photo->getClientOriginalName(), 'observaciones' => $notes,
                ]);
            });
        } catch (\Throwable $error) {
            Storage::disk('local')->delete($path);
            throw $error;
        }
    }
}
