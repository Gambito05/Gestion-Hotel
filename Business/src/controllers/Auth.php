<?php

declare(strict_types=1);

namespace App\controllers;

use App\Services\DataService;
use Firebase\JWT\JWT;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class Auth {
    public function __construct(private ContainerInterface $container) {}

    public function login(Request $request, Response $response): Response {
        $data = $request->getParsedBody();
        $data = is_array($data) ? $data : [];
        $username = trim((string) ($data['username'] ?? ''));
        $email = trim((string) ($data['email'] ?? ''));
        $password = (string) ($data['password'] ?? '');

        if (($username === '') === ($email === '') || $password === '') {
            return $this->json($response, [
                'error' => 'Debe enviar username o email, y la contraseña.',
            ], 422);
        }

        $credentialsRequest = ['password' => $password];
        if ($username !== '') {
            $credentialsRequest['username'] = $username;
        } else {
            $credentialsRequest['email'] = $email;
        }

        try {
            $service = $this->container->get(DataService::class);
            $upstream = $service->post(
                '/auth/credentials',
                json_encode($credentialsRequest, JSON_THROW_ON_ERROR)
            );

            if ($upstream->getStatusCode() === 401) {
                return $this->json($response, ['error' => 'Credenciales inválidas.'], 401);
            }
            if ($upstream->getStatusCode() !== 200) {
                return $this->json($response, [
                    'error' => 'No se pudieron validar las credenciales.',
                ], 502);
            }

            $credentials = json_decode((string) $upstream->getBody(), true);
            if (!is_array($credentials)
                || !isset($credentials['id'], $credentials['email'], $credentials['rol_id'])) {
                return $this->json($response, [
                    'error' => 'El servicio de datos devolvió una respuesta inválida.',
                ], 502);
            }

            $config = $this->container->get('config');
            $now = time();
            $credentials['token'] = JWT::encode([
                'iss' => 'gestion-hotel-business',
                'iat' => $now,
                'exp' => $now + $config->jwt_ttl,
                'sub' => (string) $credentials['id'],
                'username' => $credentials['username'] ?? null,
                'email' => $credentials['email'],
                'rol_id' => (int) $credentials['rol_id'],
            ], $config->key, 'HS256');

            return $this->json($response, $credentials, 200);
        } catch (ConnectException) {
            return $this->json($response, [
                'error' => 'El servicio de datos no está disponible.',
            ], 502);
        } catch (RequestException) {
            return $this->json($response, [
                'error' => 'No se pudo completar el inicio de sesión.',
            ], 502);
        }
    }

    private function json(Response $response, array $payload, int $status): Response {
        $response->getBody()->write(json_encode(
            $payload,
            JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
        ));

        return $response
            ->withHeader('Content-Type', 'application/json; charset=utf-8')
            ->withStatus($status);
    }
}
