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
 
 
    private function json(Response $response, array $payload, int $status): Response{
    $response->getBody()->write(json_encode($payload,
        JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
 
        return $response
            ->withHeader('Content-Type', 'application/json; charset=UTF-8')
            ->withStatus($status);
    }
 
}
