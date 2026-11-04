<?php
namespace Core\Support;

use DateTime;
use DateTimeZone;
use InvalidArgumentException;

class Date
{
    /** Formato estándar de MySQL */
    public const MYSQL = 'Y-m-d H:i:s';

    /** Zona horaria por defecto para mostrar (Argentina) */
    private const DEFAULT_TZ = 'America/Argentina/Buenos_Aires';

    /** Zona horaria de la BD (normalmente UTC) */
    private const STORAGE_TZ = 'UTC';

    /**
     * Fecha y hora actual en formato MySQL, en UTC.
     * Úsalo para guardar en la BD.
     */
    public static function now(): string
    {
        return (new DateTime('now', new DateTimeZone(self::STORAGE_TZ)))
            ->format(self::MYSQL);
    }

    /**
     * Fecha actual (sin hora) en formato MySQL, en UTC.
     * Ej: "2026-10-29"
     */
    public static function today(): string
    {
        return (new DateTime('now', new DateTimeZone(self::STORAGE_TZ)))
            ->format('Y-m-d');
    }

    /**
     * Fecha y hora actual formateada para mostrar en Argentina.
     * Ej: "29/10/2026 14:35"
     */
    public static function nowLocal(string $format = 'd/m/Y H:i'): string
    {
        return (new DateTime('now', new DateTimeZone(self::STORAGE_TZ)))
            ->setTimezone(new DateTimeZone(self::DEFAULT_TZ))
            ->format($format);
    }

    /**
     * Convierte una fecha de la BD (UTC) a hora de Argentina.
     *
     * @param string $dbDate Fecha en formato "Y-m-d H:i:s" (UTC)
     * @param string $format Formato de salida
     * @return string
     */
    public static function toLocal(string $dbDate, string $format = 'd/m/Y H:i'): string
    {
        $dt = self::parse($dbDate);
        return $dt->setTimezone(new DateTimeZone(self::DEFAULT_TZ))->format($format);
    }

    /**
     * Convierte una fecha local (Argentina) a UTC para guardar en la BD.
     *
     * @param string $localDate Fecha en formato "Y-m-d H:i:s" (hora Argentina)
     * @return string Fecha en UTC "Y-m-d H:i:s"
     */
    public static function toStorage(string $localDate): string
    {
        $dt = new DateTime($localDate, new DateTimeZone(self::DEFAULT_TZ));
        return $dt->setTimezone(new DateTimeZone(self::STORAGE_TZ))->format(self::MYSQL);
    }

    /**
     * Convierte una fecha entre dos zonas horarias.
     */
    public static function convert(
        string $date,
        string $fromTz = self::STORAGE_TZ,
        string $toTz = self::DEFAULT_TZ,
        string $format = self::MYSQL
    ): string {
        $dt = new DateTime($date, new DateTimeZone($fromTz));
        return $dt->setTimezone(new DateTimeZone($toTz))->format($format);
    }

    /**
     * Devuelve una fecha legible en español.
     * Ej: "29 de octubre de 2026, 14:35"
     */
    public static function human(string $dbDate): string
    {
        $dt = self::parse($dbDate)->setTimezone(new DateTimeZone(self::DEFAULT_TZ));

        $meses = [
            1 => 'enero',
            'febrero',
            'marzo',
            'abril',
            'mayo',
            'junio',
            'julio',
            'agosto',
            'septiembre',
            'octubre',
            'noviembre',
            'diciembre',
        ];

        return sprintf(
            '%d de %s de %d, %s',
            (int) $dt->format('j'),
            $meses[(int) $dt->format('n')],
            (int) $dt->format('Y'),
            $dt->format('H:i')
        );
    }

    /**
     * Devuelve "hace X tiempo" desde una fecha de la BD.
     * Ej: "hace 5 minutos", "hace 2 días", "hace 1 año"
     */
    public static function diffForHumans(string $dbDate): string
    {
        $then = self::parse($dbDate);
        $now = new DateTime('now', new DateTimeZone(self::STORAGE_TZ));
        $diff = $now->getTimestamp() - $then->getTimestamp();

        if ($diff < 0) {
            return 'en el futuro';
        }

        $units = [
            31536000 => 'año',
            2592000 => 'mes',
            604800 => 'semana',
            86400 => 'día',
            3600 => 'hora',
            60 => 'minuto',
            1 => 'segundo',
        ];

        foreach ($units as $seconds => $name) {
            if ($diff >= $seconds) {
                $count = (int) floor($diff / $seconds);
                $plural = $count > 1 ? 's' : '';
                return "hace {$count} {$name}{$plural}";
            }
        }

        return 'ahora mismo';
    }

