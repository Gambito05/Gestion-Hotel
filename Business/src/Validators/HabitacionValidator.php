<?php
declare(strict_types=1);

namespace App\Validators;

use Respect\Validation\ValidatorBuilder as v;
use Respect\Validation\Exceptions\ValidationException;

final class HabitacionValidator {
    private function reglas(): array {
        return [
            'tipo_habitacion_id' => [
                'regla' => v::intVal()
                ->greaterThanOrEqual(1)
                ->lessThanOrEqual(4294967295),
                'mensaje' => 'El tipo de habitación es obligatorio y debe ser un id válido.',
            ],
            'numero' => [
                'regla' => v::stringType()
                ->notBlank()
                ->length(v::between(1,10))
                ->regex('/^[A-Z0-9-]{1,10}$/'),
                'mensaje' => 'El número de habitación es obligatorio, debe tener máximo 10 caracteres y solo letras mayúsculas, números y guiones.',
            ],
            'piso' => [
                'regla' => v::intVal()
                ->greaterThanOrEqual(-5)
                ->lessThanOrEqual(100),
                'mensaje' => 'El piso es obligatorio y debe ser un número entero entre -5 y 100.',
            ],
            'estado' => [
                'regla' => v::stringType()
                ->notBlank()
                ->length(v::between(8,14))
                ->regex('/^(DISPONIBLE|MANTENIMIENTO|FUERA_SERVICIO)$/'),
                'mensaje' => 'El estado debe ser DISPONIBLE, MANTENIMIENTO o FUERA_SERVICIO.',
            ],
        ];
    }

    public function validate(array $data): array {
        $errores = [];

        foreach ($this->reglas() as $campo => $config){
            $valor = $data[$campo] ?? null;
            $regla = $config['regla'];
            $mensaje = $config['mensaje'];
            if($campo === 'estado' && ($valor === null || $valor === '')){
                continue;
            }
            if($regla->validate($valor)->hasFailed()){
                $errores[$campo] = $mensaje;
            }
        }
        return $errores;
    }
}
