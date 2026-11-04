<?php
namespace Core\Support;

class Log
{
    /**
     * Loguea cualquier valor (array, objeto, string, null, etc.)
     * de forma legible.
     *
     * Uso: Log::debug($data);
     *      Log::debug($data, 'PAYLOAD');
     */
    public static function debug(mixed $value, ?string $label = null): void
    {
        $prefix = $label !== null ? "[{$label}] " : '';

        if (is_array($value) || is_object($value)) {
            error_log($prefix . print_r($value, true));
        } else {
            error_log($prefix . var_export($value, true));
        }
    }

    /**
     * Igual que debug() pero usa var_export, que distingue null de "" y 0.
     *
     * Uso: Log::dump($data, 'VALIDATED');
     */
    public static function dump(mixed $value, ?string $label = null): void
    {
        $prefix = $label !== null ? "[{$label}] " : '';
        error_log($prefix . var_export($value, true));
    }

    /**
     * Loguea un array como JSON en una sola línea.
     *
     * Uso: Log::json($payload);
     */
    public static function json(mixed $value, ?string $label = null): void
    {
        $prefix = $label !== null ? "[{$label}] " : '';
        error_log($prefix . json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    /**
     * Loguea una excepción completa (mensaje, archivo, línea, trace).
     *
     * Uso: Log::exception($e);
     *      Log::exception($e, 'Error creando cliente');
     */
    public static function exception(\Throwable $e, ?string $context = null): void
    {
        $prefix = $context !== null ? "[{$context}] " : '';

        $message = sprintf(
            "%s%s: %s en %s:%d",
            $prefix,
            get_class($e),
            $e->getMessage(),
            $e->getFile(),
            $e->getLine()
        );

        error_log($message);
        error_log($e->getTraceAsString());
    }

    /**
     * Loguea un mensaje simple.
     *
     * Uso: Log::info('Cliente creado con ID xxx');
     */
    public static function info(string $message): void
    {
        error_log($message);
    }

    /**
     * Loguea un warning.
     */
    public static function warning(string $message): void
    {
        error_log('[WARNING] ' . $message);
    }

    /**
     * Loguea un error simple.
     */
    public static function error(string $message): void
    {
        error_log('[ERROR] ' . $message);
    }
}