<?php
namespace Core;

use Core\Http\Request;
use Core\Http\Response;
use Core\Middleware\CorsMiddleware;
use Core\Support\Log;

class Route
{
    private array $routes = [];
    private array $groupStack = [];

    public function getRoutes(): array
    {
        return $this->routes;
    }

    public function group(callable $callback): void
    {
        $this->groupStack[] = ['prefix' => '', 'middleware' => []];
        $callback($this);
        array_pop($this->groupStack);
    }

    public function prefix(string $prefix): void
    {
        $idx = count($this->groupStack) - 1;
        if ($idx >= 0) {
            $this->groupStack[$idx]['prefix'] .= '/' . trim($prefix, '/');
        }
    }

    public function middleware(mixed $middleware): void
    {
        $idx = count($this->groupStack) - 1;
        if ($idx >= 0) {
            $items = is_array($middleware) ? $middleware : [$middleware];
            $this->groupStack[$idx]['middleware'] = array_merge(
                $this->groupStack[$idx]['middleware'],
                $items
            );
        }
    }

    public function get(string $path, \Closure $handler): void
    {
        $this->add('GET', $path, $handler);
    }
    public function post(string $path, \Closure $handler): void
    {
        $this->add('POST', $path, $handler);
    }
    public function put(string $path, \Closure $handler): void
    {
        $this->add('PUT', $path, $handler);
    }
    public function patch(string $path, \Closure $handler): void
    {
        $this->add('PATCH', $path, $handler);
    }
    public function delete(string $path, \Closure $handler): void
    {
        $this->add('DELETE', $path, $handler);
    }

    private function add(string $method, string $path, \Closure $handler): void
    {
        $prefix = '';
        $middleware = [];
        foreach ($this->groupStack as $g) {
            $prefix .= $g['prefix'];
            $middleware = array_merge($middleware, $g['middleware']);
        }

        // Unir prefix y path con una sola barra
        $fullPath = '/' . trim($prefix . '/' . $path, '/');

        // Colapsar barras dobles (por si acaso)
        $fullPath = preg_replace('#/+#', '/', $fullPath);

        // Convertir {param} a regex
        $fullPath = preg_replace('/\{(\w+)\}/', '(?P<$1>[^/]+)', $fullPath);

        $this->routes[] = [
            'method' => $method,
            'pattern' => '#^' . $fullPath . '$#',
            'handler' => $handler,
            'middleware' => $middleware,
        ];
    }

    public function dispatch(Request $req, Response $res, string $message, ?CorsMiddleware $cors = null): void
    {
        // 1. CORS primero (siempre, para que cualquier respuesta tenga headers)
        if ($cors !== null) {
            $cors->handle($req, $res);
        }

        // 2. Preflight OPTIONS
        if ($req->getMethod() === 'OPTIONS') {
            http_response_code(204);
            exit;
        }

        $method = $req->getMethod();
        $uri = $req->getUri();

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method)
                continue;
            if (!preg_match($route['pattern'], $uri, $matches))
                continue;

            $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
            $req->setPathParams($params);

            foreach ($route['middleware'] as $mw) {
                if (!$mw->handle($req, $res)) {
                    // Si el middleware no envió respuesta, mandamos una por defecto
                    if (!$res->isSent()) {
                        Log::debug('No autorizado');
                        $res->json(['error' => 'No autorizado'], 401)->send();
                    }
                    return;
                }
            }

            $result = ($route['handler'])($req, $res);

            if ($result instanceof Response && !$result->isSent()) {
                $result->send();
            }

            return;
        }

        $res->notFound($message)->send();
    }
    /* public function dispatch(Request $req, Response $res, string $message, ?CorsMiddleware $cors = null): void
    {
        // 1. Preflight OPTIONS: responder inmediatamente con CORS y 204
        if ($req->getMethod() === 'OPTIONS') {
            $cors?->handle($req, $res);
            http_response_code(204);
            exit;
        }

        $method = $req->getMethod();
        $uri = $req->getUri();

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method)
                continue;
            if (!preg_match($route['pattern'], $uri, $matches))
                continue;

            $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
            $req->setPathParams($params);

            foreach ($route['middleware'] as $mw) {
                if (!$mw->handle($req, $res))
                    return;
            }

            $result = ($route['handler'])($req, $res);

            if ($result instanceof Response && !$result->isSent()) {
                $result->send();
            }

            return;
        }

        $res->notFound($message)->send();
    } */
}