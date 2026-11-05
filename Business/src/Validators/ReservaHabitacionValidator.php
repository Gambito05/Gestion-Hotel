<?php
declare(strict_types=1);

namespace App\Validators;

use Respect\Validation\ValidatorBuilder as v;
use Respect\Validation\Exceptions\ValidationException;

final class ReservaHabitacionValidator {
    private function reglas(): array {
        return [
            'codigo' => [
                'regla' => v::stringType()
                ->notBlank()
                ->length(v::between(7,20))
                ->regex('/^RH-[0-9]{4,17}$/'),
                'mensaje' => 'El código es obligatorio y debe tener el formato RH-0001.',
            ],
            'cliente_id' => [
                'regla' => v::intVal()
                ->greaterThanOrEqual(1)
                ->lessThanOrEqual(4294967295),
                'mensaje' => 'El cliente es obligatorio y debe ser un id válido.',
            ],
            'habitacion_id' => [
                'regla' => v::intVal()
                ->greaterThanOrEqual(1)
                ->lessThanOrEqual(4294967295),
                'mensaje' => 'La habitación es obligatoria y debe ser un id válido.',
            ],
            'registrada_por' => [
                'regla' => v::intVal()
                ->greaterThanOrEqual(1)
                ->lessThanOrEqual(4294967295),
                'mensaje' => 'El usuario que registra debe ser un id válido.',
            ],
            'fecha_entrada' => [
                'regla' => v::stringType()
                ->notBlank()
                ->date('Y-m-d'),
                'mensaje' => 'La fecha de entrada es obligatoria y debe ser una fecha válida con el formato AAAA-MM-DD.',
            ],
            'fecha_salida' => [
                'regla' => v::stringType()
                ->notBlank()
                ->date('Y-m-d'),
                'mensaje' => 'La fecha de salida es obligatoria y debe ser una fecha válida con el formato AAAA-MM-DD.',
            ],
            'huespedes' => [
                'regla' => v::intVal()
                ->greaterThanOrEqual(1)
                ->lessThanOrEqual(255),
                'mensaje' => 'La cantidad de huéspedes es obligatoria y debe ser un número entero mayor a 0.',
            ],
            'precio_noche' => [
                'regla' => v::numericVal()
                ->greaterThan(0)
                ->lessThanOrEqual(99999999.99),
                'mensaje' => 'El precio por noche es obligatorio, debe ser numérico y mayor a 0.',
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
                ->regex('/^(PENDIENTE|CONFIRMADA|EN_CURSO|FINALIZADA|CANCELADA)$/'),
                'mensaje' => 'El estado debe ser PENDIENTE, CONFIRMADA, EN_CURSO, FINALIZADA o CANCELADA.',
            ],
            'checkin_at' => [
                'regla' => v::stringType()
                ->notBlank()
                ->length(v::between(19,19))
                ->regex('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/'),
                'mensaje' => 'La fecha de check-in debe tener el formato AAAA-MM-DD HH:MM:SS.',
            ],
            'checkout_at' => [
                'regla' => v::stringType()
                ->notBlank()
                ->length(v::between(19,19))
                ->regex('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/'),
                'mensaje' => 'La fecha de check-out debe tener el formato AAAA-MM-DD HH:MM:SS.',
            ],
        ];
    }

    public function validate(array $data): array {
        $errores = [];

        foreach ($this->reglas() as $campo => $config){
            $valor = $data[$campo] ?? null;
            $regla = $config['regla'];
            $mensaje = $config['mensaje'];
            if(($campo === 'registrada_por' || $campo === 'estado' || $campo === 'checkin_at' || $campo === 'checkout_at') && ($valor === null || $valor === '')){
                continue;
            }
            if($regla->validate($valor)->hasFailed()){
                $errores[$campo] = $mensaje;
            }
        }
        return $errores;
    }
}
