<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Comunicado;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ComunicadoController extends Controller
{
    public function index(Request $request)
    {
        $q        = trim((string) $request->query('q'));
        $estado   = $request->query('estado');
        $vigentes = $request->boolean('vigentes');

        $comunicados = Comunicado::query()
            ->when($q !== '', function ($qb) use ($q) {
                $qb->where(function ($w) use ($q) {
                    $w->where('titulo', 'like', "%{$q}%")
                      ->orWhere('contenido', 'like', "%{$q}%");
                });
            })
            ->when($estado, fn ($qb) => $qb->where('estado', $estado))
            ->when($vigentes, fn ($qb) => $qb->vigentes())
            ->with('creador')
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('settings.comunicados.index', compact('comunicados', 'q', 'estado', 'vigentes'));
    }

    public function create()
    {
        return view('comunicados.create');
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        $data['creado_por'] = Auth::id();
        $comunicado = Comunicado::create($data);

        return redirect()
            ->route('settings.comunicados.show', $comunicado)
            ->with('status', 'Comunicado creado.');
    }

    public function show(Comunicado $comunicado)
    {
        $comunicado->load('creador');
        return view('settings.comunicados.show', compact('comunicado'));
    }

    public function edit(Comunicado $comunicado)
    {
        return view('settings.comunicados.edit', compact('comunicado'));
    }

    public function update(Request $request, Comunicado $comunicado)
    {
        $data = $this->validateData($request);
        $comunicado->update($data);

        return redirect()
            ->route('settings.comunicados.show', $comunicado)
            ->with('status', 'Comunicado actualizado.');
    }

    public function destroy(Comunicado $comunicado)
    {
        $comunicado->delete();

        return redirect()
            ->route('settings.comunicados.index')
            ->with('status', 'Comunicado eliminado.');
    }

    // Marca como leído por el usuario autenticado
    public function marcarLeido(Comunicado $comunicado)
    {
        $comunicado->marcarLeidoPor(Auth::id());

        return back()->with('status', 'Comunicado leído.');
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'titulo'        => ['required', 'string', 'max:180'],
            'contenido'     => ['required', 'string'],
            'visible_desde' => ['nullable', 'date'],
            'visible_hasta' => ['nullable', 'date', 'after_or_equal:visible_desde'],
            'estado'        => ['required', 'in:borrador,publicado,archivado'],
            'filtros'       => ['nullable'],
        ]);
    }
}
