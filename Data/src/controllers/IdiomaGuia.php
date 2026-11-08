<?php
 
namespace App\controllers;
 
use App\Models\IdiomaGuiaModel;
use Illuminate\Database\QueryException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Container\ContainerInterface;
 
 
class IdiomaGuia {
    private ContainerInterface $container;
 
    public function __construct(ContainerInterface $c){
        $this->container = $c;
    }
 
    public function read(Request $request, Response $response, array $args): Response {
        $this->container->get('eloquent');
        $idiomasGuia = IdiomaGuiaModel::query()
                ->select(['guia_id', 'idioma_id'])
                ->get()
                ->toArray();
            return $this->json($response, ['data' => $idiomasGuia], 200);
    }
 
    public function create(Request $request, Response $response, array $args): Response {
        $data = $request->getParsedBody();
 
        $data = is_array($data) ? $data : [];
 
        $errors = [];
        $guiaId = filter_var($data['guia_id'] ?? '', FILTER_VALIDATE_INT);
        $idiomaId = filter_var($data['idioma_id'] ?? '', FILTER_VALIDATE_INT);
 
        if ($guiaId === false || $guiaId < 1) {
            $errors['guia_id'] = 'Es obligatorio y debe ser un id válido.';
        }
        if ($idiomaId === false || $idiomaId < 1) {
            $errors['idioma_id'] = 'Es obligatorio y debe ser un id válido.';
        }
 
        if ($errors !== []) {
            return $this->json($response, ['errors' => $errors], 422);
        }
 
        try {
            $this->container->get('eloquent');
            IdiomaGuiaModel::create([
                'guia_id' => $guiaId,
                'idioma_id' => $idiomaId,
            ]);
        } catch (QueryException $exception) {
            if ((int) ($exception->errorInfo[1] ?? 0) === 1062) { #1062: la pareja ya existe
                return $this->json($response, ['error' => 'El guía ya tiene registrado ese idioma.'], 409);
            }
            if ((int) ($exception->errorInfo[1] ?? 0) === 1452) { #1452: la llave foránea apunta a un registro que no existe
                return $this->json($response, ['error' => 'No existe el guía o el idioma indicado.'], 422);
            }
            throw $exception;
        }
 
        return $this->json($response, ['data' => ['guia_id' => $guiaId, 'idioma_id' => $idiomaId]], 201);
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
        $guiaId = filter_var($params['guia_id'] ?? '', FILTER_VALIDATE_INT);
        $guia = trim((string) ($params['guia'] ?? ''));
        $idiomaId = filter_var($params['idioma_id'] ?? '', FILTER_VALIDATE_INT);
        $idioma = trim((string) ($params['idioma'] ?? ''));

        $query = IdiomaGuiaModel::query()
            ->select([
                'idiomas_guia.guia_id',
                'guias.nombre as guia_nombre',
                'idiomas_guia.idioma_id',
                'idiomas.nombre as idioma_nombre'
            ])
            ->join('guias', 'idiomas_guia.guia_id', '=', 'guias.id')
            ->join('idiomas', 'idiomas_guia.idioma_id', '=', 'idiomas.id');

        if ($guiaId !== false) {
            $query->where('idiomas_guia.guia_id', $guiaId);
        }
        if ($guia !== '') {
            $query->where('guias.nombre', 'like', "%{$guia}%");
        }
        if ($idiomaId !== false) {
            $query->where('idiomas_guia.idioma_id', $idiomaId);
        }
        if ($idioma !== '') {
            $query->where('idiomas.nombre', 'like', "%{$idioma}%");
        }

        $total = (clone $query)->count('idiomas_guia.guia_id');

        $idiomasGuia = $query
            ->orderBy('guias.nombre')
            ->offset($offset)
            ->limit($limit)
            ->get()
            ->toArray();

        return $this->json($response, [
            'data' => $idiomasGuia,
            'pagination' => [
                'offset' => $offset,
                'limit' => $limit,
                'total' => $total
            ]
        ], 200);
    }
 
 
 
    public function delete(Request $request, Response $response, array $args): Response {
        $guiaId = filter_var($args['guia_id'] ?? '', FILTER_VALIDATE_INT);
        $idiomaId = filter_var($args['idioma_id'] ?? '', FILTER_VALIDATE_INT);

        if ($guiaId === false || $guiaId < 1 || $idiomaId === false || $idiomaId < 1) {
            return $this->json($response, ['error' => 'Los ids deben ser números enteros válidos.'], 422);
        }

        $this->container->get('eloquent');
        #La llave primaria es compuesta, por eso se borra con una consulta y no con find()
        $eliminados = IdiomaGuiaModel::query()
            ->where('guia_id', $guiaId)
            ->where('idioma_id', $idiomaId)
            ->delete();

        if ($eliminados === 0) {
            return $this->json($response, ['error' => 'El guía no tiene asignado ese idioma.'], 404);
        }

        return $this->json($response, ['message' => 'Idioma quitado del guía correctamente.'], 200);
    }

    private function json(Response $response, array $payload, int $status): Response{
    $response->getBody()->write(json_encode($payload,
        JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
 
        return $response
            ->withHeader('Content-Type', 'application/json; charset=UTF-8')
            ->withStatus($status);
    }
 
}
