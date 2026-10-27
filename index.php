<?php
require_once __DIR__ . '/admin/includes/bootstrap.php';

$routes = array(
    'home' => 'home/index.php',
    'public-menu' => 'public-menu/index.php',
    'menu' => 'menu/index.php',
    'menu-categories' => 'menu-categories/index.php',
    'menu-detail' => 'menu-detail/index.php',
    'tables' => 'tables/index.php',
    'schedule' => 'schedule/index.php',
    'ops' => 'ops/index.php',
    'orders' => 'orders/index.php',
    'cashshift' => 'cashshift/index.php',
    'kitchen' => 'kitchen/index.php',
);
$rolePages = array(
    'waiter' => array('tables', 'schedule'),
    'cashier' => array('tables', 'ops', 'orders', 'cashshift', 'schedule'),
    'kitchen' => array('kitchen', 'schedule'),
);
$user = current_user();
if ($user) header('Cache-Control: no-store, private');
$page = $_GET['page'] ?? null;

if ($page === null || $page === '') {
    if ($user) {
        if ($user['roleKey'] === 'manager') {
            redirect('admin/index.php?page=tablemanage');
        }
        $home = $rolePages[$user['roleKey']][0] ?? null;
        if ($home) {
            redirect('index.php?page=' . rawurlencode($home));
        }
        redirect('admin/index.php?page=login');
    }
    $page = 'home';
}

if ($page === 'login') {
    redirect('admin/index.php?page=login');
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
}

if (!in_array($page, array('404', 'home', 'public-menu', 'menu', 'menu-detail', 'menu-categories'), true)) {
    if (!$user) {
        redirect('admin/index.php?page=login');
    }
    if ($page === 'ops' && $user['roleKey'] === 'manager') {
        redirect('admin/index.php?page=ops');
    }
    $allowed = $rolePages[$user['roleKey']] ?? array();
    if (!in_array($page, $allowed, true)) {
        if (!$allowed) {
            http_response_code(403);
            exit('403 - Tai khoan khong co quyen truy cap.');
        }
        redirect('index.php?page=' . rawurlencode($allowed[0]));
    }
}

$isAdmin = false;
$action = dirname($file) . '/action.php';
if (is_file($action)) {
    require $action;
}
require __DIR__ . '/layouts/public/header.php';
require $file;
require __DIR__ . '/layouts/public/footer.php';
