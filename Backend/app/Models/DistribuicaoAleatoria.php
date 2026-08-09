<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class DistribuicaoAleatoria extends Model
{
    protected $table = 'distribuicao_aleatoria';

    protected $fillable = [
        'campanha_id',
        'nome',
        'quantidade',
        'logica_aleatoriedade',
        'data_programada',
    ];

    protected $casts = [
        'data_programada' => 'datetime',
    ];

    /**
     * Guarda sempre em maiúsculas — mesma normalização do modelo Premio, para que
     * a distribuição aleatória e os prémios gerados a partir dela usem sempre o
     * mesmo nome canónico.
     */
    public function setNomeAttribute(?string $value): void
    {
        $this->attributes['nome'] = $value === null ? null : Str::upper(trim($value));
    }

    /**
     * Devolve em formato normal ("Carro") para exibição.
     */
    public function getNomeAttribute(?string $value): ?string
    {
        return $value === null ? null : Str::title(Str::lower($value));
    }

    public function campanha()
    {
        return $this->belongsTo(Campanha::class);
    }
}
