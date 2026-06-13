<?php
// Database connection setup and PDO config

define('DB_HOST', 'localhost');
define('DB_NAME', 'hazard');
define('DB_USER', 'root');
define('DB_PASS', '');

function getDB() {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $dbc = new PDO($dsn, DB_USER, DB_PASS);
    $dbc->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $dbc->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    return $dbc;
}

?>
