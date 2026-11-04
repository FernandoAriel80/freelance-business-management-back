<?php
namespace Core\Support;

class Str
{
    /**
     * Caracteres alfanuméricos para random().
     * Equivalente al charset por defecto de Laravel.
     */
    private const RANDOM_CHARS = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

    /**
     * Genera una cadena aleatoria criptográficamente segura.
     *
     * @param int $length Longitud de la cadena
     * @return string
     */
    public static function random(int $length = 16): string
    {
        if ($length < 1) {
            throw new \InvalidArgumentException('La longitud debe ser mayor que 0');
        }

        $chars = self::RANDOM_CHARS;
        $max = strlen($chars) - 1;
        $out = '';

        for ($i = 0; $i < $length; $i++) {
            $out .= $chars[random_int(0, $max)];
        }

        return $out;
    }

    /**
     * Genera un nombre de archivo aleatorio conservando la extensión.
     *
     * @param string $originalName Nombre original (ej: "mi foto.JPG")
     * @param int    $length       Longitud del nombre base
     * @return string
     */
    public static function randomFilename(string $originalName, int $length = 32): string
    {
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $name = self::random($length);

        return $ext === '' ? $name : "{$name}.{$ext}";
    }

    /**
     * Convierte un texto en slug URL-friendly.
     * "Hola Mundo! Ñandú" → "hola-mundo-nandu"
     */
    public static function slug(string $value, string $separator = '-'): string
    {
        // Transliterar caracteres no ASCII a ASCII (ñ → n, á → a, etc.)
        $value = self::ascii($value);

        // Reemplazar todo lo que no sea letra/número por el separador
        $value = preg_replace('/[^a-zA-Z0-9]+/', $separator, $value);

        // Quitar separadores al inicio y al final
        return strtolower(trim($value, $separator));
    }

    /**
     * Translitera caracteres UTF-8 a su equivalente ASCII.
     * "café" → "cafe", "ñandú" → "nandu"
     */
    public static function ascii(string $value): string
    {
        $map = [
            'á' => 'a',
            'à' => 'a',
            'ä' => 'a',
            'â' => 'a',
            'ã' => 'a',
            'å' => 'a',
            'é' => 'e',
            'è' => 'e',
            'ë' => 'e',
            'ê' => 'e',
            'í' => 'i',
            'ì' => 'i',
            'ï' => 'i',
            'î' => 'i',
            'ó' => 'o',
            'ò' => 'o',
            'ö' => 'o',
            'ô' => 'o',
            'õ' => 'o',
            'ú' => 'u',
            'ù' => 'u',
            'ü' => 'u',
            'û' => 'u',
            'ñ' => 'n',
            'ç' => 'c',
            'ý' => 'y',
            'ÿ' => 'y',
            'Á' => 'A',
            'À' => 'A',
            'Ä' => 'A',
            'Â' => 'A',
            'Ã' => 'A',
            'Å' => 'A',
            'É' => 'E',
            'È' => 'E',
            'Ë' => 'E',
            'Ê' => 'E',
            'Í' => 'I',
            'Ì' => 'I',
            'Ï' => 'I',
            'Î' => 'I',
            'Ó' => 'O',
            'Ò' => 'O',
            'Ö' => 'O',
            'Ô' => 'O',
            'Õ' => 'O',
            'Ú' => 'U',
            'Ù' => 'U',
            'Ü' => 'U',
            'Û' => 'U',
            'Ñ' => 'N',
            'Ç' => 'C',
            'Ý' => 'Y',
        ];

        $value = strtr($value, $map);

        // Quitar cualquier otro carácter multibyte que quede
        return preg_replace('/[^\x20-\x7E]/', '', $value);
    }

    /**
     * Limita un string a N caracteres, sin cortar palabras a medias.
     *
     * @param string $value
     * @param int    $limit
     * @param string $end Caracteres a añadir al final si se cortó
     */
    public static function limit(string $value, int $limit = 100, string $end = '...'): string
    {
        if (mb_strlen($value) <= $limit) {
            return $value;
        }

        return rtrim(mb_substr($value, 0, $limit)) . $end;
    }

    /**
     * Trunca un string exactamente a N caracteres (sin buscar palabras).
     */
    public static function truncate(string $value, int $limit = 100, string $end = '...'): string
    {
        if (mb_strlen($value) <= $limit) {
            return $value;
        }

        return mb_substr($value, 0, $limit) . $end;
    }

