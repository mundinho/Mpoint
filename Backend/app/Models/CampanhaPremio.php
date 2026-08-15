<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CampanhaPremio extends Model
{
    protected $table = 'campanha_premio';

    protected $fillable = [
        'campanha_id',
        'premio_banco_id',
        'modo_distribuicao',
        'quantidade',
        'logica_aleatoriedade',
        'data_programada',
    ];

    protected $casts = [
        'data_programada' => 'datetime',
    ];

    public function campanha()
    {
        return $this->belongsTo(Campanha::class);
    }

    public function premioBanco()
    {
        return $this->belongsTo(PremioBanco::class, 'premio_banco_id');
    }

    public function premios()
    {
        return $this->hasMany(Premio::class);
    }
}
