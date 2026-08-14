<?php

declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));
define('APP_TZ', 'Asia/Colombo');

date_default_timezone_set(APP_TZ);

function app_env(string $key, ?string $default = null): ?string
{
    static $env = null;

    if ($env === null) {
        $env = [];
        $envPath = BASE_PATH . '/.env';

        if (is_file($envPath)) {
            $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

            foreach ($lines as $line) {
                $line = trim($line);

                if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                    continue;
                }

                [$name, $value] = explode('=', $line, 2);
                $env[trim($name)] = trim($value, " \t\n\r\0\x0B\"'");
            }
        }
    }

    return $env[$key] ?? $default;
}

require_once BASE_PATH . '/core/Database.php';
require_once BASE_PATH . '/Models/Model.php';

foreach (glob(BASE_PATH . '/Models/*.php') as $file) {
    if (basename($file) !== 'Model.php') {
        require_once $file;
    }
}
