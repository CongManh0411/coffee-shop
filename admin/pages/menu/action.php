<?php
require_once __DIR__ . '/../../../functions.php';
$adminMenuItems = admin_menu_items();
$adminMenuCategories = db()->fetchAll('SELECT category_name FROM categories ORDER BY category_id');
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
// Bảng danh sách render bằng PHP; JavaScript bổ sung tìm kiếm và thao tác qua API.
$title = 'Thực đơn';
$css = array('menu.css');
$scripts = array('menu-data.js', 'inventory.js', 'menu.js');
