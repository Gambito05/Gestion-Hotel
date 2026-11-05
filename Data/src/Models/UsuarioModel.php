<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UsuarioModel extends Model {
    protected $table = 'usuarios';

    public $timestamps = false;

    protected $fillable = [
        'rol_id',
        'nombre',
        'apellidos',
        'email',
        'username',
        'password_hash',
        'telefono',
        'activo',
    ];

    protected $casts = [
        'id' => 'integer',
        'rol_id' => 'integer',
        'activo' => 'integer',
    ];
}
