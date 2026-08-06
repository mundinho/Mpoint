<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Premio extends Model
{
    protected $table = 'premio';

    protected $fillable = [
        'id',
        'campanha_id',
        'descricao',
        'valor_estimado',
    ];
}