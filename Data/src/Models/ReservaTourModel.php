<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReservaTourModel extends Model {
    protected $table = 'reservas_tour';

    public $timestamps = false;

    protected $fillable = [
        'codigo',
        'tour_programado_id',
        'cliente_id',
        'personas',
        'precio_persona',
        'total',
        'estado',
    ];

    protected $casts = [
        'id' => 'integer',
        'tour_programado_id' => 'integer',
        'cliente_id' => 'integer',
        'personas' => 'integer',
        'precio_persona' => 'float',
        'total' => 'float',
    ];
}
