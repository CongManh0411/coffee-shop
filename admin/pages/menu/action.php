<?php
require_once __DIR__ . '/../../../functions.php';
$adminMenuItems = admin_menu_items(); // toàn bộ món: dùng cho dữ liệu JSON (sửa / ẩn)
$adminSearch = request_search('q');
$adminPager = paginate(admin_menu_count($adminSearch), request_page('p'), 10);
$adminPageItems = admin_menu_items($adminSearch, $adminPager['perPage'], $adminPager['offset']);
$adminMenuCategories = db()->fetchAll('SELECT category_name FROM categories ORDER BY category_id');
$adminCategoryRows = menu_category_list(); // cho khung "Quản lý danh mục"
$adminMenuGroups = array();
foreach ($adminMenuCategories as $category) {
    $adminMenuGroups[$category['category_name']] = array('category' => $category['category_name'], 'items' => array());
}
$adminMenuPageGroups = array();
foreach ($adminMenuCategories as $category) {
    $adminMenuPageGroups[$category['category_name']] = array('category' => $category['category_name'], 'items' => array());
}
$adminPageIds = array();
foreach ($adminPageItems as $pageItem) $adminPageIds[(int) $pageItem['item_id']] = true;
foreach ($adminMenuItems as $item) {
    $image = menu_item_image($item);
    if (strpos($image, 'assets/') === 0) $image = '../' . $image;
    $row = array(
        'id' => (int) $item['item_id'], 'name' => $item['item_name'],
        'desc' => $item['description'] ?? '', 'price' => (int) $item['price'],
        'img' => $item['image'] ?? '', 'thumb' => $image,
        'hidden' => (int) $item['is_hidden'] === 1,
    );
    $adminMenuGroups[$item['category_name']]['items'][] = $row;
    if (isset($adminPageIds[$row['id']])) $adminMenuPageGroups[$item['category_name']]['items'][] = $row;
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
    if (!post_csrf_ok()) {
        $menuFormError = 'Phiên bảo mật hết hạn. Hãy tải lại trang rồi thử lại.';
    }
    // Ảnh món: chọn file từ máy -> lưu vào assets/images/menu/, database chỉ giữ đường dẫn
    $uploadedImage = null;
    if ($menuFormError === '') list($uploadedImage, $menuFormError) = menu_store_uploaded_image($_FILES['image_file'] ?? null);
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
        redirect(admin_url('menu', array_filter(array('q' => $adminSearch, 'p' => $adminPager['page'] > 1 ? $adminPager['page'] : null))));
    }
}
// Ẩn/hiện và xóa món: form POST thường (có CSRF), xử lý bằng PHP rồi redirect.
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['menu_action'])) {
    $actionId = (int) ($_POST['id'] ?? 0);
    if (!post_csrf_ok()) {
        $menuFormError = 'Phiên bảo mật hết hạn. Hãy tải lại trang rồi thử lại.';
    } elseif ($_POST['menu_action'] === 'toggle_hide') {
        $menuFormError = menu_toggle_hidden($actionId);
        if ($menuFormError === '') $_SESSION['menu_saved'] = 'Đã cập nhật trạng thái hiển thị của món.';
    } elseif ($_POST['menu_action'] === 'rename_category') {
        $menuFormError = menu_category_rename((int) ($_POST['category_id'] ?? 0), $_POST['category_name'] ?? '');
        if ($menuFormError === '') $_SESSION['menu_saved'] = 'Đã đổi tên danh mục.';
    } elseif ($_POST['menu_action'] === 'delete_category') {
        $menuFormError = menu_category_delete((int) ($_POST['category_id'] ?? 0));
        if ($menuFormError === '') $_SESSION['menu_saved'] = 'Đã xóa danh mục.';
    } elseif ($_POST['menu_action'] === 'delete') {
        if (menu_in_open_order($actionId)) {
            $menuFormError = 'Không thể xóa món đang có trong đơn hàng chưa thanh toán.';
        } else {
            menu_delete($actionId);
            $_SESSION['menu_saved'] = 'Đã xóa món.';
        }
    } else {
        $menuFormError = 'Thao tác không hợp lệ.';
    }
    if ($menuFormError === '') {
        redirect(admin_url('menu', array_filter(array('q' => $adminSearch, 'p' => $adminPager['page'] > 1 ? $adminPager['page'] : null))));
    }
}
$menuSavedMessage = $_SESSION['menu_saved'] ?? '';
unset($_SESSION['menu_saved']);
// pages/menu/action.php — khai báo trang "Thực đơn"
// Chạy TRƯỚC khi Router in header, nên có thể redirect() được.
// Bảng danh sách render bằng PHP; JavaScript bổ sung tìm kiếm và thao tác qua API.
$title = 'Thực đơn';
$css = array('menu.css');
$scripts = array('menu.js', 'ajax-pager.js');
