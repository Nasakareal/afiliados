<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lona extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'seccion',
        'direccion',
        'ubicacion_google',
        'lat',
        'lng',
        'foto_path',
        'foto_nombre_original',
        'foto_bytes_original',
        'foto_bytes_final',
        'responsable',
        'capturado_por',
    ];

    protected $casts = [
        'lat' => 'float',
        'lng' => 'float',
        'foto_bytes_original' => 'integer',
        'foto_bytes_final' => 'integer',
    ];

    public function capturista()
    {
        return $this->belongsTo(User::class, 'capturado_por');
    }
}
