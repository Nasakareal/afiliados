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
            'edad' => $this->edad,
            'sexo' => $this->sexo,
            'telefono'=> $this->telefono,
            'email'   => $this->email,
            'clave_elector' => $this->clave_elector,
            'tipo_vinculo' => $this->tipo_vinculo,
            'numero_mov' => $this->numero_mov,
            'municipio'=> $this->municipio,
            'cve_mun' => $this->cve_mun,
            'localidad' => $this->localidad,
            'colonia' => $this->colonia,
            'calle' => $this->calle,
            'numero_ext' => $this->numero_ext,
            'numero_int' => $this->numero_int,
            'cp' => $this->cp,
            'seccion' => $this->seccion,
            'distrito_federal' => $this->distrito_federal,
            'distrito_local' => $this->distrito_local,
            'perfil' => $this->perfil,
            'observaciones' => $this->observaciones,
            'estatus' => $this->estatus,
            'fecha_convencimiento' => $this->fecha_convencimiento,
            'lat'     => $this->lat,
            'lng'     => $this->lng,
            'created_at' => $this->created_at,
        ];
    }
}
