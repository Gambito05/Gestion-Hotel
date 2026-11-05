<?php
declare(strict_types=1);

namespace App\Validators;

use Respect\Validation\ValidatorBuilder as v;
use Respect\Validation\Exceptions\ValidationException;

final class GuiaValidator {
    private function reglas(): array {
        return [
            'nombre' => [
                'regla' => v::stringType()
                ->notBlank()
                ->length(v::between(3,120))
                ->regex('/^[A-Za-zÑñÁÉÍÓÚáéíóúÜü ]{3,120}$/u'),
                'mensaje' => 'El nombre es obligatorio y debe tener entre 3 y 120 caracteres, solo letras y espacios.',
            ],
            'telefono' => [
                'regla' => v::stringType()
                ->regex('/^[2-8][0-9]{3}-[0-9]{4}$/')
                ->length(v::equals(9)),
                'mensaje' => 'El teléfono debe tener el formato ####-#### incluyendo el guión e iniciar con un dígito entre 2 y 8.',
            ],
            'activo' => [
                'regla' => v::intVal()
                ->greaterThanOrEqual(0)
                ->lessThanOrEqual(1),
                'mensaje' => 'El campo activo debe ser 0 o 1.',
            ],
        ];
    }

    public function validate(array $data): array {
        $errores = [];

        foreach ($this->reglas() as $campo => $config){
            $valor = $data[$campo] ?? null;
            $regla = $config['regla'];
            $mensaje = $config['mensaje'];
            if(($campo === 'telefono' || $campo === 'activo') && ($valor === null || $valor === '')){
                continue;
            }
            if($regla->validate($valor)->hasFailed()){
                $errores[$campo] = $mensaje;
            }
        }
        return $errores;
    }
}
