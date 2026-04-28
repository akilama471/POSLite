<?php

declare(strict_types=1);

require_once dirname(__DIR__) . "/bootstrap/app.php";

$router = new Router();

$router->get("/", "HomeController@index");
$router->get("/login", "AuthController@showLoginForm", ["guest"]);
$router->post("/login", "AuthController@login", ["guest"]);
$router->post("/logout", "AuthController@logout", ["auth"]);
$router->get("/dashboard", "DashboardController@index", ["auth"]);

$router->dispatch($_SERVER["REQUEST_URI"], $_SERVER["REQUEST_METHOD"]);
