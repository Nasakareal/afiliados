<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BrigadaParticipante extends Model
{
    use HasFactory;

    protected $table = 'brigada_participantes';

    protected $fillable = [
        'brigada_id',
        'user_id',
        'asistio_en',
        'evidencia_path',
        'evidencia_nombre',
        'observaciones',
    ];

    protected $casts = [
        'asistio_en' => 'datetime',
    ];

    public function brigada()
    {
        return $this->belongsTo(Brigada::class);
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
