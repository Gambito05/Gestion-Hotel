<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServicioHabitacionModel extends Model {
    protected $table = 'servicios_habitacion';

    public $timestamps = false;

    protected $fillable = [
        'nombre',
    ];

    protected $casts = [
        'id' => 'integer',
    ];
}
