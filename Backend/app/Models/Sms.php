<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sms extends Model
{
    protected $table = 'sms';

    protected $fillabel = [
        'id',
        'usuario_id',
        'tipo',
        'mesnagem',
        'estado',
        'enviado_em',
    ]
}