<?php
namespace Core\Middleware;

use Core\Http\Request;
use Core\Http\Response;
use Core\Security\JWT;
use Core\Support\Log;

class AuthMiddleware implements MiddlewareInterface
{
    public function handle(Request $req, Response $res): bool
    {
        $auth = $req->header('Authorization', '');
        if (!preg_match('/^Bearer\s+(.+)$/i', $auth, $m)) {
            Log::debug('Token no proporcionado');
            $res->error('Token no proporcionado', 401)->send();
            return false;
        }

        $payload = JWT::decode($m[1], $_ENV['JWT_SECRET'] ?? 'secret');
        if (!$payload) {
            Log::debug('Token inválido o expirado');
            $res->error('Token inválido o expirado', 401)->send();
            return false;
        }

        $req->setUser($payload);
        return true;
    }
}