<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Quadrado extends Model
{
    protected $table = 'quadrado';

    protected $fillable = [
        'campanha_id',
        'numero',
        'premio_id',
        'estado',
        'aberto_por',
        'aberto_em',
    ];

    protected $casts = [
        'aberto_em' => 'datetime',
    ];

    public function campanha()
    {
        return $this->belongsTo(Campanha::class);
    }

    public function premio()
    {
        return $this->belongsTo(Premio::class);
    }

    public function abertoPor()
    {
        return $this->belongsTo(Usuario::class, 'aberto_por');
    }

    public function participacao()
    {
        return $this->hasOne(Participacao::class);
    }
}