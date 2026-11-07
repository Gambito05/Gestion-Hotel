<?php

declare(strict_types=1);

namespace App\controllers;

use App\Models\UsuarioModel;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class Auth {
    public function __construct(private ContainerInterface $container) {}

    public function credentials(Request $request, Response $response): Response {
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

        $this->container->get('eloquent');
        $usuario = UsuarioModel::query()
            ->where('activo', 1)
            ->where($username !== '' ? 'username' : 'email', $username !== '' ? $username : $email)
            ->first(['id', 'rol_id', 'username', 'email', 'password']);

        if ($usuario === null || !password_verify($password, (string) $usuario->password)) {
            return $this->json($response, ['error' => 'Credenciales inválidas.'], 401);
        }

        return $this->json($response, [
            'id' => (int) $usuario->id,
            'username' => $usuario->username,
            'email' => $usuario->email,
            'rol_id' => (int) $usuario->rol_id,
        ], 200);
    }

    public function updateTokenRef(Request $request, Response $response, array $args): Response {
        $data = $request->getParsedBody();
        $data = is_array($data) ? $data : [];
        $token = trim((string) ($data['tkRef'] ?? ''));
        $userId = filter_var($args['id'] ?? null, FILTER_VALIDATE_INT);

        if ($userId === false || $userId < 1) {
            return $this->json($response, ['error' => 'Datos de tkRef inválidos.'], 422);
        }

        $this->container->get('eloquent');
        $usuario = UsuarioModel::find($userId);
        if ($usuario === null) {
            return $this->json($response, ['error' => 'Usuario no encontrado.'], 404);
        }

        $usuario->tkRef = $token;
        $usuario->save();

        return $this->json($response, ['msj' => 'Token de refresco modificado.'], 200);
    }

    public function verifyToken(Request $request, Response $response, array $args): Response {
        $data = $request->getParsedBody();
        $data = is_array($data) ? $data : [];
        $token = $data['tkRef'] ?? null;
        $userId = filter_var($args['id'] ?? null, FILTER_VALIDATE_INT);

        if (!is_string($token) || $token === '' || $userId === false || $userId < 1) {
            return $this->json($response, ['error' => 'Datos de tkRef inválidos.'], 422);
        }

        $this->container->get('eloquent');
        $usuario = UsuarioModel::query()
            ->where('id', $userId)
            ->where('tkRef', $token)
            ->where('activo', 1)
            ->first(['id', 'username', 'email', 'rol_id']);

        if ($usuario === null) {
            return $this->json($response, ['error' => 'tkRef inválido.'], 401);
        }

        return $this->json($response, [
            'id' => (int) $usuario->id,
            'username' => $usuario->username,
            'email' => $usuario->email,
            'rol_id' => (int) $usuario->rol_id,
        ], 200);
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
