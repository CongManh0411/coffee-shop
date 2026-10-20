<?php
require_once __DIR__ . '/config/init.php';
require_once __DIR__ . '/helpers/auth.php';

// Mỗi trang là một thư mục pages/<khóa>/ gồm action.php (logic) và index.php (giao diện).
$routes = array(
    'login', 'tablemanage', 'menu', 'revenue', 'history', 'staff', 'shifts', 'warehouse',
    'ops', 'logs', 'tables', 'schedule', 'orders', 'cashshift', 'kitchen',
);

$rolePages = array(
    'manager' => array('tablemanage', 'menu', 'revenue', 'history', 'staff', 'shifts', 'warehouse', 'ops', 'logs'),
    'waiter'  => array('tables', 'schedule'),
    'cashier' => array('tables', 'ops', 'orders', 'cashshift', 'schedule'),
    'kitchen' => array('kitchen', 'schedule'),
);

$page = $_GET['page'] ?? 'login';
if (!is_string($page) || !in_array($page, $routes, true)) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo '404 - Khong tim thay trang.';
    exit;
}

$user = current_user();
if ($page !== 'login') {
    if (!$user) {
        redirect(url('login'));
    }
    $allowed = $rolePages[$user['roleKey']] ?? array();
    if (!in_array($page, $allowed, true)) {
        if (!$allowed) {
            http_response_code(403);
            exit('403 - Tai khoan khong co quyen truy cap.');
        }
        redirect(url($allowed[0]));
    }
} elseif ($user && !empty($rolePages[$user['roleKey']])) {
    redirect(url($rolePages[$user['roleKey']][0]));
}

// 1) action.php: xử lý form/redirect, đặt $title, $css, $scripts (chạy trước khi in HTML)
require __DIR__ . '/pages/' . $page . '/action.php';

// 2) in HTML: header -> giao diện trang -> footer
require __DIR__ . '/layout/header.php';
require __DIR__ . '/pages/' . $page . '/index.php';
require __DIR__ . '/layout/footer.php';
