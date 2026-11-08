<?php
declare(strict_types=1);

namespace App\Validators;

use Respect\Validation\ValidatorBuilder as v;
use Respect\Validation\Exceptions\ValidationException;

final class ServicioHabitacionValidator {
    private function reglas(): array {
        return [
            'nombre' => [
                'regla' => v::stringType()
                ->notBlank()
                ->length(v::between(2,60))
                ->regex('/^[A-Za-zÑñÁÉÍÓÚáéíóúÜü0-9 -]{2,60}$/u'),
                'mensaje' => 'El nombre es obligatorio y debe tener entre 2 y 60 caracteres, solo letras, números, espacios y guiones.',
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
