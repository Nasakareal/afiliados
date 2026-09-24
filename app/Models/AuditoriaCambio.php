<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditoriaCambio extends Model
{
    protected $table = 'auditoria_cambios';

    protected $fillable = [
        'usuario_id', 'entidad', 'entidad_id', 'accion', 'resumen',
        'cambios', 'demo_batch',
    ];

    protected $casts = [
        'cambios' => 'array',
    ];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
