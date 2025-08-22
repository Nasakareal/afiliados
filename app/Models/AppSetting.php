<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppSetting extends Model
{
    protected $table = 'app_settings';

    protected $fillable = [
        'captura_habilitada',
        'motivo_bloqueo',
    ];

    protected $casts = [
        'captura_habilitada' => 'boolean',
    ];

    // Sugerencia: manejar como singleton (primera fila)
    public static function firstOrDefaults(): self
    {
        return static::query()->first() ?? new static(['captura_habilitada' => true]);
    }

    public static function capturaActiva(): bool
    {
        return (bool) optional(static::first())->captura_habilitada ?? true;
    }
}
