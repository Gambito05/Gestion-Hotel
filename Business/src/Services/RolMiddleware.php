<?php
declare(strict_types=1);

namespace App\Services;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response as SlimResponse;

#Revisa que el rol que viene en el JWT tenga permiso para usar la ruta
final class RolMiddleware implements MiddlewareInterface {
    #Los id corresponden al orden en que se insertan los roles en gestion_hotel.sql
    public const ADMIN = 1;
    public const RECEPCIONISTA = 2;
    public const CLIENTE = 3;

    public function __construct(private array $rolesPermitidos) {}

    public function process(Request $request, RequestHandler $handler): Response {
        $rolId = self::rolDe($request);

        if (!in_array($rolId, $this->rolesPermitidos, true)) {
            $response = new SlimResponse(403);
            $response->getBody()->write(json_encode([
                'error' => 'No tiene permisos para realizar esta acción.',
            ], JSON_UNESCAPED_UNICODE));
            return $response->withHeader('Content-Type', 'application/json; charset=utf-8');
        }

        return $handler->handle($request);
    }

    #El middleware de JWT guarda el token decodificado en el atributo 'token' de la petición
    public static function rolDe(Request $request): int {
        $token = $request->getAttribute('token');
        return (int) (is_array($token) ? ($token['rol_id'] ?? 0) : 0);
    }

    public static function usuarioDe(Request $request): int {
        $token = $request->getAttribute('token');
        return (int) (is_array($token) ? ($token['sub'] ?? 0) : 0);
    }
}
