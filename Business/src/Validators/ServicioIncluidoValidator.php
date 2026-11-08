<?php
declare(strict_types=1);

namespace App\Validators;

use Respect\Validation\ValidatorBuilder as v;
use Respect\Validation\Exceptions\ValidationException;

final class ServicioIncluidoValidator {
    private function reglas(): array {
        return [
            'tipo_habitacion_id' => [
                'regla' => v::intVal()
                ->greaterThanOrEqual(1)
                ->lessThanOrEqual(4294967295),
                'mensaje' => 'El tipo de habitación es obligatorio y debe ser un id válido.',
            ],
            'servicio_habitacion_id' => [
                'regla' => v::intVal()
                ->greaterThanOrEqual(1)
                ->lessThanOrEqual(4294967295),
                'mensaje' => 'El servicio es obligatorio y debe ser un id válido.',
            ],
        ];
    }

    public function validate(array $data, bool $parcial = false): array {
        $errores = [];

        foreach ($this->reglas() as $campo => $config){
            #En una actualización solo se validan los campos que se envían
            if($parcial && !array_key_exists($campo, $data)){
                continue;
            }
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
