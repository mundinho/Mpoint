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
        'modo_distribuicao',
        'data_inicio',
        'data_fim',
        'otp_validade_minutos',
    ];

    protected $casts = [
        'data_inicio' => 'datetime',
        'data_fim' => 'datetime',
    ];

    // Coluna gerada só para o índice único que garante uma única campanha 'ativa' —
    // detalhe de implementação, não faz parte do contrato da API.
    protected $hidden = [
        'estado_ativa_unico',
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

    public function distribuicaoAleatoria()
    {
        return $this->hasMany(DistribuicaoAleatoria::class);
    }

    public static function ativa(): ?self
    {
        return static::where('estado', 'ativa')->latest('id')->first();
    }
}