    /**
     * Comprueba si una fecha de la BD es válida.
     */
    public static function isValid(string $date): bool
    {
        $dt = DateTime::createFromFormat(self::MYSQL, $date);
        return $dt !== false && $dt->format(self::MYSQL) === $date;
    }

    /**
     * Suma días/horas/meses a una fecha de la BD y la devuelve en UTC.
     * Ej: Date::add('2026-10-29 10:00:00', '+1 day')
     */
    public static function add(string $dbDate, string $interval): string
    {
        $dt = self::parse($dbDate);
        $dt->modify($interval);
        return $dt->format(self::MYSQL);
    }

    /**
     * Parsea una fecha de la BD asumiendo que está en UTC.
     */
    private static function parse(string $dbDate): DateTime
    {
        $dt = DateTime::createFromFormat(
            self::MYSQL,
            $dbDate,
            new DateTimeZone(self::STORAGE_TZ)
        );

        if ($dt === false) {
            throw new InvalidArgumentException("Fecha inválida: {$dbDate}");
        }

        return $dt;
    }

    /**
     * Formatea una fecha de la BD (UTC) a un formato personalizado,
     * SIN convertir de zona horaria.
     */
    public static function format(string $dbDate, string $format = 'd/m/Y H:i'): string
    {
        if ($dbDate === null || trim($dbDate) === '') {
            return null;
        }

        $dt = self::parse($dbDate);
        return $dt->format($format);
    }

    /**
     * Separa un datetime (venga como "Y-m-d\TH:i", "Y-m-d\TH:i:s", "Y-m-d H:i:s" o "Y-m-d")
     * en fecha y hora por separado.
     *
     * @param string $value Valor que llega del front (ej: "2026-10-31T14:30")
     * @return array{date: string, time: string}
     *               date en formato "Y-m-d", time en formato "H:i:s"
     *
     * Ej:
     *   Date::splitDateTime("2026-10-31T14:30")
     *   // → ['date' => '2026-10-31', 'time' => '14:30:00']
     *
     *   Date::splitDateTime("2026-10-31 14:30:00")
     *   // → ['date' => '2026-10-31', 'time' => '14:30:00']
     *
     *   Date::splitDateTime("2026-10-31")
     *   // → ['date' => '2026-10-31', 'time' => '00:00:00']
     */
    public static function splitDateTime(string $value): array
    {
        $value = trim($value);

        if ($value === '') {
            return ['date' => '', 'time' => ''];
        }

        // datetime-local manda "Y-m-d\TH:i" (o con segundos). Normalizamos la T a espacio.
        $normalized = str_replace('T', ' ', $value);

        // Caso solo fecha: "Y-m-d"
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $normalized)) {
            return ['date' => $normalized, 'time' => '00:00:00'];
        }

        // Caso "Y-m-d H:i"
        if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $normalized)) {
            return [
                'date' => substr($normalized, 0, 10),
                'time' => substr($normalized, 11) . ':00',
            ];
        }

        // Caso "Y-m-d H:i:s"
        if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $normalized)) {
            return [
                'date' => substr($normalized, 0, 10),
                'time' => substr($normalized, 11),
            ];
        }

        throw new InvalidArgumentException("Fecha/hora inválida: {$value}");
    }

    /**
     * Junta una fecha y una hora LOCALES (Argentina) y devuelve
     * el datetime en UTC listo para guardar en la BD.
     *
     * @param string $date Fecha en formato "Y-m-d"
     * @param string $time Hora en formato "H:i" o "H:i:s"
     * @return string Fecha en UTC "Y-m-d H:i:s"
     *
     * Ej:
     *   Date::fromLocal("2026-10-31", "14:30")
     *   // → "2026-10-31 17:30:00" (si Argentina es UTC-3)
     */
    public static function fromLocal(string $date, string $time): string
    {
        // Normalizamos la hora a "H:i:s"
        $time = substr($time . ':00', 0, 8);

        $localDateTime = "{$date} {$time}";

        $dt = DateTime::createFromFormat(
            self::MYSQL,
            $localDateTime,
            new DateTimeZone(self::DEFAULT_TZ)
        );

        if ($dt === false) {
            throw new InvalidArgumentException("Fecha/hora local inválida: {$localDateTime}");
        }

        return $dt->setTimezone(new DateTimeZone(self::STORAGE_TZ))->format(self::MYSQL);
    }
}