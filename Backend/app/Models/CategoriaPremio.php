<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CategoriaPremio extends Model
{
    protected $table = 'categoria_premio';

    protected $fillable = [
        'nome',
        'tipo',
    ];

    public function premios()
    {
        return $this->hasMany(Premio::class, 'categoria_id');
    }

    public static function tentarNovamente(): self
    {
        return static::firstOrCreate(
            ['tipo' => 'tentar_novamente'],
            ['nome' => 'Tentar Novamente']
        );
    }
}
