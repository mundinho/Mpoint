<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PremioBanco extends Model
{
    protected $table = 'premios_banco';

    protected $fillable = [
        'nome',
        'quantidade_padrao',
    ];

    /**
     * Mesma normalização de Premio/DistribuicaoAleatoria — evita entradas
     * duplicadas no banco só por diferença de maiúsculas/minúsculas.
     */
    public function setNomeAttribute(?string $value): void
    {
        $this->attributes['nome'] = $value === null ? null : Str::upper(trim($value));
    }

    public function getNomeAttribute(?string $value): ?string
    {
        return $value === null ? null : Str::title(Str::lower($value));
    }
}
