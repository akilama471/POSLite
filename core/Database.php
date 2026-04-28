<?php

class Database
{
    private static $instance = null;
    private $pdo;

    private function __construct()
    {
        $this->pdo = new PDO("mysql:host=localhost;dbname=pos", "root", "", [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
    }

    public static function connect()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance->pdo;
    }
}
