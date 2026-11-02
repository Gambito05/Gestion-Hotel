<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IdiomaGuiaModel extends Model {
    protected $table = 'idiomas_guia';

    public $timestamps = false;

    protected $fillable = [
        'guia_id',
        'idioma_id',
    ];

    protected $casts = [
        'guia_id' => 'integer',
        'idioma_id' => 'integer',
    ];
}
