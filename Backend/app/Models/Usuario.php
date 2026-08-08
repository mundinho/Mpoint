<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Usuario extends Model
{
    protected $table = 'usuarios';

    protected $fillable = [
        'nome',
        'telefone',
        'telefone_verificado',
        'tentativas_extra',
    ];

    protected $casts = [
        'telefone_verificado' => 'boolean',
    ];

    public function otps()
    {
        return $this->hasMany(Otp::class);
    }

    public function participacoes()
    {
        return $this->hasMany(Participacao::class);
    }
}

