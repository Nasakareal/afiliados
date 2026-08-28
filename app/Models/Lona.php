<?php

namespace App\Models;

use App\Support\LocalDistrictAccess;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lona extends Model
{
    use HasFactory, SoftDeletes;

    protected static function booted(): void
    {
        static::addGlobalScope('local_district', function (Builder $builder) {
            $districts = LocalDistrictAccess::districts();
            if (!$districts) {
                return;
            }

            $builder->whereExists(function ($query) use ($builder, $districts) {
                $query->selectRaw('1')
                    ->from('secciones as lona_district_sections')
                    ->whereColumn(
                        'lona_district_sections.seccion',
                        $builder->getModel()->qualifyColumn('seccion')
                    )
                    ->whereIn('lona_district_sections.distrito_local', $districts);
            });
        });
    }

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
        'capturado_por' => 'integer',
        'foto_bytes_original' => 'integer',
        'foto_bytes_final' => 'integer',
    ];

    public function capturista()
    {
        return $this->belongsTo(User::class, 'capturado_por');
    }
}
