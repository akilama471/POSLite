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
$router->get("/settings/shops", "ShopController@index", ["auth", "permission:p_71"]);
$router->get("/settings/shops/create", "ShopController@create", ["auth", "permission:p_70"]);
$router->post("/settings/shops", "ShopController@store", ["auth", "permission:p_70"]);
$router->get("/settings/shops/{id}/edit", "ShopController@edit", ["auth", "permission:p_71"]);
$router->post("/settings/shops/{id}", "ShopController@update", ["auth", "permission:p_71"]);
$router->get("/settings/profile", "UserProfileController@edit", ["auth"]);
$router->post("/settings/profile", "UserProfileController@updateDetails", ["auth"]);
$router->post("/settings/profile/password", "UserProfileController@updatePassword", ["auth"]);
$router->get("/settings/privileges", "PrivilegeController@index", ["auth", "permission:p_67"]);
$router->post("/settings/privileges", "PrivilegeController@store", ["auth", "permission:p_67"]);
$router->post("/settings/privileges/{id}", "PrivilegeController@update", ["auth", "permission:p_67"]);
$router->get("/settings/privileges/{id}/functions", "PrivilegeController@editFunctions", ["auth", "permission:p_67"]);
$router->post("/settings/privileges/{id}/functions", "PrivilegeController@updateFunctions", ["auth", "permission:p_67"]);
$router->get("/settings/privileges/{id}/reports", "PrivilegeController@editReports", ["auth", "permission:p_67"]);
$router->post("/settings/privileges/{id}/reports", "PrivilegeController@updateReports", ["auth", "permission:p_67"]);
$router->get("/settings/user-privileges", "UserPrivilegeController@index", ["auth", "permission:p_68"]);
$router->post("/settings/user-privileges/{id}", "UserPrivilegeController@update", ["auth", "permission:p_68"]);
$router->get("/categories", "ProductCategoryController@index", ["auth", "permission:p_18"]);
$router->post("/categories", "ProductCategoryController@store", ["auth", "permission:p_18"]);
$router->post("/categories/{id}", "ProductCategoryController@update", ["auth", "permission:p_18"]);
$router->post("/categories/{id}/delete", "ProductCategoryController@destroy", ["auth", "permission:p_18"]);
$router->get("/operators", "RechargeOperatorController@index", ["auth", "permission:p_22"]);
$router->post("/operators", "RechargeOperatorController@store", ["auth", "permission:p_22"]);
$router->post("/operators/{id}", "RechargeOperatorController@update", ["auth", "permission:p_22"]);
$router->get("/items", "ItemController@index", ["auth", "permission:p_16"]);
$router->get("/items/create", "ItemController@create", ["auth", "permission:p_15"]);
$router->post("/items", "ItemController@store", ["auth", "permission:p_15"]);
$router->get("/items/{id}/edit", "ItemController@edit", ["auth", "permission:p_16"]);
$router->post("/items/{id}", "ItemController@update", ["auth", "permission:p_16"]);
$router->get("/suppliers", "SupplierController@index", ["auth", "permission:p_26"]);
$router->get("/suppliers/create", "SupplierController@create", ["auth", "permission:p_25"]);
$router->post("/suppliers", "SupplierController@store", ["auth", "permission:p_25"]);
$router->get("/suppliers/{id}/edit", "SupplierController@edit", ["auth", "permission:p_26"]);
$router->post("/suppliers/{id}", "SupplierController@update", ["auth", "permission:p_26"]);
$router->get("/customers", "CustomerController@index", ["auth", "permission:p_37"]);
$router->get("/customers/create", "CustomerController@create", ["auth", "permission:p_36"]);
$router->post("/customers", "CustomerController@store", ["auth", "permission:p_36"]);
$router->get("/customers/{id}/edit", "CustomerController@edit", ["auth", "permission:p_37"]);
$router->post("/customers/{id}", "CustomerController@update", ["auth", "permission:p_37"]);
$router->post("/customers/{id}/status", "CustomerController@updateStatus", ["auth", "permission:p_37"]);

$router->dispatch($_SERVER["REQUEST_URI"], $_SERVER["REQUEST_METHOD"]);
