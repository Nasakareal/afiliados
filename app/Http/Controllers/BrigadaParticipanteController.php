<?php

namespace App\Http\Controllers;

use App\Models\Brigada;
use App\Models\BrigadaParticipante;
use App\Services\BrigadaParticipationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BrigadaParticipanteController extends Controller
{
    public function store(Request $request, Brigada $brigada, BrigadaParticipationService $service)
    {
        $data = $request->validate([
            'foto' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:9216'],
            'observaciones' => ['nullable', 'string', 'max:2000'],
        ], ['foto.required' => 'Toma o selecciona una foto para comprobar tu asistencia.', 'foto.max' => 'La foto no debe pesar más de 9 MB.']);

        $service->register($brigada, $request->user()->id, $request->file('foto'), $data['observaciones'] ?? null);
        return redirect()->route('brigadas.show', $brigada)->with('success', 'Tu asistencia quedó registrada correctamente.');
    }

    public function evidencia(Request $request, Brigada $brigada, BrigadaParticipante $participante)
    {
        abort_unless((int) $participante->brigada_id === (int) $brigada->id, 404);
        abort_unless((int) $participante->user_id === (int) $request->user()->id || $request->user()->can('brigadas.participantes.ver'), 403);
        abort_unless(Storage::disk('local')->exists($participante->evidencia_path), 404);

        return response()->file(Storage::disk('local')->path($participante->evidencia_path), [
            'Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
