<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoHabitacionModel extends Model {
    protected $table = 'tipos_habitacion';

    public $timestamps = false;

    protected $fillable = [
        'nombre',
        'descripcion',
        'capacidad',
        'precio_noche',
        'foto_url',
        'activo',
    ];

    protected $casts = [
        'id' => 'integer',
        'capacidad' => 'integer',
        'precio_noche' => 'float',
        'activo' => 'integer',
    ];
}
