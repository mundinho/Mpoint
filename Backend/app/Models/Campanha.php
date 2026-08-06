<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Campanha extends Model
{
    protected $table = 'campanha';


    
    protected $fillable = [
        'id',
        'total_quadrados',
        'total_premios',
        'estado',
        'data_inicio',
    ];
}

