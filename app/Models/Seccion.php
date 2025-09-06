<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Seccion extends Model
{
    protected $table = 'secciones';

    protected $fillable = [
        'cve_ent','cve_mun','municipio','seccion',
        'distrito_federal','distrito_local',
        'lista_nominal',
        'centroid_lat','centroid_lng',
    ];

    protected $casts = [
        'lista_nominal' => 'integer',
        'centroid_lat'  => 'float',
        'centroid_lng'  => 'float',
        'distrito_federal' => 'integer',
        'distrito_local'   => 'integer',
    ];

    public function afiliados()
    {
        return $this->hasMany(Afiliado::class, 'seccion', 'seccion')
                    ->where('municipio', $this->municipio);
    }

    // Helper para % de convencidos en esta sección (requiere lista_nominal)
    public function porcentajeConvencidos(): ?float
    {
        if (!$this->lista_nominal || $this->lista_nominal <= 0) return null;
        $total = $this->afiliados()->count();
        return $total > 0 ? round(($total / $this->lista_nominal) * 100, 2) : 0.0;
    }
}
