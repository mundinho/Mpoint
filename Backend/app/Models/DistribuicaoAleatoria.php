<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DistribuicaoAleatoria extends Model
{
    protected $table = 'distribuicao_aleatoria';

    protected $fillable = [
        'campanha_id',
        'categoria_id',
        'quantidade',
        'data_programada',
    ];

    protected $casts = [
        'data_programada' => 'datetime',
    ];

    public function campanha()
    {
        return $this->belongsTo(Campanha::class);
    }

    public function categoria()
    {
        return $this->belongsTo(CategoriaPremio::class, 'categoria_id');
    }
}
