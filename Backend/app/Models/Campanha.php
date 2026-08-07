<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Campanha extends Model
{
    protected $table = 'campanha';
    
    protected $fillable = [
        'nome',
        'total_quadrados',
        'total_premios',
        'estado',
        'data_inicio',
        'data_fim',
        'otp_validade_minutos',
    ];

    protected $casts = [
        'data_inicio' => 'datetime',
        'data_fim' => 'datetime',
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

