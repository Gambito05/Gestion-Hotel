<?php
declare(strict_types=1);

namespace App\Validators;

use Respect\Validation\ValidatorBuilder as v;
use Respect\Validation\Exceptions\ValidationException;

final class RolValidator {
    private function reglas(): array {
        return [
            'nombre' => [
                'regla' => v::stringType()
                ->notBlank()
                ->length(v::between(3,30))
                ->regex('/^[A-Z_]{3,30}$/'),
                'mensaje' => 'El nombre del rol es obligatorio, debe tener entre 3 y 30 caracteres y solo letras mayúsculas o guion bajo.',
            ],
        ];
    }

    public function validate(array $data): array {
        $errores = [];

        foreach ($this->reglas() as $campo => $config){
            $valor = $data[$campo] ?? null;
            $regla = $config['regla'];
            $mensaje = $config['mensaje'];
            if($regla->validate($valor)->hasFailed()){
                $errores[$campo] = $mensaje;
            }
        }
        return $errores;
    }
}
