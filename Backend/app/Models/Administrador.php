<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Administrador extends Model
{
    public const SESSION_MINUTES = 45;

    protected $table = 'administradores';

    protected $fillable = [
        'nome',
        'telefone',
        'ativo',
        'master',
        'todas_campanhas',
        'api_token',
        'token_expira_em',
    ];

    protected $hidden = [
        'api_token',
        'token_expira_em',
    ];

    protected $casts = [
        'ativo' => 'boolean',
        'master' => 'boolean',
        'todas_campanhas' => 'boolean',
        'token_expira_em' => 'datetime',
    ];

    public function otps()
    {
        return $this->hasMany(AdministradorOtp::class);
    }

    public function campanhas()
    {
        return $this->belongsToMany(Campanha::class, 'administrador_campanha')->withTimestamps();
    }

    /** O master tem sempre acesso a todas as campanhas. */
    public function acessoTotal(): bool
    {
        return $this->master || $this->todas_campanhas;
    }

    public function podeGerir(Campanha|int $campanha): bool
    {
        if ($this->acessoTotal()) {
            return true;
        }

        $id = $campanha instanceof Campanha ? $campanha->id : $campanha;

        return $this->campanhas()->where('campanha.id', $id)->exists();
    }

    /** Ids das campanhas que gere, ou null se gere todas. */
    public function campanhaIds(): ?array
    {
        return $this->acessoTotal() ? null : $this->campanhas()->pluck('campanha.id')->all();
    }

    /** Dados devolvidos ao painel (sessão e gestão de administradores). */
    public function perfil(): array
    {
        return [
            'id' => $this->id,
            'nome' => $this->nome,
            'telefone' => $this->telefone,
            'ativo' => $this->ativo,
            'master' => $this->master,
            'todas_campanhas' => $this->acessoTotal(),
            'campanhas' => $this->acessoTotal() ? [] : $this->campanhas()->orderBy('campanha.id')->pluck('campanha.id')->all(),
        ];
    }
}
