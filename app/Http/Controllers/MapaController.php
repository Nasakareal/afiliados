<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Afiliado;

class MapaController extends Controller
{
    public function index()
    {
        // Contar afiliados por municipio
        $conteo = Afiliado::selectRaw('municipio, COUNT(*) as total')
            ->groupBy('municipio')
            ->pluck('total','municipio');

        return view('mapa.index', compact('conteo'));
    }

    public function data()
    {
        // Todos los afiliados con coordenadas (para marcadores)
        return Afiliado::whereNotNull('lat')
            ->whereNotNull('lng')
            ->get(['id','nombre','apellido_paterno','apellido_materno','municipio','lat','lng']);
    }
}
