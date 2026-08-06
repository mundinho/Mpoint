<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Participacao extends Model
{
    protected $table = 'participacao';

    protected $fillable = [
        'campanha_id',
        'usuario_id',
        'quadrado_id',
        'numero',
        'resultado',
        'premio_id',
    ];

    public function campanha()
    {
        return $this->belongsTo(Campanha::class);
    }

    public function usuario()
    {
        return $this->belongsTo(Usuario::class);
    }

    public function quadrado()
    {
        return $this->belongsTo(Quadrado::class);
    }

    public function premio()
    {
        return $this->belongsTo(Premio::class);
    }
}