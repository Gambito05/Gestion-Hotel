<?php
 
namespace App\controllers;
 
use App\Models\TourProgramadoModel;
use Illuminate\Database\QueryException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Container\ContainerInterface;
 
class TourProgramado {
    private ContainerInterface $container;
 
    public function __construct(ContainerInterface $c){
        $this->container = $c;
    }
 
    public function read(Request $request, Response $response, array $args): Response {
        $this->container->get('eloquent');
        if(isset($args['id'])){
            $tourProgramado = TourProgramadoModel::query()
                ->select(['id', 'tour_id', 'guia_id', 'fecha_hora_inicio', 'cupo', 'estado'])
                ->find($args['id']);
 
            if ($tourProgramado === null) {
                return $this->json($response, ['error' => 'Tour programado no encontrado.'], 404);
            }
 
            return $this->json($response, ['data' => $tourProgramado], 200);
        }
 
        $toursProgramados = TourProgramadoModel::query()
                ->select(['id', 'tour_id', 'guia_id', 'fecha_hora_inicio', 'cupo', 'estado'])
                ->get()
                ->toArray();
            return $this->json($response, ['data' => $toursProgramados], 200);
    }
 
    public function create(Request $request, Response $response, array $args): Response {
        $data = $request->getParsedBody();
 
        $data = is_array($data) ? $data : [];
 
        $errors = [];
        $tourId = filter_var($data['tour_id'] ?? '', FILTER_VALIDATE_INT);
        $guiaId = isset($data['guia_id']) ? filter_var($data['guia_id'], FILTER_VALIDATE_INT) : null;
        $fechaHoraInicio = trim((string) ($data['fecha_hora_inicio'] ?? ''));
        $cupo = filter_var($data['cupo'] ?? '', FILTER_VALIDATE_INT);
        $estado = trim((string) ($data['estado'] ?? 'PROGRAMADO'));
 
        if ($tourId === false || $tourId < 1) {
            $errors['tour_id'] = 'Es obligatorio y debe ser un id válido.';
        }
        if ($guiaId === false || ($guiaId !== null && $guiaId < 1)) {
            $errors['guia_id'] = 'Debe ser un id válido.';
        }
        if ($fechaHoraInicio === '' || mb_strlen($fechaHoraInicio) > 19) {
            $errors['fecha_hora_inicio'] = 'Es obligatorio.';
        }
        if ($cupo === false || $cupo < 1) {
            $errors['cupo'] = 'Es obligatorio y debe ser un número entero mayor o igual a 1.';
        }
        if ($estado === '' || mb_strlen($estado) > 20) {
            $errors['estado'] = 'Debe ser un valor válido.';
        }
 
        if ($errors !== []) {
            return $this->json($response, ['errors' => $errors], 422);
        }
 
        try {
            $this->container->get('eloquent');
            $tourProgramado = TourProgramadoModel::create([
                'tour_id' => $tourId,
                'guia_id' => $guiaId,
                'fecha_hora_inicio' => $fechaHoraInicio,
                'cupo' => $cupo,
                'estado' => $estado,
            ]);
        } catch (QueryException $exception) {
            if ((int) ($exception->errorInfo[1] ?? 0) === 1452) { #1452: la llave foránea apunta a un registro que no existe
                return $this->json($response, ['error' => 'Uno de los registros relacionados no existe.'], 422);
            }
            if ((int) ($exception->errorInfo[1] ?? 0) === 3819) { #3819: no se cumple un CHECK de la tabla
                return $this->json($response, ['error' => 'El cupo debe ser mayor a 0.'], 422);
            }
            if ((int) ($exception->errorInfo[1] ?? 0) === 1265 || (int) ($exception->errorInfo[1] ?? 0) === 1292) { #1265 y 1292: estado o fecha con un valor no válido
                return $this->json($response, ['error' => 'Alguna fecha o estado tiene un valor no válido.'], 422);
            }
            throw $exception;
        }
 
        return $this->json($response, ['data' => $tourProgramado->toArray()], 201);
    }
 
