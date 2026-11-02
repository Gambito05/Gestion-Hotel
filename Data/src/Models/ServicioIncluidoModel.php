<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServicioIncluidoModel extends Model {
    protected $table = 'servicios_incluidos';

    public $timestamps = false;

    protected $fillable = [
        'tipo_habitacion_id',
        'servicio_habitacion_id',
    ];

    protected $casts = [
        'tipo_habitacion_id' => 'integer',
        'servicio_habitacion_id' => 'integer',
    ];
}
