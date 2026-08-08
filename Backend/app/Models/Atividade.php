<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Atividade extends Model
{
    protected $table = 'atividade';

    protected $fillable = [
        'campanha_id',
        'tipo',
        'usuario_id',
        'numero',
        'premio_id',
        'descricao',
    ];

    public function usuario()
    {
        return $this->belongsTo(Usuario::class);
    }

    public function premio()
    {
        return $this->belongsTo(Premio::class);
    }
}
