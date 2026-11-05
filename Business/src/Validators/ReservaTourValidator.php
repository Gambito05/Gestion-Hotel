<?php
declare(strict_types=1);

namespace App\Validators;

use Respect\Validation\ValidatorBuilder as v;
use Respect\Validation\Exceptions\ValidationException;

final class ReservaTourValidator {
    private function reglas(): array {
        return [
            'codigo' => [
                'regla' => v::stringType()
                ->notBlank()
                ->length(v::between(7,20))
                ->regex('/^RT-[0-9]{4,17}$/'),
                'mensaje' => 'El código es obligatorio y debe tener el formato RT-0001.',
            ],
            'tour_programado_id' => [
                'regla' => v::intVal()
                ->greaterThanOrEqual(1)
                ->lessThanOrEqual(4294967295),
                'mensaje' => 'El tour programado es obligatorio y debe ser un id válido.',
            ],
            'cliente_id' => [
                'regla' => v::intVal()
                ->greaterThanOrEqual(1)
                ->lessThanOrEqual(4294967295),
                'mensaje' => 'El cliente es obligatorio y debe ser un id válido.',
            ],
            'personas' => [
                'regla' => v::intVal()
                ->greaterThanOrEqual(1)
                ->lessThanOrEqual(255),
                'mensaje' => 'La cantidad de personas es obligatoria y debe ser un número entero mayor a 0.',
            ],
            'precio_persona' => [
                'regla' => v::numericVal()
                ->greaterThan(0)
                ->lessThanOrEqual(99999999.99),
                'mensaje' => 'El precio por persona es obligatorio, debe ser numérico y mayor a 0.',
            ],
            'total' => [
                'regla' => v::numericVal()
                ->greaterThan(0)
                ->lessThanOrEqual(99999999.99),
                'mensaje' => 'El total es obligatorio, debe ser numérico y mayor a 0.',
            ],
            'estado' => [
                'regla' => v::stringType()
                ->notBlank()
                ->length(v::between(8,10))
                ->regex('/^(PENDIENTE|CONFIRMADA|FINALIZADA|CANCELADA)$/'),
                'mensaje' => 'El estado debe ser PENDIENTE, CONFIRMADA, FINALIZADA o CANCELADA.',
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
