<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Administrador extends Model
{
    public const SESSION_MINUTES = 45;

    protected $table = 'administradores';

    protected $fillable = [
        'nome',
        'telefone',
        'ativo',
        'api_token',
        'token_expira_em',
    ];

    protected $hidden = [
        'api_token',
        'token_expira_em',
    ];

    protected $casts = [
        'ativo' => 'boolean',
        'token_expira_em' => 'datetime',
    ];

    public function otps()
    {
        return $this->hasMany(AdministradorOtp::class);
    }
}
