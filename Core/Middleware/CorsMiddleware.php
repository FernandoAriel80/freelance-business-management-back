<?php
namespace Core\Middleware;

use Core\Http\Request;
use Core\Http\Response;
use Core\Support\Log;

class CorsMiddleware implements MiddlewareInterface
{
    private array $allowedOrigins;
    private array $allowedMethods;
    private array $allowedHeaders;
    private bool $allowCredentials;
    private int $maxAge;

    public function __construct(
        array $allowedOrigins = [],
        array $allowedMethods = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
        array $allowedHeaders = ['Content-Type', 'Authorization', 'X-Requested-With'],
        bool $allowCredentials = true,
        int $maxAge = 86400
    ) {
        $this->allowedOrigins = $allowedOrigins;
        $this->allowedMethods = array_map('strtoupper', $allowedMethods);
        $this->allowedHeaders = $allowedHeaders;
        $this->allowCredentials = $allowCredentials;
        $this->maxAge = $maxAge;
    }

    public function handle(Request $req, Response $res): bool
    {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';

        // Sin Origin (curl, Postman, server-to-server)
        if ($origin === '') {
            if ($req->getMethod() === 'OPTIONS') {
                http_response_code(204);
                return false;
            }
            return true;
        }

        // Origen no autorizado → 403 y cortamos
        if (!in_array($origin, $this->allowedOrigins, true)) {
            http_response_code(403);
            header('Content-Type: application/json');
            Log::debug('Origen no permitido');
            echo json_encode(['error' => 'Origen no permitido']);
            return false;
        }

        // Origen autorizado → enviar cabeceras INMEDIATAMENTE
        header("Access-Control-Allow-Origin: {$origin}");
        header("Access-Control-Allow-Methods: " . implode(', ', $this->allowedMethods));
        header("Access-Control-Allow-Headers: " . implode(', ', $this->allowedHeaders));
        header("Access-Control-Max-Age: {$this->maxAge}");
        header("Vary: Origin");

        if ($this->allowCredentials) {
            header("Access-Control-Allow-Credentials: true");
        }

        // Preflight
        if ($req->getMethod() === 'OPTIONS') {
            http_response_code(204);
            return false;
        }

        return true;
    }
}