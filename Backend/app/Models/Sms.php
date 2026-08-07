<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sms extends Model
{
    protected $table = 'sms';

    protected $fillable = [
        'usuario_id',
        'administrador_id',
        'tipo',
        'mesnagem',
        'estado',
        'enviado_em',
    ];

    public function usuario()
    {
        return $this->belongsTo(Usuario::class);
    }

    public function administrador()
    {
        return $this->belongsTo(Administrador::class);
    }
}