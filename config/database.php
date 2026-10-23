<?php
// Local XAMPP defaults; define these environment variables on the web server for deployment.
$env = function ($key, $default) {
    $value = getenv($key);
    return $value === false ? $default : $value;
};

$appEnvironment = strtolower((string) $env('APP_ENV', 'local'));
$dbUser = $env('DB_USER', 'root');
$dbPassword = $env('DB_PASSWORD', '');
if ($appEnvironment === 'production' && ($dbUser === 'root' || $dbPassword === '' || getenv('DB_USER') === false || getenv('DB_PASSWORD') === false)) {
    throw new RuntimeException('Production requires a dedicated MySQL account and DB_USER/DB_PASSWORD environment variables.');
}

return array(
    'host'     => $env('DB_HOST', '127.0.0.1'),
    'port'     => (int) $env('DB_PORT', '3306'),
    'database' => $env('DB_NAME', 'coffee_shop'),
    'username' => $dbUser,
    'password' => $dbPassword,
    'charset'  => 'utf8mb4',
);
