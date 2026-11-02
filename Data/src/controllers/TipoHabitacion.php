<?php
 
namespace App\controllers;
 
use App\Models\TipoHabitacionModel;
use Illuminate\Database\QueryException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Container\ContainerInterface;
 
class TipoHabitacion {
    private ContainerInterface $container;
 
    public function __construct(ContainerInterface $c){
        $this->container = $c;
    }
 
    public function read(Request $request, Response $response, array $args): Response {
        $this->container->get('eloquent');
        if(isset($args['id'])){
            $tipoHabitacion = TipoHabitacionModel::query()
                ->select(['id', 'nombre', 'descripcion', 'capacidad', 'precio_noche', 'activo'])
                ->find($args['id']);
 
            if ($tipoHabitacion === null) {
                return $this->json($response, ['error' => 'Tipo de habitación no encontrado.'], 404);
            }
 
            return $this->json($response, ['data' => $tipoHabitacion], 200);
        }
 
        $tiposHabitacion = TipoHabitacionModel::query()
                ->select(['id', 'nombre', 'descripcion', 'capacidad', 'precio_noche', 'activo'])
                ->get()
                ->toArray();
            return $this->json($response, ['data' => $tiposHabitacion], 200);
    }
 
    public function create(Request $request, Response $response, array $args): Response {
        $data = $request->getParsedBody();
 
        $data = is_array($data) ? $data : [];
 
        $errors = [];
        $nombre = trim((string) ($data['nombre'] ?? ''));
        $descripcion = isset($data['descripcion']) ? trim((string) $data['descripcion']) : null;
        $capacidad = filter_var($data['capacidad'] ?? '', FILTER_VALIDATE_INT);
        $precioNoche = $data['precio_noche'] ?? '';
        $activo = filter_var($data['activo'] ?? 1, FILTER_VALIDATE_INT);
 
        if ($nombre === '' || mb_strlen($nombre) > 60) {
            $errors['nombre'] = 'Es obligatorio y debe tener máximo 60 caracteres.';
        }
        if ($descripcion !== null && mb_strlen($descripcion) > 1000) {
            $errors['descripcion'] = 'Debe tener máximo 1000 caracteres.';
        }
        if ($descripcion !== null && $descripcion === '') {
            $descripcion = null;
        }
        if ($capacidad === false || $capacidad < 1) {
            $errors['capacidad'] = 'Es obligatorio y debe ser un número entero mayor o igual a 1.';
        }
        if (!is_numeric($precioNoche) || $precioNoche <= 0) {
            $errors['precio_noche'] = 'Es obligatorio, debe ser numérico y mayor a 0.';
        }
        if ($activo === false || $activo < 0 || $activo > 1) {
            $errors['activo'] = 'Debe ser 0 o 1.';
        }
 
        if ($errors !== []) {
            return $this->json($response, ['errors' => $errors], 422);
        }
 
        try {
            $this->container->get('eloquent');
            $tipoHabitacion = TipoHabitacionModel::create([
                'nombre' => $nombre,
                'descripcion' => $descripcion,
                'capacidad' => $capacidad,
                'precio_noche' => $precioNoche,
                'activo' => $activo,
            ]);
        } catch (QueryException $exception) {
            if ((int) ($exception->errorInfo[1] ?? 0) === 1062) { #1062: el valor ya existe en una columna UNIQUE
                return $this->json($response, ['error' => 'Ya existe un tipo de habitación con ese nombre.'], 409);
            }
            if ((int) ($exception->errorInfo[1] ?? 0) === 3819) { #3819: no se cumple un CHECK de la tabla
                return $this->json($response, ['error' => 'La capacidad y el precio por noche deben ser mayores a 0.'], 422);
            }
            throw $exception;
        }
 
        return $this->json($response, ['data' => $tipoHabitacion->toArray()], 201);
    }
 
    public function update(Request $request, Response $response, array $args): Response {
        $eloquent = $this->container->get('eloquent');
        if (isset($args['id'])) {
            $tipoHabitacion = TipoHabitacionModel::find($args['id']);
 
            if ($tipoHabitacion === null) {
                return $this->json($response, ['error' => 'Tipo de habitación no encontrado.'], 404);
            }
 
            $data = $request->getParsedBody();
            $data = is_array($data) ? $data : [];
 
            if (isset($data['nombre'])) {
                $nombre = trim((string) $data['nombre']);
                if ($nombre === '' || mb_strlen($nombre) > 60) {
                    return $this->json($response, ['error' => 'Nombre inválido.'], 422);
                }
                $tipoHabitacion->nombre = $nombre;
            }

            if (isset($data['descripcion'])) {
                $descripcion = trim((string) $data['descripcion']);
                if (mb_strlen($descripcion) > 1000) {
                    return $this->json($response, ['error' => 'Descripción inválida.'], 422);
                }
                $tipoHabitacion->descripcion = $descripcion === '' ? null : $descripcion;
            }

            if (isset($data['capacidad'])) {
                $capacidad = filter_var($data['capacidad'], FILTER_VALIDATE_INT);
                if ($capacidad === false || $capacidad < 1) {
                    return $this->json($response, ['error' => 'Capacidad inválida.'], 422);
                }
                $tipoHabitacion->capacidad = $capacidad;
            }

            if (isset($data['precio_noche'])) {
                $precioNoche = $data['precio_noche'];
                if (!is_numeric($precioNoche) || $precioNoche <= 0) {
                    return $this->json($response, ['error' => 'Precio por noche inválido.'], 422);
                }
                $tipoHabitacion->precio_noche = $precioNoche;
            }

            if (isset($data['activo'])) {
                $activo = filter_var($data['activo'], FILTER_VALIDATE_INT);
                if ($activo === false || $activo < 0 || $activo > 1) {
                    return $this->json($response, ['error' => 'Valor de activo inválido.'], 422);
                }
                $tipoHabitacion->activo = $activo;
            }

            try {
                $tipoHabitacion->save();
            } catch (QueryException $exception) {
                if ((int) ($exception->errorInfo[1] ?? 0) === 1062) { #1062: el valor ya existe en una columna UNIQUE
                    return $this->json($response, ['error' => 'Ya existe un tipo de habitación con ese nombre.'], 409);
                }
                if ((int) ($exception->errorInfo[1] ?? 0) === 3819) { #3819: no se cumple un CHECK de la tabla
                    return $this->json($response, ['error' => 'La capacidad y el precio por noche deben ser mayores a 0.'], 422);
                }
                throw $exception;
            }
 
            return $this->json($response, ['data' => $tipoHabitacion->toArray()], 200);
        }
    }
 
    public function delete(Request $request, Response $response, array $args): Response {
        $eloquent = $this->container->get('eloquent');
        if (isset($args['id'])) {
            $tipoHabitacion = TipoHabitacionModel::find($args['id']);
 
            if ($tipoHabitacion === null) {
                return $this->json($response, ['error' => 'Tipo de habitación no encontrado.'], 404);
            }
 
            try {
                $tipoHabitacion->delete();
            } catch (QueryException $exception) {
                if ((int) ($exception->errorInfo[1] ?? 0) === 1451) { #1451: otros registros dependen de este
                    return $this->json($response, ['error' => 'No se puede eliminar porque tiene registros asociados.'], 409);
                }
 
                throw $exception;
            }
 
            return $this->json($response, ['message' => 'Tipo de habitación eliminado correctamente.'], 200);
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
