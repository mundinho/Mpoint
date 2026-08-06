<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sms extends Model
{
    protected $table = 'sms';

    protected $fillable = [
        'usuario_id',
        'tipo',
        'mesnagem',
        'estado',
        'enviado_em',
    ];
}