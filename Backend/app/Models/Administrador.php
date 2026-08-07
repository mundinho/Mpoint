<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Administrador extends Model
{
    protected $table = 'administradores';

    protected $fillable = [
        'nome',
        'telefone',
        'ativo',
        'api_token',
    ];

    protected $hidden = [
        'api_token',
    ];

    protected $casts = [
        'ativo' => 'boolean',
    ];

    public function otps()
    {
        return $this->hasMany(AdministradorOtp::class);
    }
}
