<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

class Comunicado extends Model
{
    use SoftDeletes;

    protected $table = 'comunicados';

    protected $fillable = [
        'creado_por',
        'titulo',
        'contenido',
        'visible_desde',
        'visible_hasta',
        'estado',       // borrador | publicado | archivado
        'filtros',      // JSON
    ];

    protected $casts = [
        'visible_desde' => 'datetime',
        'visible_hasta' => 'datetime',
        'filtros'       => 'array',
    ];

    /* ================= Relaciones ================= */

    public function creador()
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    public function lectores()
    {
        // Pivot: comunicado_user (comunicado_id, user_id, leido_at, timestamps)
        return $this->belongsToMany(User::class, 'comunicado_user', 'comunicado_id', 'user_id')
                    ->withPivot(['leido_at'])
                    ->withTimestamps();
    }

    /* ================= Scopes ================= */

    /** Solo estado = publicado */
    public function scopePublicados(Builder $q): Builder
    {
        return $q->where('estado', 'publicado');
    }

    /** Dentro de la ventana de vigencia (no valida estado) */
    public function scopeVigentes(Builder $q): Builder
    {
        $now = now();
        return $q->where(function ($w) use ($now) {
                    $w->whereNull('visible_desde')->orWhere('visible_desde', '<=', $now);
                })->where(function ($w) use ($now) {
                    $w->whereNull('visible_hasta')->orWhere('visible_hasta', '>=', $now);
                });
    }

    /** Comunicados que el usuario AÚN NO ha marcado como leídos */
    public function scopeNoLeidosPara(Builder $q, int $userId): Builder
    {
        return $q->whereDoesntHave('lectores', fn($r) => $r->where('user_id', $userId));
    }

    /** Comunicados que el usuario YA marcó como leídos */
    public function scopeLeidosPara(Builder $q, int $userId): Builder
    {
        return $q->whereHas('lectores', fn($r) => $r->where('user_id', $userId));
    }

    /* ================= Helpers ================= */

    /** Atributo calculado: ¿está vigente y publicado? */
    public function getEstaVigenteAttribute(): bool
    {
        $now     = now();
        $desdeOk = is_null($this->visible_desde) || $this->visible_desde->lte($now);
        $hastaOk = is_null($this->visible_hasta) || $this->visible_hasta->gte($now);
        return $this->estado === 'publicado' && $desdeOk && $hastaOk;
    }

    /** Marca como leído para un usuario (sin romper si ya existe) */
    public function marcarLeidoPor(int $userId): void
    {
        $this->lectores()->syncWithoutDetaching([
            $userId => ['leido_at' => now()],
        ]);
    }

    /** ¿Este comunicado ya fue leído por el usuario dado? */
    public function fueLeidoPor(int $userId): bool
    {
        return $this->lectores()->where('user_id', $userId)->exists();
    }
}
