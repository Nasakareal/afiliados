<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReferenteMunicipal extends Model
{
    use HasFactory;

    protected $table = 'referentes_municipales';

    protected $fillable = [
        'cve_mun',
        'municipio',
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
        'whatsapp' => 'boolean',
        'activo' => 'boolean',
    ];
}
