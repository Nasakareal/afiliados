<?php

namespace App\Models;

use App\Models\Concerns\RestrictsToLocalDistrict;
use Illuminate\Database\Eloquent\Model;

class Actividad extends Model
{
    use RestrictsToLocalDistrict;

    protected $table = 'actividades';

    protected $fillable = [
        'titulo','tipo','origen','descripcion',
        'inicio','fin','all_day',
        'lugar','colonia',
        'creado_por','responsable_id','capturista_id','distrito_local',
        'asistentes','evidencia_url','evidencia_path','evidencia_nombre','capturada_en','demo_batch',
        'estado','estado_revision','revisado_por','revisado_en',
    ];

    protected $casts = [
        'inicio'  => 'datetime',
        'fin'     => 'datetime',
        'all_day' => 'boolean',
        'distrito_local' => 'integer',
        'asistentes' => 'integer',
        'capturada_en' => 'datetime',
        'revisado_en' => 'datetime',
    ];

    public function creador()
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    public function responsable()
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }

    public function capturista()
    {
        return $this->belongsTo(User::class, 'capturista_id');
    }

    public function afiliados()
    {
        return $this->hasMany(Afiliado::class, 'actividad_id');
    }

    public function participantes()
    {
        return $this->belongsToMany(User::class, 'actividad_participantes')
            ->withPivot('asistio_en')->withTimestamps();
    }

    public function scopeEstado($q, $estado)
    {
        return $q->where('estado', $estado);
    }

    public function scopeEntreFechas($q, $desde, $hasta)
    {
        return $q->whereNotNull('inicio')
            ->where(function($qq) use ($desde, $hasta) {
                $qq->whereBetween('inicio', [$desde, $hasta])
                   ->orWhereBetween('fin', [$desde, $hasta])
                   ->orWhere(function($q3) use ($desde, $hasta) {
                        $q3->where('inicio', '<=', $desde)
                           ->where(function($q4) use ($hasta){
                               $q4->whereNull('fin')->orWhere('fin', '>=', $hasta);
                           });
                   });
            });
    }
}
