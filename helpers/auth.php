<?php
if (session_status() === PHP_SESSION_NONE) {
    // Lưu phiên trong thư mục của project (tự tạo nếu chưa có)
    $sessDir = __DIR__ . '/../sessions';
    if (!is_dir($sessDir)) {
        @mkdir($sessDir, 0777, true);
    }
    if (is_dir($sessDir) && is_writable($sessDir)) {
        session_save_path($sessDir);
    }
    session_set_cookie_params(array(
        'lifetime' => 0,
        'path'     => '/',       // cookie dùng cho cả /coffee-shop/
        'httponly' => true,
        'samesite' => 'Lax',
    ));
    session_start();
}

function current_user()
{
    return $_SESSION['user'] ?? null;
}

function require_login()
{
    $user = current_user();
    if (!$user) {
        json_error('Chưa đăng nhập.', 401);
    }
    return $user;
}

function require_role($roles)
{
    $user = require_login();
    if (!in_array($user['roleKey'], $roles, true)) {
        json_error('Bạn không có quyền thực hiện thao tác này.', 403);
    }
    return $user;
}