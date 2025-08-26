<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ActividadResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'          => $this->id,
            'titulo'      => $this->titulo,
            'descripcion' => $this->descripcion,
            'inicio'      => optional($this->inicio)->toIso8601String(),
            'fin'         => optional($this->fin)->toIso8601String(),
            'all_day'     => (bool) $this->all_day,
            'lugar'       => $this->lugar,
            'estado'      => $this->estado,
            'creado_por'  => $this->creado_por,
            'created_at'  => $this->created_at,
            'updated_at'  => $this->updated_at,
        ];
    }
}
