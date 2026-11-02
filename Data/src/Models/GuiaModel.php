<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GuiaModel extends Model {
    protected $table = 'guias';

    public $timestamps = false;

    protected $fillable = [
        'nombre',
        'telefono',
        'activo',
    ];

    protected $casts = [
        'id' => 'integer',
        'activo' => 'integer',
    ];
}
