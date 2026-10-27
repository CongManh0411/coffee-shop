<?php
require_once __DIR__ . '/../../../functions.php';
$adminMenuItems = admin_menu_items();
$menuFormError = '';
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['save_menu_item'])) {
    $menuData = array(
        'category' => trim((string) ($_POST['category'] ?? '')),
        'newCategory' => trim((string) ($_POST['newCategory'] ?? '')),
        'name' => trim((string) ($_POST['name'] ?? '')),
        'desc' => trim((string) ($_POST['desc'] ?? '')),
        'price' => $_POST['price'] ?? '',
    );
    if (!empty($_POST['img'])) $menuData['img'] = (string) $_POST['img'];

    if (!empty($_POST['id'])) {
        $menuFormError = menu_update((int) $_POST['id'], $menuData);
    } else {
        list($createdId, $menuFormError) = menu_create($menuData);
    }

    if ($menuFormError === '') {
        $_SESSION['menu_saved'] = 'Đã lưu món vào cơ sở dữ liệu.';
        redirect(url('menu'));
    }
}
$menuSavedMessage = $_SESSION['menu_saved'] ?? '';
unset($_SESSION['menu_saved']);
// pages/menu/action.php — khai báo trang "Thực đơn"
// Chạy TRƯỚC khi Router in header, nên có thể redirect() được.
// Trang này xử lý dữ liệu bằng JS gọi api/index.php nên chỉ cần khai báo thông tin trang.
$title = 'Thực đơn';
$css = array('menu.css');
$scripts = array('menu-data.js', 'inventory.js', 'menu.js');
