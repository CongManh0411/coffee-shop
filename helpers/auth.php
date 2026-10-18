<?php
if (session_status() === PHP_SESSION_NONE) {
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