<?php
declare(strict_types=1);

namespace App\Validators;

use Respect\Validation\ValidatorBuilder as v;
use Respect\Validation\Exceptions\ValidationException;

final class TipoHabitacionValidator {
    private function reglas(): array {
        return [
            'nombre' => [
                'regla' => v::stringType()
                ->notBlank()
                ->length(v::between(3,60))
                ->regex('/^[A-Za-zÑñÁÉÍÓÚáéíóúÜü ]{3,60}$/u'),
                'mensaje' => 'El nombre es obligatorio y debe tener entre 3 y 60 caracteres, solo letras y espacios.',
            ],
            'descripcion' => [
                'regla' => v::stringType()
                ->length(v::between(1,1000)),
                'mensaje' => 'La descripción debe ser texto de máximo 1000 caracteres.',
            ],
            'capacidad' => [
                'regla' => v::intVal()
                ->greaterThanOrEqual(1)
                ->lessThanOrEqual(255),
                'mensaje' => 'La capacidad es obligatoria y debe ser un número entero entre 1 y 255.',
            ],
            'precio_noche' => [
                'regla' => v::numericVal()
                ->greaterThan(0)
                ->lessThanOrEqual(99999999.99),
                'mensaje' => 'El precio por noche es obligatorio, debe ser numérico y mayor a 0.',
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
            if(($campo === 'descripcion' || $campo === 'activo') && ($valor === null || $valor === '')){
                continue;
            }
            if($regla->validate($valor)->hasFailed()){
                $errores[$campo] = $mensaje;
            }
        }
        return $errores;
    }
}
