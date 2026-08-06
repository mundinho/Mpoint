<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Participacao extends Model
{
    protected $table = 'participacao';

    protected $fillabel = [
        'id',
        'campanha_id',
        'usuario_id',
        'numero',
        'resultado',
        'premio_id',
    ] 
}