    /**
     * Comprueba si un string empieza por alguna de las agujas.
     *
     * @param string          $haystack
     * @param string|string[] $needles
     */
    public static function startsWith(string $haystack, string|array $needles): bool
    {
        foreach ((array) $needles as $needle) {
            if ($needle !== '' && str_starts_with($haystack, $needle)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Comprueba si un string termina con alguna de las agujas.
     */
    public static function endsWith(string $haystack, string|array $needles): bool
    {
        foreach ((array) $needles as $needle) {
            if ($needle !== '' && str_ends_with($haystack, $needle)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Comprueba si un string contiene alguna de las agujas.
     */
    public static function contains(string $haystack, string|array $needles): bool
    {
        foreach ((array) $needles as $needle) {
            if ($needle !== '' && str_contains($haystack, $needle)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Convierte "camelCase" o "PascalCase" a "snake_case".
     * "userName" → "user_name"
     */
    public static function snake(string $value): string
    {
        $value = preg_replace('/(.)(?=[A-Z])/u', '$1_', $value);
        return strtolower($value);
    }

    /**
     * Convierte "snake_case" a "camelCase".
     * "user_name" → "userName"
     */
    public static function camel(string $value): string
    {
        return lcfirst(str_replace(' ', '', ucwords(str_replace(['_', '-'], ' ', $value))));
    }

    /**
     * Convierte "snake_case" a "StudlyCase" (PascalCase).
     * "user_name" → "UserName"
     */
    public static function studly(string $value): string
    {
        return str_replace(' ', '', ucwords(str_replace(['_', '-'], ' ', $value)));
    }

    /**
     * Envuelve un string con otro si no empieza/termina ya con él.
     * Str::wrap("hola", "*") → "*hola*"
     */
    public static function wrap(string $value, string $wrapper): string
    {
        return $wrapper . trim($value, $wrapper) . $wrapper;
    }

    /**
     * Genera un UUID v4.
     */
    public static function uuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40); // versión 4
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80); // variante RFC 4122

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    /**
     * Comprueba si un string es un UUID válido (v4 o cualquier versión).
     */
    public static function isUuid(string $value, ?int $version = null): bool
    {
        $pattern = $version === null
            ? '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i'
            : '/^[0-9a-f]{8}-[0-9a-f]{4}-' . $version . '[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i';

        return preg_match($pattern, $value) === 1;
    }

    /**
     * Enmascara parte de un string (útil para emails, tarjetas, etc.).
     * Str::mask("juan@example.com", 2, 5) → "ju*****ple.com"
     */
    public static function mask(
        string $value,
        int $start = 0,
        ?int $length = null,
        string $mask = '*'
    ): string {
        $chars = mb_str_split($value);
        $total = count($chars);

        $length = $length ?? ($total - $start);

        for ($i = $start; $i < $start + $length && $i < $total; $i++) {
            $chars[$i] = $mask;
        }

        return implode('', $chars);
    }

    /**
     * Genera una contraseña aleatoria.
     * Garantiza al menos un carácter de cada tipo (mayúscula, minúscula, número, símbolo).
     */
    public static function password(int $length = 16): string
    {
        if ($length < 8) {
            throw new \InvalidArgumentException('La contraseña debe tener al menos 4 caracteres');
        }

        $lower = 'abcdefghijklmnopqrstuvwxyz';
        $upper = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $digits = '0123456789';
        $symbols = '!@#$%^&*()-_=+[]{};:,.<>?';
        $all = $lower . $upper . $digits . $symbols;

        $password = [
            $lower[random_int(0, strlen($lower) - 1)],
            $upper[random_int(0, strlen($upper) - 1)],
            $digits[random_int(0, strlen($digits) - 1)],
            $symbols[random_int(0, strlen($symbols) - 1)],
        ];

        for ($i = 4; $i < $length; $i++) {
            $password[] = $all[random_int(0, strlen($all) - 1)];
        }

        // Mezclar para que los obligatorios no queden al principio
        shuffle($password);

        return implode('', $password);
    }

    /* Se genera un token de algun rango: 32 , 64 */

    public static function generateToken($range)
    {
        return bin2hex(random_bytes(!isset($range) ? 32 : $range));
    }

    public static function stringToJson(?string $string): string
    {
        if ($string === null || trim($string) === '') {
            return json_encode([]);
        }

        $result = [];

        foreach (explode(',', $string) as $pair) {
            $parts = explode(':', $pair, 2);

            if (count($parts) !== 2) {
                continue;
            }

            $key = trim($parts[0]);
            $value = trim($parts[1]);

            if ($key === '' || $value === '') {
                continue;
            }

            $result[$key] = $value;
        }

        return json_encode($result, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    public static function jsonToString(?string $json): string
    {
        $data = json_decode($json ?? '', true);

        if (!is_array($data)) {
            return '';
        }

        $pairs = [];

        foreach ($data as $key => $value) {
            if (!is_scalar($value)) {
                continue;
            }

            $pairs[] = $key . ':' . $value;
        }

        return implode(',', $pairs);
    }


}