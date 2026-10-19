<?php
require_once __DIR__ . '/config/init.php';
require_once __DIR__ . '/helpers/auth.php';

$routes = array(
    'login'      => 'Login.html',
    'tablemanage'=> 'TableManage.html',
    'menu'       => 'Menu.html',
    'revenue'    => 'Revenue.html',
    'history'    => 'History.html',
    'staff'      => 'Staff.html',
    'shifts'     => 'Shifts.html',
    'warehouse'  => 'Warehouse.html',
    'ops'        => 'Ops.html',
    'logs'       => 'Logs.html',
    'tables'     => 'Table.html',
    'schedule'   => 'Schedule.html',
    'orders'     => 'Orders.html',
    'cashshift'  => 'Shift.html',
    'kitchen'    => 'Kitchen.html',
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
