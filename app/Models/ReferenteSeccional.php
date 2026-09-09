<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReferenteSeccional extends Model
{
    use HasFactory;

    protected $table = 'referentes_seccionales';

    protected $fillable = [
        'cve_mun',
        'municipio',
        'seccion',
        'posicion',
        'distrito_local',
        'distrito_federal',
        'nombre_completo',
        'telefono',
        'telefono_alternativo',
        'correo',
        'cargo',
        'organizacion',
        'localidad',
        'colonia',
        'direccion',
        'whatsapp',
        'activo',
        'observaciones',
    ];

    protected $casts = [
        'posicion' => 'integer',
        'distrito_local' => 'integer',
        'distrito_federal' => 'integer',
        'whatsapp' => 'boolean',
        'activo' => 'boolean',
    ];
}
