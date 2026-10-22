<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Campanha extends Model
{
    protected $table = 'campanha';
    
    protected $fillable = [
        'nome',
        'slug',
        'total_quadrados',
        'total_premios',
        'estado',
        'modo_distribuicao',
        'data_inicio',
        'data_fim',
        'otp_validade_minutos',
        'sms_resultado_ativo',
        'texto_sms_resultado',
    ];

    protected $casts = [
        'data_inicio' => 'datetime',
        'data_fim' => 'datetime',
        'sms_resultado_ativo' => 'boolean',
    ];

    // Coluna gerada só para o índice único que garante uma única campanha 'ativa' —
    // detalhe de implementação, não faz parte do contrato da API.
    protected $hidden = [
        'estado_ativa_unico',
    ];

    // Link público da campanha (ex.: https://bci.c.mpoints.site)
    protected $appends = ['link'];

    public function getLinkAttribute(): ?string
    {
        return \App\Support\CampanhaSlug::link($this->slug);
    }

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

    public function campanhaPremios()
    {
        return $this->hasMany(CampanhaPremio::class);
    }

    /**
     * Campanha activa mais recente. Só usada pelo domínio antigo (sem subdomínio de
     * campanha); com várias campanhas activas, o jogo identifica-a pelo link.
     */
    public static function ativa(): ?self
    {
        return static::where('estado', 'ativa')->latest('id')->first();
    }

    public function estaAtiva(): bool
    {
        return $this->estado === 'ativa';
    }
}

