<?php
declare(strict_types=1);

namespace App\Validators;

use Respect\Validation\ValidatorBuilder as v;
use Respect\Validation\Exceptions\ValidationException;

final class TourProgramadoValidator {
    private function reglas(): array {
        return [
            'tour_id' => [
                'regla' => v::intVal()
                ->greaterThanOrEqual(1)
                ->lessThanOrEqual(4294967295),
                'mensaje' => 'El tour es obligatorio y debe ser un id válido.',
            ],
            'guia_id' => [
                'regla' => v::intVal()
                ->greaterThanOrEqual(1)
                ->lessThanOrEqual(4294967295),
                'mensaje' => 'El guía debe ser un id válido.',
            ],
            'fecha_hora_inicio' => [
                'regla' => v::stringType()
                ->notBlank()
                ->length(v::between(19,19))
                ->regex('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/'),
                'mensaje' => 'La fecha y hora de inicio es obligatoria y debe tener el formato AAAA-MM-DD HH:MM:SS.',
            ],
            'cupo' => [
                'regla' => v::intVal()
                ->greaterThanOrEqual(1)
                ->lessThanOrEqual(65535),
                'mensaje' => 'El cupo es obligatorio y debe ser un número entero mayor a 0.',
            ],
            'estado' => [
                'regla' => v::stringType()
                ->notBlank()
                ->length(v::between(8,11))
                ->regex('/^(PROGRAMADO|EN_CURSO|FINALIZADO|CANCELADO)$/'),
                'mensaje' => 'El estado debe ser PROGRAMADO, EN_CURSO, FINALIZADO o CANCELADO.',
            ],
        ];
    }

    public function validate(array $data): array {
        $errores = [];

        foreach ($this->reglas() as $campo => $config){
            $valor = $data[$campo] ?? null;
            $regla = $config['regla'];
            $mensaje = $config['mensaje'];
            if(($campo === 'guia_id' || $campo === 'estado') && ($valor === null || $valor === '')){
                continue;
            }
            if($regla->validate($valor)->hasFailed()){
                $errores[$campo] = $mensaje;
            }
        }
        return $errores;
    }
}
