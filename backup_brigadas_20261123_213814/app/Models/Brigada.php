<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Brigada extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'brigadas';

    protected $fillable = [
        'seccion_id',
        'responsable',
        'telefono',
        'direccion',
        'ubicacion_google',
        'lat',
        'lng',
        'activo',
        'observaciones',
        'capturado_por',
    ];

    protected $casts = [
        'lat' => 'decimal:7',
        'lng' => 'decimal:7',
        'activo' => 'boolean',
    ];

    public function seccion()
    {
        return $this->belongsTo(Seccion::class, 'seccion_id');
    }

    public function capturista()
    {
        return $this->belongsTo(User::class, 'capturado_por');
    }

    public function getGoogleMapsUrlAttribute(): string
    {
        return 'https://www.google.com/maps/search/?api=1&query='
            . $this->lat . ',' . $this->lng;
    }
}
