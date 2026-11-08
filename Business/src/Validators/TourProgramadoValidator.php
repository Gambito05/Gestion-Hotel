<?php
declare(strict_types=1);

namespace App\Validators;

use Respect\Validation\ValidatorBuilder as v;
use Respect\Validation\Exceptions\ValidationException;
use App\Services\DataService;
use DateTimeImmutable;

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
                ->length(v::equals(19))
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
            if(($campo === 'guia_id' || $campo === 'estado') && ($valor === null || $valor === '')){
                continue;
            }
            if($regla->validate($valor)->hasFailed()){
                $errores[$campo] = $mensaje;
            }
        }
        return $errores;
    }

    #----- Reglas de negocio: se revisan contra la capa de datos después de validar el formato -----
    #$programado: datos completos (en una actualización, lo que había más los cambios)
    #$anterior: el tour programado como estaba guardado, o null si se está creando
    public function aplicarReglas(DataService $dataservice, array $programado, ?array $anterior = null): array {
        $esNueva = $anterior === null;

        #1. La fecha de inicio de un tour nuevo debe ser futura
        if ($esNueva && new DateTimeImmutable((string) $programado['fecha_hora_inicio']) <= new DateTimeImmutable()) {
            throw new ReglaNegocioException('La fecha y hora de inicio debe ser posterior al momento actual.', 400);
        }

        #2. El tour existe, está activo y el cupo no supera su cupo máximo
        $tour = $dataservice->obtener('tours/' . rawurlencode((string) $programado['tour_id']));
        if ($tour === null) {
            throw new ReglaNegocioException('El tour indicado no existe.', 422);
        }
        $tour = $tour['data'];
        if (($esNueva || (int) $anterior['tour_id'] !== (int) $programado['tour_id']) && (int) $tour['activo'] !== 1) {
            throw new ReglaNegocioException('El tour no está activo.', 409);
        }
        if ((int) $programado['cupo'] > (int) $tour['cupo_maximo']) {
            throw new ReglaNegocioException('El cupo no puede superar el cupo máximo del tour (' . $tour['cupo_maximo'] . ').', 400);
        }

        #3. Si se asigna guía, debe existir y estar activo
        $guiaId = $programado['guia_id'] ?? null;
        if ($guiaId !== null && $guiaId !== '' && ($esNueva || (int) ($anterior['guia_id'] ?? 0) !== (int) $guiaId)) {
            $guia = $dataservice->obtener('guias/' . rawurlencode((string) $guiaId));
            if ($guia === null) {
                throw new ReglaNegocioException('El guía indicado no existe.', 422);
            }
            if ((int) $guia['data']['activo'] !== 1) {
                throw new ReglaNegocioException('El guía no está activo.', 409);
            }
        }

        #4. Al bajar el cupo, no puede quedar por debajo de las personas ya reservadas
        if (!$esNueva && (int) $programado['cupo'] < (int) $anterior['cupo']) {
            $reservas = $dataservice->obtenerTodos('reservas-tour', ['tour_programado_id' => $anterior['id']]);
            $ocupados = 0;
            foreach ($reservas as $reserva) {
                if ($reserva['estado'] !== 'CANCELADA') {
                    $ocupados += (int) $reserva['personas'];
                }
            }
            if ((int) $programado['cupo'] < $ocupados) {
                throw new ReglaNegocioException('El cupo no puede ser menor a las ' . $ocupados . ' personas ya reservadas.', 409);
            }
        }

        return $programado;
    }
}
