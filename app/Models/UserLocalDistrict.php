<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserLocalDistrict extends Model
{
    protected $fillable = ['distrito_local'];

    protected $casts = [
        'distrito_local' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
