<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Premio extends Model
{
    protected $table = 'premio';

    protected $fillable = [
        'campanha_id',
        'categoria_id',
        'descricao',
        'valor_estimado',
        'data_programada',
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

    public function categoria()
    {
        return $this->belongsTo(CategoriaPremio::class, 'categoria_id');
    }

    public function quadrado()
    {
        return $this->hasOne(Quadrado::class);
    }
}
