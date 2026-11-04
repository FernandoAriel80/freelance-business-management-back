<?php
namespace Core\Security;

class SanctumGuard
{
    /**
     * Inicia la sesión con configuración segura si no está iniciada.
     */
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            // Cookies HttpOnly y SameSite=Lax (mismo origen, no necesita None)
            session_set_cookie_params([
                'lifetime' => 0,
                'path' => '/',
                'domain' => '',
                'secure' => filter_var(getenv('COOKIE_SECURE') ?: 'false', FILTER_VALIDATE_BOOLEAN),
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
            session_start();
        }
    }

    /**
     * Autentica al usuario guardando su ID en la sesión.
     * Regenera el ID de sesión para prevenir fijación.
     */
    public static function login(string $userId): void
    {
        self::start();
        session_regenerate_id(true); // Previene fijación de sesión
        $_SESSION['user_id'] = $userId;
        $_SESSION['login_time'] = time();
    }

    /**
     * Comprueba si hay un usuario autenticado.
     * Devuelve el user_id o null.
     */
    public static function check(): ?string
    {
        self::start();
        return $_SESSION['user_id'] ?? null;
    }

    /**
     * Cierra la sesión y borra las cookies.
     */
    public static function logout(): void
    {
        self::start();
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }
        session_destroy();
    }

    /**
     * Genera un token CSRF y lo guarda en la sesión.
     * Úsalo en tus formularios como <input type="hidden" name="_token" value="...">
     */
    public static function csrfToken(): string
    {
        self::start();
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Valida el token CSRF enviado en el formulario o cabecera.
     * Devuelve true si es válido.
     */
    public static function verifyCsrf(?string $token = null): bool
    {
        self::start();
        $token = $token ?? $_POST['_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        $sessionToken = $_SESSION['csrf_token'] ?? '';
        return $token !== '' && $sessionToken !== '' && hash_equals($sessionToken, $token);
    }
}

/* 

public function login(Request $req, Response $res)
{
    // 1. Verificar CSRF (si el formulario lo envía)
    if (!$req->isMethod('POST') || !\Core\Security\SessionGuard::verifyCsrf()) {
        return $res->error('CSRF token inválido', 403)->send();
    }

    // 2. Validar credenciales
    $data = $req->validate([
        'email'    => 'required|email',
        'password' => 'required|string',
    ]);

    $user = $this->userRepository->findByEmail($data['email']);
    if (!$user || !password_verify($data['password'], $user['password'])) {
        return $res->error('Credenciales inválidas', 401)->send();
    }

    // 3. Crear sesión
    \Core\Security\SessionGuard::login($user['id']);

    // 4. Redirigir al dashboard (proyecto fullstack)
    header('Location: /dashboard');
    exit;
}

////////////////////////////////////////

<form method="POST" action="/login">
    <input type="hidden" name="_token" value="<?= \Core\Security\SessionGuard::csrfToken() ?>">
    <input type="email" name="email" required>
    <input type="password" name="password" required>
    <button type="submit">Iniciar sesión</button>
</form>

///////////////////////////////////////////////////

class AuthMiddleware
{
    public function handle(Request $req, Response $res): bool
    {
        if (!\Core\Security\SessionGuard::check()) {
            // Proyecto fullstack: redirigir al login
            header('Location: /login');
            exit;
        }
        return true;
    }
}


/////////////////////////////////////////////////////
$route->get('/dashboard', function ($req, $res) {
    if (!\Core\Security\SessionGuard::check()) {
        return $res->error('No autorizado', 401)->send();
    }
    // ... mostrar dashboard ...
});

public function logout(Request $req, Response $res)
{
    \Core\Security\SessionGuard::logout();
    header('Location: /login');
    exit;
}


*/