    public function update(Request $request, Response $response, array $args): Response {
        $eloquent = $this->container->get('eloquent');
        if (isset($args['id'])) {
            $tourProgramado = TourProgramadoModel::find($args['id']);
 
            if ($tourProgramado === null) {
                return $this->json($response, ['error' => 'Tour programado no encontrado.'], 404);
            }
 
            $data = $request->getParsedBody();
            $data = is_array($data) ? $data : [];
 
            if (isset($data['tour_id'])) {
                $tourId = filter_var($data['tour_id'], FILTER_VALIDATE_INT);
                if ($tourId === false || $tourId < 1) {
                    return $this->json($response, ['error' => 'Tour inválido.'], 422);
                }
                $tourProgramado->tour_id = $tourId;
            }

            if (isset($data['guia_id'])) {
                $guiaId = filter_var($data['guia_id'], FILTER_VALIDATE_INT);
                if ($guiaId === false || $guiaId < 1) {
                    return $this->json($response, ['error' => 'Guía inválido.'], 422);
                }
                $tourProgramado->guia_id = $guiaId;
            }

            if (isset($data['fecha_hora_inicio'])) {
                $fechaHoraInicio = trim((string) $data['fecha_hora_inicio']);
                if ($fechaHoraInicio === '' || mb_strlen($fechaHoraInicio) > 19) {
                    return $this->json($response, ['error' => 'Fecha y hora de inicio inválida.'], 422);
                }
                $tourProgramado->fecha_hora_inicio = $fechaHoraInicio;
            }

            if (isset($data['cupo'])) {
                $cupo = filter_var($data['cupo'], FILTER_VALIDATE_INT);
                if ($cupo === false || $cupo < 1) {
                    return $this->json($response, ['error' => 'Cupo inválido.'], 422);
                }
                $tourProgramado->cupo = $cupo;
            }

            if (isset($data['estado'])) {
                $estado = trim((string) $data['estado']);
                if ($estado === '' || mb_strlen($estado) > 20) {
                    return $this->json($response, ['error' => 'Estado inválido.'], 422);
                }
                $tourProgramado->estado = $estado;
            }

            try {
                $tourProgramado->save();
            } catch (QueryException $exception) {
                if ((int) ($exception->errorInfo[1] ?? 0) === 1452) { #1452: la llave foránea apunta a un registro que no existe
                    return $this->json($response, ['error' => 'Uno de los registros relacionados no existe.'], 422);
                }
                if ((int) ($exception->errorInfo[1] ?? 0) === 3819) { #3819: no se cumple un CHECK de la tabla
                    return $this->json($response, ['error' => 'El cupo debe ser mayor a 0.'], 422);
                }
                if ((int) ($exception->errorInfo[1] ?? 0) === 1265 || (int) ($exception->errorInfo[1] ?? 0) === 1292) { #1265 y 1292: estado o fecha con un valor no válido
                    return $this->json($response, ['error' => 'Alguna fecha o estado tiene un valor no válido.'], 422);
                }
                throw $exception;
            }
 
            return $this->json($response, ['data' => $tourProgramado->toArray()], 200);
        }
    }
 
    public function delete(Request $request, Response $response, array $args): Response {
        $eloquent = $this->container->get('eloquent');
        if (isset($args['id'])) {
            $tourProgramado = TourProgramadoModel::find($args['id']);
 
            if ($tourProgramado === null) {
                return $this->json($response, ['error' => 'Tour programado no encontrado.'], 404);
            }
 
            try {
                $tourProgramado->delete();
            } catch (QueryException $exception) {
                if ((int) ($exception->errorInfo[1] ?? 0) === 1451) { #1451: otros registros dependen de este
                    return $this->json($response, ['error' => 'No se puede eliminar porque tiene registros asociados.'], 409);
                }
 
                throw $exception;
            }
 
            return $this->json($response, ['message' => 'Tour programado eliminado correctamente.'], 200);
        }
    }
 
    private function json(Response $response, array $payload, int $status): Response{
    $response->getBody()->write(json_encode($payload,
        JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
 
        return $response
            ->withHeader('Content-Type', 'application/json; charset=UTF-8')
            ->withStatus($status);
    }
 
}
