<?php
namespace Core\Storage;

class FileStorage
{
    // Extensiones nunca permitidas (defensa en profundidad)
    private const BLOCKED_EXTENSIONS = [
        'php',
        'php3',
        'php4',
        'php5',
        'phtml',
        'phar',
        'exe',
        'sh',
        'bat',
        'cmd',
        'com',
        'cgi',
        'pl',
        'py',
        'rb',
        'htaccess',
        'htpasswd',
        'ini',
        'conf',
    ];

    private const PUBLIC_BASE = __DIR__ . '/../../../public/uploads';
    private const PRIVATE_BASE = __DIR__ . '/../../../storage/private';
    private const PUBLIC_URL = '/uploads';

    /**
     * Guarda un archivo público. Devuelve el path web (ej: /uploads/avatars/foo.jpg).
     */
    public static function storePublic(array $file, string $subdir = 'images'): string
    {
        $subdir = self::sanitizeSubdir($subdir);
        $filename = self::generateFilename($file);

        $dir = self::PUBLIC_BASE . '/' . $subdir;
        $path = $dir . '/' . $filename;

        self::move($file, $dir, $path);

        return self::PUBLIC_URL . '/' . $subdir . '/' . $filename;
    }

    /**
     * Guarda un archivo privado. Devuelve el path absoluto (no expuesto al front).
     */
    public static function storePrivate(array $file, string $subdir = 'files'): string
    {
        $subdir = self::sanitizeSubdir($subdir);
        $filename = self::generateFilename($file);

        $dir = self::PRIVATE_BASE . '/' . $subdir;
        $path = $dir . '/' . $filename;

        self::move($file, $dir, $path);

        return $path; // ruta absoluta, la guardas en BD y la sirves con permisos
    }

    /**
     * Borra un archivo. Acepta el path público (/uploads/...) o el absoluto.
     */
    public static function delete(string $path): bool
    {
        $realPath = self::resolvePath($path);

        if (!$realPath || !is_file($realPath)) {
            return false;
        }

        return @unlink($realPath);
    }

    /**
     * Traduce un path público (/uploads/foo.jpg) a ruta absoluta del sistema.
     */
    private static function resolvePath(string $path): ?string
    {
        // Si es una ruta absoluta que existe, úsala
        if (is_file($path)) {
            return $path;
        }

        // Si empieza por /uploads/, traducir a public/uploads
        if (str_starts_with($path, self::PUBLIC_URL . '/')) {
            $relative = substr($path, strlen(self::PUBLIC_URL) + 1);
            $real = self::PUBLIC_BASE . '/' . $relative;
            return is_file($real) ? $real : null;
        }

        return null;
    }

    // ─────────────────────────────────────────────
    // Internos
    // ─────────────────────────────────────────────

    private static function move(array $file, string $dir, string $path): void
    {
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            throw new \RuntimeException('Archivo inválido o no subido por HTTP');
        }

        if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            throw new \RuntimeException('Error de subida: código ' . $file['error']);
        }

        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new \RuntimeException("No se pudo crear el directorio: {$dir}");
        }

        if (!move_uploaded_file($file['tmp_name'], $path)) {
            throw new \RuntimeException('No se pudo mover el archivo');
        }
    }

    private static function generateFilename(array $file): string
    {
        $ext = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));

        if ($ext === '' || in_array($ext, self::BLOCKED_EXTENSIONS, true)) {
            throw new \InvalidArgumentException("Extensión de archivo no permitida: {$ext}");
        }

        return bin2hex(random_bytes(16)) . '.' . $ext;
    }

    private static function sanitizeSubdir(string $subdir): string
    {
        // Normalizar separadores y eliminar intentos de path traversal
        $subdir = str_replace('\\', '/', $subdir);
        $subdir = trim($subdir, '/');

        if ($subdir === '' || str_contains($subdir, '..')) {
            throw new \InvalidArgumentException("Subdirectorio inválido: {$subdir}");
        }

        // Solo permitir letras, números, guiones, guiones bajos y barras
        if (!preg_match('#^[a-zA-Z0-9_\-/]+$#', $subdir)) {
            throw new \InvalidArgumentException("Subdirectorio contiene caracteres no permitidos: {$subdir}");
        }

        return $subdir;
    }
}

/*
// Guardar
$path = FileStorage::storePublic($_FILES['avatar'], 'users/avatars');
// → /uploads/users/avatars/65f3a...jpg
// Se creó: public/uploads/users/avatars/

$path = FileStorage::storePublic($_FILES['portada'], 'main_images');
// → /uploads/main_images/abc123.jpg
// Se creó: public/uploads/main_images/

// Guardar en BD
$userRepo->update($id, ['avatar_url' => $path]);

// Borrar (después, cuando cambie el avatar)
FileStorage::delete($user->avatar_url);  // acepta /uploads/... y lo traduce 
 */
