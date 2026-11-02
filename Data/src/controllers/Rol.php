<?php
 
namespace App\controllers;
 
use App\Models\RolModel;
use Illuminate\Database\QueryException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Container\ContainerInterface;
 
class Rol {
    private ContainerInterface $container;
 
    public function __construct(ContainerInterface $c){
        $this->container = $c;
    }
 
    public function read(Request $request, Response $response, array $args): Response {
        $this->container->get('eloquent');
        if(isset($args['id'])){
            $rol = RolModel::query()
                ->select(['id', 'nombre'])
                ->find($args['id']);
 
            if ($rol === null) {
                return $this->json($response, ['error' => 'Rol no encontrado.'], 404);
            }
 
            return $this->json($response, ['data' => $rol], 200);
        }
 
        $roles = RolModel::query()
                ->select(['id', 'nombre'])
                ->get()
                ->toArray();
            return $this->json($response, ['data' => $roles], 200);
    }
 
    public function create(Request $request, Response $response, array $args): Response {
        $data = $request->getParsedBody();
 
        $data = is_array($data) ? $data : [];
 
        $errors = [];
        $nombre = trim((string) ($data['nombre'] ?? ''));
 
        if ($nombre === '' || mb_strlen($nombre) > 30) {
            $errors['nombre'] = 'Es obligatorio y debe tener máximo 30 caracteres.';
        }
 
        if ($errors !== []) {
            return $this->json($response, ['errors' => $errors], 422);
        }
 
        try {
            $this->container->get('eloquent');
            $rol = RolModel::create([
                'nombre' => $nombre,
            ]);
        } catch (QueryException $exception) {
            if ((int) ($exception->errorInfo[1] ?? 0) === 1062) { #1062: el valor ya existe en una columna UNIQUE
                return $this->json($response, ['error' => 'El rol ya existe.'], 409);
            }
            throw $exception;
        }
 
        return $this->json($response, ['data' => $rol->toArray()], 201);
    }
 
    public function update(Request $request, Response $response, array $args): Response {
        $eloquent = $this->container->get('eloquent');
        if (isset($args['id'])) {
            $rol = RolModel::find($args['id']);
 
            if ($rol === null) {
                return $this->json($response, ['error' => 'Rol no encontrado.'], 404);
            }
 
            $data = $request->getParsedBody();
            $data = is_array($data) ? $data : [];
 
            if (isset($data['nombre'])) {
                $nombre = trim((string) $data['nombre']);
                if ($nombre === '' || mb_strlen($nombre) > 30) {
                    return $this->json($response, ['error' => 'Nombre inválido.'], 422);
                }
                $rol->nombre = $nombre;
            }

            try {
                $rol->save();
            } catch (QueryException $exception) {
                if ((int) ($exception->errorInfo[1] ?? 0) === 1062) { #1062: el valor ya existe en una columna UNIQUE
                    return $this->json($response, ['error' => 'El rol ya existe.'], 409);
                }
                throw $exception;
            }
 
            return $this->json($response, ['data' => $rol->toArray()], 200);
        }
    }
 
    public function delete(Request $request, Response $response, array $args): Response {
        $eloquent = $this->container->get('eloquent');
        if (isset($args['id'])) {
            $rol = RolModel::find($args['id']);
 
            if ($rol === null) {
                return $this->json($response, ['error' => 'Rol no encontrado.'], 404);
            }
 
            try {
                $rol->delete();
            } catch (QueryException $exception) {
                if ((int) ($exception->errorInfo[1] ?? 0) === 1451) { #1451: otros registros dependen de este
                    return $this->json($response, ['error' => 'No se puede eliminar porque tiene registros asociados.'], 409);
                }
 
                throw $exception;
            }
 
            return $this->json($response, ['message' => 'Rol eliminado correctamente.'], 200);
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
