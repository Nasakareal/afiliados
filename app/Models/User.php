<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    protected $fillable = ['name','email','password','distrito_local'];

    protected $hidden = ['password','remember_token'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'must_change_password' => 'boolean',
        'password_changed_at' => 'datetime',
        'distrito_local' => 'integer',
    ];

    // === Relaciones de conveniencia ===
    public function afiliadosCapturados()
    {
        return $this->hasMany(Afiliado::class, 'capturista_id');
    }

    public function actividadesCreadas()
    {
        return $this->hasMany(Actividad::class, 'creado_por');
    }

    public function lonasCapturadas()
    {
        return $this->hasMany(Lona::class, 'capturado_por');
    }

    public function localDistrictAssignments()
    {
        return $this->hasMany(UserLocalDistrict::class)->orderBy('distrito_local');
    }

    public function localDistrictNumbers(): array
    {
        $districts = $this->localDistrictAssignments
            ->pluck('distrito_local')
            ->map(fn ($district) => (int) $district)
            ->unique()
            ->sort()
            ->values()
            ->all();

        // Compatibilidad durante despliegues donde todavía sólo existe la columna anterior.
        if (!$districts && $this->distrito_local !== null) {
            return [(int) $this->distrito_local];
        }

        return $districts;
    }
}
