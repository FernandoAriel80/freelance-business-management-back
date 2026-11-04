<?php
namespace Core\Http;

class Request
{
    private $data = [];
    private $headers = [];
    private $method;
    private $uri;
    private $queryParams = [];
    private $pathParams = [];

    public function __construct()
    {
        $this->method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

        $this->uri = $_SERVER['REQUEST_URI'] ?? '/';
        $this->uri = parse_url($this->uri, PHP_URL_PATH);

        // Normalizar: quitar trailing slash excepto en la raíz
        if ($this->uri !== '/' && str_ends_with($this->uri, '/')) {
            $this->uri = rtrim($this->uri, '/');
        }

        $this->queryParams = $_GET;

        $this->captureJson();
        $this->capturePost();
        $this->captureHeaders();
    }

    private function captureJson()
    {
        $contentType = $this->getContentType();

        if (strpos($contentType, 'application/json') !== false) {
            $input = file_get_contents('php://input');

            if (!empty($input)) {
                $decoded = json_decode($input, true);

                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $this->data = array_merge($this->data, $decoded);
                }
            }
        }
    }

    private function capturePost()
    {
        if (!empty($_POST)) {
            $this->data = array_merge($this->data, $_POST);
        }

        if ($this->method === 'PUT' || $this->method === 'PATCH' || $this->method === 'DELETE') {
            parse_str(file_get_contents('php://input'), $putData);
            if (!empty($putData)) {
                $this->data = array_merge($this->data, $putData);
            }
        }
    }

    private function captureHeaders()
    {
        $this->headers = getallheaders();
    }

    /**
     * Añade path params SIN borrar los existentes.
     * Esto permite que el router ponga {id} y que los middlewares
     * añadan auth_user_id sin pisar el id de la URL.
     */
    public function setPathParams(array $params)
    {
        $this->pathParams = array_merge($this->pathParams, $params);
    }

    public function input($key, $default = null)
    {
        if (isset($this->pathParams[$key])) {
            return $this->pathParams[$key];
        }

        if (isset($this->data[$key])) {
            return $this->data[$key];
        }

        return $this->queryParams[$key] ?? $default;
    }

    /**
     * Devuelve SOLO un path param (de la URL), no del body ni query.
     * Útil para evitar ambigüedad cuando el mismo nombre puede venir de varios sitios.
     */
    public function param($key, $default = null)
    {
        return $this->pathParams[$key] ?? $default;
    }

    // Todos los datos
    public function all()
    {
        return array_merge($this->pathParams, $this->data, $this->queryParams);
    }

    public function has($key)
    {
        return isset($this->pathParams[$key]) ||
            isset($this->data[$key]) ||
            isset($this->queryParams[$key]);
    }

    public function getContentType()
    {
        return $_SERVER['CONTENT_TYPE'] ?? '';
    }

    public function header($key, $default = null)
    {
        return $this->headers[$key] ?? $default;
    }

    public function isMethod($method)
    {
        return strtoupper($this->method) === strtoupper($method);
    }

    public function getMethod()
    {
        return $this->method;
    }

    public function getUri()
    {
        return $this->uri;
    }

    public function only(array $keys)
    {
        $result = [];
        foreach ($keys as $key) {
            if ($this->has($key)) {
                $result[$key] = $this->input($key);
            }
        }
        return $result;
    }

    public function except(array $keys)
    {
        $all = $this->all();
        foreach ($keys as $key) {
            unset($all[$key]);
        }
        return $all;
    }

    public function validate(array $rules, array $messages = []): array
    {
        $validator = new \Core\Validation\Validator($this->all(), $rules, $messages);
        return $validator->validate();
    }

    public function file(string $key): ?array
    {
        return $_FILES[$key] ?? null;
    }

    private $user = null;

    public function setUser(array $user): void
    {
        $this->user = $user;
    }

    public function user(): ?array
    {
        return $this->user;
    }
}