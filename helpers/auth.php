<?php
require_once __DIR__ . "/../api/modules/auth.php";
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Lax');
    ini_set('session.gc_maxlifetime', '14400');

    // Store sessions outside the public document root.
    $sessionDirectory = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'coffee-shop-sessions';
    if (!is_dir($sessionDirectory)) @mkdir($sessionDirectory, 0700, true);
    if (is_dir($sessionDirectory) && is_writable($sessionDirectory)) session_save_path($sessionDirectory);

    $scriptDirectory = basename(dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
    $cookiePath = dirname($_SERVER['SCRIPT_NAME'] ?? '/');
    if (in_array($scriptDirectory, array('admin', 'api'), true)) $cookiePath = dirname($cookiePath);
    $cookiePath = rtrim(str_replace('\\', '/', $cookiePath), '/') . '/';
    if ($cookiePath === '//') $cookiePath = '/';

    session_set_cookie_params(array(
        'lifetime' => 0,
        'path' => $cookiePath,
        'secure' => !empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ));
    session_start();
}

function current_user()
{
    if (empty($_SESSION['user'])) return null;
    $now = time();
    if (!empty($_SESSION['last_activity']) && $now - (int) $_SESSION['last_activity'] > 14400) {
        auth_logout();
        return null;
    }
    // Revalidate active status, role and password on every authenticated request.
    $row = db()->fetchOne('SELECT staff_id, full_name, role, status, password FROM staff WHERE staff_id = ?', array((int) ($_SESSION['user']['staffId'] ?? 0)));
    $fingerprint = $row ? hash('sha256', $row['password']) : '';
    if (!$row || (int) $row['status'] !== 1
        || $row['role'] !== $_SESSION['user']['roleKey']
        || (isset($_SESSION['credential_fingerprint']) && !hash_equals($_SESSION['credential_fingerprint'], $fingerprint))) {
        auth_logout();
        return null;
    }
    $_SESSION['credential_fingerprint'] = $fingerprint;
    $_SESSION['user']['name'] = $row['full_name'];
    $_SESSION['last_activity'] = $now;
    return $_SESSION['user'];
}

function csrf_token()
{
    if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf_token'];
}

function require_csrf_token()
{
    $sent = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    $expected = $_SESSION['csrf_token'] ?? '';
    if (!is_string($sent) || $expected === '' || !hash_equals($expected, $sent)) {
        json_error('Phiên bảo mật hết hạn. Hãy tải lại trang rồi thử lại.', 419);
    }
}

function auth_attempt_file($username)
{
    $directory = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'coffee-shop-auth-attempts';
    if (!is_dir($directory)) @mkdir($directory, 0700, true);
    $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    $key = hash('sha256', strtolower(trim((string) $username)) . '|' . $ip);
    return $directory . DIRECTORY_SEPARATOR . $key . '.json';
}

function auth_attempts($username, $recordFailure = false, $clear = false, $windowSeconds = 900)
{
    $path = auth_attempt_file($username);
    $handle = @fopen($path, 'c+');
    if (!$handle) return 0;
    flock($handle, LOCK_EX);
    rewind($handle);
    $events = json_decode(stream_get_contents($handle), true);
    if (!is_array($events)) $events = array();
    $cutoff = time() - max(1, (int) $windowSeconds);
    $events = array_values(array_filter($events, function ($stamp) use ($cutoff) { return (int) $stamp > $cutoff; }));
    if ($clear) $events = array();
    elseif ($recordFailure) $events[] = time();
    ftruncate($handle, 0);
    rewind($handle);
    fwrite($handle, json_encode($events));
    fflush($handle);
    flock($handle, LOCK_UN);
    fclose($handle);
    return count($events);
}

function require_login()
{
    $user = current_user();
    if (!$user) json_error('Chưa đăng nhập.', 401);
    return $user;
}

function require_role($roles)
{
    $user = require_login();
    if (!in_array($user['roleKey'], $roles, true)) json_error('Bạn không có quyền thực hiện thao tác này.', 403);
    return $user;
}
