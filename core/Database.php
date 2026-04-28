<?php

declare(strict_types=1);

class Database
{
    private static ?PDO $instance = null;

    public static function connect(): PDO
    {
        if (self::$instance instanceof PDO) {
            return self::$instance;
        }

        $host = app_env("DB_HOST", "localhost");
        $port = app_env("DB_PORT", "3306");
        $database = app_env("DB_DATABASE", "sirixmnt_admin_db_4nshop");
        $username = app_env("DB_USERNAME", "sirixmnt_admin_db_4nshop");
        $password = app_env("DB_PASSWORD", "SQp0~!78*gdv");

        $dsn = sprintf(
            "mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4",
            $host,
            $port,
            $database,
            $database,
        );

        self::$instance = new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        return self::$instance;
    }
}
