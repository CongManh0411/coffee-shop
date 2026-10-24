<?php
function auth_login($username, $password)
{
    $row = db()->fetchOne(
        "SELECT staff_id, full_name, username, password, role FROM staff WHERE username = ? AND status = 1",
        array(trim($username))
    );

    $valid = password_verify((string) $password, $row['password'] ?? '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.');
    if (!$row || !$valid) {
        return null;
    }
    if (password_needs_rehash($row['password'], PASSWORD_DEFAULT)) {
        db()->execute('UPDATE staff SET password = ? WHERE staff_id = ?', array(password_hash((string) $password, PASSWORD_DEFAULT), (int) $row['staff_id']));
    }

    session_regenerate_id(true);
    unset($_SESSION['csrf_token']);
    $user = array(
        'staffId' => (int) $row['staff_id'],
        'name'    => $row['full_name'],
        'roleKey' => $row['role'],
    );
    $_SESSION['user'] = $user;
    $_SESSION['last_activity'] = time();
    return $user;
}

function auth_logout()
{
    $_SESSION = array();
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', array(
            'expires' => time() - 42000,
            'path' => $params['path'],
            'domain' => $params['domain'],
            'secure' => $params['secure'],
            'httponly' => $params['httponly'],
            'samesite' => $params['samesite'] ?? 'Lax',
        ));
    }
    session_destroy();
}
