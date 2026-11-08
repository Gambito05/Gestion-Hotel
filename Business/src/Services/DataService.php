<?php
namespace App\Services;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;

final class DataService {
    private ClientInterface $client;
    public function __construct(object $config) {
        $this->client = new Client([
            'base_uri' => rtrim($config->api_data, '/') . '/',
            'timeout' => $config->timeout,
            'connect_timeout' => $config->timeout,
            'http_errors' => false,
            'headers' => [
                'Accept' => 'application/json'
            ]
        ]);
    }

    public function get(string $path, array $query= []) : ResponseInterface { #cuando tiene un igual es un parámetro opcional
        return $this->client->request('GET', ltrim($path, '/'),
            ['query' => $query]);
    }

    public function filter(string $path, array $query= []) : ResponseInterface{ 
        return $this->client->request('GET', ltrim($path, '/'),
            ['query' => $query]);
    }

    public function post(string $path, string $body) : ResponseInterface{
        return $this->client->request('POST', ltrim($path, '/'),
            ['body' => $body,
            'headers' => [
                'Content-Type' => 'application/json']
            ]);
    }

    public function put(string $path, string $body) : ResponseInterface{
        return $this->client->request('PUT', ltrim($path, '/'),
            ['body' => $body,
            'headers' => [
                'Content-Type' => 'application/json']
            ]);
    }

     public function delete(string $path) : ResponseInterface{
        return $this->client->request('DELETE', ltrim($path, '/'));
    }

    #Devuelve el JSON de la capa de datos como arreglo, o null si la respuesta no es 200 (por ejemplo 404)
    public function obtener(string $path, array $query = []) : ?array{
        $upstream = $this->get($path, $query);
        if ($upstream->getStatusCode() !== 200) {
            return null;
        }
        $datos = json_decode((string) $upstream->getBody(), true);
        return is_array($datos) ? $datos : null;
    }

    #Recorre todas las páginas de un filtro de la capa de datos y devuelve todos los registros juntos
    public function obtenerTodos(string $recurso, array $query = []) : array{
        $registros = [];
        $offset = 0;
        $limit = 100;

        do {
            $pagina = $this->obtener(trim($recurso, '/') . '/filter/' . $offset . '/' . $limit, $query);
            if ($pagina === null) {
                break;
            }
            $registros = array_merge($registros, $pagina['data'] ?? []);
            $total = (int) ($pagina['pagination']['total'] ?? 0);
            $offset += $limit;
        } while ($offset < $total);

        return $registros;
    }
}