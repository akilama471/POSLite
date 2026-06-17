<?php

declare(strict_types=1);

require_once dirname(__DIR__) . "/bootstrap/app.php";

$router = new Router();

require_once dirname(__DIR__) . "/routes/web.php";

$router->dispatch($_SERVER["REQUEST_URI"], $_SERVER["REQUEST_METHOD"]);
