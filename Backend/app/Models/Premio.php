<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Premio extends Model
{
    protected $table = 'premio';

    protected $fillable = [
        'campanha_id',
        'nome',
        'quantidade',
        'valor_estimado',
        'data_programada',
        'logica_aleatoriedade',
        'especial',
        'entregue',
    ];

    protected $casts = [
        'data_programada' => 'datetime',
        'entregue' => 'boolean',
    ];

    public function campanha()
    {
        return $this->belongsTo(Campanha::class);
    }

    public function quadrados()
    {
        return $this->hasMany(Quadrado::class);
    }
}
