<?php
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../config/init.php';

$ask = function ($label) {
    if (function_exists('readline')) return trim(readline($label));
    fwrite(STDOUT, $label);
    return trim((string) fgets(STDIN));
};

$fullName = $ask('Manager full name: ');
$username = $ask('Manager username (3-100 letters/numbers/._-): ');
$password = getenv('INITIAL_MANAGER_PASSWORD');

if ($fullName === '' || !preg_match('/^[A-Za-z0-9._-]{3,100}$/', $username)) {
    fwrite(STDERR, "Invalid name or username.\n");
    exit(1);
}
if (!is_string($password) || strlen($password) < 12 || strlen($password) > 72) {
    fwrite(STDERR, "Set INITIAL_MANAGER_PASSWORD to a password of 12-72 bytes, then run this script again.\n");
    exit(1);
}

if (db()->fetchOne("SELECT staff_id FROM staff WHERE role = 'manager' AND status = 1 LIMIT 1")) {
    fwrite(STDERR, "An active manager already exists. Create additional staff from the admin panel.\n");
    exit(1);
}
if (db()->fetchOne('SELECT staff_id FROM staff WHERE username = ? LIMIT 1', array($username))) {
    fwrite(STDERR, "That username already exists.\n");
    exit(1);
}

db()->execute(
    'INSERT INTO staff (full_name, username, password, role, status) VALUES (?, ?, ?, ?, 1)',
    array($fullName, $username, password_hash($password, PASSWORD_DEFAULT), 'manager')
);
putenv('INITIAL_MANAGER_PASSWORD');
fwrite(STDOUT, "Manager account created. Clear INITIAL_MANAGER_PASSWORD from your shell.\n");
