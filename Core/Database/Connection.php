<?php
namespace Core\Database;
use Core\Env;
use PDO;
use PDOException;

class Connection
{
    private static ?PDO $instance = null;

    public static function connect(): PDO
    {
        if (self::$instance instanceof PDO) {
            return self::$instance;
        }

        $driver = Env::get('DB_DRIVER', 'mysql');
        $host   = Env::get('DB_HOST', '127.0.0.1');
        $port   = Env::get('DB_PORT', $driver === 'pgsql' ? '5432' : '3306');
        $dbname = Env::get('DB_DATABASE', '');
        $user   = Env::get('DB_USERNAME', 'root');
        $pass   = Env::get('DB_PASSWORD', '');

        $dsn = "$driver:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";

        try {
            self::$instance = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            throw new \RuntimeException('Error de conexión: ' . $e->getMessage());
        }

        return self::$instance;
    }
}