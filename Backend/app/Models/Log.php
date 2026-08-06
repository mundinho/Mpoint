<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Log extends Model
{
    protected $table = 'log';

    protected $fillable = [
        'hash',
        'class',
        'action',
        'success',
        'timestamp',
        'description',
        'device_id',
        'device_ip',
        'previos_hash',
    ];
}