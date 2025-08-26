<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AfiliadoResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'      => $this->id,
            'nombre'  => $this->nombre,
            'apellido_paterno' => $this->apellido_paterno,
            'apellido_materno' => $this->apellido_materno,
            'telefono'=> $this->telefono,
            'email'   => $this->email,
            'municipio'=> $this->municipio,
            'cve_mun' => $this->cve_mun,
            'seccion' => $this->seccion,
            'estatus' => $this->estatus,
            'lat'     => $this->lat,
            'lng'     => $this->lng,
            'created_at' => $this->created_at,
        ];
    }
}
