<?php
// admin/pages/login/action.php — trang "Đăng nhập" (Admin)
require_once __DIR__ . '/../../../functions.php';
// Đăng nhập và đăng xuất là form POST (có CSRF) do PHP xử lý rồi redirect, không còn JavaScript gọi API.
$title = 'Đăng nhập';
$bodyClass = 'login-page';
$css = array('login.css');
$scripts = array();

// Đăng xuất (nút ở thanh bên gửi POST về đây)
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && ($_POST['auth_action'] ?? '') === 'logout') {
    if (post_csrf_ok()) auth_logout();
    redirect('index.php?page=login');
}

$loginError = '';
$loginUsername = '';
// Nhớ lựa chọn ô "Lưu Mật khẩu" để lần sau (kể cả sau khi đăng xuất) ô vẫn được tick sẵn.
$rememberChecked = ($_COOKIE['remember_pref'] ?? '') === '1';
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && ($_POST['auth_action'] ?? '') === 'login') {
    $rememberChecked = !empty($_POST['remember']);
    if (!headers_sent()) {
        setcookie('remember_pref', $rememberChecked ? '1' : '', array(
            'expires' => $rememberChecked ? time() + 31536000 : time() - 3600,
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Lax',
        ));
    }
    $loginUsername = is_string($_POST['username'] ?? null) ? trim($_POST['username']) : '';
    $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
    if (!post_csrf_ok()) {
        $loginError = 'Phiên làm việc đã hết hạn. Hãy thử đăng nhập lại.';
    } elseif (strlen($loginUsername) > 100 || strlen($password) > 1024) {
        auth_attempts($loginUsername, true);
        $loginError = 'Sai tên đăng nhập hoặc mật khẩu.';
    } elseif (auth_attempts($loginUsername) >= 5) {
        $loginError = 'Tạm khóa đăng nhập trong 15 phút do nhập sai nhiều lần.';
    } else {
        $loggedIn = auth_login($loginUsername, $password, !empty($_POST['remember']));
        if (!$loggedIn) {
            auth_attempts($loginUsername, true);
            $loginError = 'Sai tên đăng nhập hoặc mật khẩu.';
        } else {
            auth_attempts($loginUsername, false, true);
            redirect(nav_home_from_login($loggedIn['roleKey']));
        }
    }
}
