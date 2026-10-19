<?php
require_once __DIR__ . '/config/init.php';
require_once __DIR__ . '/helpers/auth.php';

$routes = array(
    'login'      => 'pages/Login.php',
    'tablemanage'=> 'pages/TableManage.php',
    'menu'       => 'pages/Menu.php',
    'revenue'    => 'pages/Revenue.php',
    'history'    => 'pages/History.php',
    'staff'      => 'pages/Staff.php',
    'shifts'     => 'pages/Shifts.php',
    'warehouse'  => 'pages/Warehouse.php',
    'ops'        => 'pages/Ops.php',
    'logs'        => 'pages/Logs.php',
    'tables'     => 'pages/Table.php',
    'schedule'   => 'pages/Schedule.php',
    'orders'     => 'pages/Orders.php',
    'cashshift'  => 'pages/Shift.php',
    'kitchen'    => 'pages/Kitchen.php',
);

$rolePages = array(
    'manager' => array('tablemanage', 'menu', 'revenue', 'history', 'staff', 'shifts', 'warehouse', 'ops', 'logs'),
    'waiter'  => array('tables', 'schedule'),
    'cashier' => array('tables', 'ops', 'orders', 'cashshift', 'schedule'),
    'kitchen' => array('kitchen', 'schedule'),
);

$page = $_GET['page'] ?? 'login';
if (!is_string($page) || !isset($routes[$page])) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo '404 - Khong tim thay trang.';
    exit;
}

$user = current_user();
if ($page !== 'login') {
    if (!$user) {
        header('Location: index.php?page=login');
        exit;
    }
    $allowed = $rolePages[$user['roleKey']] ?? array();
    if (!in_array($page, $allowed, true)) {
        if (!$allowed) {
            http_response_code(403);
            exit('403 - Tai khoan khong co quyen truy cap.');
        }
        header('Location: index.php?page=' . rawurlencode($allowed[0]));
        exit;
    }
} elseif ($user && !empty($rolePages[$user['roleKey']])) {
    header('Location: index.php?page=' . rawurlencode($rolePages[$user['roleKey']][0]));
    exit;
}

require __DIR__ . '/' . $routes[$page];
