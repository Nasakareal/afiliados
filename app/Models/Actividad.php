<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Actividad extends Model
{
    protected $table = 'actividades';

    protected $fillable = [
        'titulo','descripcion',
        'inicio','fin','all_day',
        'lugar',
        'creado_por',
        'estado',
    ];

    protected $casts = [
        'inicio'  => 'datetime',
        'fin'     => 'datetime',
        'all_day' => 'boolean',
    ];

    public function creador()
    {
        return $this->belongsTo(User::class, 'creado_por');
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
