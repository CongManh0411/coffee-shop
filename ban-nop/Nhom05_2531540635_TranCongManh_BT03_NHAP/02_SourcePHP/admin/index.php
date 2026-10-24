<?php
require_once __DIR__ . '/includes/bootstrap.php';
header('Cache-Control: no-store, private');
header('Pragma: no-cache');

$routes = array(
    'login' => 'login/index.php',
    'tablemanage' => 'tablemanage/index.php',
    'menu' => 'menu/index.php',
    'revenue' => 'revenue/index.php',
    'history' => 'history/index.php',
    'staff' => 'staff/index.php',
    'shifts' => 'shifts/index.php',
    'warehouse' => 'warehouse/index.php',
    'ops' => '../../pages/ops/index.php',
    'logs' => 'logs/index.php',
    'settings' => 'settings/index.php',
);

$rolePages = array(
    'manager' => array('tablemanage', 'menu', 'revenue', 'history', 'staff', 'shifts', 'warehouse', 'ops', 'logs', 'settings'),
    'waiter' => array('tables', 'schedule'),
    'cashier' => array('tables', 'ops', 'orders', 'cashshift', 'schedule'),
    'kitchen' => array('kitchen', 'schedule'),
);

$page = $_GET['page'] ?? 'login';
if (is_string($page) && in_array($page, array('tables', 'schedule', 'orders', 'cashshift', 'kitchen'), true)) {
    redirect('../index.php?page=' . rawurlencode($page));
}
if (!is_string($page) || !isset($routes[$page])) {
    http_response_code(404);
    $page = '404';
    $file = __DIR__ . '/pages/404/index.php';
} else {
    $file = __DIR__ . '/pages/' . $routes[$page];
}
if (!is_file($file)) {
    http_response_code(404);
    $page = '404';
    $file = __DIR__ . '/pages/404/index.php';
    if (!is_file($file)) {
        header('Content-Type: text/plain; charset=utf-8');
        echo '404 - Khong tim thay trang.';
        exit;
    }
}

$user = current_user();
$isAdmin = true;
if ($user && $user['roleKey'] !== 'manager' && $page !== '404' && $page !== 'login') {
    $staffHome = $rolePages[$user['roleKey']][0] ?? null;
    if ($staffHome) {
        redirect('../index.php?page=' . rawurlencode($staffHome));
    }
}
if ($page !== '404' && $page !== 'login') {
    if (!$user) {
        redirect(admin_url('login'));
    }
    $allowed = $rolePages[$user['roleKey']] ?? array();
    if (!in_array($page, $allowed, true)) {
        if (!$allowed) {
            http_response_code(403);
            exit('403 - Tai khoan khong co quyen truy cap.');
        }
        redirect(admin_url($allowed[0]));
    }
} elseif ($page === 'login' && $user && !empty($rolePages[$user['roleKey']])) {
    $home = $rolePages[$user['roleKey']][0];
    redirect($user['roleKey'] === 'manager' ? admin_url($home) : '../index.php?page=' . rawurlencode($home));
}

$action = dirname($file) . '/action.php';
if (is_file($action)) {
    require $action;
}

require __DIR__ . '/layouts/header.php';
require $file;
require __DIR__ . '/layouts/footer.php';
