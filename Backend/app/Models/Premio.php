<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Premio extends Model
{
    protected $table = 'premio';

    protected $fillable = [
        'campanha_id',
<<<<<<< HEAD
        'nome',
        'quantidade',
=======
        'categoria_id',
        'descricao',
>>>>>>> 318b0efbcc92ff9a31ee160f7e2209f82ee66809
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

<<<<<<< HEAD
    public function quadrados()
=======
    public function categoria()
    {
        return $this->belongsTo(CategoriaPremio::class, 'categoria_id');
    }

    public function quadrado()
>>>>>>> 318b0efbcc92ff9a31ee160f7e2209f82ee66809
    {
        return $this->hasMany(Quadrado::class);
    }
}
