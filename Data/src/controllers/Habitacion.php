<?php
 
namespace App\controllers;
 
use App\Models\HabitacionModel;
use Illuminate\Database\QueryException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Container\ContainerInterface;
 
class Habitacion {
    private ContainerInterface $container;
 
    public function __construct(ContainerInterface $c){
        $this->container = $c;
    }
 
    public function read(Request $request, Response $response, array $args): Response {
        $this->container->get('eloquent');
        if(isset($args['id'])){
            $habitacion = HabitacionModel::query()
                ->select(['id', 'tipo_habitacion_id', 'numero', 'piso', 'estado'])
                ->find($args['id']);
 
            if ($habitacion === null) {
                return $this->json($response, ['error' => 'Habitación no encontrada.'], 404);
            }
 
            return $this->json($response, ['data' => $habitacion], 200);
        }
 
        $habitaciones = HabitacionModel::query()
                ->select(['id', 'tipo_habitacion_id', 'numero', 'piso', 'estado'])
                ->get()
                ->toArray();
            return $this->json($response, ['data' => $habitaciones], 200);
    }
 
    public function create(Request $request, Response $response, array $args): Response {
        $data = $request->getParsedBody();
 
        $data = is_array($data) ? $data : [];
 
        $errors = [];
        $tipoHabitacionId = filter_var($data['tipo_habitacion_id'] ?? '', FILTER_VALIDATE_INT);
        $numero = trim((string) ($data['numero'] ?? ''));
        $piso = filter_var($data['piso'] ?? '', FILTER_VALIDATE_INT);
        $estado = trim((string) ($data['estado'] ?? 'DISPONIBLE'));
 
        if ($tipoHabitacionId === false || $tipoHabitacionId < 1) {
            $errors['tipo_habitacion_id'] = 'Es obligatorio y debe ser un id válido.';
        }
        if ($numero === '' || mb_strlen($numero) > 10) {
            $errors['numero'] = 'Es obligatorio y debe tener máximo 10 caracteres.';
        }
        if ($piso === false) {
            $errors['piso'] = 'Es obligatorio y debe ser un número entero.';
        }
        if ($estado === '' || mb_strlen($estado) > 20) {
            $errors['estado'] = 'Debe ser un valor válido.';
        }
 
        if ($errors !== []) {
            return $this->json($response, ['errors' => $errors], 422);
        }
 
        try {
            $this->container->get('eloquent');
            $habitacion = HabitacionModel::create([
                'tipo_habitacion_id' => $tipoHabitacionId,
                'numero' => $numero,
                'piso' => $piso,
                'estado' => $estado,
            ]);
        } catch (QueryException $exception) {
            if ((int) ($exception->errorInfo[1] ?? 0) === 1062) { #1062: el valor ya existe en una columna UNIQUE
                return $this->json($response, ['error' => 'Ya existe una habitación con ese número.'], 409);
            }
            if ((int) ($exception->errorInfo[1] ?? 0) === 1452) { #1452: la llave foránea apunta a un registro que no existe
                return $this->json($response, ['error' => 'Uno de los registros relacionados no existe.'], 422);
            }
            if ((int) ($exception->errorInfo[1] ?? 0) === 1265 || (int) ($exception->errorInfo[1] ?? 0) === 1292) { #1265 y 1292: estado o fecha con un valor no válido
                return $this->json($response, ['error' => 'Alguna fecha o estado tiene un valor no válido.'], 422);
            }
            throw $exception;
        }
 
        return $this->json($response, ['data' => $habitacion->toArray()], 201);
    }
    public function filter(Request $request, Response $response, array $args): Response {
        $this->container->get('eloquent');

        $offset = filter_var($args['offset'] ?? 0, FILTER_VALIDATE_INT);
        $limit = filter_var($args['limit'] ?? 20, FILTER_VALIDATE_INT);

        if ($offset === false || $offset < 0 || $limit === false || $limit < 1 || $limit > 100) {
            return $this->json($response, [
                'error' => 'El offset debe ser mayor o igual a 0 y el límite debe estar entre 1 y 100.'],
                422);
        }

        $params = $request->getQueryParams();
        $numero = trim((string) ($params['numero'] ?? ''));
        $piso = filter_var($params['piso'] ?? '', FILTER_VALIDATE_INT);
        $estado = trim((string) ($params['estado'] ?? ''));
        $tipoHabitacionId = filter_var($params['tipo_habitacion_id'] ?? '', FILTER_VALIDATE_INT);
        $tipo = trim((string) ($params['tipo'] ?? ''));

        $query = HabitacionModel::query()
            ->select([
                'habitaciones.id',
                'habitaciones.tipo_habitacion_id',
                'tipos_habitacion.nombre as tipo_habitacion_nombre',
                'habitaciones.numero',
                'habitaciones.piso',
                'habitaciones.estado'
            ])
            ->join('tipos_habitacion', 'habitaciones.tipo_habitacion_id', '=', 'tipos_habitacion.id');

        if ($numero !== '') {
            $query->where('habitaciones.numero', 'like', "%{$numero}%");
        }
        if ($piso !== false) {
            $query->where('habitaciones.piso', $piso);
        }
        if ($estado !== '') {
            $query->where('habitaciones.estado', $estado);
        }
        if ($tipoHabitacionId !== false) {
            $query->where('habitaciones.tipo_habitacion_id', $tipoHabitacionId);
        }
        if ($tipo !== '') {
            $query->where('tipos_habitacion.nombre', 'like', "%{$tipo}%");
        }

        $total = (clone $query)->count('habitaciones.id');

        $habitaciones = $query
            ->orderBy('habitaciones.numero')
            ->offset($offset)
            ->limit($limit)
            ->get()
            ->toArray();

        return $this->json($response, [
            'data' => $habitaciones,
            'pagination' => [
                'offset' => $offset,
                'limit' => $limit,
                'total' => $total
            ]
        ], 200);
    }
 
 
    public function update(Request $request, Response $response, array $args): Response {
        $eloquent = $this->container->get('eloquent');
        if (isset($args['id'])) {
            $habitacion = HabitacionModel::find($args['id']);
 
            if ($habitacion === null) {
                return $this->json($response, ['error' => 'Habitación no encontrada.'], 404);
            }
 
            $data = $request->getParsedBody();
            $data = is_array($data) ? $data : [];
 
            if (isset($data['tipo_habitacion_id'])) {
                $tipoHabitacionId = filter_var($data['tipo_habitacion_id'], FILTER_VALIDATE_INT);
                if ($tipoHabitacionId === false || $tipoHabitacionId < 1) {
                    return $this->json($response, ['error' => 'Tipo de habitación inválido.'], 422);
                }
                $habitacion->tipo_habitacion_id = $tipoHabitacionId;
            }

            if (isset($data['numero'])) {
                $numero = trim((string) $data['numero']);
                if ($numero === '' || mb_strlen($numero) > 10) {
                    return $this->json($response, ['error' => 'Número de habitación inválido.'], 422);
                }
                $habitacion->numero = $numero;
            }

            if (isset($data['piso'])) {
                $piso = filter_var($data['piso'], FILTER_VALIDATE_INT);
                if ($piso === false) {
                    return $this->json($response, ['error' => 'Piso inválido.'], 422);
                }
                $habitacion->piso = $piso;
            }

            if (isset($data['estado'])) {
                $estado = trim((string) $data['estado']);
                if ($estado === '' || mb_strlen($estado) > 20) {
                    return $this->json($response, ['error' => 'Estado inválido.'], 422);
                }
                $habitacion->estado = $estado;
            }

            try {
                $habitacion->save();
            } catch (QueryException $exception) {
                if ((int) ($exception->errorInfo[1] ?? 0) === 1062) { #1062: el valor ya existe en una columna UNIQUE
                    return $this->json($response, ['error' => 'Ya existe una habitación con ese número.'], 409);
                }
                if ((int) ($exception->errorInfo[1] ?? 0) === 1452) { #1452: la llave foránea apunta a un registro que no existe
                    return $this->json($response, ['error' => 'Uno de los registros relacionados no existe.'], 422);
                }
                if ((int) ($exception->errorInfo[1] ?? 0) === 1265 || (int) ($exception->errorInfo[1] ?? 0) === 1292) { #1265 y 1292: estado o fecha con un valor no válido
                    return $this->json($response, ['error' => 'Alguna fecha o estado tiene un valor no válido.'], 422);
                }
                throw $exception;
            }
 
            return $this->json($response, ['data' => $habitacion->toArray()], 200);
        }
    }
 
    public function delete(Request $request, Response $response, array $args): Response {
        $eloquent = $this->container->get('eloquent');
        if (isset($args['id'])) {
            $habitacion = HabitacionModel::find($args['id']);
 
            if ($habitacion === null) {
                return $this->json($response, ['error' => 'Habitación no encontrada.'], 404);
            }
 
            try {
                $habitacion->delete();
            } catch (QueryException $exception) {
                if ((int) ($exception->errorInfo[1] ?? 0) === 1451) { #1451: otros registros dependen de este
                    return $this->json($response, ['error' => 'No se puede eliminar porque tiene registros asociados.'], 409);
                }
 
                throw $exception;
            }
 
            return $this->json($response, ['message' => 'Habitación eliminada correctamente.'], 200);
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
