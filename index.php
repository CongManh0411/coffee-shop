<?php
require_once __DIR__ . '/admin/includes/bootstrap.php';
require_once __DIR__ . '/functions.php';

$routes = array(
    'home' => 'home/index.php',
    'public-menu' => 'public-menu/index.php',
    'menu-detail' => 'menu-detail/index.php',
    'tables' => 'tables/index.php',
    'schedule' => 'schedule/index.php',
    'ops' => 'ops/index.php',
    'orders' => 'orders/index.php',
    'cashshift' => 'cashshift/index.php',
    'kitchen' => 'kitchen/index.php',
    'notify' => 'notify/index.php',
    'handover' => 'handover/index.php',
);
$rolePages = array(
    'waiter' => array('tables', 'notify', 'schedule', 'handover'),
    'cashier' => array('tables', 'notify', 'ops', 'orders', 'cashshift', 'schedule', 'handover'),
    'kitchen' => array('kitchen', 'schedule', 'handover'),
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

// Trang Danh mục và trang Thực đơn cũ (page=menu) đã gộp vào public-menu: giữ link cũ hoạt động
if ($page === 'menu-categories' || $page === 'menu') {
    $menuRedirect = array('page' => 'public-menu');
    $oldQ = trim((string) ($_GET['q'] ?? ''));
    if ($oldQ !== '') $menuRedirect['q'] = $oldQ;
    $oldP = (int) ($_GET['p'] ?? 0);
    if ($oldP > 1) $menuRedirect['p'] = $oldP;
    // public-menu lọc theo tên danh mục, link cũ dùng mã danh mục
    $oldCategory = (int) ($_GET['category'] ?? 0);
    if ($oldCategory > 0) {
        try {
            foreach (public_menu_category_summary() as $c) {
                if ((int) $c['category_id'] === $oldCategory) $menuRedirect['category'] = $c['category_name'];
            }
        } catch (Throwable $e) {
        }
    }
    redirect('index.php?' . http_build_query($menuRedirect));
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

if (!in_array($page, array('404', 'home', 'public-menu', 'menu-detail'), true)) {
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

// Vào trang nhân viên là tự ghi GIỜ VÀO ca hôm nay (timesheet_clock_in giữ nguyên nếu đã có bản ghi)
if ($user && in_array($user['roleKey'], array('waiter', 'cashier', 'kitchen'), true)) {
    try { timesheet_clock_in((int) $user['staffId']); } catch (Throwable $e) { error_log($e); }
}
$isAdmin = false;
$action = dirname($file) . '/action.php';
if (is_file($action)) {
    require $action;
}
require __DIR__ . '/layouts/public/header.php';
require $file;
require __DIR__ . '/layouts/public/footer.php';
