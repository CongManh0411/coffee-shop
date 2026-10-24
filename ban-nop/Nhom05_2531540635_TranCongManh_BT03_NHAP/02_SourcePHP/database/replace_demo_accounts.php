<?php
// Run from CLI only on an existing installation to replace the shipped demo accounts.
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

$fullName = $ask('New manager full name: ');
$username = $ask('New manager username (3-100 letters/numbers/._-): ');
$password = getenv('INITIAL_MANAGER_PASSWORD');

if ($fullName === '' || !preg_match('/^[A-Za-z0-9._-]{3,100}$/', $username)) {
    fwrite(STDERR, "Invalid name or username.\n");
    exit(1);
}
if (!is_string($password) || strlen($password) < 12 || strlen($password) > 72) {
    fwrite(STDERR, "Set INITIAL_MANAGER_PASSWORD to a password of 12-72 bytes, then run this script again.\n");
    exit(1);
}
if (db()->fetchOne('SELECT staff_id FROM staff WHERE username = ? LIMIT 1', array($username))) {
    fwrite(STDERR, "That username already exists. Choose another username.\n");
    exit(1);
}

// This is the bcrypt hash used by the old SQL seed for all demo users.
$demoHash = '$2y$10$.7SswrVHgpBiQXRA6SjzMeuVUJb1Vmt2N12F6G.yoIygBsFstW3jS';
db()->transaction(function () use ($fullName, $username, $password, $demoHash) {
    db()->execute(
        'INSERT INTO staff (full_name, username, password, role, status) VALUES (?, ?, ?, ?, 1)',
        array($fullName, $username, password_hash($password, PASSWORD_DEFAULT), 'manager')
    );
    db()->execute('UPDATE staff SET status = 0 WHERE password = ?', array($demoHash));
});

putenv('INITIAL_MANAGER_PASSWORD');
fwrite(STDOUT, "Manager account created and old demo accounts disabled. Remove INITIAL_MANAGER_PASSWORD from your shell.\n");
