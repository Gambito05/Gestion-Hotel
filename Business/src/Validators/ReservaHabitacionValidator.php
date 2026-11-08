<?php
declare(strict_types=1);

namespace App\Validators;

use Respect\Validation\ValidatorBuilder as v;
use Respect\Validation\Exceptions\ValidationException;
use App\Services\DataService;
use DateTimeImmutable;

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
            if(($campo === 'registrada_por' || $campo === 'estado' || $campo === 'precio_noche' || $campo === 'total' || $campo === 'checkin_at' || $campo === 'checkout_at') && ($valor === null || $valor === '')){
                continue;
            }
            if($regla->validate($valor)->hasFailed()){
                $errores[$campo] = $mensaje;
            }
        }
        return $errores;
    }

    #----- Reglas de negocio: se revisan contra la capa de datos después de validar el formato -----
    #Estados que ya no ocupan la habitación
    private const ESTADOS_LIBERADOS = ['CANCELADA', 'FINALIZADA'];


    #$reserva: datos completos de la reserva (en una actualización, lo que había más los cambios)
    #$anterior: la reserva como estaba guardada, o null si se está creando
    public function aplicarReglas(DataService $dataservice, array $reserva, ?array $anterior = null): array {
        $esNueva = $anterior === null;
        $estado = (string) ($reserva['estado'] ?? 'PENDIENTE');
        $cambioHabitacion = $esNueva || (int) $anterior['habitacion_id'] !== (int) $reserva['habitacion_id'];

        #1. Fechas coherentes
        $entrada = DateTimeImmutable::createFromFormat('!Y-m-d', (string) $reserva['fecha_entrada']);
        $salida = DateTimeImmutable::createFromFormat('!Y-m-d', (string) $reserva['fecha_salida']);
        if ($entrada === false || $salida === false) {
            throw new ReglaNegocioException('Las fechas deben tener el formato AAAA-MM-DD.', 400);
        }
        if ($salida <= $entrada) {
            throw new ReglaNegocioException('La fecha de salida debe ser posterior a la fecha de entrada.', 400);
        }
        if ($esNueva && $entrada < new DateTimeImmutable('today')) {
            throw new ReglaNegocioException('La fecha de entrada no puede ser anterior a hoy.', 400);
        }

        #2. La habitación existe y está disponible
        $habitacion = $dataservice->obtener('habitaciones/' . rawurlencode((string) $reserva['habitacion_id']));
        if ($habitacion === null) {
            throw new ReglaNegocioException('La habitación indicada no existe.', 422);
        }
        $habitacion = $habitacion['data'];
        if ($cambioHabitacion && $habitacion['estado'] !== 'DISPONIBLE') {
            throw new ReglaNegocioException('La habitación no está disponible (estado: ' . $habitacion['estado'] . ').', 409);
        }

        #3. El tipo de habitación está activo y admite la cantidad de huéspedes
        $tipo = $dataservice->obtener('tipos-habitacion/' . rawurlencode((string) $habitacion['tipo_habitacion_id']));
        if ($tipo === null) {
            throw new ReglaNegocioException('El tipo de la habitación no existe.', 422);
        }
        $tipo = $tipo['data'];
        if ($cambioHabitacion && (int) $tipo['activo'] !== 1) {
            throw new ReglaNegocioException('El tipo de habitación no está activo.', 409);
        }
        if ((int) $reserva['huespedes'] > (int) $tipo['capacidad']) {
            throw new ReglaNegocioException('La habitación admite máximo ' . $tipo['capacidad'] . ' huéspedes.', 400);
        }

        #4. No se cruza con otra reserva activa de la misma habitación
        if (!in_array($estado, self::ESTADOS_LIBERADOS, true)) {
            $cruzadas = $dataservice->obtenerTodos('reservas-habitacion', [
                'habitacion_id' => $reserva['habitacion_id'],
                'entrada_antes_de' => $salida->format('Y-m-d'),
                'salida_despues_de' => $entrada->format('Y-m-d'),
            ]);
            foreach ($cruzadas as $otra) {
                if (!$esNueva && (int) $otra['id'] === (int) $anterior['id']) {
                    continue;
                }
                if (in_array($otra['estado'], self::ESTADOS_LIBERADOS, true)) {
                    continue;
                }
                throw new ReglaNegocioException('La habitación ya está reservada del ' . $otra['fecha_entrada']
                    . ' al ' . $otra['fecha_salida'] . ' (reserva ' . $otra['codigo'] . ').', 409);
            }
        }

        #5. El precio sale del tipo de habitación y el total se calcula: noches x precio por noche
        #   Si la reserva ya existía y no cambió de habitación, se respeta el precio con que se reservó
        $precioNoche = $cambioHabitacion ? (float) $tipo['precio_noche'] : (float) $anterior['precio_noche'];
        $noches = $entrada->diff($salida)->days;

        $reserva['precio_noche'] = $precioNoche;
        $reserva['total'] = round($noches * $precioNoche, 2);

        return $reserva;
    }
}
