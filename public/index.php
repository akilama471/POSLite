<?php
require_once "../inc/bootstrap.php";
require_once "../core/Router.php";

$router = new Router();

// Define routes
$router->get("/", "HomeController@index");
$router->get("/dashboard", "HomeController@dashboard");
$router->get("/products", "ProductController@index");
$router->get("/products/{id}", "ProductController@show");

$router->dispatch($_SERVER["REQUEST_URI"], $_SERVER["REQUEST_METHOD"]);
