<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TourProgramadoModel extends Model {
    protected $table = 'tours_programados';

    public $timestamps = false;

    protected $fillable = [
        'tour_id',
        'guia_id',
        'fecha_hora_inicio',
        'cupo',
        'estado',
    ];

    protected $casts = [
        'id' => 'integer',
        'tour_id' => 'integer',
        'guia_id' => 'integer',
        'cupo' => 'integer',
    ];
}
