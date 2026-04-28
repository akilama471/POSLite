<?php

require_once "../core/Database.php";
require_once "../core/View.php";
require_once "../core/Router.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
