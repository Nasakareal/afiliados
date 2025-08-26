<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SeccionResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'               => $this->id,
            'seccion'          => $this->seccion,
            'municipio'        => $this->municipio,
            'cve_mun'          => $this->cve_mun,
            'lista_nominal'    => $this->lista_nominal,
            'distrito_local'   => $this->distrito_local,
            'distrito_federal' => $this->distrito_federal,
            'centroid_lat'     => $this->centroid_lat,
            'centroid_lng'     => $this->centroid_lng,
            'created_at'       => $this->created_at,
            'updated_at'       => $this->updated_at,
        ];
    }
}
