<?php

declare(strict_types=1);

define("BASE_PATH", dirname(__DIR__));
define("APP_TZ", "Asia/Colombo");

date_default_timezone_set(APP_TZ);

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        "httponly" => true,
        "samesite" => "Lax",
    ]);
    session_start();
}

require_once BASE_PATH . "/core/Database.php";
require_once BASE_PATH . "/core/Request.php";
require_once BASE_PATH . "/core/View.php";
require_once BASE_PATH . "/core/Middleware.php";
require_once BASE_PATH . "/core/MiddlewareManager.php";
require_once BASE_PATH . "/core/Router.php";
require_once BASE_PATH . "/Models/Model.php";

foreach (glob(BASE_PATH . "/Models/*.php") as $file) {
    if (basename($file) !== "Model.php") {
        require_once $file;
    }
}

foreach (glob(BASE_PATH . "/middleware/*.php") as $file) {
    require_once $file;
}

foreach (glob(BASE_PATH . "/controller/*.php") as $file) {
    require_once $file;
}

function app_env(string $key, ?string $default = null): ?string
{
    static $env = null;

    if ($env === null) {
        $env = [];
        $envPath = BASE_PATH . "/.env";

        if (is_file($envPath)) {
            $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

            foreach ($lines as $line) {
                $line = trim($line);

                if ($line === "" || str_starts_with($line, "#") || !str_contains($line, "=")) {
                    continue;
                }

                [$name, $value] = explode("=", $line, 2);
                $env[trim($name)] = trim($value, " \t\n\r\0\x0B\"'");
            }
        }
    }

    return $env[$key] ?? $default;
}

function app_url(string $path = "/"): string
{
    $path = "/" . ltrim($path, "/");
    return $path === "//" ? "/" : $path;
}

function redirect(string $path): void
{
    header("Location: " . app_url($path));
    exit();
}

function json_response(mixed $data, int $status = 200): void
{
    http_response_code($status);
    header("Content-Type: application/json; charset=UTF-8");
    echo json_encode($data);
    exit();
}

function csrf_token(): string
{
    if (empty($_SESSION["_csrf_token"])) {
        $_SESSION["_csrf_token"] = bin2hex(random_bytes(32));
    }

    return $_SESSION["_csrf_token"];
}

function verify_csrf(?string $token): bool
{
    return is_string($token)
        && isset($_SESSION["_csrf_token"])
        && hash_equals($_SESSION["_csrf_token"], $token);
}

function auth_user(): ?array
{
    return $_SESSION["auth"] ?? null;
}

function can(string $permission): bool
{
    $auth = auth_user();

    if (!is_array($auth)) {
        return false;
    }

    return (bool) ($auth["permissions"][$permission] ?? false);
}

function sync_cashier_session(bool $isActive, ?int $slotId): void
{
    $_SESSION["cashier"] = [
        "active" => $isActive,
        "slot_id" => $slotId,
    ];

    if (isset($_SESSION["auth"]) && is_array($_SESSION["auth"])) {
        $_SESSION["auth"]["cashier_on"] = $isActive;
        $_SESSION["auth"]["cashier_slot_id"] = $slotId;
    }
}
