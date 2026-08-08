<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ParticipanteCampanha extends Model
{
    protected $table = 'participante_campanha';

    protected $fillable = [
        'usuario_id',
        'campanha_id',
        'tentativas_disponiveis',
        'tentativas_usadas',
    ];

    public function usuario()
    {
        return $this->belongsTo(Usuario::class);
    }

    public function campanha()
    {
        return $this->belongsTo(Campanha::class);
    }
}
