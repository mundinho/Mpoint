<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Premio extends Model
{
    protected $table = 'premio';

    protected $fillable = [
        'campanha_id',
        'descricao',
        'valor_estimado',
    ];

    public function campanha()
    {
        return $this->belongsTo(Campanha::class);
    }

    public function quadrado()
    {
        return $this->hasOne(Quadrado::class);
    }
}