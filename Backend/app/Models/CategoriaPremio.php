<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CategoriaPremio extends Model
{
    protected $table = 'categorias_premio';

    protected $fillable = [
        'nome',
        'tipo',
    ];
}
