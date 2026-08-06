<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Utilizador extends Model
{
    protected $table = 'utilizador';

    protected $fillable = [
        'id',
        'utilizador_nome',
        'nome_completo',
        'password_hash',
        'password_salt',
        'criado_em',
        'atualizado_em',
        'ativo',
    ];
}