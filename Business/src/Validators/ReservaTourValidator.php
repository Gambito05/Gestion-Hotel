<?php
declare(strict_types=1);

namespace App\Validators;

use Respect\Validation\ValidatorBuilder as v;
use Respect\Validation\Exceptions\ValidationException;
use App\Services\DataService;
use DateTimeImmutable;

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
            #precio_persona y total los calcula la capa de negocio, por eso son opcionales
            if(($campo === 'estado' || $campo === 'precio_persona' || $campo === 'total') && ($valor === null || $valor === '')){
                continue;
            }
            if($regla->validate($valor)->hasFailed()){
                $errores[$campo] = $mensaje;
            }
        }
        return $errores;
    }

    #----- Reglas de negocio: se revisan contra la capa de datos después de validar el formato -----
    #$reserva: datos completos de la reserva (en una actualización, lo que había más los cambios)
    #$anterior: la reserva como estaba guardada, o null si se está creando
    public function aplicarReglas(DataService $dataservice, array $reserva, ?array $anterior = null): array {
        $esNueva = $anterior === null;
        $estado = (string) ($reserva['estado'] ?? 'PENDIENTE');
        $cambioTour = $esNueva || (int) $anterior['tour_programado_id'] !== (int) $reserva['tour_programado_id'];

        #1. El tour programado existe, sigue programado y no ha empezado
        $programado = $dataservice->obtener('tours-programados/' . rawurlencode((string) $reserva['tour_programado_id']));
        if ($programado === null) {
            throw new ReglaNegocioException('El tour programado indicado no existe.', 422);
        }
        $programado = $programado['data'];
        if ($cambioTour) {
            if ($programado['estado'] !== 'PROGRAMADO') {
                throw new ReglaNegocioException('El tour no admite reservas (estado: ' . $programado['estado'] . ').', 409);
            }
            if (new DateTimeImmutable((string) $programado['fecha_hora_inicio']) <= new DateTimeImmutable()) {
                throw new ReglaNegocioException('El tour ya inició o ya pasó, no se puede reservar.', 409);
            }
        }

        #2. Queda cupo suficiente (las reservas canceladas no ocupan espacio)
        if ($estado !== 'CANCELADA') {
            $reservas = $dataservice->obtenerTodos('reservas-tour', [
                'tour_programado_id' => $reserva['tour_programado_id'],
            ]);
            $ocupados = 0;
            foreach ($reservas as $otra) {
                if (!$esNueva && (int) $otra['id'] === (int) $anterior['id']) {
                    continue;
                }
                if ($otra['estado'] === 'CANCELADA') {
                    continue;
                }
                $ocupados += (int) $otra['personas'];
            }
            $disponibles = (int) $programado['cupo'] - $ocupados;
            if ((int) $reserva['personas'] > $disponibles) {
                throw new ReglaNegocioException('Solo quedan ' . max($disponibles, 0) . ' espacios disponibles en este tour.', 409);
            }
        }

        #3. El precio sale del tour y el total se calcula: personas x precio por persona
        #   Si la reserva ya existía y no cambió de tour, se respeta el precio con que se reservó
        if ($cambioTour) {
            $tour = $dataservice->obtener('tours/' . rawurlencode((string) $programado['tour_id']));
            if ($tour === null) {
                throw new ReglaNegocioException('El tour indicado no existe.', 422);
            }
            $precioPersona = (float) $tour['data']['precio_persona'];
        } else {
            $precioPersona = (float) $anterior['precio_persona'];
        }

        $reserva['precio_persona'] = $precioPersona;
        $reserva['total'] = round((int) $reserva['personas'] * $precioPersona, 2);

        return $reserva;
    }
}
