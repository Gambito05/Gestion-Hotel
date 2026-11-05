<?php
 
namespace App\controllers;
 
use App\Models\ServicioIncluidoModel;
use Illuminate\Database\QueryException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Container\ContainerInterface;
 
 
class ServicioIncluido {
    private ContainerInterface $container;
 
    public function __construct(ContainerInterface $c){
        $this->container = $c;
    }
 
    public function read(Request $request, Response $response, array $args): Response {
        $this->container->get('eloquent');
        $serviciosIncluidos = ServicioIncluidoModel::query()
                ->select(['tipo_habitacion_id', 'servicio_habitacion_id'])
                ->get()
                ->toArray();
            return $this->json($response, ['data' => $serviciosIncluidos], 200);
    }
 
    public function create(Request $request, Response $response, array $args): Response {
        $data = $request->getParsedBody();
 
        $data = is_array($data) ? $data : [];
 
        $errors = [];
        $tipoHabitacionId = filter_var($data['tipo_habitacion_id'] ?? '', FILTER_VALIDATE_INT);
        $servicioHabitacionId = filter_var($data['servicio_habitacion_id'] ?? '', FILTER_VALIDATE_INT);
 
        if ($tipoHabitacionId === false || $tipoHabitacionId < 1) {
            $errors['tipo_habitacion_id'] = 'Es obligatorio y debe ser un id válido.';
        }
        if ($servicioHabitacionId === false || $servicioHabitacionId < 1) {
            $errors['servicio_habitacion_id'] = 'Es obligatorio y debe ser un id válido.';
        }
 
        if ($errors !== []) {
            return $this->json($response, ['errors' => $errors], 422);
        }
 
        try {
            $this->container->get('eloquent');
            ServicioIncluidoModel::create([
                'tipo_habitacion_id' => $tipoHabitacionId,
                'servicio_habitacion_id' => $servicioHabitacionId,
            ]);
        } catch (QueryException $exception) {
            if ((int) ($exception->errorInfo[1] ?? 0) === 1062) { #1062: la pareja ya existe
                return $this->json($response, ['error' => 'Ese servicio ya está incluido en el tipo de habitación.'], 409);
            }
            if ((int) ($exception->errorInfo[1] ?? 0) === 1452) { #1452: la llave foránea apunta a un registro que no existe
                return $this->json($response, ['error' => 'No existe el tipo de habitación o el servicio indicado.'], 422);
            }
            throw $exception;
        }
 
        return $this->json($response, ['data' => ['tipo_habitacion_id' => $tipoHabitacionId, 'servicio_habitacion_id' => $servicioHabitacionId]], 201);
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
        $tipoHabitacionId = filter_var($params['tipo_habitacion_id'] ?? '', FILTER_VALIDATE_INT);
        $tipo = trim((string) ($params['tipo'] ?? ''));
        $servicioHabitacionId = filter_var($params['servicio_habitacion_id'] ?? '', FILTER_VALIDATE_INT);
        $servicio = trim((string) ($params['servicio'] ?? ''));

        $query = ServicioIncluidoModel::query()
            ->select([
                'servicios_incluidos.tipo_habitacion_id',
                'tipos_habitacion.nombre as tipo_habitacion_nombre',
                'servicios_incluidos.servicio_habitacion_id',
                'servicios_habitacion.nombre as servicio_habitacion_nombre'
            ])
            ->join('tipos_habitacion', 'servicios_incluidos.tipo_habitacion_id', '=', 'tipos_habitacion.id')
            ->join('servicios_habitacion', 'servicios_incluidos.servicio_habitacion_id', '=', 'servicios_habitacion.id');

        if ($tipoHabitacionId !== false) {
            $query->where('servicios_incluidos.tipo_habitacion_id', $tipoHabitacionId);
        }
        if ($tipo !== '') {
            $query->where('tipos_habitacion.nombre', 'like', "%{$tipo}%");
        }
        if ($servicioHabitacionId !== false) {
            $query->where('servicios_incluidos.servicio_habitacion_id', $servicioHabitacionId);
        }
        if ($servicio !== '') {
            $query->where('servicios_habitacion.nombre', 'like', "%{$servicio}%");
        }

        $total = (clone $query)->count('servicios_incluidos.tipo_habitacion_id');

        $serviciosIncluidos = $query
            ->orderBy('tipos_habitacion.nombre')
            ->offset($offset)
            ->limit($limit)
            ->get()
            ->toArray();

        return $this->json($response, [
            'data' => $serviciosIncluidos,
            'pagination' => [
                'offset' => $offset,
                'limit' => $limit,
                'total' => $total
            ]
        ], 200);
    }
 
 
    private function json(Response $response, array $payload, int $status): Response{
    $response->getBody()->write(json_encode($payload,
        JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
 
        return $response
            ->withHeader('Content-Type', 'application/json; charset=UTF-8')
            ->withStatus($status);
    }
 
}
