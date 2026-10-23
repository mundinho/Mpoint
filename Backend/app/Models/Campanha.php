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

    protected static function booted(): void
    {
        // Imagem de partilha: gerada ao criar a campanha e quando o nome ou o link mudam
        static::saved(function (Campanha $campanha) {
            if (!$campanha->slug || !($campanha->wasRecentlyCreated || $campanha->wasChanged(['nome', 'slug']))) {
                return;
            }

            try {
                app(\App\Services\CampanhaImagemService::class)->gerar($campanha);
            } catch (\Throwable $e) {
                report($e);
            }
        });
    }

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

    public function administradores()
    {
        return $this->belongsToMany(Administrador::class, 'administrador_campanha')->withTimestamps();
    }
}
