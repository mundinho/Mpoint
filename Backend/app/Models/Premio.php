<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Premio extends Model
{
    protected $table = 'premio';

    protected $fillable = [
        'campanha_id',
        'campanha_premio_id',
        'nome',
        'data_programada',
        'entregue',
    ];

    protected $casts = [
        'data_programada' => 'datetime',
        'entregue' => 'boolean',
    ];

    /**
     * Guarda sempre em maiúsculas — "Carro", "carro" e "CARRO" tornam-se todos
     * "CARRO" na base de dados, evitando prémios duplicados por diferença de caixa.
     */
    public function setNomeAttribute(?string $value): void
    {
        $this->attributes['nome'] = $value === null ? null : Str::upper(trim($value));
    }

    /**
     * Devolve em formato normal ("Carro") para exibição, independentemente de como
     * está guardado na base de dados.
     */
    public function getNomeAttribute(?string $value): ?string
    {
        return $value === null ? null : Str::title(Str::lower($value));
    }

    public function campanha()
    {
        return $this->belongsTo(Campanha::class);
    }

    public function campanhaPremio()
    {
        return $this->belongsTo(CampanhaPremio::class);
    }

    public function quadrado()
    {
        return $this->hasOne(Quadrado::class);
    }
}
