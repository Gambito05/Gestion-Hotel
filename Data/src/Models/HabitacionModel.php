<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HabitacionModel extends Model {
    protected $table = 'habitaciones';

    public $timestamps = false;

    protected $fillable = [
        'tipo_habitacion_id',
        'numero',
        'piso',
        'estado',
    ];

    protected $casts = [
        'id' => 'integer',
        'tipo_habitacion_id' => 'integer',
        'piso' => 'integer',
    ];
}
