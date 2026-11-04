<?php
namespace Core\Security;

use Core\Env;

class Cookie
{
   private static function getOptions(int $expires): array
    {
        return [
            'expires'  => $expires,
            'path'     => '/',
            'domain'   => getenv('COOKIE_DOMAIN') ?: '',
            'secure'   => filter_var(getenv('COOKIE_SECURE') ?: 'false', FILTER_VALIDATE_BOOLEAN),
            'httponly' => true,
            'samesite' => getenv('COOKIE_SAMESITE') ?: 'Lax',
        ];
    }

    public static function setTokens(string $accessToken, string $refreshToken): void
    {
        setcookie('access_token', $accessToken, self::getOptions(time() + 900));
        setcookie('refresh_token', $refreshToken, self::getOptions(time() + 604800));
    }

    public static function setCsrfToken(): string
    {
        $csrfToken = bin2hex(random_bytes(32));
        $options = self::getOptions(time() + 3600);
        $options['httponly'] = false; // El front debe leerla

        setcookie('XSRF-TOKEN', $csrfToken, $options);
        return $csrfToken;
    }

    public static function clearTokens(): void
    {
        $expired = time() - 3600;
        setcookie('access_token', '', self::getOptions($expired));
        setcookie('refresh_token', '', self::getOptions($expired));
        setcookie('XSRF-TOKEN', '', self::getOptions($expired));
    }
}