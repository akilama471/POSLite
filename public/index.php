<?php

declare(strict_types=1);

require_once dirname(__DIR__) . "/bootstrap/app.php";

$router = new Router();

$router->get("/", "HomeController@index");
$router->get("/login", "AuthController@showLoginForm", ["guest"]);
$router->post("/login", "AuthController@login", ["guest"]);
$router->post("/logout", "AuthController@logout", ["auth"]);
$router->get("/dashboard", "DashboardController@index", ["auth"]);
$router->get("/settings", "SettingsController@index", ["auth", "permission:p_63"]);
$router->get("/settings/users", "UserManagementController@index", ["auth", "permission:p_65"]);
$router->get("/settings/users/create", "UserManagementController@create", ["auth", "permission:p_64"]);
$router->post("/settings/users", "UserManagementController@store", ["auth", "permission:p_64"]);
$router->post("/settings/users/{id}/status", "UserManagementController@updateStatus", ["auth", "permission:p_65"]);
$router->get("/settings/profile", "UserProfileController@edit", ["auth"]);
$router->post("/settings/profile", "UserProfileController@updateDetails", ["auth"]);
$router->post("/settings/profile/password", "UserProfileController@updatePassword", ["auth"]);
$router->get("/settings/privileges", "PrivilegeController@index", ["auth", "permission:p_67"]);
$router->post("/settings/privileges", "PrivilegeController@store", ["auth", "permission:p_67"]);
$router->post("/settings/privileges/{id}", "PrivilegeController@update", ["auth", "permission:p_67"]);
$router->get("/settings/user-privileges", "UserPrivilegeController@index", ["auth", "permission:p_68"]);
$router->post("/settings/user-privileges/{id}", "UserPrivilegeController@update", ["auth", "permission:p_68"]);

$router->dispatch($_SERVER["REQUEST_URI"], $_SERVER["REQUEST_METHOD"]);
