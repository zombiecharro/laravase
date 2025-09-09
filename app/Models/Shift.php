<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Shift extends Model
{
    protected $fillable = [
        'user_id', // null = global, id = personalizado
        'name',    // nombre del turno (opcional, para referencia)
        'global',  // si es true, aplica a todos los usuarios
    'reverse',  // si es true, es un bloqueo (no disponible)
    'start_time',
    'end_time',
    ];
}
