<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SeccionPrioritaria extends Model
{
    protected $table = 'secciones_prioritarias';

    protected $fillable = [
        'cve_mun',
        'seccion',
        'prioridad',
        'meta_convencidos',
        'meta_lonas',
        'asignado_por',
    ];

    protected $casts = [
        'prioridad' => 'integer',
        'meta_convencidos' => 'integer',
        'meta_lonas' => 'integer',
    ];
}
