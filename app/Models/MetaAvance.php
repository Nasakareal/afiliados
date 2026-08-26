<?php

namespace App\Models;

use App\Models\Concerns\RestrictsToLocalDistrict;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MetaAvance extends Model
{
    use RestrictsToLocalDistrict;

    use HasFactory;

    public const TIPO_CONVENCIDOS = 'convencidos';
    public const TIPO_LONAS = 'lonas';

    protected $fillable = [
        'tipo',
        'cve_mun',
        'distrito_local',
        'meta',
        'fecha_inicio',
        'fecha_fin',
        'activa',
        'asignado_por',
    ];

    protected $casts = [
        'meta' => 'integer',
        'distrito_local' => 'integer',
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
            self::TIPO_CONVENCIDOS => 'Meta de convencidos',
            self::TIPO_LONAS => 'Meta de lonas',
        ];
    }
}
