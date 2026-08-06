<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Quadrado extends Model
{
    protected $table = 'quadrado';

    protected $fillabel = [
        'campanha_id',
        'numero',
        'premio_id',
        'estado',
        'aberto_por',
        'aberto_em',
    ] 
}