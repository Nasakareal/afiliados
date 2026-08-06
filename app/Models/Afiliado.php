<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Afiliado extends Model
{
    use SoftDeletes;

    public const TIPOS_VINCULO = [
        'dv' => 'DV',
        'comite' => 'Comité',
        'mov' => 'MOV',
    ];

    protected $fillable = [
        'capturista_id',
        'nombre','apellido_paterno','apellido_materno',
        'edad','sexo',
        'telefono','email','clave_elector','tipo_vinculo','numero_mov',
        'municipio','cve_mun','localidad','colonia','calle','numero_ext','numero_int','cp',
        'lat','lng',
        'seccion','distrito_federal','distrito_local',
        'perfil','observaciones',
        'estatus','fecha_convencimiento',
    ];

    protected $casts = [
        'edad' => 'integer',
        'lat'  => 'float',
        'lng'  => 'float',
        'fecha_convencimiento' => 'datetime',
    ];

    public function setClaveElectorAttribute($value): void
    {
        $normalizada = preg_replace('/\s+/', '', mb_strtoupper(trim((string) $value), 'UTF-8'));
        $this->attributes['clave_elector'] = $normalizada !== '' ? $normalizada : null;
    }

    public function capturista()
    {
        return $this->belongsTo(User::class,'capturista_id');
    }

    // 🔧 Relación a Seccion por (seccion,cve_mun)
    public function seccion()
    {
        return $this->belongsTo(Seccion::class,'seccion','seccion')
                    ->whereColumn('afiliados.cve_mun','secciones.cve_mun');
    }

    // === Scopes ===
    public function scopeSecciones($q, $secciones)
    {
        $vals = is_array($secciones) ? $secciones : explode(',', (string)$secciones);
        return $q->whereIn('seccion', array_filter(array_map('trim', $vals)));
    }

    public function scopeMunicipios($q, $municipios)
    {
        $vals = is_array($municipios) ? $municipios : explode(',', (string)$municipios);
        return $q->whereIn('municipio', array_filter(array_map('trim', $vals)));
    }

    public function scopeCapturistaId($q, $userId)
    {
        return $q->where('capturista_id',$userId);
    }

    public function scopeEstatus($q, $estatus)
    {
        return $q->where('estatus',$estatus);
    }
}
