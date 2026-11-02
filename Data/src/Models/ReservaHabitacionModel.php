<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReservaHabitacionModel extends Model {
    protected $table = 'reservas_habitacion';

    public $timestamps = false;

    protected $fillable = [
        'codigo',
        'cliente_id',
        'habitacion_id',
        'registrada_por',
        'fecha_entrada',
        'fecha_salida',
        'huespedes',
        'precio_noche',
        'total',
        'estado',
        'checkin_at',
        'checkout_at',
    ];

    protected $casts = [
        'id' => 'integer',
        'cliente_id' => 'integer',
        'habitacion_id' => 'integer',
        'registrada_por' => 'integer',
        'huespedes' => 'integer',
        'precio_noche' => 'float',
        'total' => 'float',
    ];
}
