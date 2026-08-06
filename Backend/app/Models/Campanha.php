<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Campanha extends Model
{
    protected $table = 'campanha';

    protected $fillable = [
        'total_quadrados',
        'total_premios',
        'estado',
        'data_inicio',
    ];

    public function quadrados()
    {
        return $this->hasMany(Quadrado::class);
    }

    public function premios()
    {
        return $this->hasMany(Premio::class);
    }

    public function participacoes()
    {
        return $this->hasMany(Participacao::class);
    }

    public static function ativa(): ?self
    {
        return static::where('estado', 'ativa')->latest('id')->first();
    }
}

