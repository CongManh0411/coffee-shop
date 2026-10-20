<?php
// Local XAMPP defaults; define these environment variables on the web server for deployment.
$env = function ($key, $default) {
    $value = getenv($key);
    return $value === false ? $default : $value;
};

return array(
    'host'     => $env('DB_HOST', '127.0.0.1'),
    'port'     => (int) $env('DB_PORT', '3306'),
    'database' => $env('DB_NAME', 'coffee_shop'),
    'username' => $env('DB_USER', 'root'),
    'password' => $env('DB_PASSWORD', ''),
    'charset'  => 'utf8mb4',
);
