<?php
require_once __DIR__ . '/../../../functions.php';
$adminMenuItems = admin_menu_items();
$adminMenuCategories = db()->fetchAll('SELECT category_name FROM categories ORDER BY category_id');
$adminMenuGroups = array();
foreach ($adminMenuCategories as $category) {
    $adminMenuGroups[$category['category_name']] = array('category' => $category['category_name'], 'items' => array());
}
foreach ($adminMenuItems as $item) {
    $image = menu_item_image($item);
    if (strpos($image, 'assets/') === 0) $image = '../' . $image;
    $adminMenuGroups[$item['category_name']]['items'][] = array(
        'id' => (int) $item['item_id'], 'name' => $item['item_name'],
        'desc' => $item['description'] ?? '', 'price' => (int) $item['price'],
        'img' => $item['image'] ?? '', 'thumb' => $image,
        'hidden' => (int) $item['is_hidden'] === 1,
    );
}
$menuFormError = '';
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['save_menu_item'])) {
    $menuData = array(
        'category' => trim((string) ($_POST['category'] ?? '')),
        'newCategory' => trim((string) ($_POST['newCategory'] ?? '')),
        'name' => trim((string) ($_POST['name'] ?? '')),
        'desc' => trim((string) ($_POST['desc'] ?? '')),
        'price' => $_POST['price'] ?? '',
    );
    // Ảnh món: chọn file từ máy -> lưu vào assets/images/menu/, database chỉ giữ đường dẫn
    list($uploadedImage, $menuFormError) = menu_store_uploaded_image($_FILES['image_file'] ?? null);
    if ($uploadedImage !== null) $menuData['img'] = $uploadedImage;

    if ($menuFormError === '') {
        if (!empty($_POST['id'])) {
            $menuFormError = menu_update((int) $_POST['id'], $menuData);
        } else {
            list($createdId, $menuFormError) = menu_create($menuData);
        }
        if ($menuFormError !== '' && $uploadedImage !== null) menu_remove_image_file($uploadedImage);
    }

    if ($menuFormError === '') {
        $_SESSION['menu_saved'] = 'Đã lưu món vào cơ sở dữ liệu.';
        redirect(admin_url('menu'));
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
