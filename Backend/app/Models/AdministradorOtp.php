<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdministradorOtp extends Model
{
    protected $table = 'administrador_otp';

    protected $fillable = [
        'administrador_id',
        'codigo_hash',
        'expira_em',
        'tentativas',
        'validado_em',
    ];

    protected $casts = [
        'expira_em' => 'datetime',
        'validado_em' => 'datetime',
    ];

    public function administrador()
    {
        return $this->belongsTo(Administrador::class);
    }

    public function expirado(): bool
    {
        return $this->expira_em->isPast();
    }
}
