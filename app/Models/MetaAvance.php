<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MetaAvance extends Model
{
    use HasFactory;

    public const TIPO_NACIONAL = 'nacional';
    public const TIPO_ESTATAL = 'estatal';

    protected $fillable = [
        'tipo',
        'cve_mun',
        'meta',
        'fecha_inicio',
        'fecha_fin',
        'activa',
        'asignado_por',
    ];

    protected $casts = [
        'meta' => 'integer',
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
        'activa' => 'boolean',
        'asignado_por' => 'integer',
    ];

    public function asignador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'asignado_por');
    }

    public static function tipos(): array
    {
        return [
            self::TIPO_NACIONAL => 'Meta nacional',
            self::TIPO_ESTATAL => 'Meta estatal',
        ];
    }
}
