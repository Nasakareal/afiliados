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

    protected $fillable = ['name','email','password'];

    protected $hidden = ['password','remember_token'];

    protected $casts = [
        'email_verified_at' => 'datetime',
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
}
