<?php
namespace Core\Security;

class SanctumSPA
{
    /**
     * Inicia la sesión si no está iniciada y configura cookies seguras.
     */
    public static function startSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            // Configuración de cookie de sesión (HttpOnly, SameSite según entorno)
            $secure = filter_var(getenv('COOKIE_SECURE') ?: 'false', FILTER_VALIDATE_BOOLEAN);
            $sameSite = getenv('COOKIE_SAMESITE') ?: 'Lax';
            $domain = getenv('COOKIE_DOMAIN') ?: '';

            session_set_cookie_params([
                'lifetime' => 0,
                'path' => '/',
                'domain' => $domain,
                'secure' => $secure,
                'httponly' => true,      // El JS no puede leer la cookie de sesión
                'samesite' => $sameSite,
            ]);

            session_start();
        }
    }

    /**
     * Endpoint /csrf-cookie: inicia sesión y genera/renueva el token CSRF.
     */
    public static function csrfCookie(): string
    {
        self::startSession();

        // Genera un token CSRF nuevo (o reutiliza el de la sesión)
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        $token = $_SESSION['csrf_token'];

        // Envía el token en una cookie NO HttpOnly para que el front lo lea
        $secure = filter_var(getenv('COOKIE_SECURE') ?: 'false', FILTER_VALIDATE_BOOLEAN);
        $sameSite = getenv('COOKIE_SAMESITE') ?: 'Lax';
        $domain = getenv('COOKIE_DOMAIN') ?: '';

        setcookie('XSRF-TOKEN', $token, [
            'expires' => time() + 3600,
            'path' => '/',
            'domain' => $domain,
            'secure' => $secure,
            'httponly' => false,     // ¡Importante! El front debe poder leerla
            'samesite' => $sameSite,
        ]);

        return $token;
    }

    /**
     * Verifica el token CSRF en peticiones que modifican estado.
     */
    public static function verifyCsrf(): bool
    {
        self::startSession();

        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        if (in_array($method, ['GET', 'HEAD', 'OPTIONS'])) {
            return true; // No se necesita CSRF en lecturas
        }

        $headerToken = $_SERVER['HTTP_X_XSRF_TOKEN'] ?? '';
        $sessionToken = $_SESSION['csrf_token'] ?? '';

        // hash_equals previene timing attacks [citation:10]
        return $headerToken && $sessionToken && hash_equals($sessionToken, $headerToken);
    }

    /**
     * Autentica al usuario guardando su ID en la sesión.
     */
    public static function login(string $userId): void
    {
        self::startSession();
        session_regenerate_id(true); // Previene fijación de sesión [citation:13]
        $_SESSION['user_id'] = $userId;
    }

    /**
     * Comprueba si hay un usuario autenticado en la sesión.
     */
    public static function check(): ?string
    {
        self::startSession();
        return $_SESSION['user_id'] ?? null;
    }

    /**
     * Cierra la sesión y borra las cookies.
     */
    public static function logout(): void
    {
        self::startSession();
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
        setcookie('XSRF-TOKEN', '', time() - 3600, '/');
    }
}