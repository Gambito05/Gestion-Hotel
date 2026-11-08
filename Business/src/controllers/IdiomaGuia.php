<?php

namespace App\controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Container\ContainerInterface;
use App\Services\DataService;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use App\Validators\IdiomaGuiaValidator;

class IdiomaGuia {

    public function __construct(private ContainerInterface $container){}

    public function read(Request $request, Response $response, array $args): Response {
        try {
            $dataservice = $this->container->get(DataService::class);
            $path = 'idiomas-guia';
            $upstream = $dataservice->get($path);

            $response->getBody()->write((string) $upstream->getBody());
            return $response
                ->withHeader(
                    'Content-Type', $upstream->getHeaderLine('Content-Type') ? : 'application/json; charset=utf-8')
                ->withStatus($upstream->getStatusCode());
        }catch (ConnectException){
            return $this->json($response, ['error' => 'El servicio de datos no está disponible'], 502);
        }
        catch (RequestException){
            return $this->json($response, ['error' => 'No se pudo consultar el servicio de datos'], 502);
        }
    }

    public function filter(Request $request, Response $response, array $args): Response {
        $offset = filter_var($args['offset'] ?? 0, FILTER_VALIDATE_INT);
        $limit = filter_var($args['limit'] ?? 20, FILTER_VALIDATE_INT);

        if($offset === false || $offset < 0 || $limit === false || $limit < 1 || $limit > 100){
            return $this->json($response,
                ['error' => 'El offset debe ser mayor o igual 0 y el límite debe estar entre 1 y 100.'], 422);
        }

        try {
            $params = $request->getQueryParams();

            #Se envían siempre todas las llaves para que la capa de datos no reciba parámetros indefinidos
            $query = [
                'guia_id' => trim((string) ($params['guia_id'] ?? '')),
                'guia' => trim((string) ($params['guia'] ?? '')),
                'idioma_id' => trim((string) ($params['idioma_id'] ?? '')),
                'idioma' => trim((string) ($params['idioma'] ?? ''))
            ];

            $dataservice = $this->container->get(DataService::class);
            $upstream = $dataservice->get('idiomas-guia/filter/' . $offset . '/' . $limit, $query);

            $response->getBody()->write((string) $upstream->getBody());
            return $response
                ->withHeader(
                    'Content-Type', $upstream->getHeaderLine('Content-Type') ? : 'application/json; charset=utf-8')
                ->withStatus($upstream->getStatusCode());
        }catch (ConnectException){
            return $this->json($response, ['error' => 'El servicio de datos no está disponible'], 502);
        }
        catch (RequestException){
            return $this->json($response, ['error' => 'No se pudo consultar el servicio de datos'], 502);
        }
    }

    public function create(Request $request, Response $response, array $args): Response {
        try {
            $request->getBody()->rewind();
            $body = (string) $request->getBody();

            $data = json_decode($body, true);

            if (!is_array($data) || json_last_error() !== JSON_ERROR_NONE) {
                return $this->json($response, ['errors' => ['body' => 'Debe enviar un objeto JSON válido']], 400);
            }

            #Validación en la capa de negocio: si falla, no se consume la capa de datos
            $errors = (new IdiomaGuiaValidator())->validate($data);
            if ($errors !== []) {
                return $this->json($response, ['errors' => $errors], 400);
            }

            $dataservice = $this->container->get(DataService::class);
            $upstream = $dataservice->post('idiomas-guia', json_encode($data, JSON_THROW_ON_ERROR));

            return $this->json($response, json_decode((string) $upstream->getBody(), true), $upstream->getStatusCode());
        }catch (ConnectException){
            return $this->json($response, ['error' => 'El servicio de datos no está disponible'], 502);
        }
        catch (RequestException){
            return $this->json($response, ['error' => 'No se pudo consultar el servicio de datos'], 502);
        }
    }

    public function delete(Request $request, Response $response, array $args): Response {
        try {
            $dataservice = $this->container->get(DataService::class);
            $upstream = $dataservice->delete('idiomas-guia/' . rawurlencode($args['guia_id']) . '/' . rawurlencode($args['idioma_id']));

            return $this->json($response, json_decode((string) $upstream->getBody(), true), $upstream->getStatusCode());

        }catch (ConnectException){
            return $this->json($response, ['error' => 'El servicio de datos no está disponible'], 502);
        }
        catch (RequestException){
            return $this->json($response, ['error' => 'No se pudo consultar el servicio de datos'], 502);
        }
    }

    private function json(Response $response, array $payload, int $status): Response{
        $response->getBody()->write(json_encode($payload,
            JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));

        return $response
            ->withHeader('Content-Type','application/json; charset=utf-8')
            ->withStatus($status);
    }

}
