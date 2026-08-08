<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DistribuicaoAleatoriaConfig extends Model
{
    protected $table = 'distribuicao_aleatoria_config';

    protected $fillable = [
        'campanha_id',
        'categoria_id',
        'quantidade',
        'data_programada',
    ];

    protected $casts = [
        'data_programada' => 'datetime',
    ];

    public function categoria()
    {
        return $this->belongsTo(CategoriaPremio::class, 'categoria_id');
    }
}
