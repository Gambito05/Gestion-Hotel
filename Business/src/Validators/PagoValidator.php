<?php
declare(strict_types=1);

namespace App\Validators;

use Respect\Validation\ValidatorBuilder as v;
use Respect\Validation\Exceptions\ValidationException;

final class PagoValidator {
    private function reglas(): array {
        return [
            'reserva_habitacion_id' => [
                'regla' => v::intVal()
                ->greaterThanOrEqual(1)
                ->lessThanOrEqual(4294967295),
                'mensaje' => 'La reserva de habitación debe ser un id válido.',
            ],
            'reserva_tour_id' => [
                'regla' => v::intVal()
                ->greaterThanOrEqual(1)
                ->lessThanOrEqual(4294967295),
                'mensaje' => 'La reserva de tour debe ser un id válido.',
            ],
            'registrado_por' => [
                'regla' => v::intVal()
                ->greaterThanOrEqual(1)
                ->lessThanOrEqual(4294967295),
                'mensaje' => 'El usuario que registra es obligatorio y debe ser un id válido.',
            ],
            'monto' => [
                'regla' => v::numericVal()
                ->greaterThan(0)
                ->lessThanOrEqual(99999999.99),
                'mensaje' => 'El monto es obligatorio, debe ser numérico y mayor a 0.',
            ],
            'moneda' => [
                'regla' => v::stringType()
                ->notBlank()
                ->length(v::between(3,3))
                ->regex('/^(CRC|USD)$/'),
                'mensaje' => 'La moneda debe ser CRC o USD.',
            ],
            'metodo' => [
                'regla' => v::stringType()
                ->notBlank()
                ->length(v::between(5,8))
                ->regex('/^(EFECTIVO|TARJETA|SINPE)$/'),
                'mensaje' => 'El método de pago es obligatorio y debe ser EFECTIVO, TARJETA o SINPE.',
            ],
            'referencia' => [
                'regla' => v::stringType()
                ->notBlank()
                ->length(v::between(3,50))
                ->regex('/^[A-Za-z0-9-]{3,50}$/'),
                'mensaje' => 'La referencia debe tener entre 3 y 50 caracteres, solo letras, números y guiones.',
            ],
            'pagado_at' => [
                'regla' => v::stringType()
                ->notBlank()
                ->length(v::between(19,19))
                ->regex('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/'),
                'mensaje' => 'La fecha de pago es obligatoria y debe tener el formato AAAA-MM-DD HH:MM:SS.',
            ],
        ];
    }

    public function validate(array $data): array {
        $errores = [];

        foreach ($this->reglas() as $campo => $config){
            $valor = $data[$campo] ?? null;
            $regla = $config['regla'];
            $mensaje = $config['mensaje'];
            if(($campo === 'reserva_habitacion_id' || $campo === 'reserva_tour_id' || $campo === 'moneda' || $campo === 'referencia') && ($valor === null || $valor === '')){
                continue;
            }
            if($regla->validate($valor)->hasFailed()){
                $errores[$campo] = $mensaje;
            }
        }
        return $errores;
    }
}
