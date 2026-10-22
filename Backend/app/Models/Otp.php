<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Otp extends Model
{
    protected $table = 'otp';

    protected $fillable = [
        'usuario_id',
        'campanha_id',
        'codigo_hash',
        'expira_em',
        'tentativas',
        'validado_em',
        'token_hash',
        'token_expira_em',
    ];

    protected $casts = [
        'expira_em' => 'datetime',
        'validado_em' => 'datetime',
        'token_expira_em' => 'datetime',
    ];

    protected $hidden = [
        'codigo_hash',
        'token_hash',
    ];

    public function usuario()
    {
        return $this->belongsTo(Usuario::class);
    }

    public function expirado(): bool
    {
        return $this->expira_em->isPast();
    }

    public function validado(): bool
    {
        return $this->validado_em !== null;
    }
}